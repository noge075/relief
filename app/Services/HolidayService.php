<?php

namespace App\Services;

use App\Models\SpecialDay;
use App\Repositories\Contracts\SpecialDayRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Spatie\Holidays\Holidays;

class HolidayService
{
    public function __construct(
        protected SpecialDayRepositoryInterface $specialDayRepository
    ) {}

    /**
     * Ellenőrzi, hogy a megadott dátum munkaszüneti nap-e.
     * (Hétvége, hivatalos ünnep, vagy áthelyezett pihenőnap)
     * Kivéve, ha áthelyezett munkanap.
     */
    public function isHoliday(CarbonInterface $date): bool
    {
        $dateStr = $date->format('Y-m-d');

        // 1. Megnézzük az adatbázist (kivételek)
        $specialDay = $this->specialDayRepository->findByDate($dateStr);

        if ($specialDay) {
            if ($specialDay->type === 'holiday') {
                return true; // Explicit pihenőnap (pl. híd nap)
            }
            if ($specialDay->type === 'workday') {
                return false; // Explicit munkanap (pl. szombati ledolgozás)
            }
        }

        // 2. Spatie Holidays (Hivatalos ünnepek)
        try {
            $holidays = Holidays::for('hu')->get('hu', $date->year);
            foreach ($holidays as $holiday) {
                if ($holiday['date']->format('Y-m-d') === $dateStr) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        // 3. Hétvége ellenőrzés
        return $date->isWeekend();
    }

    /**
     * Visszaadja az összes ünnepnapot és pihenőnapot egy időszakra.
     * (A hétvégéket nem teszi bele külön, csak ha explicit holiday)
     */
    public function getHolidaysInRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $holidays = [];

        // 1. Spatie Holidays
        $years = range($start->year, $end->year);
        foreach ($years as $year) {
            try {
                $spatieHolidays = Holidays::for('hu')->get('hu', $year);
                foreach ($spatieHolidays as $holiday) {
                    $hDate = Carbon::instance($holiday['date']);
                    if ($hDate->between($start, $end)) {
                        $holidays[$hDate->format('Y-m-d')] = [
                            'date' => $hDate,
                            'name' => $holiday['name'],
                            'type' => 'holiday'
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Adatbázis kivételek
        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        foreach ($specialDays as $day) {
            if ($day->type === 'holiday') {
                $holidays[$day->date->format('Y-m-d')] = [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Special Holiday'),
                    'type' => 'holiday'
                ];
            } elseif ($day->type === 'workday') {
                unset($holidays[$day->date->format('Y-m-d')]);
            }
        }

        return $holidays;
    }
    
    /**
     * Visszaadja az áthelyezett munkanapokat (pl. szombatok).
     */
    public function getExtraWorkdaysInRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $workdays = [];
        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        foreach ($specialDays as $day) {
            if ($day->type === 'workday') {
                $workdays[$day->date->format('Y-m-d')] = [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Extra Workday'),
                    'type' => 'workday'
                ];
            }
        }
        
        return $workdays;
    }

    /**
     * Visszaadja a nyers adatokat (Spatie + DB) adminisztrációhoz.
     */
    public function getRawSpecialDays(int $year): array
    {
        $days = [];

        // 1. Spatie
        try {
            $spatie = Holidays::for('hu')->get('hu', $year);
            
            foreach ($spatie as $h) {
                $days[$h['date']->format('Y-m-d')] = [
                    'id' => null,
                    'date' => $h['date']->format('Y-m-d'),
                    'name' => $h['name'],
                    'type' => 'holiday',
                    'source' => 'auto',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Spatie Holidays Error: ' . $e->getMessage());
        }

        // 2. DB
        $dbDays = $this->specialDayRepository->getByYear($year);
        foreach ($dbDays as $day) {
            $days[$day->date->format('Y-m-d')] = [
                'id' => $day->id,
                'date' => $day->date->format('Y-m-d'),
                'name' => $day->description ?? ($day->type === 'holiday' ? __('Special Holiday') : __('Extra Workday')),
                'type' => $day->type,
                'source' => 'manual',
            ];
        }

        ksort($days);
        return $days;
    }

    // --- CRUD Műveletek ---

    public function getSpecialDaysByYear(int $year)
    {
        return $this->specialDayRepository->getByYear($year);
    }

    public function findSpecialDay(int $id): ?SpecialDay
    {
        return $this->specialDayRepository->find($id);
    }

    public function findSpecialDayByDate(string $date): ?SpecialDay
    {
        return $this->specialDayRepository->findByDate($date);
    }

    public function createSpecialDay(array $data): SpecialDay
    {
        return $this->specialDayRepository->create($data);
    }

    public function updateSpecialDay(int $id, array $data): bool
    {
        return $this->specialDayRepository->update($id, $data);
    }

    public function deleteSpecialDay(int $id): bool
    {
        return $this->specialDayRepository->delete($id);
    }
}
