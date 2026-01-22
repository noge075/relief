<div class="flex flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('My Requests') }}</flux:heading>
            <flux:subheading>{{ __('View the status of your leave requests.') }}</flux:subheading>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row gap-4 justify-between items-end bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-end">
            <flux:select wire:model.live="yearFilter" placeholder="{{ __('All Years') }}" class="w-32">
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
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" class="text-red-500 hover:text-red-600">{{ __('Clear') }}</flux:button>
            @endif
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Days') }}</flux:table.column>
                <flux:table.column>{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Manager Comment') }}</flux:table.column>
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
                            @endphp
                            <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $request->start_date->format('Y.m.d') }}
                            @if($request->start_date != $request->end_date)
                                - {{ $request->end_date->format('Y.m.d') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $request->days_count }}</flux:table.cell>
                        <flux:table.cell class="truncate max-w-[200px]" title="{{ $request->reason }}">{{ $request->reason }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $status = $request->status->value;
                                $statusColor = match($status) { 'approved' => 'green', 'rejected' => 'red', 'pending' => 'yellow', default => 'zinc' };
                                $statusLabel = match($status) { 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'pending' => __('Pending'), default => ucfirst($status) };
                            @endphp
                            <flux:badge :color="$statusColor" size="sm">{{ $statusLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="truncate max-w-[200px]" title="{{ $request->manager_comment }}">
                            @if($request->manager_comment)
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $request->manager_comment }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="openDetails({{ $request->id }})">{{ __('Details') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-zinc-500 w-full md:w-1/3">
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
                        <flux:select wire:model.live="perPage" class="!border-0 !shadow-none !rounded-none focus:!ring-0">
                            <flux:select.option value="5">5</flux:select.option>
                            <flux:select.option value="10">10</flux:select.option>
                            <flux:select.option value="15">15</flux:select.option>
                            <flux:select.option value="25">25</flux:select.option>
                            <flux:select.option value="50">50</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 flex justify-end">
                {{ $requests->links('pagination.buttons') }}
            </div>
        </div>
    </flux:card>

    <!-- Details Modal -->
    <flux:modal wire:model="showDetailsModal" class="min-w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Request Details') }}</flux:heading>
                <flux:subheading>
                    @if($selectedRequest)
                        {{ ucfirst($selectedRequest->type->value) }} - {{ $selectedRequest->start_date->format('Y.m.d') }}
                    @endif
                </flux:subheading>
            </div>

            @if($selectedRequest)
                <div class="space-y-4">
                    <!-- Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 block">{{ __('Status') }}</span>
                            <span class="font-medium">{{ __(ucfirst($selectedRequest->status->value)) }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 block">{{ __('Days') }}</span>
                            <span class="font-medium">{{ $selectedRequest->days_count }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-zinc-500 block">{{ __('Reason') }}</span>
                            <span class="font-medium">{{ $selectedRequest->reason ?? '-' }}</span>
                        </div>
                        @if($selectedRequest->manager_comment)
                            <div class="col-span-2">
                                <span class="text-zinc-500 block">{{ __('Manager Comment') }}</span>
                                <span class="font-medium text-red-600">{{ $selectedRequest->manager_comment }}</span>
                            </div>
                        @endif
                    </div>

                    <flux:separator />

                    <!-- Documents -->
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Documents') }}</flux:heading>

                        @if($selectedRequest->getMedia('documents')->isNotEmpty())
                            <div class="space-y-2 mb-4">
                                @foreach($selectedRequest->getMedia('documents') as $media)
                                    <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <flux:icon name="paper-clip" class="text-zinc-400 flex-shrink-0" />
                                            <span class="text-sm truncate">{{ $media->file_name }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <flux:button variant="ghost" size="xs" icon="arrow-down-tray" href="{{ $media->getUrl() }}" target="_blank" />
                                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500 hover:text-red-600" wire:click="deleteDocument({{ $media->id }})" wire:confirm="{{ __('Are you sure?') }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-zinc-500 mb-4">{{ __('No documents attached.') }}</p>
                        @endif

                        <!-- Upload (csak ha Sick Leave, vagy mindig?) -->
                        <!-- A felhasználó kérése: "Ezt utolóag is lehetne... amikor leadja a betegszabit még nem tud feltölteni" -->
                        <!-- Tehát mindig engedjük, vagy csak ha Sick Leave. -->
                        @if($selectedRequest->type === \App\Enums\LeaveType::SICK)
                            <div class="space-y-2">
                                <flux:input type="file" wire:model="upload" label="{{ __('Upload Document') }}" />
                                <div class="flex justify-end">
                                    <flux:button wire:click="saveDocument" variant="primary" size="sm" :disabled="!$upload">{{ __('Upload') }}</flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="$set('showDetailsModal', false)">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
