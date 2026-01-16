<flux:modal wire:model="showRequestModal" class="w-full md:min-w-[400px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Request') : __('New Request') }}
            </flux:heading>
            <flux:subheading>
                @if($editingId) {{ __('Modify the selected request.') }}
                @else {{ __('Select type and period.') }}
                @endif
            </flux:subheading>
        </div>

        <div class="grid gap-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="selectedDate" type="date" label="{{ __('Start Date') }}" />
                <flux:input wire:model="endDate" type="date" label="{{ __('End Date') }}" />
            </div>

            <flux:radio.group wire:model="requestType" label="{{ __('Type') }}">
                <flux:radio value="vacation" label="🏖️ {{ __('Vacation') }}" description="{{ __('Deducted from annual balance') }}" />
                <flux:radio value="home_office" label="🏠 {{ __('Home Office') }}" />
                <flux:radio value="sick" label="💊 {{ __('Sick Leave') }}" />
            </flux:radio.group>

            <flux:textarea wire:model="reason" label="{{ __('Reason') }}" rows="2" placeholder="{{ __('Optional comment...') }}" />
        </div>

        <div class="flex flex-col-reverse md:flex-row justify-between gap-4 md:gap-2">
            @if($editingId)
                <flux:button variant="danger" class="w-full md:w-auto" wire:click="deleteEvent({{ $editingId }})">{{ __('Delete') }}</flux:button>
            @else
                <div></div>
            @endif
            <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                <flux:button variant="ghost" class="w-full md:w-auto" wire:click="$set('showRequestModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" class="w-full md:w-auto" wire:click="saveEvent">
                    {{ $editingId ? __('Save') : __('Submit') }}
                </flux:button>
            </div>
        </div>
    </div>
</flux:modal>
