<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Attendance') }}</flux:heading>
            <flux:subheading>{{ __('Track your daily attendance.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
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

            <flux:button variant="primary" icon="arrow-down-tray" href="{{ route('attendance.download-pdf', ['year' => $year, 'month' => $month]) }}" target="_blank">{{ __('Download PDF') }}</flux:button>
        </div>
    </div>

    @if(auth()->user()->employment_type?->needsTimeTracking())
        <flux:card class="flex flex-col items-center justify-center py-6 gap-4 bg-zinc-50 dark:bg-zinc-800/50 border-dashed">
            <div class="text-center">
                <div class="text-sm text-zinc-500 mb-1">{{ \Carbon\Carbon::today()->translatedFormat('Y. F d. l') }}</div>
                @if($currentLog)
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">
                        {{ __('Checked in at:') }} {{ $currentLog->check_in->format('H:i') }}
                    </div>
                    <flux:button wire:click="checkOut" variant="danger" class="min-w-[200px] h-12 text-lg" icon="arrow-right-start-on-rectangle">
                        {{ __('Check Out') }}
                    </flux:button>
                @else
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">
                        {{ __('Not checked in') }}
                    </div>
                    <flux:button wire:click="checkIn" variant="primary" class="min-w-[200px] h-12 text-lg" icon="arrow-right-end-on-rectangle">
                        {{ __('Check In') }}
                    </flux:button>
                @endif
            </div>
        </flux:card>
    @endif

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Check In') }}</flux:table.column>
                <flux:table.column>{{ __('Check Out') }}</flux:table.column>
                <flux:table.column>{{ __('Worked Hours') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                @if(auth()->user()->employment_type?->needsTimeTracking())
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                @endif
            </flux:table.columns>

            <flux:table.rows>
                @foreach($days as $day)
                    @php
                        $rowClass = $day['is_weekend'] || $day['is_holiday'] ? 'bg-zinc-50/50 dark:bg-zinc-900/50' : '';
                        if ($day['is_today']) $rowClass .= ' bg-blue-50/30 dark:bg-blue-900/10';
                    @endphp
                    <flux:table.row :key="$day['date']->format('Y-m-d')" class="{{ $rowClass }}">
                        <flux:table.cell class="font-medium {{ $day['is_weekend'] || $day['is_holiday'] ? 'text-zinc-400' : '' }}">
                            {{ $day['date']->translatedFormat('Y.m.d (l)') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $day['check_in'] ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $day['check_out'] ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['worked_hours'] > 0)
                                {{ number_format($day['worked_hours'], 2) }} {{ __('h') }}
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['status'] && $day['status'] !== '-')
                                @php
                                    $color = match($day['status_type']) {
                                        'present' => 'green',
                                        'vacation' => 'yellow',
                                        'sick' => 'red',
                                        'home_office' => 'blue',
                                        'holiday' => 'zinc',
                                        'weekend' => 'zinc',
                                        'scheduled' => 'zinc',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$color">{{ __($day['status']) }}</flux:badge>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        @if(auth()->user()->employment_type?->needsTimeTracking())
                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editLog('{{ $day['date']->format('Y-m-d') }}')">{{ __('Edit') }}</flux:button>
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Edit Modal -->
    <flux:modal wire:model="showEditModal" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Attendance') }}</flux:heading>
                <flux:subheading>{{ $editingDate ? \Carbon\Carbon::parse($editingDate)->translatedFormat('Y. F d.') : '' }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editingCheckIn" type="time" label="{{ __('Check In') }}" />
                    <flux:input wire:model="editingCheckOut" type="time" label="{{ __('Check Out') }}" />
                </div>

                <div class="text-sm text-zinc-500">
                    {{ __('Worked hours will be calculated automatically.') }}
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showEditModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="saveLog" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
