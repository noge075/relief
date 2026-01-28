<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyPayrollExport implements WithMultipleSheets
{
    use Exportable;

    protected $year;
    protected $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function sheets(): array
    {
        return [
            new SummarySheet($this->year, $this->month),
            new DailySheet($this->year, $this->month),
        ];
    }
}
