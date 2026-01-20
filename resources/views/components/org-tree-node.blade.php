@props(['user', 'level' => 0])

<div class="relative {{ $level > 0 ? 'ml-8 pl-8 border-l-2 border-zinc-200 dark:border-zinc-700' : '' }}" data-user-id="{{ $user->id }}">
    @if($level > 0)
        <!-- Vízszintes vonal -->
        <div class="absolute top-8 left-0 w-8 border-t-2 border-zinc-200 dark:border-zinc-700"></div>
    @endif

    <div class="py-2">
        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm group hover:border-indigo-300 dark:hover:border-indigo-700 transition w-full max-w-md relative z-10 cursor-move">
            <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}" size="sm" />

            <div class="flex flex-col flex-grow">
                <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                <div class="flex items-center gap-2 mt-1">
                    @php
                        $roleName = $user->roles->first()?->name;
                        $roleLabel = $roleName ? (\App\Enums\RoleType::tryFrom($roleName)?->label() ?? ucfirst($roleName)) : __('Employee');

                        $roleColor = match($roleName) {
                            'super-admin' => 'red',
                            'hr' => 'pink',
                            'manager' => 'blue',
                            'payroll' => 'cyan',
                            default => 'zinc'
                        };
                    @endphp
                    <flux:badge size="xs" :color="$roleColor">{{ $roleLabel }}</flux:badge>

                    @if($user->department)
                        @php
                            $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                            $deptColor = $deptColors[$user->department->id % count($deptColors)];
                        @endphp
                        <flux:badge size="xs" :color="$deptColor">{{ $user->department->name }}</flux:badge>
                    @endif
                </div>
            </div>

            @can(\App\Enums\PermissionType::EDIT_USERS->value)
                <flux:button variant="ghost" size="xs" icon="pencil-square" wire:click="openEdit({{ $user->id }})" class="opacity-0 group-hover:opacity-100 transition text-zinc-400 hover:text-indigo-600" />
            @endcan
        </div>

        <div class="mt-2 relative min-h-[10px]" x-init="initSortable($el)">
            <!-- Függőleges vonal a gyerekekhez -->
            @if($user->subordinates->count() > 0 && $level == 0)
                 <div class="absolute top-0 left-4 h-full border-l-2 border-zinc-200 dark:border-zinc-700 -z-10"></div>
            @endif

            @foreach($user->subordinates as $subordinate)
                <div data-id="{{ $subordinate->id }}">
                    <x-org-tree-node :user="$subordinate" :level="$level + 1" />
                </div>
            @endforeach
        </div>
    </div>
</div>
