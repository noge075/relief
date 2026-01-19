<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Roles & Permissions') }}</flux:heading>
            <flux:subheading>{{ __('Manage roles and their associated permissions.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create">{{ __('New Role') }}</flux:button>
    </div>

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

    @include('livewire.roles.form')
</div>
