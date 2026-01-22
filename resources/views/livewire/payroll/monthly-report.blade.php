<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Monthly Report') }}</flux:heading>
            <flux:subheading>{{ __('Payroll data export.') }}</flux:subheading>
        </div>

        <div class="flex gap-2 items-center">
            @if($closure && $closure->is_closed)
                <div class="flex items-center gap-2 mr-4">
                    <flux:badge color="red" icon="lock-closed">{{ __('Closed') }}</flux:badge>
                    <span class="text-xs text-zinc-500">
                        {{ $closure->closed_at ? \Carbon\Carbon::parse($closure->closed_at)->format('Y.m.d H:i') : '' }}
                        ({{ $closure->closedBy->name ?? '-' }})
                    </span>
                </div>

                @can(\App\Enums\PermissionType::MANAGE_MONTHLY_CLOSURES->value)
                    <flux:button variant="ghost" icon="lock-open" wire:click="reopenMonth" wire:confirm="{{ __('Are you sure you want to reopen this month?') }}">{{ __('Reopen Month') }}</flux:button>
                @endcan
            @else
                @can(\App\Enums\PermissionType::MANAGE_MONTHLY_CLOSURES->value)
                    <flux:button variant="danger" icon="lock-closed" wire:click="closeMonth" wire:confirm="{{ __('Are you sure you want to close this month? No further changes will be allowed.') }}">{{ __('Close Month') }}</flux:button>
                @endcan
            @endif

            <flux:button variant="primary" icon="arrow-down-tray" wire:click="export">{{ __('Export to Excel') }}</flux:button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:select wire:model.live="year" class="w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="month" class="w-40">
                @foreach(range(1, 12) as $m)
                    <flux:select.option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Department') }}</flux:table.column>
                <flux:table.column>{{ __('Total Workdays') }}</flux:table.column>
                <flux:table.column>{{ __('Worked Days') }}</flux:table.column>
                <flux:table.column>{{ __('Vacation Days') }}</flux:table.column>
                <flux:table.column>{{ __('Sick Days') }}</flux:table.column>
                <flux:table.column>{{ __('Home Office Days') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($report as $row)
                    <flux:table.row :key="$row['user_id']">
                        <flux:table.cell class="font-medium">{{ $row['name'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['department'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['total_workdays'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['worked_days'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['vacation_days'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['sick_days'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['home_office_days'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @if($exports->total() > 0)
        <flux:card class="p-0! overflow-hidden">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Export History') }}</flux:heading>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($exports as $export)
                    <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <flux:icon name="document-text" class="text-zinc-400" />
                            <div>
                                <div class="font-medium text-sm">{{ $export->file_name }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $export->created_at->format('Y.m.d H:i') }}
                                    ({{ $export->size / 1024 > 1024 ? round($export->size / 1024 / 1024, 2) . ' MB' : round($export->size / 1024, 2) . ' KB' }})
                                </div>
                            </div>
                        </div>
                        <flux:button variant="ghost" size="sm" icon="arrow-down-tray" href="{{ $export->getUrl() }}" target="_blank">{{ __('Download') }}</flux:button>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-zinc-500 w-full md:w-1/3">
                    {{ __('Showing') }} <span class="font-medium">{{ $exports->firstItem() }}-{{ $exports->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $exports->total() }}</span> {{ __('results') }}
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
                            </flux:select>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-1/3 flex justify-end">
                    {{ $exports->links('pagination.buttons') }}
                </div>
            </div>
        </flux:card>
    @endif
</div>
