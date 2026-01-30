<flux:modal wire:model="showModal" class="w-full sm:w-11/12 max-w-4xl max-h-[90vh] overflow-y-auto">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $isEditing ? __('Edit Role') : __('New Role') }}</flux:heading>
            <flux:subheading>{{ __('Set the role name and permissions.') }}</flux:subheading>
        </div>

        <flux:input wire:model="name" label="{{ __('Role Name') }}" placeholder="{{ __('e.g. HR Manager') }}" />

        <div>
            <div class="flex justify-between items-center mb-2">
                <flux:label>{{ __('Permissions') }}</flux:label>
            </div>

            <div class="mt-2 space-y-6 border rounded-lg p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
                @foreach($permissions as $group => $perms)
                    <div>
                        <flux:heading size="sm" class="mb-3 text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs font-bold sticky top-0 bg-zinc-50 dark:bg-zinc-900 py-1 z-10">
                            {{ __($group) }}
                        </flux:heading>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($perms as $permission)
                                <flux:checkbox
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->name }}"
                                        label="{{ __($permission->name) }}"
                                />
                            @endforeach
                        </div>
                    </div>
                    @if(!$loop->last) <hr class="border-zinc-200 dark:border-zinc-700 my-6"/> @endif
                @endforeach
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
            @if($isEditing && $editingId)
                <flux:button
                        wire:click="delete({{ $editingId }})"
                        variant="danger"
                        wire:confirm="{{ __('Are you sure?') }}"
                        class="w-full sm:w-auto md:hidden"
                >
                    {{ __('Delete') }}
                </flux:button>
            @endif

            <div class="flex flex-col-reverse sm:flex-row gap-2 w-full sm:w-auto">
                <flux:button wire:click="$set('showModal', false)" variant="ghost" class="w-full sm:w-auto">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary" class="w-full sm:w-auto">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </div>
</flux:modal>