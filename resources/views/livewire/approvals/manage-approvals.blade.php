<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Approvals') }}</flux:heading>
            <flux:subheading>{{ __('Manage leave requests from your team.') }}</flux:subheading>
        </div>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Employee') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Days') }}</flux:table.column>
                <flux:table.column>{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($requests as $request)
                    <flux:table.row :key="$request->id">
                        <flux:table.cell class="font-medium">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $request->user->profile_photo_url ?? '' }}" name="{{ $request->user->name }}" />
                                <div>
                                    <div class="font-medium">{{ $request->user->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $request->user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $type = $request->type->value;
                                $color = match($type) { 'vacation' => 'yellow', 'sick' => 'red', 'home_office' => 'blue', default => 'zinc' };
                                $label = match($type) { 'vacation' => __('Vacation'), 'sick' => __('Sick Leave'), 'home_office' => __('Home Office'), default => __('Other') };
                            @endphp
                            <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $request->start_date->format('Y.m.d') }}
                            @if($request->start_date != $request->end_date)
                                - {{ $request->end_date->format('Y.m.d') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $request->days_count }}</flux:table.cell>
                        <flux:table.cell class="truncate max-w-[200px]">{{ $request->reason }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button variant="primary" size="sm" icon="check" wire:click="approve({{ $request->id }})">{{ __('Approve') }}</flux:button>
                                <flux:button variant="danger" size="sm" icon="x-mark" wire:click="openRejectModal({{ $request->id }})">{{ __('Reject') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </flux:card>

    <!-- Reject Modal -->
    <flux:modal wire:model="showRejectModal" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Reject Request') }}</flux:heading>
                <flux:subheading>{{ __('Please provide a reason for rejection.') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <flux:textarea wire:model="managerComment" label="{{ __('Manager Comment') }}" rows="3" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRejectModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="reject" variant="danger">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
