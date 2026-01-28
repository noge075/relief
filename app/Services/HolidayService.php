<?php

namespace App\Services;

use App\Models\SpecialDay;
use App\Repositories\Contracts\SpecialDayRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Holidays\Holidays;

class HolidayService
{
    protected const int CACHE_TTL = 86400;

    public function __construct(
        protected SpecialDayRepositoryInterface $specialDayRepository
    ) {}

    /**
     * Checks if a date is a designated holiday, ignoring weekends.
     */
    public function isHoliday(CarbonInterface $date): bool
    {
        $dateStr = $date->format('Y-m-d');
        $specialDay = $this->specialDayRepository->findByDate($dateStr);

        // An explicitly defined workday (like a Saturday) is NOT a holiday.
        if ($specialDay && $specialDay->type === 'workday') {
            return false;
        }

        // An explicitly defined holiday IS a holiday.
        if ($specialDay && $specialDay->type === 'holiday') {
            return true;
        }
        
        // If it's a weekend and no special rule applies, it's not a designated holiday.
        if ($date->isWeekend()) {
            // Check if Spatie considers it a holiday anyway (e.g., Christmas on a Sunday)
            // but only if there isn't a rule making it a workday.
            return $this->isOfficialHoliday($date);
        }

        // If no special day is defined, check the official calendar for the weekday.
        return $this->isOfficialHoliday($date);
    }

    /**
     * Visszaadja az összes ünnepnapot egy időszakra (DB + Hivatalos - Kivételek).
     */
    public function getHolidaysInRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $officialHolidays = collect();
        foreach (range($start->year, $end->year) as $year) {
            $officialHolidays = $officialHolidays->merge($this->getSpatieHolidays($year));
        }

        $holidays = $officialHolidays
            ->filter(fn ($h) => Carbon::parse($h['date'])->between($start, $end))
            ->mapWithKeys(fn ($h) => [
                $h['date']->format('Y-m-d') => [
                    'date' => $h['date'],
                    'name' => $h['name'],
                    'type' => 'holiday'
                ]
            ]);

        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        foreach ($specialDays as $day) {
            $dateStr = $day->date->format('Y-m-d');

            if ($day->type === 'holiday') {
                $holidays[$dateStr] = [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Special Holiday'),
                    'type' => 'holiday'
                ];
            } elseif ($day->type === 'workday') {
                $holidays->forget($dateStr);
            }
        }

        return $holidays->all();
    }

    /**
     * Visszaadja az áthelyezett munkanapokat (pl. szombatok).
     */
    public function getExtraWorkdaysInRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        return $specialDays
            ->where('type', 'workday')
            ->mapWithKeys(fn ($day) => [
                $day->date->format('Y-m-d') => [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Extra Workday'),
                    'type' => 'workday'
                ]
            ])
            ->all();
    }

    /**
     * Adminisztrációs nézethez adatok.
     */
    public function getRawSpecialDays(int $year, ?string $search = null, string $sortCol = 'date', bool $sortAsc = true): array
    {
        $spatieDays = $this->getSpatieHolidays($year)->map(fn ($h) => [
            'id' => null,
            'date' => $h['date']->format('Y-m-d'),
            'name' => $h['name'],
            'type' => 'holiday',
            'source' => 'auto',
        ]);

        $dbDays = $this->specialDayRepository->getByYear($year)->map(fn ($day) => [
            'id' => $day->id,
            'date' => $day->date->format('Y-m-d'),
            'name' => $day->description ?? ($day->type === 'holiday' ? __('Special Holiday') : __('Extra Workday')),
            'type' => $day->type,
            'source' => 'manual',
        ]);

        return $spatieDays->merge($dbDays)
            ->when($search, function ($collection) use ($search) {
                return $collection->filter(function ($item) use ($search) {
                    return str_contains(strtolower($item['name']), strtolower($search)) ||
                        str_contains($item['date'], $search);
                });
            })
            ->sortBy($sortCol, SORT_REGULAR, !$sortAsc)
            ->values()
            ->all();
    }


    /**
     * Cache-elt lekérdezés a Spatie Holidays csomagból.
     * Ez gyorsítja a folyamatot, ha sokszor hívjuk meg ugyanazt az évet.
     */
    protected function getSpatieHolidays(int $year): Collection
    {
        return Cache::remember("holidays_hu_{$year}", self::CACHE_TTL, function () use ($year) {
            try {
                $data = Holidays::for('hu')->get('hu', $year);
                return collect($data);
            } catch (\Throwable $e) {
                Log::error("Spatie Holidays hiba ({$year}): " . $e->getMessage());
                return collect();
            }
        });
    }

    protected function isOfficialHoliday(CarbonInterface $date): bool
    {
        return Holidays::for('hu')->isHoliday($date->toDateString());
    }

    public function createSpecialDay(array $data): SpecialDay
    {
        $this->clearCache($data['date'] ?? now());
        return $this->specialDayRepository->create($data);
    }

    public function updateSpecialDay(int $id, array $data): bool
    {
        $day = $this->specialDayRepository->find($id);
        if ($day) $this->clearCache($day->date);

        return $this->specialDayRepository->update($id, $data);
    }

    public function deleteSpecialDay(int $id): bool
    {
        $day = $this->specialDayRepository->find($id);
        if ($day) $this->clearCache($day->date);

        return $this->specialDayRepository->delete($id);
    }

    protected function clearCache($date): void
    {
        $year = $date instanceof CarbonInterface ? $date->year : Carbon::parse($date)->year;
        Cache::forget("holidays_hu_{$year}");
    }

    public function getSpecialDaysByYear(int $year)
    {
        return $this->specialDayRepository->getByYear($year);
    }

    public function findSpecialDay(int $id): ?SpecialDay
    {
        /**
         * @var SpecialDay $day
         */
        return $this->specialDayRepository->find($id);
    }
}
