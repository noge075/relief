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
    }

    public function export()
    {
        $this->authorize(PermissionType::VIEW_PAYROLL_DATA->value);
        
        $filename = 'payroll_report_' . $this->year . '_' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '.xlsx';
        
        return Excel::download(new MonthlyPayrollExport($this->year, $this->month), $filename);
    }

    public function render()
    {
        $report = $this->payrollService->getMonthlyReportData($this->year, $this->month);

        return view('livewire.payroll.monthly-report', [
            'report' => $report
        ])->title(__('Monthly Report'));
    }
}
