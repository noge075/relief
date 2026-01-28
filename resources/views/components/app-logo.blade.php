@props([
'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('img/logo.jpg') }}" alt="Logo" class="h-12 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('img/logo.jpg') }}" alt="Logo" class="h-12 w-auto" />
        </x-slot>
    </flux:brand>
@endif
