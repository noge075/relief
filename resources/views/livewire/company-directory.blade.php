<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Company Directory') }}</flux:heading>
            <flux:subheading>{{ __('Find and connect with your colleagues.') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name or email...') }}" class="w-full sm:w-64" />

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

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($users as $user)
            <flux:card class="flex flex-col items-center text-center p-4">
                <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}" size="lg" class="mb-3" />
                <flux:heading size="md" class="mb-1">{{ $user->name }}</flux:heading>
                <p class="text-sm text-zinc-500 mb-2">{{ $user->department->name ?? __('No Department') }}</p>
                <div class="flex gap-2">
                    @if($user->email)
                        <flux:button variant="ghost" size="sm" icon="envelope" href="mailto:{{ $user->email }}" target="_blank" />
                    @endif
                    @if($user->phone)
                        <flux:button variant="ghost" size="sm" icon="phone" href="tel:{{ $user->phone }}" target="_blank" />
                    @endif
                </div>
            </flux:card>
        @empty
            <div class="col-span-full text-center text-zinc-500 py-8">
                {{ __('No employees found matching your criteria.') }}
            </div>
        @endforelse
    </div>

    <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-sm text-zinc-500 w-full md:w-1/3">
            @if($users->total() > 0)
                {{ __('Showing') }} <span class="font-medium">{{ $users->firstItem() }}-{{ $users->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $users->total() }}</span> {{ __('results') }}
            @else
                {{ __('No results found.') }}
            @endif
        </div>

        <div class="w-full md:w-1/3 flex justify-end">
            {{ $users->links('pagination.buttons') }}
        </div>
    </div>
</div>
