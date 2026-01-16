<?php

namespace App\Livewire;

use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveRequestService;
use App\Services\HolidayService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class Calendar extends Component
{
    public $date;

    // Modal vezérléshez
    public $selectedDate = null;
    public $showRequestModal = false;
    public $editingId = null;
    
    // Form mezők
    public $requestType = 'vacation';
    public $reason = '';

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
        $this->date = CarbonImmutable::now()->startOfMonth()->format('Y-m-d');
    }

    // --- Navigáció ---
    public function nextMonth()
    {
        $this->date = CarbonImmutable::parse($this->date)->addMonth()->format('Y-m-d');
    }

    public function prevMonth()
    {
        $this->date = CarbonImmutable::parse($this->date)->subMonth()->format('Y-m-d');
    }

    public function jumpToToday()
    {
        $this->date = CarbonImmutable::now()->startOfMonth()->format('Y-m-d');
    }

    // --- Interakció ---
    public function selectDate($dateStr)
    {
        $this->selectedDate = $dateStr;
        $this->editingId = null;
        $this->requestType = 'vacation'; 
        $this->reason = '';
        $this->showRequestModal = true;
    }

    #[Computed]
    public function calendarDays()
    {
        $currentMonth = CarbonImmutable::parse($this->date);
        $startOfGrid = $currentMonth->startOfWeek(Carbon::MONDAY);
        $endOfGrid = $startOfGrid->copy()->addDays(41);

        // Ünnepnapok és extra munkanapok lekérése
        $holidays = $this->holidayService->getHolidaysInRange($startOfGrid, $endOfGrid);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($startOfGrid, $endOfGrid);

        // Adatbázis lekérdezés
        $dbRequests = $this->leaveRequestRepository->getForUserInPeriod(
            auth()->id(),
            $startOfGrid->format('Y-m-d'),
            $endOfGrid->format('Y-m-d')
        );

        // Mapelés dátumra
        $requestsByDate = [];
        foreach ($dbRequests as $req) {
            $period = Carbon::parse($req->start_date)->daysUntil($req->end_date);
            foreach ($period as $date) {
                $requestsByDate[$date->format('Y-m-d')] = $req;
            }
        }

        $days = collect();

        for ($i = 0; $i < 42; $i++) {
            $day = $startOfGrid->addDays($i);
            $dateStr = $day->format('Y-m-d');
            
            // Ünnepnap logika:
            // 1. Explicit ünnepnap (Spatie vagy DB holiday)
            // 2. Hétvége, KIVÉVE ha explicit munkanap (DB workday)
            $isExplicitHoliday = isset($holidays[$dateStr]);
            $isExplicitWorkday = isset($extraWorkdays[$dateStr]);
            
            $isHoliday = $isExplicitHoliday || ($day->isWeekend() && !$isExplicitWorkday);

            $days->push([
                'date' => $day,
                'date_string' => $dateStr,
                'is_current_month' => $day->month === $currentMonth->month,
                'is_today' => $day->isToday(),
                'is_weekend' => $day->isWeekend(),
                'is_holiday' => $isHoliday,
                'holiday_name' => $isExplicitHoliday ? $holidays[$dateStr]['name'] : ($isExplicitWorkday ? $extraWorkdays[$dateStr]['name'] : null),
                'event' => $requestsByDate[$dateStr] ?? null,
            ]);
        }

        return $days;
    }

    #[Computed]
    public function monthlyStats()
    {
        $days = $this->calendarDays->where('is_current_month', true);

        return [
            'workdays' => $days->where('is_holiday', false)->count(),
            'holidays' => $days->where('is_holiday', true)->count(),
            'requests' => $days->whereNotNull('event')->count(),
        ];
    }

    public function jumpToDate($year, $month)
    {
        $this->date = CarbonImmutable::createFromDate($year, $month, 1)->format('Y-m-d');
    }

    #[Computed]
    public function currentYear()
    {
        return Carbon::parse($this->date)->year;
    }

    #[Computed]
    public function currentMonth()
    {
        return Carbon::parse($this->date)->month;
    }

    public function editEvent($eventId)
    {
        $this->editingId = $eventId;
        $event = $this->leaveRequestRepository->find($eventId);
        
        if ($event && $event->user_id === auth()->id()) {
            $this->requestType = $event->type->value;
            $this->reason = $event->reason;
            $this->selectedDate = $event->start_date->format('Y-m-d'); // Csak a kezdő dátumot kezeljük most
            $this->showRequestModal = true;
        }
    }

    public function saveEvent(LeaveRequestService $leaveRequestService)
    {
        try {
            $this->validate([
                'requestType' => 'required',
                'selectedDate' => 'required|date',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($this->editingId) {
                $leaveRequestService->updateRequest(auth()->user(), $this->editingId, [
                    'type' => $this->requestType,
                    'start_date' => $this->selectedDate,
                    'end_date' => $this->selectedDate, // Egyelőre 1 napos
                    'reason' => $this->reason,
                ]);
                Flux::toast(__('Request updated successfully.'), variant: 'success');
            } else {
                $leaveRequestService->createRequest(auth()->user(), [
                    'type' => $this->requestType,
                    'start_date' => $this->selectedDate,
                    'end_date' => $this->selectedDate, // Egyelőre 1 napos
                    'reason' => $this->reason,
                ]);
                Flux::toast(__('Request submitted successfully.'), variant: 'success');
            }
            
            $this->showRequestModal = false;
        } catch (ValidationException $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function deleteEvent($id, LeaveRequestService $leaveRequestService)
    {
        try {
            $leaveRequestService->deleteRequest($id, auth()->id());
            Flux::toast(__('Request deleted.'), variant: 'success');
            $this->showRequestModal = false;
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.calendar');
    }
}
