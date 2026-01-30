<flux:modal wire:model="showModal" class="w-full sm:w-120">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingId ? __('Edit Special Day') : __('New Special Day') }}</flux:heading>
            <flux:subheading>{{ __('Define a holiday or an extra workday.') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
            <flux:date-picker locale="hu-HU" wire:model="date" label="{{ __('Date') }}" />

            <flux:radio.group wire:model="type" label="{{ __('Type') }}">
                <flux:radio value="holiday" label="{{ __('Holiday') }}" description="{{ __('Day off (e.g. bridge day)') }}" />
                <flux:radio value="workday" label="{{ __('Workday') }}" description="{{ __('Extra working day (e.g. Saturday)') }}" />
            </flux:radio.group>

            <flux:input wire:model="description" label="{{ __('Description') }}" placeholder="{{ __('e.g. Bridge day for Aug 20') }}" />
        </div>

        <div class="flex flex-col-reverse sm:flex-row justify-between gap-2 mt-4">
            @if($editingId)
                <flux:button
                        wire:click="delete({{ $editingId }})"
                        variant="danger"
                        wire:confirm="{{ __('Are you sure?') }}"
                        class="w-full sm:w-auto"
                >
                    {{ __('Delete') }}
                </flux:button>
            @else
                <div class="hidden sm:block"></div> @endif

            <div class="flex flex-col-reverse sm:flex-row gap-2 w-full sm:w-auto">
                <flux:button wire:click="$set('showModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary" class="w-full sm:w-auto">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </div>
</flux:modal>