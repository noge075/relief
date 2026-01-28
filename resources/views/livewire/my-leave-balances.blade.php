<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Vacation Balance -->
    <flux:card class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="p-2 bg-yellow-100 rounded-lg dark:bg-yellow-900/30">
                    <flux:icon name="sun" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Vacation') }}</flux:heading>
                    <flux:subheading>{{ \Carbon\Carbon::parse($this->startDate)->year }}</flux:subheading>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ $vacationBalance ? ($vacationBalance->allowance - $vacationBalance->used + 0) : 0 }}
                </div>
                <div class="text-xs text-zinc-500 uppercase font-medium tracking-wide">{{ __('Remaining') }}</div>
                @if($vacationPending > 0)
                    <div class="text-xs text-yellow-600 dark:text-yellow-500 mt-1" title="{{ __('Pending requests will reduce this') }}">
                        (-{{ $vacationPending + 0 }} {{ __('Pending') }})
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
            <div>
                <div class="text-sm text-zinc-500">{{ __('Annual Allowance') }}</div>
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $vacationBalance ? ($vacationBalance->allowance + 0) : 0 }}
                </div>
            </div>
            <div>
                <div class="text-sm text-zinc-500">{{ __('Taken') }}</div>
                <div class="flex items-baseline gap-2">
                    <div class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $vacationApproved + 0 }}
                    </div>
                    @if($vacationPending > 0)
                        <div class="text-xs text-yellow-600 dark:text-yellow-500 font-medium">
                            + {{ $vacationPending + 0 }} {{ __('Pending') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Sick Days -->
    <flux:card class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="p-2 bg-red-100 rounded-lg dark:bg-red-900/30">
                    <flux:icon name="plus" class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Sick Leave') }}</flux:heading>
                    <flux:subheading>{{ \Carbon\Carbon::parse($this->startDate)->year }}</flux:subheading>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                    {{ $sickApproved + 0 }}
                </div>
                <div class="text-xs text-zinc-500 uppercase font-medium tracking-wide">{{ __('Days Taken') }}</div>
                @if($sickPending > 0)
                    <div class="text-xs text-red-500 dark:text-red-400 mt-1 font-medium">
                        + {{ $sickPending + 0 }} {{ __('Pending') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-700">
            <div class="text-sm text-zinc-500">
                {{ __('Sick leave does not affect your annual vacation allowance.') }}
            </div>
        </div>
    </flux:card>
</div>
