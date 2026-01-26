<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Status Board') }}</flux:heading>
            <flux:subheading>{{ __('Who is where?') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar & Navigation -->
    <div class="flex flex-col gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">

        <!-- Top Row: Date & Navigation -->
        <div class="flex flex-col lg:flex-row justify-between items-center gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-center">
                <flux:input wire:model.live="startDate" type="date" label="{{ __('Start Date') }}" icon="calendar" class="w-full sm:w-40" />
                <flux:input wire:model.live="endDate" type="date" label="{{ __('End Date') }}" icon="calendar" class="w-full sm:w-40" />
            </div>

            <div class="flex items-center gap-4 w-full lg:w-auto justify-between lg:justify-end">
                <flux:button icon="chevron-left" wire:click="prevPeriod" variant="ghost" size="sm" />
                <div class="font-medium text-sm flex items-center gap-2 whitespace-nowrap">
                    <flux:icon name="calendar-days" class="w-4 h-4 text-zinc-400" />
                    {{ $periodStart->translatedFormat('M d.') }} - {{ $periodEnd->translatedFormat('M d.') }}
                </div>
                <div class="flex gap-2">
                    <flux:button wire:click="thisWeek" variant="subtle" size="sm">{{ __('This Week') }}</flux:button>
                    <flux:button icon="chevron-right" wire:click="nextPeriod" variant="ghost" size="sm" />
                </div>
            </div>
        </div>

        <!-- Quick Date Filters -->
        <div class="flex flex-wrap gap-2 justify-center lg:justify-start border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <flux:button wire:click="previousWeek" variant="subtle" size="sm">{{ __('Previous Week') }}</flux:button>
            <flux:button wire:click="thisMonth" variant="subtle" size="sm">{{ __('This Month') }}</flux:button>
            <flux:button wire:click="previousMonth" variant="subtle" size="sm">{{ __('Previous Month') }}</flux:button>
        </div>

        <!-- Bottom Row: Filters & Stats -->
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
                    <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
                @endif
            </div>

            <!-- Stats -->
            <div class="flex gap-6 text-sm font-medium px-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-zinc-100 dark:border-zinc-800 w-full lg:w-auto justify-end">
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

    <div class="overflow-auto border border-zinc-200 dark:border-zinc-700 rounded-xl max-h-150">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-zinc-50 dark:bg-zinc-800/90 text-xs uppercase font-medium text-zinc-500 sticky top-0 z-20 backdrop-blur-sm">
                <tr>
                    <th class="px-4 py-3 sticky left-0 top-0 bg-zinc-50 dark:bg-zinc-800 z-30 w-48 border-b border-r border-zinc-200 dark:border-zinc-700 shadow-sm">{{ __('Employee') }}</th>
                    @foreach($matrix[0]['days'] ?? [] as $day)
                        <th class="px-2 py-3 text-center min-w-10 border-b border-zinc-200 dark:border-zinc-700 {{ $day['status'] === 'off' ? 'bg-zinc-100/50 dark:bg-zinc-800/80' : '' }}">
                            <div class="flex flex-col">
                                <span>{{ $day['day_name'] }}</span>
                                <span class="text-lg font-bold">{{ $day['day_number'] }}</span>
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                @foreach($matrix as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-4 py-3 font-medium sticky left-0 bg-white dark:bg-zinc-900 z-10 border-r border-zinc-100 dark:border-zinc-800 shadow-sm">
                            <div class="flex items-center gap-2">
                                <flux:avatar src="{{ $row['user']->profile_photo_url ?? '' }}" name="{{ $row['user']->name }}" size="xs" />
                                <div class="flex flex-col">
                                    <span class="truncate max-w-30">{{ $row['user']->name }}</span>
                                    @if($row['user']->departments->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($row['user']->departments as $dept)
                                                @php
                                                    $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                                                    $deptColor = $deptColors[$dept->id % count($deptColors)];
                                                @endphp
                                                <flux:badge size="xs" :color="$deptColor">{{ $dept->name }}</flux:badge>
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
                                    'sick' => 'plus', // medical cross
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
                            <td class="p-1 text-center h-12 border-r border-zinc-50 dark:border-zinc-800 last:border-0">
                                @if($tooltip)
                                    <flux:tooltip content="{{ $tooltip }}">
                                        <div class="w-full h-full rounded-md flex items-center justify-center {{ $colorClass }} cursor-help">
                                            @if($icon)
                                                <flux:icon :name="$icon" class="w-4 h-4" />
                                            @endif
                                        </div>
                                    </flux:tooltip>
                                @else
                                    <div class="w-full h-full rounded-md flex items-center justify-center {{ $colorClass }}">
                                        @if($icon)
                                            <flux:icon :name="$icon" class="w-4 h-4" />
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

    <div class="flex flex-wrap gap-6 text-sm text-zinc-600 dark:text-zinc-400 mt-2 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-green-100 rounded border border-green-200 flex items-center justify-center"><flux:icon name="check" class="w-3 h-3 text-green-700" /></div> {{ __('Present') }}</div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-blue-100 rounded border border-blue-200 flex items-center justify-center"><flux:icon name="home" class="w-3 h-3 text-blue-700" /></div> {{ __('Home Office') }}</div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-yellow-100 rounded border border-yellow-200 flex items-center justify-center"><flux:icon name="sun" class="w-3 h-3 text-yellow-700" /></div> {{ __('Vacation') }}</div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-red-100 rounded border border-red-200 flex items-center justify-center"><flux:icon name="plus" class="w-3 h-3 text-red-700" /></div> {{ __('Sick Leave') }}</div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-zinc-100 rounded border border-zinc-200"></div> {{ __('Off / Holiday') }}</div>
    </div>
</div>
