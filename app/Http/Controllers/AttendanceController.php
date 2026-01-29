<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        $year = (int)$request->query('year', now()->year);
        $month = (int)$request->query('month', now()->month);

        $document = $this->attendanceService->createAndStorePdf($user, $year, $month);
        $media = $document->getFirstMedia('signed_sheets');

        if (! $media) {
            abort(404, 'Generated document not found.');
        }

        $path = $media->getPath();

        if (!file_exists($path)) {
            abort(404, 'File not found on disk.');
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
