<?php

namespace App\Livewire;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyLeaveBalances extends Component
{
    use AuthorizesRequests;

    public $year;
    public $vacationBalance;
    public $vacationPending = 0;
    public $vacationApproved = 0;
    
    public $sickApproved = 0;
    public $sickPending = 0;

    protected LeaveBalanceRepositoryInterface $leaveBalanceRepository;
    protected LeaveRequestRepositoryInterface $leaveRequestRepository;

    public function boot(
        LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        LeaveRequestRepositoryInterface $leaveRequestRepository
    ) {
        $this->leaveBalanceRepository = $leaveBalanceRepository;
        $this->leaveRequestRepository = $leaveRequestRepository;
    }

    public function mount()
    {
        $this->year = Carbon::now()->year;
        $this->loadData();
    }

    #[On('leave-request-updated')]
    public function loadData()
    {
        $user = auth()->user();
        $startOfYear = Carbon::createFromDate($this->year, 1, 1)->format('Y-m-d');
        $endOfYear = Carbon::createFromDate($this->year, 12, 31)->format('Y-m-d');

        // 1. Szabadság keret
        $this->vacationBalance = $this->leaveBalanceRepository->getBalance($user->id, $this->year, LeaveType::VACATION->value);
        
        // Elfogadott (a balance used mezője tartalmazza)
        $this->vacationApproved = $this->vacationBalance ? $this->vacationBalance->used : 0;

        // Függő szabadságok lekérése
        $pendingVacationRequests = $this->leaveRequestRepository->getForUserInPeriod($user->id, $startOfYear, $endOfYear)
            ->where('type', LeaveType::VACATION)
            ->where('status', LeaveStatus::PENDING);
            
        $this->vacationPending = $pendingVacationRequests->sum('days_count');

        // 2. Betegszabadság (LeaveRequest-ekből számolva)
        $allSickRequests = $this->leaveRequestRepository->getForUserInPeriod($user->id, $startOfYear, $endOfYear)
            ->where('type', LeaveType::SICK);

        $this->sickApproved = $allSickRequests->where('status', LeaveStatus::APPROVED)->sum('days_count');
        $this->sickPending = $allSickRequests->where('status', LeaveStatus::PENDING)->sum('days_count');
    }

    public function render()
    {
        return view('livewire.my-leave-balances');
    }
}
