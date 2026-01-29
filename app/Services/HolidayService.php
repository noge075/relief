<?php

namespace App\Services;

use App\Enums\SpecialDayType;
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

    public function isHoliday(CarbonInterface $date): bool
    {
        $dateStr = $date->format('Y-m-d');
        $specialDay = $this->specialDayRepository->findByDate($dateStr);

        if ($specialDay && $specialDay->type === SpecialDayType::WORKDAY) {
            return false;
        }

        if ($specialDay && $specialDay->type === SpecialDayType::HOLIDAY) {
            return true;
        }

        if ($date->isWeekend()) {
            return $this->isOfficialHoliday($date);
        }

        return $this->isOfficialHoliday($date);
    }

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
                    'type' => SpecialDayType::HOLIDAY
                ]
            ]);

        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        foreach ($specialDays as $day) {
            $dateStr = $day->date->format('Y-m-d');

            if ($day->type === SpecialDayType::HOLIDAY) {
                $holidays[$dateStr] = [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Special Holiday'),
                    'type' => SpecialDayType::HOLIDAY
                ];
            } elseif ($day->type === SpecialDayType::WORKDAY) {
                $holidays->forget($dateStr);
            }
        }

        return $holidays->all();
    }

    public function getExtraWorkdaysInRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $specialDays = $this->specialDayRepository->getInRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        return $specialDays
            ->where('type', SpecialDayType::WORKDAY)
            ->mapWithKeys(fn ($day) => [
                $day->date->format('Y-m-d') => [
                    'date' => $day->date,
                    'name' => $day->description ?? __('Extra Workday'),
                    'type' => SpecialDayType::WORKDAY
                ]
            ])
            ->all();
    }

    public function getRawSpecialDays(int $year, ?string $search = null, string $sortCol = 'date', bool $sortAsc = true): array
    {
        $spatieDays = $this->getSpatieHolidays($year)->map(fn ($h) => [
            'id' => null,
            'date' => $h['date']->format('Y-m-d'),
            'name' => $h['name'],
            'type' => SpecialDayType::HOLIDAY,
            'source' => 'auto',
        ]);

        $dbDays = $this->specialDayRepository->getByYear($year)->map(fn ($day) => [
            'id' => $day->id,
            'date' => $day->date->format('Y-m-d'),
            'name' => $day->description ?? ($day->type === SpecialDayType::HOLIDAY ? __('Special Holiday') : __('Extra Workday')),
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

    public function findSpecialDay(int $id): ?SpecialDay
    {
        /**
         * @var SpecialDay|null
         */
        return $this->specialDayRepository->find($id);
    }
}
