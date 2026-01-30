<div class="flex flex-col gap-6">
    <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Monthly Report') }}</flux:heading>
            <flux:subheading>{{ __('Payroll data export.') }}</flux:subheading>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center w-full lg:w-auto">
            @if($closure && $closure->is_closed)
                <div class="flex flex-wrap items-center gap-2 mb-2 sm:mb-0 sm:mr-4">
                    <flux:badge color="red" icon="lock-closed">{{ __('Closed') }}</flux:badge>
                    <div class="text-xs text-zinc-500 flex flex-col sm:flex-row sm:gap-1">
                        <span>{{ $closure->closed_at ? \Carbon\Carbon::parse($closure->closed_at)->format('Y.m.d H:i') : '' }}</span>
                        <span class="hidden sm:inline">User:</span>
                        <span class="font-medium">({{ $closure->closedBy->name ?? '-' }})</span>
                    </div>
                </div>

                @can(\App\Enums\PermissionType::MANAGE_MONTHLY_CLOSURES->value)
                    <flux:button variant="ghost" icon="lock-open" wire:click="reopenMonth" wire:confirm="{{ __('Are you sure you want to reopen this month?') }}" class="w-full sm:w-auto justify-center">
                        {{ __('Reopen Month') }}
                    </flux:button>
                @endcan
            @else
                @can(\App\Enums\PermissionType::MANAGE_MONTHLY_CLOSURES->value)
                    <flux:button variant="danger" icon="lock-closed" wire:click="closeMonth" wire:confirm="{{ __('Are you sure you want to close this month? No further changes will be allowed.') }}" class="w-full sm:w-auto justify-center">
                        {{ __('Close Month') }}
                    </flux:button>
                @endcan
            @endif

            <flux:button variant="primary" icon="arrow-down-tray" wire:click="export" class="w-full sm:w-auto justify-center">
                {{ __('Export to Excel') }}
            </flux:button>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:select wire:model.live="year" class="w-full sm:w-32">
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="month" class="w-full sm:w-40">
                @foreach(range(1, 12) as $m)
                    <flux:select.option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Employee') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Department') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Total') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Worked') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Vacation') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Sick') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('HO') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($report as $row)
                    <flux:table.row :key="$row['user_id']">
                        <flux:table.cell>
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['name'] }}</span>
                                <span class="text-xs text-zinc-500 md:hidden">{{ $row['department'] }}</span>

                                <div class="grid grid-cols-2 gap-2 mt-2 md:hidden">
                                    <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-800 p-1.5 rounded">
                                        <span class="text-xs text-zinc-500">{{ __('Workday') }}</span>
                                        <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $row['worked_days'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-yellow-50 dark:bg-yellow-900/20 p-1.5 rounded">
                                        <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ __('Vacation') }}</span>
                                        <span class="text-sm font-bold text-yellow-700 dark:text-yellow-300">{{ $row['vacation_days'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 p-1.5 rounded">
                                        <span class="text-xs text-red-600 dark:text-red-400">{{ __('Sick') }}</span>
                                        <span class="text-sm font-bold text-red-700 dark:text-red-300">{{ $row['sick_days'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 p-1.5 rounded">
                                        <span class="text-xs text-blue-600 dark:text-blue-400">{{ __('HO') }}</span>
                                        <span class="text-sm font-bold text-blue-700 dark:text-blue-300">{{ $row['home_office_days'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">{{ $row['department'] }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $row['total_workdays'] }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell font-semibold">{{ $row['worked_days'] }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-yellow-600 dark:text-yellow-400">{{ $row['vacation_days'] }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-red-600 dark:text-red-400">{{ $row['sick_days'] }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-blue-600 dark:text-blue-400">{{ $row['home_office_days'] }}</flux:table.cell>
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
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 gap-3 bg-zinc-50 dark:bg-zinc-800 hover:bg-white dark:hover:bg-zinc-700/50 transition">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="shrink-0 w-10 h-10 rounded-lg bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 flex items-center justify-center text-green-600">
                                <flux:icon name="document-text" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-sm truncate text-zinc-900 dark:text-zinc-100">{{ $export->file_name }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $export->created_at->format('Y.m.d H:i') }}
                                    <span class="mx-1">•</span>
                                    {{ $export->size / 1024 > 1024 ? round($export->size / 1024 / 1024, 2) . ' MB' : round($export->size / 1024, 2) . ' KB' }}
                                </div>
                            </div>
                        </div>
                        <flux:button variant="ghost" size="sm" icon="arrow-down-tray" href="{{ $export->getUrl() }}" target="_blank" class="w-full sm:w-auto justify-center">
                            {{ __('Download') }}
                        </flux:button>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
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