<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Special Days') }}</flux:heading>
            <flux:subheading>{{ __('Configure holidays and extra workdays.') }}</flux:subheading>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <flux:select wire:model.live="year" class="w-full sm:w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 2) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button variant="primary" icon="plus" wire:click="create" class="w-full sm:w-auto">
                {{ __('New Special Day') }}
            </flux:button>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'date'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('date')">{{ __('Date') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'source'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('source')">{{ __('Source') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($specialDays as $day)
                    <flux:table.row :key="$day['id'] ?? $day['date']">
                        <flux:table.cell class="font-medium whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('Y.m.d (l)') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex flex-col">
                                <span>{{ $day['name'] }}</span>

                                <div class="flex flex-wrap gap-1 mt-1 md:hidden">
                                    @if($day['type'] === 'holiday')
                                        <flux:badge color="green" size="xs">{{ __('Holiday') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="xs">{{ __('Workday') }}</flux:badge>
                                    @endif

                                    @if($day['source'] === 'auto')
                                        <flux:badge color="zinc" size="xs" icon="server">{{ __('System') }}</flux:badge>
                                    @else
                                        <flux:badge color="blue" size="xs" icon="user">{{ __('Manual') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            @if($day['type'] === 'holiday')
                                <flux:badge color="green" size="sm">{{ __('Holiday') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Workday') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if($day['source'] === 'auto')
                                <flux:badge color="zinc" size="sm" icon="server">{{ __('System') }}</flux:badge>
                            @else
                                <flux:badge color="blue" size="sm" icon="user">{{ __('Manual') }}</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($day['source'] === 'manual')
                                {{-- MOBIL: Nagy szerkesztés gomb --}}
                                <button
                                        wire:click="edit({{ $day['id'] }})"
                                        class="md:hidden w-10 h-10 flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 active:scale-95 transition-transform"
                                >
                                    <flux:icon.pencil-square class="size-5" />
                                </button>

                                {{-- DESKTOP: Dropdown menü --}}
                                <div class="hidden md:block">
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item wire:click="edit({{ $day['id'] }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="delete({{ $day['id'] }})" icon="trash" variant="danger" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            @else
                                <span class="text-zinc-400 text-xs italic">{{ __('Read-only') }}</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($specialDays->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $specialDays->firstItem() }}-{{ $specialDays->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $specialDays->total() }}</span> {{ __('results') }}
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

            <div class="w-full md:w-1/3 flex justify-end">
                {{ $specialDays->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    @include('livewire.special-days.form')
</div>