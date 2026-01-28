<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatusType;
use App\Models\AttendanceLog;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use App\Services\HolidayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceLogRepositoryInterface $attendanceLogRepository,
        private HolidayService $holidayService
    ) {}

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $existingLogs = $this->attendanceLogRepository->getLogsForPeriod(
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString(),
            $user->id
        )->keyBy(fn ($log) => $log->date->toDateString());

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

        $data = [
            'user' => $user,
            'days' => $days,
            'year' => $year,
            'monthName' => $startOfMonth->translatedFormat('F'),
            'summaryStats' => $summaryStats,
        ];

        $pdf = Pdf::loadView('pdf.attendance-sheet', $data);

        // Generate the correct filename
        $safeName = Str::slug($user->name, '_');
        $filename = "{$safeName}_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($filename);
    }
}
