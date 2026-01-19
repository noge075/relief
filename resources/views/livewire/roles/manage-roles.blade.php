<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Roles & Permissions') }}</flux:heading>
            <flux:subheading>{{ __('Manage roles and their associated permissions.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create">{{ __('New Role') }}</flux:button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />
        </div>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Permissions Count') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($roles as $role)
                    <flux:table.row :key="$role->id">
                        <flux:table.cell class="font-medium">
                            {{ \App\Enums\RoleType::tryFrom($role->name)?->label() ?? $role->name }}
                        </flux:table.cell>
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

    @include('livewire.roles.form')
</div>
