<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <flux:heading size="xl">{{ __('Company Directory') }}</flux:heading>
            <flux:subheading>{{ __('Find and connect with your colleagues.') }}</flux:subheading>
        </div>

        @can(\App\Enums\PermissionType::SEND_BULK_EMAILS->value)
            <flux:button variant="primary" icon="envelope" wire:click="openBulkEmailModal" class="w-full sm:w-auto">
                {{ __('Send Bulk Email') }}
            </flux:button>
        @endcan
    </div>

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
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($users as $user)
            <flux:card class="flex flex-col items-center text-center p-4 h-full">
                <flux:avatar src="{{ $user->profile_photo_url ?? '' }}" name="{{ $user->name }}" size="xl" class="mb-3" />

                <flux:heading size="md" class="mb-1 font-semibold">{{ $user->name }}</flux:heading>

                <div class="flex flex-wrap gap-1 justify-center mb-4 min-h-6">
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

                <div class="w-full pt-3 mt-auto border-t border-zinc-100 dark:border-zinc-800 space-y-1">
                    @if($user->email)
                        <a href="mailto:{{ $user->email }}" class="flex items-center justify-center gap-2 text-sm text-zinc-500 hover:text-indigo-600 transition-colors group p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <flux:icon.envelope variant="micro" class="text-zinc-400 group-hover:text-indigo-500 shrink-0" />
                            <span class="truncate">{{ $user->email }}</span>
                        </a>
                    @endif

                    @if($user->phone)
                        <a href="tel:{{ $user->phone }}" class="flex items-center justify-center gap-2 text-sm text-zinc-500 hover:text-indigo-600 transition-colors group p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <flux:icon.phone variant="micro" class="text-zinc-400 group-hover:text-indigo-500 shrink-0" />
                            <span>{{ $user->phone }}</span>
                        </a>
                    @endif
                </div>
            </flux:card>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center text-zinc-500 py-12 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:icon name="magnifying-glass" class="w-12 h-12 text-zinc-300 mb-2" />
                <p>{{ __('No employees found matching your criteria.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
            @if($users->total() > 0)
                {{ __('Showing') }} <span class="font-medium">{{ $users->firstItem() }}-{{ $users->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $users->total() }}</span> {{ __('results') }}
            @else
                {{ __('No results found.') }}
            @endif
        </div>

        <div class="w-full md:w-1/3 flex justify-center md:justify-end">
            {{ $users->links('pagination.buttons') }}
        </div>
    </div>

    <flux:modal wire:model="showBulkEmailModal" class="w-full sm:w-160 max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Send Bulk Email') }}</flux:heading>
                <flux:subheading>{{ __('Send an email to multiple users.') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <div class="flex justify-end">
                    <flux:button wire:click="toggleSelectAll" variant="ghost" size="sm">
                        @if(count($selectedUserIds) === \App\Models\User::where('is_active', true)->count())
                            {{ __('Deselect All') }}
                        @else
                            {{ __('Select All') }}
                        @endif
                    </flux:button>
                </div>

                <flux:select indicator="checkbox" variant="listbox" multiple
                             wire:model="selectedUserIds" label="{{ __('Recipients') }}" placeholder="{{ __('Select users...') }}">
                    @foreach($allUsers as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="emailSubject" label="{{ __('Subject') }}" />

                <div wire:ignore>
                    <label for="emailBody" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Message') }}</label>
                    <input id="emailBody" type="hidden" name="emailBody" value="{{ $emailBody }}">
                    <trix-editor input="emailBody"
                                 class="block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-200 trix-content"
                                 style="min-height: 200px; max-height: 400px; overflow-y: auto;"
                                 x-data
                                 x-on:trix-change="$wire.set('emailBody', $event.target.value)"
                    ></trix-editor>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-4">
                <flux:button wire:click="$set('showBulkEmailModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="sendBulkEmail" variant="primary" class="w-full sm:w-auto">{{ __('Send Email') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>