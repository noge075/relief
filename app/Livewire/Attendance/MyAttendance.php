<?php

namespace App\Livewire\Attendance;

use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Models\AttendanceLog;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\HolidayService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyAttendance extends Component
{
    use AuthorizesRequests;

    public $year;
    public $month;
    
    public $currentLog;

    // Editing
    public $showEditModal = false;
    public $editingDate;
    public $editingHours; // Csak megjelenítésre
    public $editingCheckIn;
    public $editingCheckOut;

    protected LeaveRequestRepositoryInterface $leaveRequestRepository;
    protected HolidayService $holidayService;

    public function boot(
        LeaveRequestRepositoryInterface $leaveRequestRepository,
        HolidayService $holidayService
    ) {
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->holidayService = $holidayService;
    }

    public function mount()
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->loadCurrentLog();
    }

    public function loadCurrentLog()
    {
        $this->currentLog = AttendanceLog::where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->whereNull('check_out')
            ->latest()
            ->first();
    }

    public function checkIn()
    {
        AttendanceLog::create([
            'user_id' => auth()->id(),
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
            'status' => 'present',
        ]);

        $this->loadCurrentLog();
        Flux::toast(__('Checked in successfully.'), variant: 'success');
    }

    public function checkOut()
    {
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

    public function editLog($dateStr)
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

    public function saveLog()
    {
        $this->validate([
            'editingCheckIn' => 'nullable|date_format:H:i',
            'editingCheckOut' => 'nullable|date_format:H:i|after:editingCheckIn',
        ]);

        $date = Carbon::parse($this->editingDate);
        
        // Check In/Out konvertálása datetime-ra
        $checkIn = $this->editingCheckIn ? $date->copy()->setTimeFromTimeString($this->editingCheckIn) : null;
        $checkOut = $this->editingCheckOut ? $date->copy()->setTimeFromTimeString($this->editingCheckOut) : null;

        // Automatikus számítás
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

    public function render()
    {
        $user = auth()->user();
        $start = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // 1. Tényleges logok
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        // 2. Szabadságok
        $leaveRequests = $this->leaveRequestRepository->getForUserInPeriod($user->id, $start->format('Y-m-d'), $end->format('Y-m-d'))
            ->where('status', LeaveStatus::APPROVED->value);

        // 3. Ünnepnapok
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        // 4. Munkarend
        $workSchedule = $user->workSchedule;
        $weeklyPattern = $workSchedule ? $workSchedule->weekly_pattern : null;

        // 5. Napok generálása
        $days = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $log = $logs->get($dateStr);
            
            // Alapértelmezett adatok
            $checkIn = null;
            $checkOut = null;
            $workedHours = null;
            $status = null;
            $statusType = null;
            
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);

            // Munkarend ellenőrzése
            $isScheduledWorkday = false;
            if ($weeklyPattern) {
                $dayName = strtolower($date->format('l')); // monday, tuesday...
                if (isset($weeklyPattern[$dayName]) && $weeklyPattern[$dayName] > 0) {
                    $isScheduledWorkday = true;
                }
            } else {
                // Ha nincs munkarend, akkor H-P munkanap
                $isScheduledWorkday = !$isWeekend;
            }
            
            // Ünnepnap felülírja a munkarendet (kivéve extra munkanap)
            if ($isHoliday && !$isExtraWorkday) {
                $isScheduledWorkday = false;
            }
            if ($isExtraWorkday) {
                $isScheduledWorkday = true;
            }

            // Ha van tényleges log, az felülír mindent
            if ($log) {
                $checkIn = $log->check_in ? $log->check_in->format('H:i') : '-';
                $checkOut = $log->check_out ? $log->check_out->format('H:i') : '-';
                $workedHours = $log->worked_hours;
                $status = __($log->status);
                $statusType = $log->status;
            } else {
                // Ha nincs log, akkor számolunk
                
                // Szabadság ellenőrzés
                $leave = null;
                foreach ($leaveRequests as $req) {
                    if ($date->between($req->start_date, $req->end_date)) {
                        $leave = $req;
                        break;
                    }
                }

                if ($leave) {
                    $status = ucfirst($leave->type->value);
                    $statusType = $leave->type->value;
                    
                    if ($leave->type === \App\Enums\LeaveType::HOME_OFFICE) {
                        $workedHours = 8; // Vagy a munkarend szerinti óra
                        $checkIn = '09:00';
                        $checkOut = '17:00';
                    }
                } elseif (!$isScheduledWorkday) {
                    $status = $isHoliday ? $holidays[$dateStr]['name'] : __('Weekend');
                    $statusType = $isHoliday ? 'holiday' : 'weekend';
                    
                    // Ha diák és nincs beosztva, akkor csak üres (vagy -)
                    if ($user->employment_type === EmploymentType::STUDENT && !$isHoliday) {
                        $status = '-';
                        $statusType = 'none';
                    }
                } else {
                    // Munkanap (Scheduled)
                    if ($user->employment_type === EmploymentType::STANDARD) {
                        $checkIn = '09:00';
                        $checkOut = '17:00';
                        $workedHours = $weeklyPattern ? $weeklyPattern[strtolower($date->format('l'))] : 8;
                        $status = __('Present');
                        $statusType = 'present';
                    } else {
                        // Órabéres/Diák: ha nincs log, de be volt osztva -> Hiányzás? Vagy csak üres.
                        // A specifikáció szerint "plusz jelenlét", tehát ha nem jön, az nem baj (kivéve ha fix nap).
                        // Jelöljük, hogy "Scheduled"?
                        $status = __('Scheduled');
                        $statusType = 'scheduled';
                    }
                }
            }

            $days[] = [
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'worked_hours' => $workedHours,
                'status' => $status,
                'status_type' => $statusType,
                'is_weekend' => $isWeekend,
                'is_today' => $date->isToday(),
                'is_holiday' => $isHoliday,
            ];
        }

        return view('livewire.attendance.my-attendance', [
            'days' => $days
        ])->title(__('Attendance'));
    }
}
