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

    public function getEmployeesList(User $actor, int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        if ($actor->can('view all users')) {
            return $this->userRepository->getPaginated($perPage, $filters, $sortCol, $sortAsc);
        }

        if ($actor->can('view users')) {
            return $this->userRepository->getSubordinatesPaginated($actor->id, $perPage, $filters, $sortCol, $sortAsc);
        }

        return new LengthAwarePaginator([], 0, $perPage);
    }

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password'] ?? 'password');
            $roleName = $data['role'] ?? RoleType::EMPLOYEE->value;
            $permissions = $data['permissions'] ?? [];
            $departments = $data['departments'] ?? [];
            
            unset($data['role']);
            unset($data['permissions']);
            unset($data['departments']);

            $user = $this->userRepository->create($data);
            $this->userRepository->syncRoles($user, [$roleName]);
            
            if (!empty($permissions)) {
                $this->userRepository->syncPermissions($user, $permissions);
            }

            if (!empty($departments)) {
                $user->departments()->sync($departments);
            }

            return $user;
        });
    }

    public function updateEmployee(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $roleName = $data['role'] ?? null;
            $permissions = $data['permissions'] ?? null;
            $departments = $data['departments'] ?? null;
            
            unset($data['role']);
            unset($data['permissions']);
            unset($data['departments']); // Új: departments

            $updated = $this->userRepository->update($id, $data);

            if ($updated) {
                $user = $this->userRepository->find($id);
                
                if ($roleName) {
                    $this->userRepository->syncRoles($user, [$roleName]);
                }
                
                if ($permissions !== null) {
                    $this->userRepository->syncPermissions($user, $permissions);
                }

                if ($departments !== null) {
                    $user->departments()->sync($departments);
                }
            }

            return $updated;
        });
    }

    public function deleteEmployee(int $id): bool
    {
        return $this->userRepository->delete($id);
    }
}
