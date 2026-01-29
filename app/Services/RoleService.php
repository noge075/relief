<?php

namespace App\Services;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    public function __construct(protected RoleRepositoryInterface $roleRepository) {}

    public function getAllRoles(): Collection
    {
        return $this->roleRepository->all();
    }

    public function getPaginatedRoles(int $perPage = 10, ?string $search = null, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        return $this->roleRepository->getPaginated($perPage, $search, $sortCol, $sortAsc);
    }

    public function getAllPermissions(): Collection
    {
        return $this->roleRepository->getAllPermissions();
    }

    public function getGroupedPermissions(): array
    {
        $permissions = $this->roleRepository->getAllPermissions();
        $grouped = [];

        // Define groups and keywords
        $groups = [
            'User Management' => ['users', 'user profile'],
            'Departments' => ['departments'],
            'Work Schedules' => ['work schedules'],
            'Leave Management' => ['leave'],
            'Attendance' => ['attendance'],
            'Documents' => ['documents'],
            'Payroll' => ['payroll', 'monthly closures'],
            'System Settings' => ['settings', 'audit logs'],
        ];

        foreach ($permissions as $permission) {
            $foundGroup = 'Other';
            foreach ($groups as $groupName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($permission->name, $keyword)) {
                        $foundGroup = $groupName;
                        break 2;
                    }
                }
            }
            $grouped[$foundGroup][] = $permission;
        }

        return $grouped;
    }

    public function createRole(array $data): Role
    {
        $role = $this->roleRepository->create(['name' => $data['name'], 'guard_name' => 'web']);
        
        if (isset($data['permissions'])) {
            $this->roleRepository->syncPermissions($role, $data['permissions']);
        }

        return $role;
    }

    public function updateRole(int $id, array $data): bool
    {
        $updated = $this->roleRepository->update($id, ['name' => $data['name']]);
        
        if ($updated && isset($data['permissions'])) {
            /**
             * @var Role|null $role
             */
            $role = $this->roleRepository->find($id);
            $this->roleRepository->syncPermissions($role, $data['permissions']);
        }

        return $updated;
    }

    public function deleteRole(int $id): bool
    {
        return $this->roleRepository->delete($id);
    }
    
    public function getRole(int $id): ?Role
    {
        /**
         * @var Role|null
         */
        return $this->roleRepository->find($id);
    }
}
