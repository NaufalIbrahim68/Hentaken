@auth
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            {{-- BAGIAN KIRI: Logo --}}
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('assets/images/AVI.png') }}" alt="Logo AVI" class="block h-10 w-auto" />
                    </a>
                </div>
            </div>

            {{-- BAGIAN KANAN: Menu Navigasi --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 focus:outline-none transition ease-in-out duration-150">
                    Dashboard
                </a>

                {{-- MASTER DATA --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                            <div>Master Data</div>
                            <svg class="ms-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('manpower.index')">Man Power</x-dropdown-link>
                        <x-dropdown-link :href="route('machines.index')">Machine</x-dropdown-link>
                        <x-dropdown-link :href="route('materials.index')">Material</x-dropdown-link>
                        <x-dropdown-link :href="route('methods.index')">Method</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- BUAT HENKATEN --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                            <div>Buat Henkaten</div>
                            <svg class="ms-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('henkaten.create')">Man Power</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.machine.create')">Machine</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.material.create')">Material</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.method.create')">Method</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- HENKATEN START --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                            <div>Henkaten Start</div>
                            <svg class="ms-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('henkaten.manpower.start.page')">Man Power</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.machine.start.page')">Machine</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.material.start.page')">Material</x-dropdown-link>
                        <x-dropdown-link :href="route('henkaten.method.start.page')">Method</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- ACTIVITY LOG --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                            <div>Activity Log</div>
                            <svg class="ms-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('activity.log.manpower')">Man Power</x-dropdown-link>
                        <x-dropdown-link :href="route('activity.log.machine')">Machine</x-dropdown-link>
                        <x-dropdown-link :href="route('activity.log.material')">Material</x-dropdown-link>
                        <x-dropdown-link :href="route('activity.log.method')">Method</x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                {{-- USER MENU --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                            <div>{{ Auth::user()->name ?? 'User' }}</div>
                            <svg class="ms-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- TOMBOL MENU MOBILE --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }"
                              class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }"
                              class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- MENU MOBILE --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-gray-50 border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            {{-- MASTER DATA --}}
            <details class="group">
                <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Master Data
                </summary>
                <div class="pl-6 pb-2">
                    <x-responsive-nav-link :href="route('manpower.index')">Man Power</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('machines.index')">Machine</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('materials.index')">Material</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('methods.index')">Method</x-responsive-nav-link>
                </div>
            </details>

            {{-- BUAT HENKATEN --}}
            <details class="group">
                <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Buat Henkaten
                </summary>
                <div class="pl-6 pb-2">
                    <x-responsive-nav-link :href="route('henkaten.create')">Man Power</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.machine.create')">Machine</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.material.create')">Material</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.method.create')">Method</x-responsive-nav-link>
                </div>
            </details>

            {{-- HENKATEN START --}}
            <details class="group">
                <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Henkaten Start
                </summary>
                <div class="pl-6 pb-2">
                    <x-responsive-nav-link :href="route('henkaten.manpower.start.page')">Man Power</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.machine.start.page')">Machine</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.material.start.page')">Material</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('henkaten.method.start.page')">Method</x-responsive-nav-link>
                </div>
            </details>

            {{-- ACTIVITY LOG --}}
            <details class="group">
                <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Activity Log
                </summary>
                <div class="pl-6 pb-2">
                    <x-responsive-nav-link :href="route('activity.log.manpower')">Man Power</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('activity.log.machine')">Machine</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('activity.log.material')">Material</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('activity.log.method')">Method</x-responsive-nav-link>
                </div>
            </details>

            {{-- LOGOUT --}}
            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-gray-200 pt-2">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
@endauth
