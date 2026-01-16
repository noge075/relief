<?php


namespace App\Repositories\Eloquent;

use App\Models\LeaveRequest;
use App\Enums\LeaveStatus;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
        // Azokat keressük, ahol vagy a manager_id egyezik a userrel (hierarchia),
        // VAGY direktben ő van approvernek kijelölve (ha lenne ilyen logika),
        // de a specifikáció szerint a User->manager_id a mérvadó.
        // Itt egy összetettebb query kell: lekérni a beosztottakat, és azok pendingjeit.

        return LeaveRequest::whereHas('user', function ($query) use ($managerId) {
            $query->where('manager_id', $managerId);
        })
            ->where('status', LeaveStatus::PENDING->value)
            ->orderBy('start_date', 'asc')
            ->get();
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
