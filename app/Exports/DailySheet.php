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

class DailySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
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
        return app(PayrollService::class)->getDailyReportData($this->year, $this->month);
    }

    public function headings(): array
    {
        return [
            'Dátum',
            'Név',
            'Részleg',
            'Státusz',
            'Megjegyzés',
            'Munkakezdés',
            'Munkabefejezés',
            'Ledolgozott órák',
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['name'],
            $row['department'],
            $row['status'],
            $row['meta'] ?? '',
            $row['check_in'] ?? '',
            $row['check_out'] ?? '',
            $row['worked_hours'] ?? '',
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
        return 'Napi bontás';
    }
}
