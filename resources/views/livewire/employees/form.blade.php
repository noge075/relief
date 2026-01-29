<flux:modal wire:model="showModal" class="min-w-200">
    <div class="space-y-6" x-data="{ tab: 'basic' }">
        <div>
            <flux:heading size="lg">{{ $isEditing ? __('Edit Employee') : __('New Employee') }}</flux:heading>
            <flux:subheading>{{ __('Fill in the details to save.') }}</flux:subheading>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button @click="tab = 'basic'" :class="tab === 'basic' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Basic Info') }}
            </button>
            <button @click="tab = 'personal'" :class="tab === 'personal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Personal Details') }}
            </button>
            <button @click="tab = 'permissions'" :class="tab === 'permissions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Permissions') }}
            </button>
            @if($isEditing)
                <button @click="tab = 'documents'" :class="tab === 'documents' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                    {{ __('Documents') }}
                </button>
            @endif
        </div>

        <!-- Tab Content Wrapper -->
        <div class="min-h-112.5">
            <!-- Basic Info Tab -->
            <div x-show="tab === 'basic'" class="space-y-4">
                <!-- Fake inputs -->
                <input type="text" style="display:none">
                <input type="password" style="display:none">

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="last_name" label="{{ __('Last Name') }}" autocomplete="off" />
                    <flux:input wire:model="first_name" label="{{ __('First Name') }}" autocomplete="off" />
                </div>

                <flux:input wire:model="email" label="{{ __('Email Address') }}" type="email" autocomplete="off" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select indicator="checkbox" variant="listbox" multiple
                            wire:model="selectedDepartmentIds" label="{{ __('Department') }}" placeholder="{{ __('Select...') }}">
                        @foreach($departments as $dept)
                            <flux:select.option value="{{ $dept->id }}">
                                {{ $dept->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="work_schedule_id" label="{{ __('Work Schedule') }}">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($schedules as $sched)
                            <flux:select.option value="{{ $sched->id }}">{{ $sched->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="employment_type" label="{{ __('Employment Type') }}">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($employmentTypes as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="home_office_policy_id" label="{{ __('Home Office Policy') }}">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($homeOfficePolicies as $policy)
                            <flux:select.option value="{{ $policy->id }}">
                                {{ $policy->type->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model.live="role" label="{{ __('Role') }}">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($roles as $r)
                        <flux:select.option value="{{ $r->name }}">
                            {{ \App\Enums\RoleType::tryFrom($r->name)?->label() ?? ucfirst($r->name) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="password" type="password" label="{{ __('Password') }}"
                            description="{{ $isEditing ? __('Leave empty if you don\'t want to change it.') : __('Initial password for login.') }}"
                            autocomplete="new-password"
                />
            </div>

            <!-- Personal Details Tab -->
            <div x-show="tab === 'personal'" class="space-y-4" style="display: none;">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="id_card_number" label="{{ __('ID Card Number') }}" />
                    <flux:input wire:model="tax_id" label="{{ __('Tax ID') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="ssn" label="{{ __('SSN (TAJ)') }}" />
                    <flux:input wire:model="phone" label="{{ __('Phone Number') }}" />
                </div>

                <flux:input wire:model="address" label="{{ __('Address') }}" />
            </div>

            <!-- Permissions Tab -->
            <div x-show="tab === 'permissions'" class="space-y-4" style="display: none;">
                <div class="flex justify-between items-center mb-2">
                    <flux:label>{{ __('Additional Permissions') }}</flux:label>
                    <flux:button variant="ghost" size="xs" wire:click="toggleAllPermissions">
                        {{ count($selectedPermissions) > 0 ? __('Deselect All') : __('Select All') }}
                    </flux:button>
                </div>
                <div class="mt-2 space-y-4 max-h-100 overflow-y-auto border rounded-lg p-4 bg-zinc-50 dark:bg-zinc-900">
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

            <!-- Documents Tab -->
            @if($isEditing)
                <div x-show="tab === 'documents'" class="space-y-4" style="display: none;">
                    @can(\App\Enums\PermissionType::MANAGE_USER_DOCUMENTS->value)
                        <div class="space-y-2">
                            <flux:input type="file" wire:model="documentUpload" label="{{ __('Upload New Document') }}" />
                            <div class="flex justify-end">
                                <flux:button wire:click="uploadDocument" variant="primary" size="sm" :disabled="!$documentUpload">{{ __('Upload') }}</flux:button>
                            </div>
                        </div>
                        <flux:separator />
                    @endcan

                    <flux:heading size="sm">{{ __('Uploaded Documents') }}</flux:heading>
                    @if($userDocuments && $userDocuments->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($userDocuments as $media)
                                <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <flux:icon name="paper-clip" class="text-zinc-400 shrink-0" />
                                        <span class="text-sm truncate">{{ $media->file_name }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="xs" icon="arrow-down-tray" wire:click="downloadDocument({{ $media->id }})" />
                                        @can(\App\Enums\PermissionType::MANAGE_USER_DOCUMENTS->value)
                                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600" wire:click="deleteDocument({{ $media->id }})" wire:confirm="{{ __('Are you sure?') }}" />
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">{{ __('No documents found.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
