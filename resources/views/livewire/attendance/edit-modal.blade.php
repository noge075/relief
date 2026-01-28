<flux:modal wire:model="showEditModal" class="min-w-150">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Edit Attendance') }}</flux:heading>
            <flux:subheading>{{ __('Editing attendance for :date', ['date' => $editingDate ? \Carbon\Carbon::parse($editingDate)->translatedFormat('Y. F d.') : '']) }}</flux:subheading>
        </div>

        <div class="space-y-4">
            @if(auth()->user()->workSchedule?->start_time && auth()->user()->workSchedule?->end_time)
                <div class="text-sm text-zinc-500 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    {{ __('Scheduled work time:') }}
                    <span class="font-semibold">{{ \Carbon\Carbon::parse(auth()->user()->workSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse(auth()->user()->workSchedule->end_time)->format('H:i') }}</span>
                </div>
            @endif

            <flux:input wire:model="editingCheckIn" type="time" label="{{ __('Check In') }}" />
            <flux:input wire:model="editingCheckOut" type="time" label="{{ __('Check Out') }}" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showEditModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="saveLog" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
