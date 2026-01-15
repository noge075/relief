<?php

namespace App\Providers;

use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\WorkScheduleRepositoryInterface;
use App\Repositories\Eloquent\EloquentAttendanceDocumentRepository;
use App\Repositories\Eloquent\EloquentDepartmentRepository;
use App\Repositories\Eloquent\EloquentWorkScheduleRepository;
use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use App\Repositories\Contracts\MonthlyClosureRepositoryInterface;

use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentLeaveRequestRepository;
use App\Repositories\Eloquent\EloquentLeaveBalanceRepository;
use App\Repositories\Eloquent\EloquentAttendanceLogRepository;
use App\Repositories\Eloquent\EloquentMonthlyClosureRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, EloquentLeaveRequestRepository::class);
        $this->app->bind(LeaveBalanceRepositoryInterface::class, EloquentLeaveBalanceRepository::class);
        $this->app->bind(AttendanceLogRepositoryInterface::class, EloquentAttendanceLogRepository::class);
        $this->app->bind(MonthlyClosureRepositoryInterface::class, EloquentMonthlyClosureRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(WorkScheduleRepositoryInterface::class, EloquentWorkScheduleRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, EloquentLeaveRequestRepository::class);
        $this->app->bind(LeaveBalanceRepositoryInterface::class, EloquentLeaveBalanceRepository::class);
        $this->app->bind(AttendanceLogRepositoryInterface::class, EloquentAttendanceLogRepository::class);
        $this->app->bind(MonthlyClosureRepositoryInterface::class, EloquentMonthlyClosureRepository::class);
        $this->app->bind(AttendanceDocumentRepositoryInterface::class, EloquentAttendanceDocumentRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
