<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="w-72 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" wire:navigate class="data-current:font-bold">
                    <x-app-logo-icon />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:separator class="my-2" />

            <flux:sidebar.nav>
                <!-- General -->
                <flux:sidebar.group :heading="__('General')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="clock" :href="route('attendance.index')" :current="request()->routeIs('attendance.index')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="list-bullet" :href="route('my-requests.index')" :current="request()->routeIs('my-requests.index')" wire:navigate>
                        {{ __('My Requests') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="document-text" :href="route('my-documents.index')" :current="request()->routeIs('my-documents.index')" wire:navigate>
                        {{ __('My Documents') }}
                    </flux:sidebar.item>

                    <x-flux::sidebar.item icon="users" href="{{ route('company-directory.index') }}">
                        {{ __('Company Directory') }}
                    </x-flux::sidebar.item>

                    <flux:sidebar.item icon="user-group" :href="route('organization.index')" :current="request()->routeIs('organization.index')" wire:navigate>
                        {{ __('Organization Chart') }}
                    </flux:sidebar.item>

                    @can('view status board')
                        <flux:sidebar.item icon="table-cells" :href="route('status-board')" :current="request()->routeIs('status-board')" wire:navigate>
                            {{ __('Status Board') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                <flux:separator class="my-2" />

                <!-- Management -->
                @if(auth()->user()->can('view users') || auth()->user()->can('approve leave requests') || auth()->user()->can('adjust leave balances') || auth()->user()->can('view payroll data') || auth()->user()->can('manage work schedules') || auth()->user()->can('manage departments'))
                    <flux:sidebar.group :heading="__('Management')" class="grid">
                        @can('view users')
                            <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.index')" wire:navigate>
                                {{ __('Employees') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('manage departments')
                            <flux:sidebar.item icon="building-office" :href="route('departments.index')" :current="request()->routeIs('departments.index')" wire:navigate>
                                {{ __('Departments') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('approve leave requests')
                            <flux:sidebar.item icon="check-badge" :href="route('approvals.index')" :current="request()->routeIs('approvals.index')" wire:navigate>
                                {{ __('Approvals') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('adjust leave balances')
                            <flux:sidebar.item icon="scale" :href="route('employees.balances')" :current="request()->routeIs('employees.balances')" wire:navigate>
                                {{ __('Leave Balances') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('manage work schedules')
                            <flux:sidebar.item icon="clock" :href="route('work-schedules.index')" :current="request()->routeIs('work-schedules.index')" wire:navigate>
                                {{ __('Work Schedules') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('view payroll data')
                            <flux:sidebar.item icon="document-chart-bar" :href="route('payroll.report')" :current="request()->routeIs('payroll.report')" wire:navigate>
                                {{ __('Monthly Report') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif

                <flux:separator class="my-2" />

                <!-- Settings -->
                @can('manage settings')
                    <flux:sidebar.group :heading="__('Settings')" class="grid">
                        <flux:sidebar.item icon="lock-closed" :href="route('settings.roles')" :current="request()->routeIs('settings.roles')" wire:navigate>
                            {{ __('Roles & Permissions') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('settings.special-days')" :current="request()->routeIs('settings.special-days')" wire:navigate>
                            {{ __('Special Days') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="home-modern" :href="route('settings.home-office-policies')" :current="request()->routeIs('settings.home-office-policies')" wire:navigate>
                            {{ __('Home Office Policies') }}
                        </flux:sidebar.item>
                        @can('view audit logs')
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('settings.audit-logs')" :current="request()->routeIs('settings.audit-logs')" wire:navigate>
                                {{ __('Audit Logs') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>
        </flux:sidebar>

        <flux:header class="hidden lg:flex items-center bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 px-4">
            <flux:navbar class="w-full justify-end">
                <div class="flex items-center gap-x-4">
                    @persist('notification-bell-desktop')
                        <div class="relative">
                            <livewire:notification-center menu-class="origin-top-right right-0 w-96 max-w-[90vw]" />
                        </div>
                    @endpersist
                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                        <flux:radio value="light" icon="sun"></flux:radio>
                        <flux:radio value="dark" icon="moon"></flux:radio>
                    </flux:radio.group>
                    @livewire('user-avatar')
                </div>
            </flux:navbar>
        </flux:header>

        <!-- Mobile Header -->
        <flux:header class="flex items-center lg:hidden px-4">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <div class="flex items-center gap-x-4">
                @persist('notification-bell-mobile')
                    <div class="relative">
                        <livewire:notification-center menu-class="origin-top-right absolute right-0 mt-2 w-96 max-w-[90vw] rounded-md shadow-lg bg-white dark:bg-zinc-800 ring-1 ring-black ring-opacity-5 focus:outline-none" />
                    </div>
                @endpersist
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun"></flux:radio>
                    <flux:radio value="dark" icon="moon"></flux:radio>
                </flux:radio.group>
                @livewire('user-avatar')
            </div>
        </flux:header>

        {{ $slot }}

        <flux:toast position="bottom right" />

        @fluxScripts
        @vite('resources/js/app.js')
        @stack('scripts')
    </body>
</html>
