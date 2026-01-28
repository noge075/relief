<?php

namespace App\Livewire;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveRequestService;
use App\Services\HolidayService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

#[Lazy]
class Calendar extends Component
{
    public $date;
    public $selectedDate = null;
    public $endDate = null;
    public $showRequestModal = false;
    public $editingId = null;
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

    public function mount(): void
    {
        $this->date = CarbonImmutable::now()->startOfMonth()->format('Y-m-d');
        $this->dispatchDateRangeSelected();
    }

    private function dispatchDateRangeSelected(): void
    {
        $currentMonth = Carbon::parse($this->date);
        $this->dispatch('date-range-selected', 
            startDate: $currentMonth->startOfMonth()->format('Y-m-d'), 
            endDate: $currentMonth->endOfMonth()->format('Y-m-d')
        );
    }

    public function placeholder()
    {
        return view('livewire.placeholders.calendar');
    }

    public function nextMonth(): void
    {
        $this->date = CarbonImmutable::parse($this->date)->addMonth()->format('Y-m-d');
        $this->dispatchDateRangeSelected();
    }

    public function prevMonth(): void
    {
        $this->date = CarbonImmutable::parse($this->date)->subMonth()->format('Y-m-d');
        $this->dispatchDateRangeSelected();
    }

    public function jumpToToday(): void
    {
        $this->date = CarbonImmutable::now()->startOfMonth()->format('Y-m-d');
        $this->dispatchDateRangeSelected();
    }

    public function selectDate($dateStr): void
    {
        $this->selectedDate = $dateStr;
        $this->endDate = $dateStr;
        $this->editingId = null;
        $this->requestType = 'vacation'; 
        $this->reason = '';
        $this->showRequestModal = true;
    }

    #[Computed]
    public function calendarDays(): Collection
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
    public function monthlyStats(): array
    {
        $days = $this->calendarDays->where('is_current_month', true);

        return [
            'workdays' => $days->where('is_weekend', false)->where('is_holiday', false)->count(),
            'holidays' => $days->where('is_holiday', true)->count(),
            'requests' => $days->whereNotNull('event')->count(),
        ];
    }

    public function jumpToDate($year, $month): void
    {
        $this->date = CarbonImmutable::createFromDate($year, $month, 1)->format('Y-m-d');
        $this->dispatchDateRangeSelected();
    }

    #[Computed]
    public function currentYear(): int
    {
        return Carbon::parse($this->date)->year;
    }

    #[Computed]
    public function currentMonth(): int
    {
        return Carbon::parse($this->date)->month;
    }

    public function editEvent($eventId): void
    {
        $this->editingId = $eventId;
        $event = $this->leaveRequestRepository->find($eventId);
        
        if ($event && $event->user_id === auth()->id()) {
            $this->requestType = $event->type->value;
            $this->reason = $event->reason;
            $this->selectedDate = $event->start_date->format('Y-m-d');
            $this->endDate = $event->end_date->format('Y-m-d');
            $this->showRequestModal = true;
        }
    }

    public function saveEvent(LeaveRequestService $leaveRequestService): void
    {
        try {
            $this->validate([
                'requestType' => 'required',
                'selectedDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:selectedDate',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($this->editingId) {
                $request = $leaveRequestService->updateRequest(auth()->user(), $this->editingId, [
                    'type' => $this->requestType,
                    'start_date' => $this->selectedDate,
                    'end_date' => $this->endDate,
                    'reason' => $this->reason,
                ]);
            } else {
                $request = $leaveRequestService->createRequest(auth()->user(), [
                    'type' => $this->requestType,
                    'start_date' => $this->selectedDate,
                    'end_date' => $this->endDate,
                    'reason' => $this->reason,
                ]);
            }
            
            if ($request && $request->has_warning) {
                Flux::toast(__('Request submitted with warning: ') . $request->warning_message, variant: 'warning');
            } else {
                Flux::toast($this->editingId ? __('Request updated successfully.') : __('Request submitted successfully.'), variant: 'success');
            }
            
            $this->showRequestModal = false;
            $this->dispatch('leave-request-updated');
        } catch (ValidationException $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function deleteEvent($id, LeaveRequestService $leaveRequestService): void
    {
        try {
            $leaveRequestService->deleteRequest($id, auth()->id());
            Flux::toast(__('Request deleted.'), variant: 'success');
            $this->showRequestModal = false;
            $this->dispatch('leave-request-updated');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }
    
    #[Computed]
    public function canDelete(): bool
    {
        if (!$this->editingId) {
            return false;
        }
        
        $request = $this->leaveRequestRepository->find($this->editingId);
        return $request && $request->status === LeaveStatus::PENDING;
    }
    
    #[On('leave-request-updated')]
    public function refresh() {}

    public function render()
    {
        return view('livewire.calendar');
    }
}
