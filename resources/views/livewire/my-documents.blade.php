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
            <flux:input type="file" wire:model="upload" label="{{ __('Select File') }}" />
            @error('upload') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            <div class="flex justify-end">
                <flux:button wire:click="save" variant="primary" :disabled="!$upload">{{ __('Upload Document') }}</flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Your Documents') }}</flux:heading>

        @if($documents->isNotEmpty())
            <div class="space-y-2">
                @foreach($documents as $media)
                    <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <flux:icon name="paper-clip" class="text-zinc-400 shrink-0" />
                            <span class="text-sm truncate">{{ $media->file_name }}</span>
                        </div>
                        <div class="flex gap-2">
                            <flux:button variant="ghost" size="xs" icon="arrow-down-tray" wire:click="download({{ $media->id }})" />
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600" wire:click="delete({{ $media->id }})" wire:confirm="{{ __('Are you sure you want to delete this document?') }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-zinc-500">{{ __('No documents found.') }}</p>
        @endif
    </flux:card>
</div>
