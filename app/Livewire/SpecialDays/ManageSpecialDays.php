<?php

namespace App\Livewire\SpecialDays;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Services\HolidayService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Lazy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class ManageSpecialDays extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    public $year;
    public $search = '';
    public $perPage = 10;
    
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

    protected $queryString = [
        'year' => ['except' => null],
        'search' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'date'],
        'sortAsc' => ['except' => true],
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
        
        $this->perPage = request()->query('per_page', 10);
    }

    public function placeholder()
    {
        return view('livewire.placeholders.manage-special-days');
    }

    public function updatedYear()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedPerPage()
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

        $existing = $this->holidayService->findSpecialDayByDate($this->date);
        if ($existing && $existing->id !== $this->editingId) {
            $this->addError('date', __('This date is already configured as a special day.'));
            return;
        }

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
        
        $currentPage = $this->getPage();
        $perPage = (int) $this->perPage;
        
        $currentItems = array_slice($allDays, ($currentPage - 1) * $perPage, $perPage);
        
        $paginatedDays = new LengthAwarePaginator($currentItems, count($allDays), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => [
                'year' => $this->year,
                'search' => $this->search,
                'per_page' => $this->perPage,
                'sortCol' => $this->sortCol,
                'sortAsc' => $this->sortAsc,
            ],
        ]);

        return view('livewire.special-days.manage-special-days', [
            'specialDays' => $paginatedDays
        ])->title(__('Manage Special Days'));
    }
}
