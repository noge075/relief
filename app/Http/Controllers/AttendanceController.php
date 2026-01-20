<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function downloadPdf($year, $month)
    {
        $user = Auth::user();
        
        $days = $this->attendanceService->getAttendanceData($user, $year, $month);

        $filename = 'attendance_' . $user->id . '_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return Pdf::loadView('pdf.attendance-sheet', [
            'days' => $days,
            'user' => $user,
            'year' => $year,
            'month' => $month,
        ])->stream($filename);
    }
}
