<flux:card class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900/30">
                <flux:icon name="clock" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ __('Attendance') }}</flux:heading>
                <flux:subheading>{{ \Carbon\Carbon::today()->translatedFormat('Y. F d. l') }}</flux:subheading>
            </div>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center py-4">
        @if($currentLog)
            <div class="text-center mb-4">
                <div class="text-sm text-zinc-500">{{ __('Checked in at:') }}</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $currentLog->check_in->format('H:i') }}
                </div>
            </div>
            <flux:button wire:click="checkOut" variant="danger" class="w-full md:w-auto min-w-50 h-12 text-lg">
                {{ __('Check Out') }}
            </flux:button>
        @else
            <flux:button wire:click="checkIn" variant="primary" class="w-full md:w-auto min-w-50 h-12 text-lg">
                {{ __('Check In') }}
            </flux:button>
        @endif
    </div>
</flux:card>
