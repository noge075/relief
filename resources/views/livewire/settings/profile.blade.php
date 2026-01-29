<x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information.')">
    <div x-data="{ tab: 'personal' }">
        <!-- Tabs Navigation -->
        <div class="flex border-b border-zinc-200 dark:border-zinc-700 mb-6">
            <button @click="tab = 'personal'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'personal', 'border-transparent text-zinc-500 hover:text-zinc-700': tab !== 'personal' }"
                 class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Personal Details') }}
            </button>
            <button @click="tab = 'avatar'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'avatar', 'border-transparent text-zinc-500 hover:text-zinc-700': tab !== 'avatar' }" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Profile Picture') }}
            </button>
            <button @click="tab = 'signature'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'signature', 'border-transparent text-zinc-500 hover:text-zinc-700': tab !== 'signature' }" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                {{ __('Signature') }}
            </button>
        </div>

        <!-- Personal Details Tab -->
        <div x-show="tab === 'personal'" class="space-y-6">
            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="last_name" label="{{ __('Last Name') }}" />
                    <flux:input wire:model="first_name" label="{{ __('First Name') }}" />
                </div>

                <flux:input wire:model="email" label="{{ __('Email Address') }}" type="email" />

                <flux:separator />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="id_card_number" label="{{ __('ID Card Number') }}" />
                    <flux:input wire:model="tax_id" label="{{ __('Tax ID') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="ssn" label="{{ __('SSN (TAJ)') }}" />
                    <flux:input wire:model="phone" label="{{ __('Phone Number') }}" />
                </div>

                <flux:input wire:model="address" label="{{ __('Address') }}" />

                <div class="flex items-center space-x-2">
                    <flux:field variant="inline">
                        <flux:checkbox wire:model.live="accepted_terms" />

                        <flux:label>
                            {{ __('I accept the') }}&nbsp;
                            <a href="#" wire:click.prevent="$set('showPrivacyModal', true)" class="text-accent hover:text-primary font-bold">
                                {{ __('privacy policy') }}
                            </a>
                        </flux:label>

                        <flux:error name="accepted_terms" />
                    </flux:field>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" :disabled="!$accepted_terms">{{ __('Save Changes') }}</flux:button>
                </div>
            </form>
        </div>

        <!-- Avatar Tab -->
        <div x-show="tab === 'avatar'" class="space-y-6" style="display: none;">
            <div class="flex items-center space-x-4">
                @if (auth()->user()->getFirstMedia('avatar'))
                    <img src="{{ auth()->user()->getFirstMediaUrl('avatar') }}" alt="Avatar" class="h-20 w-20 rounded-full">
                @else
                    <div class="h-20 w-20 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                        <span class="text-2xl font-bold text-zinc-500">{{ auth()->user()->initials() }}</span>
                    </div>
                @endif
                <div>
                    <flux:input type="file" wire:model="avatar" label="{{ __('Upload New Picture') }}" accept="image/*" />
                    <div class="mt-2 flex space-x-2">
                        <flux:button wire:click="saveAvatar" variant="primary" :disabled="!$avatar">{{ __('Save Picture') }}</flux:button>
                        @if (auth()->user()->getFirstMedia('avatar'))
                            <flux:button wire:click="deleteAvatar" variant="danger" wire:confirm="{{ __('Are you sure you want to delete your profile picture?') }}">{{ __('Delete Picture') }}</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Tab -->
        <div x-show="tab === 'signature'" class="space-y-6" style="display: none;">
            @if(!$currentSignature)
                <div x-data="{
                    sigTab: 'upload',
                    signaturePad: null,
                    init() {
                        this.$watch('sigTab', (value) => {
                            if (value === 'draw') {
                                this.$nextTick(() => {
                                    this.initPad();
                                });
                            }
                        });
                    },
                    initPad() {
                        const canvas = document.getElementById('signature-pad');
                        if (!canvas) return;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext('2d').scale(ratio, ratio);

                        if (!this.signaturePad) {
                            this.signaturePad = new SignaturePad(canvas);
                        } else {
                            this.signaturePad.clear();
                        }
                    },
                    clear() {
                        this.signaturePad?.clear();
                    },
                    save() {
                        if (this.signaturePad?.isEmpty()) {
                            alert('Please provide a signature first.');
                        } else {
                            const data = this.signaturePad.toDataURL();
                            @this.set('signatureData', data);
                            @this.call('saveSignatureDraw');
                        }
                    }
                }">
                    <div class="flex border-b border-zinc-200 dark:border-zinc-700 mb-4">
                        <button @click="sigTab = 'upload'" :class="sigTab === 'upload' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                            {{ __('Upload') }}
                        </button>
                        <button @click="sigTab = 'draw'" :class="sigTab === 'draw' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">
                            {{ __('Draw') }}
                        </button>
                    </div>

                    <div x-show="sigTab === 'upload'" class="space-y-4">
                        <flux:input type="file" wire:model="signature" label="{{ __('Upload Signature Image') }}" accept="image/*" />
                        <div class="flex justify-end">
                            <flux:button wire:click="saveSignatureUpload" variant="primary" :disabled="!$signature">{{ __('Save Uploaded Signature') }}</flux:button>
                        </div>
                    </div>

                    <div x-show="sigTab === 'draw'" class="space-y-4" style="display: none;">
                        <div class="border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white" wire:ignore>
                            <canvas id="signature-pad" class="w-full h-40 rounded-lg"></canvas>
                        </div>
                        <div class="flex justify-between">
                            <flux:button @click="clear" variant="ghost">{{ __('Clear') }}</flux:button>
                            <flux:button @click="save" variant="primary">{{ __('Save Drawn Signature') }}</flux:button>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-4 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900 flex justify-between items-center">
                    <div>
                        <div class="text-sm font-medium mb-2">{{ __('Current Signature') }}</div>
                        <img src="{{ Storage::url($currentSignature) }}" alt="Signature" class="h-16 object-contain bg-white rounded border border-zinc-200 p-1">
                    </div>
                    <flux:button wire:click="deleteSignature" variant="danger" icon="trash" wire:confirm="{{ __('Are you sure you want to delete your signature?') }}">{{ __('Delete Signature') }}</flux:button>
                </div>
            @endif
        </div>

        <!-- Privacy Policy Modal -->
        <flux:modal wire:model="showPrivacyModal" class="min-w-300">
            <div class="space-y-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Privacy Policy') }}</h2>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 space-y-4">
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. ...</p>
                </div>
                <div class="mt-6 flex justify-end">
                    <flux:button wire:click="$set('showPrivacyModal', false)">{{ __('Close') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</x-settings.layout>
