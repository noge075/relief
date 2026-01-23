<x-layouts::app.sidebar :title="$title ?? null">
    @impersonating
        <div class="bg-red-500 text-white px-4 py-2 flex justify-between items-center">
            <span class="font-medium">{{ __('You are impersonating :name', ['name' => auth()->user()->name]) }}</span>
            <a href="{{ route('impersonate.stop') }}" class="bg-white text-red-500 px-3 py-1 rounded text-sm font-bold hover:bg-zinc-100 transition">
                {{ __('Stop Impersonating') }}
            </a>
        </div>
    @endImpersonating

    <flux:main>
        <div class="flex justify-end p-4">
            @persist('notification-bell')
                <livewire:notification-center />
            @endpersist
        </div>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
