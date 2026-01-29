@props([
'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('img/logo.svg') }}" alt="Logo" class="h-10 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('img/logo.svg') }}" alt="Logo" class="h-14 w-auto" />
        </x-slot>
    </flux:brand>
@endif
