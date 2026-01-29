<?php

namespace App\Services;

use App\Enums\AttendanceStatusType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AttendanceDocument;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceService
{
    public function __construct(
        protected HolidayService                        $holidayService,
        protected AttendanceLogRepositoryInterface      $attendanceLogRepository,
        protected AttendanceDocumentRepositoryInterface $attendanceDocumentRepository
    )
    {
    }

    public function createAndStorePdf(User $user, int $year, int $month): AttendanceDocument
    {
        $data = $this->getMonthlyAttendanceData($user, $year, $month);

        $pdf = Pdf::loadView('pdf.attendance-sheet', [
            'user' => $user,
            'days' => $data['days'],
            'year' => $year,
            'monthName' => $data['monthName'],
            'summaryStats' => $data['summaryStats'],
        ]);

        return $this->storePdfRaw(
            $year,
            $month,
            $data['filename'],
            $pdf->output(),
            $user
        );
    }

    public function getMonthlyAttendanceData(User $user, int $year, int $month): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $existingLogs = $this->attendanceLogRepository->getLogsForPeriod(
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString(),
            $user->id
        )->keyBy(fn($log) => $log->date->toDateString());

        $holidays = $this->holidayService->getHolidaysInRange($startOfMonth, $endOfMonth);

        $days = collect(CarbonPeriod::create($startOfMonth, $endOfMonth))->map(function (Carbon $date) use ($existingLogs, $holidays, $user) {
            $dateString = $date->toDateString();

            if ($existingLogs->has($dateString)) {
                $log = $existingLogs->get($dateString);

                if ($log->status === AttendanceStatusType::PRESENT && is_null($log->check_in)) {
                    $log->status = AttendanceStatusType::SCHEDULED;
                }
            } else {
                $status = AttendanceStatusType::OFF;

                if (isset($holidays[$dateString])) {
                    $status = AttendanceStatusType::HOLIDAY;
                } elseif ($date->isWeekend()) {
                    $status = AttendanceStatusType::WEEKEND;
                } elseif ($user->workSchedule && $user->workSchedule->isWorkday($date)) {
                    $status = AttendanceStatusType::SCHEDULED;
                }

                $log = new AttendanceLog(['user_id' => $user->id, 'date' => $date, 'status' => $status]);
            }

            if ($log->status === AttendanceStatusType::HOLIDAY) {
                $log->holiday_name = $holidays[$dateString]['name'] ?? __('Holiday');
            }

            return $log;
        });

        $summaryStats = [
            'present' => 0, 'vacation' => 0, 'sick_leave' => 0, 'home_office' => 0, 'total_worked_hours' => 0.0,
        ];

        foreach ($days as $day) {
            $summaryStats['total_worked_hours'] += $day->worked_hours ?? 0;
            match ($day->status) {
                AttendanceStatusType::PRESENT => $summaryStats['present']++,
                AttendanceStatusType::VACATION => $summaryStats['vacation']++,
                AttendanceStatusType::SICK_LEAVE => $summaryStats['sick_leave']++,
                AttendanceStatusType::HOME_OFFICE => $summaryStats['home_office']++,
                default => null,
            };
        }

        $safeName = Str::slug($user->name, '_');
        $filename = "{$safeName}_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return [
            'days' => $days,
            'summaryStats' => $summaryStats,
            'monthName' => $startOfMonth->translatedFormat('F'),
            'filename' => $filename,
        ];
    }

    protected function storePdfRaw(int $year, int $month, string $filename, string $pdfContent, User $user): AttendanceDocument
    {
        $periodDate = Carbon::create($year, $month, 1)->toDateString();
        $document = $this->attendanceDocumentRepository->findExisting($user->id, $periodDate);

        if (!$document) {
            /** @var AttendanceDocument $document */
            $document = $this->attendanceDocumentRepository->create([
                'user_id' => $user->id,
                'month'   => $periodDate,
                'status'  => 'generated',
            ]);
        }

        $document->addMediaFromStream($pdfContent)
            ->usingFileName($filename)
            ->toMediaCollection('signed_sheets');

        return $document;
    }

    public function generateLogForUser(User $user, Carbon $date): void
    {
        if ($this->holidayService->isHoliday($date)) {
            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::HOLIDAY
            );
            return;
        }

        $workSchedule = $user->workSchedule;
        if ($workSchedule && $workSchedule->isWorkday($date)) {
            $leaveRequest = $user->leaveRequests()
                ->where('status', LeaveStatus::APPROVED)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->first();

            if ($leaveRequest) {
                $attendanceStatus = match ($leaveRequest->type) {
                    LeaveType::VACATION => AttendanceStatusType::VACATION,
                    LeaveType::SICK => AttendanceStatusType::SICK_LEAVE,
                    LeaveType::HOME_OFFICE => AttendanceStatusType::HOME_OFFICE,
                    LeaveType::UNPAID => AttendanceStatusType::UNPAID,
                    default => AttendanceStatusType::OFF,
                };
                $this->attendanceLogRepository->updateOrCreateLog($user->id, $date->toDateString(), $attendanceStatus);
                return;
            }

            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::PRESENT,
                $workSchedule->getWorkHoursForDay($date)
            );
            return;
        }

        if ($date->isWeekend()) {
            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::WEEKEND
            );
            return;
        }

        $this->attendanceLogRepository->updateOrCreateLog(
            $user->id,
            $date->toDateString(),
            AttendanceStatusType::OFF
        );
    }

    protected function uploadToSharePoint(int $year, string $filename, string $content): void
    {
        try {
            // Megkeressük az első olyan felhasználót (Admint), akinek van élő Microsoft kapcsolata.
            $adminWithToken = User::whereHas('msGraphToken')->first();

            if ($adminWithToken) {
                // Elmentjük, ki van épp bejelentkezve (ha van)
                $previousUser = Auth::user();

                // Bejelentkeztetjük az Admint a kód futásának idejére.
                // A 'msgraph' driver az Auth::user()-t használja a token megtalálásához.
                Auth::login($adminWithToken);

                // A mentés
                $path = "Attendance/{$year}/{$filename}";
                Storage::disk('msgraph')->put($path, $content);

                Log::info("PDF feltöltve SharePointra: {$path}");

                // Visszaállítjuk az eredeti állapotot
                if ($previousUser) {
                    Auth::login($previousUser);
                } else {
                    Auth::logout();
                }
            } else {
                Log::warning("SharePoint feltöltés kihagyva: Nincs Microsofttal összekötött Admin felhasználó.");
            }

        } catch (\Exception $e) {
            // Ha hiba van, csak logoljuk, a fő folyamat nem áll meg.
            Log::error('SharePoint feltöltési hiba: ' . $e->getMessage());
        }
    }
}