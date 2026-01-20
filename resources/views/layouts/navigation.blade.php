@auth
    <div x-data="{ sidebarOpen: false }" class="relative">
        {{-- TOGGLE BUTTON - OUTSIDE SIDEBAR --}}
        <button @click="sidebarOpen = !sidebarOpen"
            class="fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-lg hover:bg-gray-100 transition border border-gray-200">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- OVERLAY --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30"></div>

        {{-- SIDEBAR --}}
        <aside x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col z-40 shadow-xl">

            {{-- MENU --}}
            <nav class="flex-1 overflow-y-auto p-4 pt-6">
                {{-- === ROLE: ADMIN === --}}
                @if (Auth::user()->role === 'Admin')
                    {{-- DASHBOARD --}}
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- MASTER DATA --}}
                    <div x-data="{ masterOpen: false }" class="mb-2">
                        <button @click="masterOpen = !masterOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                                <span>Master Data</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': masterOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="masterOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('manpower.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('machines.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('materials.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('methods.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- ACTIVITY LOG --}}
                    <div x-data="{ activityOpen: false }" class="mb-2">
                        <button @click="activityOpen = !activityOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Activity Log</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': activityOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activityOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('activity.log.manpower') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('activity.log.machine') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('activity.log.material') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('activity.log.method') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- LOG MASTER DATA --}}
                    <div x-data="{ masterLogOpen: false }" class="mb-2">
                        <button @click="masterLogOpen = !masterLogOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Log Master Data</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0"
                                :class="{ 'rotate-180': masterLogOpen }" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="masterLogOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('master.log.manpower') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('master.log.machine') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('master.log.material') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('master.log.method') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- USER MANAGEMENT --}}
                    <div class="mb-2">
                        <a href="{{ route('users.index') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>User Management</span>
                        </a>
                    </div>


                    {{-- === ROLE: SECT HEAD === --}}
                @elseif(in_array(Auth::user()->role, ['Sect Head Produksi', 'Sect Head PPIC', 'Sect Head QC']))
                    {{-- DASHBOARD --}}
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- KONFIRMASI MASTER DATA --}}
                    <a href="{{ route('master.confirmation') }}"
                        class="flex items-center justify-between px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Konfirmasi Data Master</span>
                        </span>
                        @if (isset($pendingMasterDataCount) && $pendingMasterDataCount > 0)
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $pendingMasterDataCount }}
                            </span>
                        @endif
                    </a>

                    {{-- KONFIRMASI HENKATEN --}}
                    <a href="{{ route('henkaten.approval.index') }}"
                        class="flex items-center justify-between px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Konfirmasi Henkaten</span>
                        </span>
                        @if (isset($pendingHenkatenCount) && $pendingHenkatenCount > 0)
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $pendingHenkatenCount }}
                            </span>
                        @endif
                    </a>

                    {{-- KONFIRMASI MATRIX MAN POWER --}}
                    <a href="{{ route('approval.omm.index') }}"
                        class="flex items-center justify-between px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Konfirmasi Matrix Man Power</span>
                        </span>
                        @if (isset($pendingMatrixManPowerCount) && $pendingMatrixManPowerCount > 0)
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $pendingMatrixManPowerCount }}
                            </span>
                        @endif
                    </a>

                    {{-- === ROLE: LEADER === --}}
                @else
                    {{-- DASHBOARD --}}
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-3 mb-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- MASTER DATA --}}
                    <div x-data="{ masterOpen: false }" class="mb-2">
                        <button @click="masterOpen = !masterOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                                <span>Master Data</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': masterOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="masterOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('manpower.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('machines.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('materials.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('methods.index') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- BUAT HENKATEN --}}
                    <div x-data="{ buatOpen: false }" class="mb-2">
                        <button @click="buatOpen = !buatOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Buat Henkaten</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': buatOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="buatOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('henkaten.create') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('henkaten.machine.create') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('henkaten.material.create') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('henkaten.method.create') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- ACTIVITY LOG --}}
                    <div x-data="{ activityOpen: false }" class="mb-2">
                        <button @click="activityOpen = !activityOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Activity Log</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': activityOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activityOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('activity.log.manpower') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('activity.log.machine') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('activity.log.material') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('activity.log.method') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>

                    {{-- LOG MASTER DATA --}}
                    <div x-data="{ masterLogOpen: false }" class="mb-2">
                        <button @click="masterLogOpen = !masterLogOpen"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Log Master Data</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform flex-shrink-0"
                                :class="{ 'rotate-180': masterLogOpen }" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="masterLogOpen" x-transition class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('master.log.manpower') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Man Power</a>
                            <a href="{{ route('master.log.machine') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Machine</a>
                            <a href="{{ route('master.log.material') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Material</a>
                            <a href="{{ route('master.log.method') }}"
                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Method</a>
                        </div>
                    </div>
                @endif
            </nav>

            {{-- USER MENU --}}
            <div class="p-4 border-t border-gray-200">
                <div x-data="{ userOpen: false }" class="relative">
                    <button @click="userOpen = !userOpen"
                        class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="truncate">{{ Auth::user()->name ?? 'User' }}</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': userOpen }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="userOpen" x-transition class="mt-2 space-y-1">
                        {{-- Profile Link --}}
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('Profile') }}
                        </a>

                        {{-- Log Out --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endauth
