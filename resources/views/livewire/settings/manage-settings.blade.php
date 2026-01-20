<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('System Settings') }}</flux:heading>
            <flux:subheading>{{ __('Configure global system settings.') }}</flux:subheading>
        </div>
    </div>

    <flux:card class="max-w-2xl space-y-6">
        <flux:heading size="lg">{{ __('Home Office Rules') }}</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="hoLimitDays" type="number" label="{{ __('Home Office Limit (Days)') }}" description="{{ __('Max days allowed per period.') }}" />
            <flux:input wire:model="hoLimitPeriod" type="number" label="{{ __('Period (Days)') }}" description="{{ __('Rolling period length.') }}" />
        </div>

        <div class="flex justify-end">
            <flux:button wire:click="save" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </flux:card>
</div>
