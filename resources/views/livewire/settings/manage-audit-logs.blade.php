<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Audit Logs') }}</flux:heading>
            <flux:subheading>{{ __('View system activity logs.') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search in details...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="eventFilter" placeholder="{{ __('All Events') }}" class="w-full sm:w-40">
                <flux:select.option value="">{{ __('All Events') }}</flux:select.option>
                <flux:select.option value="created">Created</flux:select.option>
                <flux:select.option value="updated">Updated</flux:select.option>
                <flux:select.option value="deleted">Deleted</flux:select.option>
            </flux:select>

            <flux:input wire:model.live.debounce.300ms="causerFilter" icon="user" placeholder="{{ __('Filter by User...') }}" class="w-full sm:w-48" />

            @if($search || $eventFilter || $causerFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Event') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Details') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($activities as $activity)
                    <flux:table.row :key="$activity->id">
                        <flux:table.cell class="whitespace-nowrap text-xs text-zinc-500">
                            {{ $activity->created_at->format('Y.m.d H:i:s') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($activity->causer)
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="{{ $activity->causer->profile_photo_url ?? '' }}" name="{{ $activity->causer->name }}" size="xs" />
                                    <span class="text-sm">{{ $activity->causer->name }}</span>
                                </div>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $color = match($activity->event) {
                                    'created' => 'green',
                                    'updated' => 'blue',
                                    'deleted' => 'red',
                                    default => 'zinc'
                                };
                            @endphp
                            <flux:badge size="sm" :color="$color">{{ ucfirst($activity->event) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs font-mono bg-zinc-100 dark:bg-zinc-800 px-1 rounded">
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($activity->properties->count() > 0)
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="showDetails({{ $activity->id }})">{{ __('View') }}</flux:button>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3">
                @if($activities->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $activities->firstItem() }}-{{ $activities->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $activities->total() }}</span> {{ __('results') }}
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
                {{ $activities->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <!-- Details Modal -->
    <flux:modal wire:model="showDetailsModal" class="min-w-150">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Activity Details') }}</flux:heading>
                <flux:subheading>
                    @if($selectedActivity)
                        {{ ucfirst($selectedActivity->event) }} {{ class_basename($selectedActivity->subject_type) }} #{{ $selectedActivity->subject_id }}
                    @endif
                </flux:subheading>
            </div>

            @if($selectedActivity)
                <div class="space-y-4">
                    @if($selectedActivity->properties->has('attributes'))
                        <div>
                            <div class="font-bold text-sm mb-2">{{ __('New Values') }}</div>
                            <pre class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-700 overflow-x-auto text-xs font-mono">
{{ json_encode($selectedActivity->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                            </pre>
                        </div>
                    @endif

                    @if($selectedActivity->properties->has('old'))
                        <div>
                            <div class="font-bold text-sm mb-2">{{ __('Old Values') }}</div>
                            <pre class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-700 overflow-x-auto text-xs font-mono">
{{ json_encode($selectedActivity->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                            </pre>
                        </div>
                    @endif

                    @if(!$selectedActivity->properties->has('attributes') && !$selectedActivity->properties->has('old'))
                         <pre class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-700 overflow-x-auto text-xs font-mono">
{{ json_encode($selectedActivity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                        </pre>
                    @endif
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="$set('showDetailsModal', false)">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
