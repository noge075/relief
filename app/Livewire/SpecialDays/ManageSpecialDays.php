<?php

namespace App\Livewire\SpecialDays;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Services\HolidayService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageSpecialDays extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    public $year;
    public $search = '';
    
    // Form
    public $showModal = false;
    public $editingId = null;
    public $date;
    public $type = 'holiday';
    public $description = '';

    protected HolidayService $holidayService;

    protected $rules = [
        'date' => 'required|date',
        'type' => 'required|in:holiday,workday',
        'description' => 'nullable|string|max:255',
    ];

    public function boot(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->year = Carbon::now()->year;
        $this->sortCol = 'date';
    }

    public function updatedYear()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['editingId', 'date', 'type', 'description']);
        $this->type = 'holiday';
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $day = $this->holidayService->findSpecialDay($id);
        if ($day) {
            $this->editingId = $day->id;
            $this->date = $day->date->format('Y-m-d');
            $this->type = $day->type;
            $this->description = $day->description;
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->validate();

        // Ellenőrizzük, hogy van-e már ilyen dátum (ha új, vagy ha dátumot módosítunk)
        $existing = $this->holidayService->findSpecialDayByDate($this->date);
        if ($existing && $existing->id !== $this->editingId) {
            $this->addError('date', __('This date is already configured as a special day.'));
            return;
        }

        // Rendszer ünnep ütközés ellenőrzése (itt most egyszerűsítve, vagy újra implementálva)
        // ... (a korábbi logikát ide kellene másolni, vagy a service-be szervezni)

        $data = [
            'date' => $this->date,
            'type' => $this->type,
            'description' => $this->description,
        ];

        if ($this->editingId) {
            $this->holidayService->updateSpecialDay($this->editingId, $data);
        } else {
            $this->holidayService->createSpecialDay($data);
        }

        Flux::toast(__('Special day saved successfully.'), variant: 'success');
        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->holidayService->deleteSpecialDay($id);
        Flux::toast(__('Special day deleted.'), variant: 'success');
    }

    public function render()
    {
        $allDays = $this->holidayService->getRawSpecialDays((int) $this->year, $this->search, $this->sortCol, $this->sortAsc);
        
        // Manuális pagináció
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($allDays, ($currentPage - 1) * $perPage, $perPage);
        
        $paginatedDays = new LengthAwarePaginator($currentItems, count($allDays), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('livewire.special-days.manage-special-days', [
            'specialDays' => $paginatedDays
        ])->title(__('Manage Special Days'));
    }
}
