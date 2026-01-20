<?php

namespace App\Livewire\Payroll;

use App\Enums\PermissionType;
use App\Exports\MonthlyPayrollExport;
use App\Services\PayrollService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyReport extends Component
{
    use AuthorizesRequests;

    public $year;
    public $month;
    
    public $closure; // MonthlyClosure modell

    protected PayrollService $payrollService;

    public function boot(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_PAYROLL_DATA->value);
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->loadClosureStatus();
    }
    
    public function updatedYear() { $this->loadClosureStatus(); }
    public function updatedMonth() { $this->loadClosureStatus(); }

    public function loadClosureStatus()
    {
        $this->closure = $this->payrollService->getClosureStatus($this->year, $this->month);
    }

    public function export()
    {
        $this->authorize(PermissionType::VIEW_PAYROLL_DATA->value);
        
        $filename = 'payroll_report_' . $this->year . '_' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '.xlsx';
        
        return Excel::download(new MonthlyPayrollExport($this->year, $this->month), $filename);
    }
    
    public function closeMonth()
    {
        $this->authorize(PermissionType::MANAGE_MONTHLY_CLOSURES->value);
        
        $this->payrollService->closeMonth($this->year, $this->month, auth()->user());
        $this->loadClosureStatus();
        
        Flux::toast(__('Month closed successfully.'), variant: 'success');
    }
    
    public function reopenMonth()
    {
        $this->authorize(PermissionType::MANAGE_MONTHLY_CLOSURES->value);
        
        $this->payrollService->reopenMonth($this->year, $this->month, auth()->user());
        $this->loadClosureStatus();
        
        Flux::toast(__('Month reopened successfully.'), variant: 'success');
    }

    public function render()
    {
        $report = $this->payrollService->getMonthlyReportData($this->year, $this->month);

        return view('livewire.payroll.monthly-report', [
            'report' => $report
        ])->title(__('Monthly Report'));
    }
}
