<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Manage Special Days') }}</flux:heading>
            <flux:subheading>{{ __('Configure holidays and extra workdays.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:select wire:model.live="year" class="w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 2) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Special Day') }}</flux:button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'date'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('date')">{{ __('Date') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'source'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('source')">{{ __('Source') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($specialDays as $day)
                    <flux:table.row :key="$day['date']">
                        <flux:table.cell class="font-medium">
                            {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('Y.m.d (l)') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $day['name'] }}</flux:table.cell>
                        <flux:table.cell>
                            @if($day['type'] === 'holiday')
                                <flux:badge color="green" size="sm">{{ __('Holiday') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Workday') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['source'] === 'auto')
                                <flux:badge color="zinc" size="sm" icon="server">{{ __('System') }}</flux:badge>
                            @else
                                <flux:badge color="blue" size="sm" icon="user">{{ __('Manual') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['source'] === 'manual')
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $day['id'] }})">{{ __('Edit') }}</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $day['id'] }})">{{ __('Delete') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @else
                                <span class="text-zinc-400 text-xs italic">{{ __('Read-only') }}</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500">
                {{ __('Showing') }} <span class="font-medium">{{ $specialDays->firstItem() }}</span> {{ __('to') }} <span class="font-medium">{{ $specialDays->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $specialDays->total() }}</span> {{ __('results') }}
            </div>
            {{ $specialDays->links() }}
        </div>
    </flux:card>

    @include('livewire.special-days.form')
</div>
