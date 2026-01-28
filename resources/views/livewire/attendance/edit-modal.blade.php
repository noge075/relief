<flux:modal wire:model="showEditModal" class="min-w-100">
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