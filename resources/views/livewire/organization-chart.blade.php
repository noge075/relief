<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Organization Chart') }}</flux:heading>
            <flux:subheading>{{ __('Manage the employee hierarchy.') }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 text-sm">
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
                @php
                    $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                @endphp
                @foreach($departments as $dept)
                    @php
                        $deptColor = $deptColors[$dept->id % count($deptColors)];
                    @endphp
                    <flux:badge size="sm" :color="$deptColor">{{ $dept->name }}</flux:badge>
                @endforeach
            </div>
        </div>
    </div>

    <flux:card class="overflow-x-auto">
        @can(\App\Enums\PermissionType::EDIT_USERS->value)
            <div
                    class="space-y-4 min-w-75"
                    x-data="{
                    initSortable(el) {
                        new Sortable(el, {
                            group: 'nested',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            handle: '.drag-handle', // Javaslat: Adj hozzá egy 'drag-handle' class-t az ikonhoz a node-ban, hogy mobilon könnyebb legyen görgetni a húzás helyett
                            onEnd: (evt) => {
                                let itemEl = evt.item;
                                let newParentEl = itemEl.closest('[data-user-id]');
                                let newManagerId = newParentEl ? newParentEl.dataset.userId : 'root';
                                let userId = itemEl.dataset.id;

                                if (evt.to === evt.from && evt.newIndex === evt.oldIndex) return;

                                $wire.updateManager(userId, newManagerId);
                            }
                        });
                    }
                }"
                    x-init="initSortable($el)"
            >
                @forelse($tree as $rootUser)
                    <div data-id="{{ $rootUser->id }}">
                        <x-org-tree-node :user="$rootUser" />
                    </div>
                @empty
                    <p class="text-zinc-500 text-center py-4">{{ __('No users found.') }}</p>
                @endforelse
            </div>
        @else
            <div class="space-y-4 min-w-75">
                @forelse($tree as $rootUser)
                    <div data-id="{{ $rootUser->id }}">
                        <x-org-tree-node :user="$rootUser" />
                    </div>
                @empty
                    <p class="text-zinc-500 text-center py-4">{{ __('No users found.') }}</p>
                @endforelse
            </div>
        @endcan
    </flux:card>

    @can(\App\Enums\PermissionType::EDIT_USERS->value)
        <flux:modal wire:model="showEditModal" class="w-full sm:w-120">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Edit Manager') }}</flux:heading>
                    <flux:subheading>{{ __('Select a new manager for the employee.') }}</flux:subheading>
                </div>

                <div class="grid gap-4">
                    <flux:select wire:model="selectedManagerId" label="{{ __('Manager') }}" class="w-full">
                        <flux:select.option value="">{{ __('No Manager') }}</flux:select.option>
                        @foreach($allUsers as $user)
                            @if($user->id !== $selectedUserId)
                                <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <flux:button wire:click="$set('showEditModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
                    <flux:button wire:click="saveManager" variant="primary" class="w-full sm:w-auto">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
</div>