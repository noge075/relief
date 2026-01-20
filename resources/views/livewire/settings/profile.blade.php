<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <flux:separator class="my-6" />

        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Signature') }}</flux:heading>
            <flux:subheading>{{ __('Upload or draw your signature for documents.') }}</flux:subheading>

            @if(auth()->user()->signature_path)
                <div class="border rounded-lg p-4 bg-white dark:bg-zinc-900 flex flex-col items-center gap-4">
                    <img src="{{ Storage::url(auth()->user()->signature_path) }}" alt="Signature" class="max-h-32" />
                    <flux:button variant="danger" wire:click="deleteSignature" wire:confirm="{{ __('Are you sure you want to delete your signature?') }}">{{ __('Delete Signature') }}</flux:button>
                </div>
            @else
                <div x-data="{ tab: 'upload' }" class="space-y-4">
                    <div class="flex gap-4 border-b border-zinc-200 dark:border-zinc-700">
                        <button @click="tab = 'upload'" :class="tab === 'upload' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700'" class="px-4 py-2">{{ __('Upload') }}</button>
                        <button @click="tab = 'draw'" :class="tab === 'draw' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700'" class="px-4 py-2">{{ __('Draw') }}</button>
                    </div>

                    <div x-show="tab === 'upload'" class="space-y-4">
                        <flux:input type="file" wire:model="signature" label="{{ __('Upload Signature Image') }}" accept="image/*" />
                        <flux:button wire:click="saveSignature" variant="primary">{{ __('Save Uploaded Signature') }}</flux:button>
                    </div>

                    <div x-show="tab === 'draw'" class="space-y-4" x-data="{
                        signaturePad: null,
                        init() {
                            this.signaturePad = new SignaturePad(this.$refs.canvas);
                        },
                        clear() {
                            this.signaturePad.clear();
                        },
                        save() {
                            if (this.signaturePad.isEmpty()) {
                                alert('Please provide a signature first.');
                            } else {
                                $wire.set('signatureData', this.signaturePad.toDataURL());
                                $wire.saveSignature();
                            }
                        }
                    }">
                        <div class="border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white">
                            <canvas x-ref="canvas" width="500" height="200" class="w-full h-48 cursor-crosshair"></canvas>
                        </div>
                        <div class="flex gap-2">
                            <flux:button @click="clear" variant="ghost">{{ __('Clear') }}</flux:button>
                            <flux:button @click="save" variant="primary">{{ __('Save Drawn Signature') }}</flux:button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
