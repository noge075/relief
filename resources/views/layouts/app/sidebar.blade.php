<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="w-72 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

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

                    @can('view status board')
                        <flux:sidebar.item icon="table-cells" :href="route('status-board')" :current="request()->routeIs('status-board')" wire:navigate>
                            {{ __('Status Board') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                <!-- Management -->
                @if(auth()->user()->can('view users') || auth()->user()->can('approve leave requests') || auth()->user()->can('adjust leave balances') || auth()->user()->can('view payroll data') || auth()->user()->can('manage work schedules') || auth()->user()->can('manage departments'))
                    <flux:sidebar.group :heading="__('Management')" class="grid">
                        @can('view users')
                            <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.index')" wire:navigate>
                                {{ __('Employees') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="user-group" :href="route('organization.index')" :current="request()->routeIs('organization.index')" wire:navigate>
                                {{ __('Organization Chart') }}
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

                <!-- Settings -->
                @can('manage settings')
                    <flux:sidebar.group :heading="__('Settings')" class="grid">
                        <flux:sidebar.item icon="lock-closed" :href="route('settings.roles')" :current="request()->routeIs('settings.roles')" wire:navigate>
                            {{ __('Roles & Permissions') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('settings.special-days')" :current="request()->routeIs('settings.special-days')" wire:navigate>
                            {{ __('Special Days') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('settings.index')" :current="request()->routeIs('settings.index')" wire:navigate>
                            {{ __('System Settings') }}
                        </flux:sidebar.item>
                        @can('view audit logs')
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('settings.audit-logs')" :current="request()->routeIs('settings.audit-logs')" wire:navigate>
                                {{ __('Audit Logs') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcan

                <!-- Platform -->
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="code-bracket" href="https://github.com/livewire/flux" target="_blank">{{ __('Repository') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" href="https://flux.laravel-livewire.com" target="_blank">{{ __('Documentation') }}</flux:sidebar.item>
                    @can('viewHorizon')
                        <flux:sidebar.item icon="server" href="/horizon" target="_blank">{{ __('Horizon') }}</flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <flux:toast position="bottom right" />

        @fluxScripts
        @vite('resources/js/app.js')
    </body>
</html>
