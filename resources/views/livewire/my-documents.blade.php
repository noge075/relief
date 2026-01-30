<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('My Documents') }}</flux:heading>
            <flux:subheading>{{ __('Manage your personal documents.') }}</flux:subheading>
        </div>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Upload New Document') }}</flux:heading>
        <div class="space-y-4">
            <div x-data="{ fileName: null }" class="w-full" @file-uploaded.window="fileName = null">
                <flux:label>{{ __('Select File') }}</flux:label>

                <label class="relative flex flex-col items-center justify-center w-full mt-2 cursor-pointer">

                    <div class="flex items-center justify-between w-full p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50 group">

                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="shrink-0 w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-zinc-500 group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">
                                <template x-if="fileName">
                                    <flux:icon name="document-text" class="w-5 h-5" />
                                </template>
                                <template x-if="!fileName">
                                    <flux:icon name="arrow-up-tray" class="w-5 h-5" />
                                </template>
                            </div>

                            <span class="text-sm truncate"
                                  :class="fileName ? 'font-medium text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 italic'"
                                  x-text="fileName ? fileName : '{{ __('Kattints a fájl kiválasztásához...') }}'">
                            </span>
                        </div>

                        <div wire:loading wire:target="upload">
                            <flux:icon.arrow-path class="w-5 h-5 animate-spin text-brand-blue" />
                        </div>

                        <div wire:loading.remove wire:target="upload" class="bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 text-xs font-medium px-2 py-1 rounded">
                            {{ __('Tallózás') }}
                        </div>
                    </div>

                    <input type="file"
                           wire:model="upload"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                           @change="fileName = $event.target.files[0] ? $event.target.files[0].name : null" />
                </label>

                @error('upload')
                <p class="mt-2 text-sm text-red-500 flex items-center gap-1">
                    <flux:icon.exclamation-circle class="w-4 h-4" />
                    {{ $message }}
                </p>
                @enderror
            </div>

            @error('upload')
            <p class="text-red-500 text-sm flex items-center gap-1">
                <flux:icon.exclamation-circle class="w-4 h-4" />
                {{ $message }}
            </p>
            @enderror

            <div class="flex justify-end">
                <flux:button wire:click="save" variant="primary" :disabled="!$upload" class="w-full sm:w-auto">
                    {{ __('Upload Document') }}
                </flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-0! overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Your Documents') }}</flux:heading>
        </div>

        @if($documents->isNotEmpty())
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($documents as $media)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 gap-3 bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/50 transition-colors">

                        <div class="flex items-center gap-3 overflow-hidden w-full">
                            <div class="shrink-0 w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center border border-zinc-200 dark:border-zinc-700 text-zinc-500">
                                <flux:icon name="document-text" class="w-6 h-6" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $media->file_name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $media->human_readable_size ?? '' }} <span class="hidden sm:inline"> • {{ $media->created_at->format('Y.m.d') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                            <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-down-tray"
                                    class="w-full sm:w-auto justify-center"
                                    wire:click="download({{ $media->id }})"
                            >
                                <span class="sm:hidden ml-2">{{ __('Download') }}</span>
                            </flux:button>

                            <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-700 hidden sm:block"></div>

                            <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    class="w-full sm:w-auto justify-center text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    wire:click="delete({{ $media->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this document?') }}"
                            >
                                <span class="sm:hidden ml-2">{{ __('Delete') }}</span>
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 mb-3">
                    <flux:icon name="document" class="w-6 h-6 text-zinc-400" />
                </div>
                <p class="text-zinc-500 font-medium">{{ __('No documents found.') }}</p>
                <p class="text-zinc-400 text-sm mt-1">{{ __('Upload a file to get started.') }}</p>
            </div>
        @endif
    </flux:card>
</div>