<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('My Requests') }}</flux:heading>
            <flux:subheading>{{ __('View the status of your leave requests.') }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end flex-wrap">
            <flux:select wire:model.live="yearFilter" placeholder="{{ __('All Years') }}" class="w-full sm:w-32">
                <flux:select.option value="">{{ __('All Years') }}</flux:select.option>
                @foreach(range(Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year + 1) as $y)
                    <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="typeFilter" placeholder="{{ __('All Types') }}" icon="tag" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                <flux:select.option value="vacation">{{ __('Vacation') }}</flux:select.option>
                <flux:select.option value="sick">{{ __('Sick Leave') }}</flux:select.option>
                <flux:select.option value="home_office">{{ __('Home Office') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}" icon="check-circle" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
            </flux:select>

            @if($statusFilter || $typeFilter || $yearFilter)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="w-full sm:w-auto text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="p-0! overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Date') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Days') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Reason') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Manager Comment') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($requests as $request)
                    <flux:table.row :key="$request->id">
                        <flux:table.cell>
                            @php
                                $type = $request->type->value;
                                $color = match($type) { 'vacation' => 'yellow', 'sick' => 'red', 'home_office' => 'blue', default => 'zinc' };
                                $label = match($type) { 'vacation' => __('Vacation'), 'sick' => __('Sick Leave'), 'home_office' => __('Home Office'), default => __('Other') };

                                $status = $request->status->value;
                                $statusColor = match($status) { 'approved' => 'green', 'rejected' => 'red', 'pending' => 'yellow', default => 'zinc' };
                                $statusLabel = match($status) { 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'pending' => __('Pending'), default => ucfirst($status) };
                            @endphp

                            {{-- MOBIL NÉZET: Minden infó egy helyen --}}
                            <div class="flex flex-col gap-1 md:hidden">
                                <div class="font-medium">
                                    <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                                </div>
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $request->start_date->format('Y.m.d') }}
                                    @if($request->start_date != $request->end_date)
                                        - {{ $request->end_date->format('m.d') }}
                                    @endif
                                    <span class="text-zinc-500 text-xs ml-1">({{ $request->days_count }} {{ __('days') }})</span>
                                </div>
                                <div>
                                    <flux:badge :color="$statusColor" size="xs">{{ $statusLabel }}</flux:badge>
                                </div>
                            </div>

                            {{-- DESKTOP NÉZET: Csak a badge --}}
                            <div class="hidden md:block">
                                <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            {{ $request->start_date->format('Y.m.d') }}
                            @if($request->start_date != $request->end_date)
                                - {{ $request->end_date->format('Y.m.d') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $request->days_count }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell truncate max-w-40" title="{{ $request->reason }}">{{ $request->reason }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge :color="$statusColor" size="sm">{{ $statusLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell truncate max-w-40" title="{{ $request->manager_comment }}">
                            @if($request->manager_comment)
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $request->manager_comment }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            {{-- MOBIL GOMB: Nagy "Részletek" gomb --}}
                            <span class="md:hidden">
                                <button
                                        wire:click="openDetails({{ $request->id }})"
                                        class="w-10 h-10 flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 active:scale-95 transition-transform"
                                >
                                    <flux:icon.eye class="size-6" />
                                </button>
                            </span>

                            {{-- DESKTOP MENÜ --}}
                            <div class="hidden md:block">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="openDetails({{ $request->id }})">{{ __('Details') }}</flux:menu.item>

                                        @if($request->status === \App\Enums\LeaveStatus::PENDING)
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteRequest({{ $request->id }})" wire:confirm="{{ __('Are you sure you want to delete this request?') }}">{{ __('Delete') }}</flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3 text-center md:text-left">
                @if($requests->total() > 0)
                    {{ __('Showing') }} <span class="font-medium">{{ $requests->firstItem() }}-{{ $requests->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $requests->total() }}</span> {{ __('results') }}
                @else
                    {{ __('No results found.') }}
                @endif
            </div>

            <div class="w-full md:w-1/3 flex justify-center">
                <div class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                    <div class="bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-500 border-r border-zinc-200 dark:border-zinc-700 whitespace-nowrap">
                        {{ __('Per Page') }}
                    </div>
                    <div class="w-20">
                        <flux:select wire:model.live="perPage" class="border-0! shadow-none! rounded-none! focus:ring-0!">
                            <flux:select.option value="5">5</flux:select.option>
                            <flux:select.option value="10">10</flux:select.option>
                            <flux:select.option value="15">15</flux:select.option>
                            <flux:select.option value="25">25</flux:select.option>
                            <flux:select.option value="50">50</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 flex justify-center md:justify-end">
                {{ $requests->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <flux:modal wire:model="showDetailsModal" class="w-full sm:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Request Details') }}</flux:heading>
                <flux:subheading>
                    @if($selectedRequest)
                        {{ __(ucfirst($selectedRequest->type->value)) }} - {{ $selectedRequest->start_date->format('Y.m.d') }}
                    @endif
                </flux:subheading>
            </div>

            @if($selectedRequest)
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 block text-xs uppercase font-bold">{{ __('Status') }}</span>
                            <span class="font-medium text-lg">{{ __(ucfirst($selectedRequest->status->value)) }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 block text-xs uppercase font-bold">{{ __('Days') }}</span>
                            <span class="font-medium text-lg">{{ $selectedRequest->days_count }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-zinc-500 block text-xs uppercase font-bold">{{ __('Reason') }}</span>
                            <div class="mt-1 p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{ $selectedRequest->reason ?? '-' }}
                            </div>
                        </div>
                        @if($selectedRequest->manager_comment)
                            <div class="sm:col-span-2">
                                <span class="text-zinc-500 block text-xs uppercase font-bold">{{ __('Manager Comment') }}</span>
                                <div class="mt-1 p-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg border border-red-200 dark:border-red-800">
                                    {{ $selectedRequest->manager_comment }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Documents') }}</flux:heading>

                        @if($selectedRequest->getMedia('documents')->isNotEmpty())
                            <div class="space-y-2 mb-4">
                                @foreach($selectedRequest->getMedia('documents') as $media)
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <flux:icon name="paper-clip" class="text-zinc-400 shrink-0" />
                                            <span class="text-sm truncate font-medium">{{ $media->file_name }}</span>
                                        </div>
                                        <div class="flex gap-2 self-end sm:self-auto">
                                            <flux:button variant="ghost" size="xs" icon="arrow-down-tray" href="{{ $media->getUrl() }}" target="_blank">{{ __('Download') }}</flux:button>
                                            @if($selectedRequest->status === \App\Enums\LeaveStatus::PENDING)
                                                <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600" wire:click="deleteDocument({{ $media->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-zinc-500 mb-4 italic">{{ __('No documents attached.') }}</p>
                        @endif

                        @if($selectedRequest->type === \App\Enums\LeaveType::SICK && $selectedRequest->status === \App\Enums\LeaveStatus::PENDING)
                            <div class="space-y-2 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-800/30">
                                <flux:input type="file" wire:model="upload" label="{{ __('Upload Document') }}" />
                                <div class="flex justify-end mt-2">
                                    <flux:button wire:click="saveDocument" variant="primary" size="sm" :disabled="!$upload" class="w-full sm:w-auto">{{ __('Upload') }}</flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex flex-col-reverse sm:flex-row justify-between gap-3 mt-6">
                @if($selectedRequest && $selectedRequest->status === \App\Enums\LeaveStatus::PENDING)
                    <flux:button variant="danger" wire:click="deleteRequest({{ $selectedRequest->id }})" wire:confirm="{{ __('Are you sure you want to delete this request?') }}" class="w-full sm:w-auto">{{ __('Delete Request') }}</flux:button>
                @else
                    <div class="hidden sm:block"></div> {{-- Spacer --}}
                @endif
                <flux:button wire:click="$set('showDetailsModal', false)" class="w-full sm:w-auto">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>