<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Manage Leave Balances') }}</flux:heading>
            <flux:subheading>{{ __('Adjust annual leave allowances for employees.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Balance') }}</flux:button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />

            <flux:select wire:model.live="year" class="w-full sm:w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="departmentFilter" placeholder="{{ __('All Departments') }}" icon="building-office" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Departments') }}</flux:select.option>
                @foreach($departments as $dept)
                    <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if($search || $departmentFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'year'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('year')">{{ __('Year') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'allowance'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('allowance')">{{ __('Allowance') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortCol === 'used'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('used')">{{ __('Used') }}</flux:table.column>
                <flux:table.column>{{ __('Remaining') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($balances as $balance)
                    <flux:table.row :key="$balance->id">
                        <flux:table.cell class="font-medium">
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $balance->user->profile_photo_url ?? '' }}" name="{{ $balance->user->name }}" />
                                <div>
                                    <div class="font-medium">{{ $balance->user->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $balance->user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $balance->year }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc">{{ $balance->allowance }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="yellow">{{ $balance->used }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="green">{{ $balance->allowance - $balance->used }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $balance->id }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500">
                {{ __('Showing') }} <span class="font-medium">{{ $balances->firstItem() }}</span> {{ __('to') }} <span class="font-medium">{{ $balances->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $balances->total() }}</span> {{ __('results') }}
            </div>
            {{ $balances->links() }}
        </div>
    </flux:card>

    @include('livewire.employees.balance-form')
</div>
