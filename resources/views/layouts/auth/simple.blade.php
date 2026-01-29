<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 antialiased flex items-center justify-center">
        <div class="w-full max-w-md p-6">
            <div class="flex flex-col items-center gap-6 mb-6">
                <a href="{{ route('home') }}" wire:navigate>
                    <img src="{{ asset('img/logo.svg') }}" alt="Logo" class="h-24 w-auto" />
                </a>
            </div>

            {{ $slot }}
        </div>
        @fluxScripts
    </body>
</html>
