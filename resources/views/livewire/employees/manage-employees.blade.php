<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <flux:heading size="xl">{{ __('Employees') }}</flux:heading>
        @can(\App\Enums\PermissionType::CREATE_USERS->value)
            <flux:button variant="primary" icon="plus" wire:click="openCreate" class="w-full sm:w-auto">
                {{ __('New Employee') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 text-sm">
        <div>
            <flux:heading size="sm" class="mb-2 text-zinc-500 uppercase tracking-wider font-bold">{{ __('Employment Types') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                <flux:badge size="sm" color="blue">{{ __('Standard') }}</flux:badge>
                <flux:badge size="sm" color="yellow">{{ __('Hourly') }}</flux:badge>
                <flux:badge size="sm" color="purple">{{ __('Fixed') }}</flux:badge>
                <flux:badge size="sm" color="orange">{{ __('Student') }}</flux:badge>
            </div>
        </div>
        <div>
            <flux:heading size="sm" class="mb-2 text-zinc-500 uppercase tracking-wider font-bold">{{ __('Roles') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                <flux:badge size="sm" color="red">{{ __('Super Admin') }}</flux:badge>
                <flux:badge size="sm" color="pink">{{ __('HR') }}</flux:badge>
                <flux:badge size="sm" color="blue">{{ __('Manager') }}</flux:badge>
                <flux:badge size="sm" color="cyan">{{ __('Payroll') }}</flux:badge>
                <flux:badge size="sm" color="zinc">{{ __('Employee') }}</flux:badge>
            </div>
        </div>
        <div>
            <flux:heading size="sm" class="mb-2 text-zinc-500 uppercase tracking-wider font-bold">{{ __('Departments') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                @foreach($departments as $dept)
                    @php
                        $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                    @endphp
                    <flux:badge size="sm" :color="$deptColors[$dept->id % count($deptColors)]">{{ $dept->name }}</flux:badge>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="departmentFilter" placeholder="{{ __('All Departments') }}" icon="building-office" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Departments') }}</flux:select.option>
                @foreach($departments as $dept)
                    <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="employmentTypeFilter" placeholder="{{ __('All Employment Types') }}" icon="briefcase" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Employment Types') }}</flux:select.option>
                @foreach(\App\Enums\EmploymentType::cases() as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="workScheduleFilter" placeholder="{{ __('All Work Schedules') }}" icon="clock" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Work Schedules') }}</flux:select.option>
                @foreach($schedules as $sched)
                    <flux:select.option value="{{ $sched->id }}">{{ $sched->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="homeOfficePolicyFilter" placeholder="{{ __('All Home Office Policies') }}" icon="home" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Home Office Policies') }}</flux:select.option>
                @foreach($homeOfficePolicies as $policy)
                    <flux:select.option value="{{ $policy->id }}">{{ $policy->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="roleFilter" placeholder="{{ __('All Roles') }}" icon="user-group" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Roles') }}</flux:select.option>
                @foreach($roles as $r)
                    <flux:select.option value="{{ $r->name }}">
                        {{ \App\Enums\RoleType::tryFrom($r->name)?->label() ?? ucfirst($r->name) }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}" icon="check-circle" class="w-full sm:w-40">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Inactive') }}</flux:select.option>
            </flux:select>

            @if($search || $departmentFilter || $roleFilter || $statusFilter !== null || $employmentTypeFilter || $workScheduleFilter || $homeOfficePolicyFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Department') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Employment Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Work Schedule') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Home Office Policy') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Role') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}"/>
                                <div class="flex flex-col min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-500 truncate">{{ $user->email }}</div>

                                    <div class="flex flex-wrap gap-1 mt-1 md:hidden">
                                        @foreach($user->roles as $role)
                                            @php
                                                $roleColor = match($role->name) {
                                                    'super-admin' => 'red',
                                                    'hr' => 'pink',
                                                    'manager' => 'blue',
                                                    'payroll' => 'cyan',
                                                    default => 'zinc'
                                                };
                                            @endphp
                                            <flux:badge size="xs" :color="$roleColor">
                                                {{ \App\Enums\RoleType::tryFrom($role->name)?->label() ?? $role->name }}
                                            </flux:badge>
                                        @endforeach

                                        @if($user->is_active)
                                            <flux:badge color="green" size="xs" inset="top bottom">{{ __('Active') }}</flux:badge>
                                        @else
                                            <flux:badge color="red" size="xs" inset="top bottom">{{ __('Inactive') }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            @if($user->departments->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($user->departments as $dept)
                                        @php
                                            $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                                        @endphp
                                        <flux:badge size="sm" :color="$deptColors[$dept->id % count($deptColors)]">{{ $dept->name }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if($user->employment_type)
                                @php
                                    $empColor = match($user->employment_type->value) {
                                        1 => 'blue', // Standard
                                        2 => 'yellow', // Hourly
                                        3 => 'purple', // Fixed
                                        4 => 'orange', // Student
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$empColor">{{ $user->employment_type->label() }}</flux:badge>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ $user->workSchedule->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if($user->homeOfficePolicy)
                                @php
                                    $policyColor = match($user->homeOfficePolicy->type->value) {
                                        'full_remote' => 'green',
                                        'flexible' => 'blue',
                                        'limited' => 'yellow',
                                        'none' => 'red',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$policyColor">{{ $user->homeOfficePolicy->name }}</flux:badge>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @foreach($user->roles as $role)
                                @php
                                    $roleColor = match($role->name) {
                                        'super-admin' => 'red',
                                        'hr' => 'pink',
                                        'manager' => 'blue',
                                        'payroll' => 'cyan',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$roleColor">
                                    {{ \App\Enums\RoleType::tryFrom($role->name)?->label() ?? $role->name }}
                                </flux:badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if($user->is_active)
                                <flux:badge color="green" size="sm" inset="top bottom">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm" inset="top bottom">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{-- MOBIL GOMB: Nagy kerek szerkesztés gomb --}}
                            @can(\App\Enums\PermissionType::EDIT_USERS->value)
                                <button
                                        wire:click="openEdit({{ $user->id }})"
                                        class="md:hidden w-10 h-10 flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 active:scale-95 transition-transform"
                                >
                                    <flux:icon.pencil-square class="size-5" />
                                </button>
                            @endcan

                            {{-- DESKTOP MENÜ --}}
                            <div class="hidden md:block">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>

                                    <flux:menu>
                                        @if($this->canImpersonate($user))
                                            <flux:menu.item icon="user-plus" href="{{ route('impersonate', $user->id) }}">
                                                {{ __('Impersonate') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                        @endif

                                        @can(\App\Enums\PermissionType::EDIT_USERS->value)
                                            <flux:menu.item icon="pencil-square" wire:click="openEdit({{ $user->id }})">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can(\App\Enums\PermissionType::DELETE_USERS->value)
                                            <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $user->id }})"
                                                            wire:confirm="{{ __('Are you sure you want to delete this user?') }}">{{ __('Delete') }}
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($users->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $users->firstItem() }}-{{ $users->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $users->total() }}</span> {{ __('results') }}
                @else
                    {{ __('No results found.') }}
                @endif
            </div>

            <div class="w-full md:w-1/3 flex justify-center">
                <div class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                    <div class="bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-500 border-r border-zinc-200 dark:border-zinc-700 whitespace-nowrap">
                        {{ __('Per Page') }}
                    </div>
                    <div class="w-20">
                        <flux:select wire:model.live="perPage" class="border-0! shadow-none! rounded-none! focus:ring-0!">
                            <flux:select.option value="5">5</flux:select.option>
                            <flux:select.option value="10">10</flux:select.option>
                            <flux:select.option value="15">15</flux:select.option>
                            <flux:select.option value="25">25</flux:select.option>
                            <flux:select.option value="50">50</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 flex justify-center md:justify-end">
                {{ $users->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    @include('livewire.employees.form')
</div>