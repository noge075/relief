<flux:modal wire:model="showModal" class="min-w-[500px]">
    <div class="space-y-6">
        <div>
            <flux:heading
                    size="lg">{{ $isEditing ? __('Edit Employee') : __('New Employee') }}</flux:heading>
            <flux:subheading>{{ __('Fill in the details to save.') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
            <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="{{ __('Full Name') }}"/>

            <flux:input wire:model="email" label="{{ __('Email Address') }}" type="email"/>

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

            <flux:select wire:model="role" label="{{ __('Role') }}" placeholder="{{ __('Select...') }}">
                @foreach($roles as $r)
                    <flux:select.option value="{{ $r->name }}">{{ ucfirst($r->name) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="password" type="password" label="{{ __('Password') }}"
                        description="{{ $isEditing ? __('Leave empty if you don\'t want to change it.') : __('Initial password for login.') }}"
            />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showModal', false)">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
        </div>
    </div>
</flux:modal>
