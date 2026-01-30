<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Approvals') }}</flux:heading>
            <flux:subheading>{{ __('Manage leave requests from your team.') }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="typeFilter" placeholder="{{ __('All Types') }}" icon="tag" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                <flux:select.option value="vacation">{{ __('Vacation') }}</flux:select.option>
                <flux:select.option value="sick">{{ __('Sick Leave') }}</flux:select.option>
                <flux:select.option value="home_office">{{ __('Home Office') }}</flux:select.option>
            </flux:select>

            @if($search || $typeFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Employee') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'start_date'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('start_date')">{{ __('Date') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'days_count'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('days_count')">{{ __('Days') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($requests as $request)
                    <flux:table.row :key="$request->id">
                        <flux:table.cell class="font-medium">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $request->user->profile_photo_url ?? '' }}" name="{{ $request->user->name }}" />
                                <div class="flex flex-col min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->user->name }}</div>
                                    <div class="text-xs text-zinc-500 truncate">{{ $request->user->email }}</div>

                                    <div class="md:hidden mt-2 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @php
                                                $type = $request->type->value;
                                                $color = match($type) { 'vacation' => 'yellow', 'sick' => 'red', 'home_office' => 'blue', default => 'zinc' };
                                                $label = match($type) { 'vacation' => __('Vacation'), 'sick' => __('Sick Leave'), 'home_office' => __('Home Office'), default => __('Other') };
                                            @endphp
                                            <flux:badge :color="$color" size="xs">{{ $label }}</flux:badge>

                                            <span class="text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $request->start_date->format('m.d') }}
                                                @if($request->start_date != $request->end_date)
                                                    - {{ $request->end_date->format('m.d') }}
                                                @endif
                                                <span class="font-semibold">({{ $request->days_count }} {{ __('days') }})</span>
                                            </span>
                                        </div>

                                        <div class="text-xs text-zinc-500 italic truncate max-w-50 flex items-center gap-1">
                                            {{ $request->reason }}
                                            @if($request->has_warning)
                                                <flux:icon name="exclamation-triangle" class="w-3 h-3 text-yellow-500 shrink-0" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ $request->start_date->format('Y.m.d') }}
                            @if($request->start_date != $request->end_date)
                                - {{ $request->end_date->format('Y.m.d') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $request->days_count . ' ' . __('day') }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell truncate max-w-50">
                            {{ $request->reason }}
                            @if($request->has_warning)
                                <flux:tooltip content="{{ $request->warning_message }}">
                                    <flux:icon name="exclamation-triangle" class="w-4 h-4 text-yellow-500 inline-block ml-1 cursor-help" />
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex md:hidden flex-col gap-2">
                                <button
                                        wire:click="approve({{ $request->id }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 active:scale-95 transition-transform"
                                >
                                    <flux:icon name="check" class="w-5 h-5" />
                                </button>
                                <button
                                        wire:click="openRejectModal({{ $request->id }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 active:scale-95 transition-transform"
                                >
                                    <flux:icon name="x-mark" class="w-5 h-5" />
                                </button>
                            </div>

                            <div class="hidden md:flex items-center gap-2">
                                <flux:tooltip content="{{ __('Approve') }}">
                                    <flux:button
                                            wire:click="approve({{ $request->id }})"
                                            icon="check"
                                            variant="ghost"
                                            size="sm"
                                            class="text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/30"
                                    />
                                </flux:tooltip>

                                <flux:tooltip content="{{ __('Reject') }}">
                                    <flux:button
                                            wire:click="openRejectModal({{ $request->id }})"
                                            icon="x-mark"
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30"
                                    />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($requests->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $requests->firstItem() }}-{{ $requests->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $requests->total() }}</span> {{ __('results') }}
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
                {{ $requests->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <flux:modal wire:model="showRejectModal" class="w-full sm:w-120">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Reject Request') }}</flux:heading>
                <flux:subheading>{{ __('Please provide a reason for rejection.') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <flux:textarea wire:model="managerComment" label="{{ __('Manager Comment') }}" rows="3" />
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
                <flux:button wire:click="$set('showRejectModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="reject" variant="danger" class="w-full sm:w-auto">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>