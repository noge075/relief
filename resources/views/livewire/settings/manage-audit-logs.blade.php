<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Audit Logs') }}</flux:heading>
            <flux:subheading>{{ __('View system activity logs.') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search in details...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="eventFilter" placeholder="{{ __('All Events') }}" icon="list-bullet" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Events') }}</flux:select.option>
                @foreach(\Spatie\Activitylog\Models\Activity::select('event')->distinct()->pluck('event') as $event)
                    <flux:select.option value="{{ $event }}">{{ __(ucfirst($event)) }}</flux:select.option>
                @endforeach
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
                <flux:table.column>{{ __('Event') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($activities as $activity)
                    <flux:table.row :key="$activity->id">
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($activity->event) {
                                'created' => 'green',
                                'updated' => 'blue',
                                'deleted' => 'red',
                                default => 'zinc'
                            }">
                                {{ __(ucfirst($activity->event)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $this->getSubjectDescription($activity) }}</flux:table.cell>
                        <flux:table.cell>{{ $this->getCauserName($activity) }}</flux:table.cell>
                        <flux:table.cell>{{ $activity->created_at->format('Y.m.d H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="showDetails({{ $activity->id }})">{{ __('View Changes') }}</flux:button>
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
    <flux:modal wire:model="showDetailsModal" class="min-w-100">
        @if($selectedActivity)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Activity Details') }}</flux:heading>
                    <flux:subheading>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                            {{-- 1. Sor: Esemény és Dátum --}}
                            <div>
                                <flux:label class="mb-1">{{ __('Event') }}</flux:label>
                                <div class="font-medium text-zinc-900 dark:text-white flex items-center gap-2">
                                    {{-- Opcionális: tehetsz ide egy ikont is az event típusa alapján --}}
                                    <flux:badge size="sm" color="zinc">{{ __(ucfirst($selectedActivity->event)) }}</flux:badge>
                                </div>
                            </div>

                            <div>
                                <flux:label class="mb-1">{{ __('Date') }}</flux:label>
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $selectedActivity->created_at->format('Y.m.d H:i') }}
                                </div>
                            </div>

                            <flux:separator class="col-span-1 sm:col-span-2 my-2" />

                            {{-- 2. Sor: Aki csinálta (Actor) és Az érintett (Target) --}}
                            <div>
                                <flux:label class="mb-1">{{ __('Performed by') }}</flux:label> {{-- "User" helyett pontosabb --}}
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $this->getCauserName($selectedActivity) }}
                                </div>
                            </div>

                            <div>
                                <flux:label class="mb-1">{{ __('Affected User') }}</flux:label> {{-- "User" helyett pontosabb --}}
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $this->getSubjectUserName($selectedActivity) ?: '-' }}
                                </div>
                            </div>

                            {{-- 3. Sor: Tárgy leírása (pl. melyik rekord) --}}
                            <div class="col-span-1 sm:col-span-2">
                                <flux:label class="mb-1">{{ __('Subject Description') }}</flux:label>
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $this->getSubjectDescription($selectedActivity) }}
                                </div>
                            </div>
                        </div>
                    </flux:subheading>
                </div>

                @if($selectedActivity->properties->has('old'))
                    <flux:separator />
                    <flux:heading size="md">{{ __('Old Values') }}</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg text-sm font-mono overflow-auto max-h-60">
                        <pre>{{ json_encode($selectedActivity->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif

                @if($selectedActivity->properties->has('attributes'))
                    <flux:separator />
                    <flux:heading size="md">{{ __('New Values') }}</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg text-sm font-mono overflow-auto max-h-60">
                        <pre>{{ json_encode($selectedActivity->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:button wire:click="$set('showDetailsModal', false)" variant="ghost">{{ __('Close') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
