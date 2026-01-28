<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Attendance') }}</flux:heading>
            <flux:subheading>{{ __('Track your daily attendance.') }}</flux:subheading>
        </div>

        <div class="flex gap-2 items-center">
            <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg shadow-sm">
                <flux:button wire:click="jumpToPreviousMonth" variant="ghost" size="sm" class="h-8! w-8!" icon="chevron-left" />
                <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                <flux:button wire:click="jumpToCurrentMonth" variant="ghost" size="sm" class="text-xs font-medium px-3">{{ __('Current Month') }}</flux:button>
                <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                <flux:button wire:click="jumpToNextMonth" variant="ghost" size="sm" class="h-8! w-8!" icon="chevron-right" />
            </div>

            <flux:select wire:model.live="year" class="w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="month" class="w-40">
                @foreach(range(1, 12) as $m)
                    <flux:select.option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button
                variant="primary"
                icon="arrow-down-tray"
                wire:click="downloadPdf"
            >
                {{ __('Download PDF') }}
            </flux:button>
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Check In') }}</flux:table.column>
                <flux:table.column>{{ __('Check Out') }}</flux:table.column>
                <flux:table.column>{{ __('Worked Hours') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($days as $day)
                    @php
                        $isWeekend = $day->date->isWeekend();
                        $isHoliday = $day->status === \App\Enums\AttendanceStatusType::HOLIDAY;
                        $isToday = $day->date->isToday();
                        $rowClass = $isWeekend || $isHoliday ? 'bg-zinc-50/50 dark:bg-zinc-900/50' : '';
                        if ($isToday) $rowClass .= ' bg-blue-50/30 dark:bg-blue-900/10';
                    @endphp
                    <flux:table.row :key="$day->date->format('Y-m-d')" class="{{ $rowClass }}">
                        <flux:table.cell class="font-medium {{ $isWeekend || $isHoliday ? 'text-zinc-400' : '' }}">
                            {{ $day->date->translatedFormat('Y.m.d (l)') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $day->check_in ? $day->check_in->format('H:i') : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $day->check_out ? $day->check_out->format('H:i') : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day->worked_hours > 0)
                                {{ number_format($day->worked_hours, 2) }} {{ __('h') }}
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $color = match($day->status) {
                                    \App\Enums\AttendanceStatusType::PRESENT => 'green',
                                    \App\Enums\AttendanceStatusType::VACATION => 'yellow',
                                    \App\Enums\AttendanceStatusType::SICK_LEAVE => 'red',
                                    \App\Enums\AttendanceStatusType::HOME_OFFICE => 'blue',
                                    \App\Enums\AttendanceStatusType::HOLIDAY => 'zinc',
                                    \App\Enums\AttendanceStatusType::OFF => 'zinc',
                                    \App\Enums\AttendanceStatusType::UNPAID => 'gray',
                                    \App\Enums\AttendanceStatusType::SCHEDULED => 'sky',
                                    \App\Enums\AttendanceStatusType::WEEKEND => 'zinc',
                                    default => 'zinc'
                                };
                            @endphp
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" :color="$color">{{ $day->status->label() }}</flux:badge>
                                @if($day->status === \App\Enums\AttendanceStatusType::HOLIDAY && $day->holiday_name)
                                    <span class="text-xs text-zinc-500">{{ $day->holiday_name }}</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($this->canEditLog($day))
                                <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editLog('{{ $day->date->format('Y-m-d') }}')">{{ __('Edit') }}</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @include('livewire.attendance.edit-modal')
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        @this.on('open-pdf-new-tab', ({ url }) => {
            window.open(url, '_blank');
        });
    });
</script>
@endpush
