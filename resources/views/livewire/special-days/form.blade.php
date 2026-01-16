<flux:modal wire:model="showModal" class="min-w-[500px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingId ? __('Edit Special Day') : __('New Special Day') }}</flux:heading>
            <flux:subheading>{{ __('Define a holiday or an extra workday.') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
            <flux:input wire:model="date" type="date" label="{{ __('Date') }}" />

            <flux:radio.group wire:model="type" label="{{ __('Type') }}">
                <flux:radio value="holiday" label="{{ __('Holiday') }}" description="{{ __('Day off (e.g. bridge day)') }}" />
                <flux:radio value="workday" label="{{ __('Workday') }}" description="{{ __('Extra working day (e.g. Saturday)') }}" />
            </flux:radio.group>

            <flux:input wire:model="description" label="{{ __('Description') }}" placeholder="{{ __('e.g. Bridge day for Aug 20') }}" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="save" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
