<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Status Board') }}</flux:heading>
            <flux:subheading>{{ __('Who is where?') }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">

        <div class="flex flex-col lg:flex-row justify-between items-center gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-center">
                <flux:date-picker locale="hu-HU" wire:model="startDate" label="{{ __('Start Date') }}" class="w-full sm:w-auto" />
                <flux:date-picker locale="hu-HU" wire:model="endDate" label="{{ __('End Date') }}" class="w-full sm:w-auto" />
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto justify-between lg:justify-end">
                <div class="flex items-center justify-between w-full sm:w-auto gap-2">
                    <flux:button icon="chevron-left" wire:click="prevPeriod" variant="ghost" size="sm" />

                    <div class="font-medium text-sm flex items-center gap-2 whitespace-nowrap px-2">
                        <flux:icon name="calendar-days" class="w-4 h-4 text-zinc-400" />
                        {{ $periodStart->translatedFormat('M d.') }} - {{ $periodEnd->translatedFormat('M d.') }}
                    </div>

                    <flux:button icon="chevron-right" wire:click="nextPeriod" variant="ghost" size="sm" />
                </div>

                <flux:button wire:click="thisWeek" variant="subtle" size="sm" class="w-full sm:w-auto">{{ __('This Week') }}</flux:button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 justify-center lg:justify-start border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <flux:button wire:click="previousWeek" variant="subtle" size="sm" class="grow sm:grow-0">{{ __('Prev Week') }}</flux:button>
            <flux:button wire:click="nextWeek" variant="subtle" size="sm" class="grow sm:grow-0">{{ __('Next Week') }}</flux:button>
            <flux:button wire:click="thisMonth" variant="subtle" size="sm" class="grow sm:grow-0">{{ __('This Month') }}</flux:button>
            <flux:button wire:click="previousMonth" variant="subtle" size="sm" class="grow sm:grow-0">{{ __('Prev Month') }}</flux:button>
            <flux:button wire:click="nextMonth" variant="subtle" size="sm" class="grow sm:grow-0">{{ __('Next Month') }}</flux:button>
        </div>

        <div class="flex flex-col lg:flex-row justify-between items-end gap-4">
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-48" />

                <flux:select wire:model.live="departmentId" label="{{ __('Department') }}" placeholder="{{ __('All Departments') }}" icon="building-office" class="w-full sm:w-48">
                    <flux:select.option value="">{{ __('All Departments') }}</flux:select.option>
                    @foreach($departments as $dept)
                        <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="managerId" label="{{ __('Manager') }}" placeholder="{{ __('All Managers') }}" icon="user-group" class="w-full sm:w-48">
                    <flux:select.option value="">{{ __('All Managers') }}</flux:select.option>
                    @foreach($managers as $manager)
                        <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if($departmentId || $managerId || $search)
                    <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
                @endif
            </div>

            <div class="flex gap-4 sm:gap-6 text-sm font-medium px-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-zinc-100 dark:border-zinc-800 w-full lg:w-auto justify-start lg:justify-end flex-wrap">
                <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-500" title="{{ __('Pending Requests') }}">
                    <flux:icon name="clock" class="w-4 h-4" />
                    <span>{{ __('Pending') }}: <strong>{{ $stats['pending'] }}</strong></span>
                </div>
                <div class="flex items-center gap-2 text-green-600 dark:text-green-500" title="{{ __('Approved Requests') }}">
                    <flux:icon name="check-circle" class="w-4 h-4" />
                    <span>{{ __('Approved') }}: <strong>{{ $stats['approved'] }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-xl max-h-[calc(100vh-300px)]">
        <table class="w-full text-sm text-left border-collapse min-w-200">
            <thead class="bg-zinc-50 dark:bg-zinc-800/90 text-xs uppercase font-medium text-zinc-500 sticky top-0 z-20 backdrop-blur-sm shadow-sm">
            <tr>
                <th class="py-3 sticky left-0 top-0 bg-zinc-50 dark:bg-zinc-800 z-30 border-b border-r border-zinc-200 dark:border-zinc-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-14 sm:w-48 text-center sm:text-left sm:px-4">
                    <span class="hidden sm:inline">{{ __('Employee') }}</span>
                    <flux:icon name="users" class="w-5 h-5 mx-auto sm:hidden" />
                </th>

                @foreach($matrix[0]['days'] ?? [] as $day)
                    <th class="px-1 sm:px-2 py-3 text-center min-w-10 border-b border-zinc-200 dark:border-zinc-700 {{ $day['status'] === 'off' ? 'bg-zinc-100/50 dark:bg-zinc-800/80' : '' }}">
                        <div class="flex flex-col items-center">
                            <span class="text-[10px] hidden sm:block">{{ $day['day_name'] }}</span>
                            <span class="text-sm sm:text-base font-bold">{{ $day['day_number'] }}</span>
                        </div>
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
            @foreach($matrix as $row)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition group">

                    <td class="py-2 sm:py-3 font-medium sticky left-0 bg-white dark:bg-zinc-900 group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800/50 z-10 border-r border-zinc-100 dark:border-zinc-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] px-1 sm:px-4">
                        <div class="flex items-center justify-center sm:justify-start gap-3">
                            <div class="relative group/avatar">
                                <flux:avatar src="{{ $row['user']->profile_photo_url ?? '' }}" name="{{ $row['user']->name }}" size="xs" class="shrink-0" />

                                <div class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded opacity-0 group-hover/avatar:opacity-100 transition pointer-events-none whitespace-nowrap z-50 sm:hidden">
                                    {{ $row['user']->name }}
                                </div>
                            </div>

                            <div class="hidden sm:flex flex-col min-w-0">
                                <span class="truncate w-32 font-semibold text-zinc-900 dark:text-zinc-100">{{ $row['user']->name }}</span>

                                @if($row['user']->departments->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($row['user']->departments as $dept)
                                            @php
                                                $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                                                $deptColor = $deptColors[$dept->id % count($deptColors)];
                                            @endphp
                                            <flux:badge size="xs" :color="$deptColor" class="truncate max-w-25">{{ $dept->name }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    @foreach($row['days'] as $day)
                        @php
                            $colorClass = match($day['status']) {
                                'present' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'vacation' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'sick' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                'home_office' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'off' => 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-600',
                                default => 'bg-zinc-50 text-zinc-400'
                            };
                            $icon = match($day['status']) {
                                'present' => 'check',
                                'vacation' => 'sun',
                                'sick' => 'plus',
                                'home_office' => 'home',
                                'off' => null,
                                default => null
                            };
                             $tooltip = $day['meta'] ?: ($day['status'] === 'present' ? __('Present') : __($day['status']));
                             if ($day['is_pending'] ?? false) {
                                 $tooltip .= ' (' . __('Pending') . ')';
                                 $colorClass = str_replace(['bg-yellow-100', 'bg-red-100', 'bg-blue-100'], ['bg-yellow-50 border-dashed border-yellow-300', 'bg-red-50 border-dashed border-red-300', 'bg-blue-50 border-dashed border-blue-300'], $colorClass);
                                 $colorClass .= ' border';
                             }
                        @endphp

                        <td class="p-0.5 sm:p-1 text-center h-10 sm:h-12 border-r border-zinc-50 dark:border-zinc-800 last:border-0 min-w-10">
                            @if($tooltip)
                                <flux:tooltip content="{{ $tooltip }}">
                                    <div class="w-full h-full rounded sm:rounded-md flex items-center justify-center {{ $colorClass }} cursor-help">
                                        @if($icon)
                                            <flux:icon :name="$icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                        @endif
                                    </div>
                                </flux:tooltip>
                            @else
                                <div class="w-full h-full rounded sm:rounded-md flex items-center justify-center {{ $colorClass }}">
                                    @if($icon)
                                        <flux:icon :name="$icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                    @endif
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap gap-4 text-sm text-zinc-600 dark:text-zinc-400 mt-2 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 justify-center sm:justify-start">
        <div class="flex items-center gap-2"><div class="w-5 h-5 bg-green-100 rounded border border-green-200 flex items-center justify-center"><flux:icon name="check" class="w-3 h-3 text-green-700" /></div> {{ __('Present') }}</div>
        <div class="flex items-center gap-2"><div class="w-5 h-5 bg-blue-100 rounded border border-blue-200 flex items-center justify-center"><flux:icon name="home" class="w-3 h-3 text-blue-700" /></div> {{ __('Home Office') }}</div>
        <div class="flex items-center gap-2"><div class="w-5 h-5 bg-yellow-100 rounded border border-yellow-200 flex items-center justify-center"><flux:icon name="sun" class="w-3 h-3 text-yellow-700" /></div> {{ __('Vacation') }}</div>
        <div class="flex items-center gap-2"><div class="w-5 h-5 bg-red-100 rounded border border-red-200 flex items-center justify-center"><flux:icon name="plus" class="w-3 h-3 text-red-700" /></div> {{ __('Sick Leave') }}</div>
        <div class="flex items-center gap-2"><div class="w-5 h-5 bg-zinc-100 rounded border border-zinc-200"></div> {{ __('Off / Holiday') }}</div>
    </div>
</div>