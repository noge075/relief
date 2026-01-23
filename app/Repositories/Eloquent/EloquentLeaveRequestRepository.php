<?php


namespace App\Repositories\Eloquent;

use App\Models\LeaveRequest;
use App\Enums\LeaveStatus;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentLeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequest $model)
    {
        parent::__construct($model);
    }

    public function getForUser(int $userId, ?string $status = null): Collection
    {
        $query = LeaveRequest::where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    public function getForUserInPeriod(int $userId, string $start, string $end): Collection
    {
        return LeaveRequest::where('user_id', $userId)
            ->where(function ($query) use ($start, $end) {
                // Átfedés logika: (StartA <= EndB) and (EndA >= StartB)
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            })
            ->orderBy('start_date')
            ->get();
    }

    public function getPendingForManager(int $managerId): Collection
    {
        return LeaveRequest::whereHas('user', function ($query) use ($managerId) {
            $query->where('manager_id', $managerId);
        })
            ->where('status', LeaveStatus::PENDING->value)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public function getPendingRequests(?int $managerId = null, int $perPage = 10, array $filters = [], string $sortCol = 'start_date', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = LeaveRequest::with('user')
            ->where('status', LeaveStatus::PENDING->value);

        if ($managerId) {
            $query->whereHas('user', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($sortCol === 'name') {
            $query->join('users', 'leave_requests.user_id', '=', 'users.id')
                ->orderBy('users.last_name', $sortAsc ? 'asc' : 'desc')
                ->orderBy('users.first_name', $sortAsc ? 'asc' : 'desc')
                ->select('leave_requests.*');
        } else {
            $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findOverlapping(int $userId, string $start, string $end, ?int $excludeId = null): Collection
    {
        // Átfedés logika:
        // (StartA <= EndB) and (EndA >= StartB)
        // Kihagyjuk a 'Rejected' és 'Cancelled' státuszokat, mert azok nem foglalnak helyet.

        return LeaveRequest::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', LeaveStatus::APPROVED->value)
                    ->orWhere('status', LeaveStatus::PENDING->value);
            })
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                    ->where('end_date', '>=', $start);
            })
            ->when($excludeId, function ($query, $id) {
                return $query->where('id', '!=', $id);
            })
            ->get();
    }

    public function updateStatus(LeaveRequest $request, string $status, ?string $comment = null): bool
    {
        $data = ['status' => $status];
        if ($comment) {
            $data['manager_comment'] = $comment;
        }

        // Ha elfogadják, beállítjuk az approvert a jelenlegi userre
        if ($status === LeaveStatus::APPROVED->value) {
            $data['approver_id'] = auth()->id();
        }

        return $request->update($data);
    }
}
