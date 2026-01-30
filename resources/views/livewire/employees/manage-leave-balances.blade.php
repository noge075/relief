<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Leave Balances') }}</flux:heading>
            <flux:subheading>{{ __('Adjust annual leave allowances for employees.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create" class="w-full sm:w-auto">
            {{ __('New Balance') }}
        </flux:button>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="yearFilter" class="w-full sm:w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="departmentFilter" placeholder="{{ __('All Departments') }}" icon="building-office" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Departments') }}</flux:select.option>
                @foreach($departments as $dept)
                    <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if($search || $departmentFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'year'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('year')">{{ __('Year') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'allowance'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('allowance')">{{ __('Allowance') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'used'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('used')">{{ __('Used') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Remaining') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($balances as $balance)
                    <flux:table.row :key="$balance->id">
                        <flux:table.cell class="font-medium">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $balance->user->profile_photo_url ?? '' }}" name="{{ $balance->user->name }}" />
                                <div class="flex flex-col min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $balance->user->name }}</div>
                                    <div class="text-xs text-zinc-500 truncate">{{ $balance->user->email }}</div>

                                    <div class="flex flex-wrap gap-2 mt-1 md:hidden">
                                        <flux:badge size="xs" color="zinc">{{ $balance->year }}</flux:badge>
                                        @php
                                            $remaining = $balance->allowance - $balance->used;
                                            $color = $remaining < 3 ? 'red' : ($remaining < 10 ? 'yellow' : 'green');
                                        @endphp
                                        <flux:badge size="xs" :color="$color">{{ $remaining . ' ' . __('remaining') }}</flux:badge>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">{{ $balance->year }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge color="zinc">{{ $balance->allowance . ' ' . __('day') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge color="yellow">{{ $balance->used . ' ' . __('day') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge color="green">{{ $balance->allowance - $balance->used  . ' ' . __('day') }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <button
                                    wire:click="edit({{ $balance->id }})"
                                    class="md:hidden w-10 h-10 flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 active:scale-95 transition-transform"
                            >
                                <flux:icon.pencil-square class="size-5" />
                            </button>

                            <div class="hidden md:block">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="edit({{ $balance->id }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($balances->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $balances->firstItem() }}-{{ $balances->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $balances->total() }}</span> {{ __('results') }}
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

            <div class="w-full md:w-1/3 flex justify-center md:justify-end">
                {{ $balances->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    @include('livewire.employees.balance-form')
</div>