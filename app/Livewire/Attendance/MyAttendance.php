<?php

namespace App\Livewire\Attendance;

use App\Enums\AttendanceStatusType;
use App\Models\AttendanceLog;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use App\Services\HolidayService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyAttendance extends Component
{
    use AuthorizesRequests;

    // State
    public int $year;
    public int $month;
    public bool $showEditModal = false;

    // Form State
    public $editingDate;
    public $editingHours;
    public $editingCheckIn;
    public $editingCheckOut;
    public ?AttendanceLog $editingLog = null;

    protected $validationAttributes = [
        'editingCheckIn' => 'Check In',
        'editingCheckOut' => 'Check Out',
    ];

    protected AttendanceLogRepositoryInterface $attendanceLogRepository;
    protected PayrollService $payrollService;
    protected HolidayService $holidayService;

    public function boot(
        AttendanceLogRepositoryInterface $attendanceLogRepository,
        PayrollService $payrollService,
        HolidayService $holidayService
    ) {
        $this->attendanceLogRepository = $attendanceLogRepository;
        $this->payrollService = $payrollService;
        $this->holidayService = $holidayService;
    }

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }


    #[Computed]
    public function selectedDate(): Carbon
    {
        return Carbon::createFromDate($this->year, $this->month, 1);
    }

    #[Computed]
    public function isMonthClosed(): bool
    {
        return $this->payrollService->isMonthClosed($this->year, $this->month);
    }

    #[Computed]
    public function days()
    {
        $start = $this->selectedDate->copy()->startOfMonth();
        $end = $this->selectedDate->copy()->endOfMonth();
        $user = auth()->user()->load('workSchedule');

        $logs = $this->attendanceLogRepository
            ->getLogsForPeriod($start->toDateString(), $end->toDateString(), $user->id)
            ->keyBy(fn ($log) => $log->date->toDateString());

        $holidays = $this->holidayService->getHolidaysInRange($start, $end);

        return collect(CarbonPeriod::create($start, $end))
            ->map(fn (Carbon $date) => $this->resolveDayLog($date, $logs, $holidays, $user));
    }

    public function jumpToCurrentMonth(): void
    {
        $this->fill(['year' => now()->year, 'month' => now()->month]);
    }

    public function jumpToPreviousMonth(): void
    {
        $date = $this->selectedDate->subMonthNoOverflow();
        $this->fill(['year' => $date->year, 'month' => $date->month]);
    }

    public function jumpToNextMonth(): void
    {
        $date = $this->selectedDate->addMonthNoOverflow();
        $this->fill(['year' => $date->year, 'month' => $date->month]);
    }

    public function downloadPdf(): void
    {
        $url = route('attendance.download-pdf', [
            'year' => $this->year,
            'month' => $this->month,
        ]);

        $this->dispatch('open-pdf-new-tab', url: $url);
    }

    public function editLog(string $dateStr): void
    {
        $date = Carbon::parse($dateStr);
        $log = $this->attendanceLogRepository->getLogsForPeriod($dateStr, $dateStr, auth()->id())->first();

        if (!$log) {
            $schedule = auth()->user()->workSchedule;
            $status = ($schedule && $schedule->isWorkday($date))
                ? AttendanceStatusType::SCHEDULED
                : AttendanceStatusType::OFF;

            $log = new AttendanceLog(['date' => $date, 'status' => $status]);
        }

        $this->editingLog = $log;
        $this->editingDate = $dateStr;

        if ($log->exists) {
            $this->editingHours = $log->worked_hours;
            $this->editingCheckIn = $log->check_in?->format('H:i');
            $this->editingCheckOut = $log->check_out?->format('H:i');
        } else {
            $this->applyWorkScheduleDefaults($date);
        }

        $this->showEditModal = true;
    }

    public function saveLog(): void
    {
        $date = Carbon::parse($this->editingDate);
        $logToCheck = $this->attendanceLogRepository->getLogsForPeriod(
            $this->editingDate,
            $this->editingDate,
            auth()->id()
        )->first();

        if (!$logToCheck) {
            $schedule = auth()->user()->workSchedule;
            $status = ($schedule && $schedule->isWorkday($date))
                ? AttendanceStatusType::SCHEDULED
                : AttendanceStatusType::OFF;

            $logToCheck = new AttendanceLog([
                'user_id' => auth()->id(),
                'date' => $date,
                'status' => $status
            ]);
        }

        if (!$this->canEditLog($logToCheck)) {
            Flux::toast(__('You cannot edit this day.'), variant: 'danger');
            $this->showEditModal = false;
            return;
        }

        try {
            $this->validateLogData();
        } catch (ValidationException $e) {
            Flux::toast(__('Check-in/out times must be strictly within work schedule.'), variant: 'danger');
            throw $e;
        }

        $checkIn = $this->editingCheckIn ? $date->copy()->setTimeFromTimeString($this->editingCheckIn) : null;
        $checkOut = $this->editingCheckOut ? $date->copy()->setTimeFromTimeString($this->editingCheckOut) : null;
        $workedHours = ($checkIn && $checkOut) ? $checkIn->floatDiffInHours($checkOut) : 0;

        $this->attendanceLogRepository->updateOrCreateLog(
            auth()->id(),
            $this->editingDate,
            AttendanceStatusType::PRESENT,
            $workedHours,
            $checkIn,
            $checkOut
        );

        Flux::toast(__('Attendance updated successfully.'), variant: 'success');
        $this->showEditModal = false;
    }

    private function resolveDayLog(Carbon $date, $existingLogs, $holidays, $user): AttendanceLog
    {
        $dateStr = $date->toDateString();

        if ($existingLogs->has($dateStr)) {
            $log = $existingLogs->get($dateStr);
            if ($log->status === AttendanceStatusType::PRESENT && !$log->check_in) {
                $log->status = AttendanceStatusType::SCHEDULED;
            }
            return $log;
        }

        $status = match(true) {
            isset($holidays[$dateStr]) => AttendanceStatusType::HOLIDAY,
            $date->isWeekend() => AttendanceStatusType::WEEKEND,
            $user->workSchedule?->isWorkday($date) => AttendanceStatusType::SCHEDULED,
            default => AttendanceStatusType::OFF,
        };

        $log = new AttendanceLog(['user_id' => $user->id, 'date' => $date, 'status' => $status]);

        if ($status === AttendanceStatusType::HOLIDAY) {
            $log->holiday_name = $holidays[$dateStr]['name'] ?? __('Holiday');
        }

        return $log;
    }

    private function applyWorkScheduleDefaults(Carbon $date): void
    {
        $schedule = auth()->user()->workSchedule;

        if ($schedule && $schedule->isWorkday($date)) {
            $this->editingCheckIn = $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : null;
            $this->editingCheckOut = $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : null;
        } else {
            $this->reset(['editingHours', 'editingCheckIn', 'editingCheckOut']);
        }
    }

    private function validateLogData(): void
    {
        $schedule = auth()->user()->workSchedule;

        $start = $schedule?->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : '00:00';
        $end = $schedule?->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : '23:59';

        $this->validate([
            'editingCheckIn' => [
                'nullable',
                'date_format:H:i',
                "after_or_equal:{$start}"
            ],
            'editingCheckOut' => [
                'nullable',
                'date_format:H:i',
                "before_or_equal:{$end}",
                'after:editingCheckIn'
            ],
        ], [
            'editingCheckIn.after_or_equal' => __("Too early. Work starts at :time.", ['time' => $start]),
            'editingCheckOut.before_or_equal' => __("Too late. Work ends at :time.", ['time' => $end]),
        ]);
    }

    public function canEditLog(?AttendanceLog $log): bool
    {
        if (!$log || empty($log->date)) return false;
        $date = $log->date instanceof Carbon ? $log->date : Carbon::parse($log->date);

        if ($this->payrollService->isMonthClosed($date->year, $date->month)) {
            return false;
        }

        if (auth()->user()->hasRole('super-admin')) return true;

        return in_array($log->status, [
            AttendanceStatusType::PRESENT,
            AttendanceStatusType::SCHEDULED,
            AttendanceStatusType::OFF
        ]);
    }

    public function render()
    {
        return view('livewire.attendance.my-attendance', [
            'days' => $this->days
        ])->title(__('Attendance'));
    }
}
