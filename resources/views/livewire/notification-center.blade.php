<div class="relative" x-data="{ open: false }" wire:poll.5s="loadNotifications">
    <button
            @click="open = !open"
            class="relative w-9 h-9 flex items-center justify-center bg-zinc-200 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-500"
    >
        <flux:icon name="bell" class="w-5 h-5 text-zinc-500 dark:text-zinc-300" />

        @if($unreadNotifications->count() > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full border-2 border-white dark:border-zinc-800">
                {{ $unreadNotifications->count() }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false" style="display: none;" class="fixed inset-x-4 top-16 rounded-lg sm:absolute sm:w-80 sm:right-0 sm:left-auto sm:top-auto sm:mt-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-lg z-50">
        <div class="p-4 flex justify-between items-center border-b dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
            @if($unreadNotifications->count() > 0)
                <flux:button variant="ghost" size="xs" wire:click="markAllAsRead">{{ __('Mark all as read') }}</flux:button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @if($unreadNotifications->isEmpty() && $readNotifications->isEmpty())
                <p class="text-sm text-zinc-500 p-4 text-center">{{ __('No notifications yet.') }}</p>
            @else
                @foreach($unreadNotifications as $notification)
                    <div class="p-4 border-b dark:border-zinc-700 bg-blue-50 dark:bg-blue-900/20 group">
                        <div class="flex justify-between items-start">
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="block grow" wire:click="markAsRead('{{ $notification->id }}')">
                                <div class="font-bold text-sm text-zinc-800 dark:text-zinc-100">{{ $notification->data['title'] }}</div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-300">{{ $notification->data['message'] }}</p>
                                <div class="text-xs text-zinc-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" wire:click.stop="deleteNotification('{{ $notification->id }}')" />
                        </div>
                    </div>
                @endforeach

                @foreach($readNotifications as $notification)
                    <div class="p-4 border-b dark:border-zinc-700/50 opacity-60 group">
                        <div class="flex justify-between items-start">
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="block grow">
                                <div class="font-bold text-sm text-zinc-700 dark:text-zinc-300">{{ $notification->data['title'] }}</div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->data['message'] }}</p>
                                <div class="text-xs text-zinc-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" wire:click.stop="deleteNotification('{{ $notification->id }}')" />
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
