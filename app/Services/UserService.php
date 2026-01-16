<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getEmployeesList(User $actor, int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        if ($actor->hasRole('hr') || $actor->hasRole('super-admin') || $actor->hasRole('payroll')) {
            return $this->userRepository->getPaginated($perPage, $search);
        }

        if ($actor->hasRole('manager')) {
            return $this->userRepository->getSubordinatesPaginated($actor->id, $perPage, $search);
        }

        // Fallback: ha valakinek van joga látni, de nem a fenti role-ok egyike, akkor csak magát lássa?
        // Vagy üres lista?
        // A biztonság kedvéért üres lista, vagy csak saját maga.
        // De a 'view users' jogot csak a fentiek kapták meg.
        
        return $this->userRepository->getPaginated($perPage, $search);
    }

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password'] ?? 'password');
            $roleName = $data['role'] ?? 'employee';
            unset($data['role']);

            $user = $this->userRepository->create($data);
            $this->userRepository->syncRoles($user, [$roleName]);

            return $user;
        });
    }

    public function updateEmployee(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            // Only hash password if it's being changed
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $roleName = $data['role'] ?? null;
            unset($data['role']);

            $updated = $this->userRepository->update($id, $data);

            if ($updated && $roleName) {
                $user = $this->userRepository->find($id);
                $this->userRepository->syncRoles($user, [$roleName]);
            }

            return $updated;
        });
    }

    public function deleteEmployee(int $id): bool
    {
        return $this->userRepository->delete($id);
    }
}
