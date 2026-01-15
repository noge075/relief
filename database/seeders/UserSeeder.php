<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deptIT = Department::where('name', 'IT Fejlesztés')->first();
        $deptHR = Department::where('name', 'HR & Payroll')->first();
        $scheduleStandard = WorkSchedule::where('name', 'Standard H-P 8ó')->first();
        $scheduleStudent = WorkSchedule::where('name', 'Diák Kedd-Csütörtök')->first();

        // 1. HR ADMIN (Aki mindent lát)
        $hrUser = User::create([
            'name' => 'HR Hédi',
            'email' => 'hr@oe.hu',
            'password' => Hash::make('password'),
            'department_id' => $deptHR->id,
            'employment_type' => EmploymentType::STANDARD,
            'work_schedule_id' => $scheduleStandard->id,
            'hired_at' => Carbon::parse('2020-01-01'),
        ]);
        $hrUser->assignRole('hr');
        $this->createBalance($hrUser, 25);

        // 2. IT MANAGER
        $itManager = User::create([
            'name' => 'Vezető Viktor (IT)',
            'email' => 'manager@oe.hu',
            'password' => Hash::make('password'),
            'department_id' => $deptIT->id,
            'employment_type' => EmploymentType::STANDARD,
            'work_schedule_id' => $scheduleStandard->id,
            'hired_at' => Carbon::parse('2018-05-01'),
        ]);
        $itManager->assignRole('manager');
        $this->createBalance($itManager, 30);

        // 3. IT BEOSZTOTTAK (Standard munkavállalók)
        $employees = [
            ['name' => 'Dolgozó Dénes', 'email' => 'denes@oe.hu'],
            ['name' => 'Programozó Pál', 'email' => 'pal@oe.hu'],
            ['name' => 'Tesztelő Tímea', 'email' => 'timea@oe.hu'],
        ];

        foreach ($employees as $empData) {
            $u = User::create([
                'name' => $empData['name'],
                'email' => $empData['email'],
                'password' => Hash::make('password'),
                'department_id' => $deptIT->id,
                'manager_id' => $itManager->id, // Hierarchia kapcsolat
                'employment_type' => EmploymentType::STANDARD,
                'work_schedule_id' => $scheduleStandard->id,
                'hired_at' => Carbon::now()->subYears(2),
            ]);
            $u->assignRole('employee');
            $this->createBalance($u, 22);

            // Generálunk nekik demo igényléseket
            $this->createRandomRequests($u, $itManager);
        }

        // 4. DIÁK (Specifikus csoport) [cite: 71]
        $student = User::create([
            'name' => 'Diák Dani',
            'email' => 'dani@oe.hu',
            'password' => Hash::make('password'),
            'department_id' => $deptIT->id,
            'manager_id' => $itManager->id,
            'employment_type' => EmploymentType::STUDENT,
            'work_schedule_id' => $scheduleStudent->id,
        ]);
        $student->assignRole('employee');
        $this->createBalance($student, 0);

        // 5. SUPER ADMIN
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
        ]);
        $superAdmin->assignRole('super-admin');
    }

    private function createBalance(User $user, float $days): void
    {
        LeaveBalance::create([
            'user_id' => $user->id,
            'year' => Carbon::now()->year,
            'type' => LeaveType::VACATION->value,
            'allowance' => $days,
            'used' => 0,
        ]);
    }

    private function createRandomRequests(User $user, User $manager)
    {
        // Múltbeli elfogadott szabadság
        LeaveRequest::create([
            'user_id' => $user->id,
            'approver_id' => $manager->id,
            'type' => LeaveType::VACATION->value,
            'status' => LeaveStatus::APPROVED->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->subDays(8),
            'days_count' => 3,
            'reason' => 'Pihenés',
        ]);

        $user->leaveBalances()
            ->where('type', LeaveType::VACATION->value)
            ->increment('used', 3);

        // Jövőbeli függő HO kérelem
        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => LeaveType::HOME_OFFICE->value,
            'status' => LeaveStatus::PENDING->value,
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(5),
            'days_count' => 1,
            'reason' => 'Szerelő jön',
        ]);
    }
}
