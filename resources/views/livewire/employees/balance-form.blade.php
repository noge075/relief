<flux:modal wire:model="showModal" class="w-full sm:w-120">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingBalanceId ? __('Edit Balance') : __('New Balance') }}</flux:heading>
            <flux:subheading>
                @if($editingBalanceId) {{ $year }}
                @else {{ __('Create a new leave balance record.') }}
                @endif
            </flux:subheading>
        </div>

        <div class="grid gap-4">
            @if($editingBalanceId)
                <flux:input value="{{ $userName }}" label="{{ __('Employee') }}" readonly />
            @else
                <flux:select wire:model.live="userId" label="{{ __('Employee') }}" placeholder="{{ __('Select...') }}">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($users as $user)
                        <flux:select.option value="{{ $user->id }}">
                            {{ $user->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model.live="year" label="{{ __('Year') }}" :disabled="$editingBalanceId" placeholder="{{ __('Select...') }}">
                <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                @foreach($availableYears as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="allowance" type="number" label="{{ __('Allowance') }}" step="1" />
            <flux:input wire:model="used" type="number" label="{{ __('Used') }}" step="1" description="{{ __('Manually adjust used days if necessary.') }}" />
        </div>

        <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="save" variant="primary" class="w-full sm:w-auto">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>