<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(AttendanceService $attendanceService): void
    {
        AttendanceLog::query()->delete();
        LeaveRequest::query()->delete();

        $employee = User::where('email', 'employee@oe.hu')->first();
        $hourly = User::where('email', 'hourly@oe.hu')->first();
        $manager = User::where('email', 'manager@oe.hu')->first();

        // --- Create realistic leave requests ---
        $prevMonth = now()->subMonth();
        LeaveRequest::firstOrCreate([
            'user_id' => $employee->id,
            'type' => LeaveType::VACATION,
            'start_date' => $prevMonth->copy()->setDay(10),
            'end_date' => $prevMonth->copy()->setDay(12),
        ], ['days_count' => 3, 'status' => LeaveStatus::APPROVED, 'approver_id' => $manager->id]);

        LeaveRequest::firstOrCreate([
            'user_id' => $hourly->id,
            'type' => LeaveType::SICK,
            'start_date' => now()->setDay(5),
            'end_date' => now()->setDay(5),
        ], ['days_count' => 1, 'status' => LeaveStatus::APPROVED, 'approver_id' => $manager->id]);

        LeaveRequest::firstOrCreate([
            'user_id' => $employee->id,
            'type' => LeaveType::HOME_OFFICE,
            'start_date' => $prevMonth->copy()->setDay(18),
            'end_date' => $prevMonth->copy()->setDay(18),
        ], ['days_count' => 1, 'status' => LeaveStatus::APPROVED, 'approver_id' => $manager->id]);
        
        LeaveRequest::firstOrCreate([
            'user_id' => $employee->id,
            'type' => LeaveType::VACATION,
            'start_date' => now()->setDay(20),
            'end_date' => now()->setDay(21),
        ], ['days_count' => 2, 'status' => LeaveStatus::PENDING]);

        // --- Generate daily logs up to today ---
        $users = User::where('is_active', true)->with('workSchedule')->get();
        $period = CarbonPeriod::create(now()->subMonth()->startOfMonth(), now()->today());

        foreach ($users as $user) {
            foreach ($period as $date) {
                $attendanceService->generateLogForUser($user, $date);

                $log = AttendanceLog::where('user_id', $user->id)->where('date', $date->toDateString())->first();

                // Generate realistic check-in/out times only for PRESENT days and if a schedule with times exists
                if ($log && $log->status === AttendanceStatusType::PRESENT && $user->workSchedule?->start_time && $user->workSchedule?->end_time) {
                    
                    // Base times from schedule
                    $baseCheckIn = $date->copy()->setTimeFromTimeString($user->workSchedule->start_time);
                    $baseCheckOut = $date->copy()->setTimeFromTimeString($user->workSchedule->end_time);

                    // Add random deviation
                    $checkInTime = $baseCheckIn->addMinutes(rand(-15, 20));
                    $checkOutTime = $baseCheckOut->addMinutes(rand(-20, 15));

                    // Ensure check-out is after check-in
                    if ($checkOutTime <= $checkInTime) {
                        $checkOutTime = $checkInTime->copy()->addHours(8); // Fallback
                    }

                    $log->update([
                        'check_in' => $checkInTime,
                        'check_out' => $checkOutTime,
                        'worked_hours' => max(0, ($checkInTime->floatDiffInHours($checkOutTime) - 0.5))
                    ]);
                }
            }
        }
    }
}
