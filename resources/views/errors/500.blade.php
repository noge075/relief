<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 flex items-center justify-center p-6">
        <div class="text-center max-w-md space-y-6">
            <div class="flex justify-center">
                <div class="rounded-full bg-red-50 dark:bg-red-900/20 p-4">
                    <flux:icon.exclamation-triangle class="size-12 text-red-500 dark:text-red-400" />
                </div>
            </div>

            <div>
                <flux:heading size="xl" level="1">{{ __('Server Error') }}</flux:heading>
                <flux:subheading class="mt-2">
                    {{ __('Whoops, something went wrong on our servers.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-center gap-4">
                <flux:button href="{{ route('dashboard') }}" variant="primary">{{ __('Go to Dashboard') }}</flux:button>
            </div>
        </div>
        @fluxScripts
        @vite('resources/js/app.js')
    </body>
</html>
