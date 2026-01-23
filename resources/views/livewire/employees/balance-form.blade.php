<flux:modal wire:model="showModal" class="min-w-100">
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
                <flux:select wire:model="userId" label="{{ __('Employee') }}" placeholder="{{ __('Select employee...') }}">
                    @foreach($users as $user)
                        <flux:select.option value="{{ $user->id }}">
                            {{ $user->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="allowance" type="number" label="{{ __('Allowance') }}" step="1" />
            <flux:input wire:model="used" type="number" label="{{ __('Used') }}" step="1" description="{{ __('Manually adjust used days if necessary.') }}" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="save" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
