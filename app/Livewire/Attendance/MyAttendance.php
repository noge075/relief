<?php

namespace App\Livewire\Attendance;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyAttendance extends Component
{
    use AuthorizesRequests;

    public $year;
    public $month;
    public $currentLog;
    public $showEditModal = false;
    public $editingDate;
    public $editingHours;
    public $editingCheckIn;
    public $editingCheckOut;

    protected AttendanceService $attendanceService;
    protected PayrollService $payrollService;

    public function boot(
        AttendanceService $attendanceService,
        PayrollService $payrollService
    ) {
        $this->attendanceService = $attendanceService;
        $this->payrollService = $payrollService;
    }

    public function mount(): void
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->loadCurrentLog();
    }

    public function loadCurrentLog(): void
    {
        $this->currentLog = AttendanceLog::where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->whereNull('check_out')
            ->latest()
            ->first();
    }

    public function checkIn(): void
    {
        $this->validateMonthlyClosure(Carbon::today());

        AttendanceLog::create([
            'user_id' => auth()->id(),
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
            'status' => 'present',
        ]);

        $this->loadCurrentLog();
        Flux::toast(__('Checked in successfully.'), variant: 'success');
    }

    public function checkOut(): void
    {
        $this->validateMonthlyClosure(Carbon::today());

        if ($this->currentLog) {
            $checkOut = Carbon::now();
            $workedHours = $this->currentLog->check_in->diffInHours($checkOut);

            $this->currentLog->update([
                'check_out' => $checkOut,
                'worked_hours' => $workedHours,
            ]);
            
            $this->loadCurrentLog();
            Flux::toast(__('Checked out successfully.'), variant: 'success');
        }
    }

    public function editLog($dateStr): void
    {
        $this->editingDate = $dateStr;
        $log = AttendanceLog::where('user_id', auth()->id())
            ->where('date', $dateStr)
            ->first();

        if ($log) {
            $this->editingHours = $log->worked_hours;
            $this->editingCheckIn = $log->check_in ? $log->check_in->format('H:i') : null;
            $this->editingCheckOut = $log->check_out ? $log->check_out->format('H:i') : null;
        } else {
            $this->editingHours = null;
            $this->editingCheckIn = null;
            $this->editingCheckOut = null;
        }

        $this->showEditModal = true;
    }

    public function saveLog(): void
    {
        $date = Carbon::parse($this->editingDate);
        $this->validateMonthlyClosure($date);

        $this->validate([
            'editingCheckIn' => 'nullable|date_format:H:i',
            'editingCheckOut' => 'nullable|date_format:H:i|after:editingCheckIn',
        ]);

        $checkIn = $this->editingCheckIn ? $date->copy()->setTimeFromTimeString($this->editingCheckIn) : null;
        $checkOut = $this->editingCheckOut ? $date->copy()->setTimeFromTimeString($this->editingCheckOut) : null;

        $workedHours = 0;
        if ($checkIn && $checkOut) {
            $workedHours = $checkIn->floatDiffInHours($checkOut);
        }

        AttendanceLog::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'date' => $this->editingDate,
            ],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'worked_hours' => $workedHours,
                'status' => 'present',
            ]
        );

        Flux::toast(__('Attendance updated successfully.'), variant: 'success');
        $this->showEditModal = false;
        $this->loadCurrentLog();
    }

    protected function validateMonthlyClosure(Carbon $date): void
    {
        if ($this->payrollService->isMonthClosed($date->year, $date->month)) {
            throw ValidationException::withMessages([
                'date' => __('This month is closed and cannot be modified.')
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.attendance.my-attendance', [
            'days' => $this->attendanceService->getAttendanceData(auth()->user(), $this->year, $this->month)
        ])->title(__('Attendance'));
    }
}
