@props(['user', 'level' => 0])

<div
        class="relative {{ $level > 0 ? 'ml-4 pl-4 md:ml-8 md:pl-8 border-l-2 border-zinc-200 dark:border-zinc-700' : '' }}"
        data-user-id="{{ $user->id }}"
        x-data="{ open: false }"
>
    @if($level > 0)
        <div class="absolute top-8 left-0 w-4 md:w-8 border-t-2 border-zinc-200 dark:border-zinc-700"></div>
    @endif

    <div class="py-2">
        <div class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm group hover:border-indigo-300 dark:hover:border-indigo-700 transition w-full max-w-md relative z-10 @can(\App\Enums\PermissionType::EDIT_USERS->value) cursor-move @endcan">

            @if($user->subordinates->count() > 0)
                <button
                        @click="open = !open"
                        class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-full flex items-center justify-center text-zinc-500 hover:text-indigo-600 hover:border-indigo-300 transition z-20"
                        :class="{ 'rotate-180': open }"
                >
                    <flux:icon name="chevron-down" class="w-3 h-3 transition-transform duration-200" />
                </button>
            @endif

            <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}" size="sm" class="shrink-0" />

            <div class="flex flex-col grow min-w-0">
                <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $user->name }}</span>

                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                    @php
                        $roleName = $user->roles->first()?->name;
                        $roleLabel = $roleName
                            ? (\App\Enums\RoleType::tryFrom($roleName)?->label() ?? ucfirst($roleName))
                            : __('Employee');

                        $roleColor = match($roleName) {
                            'super-admin' => 'red',
                            'hr' => 'pink',
                            'manager' => 'blue',
                            'payroll' => 'cyan',
                            default => 'zinc'
                        };
                    @endphp
                    <flux:badge size="xs" :color="$roleColor">{{ $roleLabel }}</flux:badge>

                    @if($user->departments->isNotEmpty())
                        @php
                            $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                        @endphp
                        @foreach($user->departments as $dept)
                            @php
                                $deptColor = $deptColors[$dept->id % count($deptColors)];
                            @endphp
                            <flux:badge size="xs" :color="$deptColor">{{ $dept->name }}</flux:badge>
                        @endforeach
                    @endif

                    @if($user->subordinates->count() > 0)
                        <div x-show="!open" class="flex items-center gap-1 cursor-pointer" @click="open = true">
                            <flux:badge size="xs" color="zinc" icon="users">{{ $user->subordinates->count() }}</flux:badge>
                        </div>
                    @endif
                </div>
            </div>

            @can(\App\Enums\PermissionType::EDIT_USERS->value)
                <div class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-200">
                    <flux:button variant="ghost" size="xs" icon="pencil-square" wire:click="openEdit({{ $user->id }})" class="text-zinc-400 hover:text-indigo-600" />
                </div>
            @endcan
        </div>

        @if($user->subordinates->count() > 0)
            <div
                    class="mt-2 relative min-h-2.5"
                    x-init="initSortable($el)"
                    x-show="open"
                    x-transition
            >
                @if($level == 0)
                    <div class="absolute top-0 left-2 md:left-4 h-full border-l-2 border-zinc-200 dark:border-zinc-700 -z-10"></div>
                @endif

                @foreach($user->subordinates as $subordinate)
                    <div data-id="{{ $subordinate->id }}">
                        <x-org-tree-node :user="$subordinate" :level="$level + 1" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>