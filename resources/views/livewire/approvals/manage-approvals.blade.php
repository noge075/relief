<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Approvals') }}</flux:heading>
            <flux:subheading>{{ __('Manage leave requests from your team.') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="typeFilter" placeholder="{{ __('All Types') }}" icon="tag" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                <flux:select.option value="vacation">{{ __('Vacation') }}</flux:select.option>
                <flux:select.option value="sick">{{ __('Sick Leave') }}</flux:select.option>
                <flux:select.option value="home_office">{{ __('Home Office') }}</flux:select.option>
            </flux:select>

            @if($search || $typeFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Employee') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'start_date'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('start_date')">{{ __('Date') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'days_count'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('days_count')">{{ __('Days') }}</flux:table.column>
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
                        <flux:table.cell class="truncate max-w-[200px]">
                            {{ $request->reason }}
                            @if($request->has_warning)
                                <flux:tooltip content="{{ $request->warning_message }}">
                                    <flux:icon name="exclamation-triangle" class="w-4 h-4 text-yellow-500 inline-block ml-1 cursor-help" />
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="check" wire:click="approve({{ $request->id }})">{{ __('Approve') }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="x-mark" variant="danger" wire:click="openRejectModal({{ $request->id }})">{{ __('Reject') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500">
                {{ __('Showing') }} <span class="font-medium">{{ $requests->firstItem() }}</span> {{ __('to') }} <span class="font-medium">{{ $requests->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $requests->total() }}</span> {{ __('results') }}
            </div>
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
