<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Manage Special Days') }}</flux:heading>
            <flux:subheading>{{ __('Configure holidays and extra workdays.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:select wire:model.live="year" class="w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 2) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Special Day') }}</flux:button>
        </div>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Source') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($specialDays as $day)
                    <flux:table.row :key="$day['date']">
                        <flux:table.cell class="font-medium">
                            {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('Y.m.d (l)') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $day['name'] }}</flux:table.cell>
                        <flux:table.cell>
                            @if($day['type'] === 'holiday')
                                <flux:badge color="green" size="sm">{{ __('Holiday') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Workday') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['source'] === 'auto')
                                <flux:badge color="zinc" size="sm" icon="server">{{ __('System') }}</flux:badge>
                            @else
                                <flux:badge color="blue" size="sm" icon="user">{{ __('Manual') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($day['source'] === 'manual')
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="edit({{ $day['id'] }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="delete({{ $day['id'] }})" icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @else
                                <span class="text-zinc-400 text-xs italic">{{ __('Read-only') }}</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $specialDays->links() }}
        </div>
    </flux:card>

    <!-- Modal -->
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
</div>
