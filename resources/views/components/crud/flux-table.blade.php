@props([
    'items',
    'title' => null,
    'resourceName' => null,
    'resourcePlural' => null,
    'createUrl' => null,
    'createLabel' => null,
    'searchPlaceholder' => null,

    'emptyText' => null,

    // delete modal (generikus)
    'deleteTitle' => null,           // pl. "Delete employee?"
    'deleteText' => null,            // pl. "This action cannot be undone."
    'deleteCancelLabel' => null,     // "Cancel"
    'deleteConfirmLabel' => null,    // "Delete"

    'perPageOptions' => [5, 10, 25, 50],
])

@php
    $title = $title
        ?? ($resourcePlural ? str(\Illuminate\Support\Str::headline($resourcePlural)) : 'Items');

    $createLabel = $createLabel
        ?? ($resourceName ? __('Add new :name', ['name' => str($resourceName)->lower()]) : __('Add new'));

    $searchPlaceholder = $searchPlaceholder
        ?? ($resourcePlural ? __('Search :name...', ['name' => str($resourcePlural)->lower()]) : __('Search...'));

    $emptyText = $emptyText
        ?? ($resourcePlural ? __('No :name found.', ['name' => str($resourcePlural)->lower()]) : __('No results.'));

    $deleteTitle = $deleteTitle
        ?? ($resourceName ? __('Delete :name?', ['name' => str($resourceName)->lower()]) : __('Delete item?'));

    $deleteText = $deleteText ?? __('This action cannot be undone.');
    $deleteCancelLabel = $deleteCancelLabel ?? __('Cancel');
    $deleteConfirmLabel = $deleteConfirmLabel ?? __('Delete');
@endphp

<div>
    {{-- Header --}}
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

            @if($createUrl)
                <flux:button href="{{ $createUrl }}" variant="primary">
                    {{ $createLabel }}
                </flux:button>
            @endif
        </div>

        <flux:separator variant="subtle" class="mt-4" />
    </div>

    {{-- Filters: search + perPage --}}
    <div class="mb-6">
        <div class="flex justify-between flex-col gap-4 md:flex-row md:items-center">
            <div class="w-full md:max-w-md">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ $searchPlaceholder }}"
                />
            </div>

            <div class="w-full md:w-48">
                <flux:select wire:model.live="perPage">
                    @foreach($perPageOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }} per page</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- extra filters slot --}}
        @isset($filters)
            <div class="mt-4">
                {{ $filters }}
            </div>
        @endisset
    </div>

    {{-- Flash --}}
    @if (session('message'))
        <div class="mb-4">
            <flux:callout variant="success">
                {{ session('message') }}
            </flux:callout>
        </div>
    @endif

    {{-- Table --}}
    <flux:table :paginate="$items">
        <flux:table.columns>
            {{ $columns }}
        </flux:table.columns>

        <flux:table.rows>
            @if($items && $items->count())
                {{ $rows }}
            @else
                <flux:table.row>
                    <flux:table.cell colspan="100" class="text-center">
                        {{ $emptyText }}
                    </flux:table.cell>
                </flux:table.row>
            @endif
        </flux:table.rows>
    </flux:table>

    {{-- Delete Modal: beépítve --}}
    <flux:modal wire:model.live="showDeleteModal">
        @isset($deleteModal)
            {{-- ha valahol egyedi kell, slot felülírja --}}
            {{ $deleteModal }}
        @else
            <flux:heading>{{ $deleteTitle }}</flux:heading>
            <flux:text>{{ $deleteText }}</flux:text>

            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ $deleteCancelLabel }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ $deleteConfirmLabel }}
                </flux:button>
            </div>
        @endisset
    </flux:modal>
</div>
