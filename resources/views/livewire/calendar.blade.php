<div class="flex flex-col gap-6 w-full">

    <div class="flex flex-col md:flex-row items-center justify-between mb-4 gap-4 md:gap-0" x-data="{ open: false }">
        <div class="relative flex items-center gap-2 w-full md:w-auto justify-between md:justify-start">
            <flux:button variant="ghost" icon-trailing="chevron-down" class="text-xl! font-bold! text-zinc-800 dark:text-zinc-100 px-2 -ml-2" @click="open = !open">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('Y. F') }}
            </flux:button>

            <div x-show="open" @click.outside="open = false" style="display: none;" class="absolute top-full left-0 mt-2 z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl p-4 w-75">
                <div class="flex items-center justify-between mb-4 px-2">
                    <flux:button icon="chevron-left" variant="subtle" size="sm" wire:click.stop="jumpToDate({{ $this->currentYear - 1 }}, {{ $this->currentMonth }})" />
                    <span class="font-bold text-lg text-zinc-700 dark:text-zinc-200">{{ $this->currentYear }}</span>
                    <flux:button icon="chevron-right" variant="subtle" size="sm" wire:click.stop="jumpToDate({{ $this->currentYear + 1 }}, {{ $this->currentMonth }})" />
                </div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(range(1, 12) as $m)
                        <button type="button" wire:click="jumpToDate({{ $this->currentYear }}, {{ $m }}); open = false" class="py-2 px-3 text-sm rounded-md transition text-center font-medium w-full {{ $m === $this->currentMonth ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('M') }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg shadow-sm w-full md:w-auto justify-center">
            <flux:button icon="chevron-left" wire:click="prevMonth" variant="ghost" size="sm" class="h-8! w-8!" />
            <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
            <flux:button wire:click="jumpToToday" variant="ghost" size="sm" class="text-xs font-medium px-3">{{ __('Today') }}</flux:button>
            <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
            <flux:button icon="chevron-right" wire:click="nextMonth" variant="ghost" size="sm" class="h-8! w-8!" />
        </div>
    </div>

    <div class="border-t border-zinc-200 dark:border-zinc-700 md:border md:rounded-xl md:overflow-hidden md:shadow-sm bg-white dark:bg-zinc-900">

        <div class="hidden md:grid grid-cols-7 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div class="py-3 text-center text-xs font-bold text-zinc-500 uppercase tracking-wider">
                    {{ __($dayName) }}
                </div>
            @endforeach
        </div>

        <div class="flex flex-col md:grid md:grid-cols-7">
            @foreach($this->calendarDays as $day)
                <div
                    @if(!$day['is_holiday'])
                        wire:click="selectDate('{{ $day['date_string'] }}')"
                    @endif
                    class="
                        {{ !$day['is_current_month'] ? 'hidden md:block' : '' }}

                        /* MOBIL STÍLUSOK (Lista elem) */
                        w-full flex flex-row items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-800

                        /* DESKTOP STÍLUSOK (Grid cella) */
                        md:block md:min-h-30 md:p-2 md:border-b-0 md:border-r

                        /* Közös */
                        relative group transition
                        {{ $day['is_holiday'] ? 'cursor-not-allowed opacity-60 bg-zinc-100 dark:bg-zinc-800/50' : 'cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800' }}
                        {{ !$day['is_current_month'] && !$day['is_holiday'] ? 'bg-zinc-50/50 dark:bg-zinc-900/50 opacity-40 grayscale' : '' }}
                    "
                >
                    <div class="flex items-center md:items-start md:justify-between gap-3 md:gap-0 w-full md:w-auto">

                        <div class="flex flex-col items-center">
                            <span class="md:hidden text-[10px] font-bold text-zinc-400 uppercase tracking-wide mb-1">
                                {{ $day['date']->translatedFormat('D') }}
                            </span>

                            <span class="
                                text-sm font-semibold w-8 h-8 md:w-7 md:h-7 flex items-center justify-center rounded-full
                                {{ $day['is_today'] ? 'bg-indigo-600 text-white shadow-md' : 'text-zinc-500 bg-zinc-100 md:bg-transparent dark:bg-zinc-800' }}
                                {{ $day['is_holiday'] ? 'text-red-600' : '' }}
                            ">
                                {{ $day['date']->day }}
                            </span>
                        </div>

                        @if($day['is_holiday'])
                            <span class="md:hidden text-xs font-medium text-red-500 ml-2">
                                {{ $day['holiday_name'] ?? __('Holiday') }}
                            </span>
                        @endif

                        @if($day['is_current_month'] && !$day['is_holiday'] && !$day['is_weekend'])
                            <div class="hidden md:block opacity-0 group-hover:opacity-100 transition">
                                <flux:icon name="plus" class="w-4 h-4 text-zinc-400" />
                            </div>
                        @endif
                    </div>

                    @if($day['is_holiday'])
                        <div class="hidden md:block mt-1 text-[10px] font-medium text-red-500 truncate text-center">
                            {{ $day['holiday_name'] ?? __('Holiday') }}
                        </div>
                    @endif

                    <div class="mt-0 md:mt-2 grow md:grow-0 flex justify-end md:block ml-4 md:ml-0">
                        @if($day['event'])
                            @php
                                $type = $day['event']->type->value;
                                $isPending = $day['event']->status->value === 'pending';
                                $color = match($type) { 'vacation' => 'yellow', 'sick' => 'red', 'home_office' => 'blue', default => 'zinc' };
                                $label = match($type) { 'vacation' => __('Vacation'), 'sick' => __('Sick Leave'), 'home_office' => __('Home Office'), default => __('Other') };
                            @endphp

                            <flux:badge
                                    :color="$color"
                                    size="sm"
                                    class="w-auto md:w-full justify-center md:justify-start truncate cursor-pointer gap-1.5"
                                    wire:click.stop="editEvent('{{ $day['event']->id }}')"
                            >
                                @php
                                    $iconName = match($type) {
                                        'vacation' => 'sun',
                                        'home_office' => 'home',
                                        default => 'plus'
                                    };
                                @endphp

                                <flux:icon :name="$iconName" class="size-3.5 shrink-0 opacity-70" />

                                <span class="hidden sm:inline truncate">{{ $label }}</span>

                                @if($isPending)
                                    <span class="ml-1 text-[9px] opacity-70">({{ __('Pending') }})</span>
                                @endif
                            </flux:badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 text-xs flex flex-wrap gap-4 md:gap-6 border-t border-zinc-200 dark:border-zinc-700">

            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-zinc-400"></div>
                <span class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Workdays') }}: <strong>{{ $this->monthlyStats['workdays'] }}</strong>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                <span class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Holidays/Weekends') }}: <strong>{{ $this->monthlyStats['holidays'] }}</strong>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                <span class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Requests') }}: <strong>{{ $this->monthlyStats['requests'] }}</strong>
                </span>
            </div>

        </div>
    </div>

    @include('livewire.calendar-request-form')

</div>
