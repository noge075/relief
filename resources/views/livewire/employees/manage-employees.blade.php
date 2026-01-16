<div class="space-y-6">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ __('Employees') }}</flux:heading>
        @can('create users')
            <flux:button variant="primary" icon="plus" wire:click="openCreate">{{ __('New Employee') }}</flux:button>
        @endcan
    </div>

    <div class="max-w-md">
        <form role="search" onsubmit="return false;" autocomplete="off">
            <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    name="search_query_unique_id"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search by name or email...') }}"
                    autocomplete="one-time-code"
            />
        </form>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Department') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}"/>
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->department->name ?? '-' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @foreach($user->roles as $role)
                            <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($user->is_active)
                            <flux:badge color="green" size="sm" inset="top bottom">{{ __('Active') }}</flux:badge>
                        @else
                            <flux:badge color="red" size="sm" inset="top bottom">{{ __('Inactive') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @canany(['edit users', 'delete users'])
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>

                                <flux:menu>
                                    @can('edit users')
                                        <flux:menu.item icon="pencil-square" wire:click="openEdit({{ $user->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                    @endcan

                                    @can('delete users')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $user->id }})"
                                                        wire:confirm="{{ __('Are you sure you want to delete this user?') }}">{{ __('Delete') }}
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endcanany
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $users->links() }}
    </div>

    @include('livewire.employees.form')
</div>
