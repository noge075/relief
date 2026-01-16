<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Roles & Permissions') }}</flux:heading>
            <flux:subheading>{{ __('Manage roles and their associated permissions.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create">{{ __('New Role') }}</flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Permissions Count') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($roles as $role)
                    <flux:table.row :key="$role->id">
                        <flux:table.cell class="font-medium">{{ $role->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ $role->permissions->count() }} {{ __('permissions') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $role->id }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="delete({{ $role->id }})" icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $roles->links() }}
        </div>
    </flux:card>

    <!-- Modal -->
    <flux:modal wire:model="showModal" class="min-w-[800px] md:min-w-[1000px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? __('Edit Role') : __('New Role') }}</flux:heading>
                <flux:subheading>{{ __('Set the role name and permissions.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('Role Name') }}" placeholder="{{ __('e.g. HR Manager') }}" />

            <div>
                <flux:label>{{ __('Permissions') }}</flux:label>
                <div class="mt-2 space-y-6 max-h-[60vh] md:max-h-[70vh] overflow-y-auto border rounded-lg p-6">
                    @foreach($permissions as $group => $perms)
                        <div>
                            <flux:heading size="sm" class="mb-3 text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs font-bold">{{ __($group) }}</flux:heading>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach($perms as $permission)
                                    <flux:checkbox
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->name }}"
                                        label="{{ __($permission->name) }}"
                                    />
                                @endforeach
                            </div>
                        </div>
                        @if(!$loop->last) <hr class="border-zinc-200 dark:border-zinc-700 my-6"/> @endif
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
