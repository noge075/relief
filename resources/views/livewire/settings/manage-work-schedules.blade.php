<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Work Schedules') }}</flux:heading>
            <flux:subheading>{{ __('Manage work schedules.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Work Schedule') }}</flux:button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Weekly Hours') }}</flux:table.column>
                <flux:table.column>{{ __('Work Time') }}</flux:table.column>
                <flux:table.column>{{ __('Weekly Pattern') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($schedules as $schedule)
                    <flux:table.row :key="$schedule->id">
                        <flux:table.cell class="font-medium">{{ $schedule->name }}</flux:table.cell>
                        <flux:table.cell>
                            {{ array_sum($schedule->weekly_pattern) }} {{ __('h') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($schedule->start_time && $schedule->end_time)
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    @php
                                        $key = strtolower($day);
                                        $hours = $schedule->weekly_pattern[$key] ?? 0;
                                        $color = $hours > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-600';
                                    @endphp
                                    <div class="w-6 h-6 rounded flex items-center justify-center text-xs font-medium {{ $color }}" title="{{ __($day) }}: {{ $hours }}h">
                                        {{ substr(__($day), 0, 1) }}
                                    </div>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $schedule->id }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="delete({{ $schedule->id }})" icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3">
                @if($schedules->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $schedules->firstItem() }}-{{ $schedules->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $schedules->total() }}</span> {{ __('results') }}
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
                {{ $schedules->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <!-- Modal -->
    <flux:modal wire:model="showModal" class="min-w-125">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Work Schedule') : __('New Work Schedule') }}</flux:heading>
                <flux:subheading>{{ __('Define the weekly working hours.') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <flux:input wire:model="name" label="{{ __('Name') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="start_time" type="time" label="{{ __('Start Time') }}" />
                    <flux:input wire:model="end_time" type="time" label="{{ __('End Time') }}" />
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                        <flux:input wire:model="weeklyPattern.{{ $day }}" type="number" label="{{ __(ucfirst($day)) }}" min="0" max="24" />
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
