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

                <div class="flex flex-wrap gap-1 justify-center mb-4">
                    @forelse($user->departments as $dept)
                        @php
                            $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];
                            $deptColor = $deptColors[$dept->id % count($deptColors)];
                        @endphp
                        <flux:badge inset="top bottom" size="sm" :color="$deptColor">{{ $dept->name }}</flux:badge>
                    @empty
                        <p class="text-xs text-zinc-400 italic">{{ __('No Department') }}</p>
                    @endforelse
                </div>

                <div class="w-full pt-3 mt-auto border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                    @if($user->email)
                        <a href="mailto:{{ $user->email }}" class="flex items-center justify-center gap-2 text-sm text-zinc-500 hover:text-indigo-600 transition-colors group">
                            <flux:icon.envelope variant="micro" class="text-zinc-400 group-hover:text-indigo-500" />
                            <span class="truncate max-w-45">{{ $user->email }}</span>
                        </a>
                    @endif

                    @if($user->phone)
                        <a href="tel:{{ $user->phone }}" class="flex items-center justify-center gap-2 text-sm text-zinc-500 hover:text-indigo-600 transition-colors group">
                            <flux:icon.phone variant="micro" class="text-zinc-400 group-hover:text-indigo-500" />
                            <span>{{ $user->phone }}</span>
                        </a>
                    @endif
                </div>
            </flux:card>
        @empty
            <div class="col-span-full text-center text-zinc-500 py-12 border-2 border-dashed border-zinc-200 rounded-xl">
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
