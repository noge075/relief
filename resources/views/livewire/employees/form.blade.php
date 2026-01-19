<flux:modal wire:model="showModal" class="min-w-[800px]">
    <div class="space-y-6">
        <div>
            <flux:heading
                    size="lg">{{ $isEditing ? __('Edit Employee') : __('New Employee') }}</flux:heading>
            <flux:subheading>{{ __('Fill in the details to save.') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
            <!-- Fake inputs to trick browser autofill -->
            <input type="text" style="display:none">
            <input type="password" style="display:none">

            <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="{{ __('Full Name') }}" autocomplete="off" />

            <flux:input wire:model="email" label="{{ __('Email Address') }}" type="email" autocomplete="off" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="department_id" label="{{ __('Department') }}" placeholder="{{ __('Select...') }}">
                    @foreach($departments as $dept)
                        <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="work_schedule_id" label="{{ __('Work Schedule') }}" placeholder="{{ __('Select...') }}">
                    @foreach($schedules as $sched)
                        <flux:select.option value="{{ $sched->id }}">{{ $sched->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="employment_type" label="{{ __('Employment Type') }}" placeholder="{{ __('Select...') }}">
                    @foreach($employmentTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="role" label="{{ __('Role') }}" placeholder="{{ __('Select...') }}">
                    @foreach($roles as $r)
                        <flux:select.option value="{{ $r->name }}">
                            {{ \App\Enums\RoleType::tryFrom($r->name)?->label() ?? ucfirst($r->name) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="password" type="password" label="{{ __('Password') }}"
                        description="{{ $isEditing ? __('Leave empty if you don\'t want to change it.') : __('Initial password for login.') }}"
                        autocomplete="new-password"
            />

            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
                <flux:label>{{ __('Additional Permissions') }}</flux:label>
                <div class="mt-2 space-y-4 max-h-[40vh] overflow-y-auto border rounded-lg p-4 bg-zinc-50 dark:bg-zinc-900">
                    @foreach($allPermissions as $group => $perms)
                        <div>
                            <flux:heading size="sm" class="mb-2 text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs font-bold">{{ __($group) }}</flux:heading>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($perms as $permission)
                                    @php
                                        $isRolePermission = in_array($permission->name, $rolePermissions);
                                    @endphp

                                    @if($isRolePermission)
                                        <flux:checkbox
                                            checked
                                            disabled
                                            label="{{ __($permission->name) }}"
                                        />
                                    @else
                                        <flux:checkbox
                                            wire:model="selectedPermissions"
                                            value="{{ $permission->name }}"
                                            label="{{ __($permission->name) }}"
                                        />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @if(!$loop->last) <hr class="border-zinc-200 dark:border-zinc-700 my-4"/> @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
