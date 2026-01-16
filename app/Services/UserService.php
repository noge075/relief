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

    public function getEmployeesList(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
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