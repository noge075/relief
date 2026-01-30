<?php

namespace App\Livewire;

use App\Enums\PermissionType;
use App\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use App\Mail\BulkEmail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Repositories\Contracts\UserRepositoryInterface;
use Livewire\Attributes\Lazy;

#[Lazy]
class CompanyDirectory extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    public $search = '';
    public $departmentFilter = '';
    public $perPage = 12;
    public $showBulkEmailModal = false;
    public $selectedUserIds = [];
    public $emailSubject = '';
    public $emailBody = '';

    protected UserRepositoryInterface $userRepository;

    protected $queryString = [
        'search' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function boot(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function placeholder()
    {
        return view('livewire.placeholders.company-directory');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'departmentFilter']);
        $this->resetPage();
    }

    public function openBulkEmailModal()
    {
        $this->authorize(PermissionType::SEND_BULK_EMAILS->value);
        $this->reset(['selectedUserIds', 'emailSubject', 'emailBody']);
        $this->showBulkEmailModal = true;
    }

    public function toggleSelectAll()
    {
        $allUserIds = $this->userRepository->getAllActiveOrderedByName()->pluck('id')->toArray();

        if (count($this->selectedUserIds) === count($allUserIds)) {
            $this->selectedUserIds = [];
        } else {
            $this->selectedUserIds = $allUserIds;
        }
    }

    public function sendBulkEmail()
    {
        $this->authorize(PermissionType::SEND_BULK_EMAILS->value);

        $this->validate([
            'selectedUserIds' => 'required|array|min:1',
            'selectedUserIds.*' => 'exists:users,id',
            'emailSubject' => 'required|string|max:255',
            'emailBody' => 'required|string',
        ]);

        $recipients = $this->userRepository->getByIds($this->selectedUserIds);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->queue(new BulkEmail($this->emailSubject, $this->emailBody, auth()->user()));
        }

        Flux::toast(__('Emails sent successfully.'), variant: 'success');
        $this->showBulkEmailModal = false;
        $this->reset(['selectedUserIds', 'emailSubject', 'emailBody']);
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'department_id' => $this->departmentFilter,
            'status' => true,
        ];

        $users = $this->userRepository->getPaginated($this->perPage, $filters, 'last_name');

        $departments = Department::orderBy('name')->get();
        $allUsers = $this->userRepository->getAllActiveOrderedByName();

        return view('livewire.company-directory', [
            'users' => $users,
            'departments' => $departments,
            'allUsers' => $allUsers,
        ])->title(__('Company Directory'));
    }
}
