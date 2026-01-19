<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Manage Leave Balances') }}</flux:heading>
            <flux:subheading>{{ __('Adjust annual leave allowances for employees.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:select wire:model.live="year" class="w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Balance') }}</flux:button>
        </div>
    </div>

    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Year') }}</flux:table.column>
            <flux:table.column>{{ __('Allowance') }}</flux:table.column>
            <flux:table.column>{{ __('Used') }}</flux:table.column>
            <flux:table.column>{{ __('Remaining') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($balances as $balance)
                <flux:table.row :key="$balance->id">
                    <flux:table.cell class="font-medium">
                        <div class="flex items-center gap-3">
                            <flux:avatar src="{{ $balance->user->profile_photo_url ?? '' }}" name="{{ $balance->user->name }}" />
                            <div>
                                <div class="font-medium">{{ $balance->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $balance->user->email }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $balance->year }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="zinc">{{ $balance->allowance }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="yellow">{{ $balance->used }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="green">{{ $balance->allowance - $balance->used }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $balance->id }})">{{ __('Edit') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $balances->links() }}
    </div>

    @include('livewire.employees.balance-form')
</div>
