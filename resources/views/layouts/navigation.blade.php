@auth
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            {{-- BAGIAN KIRI: LOGO --}}
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('assets/images/AVI.png') }}" alt="Logo AVI" class="block h-10 w-auto" />
                    </a>
                </div>
            </div>

            {{-- BAGIAN KANAN: MENU --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
                {{-- === ROLE: SECT HEAD === --}}
                @if(in_array(Auth::user()->role, ['Sect Head Produksi', 'Sect Head PPIC', 'Sect Head QC']))
                    {{-- DASHBOARD --}}
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                        Dashboard
                    </a>

                    {{-- KONFIRMASI MASTER DATA --}}
                    <a href="{{ route('master.confirmation') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                        Konfirmasi Master Data
                    </a>

                    {{-- KONFIRMASI HENKATEN --}}
                    <a href="{{ route('henkaten.approval') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                        Konfirmasi Henkaten
                    </a>

                {{-- === ROLE: ADMIN & LEADER === --}}
                @else
                    {{-- DASHBOARD --}}
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-gray-600 hover:text-gray-900">
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
                @endif

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
        </div>
    </div>
</nav>
@endauth
