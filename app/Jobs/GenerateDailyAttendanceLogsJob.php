<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDailyAttendanceLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AttendanceService $attendanceService): void
    {
        $yesterday = Carbon::yesterday();
        $activeUsers = User::where('is_active', true)->get();

        foreach ($activeUsers as $user) {
            $attendanceService->generateLogForUser($user, $yesterday);
        }
    }
}
