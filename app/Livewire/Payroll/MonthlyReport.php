<?php

namespace App\Livewire\Payroll;

use App\Enums\PermissionType;
use App\Exports\MonthlyPayrollExport;
use App\Services\PayrollService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyReport extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public $year;
    #[Url]
    public $month;
    #[Url(except: 5)]
    public $perPage = 5;

    public $closure;

    protected PayrollService $payrollService;

    public function boot(PayrollService $payrollService): void
    {
        $this->payrollService = $payrollService;
    }

    public function mount(): void
    {
        $this->authorize(PermissionType::VIEW_PAYROLL_DATA->value);
        $this->year = request()->query('year', Carbon::now()->year);
        $this->month = request()->query('month', Carbon::now()->month);
        $this->perPage = request()->query('perPage', 5);
        $this->loadClosureStatus();
    }

    public function updatedYear(): void
    {
        $this->loadClosureStatus();
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->loadClosureStatus();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function loadClosureStatus(): void
    {
        $this->closure = $this->payrollService->getClosureStatus($this->year, $this->month);
    }

    public function export()
    {
        $this->authorize(PermissionType::VIEW_PAYROLL_DATA->value);

        $filename = 'payroll_report_' . $this->year . '_' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '.xlsx';

        $fileContent = Excel::raw(new MonthlyPayrollExport($this->year, $this->month), \Maatwebsite\Excel\Excel::XLSX);

        $this->payrollService->storeExport($this->year, $this->month, auth()->user(), $fileContent, $filename);

        return response()->streamDownload(function () use ($fileContent) {
            echo $fileContent;
        }, $filename);
    }

    public function closeMonth(): void
    {
        $this->authorize(PermissionType::MANAGE_MONTHLY_CLOSURES->value);

        $this->payrollService->closeMonth($this->year, $this->month, auth()->user());
        $this->loadClosureStatus();

        Flux::toast(__('Month closed successfully.'), variant: 'success');
    }

    public function reopenMonth(): void
    {
        $this->authorize(PermissionType::MANAGE_MONTHLY_CLOSURES->value);

        $this->payrollService->reopenMonth($this->year, $this->month, auth()->user());
        $this->loadClosureStatus();

        Flux::toast(__('Month reopened successfully.'), variant: 'success');
    }

    public function render()
    {
        $report = $this->payrollService->getMonthlyReportData($this->year, $this->month);
        $exports = $this->payrollService->getExports($this->year, $this->month, (int)$this->perPage, $this->getPage());

        return view('livewire.payroll.monthly-report', [
            'report' => $report,
            'exports' => $exports,
        ])->title(__('Monthly Report'));
    }
}
