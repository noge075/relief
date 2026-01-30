<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Home Office Policies') }}</flux:heading>
            <flux:subheading>{{ __('Create and manage home office policies.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create" class="w-full sm:w-auto">
            {{ __('New Policy') }}
        </flux:button>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name...') }}" class="w-full sm:w-64" />

            @if($search)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'limit_days'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('limit_days')">{{ __('Limit Days') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortCol === 'period_days'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('period_days')">{{ __('Period Days') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($policies as $policy)
                    <flux:table.row :key="$policy->id">
                        <flux:table.cell class="font-medium">
                            <div class="flex flex-col">
                                <span class="text-zinc-900 dark:text-zinc-100">{{ $policy->name }}</span>

                                <div class="md:hidden mt-1 flex flex-wrap gap-2 items-center">
                                    <flux:badge size="xs" color="zinc">{{ $policy->type->label() }}</flux:badge>

                                    @if($policy->limit_days && $policy->period_days)
                                        <span class="text-xs text-zinc-500">
                                            {{ $policy->limit_days }} / {{ $policy->period_days }} {{ __('days') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge size="sm" color="zinc">{{ $policy->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $policy->limit_days ?: '-' }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $policy->period_days ?: '-' }}</flux:table.cell>

                        <flux:table.cell>
                            {{-- MOBIL: Nagy szerkesztés gomb --}}
                            <button
                                    wire:click="edit({{ $policy->id }})"
                                    class="md:hidden w-10 h-10 flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 active:scale-95 transition-transform"
                            >
                                <flux:icon.pencil-square class="size-5" />
                            </button>

                            {{-- DESKTOP: Dropdown --}}
                            <div class="hidden md:block">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $policy->id }})">{{ __('Edit') }}</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $policy->id }})" wire:confirm="{{ __('Are you sure you want to delete this policy?') }}">{{ __('Delete') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($policies->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $policies->firstItem() }}-{{ $policies->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $policies->total() }}</span> {{ __('results') }}
                @else
                    {{ __('No results found.') }}
                @endif
            </div>

            <div class="w-full md:w-1/3 flex justify-center">
                <div class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                    <div class="bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-500 border-r border-zinc-200 dark:border-zinc-700 whitespace-nowrap">
                        {{ __('Per Page') }}
                    </div>
                    <div class="w-20">
                        <flux:select wire:model.live="perPage" class="border-0! shadow-none! rounded-none! focus:ring-0!">
                            <flux:select.option value="5">5</flux:select.option>
                            <flux:select.option value="10">10</flux:select.option>
                            <flux:select.option value="15">15</flux:select.option>
                            <flux:select.option value="25">25</flux:select.option>
                            <flux:select.option value="50">50</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 flex justify-end">
                {{ $policies->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <flux:modal wire:model="showModal" class="w-full sm:w-140">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? __('Edit Policy') : __('New Policy') }}</flux:heading>
                <flux:subheading>{{ __('Define the home office policy details.') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <flux:input wire:model="name" label="{{ __('Name') }}" />
                <flux:textarea wire:model="description" label="{{ __('Description') }}" />

                <flux:select wire:model.live="type" label="{{ __('Policy Type') }}">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($policyTypes as $policyType)
                        <flux:select.option value="{{ $policyType->value }}">
                            {{ $policyType->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if($type && ($type === 'limited' || (is_object($type) && $type->value === 'limited')))
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="limit_days" type="number" label="{{ __('Limit Days') }}" />
                        <flux:input wire:model="period_days" type="number" label="{{ __('Period Days') }}" />
                    </div>
                @endif
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-between gap-2 mt-4">
                @if($isEditing && isset($policyId)) <flux:button
                        wire:click="delete({{ $policyId }})"
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
</div>