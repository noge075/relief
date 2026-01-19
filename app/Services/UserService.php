<?php

namespace App\Services;

use App\Enums\RoleType;
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
        if ($actor->can('view all users')) {
            return $this->userRepository->getPaginated($perPage, $search);
        }

        // Ha nincs 'view all users', de van 'view users', akkor feltételezzük, hogy Manager (vagy csak beosztottakat láthat)
        if ($actor->can('view users')) {
            return $this->userRepository->getSubordinatesPaginated($actor->id, $perPage, $search);
        }

        // Ha nincs joga, üres lista (vagy exception, de a Livewire komponens már ellenőrizte a 'view users'-t)
        // De a biztonság kedvéért:
        return new LengthAwarePaginator([], 0, $perPage);
    }

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password'] ?? 'password');
            $roleName = $data['role'] ?? RoleType::EMPLOYEE->value;
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
