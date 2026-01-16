<x-crud.flux-table
    :items="$items"
    :title="$crudMeta['title']"
    :resource-name="$crudMeta['resourceName']"
    :resource-plural="$crudMeta['resourcePlural']"
    :create-url="$crudMeta['createUrl']"
>
    <x-slot:columns>
        <flux:table.column></flux:table.column>
        <flux:table.column class="max-md:hidden">ID</flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortField === 'name'"
            :direction="$sortDirection"
            wire:click="sortBy('name')"
        >
            Name
        </flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortField === 'email'"
            :direction="$sortDirection"
            wire:click="sortBy('email')"
        >
            Email
        </flux:table.column>

        <flux:table.column></flux:table.column>
        <flux:table.column></flux:table.column>
    </x-slot:columns>

    <x-slot:rows>
        @foreach($items as $employee)
            <flux:table.row :key="$employee->id">
                <flux:table.cell class="pr-2"><flux:checkbox /></flux:table.cell>
                <flux:table.cell class="max-md:hidden">{{ $employee->id }}</flux:table.cell>
                <flux:table.cell>{{ $employee->name }}</flux:table.cell>
                <flux:table.cell>{{ $employee->email }}</flux:table.cell>

                <flux:table.cell class="text-right">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        wire:click="confirmDelete({{ $employee->id }})"
                    />
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown position="bottom" align="end" offset="-15">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                        <flux:menu>
                            <flux:menu.item icon="document-text">View</flux:menu.item>
                            <flux:menu.item icon="archive-box" variant="danger">Archive</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </x-slot:rows>
</x-crud.flux-table>
