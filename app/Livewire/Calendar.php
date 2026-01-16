<?php

namespace App\Livewire;

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
        // ... (dátum ellenőrzés marad) ...

        $this->selectedDate = $dateStr;

        // FONTOS: Reseteljük a szerkesztést, mert ez új felvitel!
        $this->editingId = null;
        $this->requestType = 'vacation'; // Alapértelmezett érték visszaállítása

        $this->showRequestModal = true;
    }

    #[Computed]
    public function calendarDays()
    {
        $currentMonth = CarbonImmutable::parse($this->date);
        $startOfGrid = $currentMonth->startOfWeek(Carbon::MONDAY);

        // Demo ünnepnapok (Március 15, Május 1, Aug 20, Okt 23, Nov 1, Dec 25-26)
        $holidays = [
            '2025-03-15', '2025-05-01', '2025-08-20', '2025-10-23', '2025-11-01', '2025-12-25', '2025-12-26',
            '2026-01-01', '2026-03-15'
        ];

        // Demo Igénylések (Ezt majd a Repository-ból töltjük)
        $requests = [
            '2026-01-10' => ['type' => 'vacation', 'status' => 'approved'],
            '2026-01-20' => ['type' => 'home_office', 'status' => 'pending'],
            '2026-01-21' => ['type' => 'home_office', 'status' => 'pending'],
            '2026-01-24' => ['type' => 'sick', 'status' => 'approved'],
        ];

        $days = collect();

        for ($i = 0; $i < 42; $i++) {
            $day = $startOfGrid->addDays($i);
            $dateStr = $day->format('Y-m-d');

            $days->push([
                'date' => $day,
                'date_string' => $dateStr,
                'is_current_month' => $day->month === $currentMonth->month,
                'is_today' => $day->isToday(),
                'is_weekend' => $day->isWeekend(),
                'is_holiday' => in_array($dateStr, $holidays),
                // Adatok összefésülése
                'event' => $requests[$dateStr] ?? null,
            ]);
        }

        return $days;
    }

    #[Computed]
    public function monthlyStats()
    {
        // Gyors statisztika a footerhez
        $days = $this->calendarDays->where('is_current_month', true);

        return [
            'workdays' => $days->where('is_weekend', false)->where('is_holiday', false)->count(),
            'holidays' => $days->where('is_holiday', true)->count(),
            'requests' => $days->whereNotNull('event')->count(),
        ];
    }

    public function jumpToDate($year, $month)
    {
        // Összerakjuk az új dátumot (mindig elsejére)
        $this->date = CarbonImmutable::createFromDate($year, $month, 1)->format('Y-m-d');

        // Opcionális: itt lehetne bezárni a dropdown-t, de a Flux/Livewire ezt általában lekezeli az újrarajzolással.
    }

    // Segédmetódus a nézetnek, hogy tudjuk, épp melyik évet nézzük
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

        // ITT TÖLTENÉNK BE ADATBÁZISBÓL (Most csak demo)
        // $event = LeaveRequest::find($eventId);
        // $this->requestType = $event->type;
        $this->requestType = 'home_office'; // Demo: tegyük fel, hogy HO-t szerkesztünk

        $this->showRequestModal = true;
    }

    public function saveEvent()
    {
        if ($this->editingId) {
            // Update logika (Repository update)
        } else {
            // Create logika (Repository create)
        }

        $this->showRequestModal = false;
        // $this->dispatch('refresh-calendar'); // Ha lenne ilyen
    }

    public function deleteEvent($id)
    {
        // Delete logika
        $this->showRequestModal = false;
    }

    public function render()
    {
        return view('livewire.calendar');
    }
}