<?php

namespace App\Livewire\Settings;

use App\Enums\HomeOfficePolicyType;
use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\HomeOfficePolicy;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Lazy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class ManageHomeOfficePolicies extends Component
{
    use WithPagination;
    use AuthorizesRequests;
    use WithSorting;

    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;
    public $name = '';
    public $description = '';
    public $type = HomeOfficePolicyType::LIMITED;
    public $limit_days = 0;
    public $period_days = 0;

    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'name'],
        'sortAsc' => ['except' => true],
    ];

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_HOME_OFFICE_POLICIES->value);
        $this->sortCol = 'name';
        $this->perPage = request()->query('per_page', 10);
    }

    public function placeholder()
    {
        return view('livewire.placeholders.manage-home-office-policies');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $id;

        $policy = HomeOfficePolicy::find($id);

        $this->name = $policy->name;
        $this->description = $policy->description;
        $this->type = $policy->type;
        $this->limit_days = $policy->limit_days;
        $this->period_days = $policy->period_days;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::enum(HomeOfficePolicyType::class)],
            'limit_days' => 'required_if:type,' . HomeOfficePolicyType::LIMITED->value . '|integer|min:0',
            'period_days' => 'required_if:type,' . HomeOfficePolicyType::LIMITED->value . '|integer|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'limit_days' => $this->type === HomeOfficePolicyType::LIMITED ? $this->limit_days : 0,
            'period_days' => $this->type === HomeOfficePolicyType::LIMITED ? $this->period_days : 0,
        ];

        if ($this->isEditing) {
            HomeOfficePolicy::find($this->editingId)->update($data);
            Flux::toast(__('Home office policy updated successfully.'), variant: 'success');
        } else {
            HomeOfficePolicy::create($data);
            Flux::toast(__('Home office policy created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        HomeOfficePolicy::find($id)->delete();
        Flux::toast(__('Home office policy deleted.'), variant: 'danger');
    }

    private function resetForm()
    {
        $this->reset(['name', 'description', 'type', 'limit_days', 'period_days', 'editingId', 'isEditing']);
        $this->type = HomeOfficePolicyType::LIMITED;
    }

    public function render()
    {
        $query = HomeOfficePolicy::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        }

        $policies = $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                          ->paginate($this->perPage);

        $policies->appends([
            'search' => $this->search,
            'per_page' => $this->perPage,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.settings.manage-home-office-policies', [
            'policies' => $policies,
            'policyTypes' => HomeOfficePolicyType::cases(),
        ])->title(__('Manage Home Office Policies'));
    }
}
