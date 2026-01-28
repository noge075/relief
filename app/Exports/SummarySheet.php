<?php

namespace App\Exports;

use App\Services\PayrollService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $year;
    protected $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        return app(PayrollService::class)->getMonthlyReportData($this->year, $this->month);
    }

    public function headings(): array
    {
        return [
            'Dolgozó azonosító',
            'Csoport',
            'Hónap',
            'Szabadság napok száma (approved)',
            'Beteg napok száma (approved)',
            'Ledolgozott munkanapok száma',
            'HO napok száma',
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_id'],
            $row['group'],
            $row['month'],
            (string) $row['vacation_days'], // String cast to ensure 0 is displayed
            (string) $row['sick_days'],
            (string) $row['worked_days'],
            (string) $row['home_office_days'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
