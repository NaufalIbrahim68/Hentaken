{{-- MATERIAL QC-PPIC --}}
<div class="bg-white shadow rounded p-4 flex flex-col mt-1">
    <h2 class="text-sm font-semibold mb-3 text-center">MATERIAL</h2>

    {{-- Material Table --}}
    <div class="w-full">
        <table class="w-full border-collapse">
            <tbody>
                {{-- Row Icon --}}
                <tr>
                    @foreach($stationStatuses as $station)
                        @php
                            $isHenkaten = $station['status'] !== 'NORMAL';
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                            <div class="flex justify-center items-center">
                                <div class="material-status rounded-full bg-purple-600 flex items-center justify-center cursor-pointer" data-id="{{ $station['id'] }}" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;">
                                    <svg class="text-white" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
                
                {{-- Row Name (Below Icon) --}}
                <tr>
                    @foreach($stationStatuses as $station)
                        @php
                            $isHenkaten = $station['status'] !== 'NORMAL';
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                            $textColor = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <td class="border border-gray-300 px-1 py-1.5 {{ $bgColorCell }}">
                            <div class="text-center">
                                <p class="text-[9px] font-semibold {{ $textColor }} leading-tight break-words">
                                    {{ $station['material_name'] ?? $station['name'] ?? '-' }}
                                </p>
                            </div>
                        </td>
                    @endforeach
                </tr>
                
                {{-- Row Status --}}
                <tr>
                    @foreach ($stationStatuses as $station)
                        @php
                            $isHenkaten = ($station['status'] === 'HENKATEN');
                            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColor }}">
                            <div class="flex justify-center">
                                <div class="rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}" style="width: 12px; height: 12px; min-width: 12px; min-height: 12px;"></div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="flex justify-center gap-4 mt-3 text-[10px]">
        <div class="flex items-center gap-1">
            <div class="w-2 h-2 rounded-full bg-green-600"></div>
            <span class="text-gray-600">Normal</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-2 h-2 rounded-full bg-red-600"></div>
            <span class="text-gray-600">Henkaten</span>
        </div>
    </div>

    {{-- MATERIAL HENKATEN CARDS --}}
    <div class="border-t mt-2 pt-2">
        <div class="relative">
            <button 
                onclick="scrollMaterialHenkaten('left')" 
                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-700 rounded-full p-2 shadow-md border border-gray-200 transition"
                id="scrollLeftBtnMaterial">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="overflow-x-auto scrollbar-hide scroll-smooth" id="materialHenkatenContainer">
                <div class="flex justify-center gap-3 p-2">
                    @php
                        $filteredMaterialHenkatens = collect();
                        if (isset($materialHenkatens)) {
                            $filteredMaterialHenkatens = $materialHenkatens->filter(function ($henkaten) {
                                return strtolower($henkaten->status) === 'pending';
                            });
                        }
                    @endphp

                    @if($filteredMaterialHenkatens->isNotEmpty())
                        @foreach($filteredMaterialHenkatens as $henkaten)
                            <div 
                                class="material-card flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition"
                                style="width: 220px;"
                                onclick="showMaterialHenkatenDetail(this)" 
                                data-nama="{{ $henkaten->description_before ?? '-' }}"
                                data-nama-after="{{ $henkaten->description_after ?? '-' }}"
                                data-station="{{ $henkaten->station->station_name ?? '-' }}"
                                data-shift="{{ $henkaten->shift ?? '-' }}"
                                data-line-area="{{ $henkaten->line_area ?? '-' }}"
                                data-keterangan="{{ $henkaten->keterangan ?? '-' }}"
                                data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                                data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                                data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                                data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}"
                                data-effective-date="{{ $henkaten->effective_date ? \Carbon\Carbon::parse($henkaten->effective_date)->format('d M Y') : '-' }}"
                                data-end-date="{{ $henkaten->end_date ? \Carbon\Carbon::parse($henkaten->end_date)->format('d M Y') : 'Selanjutnya' }}"
                                data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                                data-material="{{ $henkaten->material->material_name ?? '-' }}">

                                <div class="grid grid-cols-2 gap-1">
                                    <div class="bg-white shadow rounded p-1 text-center">
                                        <h3 class="text-[8px] font-bold mb-0.5">CURRENT PART</h3>
                                        <p class="text-xs font-medium py-1">{{ $henkaten->description_before ?? 'N/A' }}</p>
                                    </div>
                                    <div class="bg-white shadow rounded p-1 text-center">
                                        <h3 class="text-[8px] font-bold mb-0.5 text-red-600">NEW PART</h3>
                                        <p class="text-xs font-medium py-1">{{ $henkaten->description_after ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-1">
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[7px] text-white font-medium">
                                            Start: {{ $henkaten->serial_number_start ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[7px] text-white font-medium">
                                            End: {{ $henkaten->serial_number_end ?? '-' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex justify-center">
                                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                                        ACTIVE:
                                        {{ $henkaten->effective_date ? \Carbon\Carbon::parse($henkaten->effective_date)->format('d/M/y') : 'N/A' }}
                                        -
                                        {{ $henkaten->end_date ? \Carbon\Carbon::parse($henkaten->end_date)->format('d/M/y') : '...' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-xs text-gray-400 py-4 w-full">No Actived Material Henkaten</div>
                    @endif
                </div>
            </div>

            <button 
                onclick="scrollMaterialHenkaten('right')" 
                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-700 rounded-full p-2 shadow-md border border-gray-200 transition"
                id="scrollRightBtnMaterial">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    {{-- MATERIAL MODAL --}}
    <div id="materialHenkatenDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100">
            <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-yellow-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten Material</h3>
                <button onclick="closeMaterialHenkatenModal()" class="text-white hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Material</h4>
                    <div class="flex items-center justify-around">
                        <div class="text-center">
                            <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-500 mx-auto my-2">
                                <path d="M17.004 10.407c.138.435-.216.842-.672.842h-3.465a.75.75 0 0 1-.65-.375l-1.732-3c-.229-.396-.053-.907.393-1.004a5.252 5.252 0 0 1 6.126 3.537ZM8.12 8.464c.307-.338.838-.235 1.066.16l1.732 3a.75.75 0 0 1 0 .75l-1.732 3c-.229.397-.76.5-1.067.161A5.23 5.23 0 0 1 6.75 12a5.23 5.23 0 0 1 1.37-3.536ZM10.878 17.13c-.447-.098-.623-.608-.394-1.004l1.733-3.002a.75.75 0 0 1 .65-.375h3.465c.457 0 .81.407.672.842a5.252 5.252 0 0 1-6.126 3.539Z" />
                                <path fill-rule="evenodd" d="M21 12.75a.75.75 0 1 0 0-1.5h-.783a8.22 8.22 0 0 0-.237-1.357l.734-.267a.75.75 0 1 0-.513-1.41l-.735.268a8.24 8.24 0 0 0-.689-1.192l.6-.503a.75.75 0 1 0-.964-1.149l-.6.504a8.3 8.3 0 0 0-1.054-.885l.391-.678a.75.75 0 1 0-1.299-.75l-.39.676a8.188 8.188 0 0 0-1.295-.47l.136-.77a.75.75 0 0 0-1.477-.26l-.136.77a8.36 8.36 0 0 0-1.377 0l-.136-.77a.75.75 0 1 0-1.477.26l.136.77c-.448.121-.88.28-1.294.47l-.39-.676a.75.75 0 0 0-1.3.75l.392.678a8.29 8.29 0 0 0-1.054.885l-.6-.504a.75.75 0 1 0-.965 1.149l.6.503a8.243 8.243 0 0 0-.689 1.192L3.8 8.216a.75.75 0 1 0-.513 1.41l.735.267a8.222 8.222 0 0 0-.238 1.356h-.783a.75.75 0 0 0 0 1.5h.783c.042.464.122.917.238 1.356l-.735.268a.75.75 0 0 0 .513 1.41l.735-.268c.197.417.428.816.69 1.191l-.6.504a.75.75 0 0 0 .963 1.15l.601-.505c.326.323.679.62 1.054.885l-.392.68a.75.75 0 0 0 1.3.75l.39-.679c.414.192.847.35 1.294.471l-.136.77a.75.75 0 0 0 1.477.261l.137-.772a8.332 8.332 0 0 0 1.376 0l.136.772a.75.75 0 1 0 1.477-.26l-.136-.771a8.19 8.19 0 0 0 1.294-.47l.391.677a.75.75 0 0 0 1.3-.75l-.393-.679a8.29 8.29 0 0 0 1.054-.885l.601.504a.75.75 0 0 0 .964-1.15l-.6-.503c.261-.375.492-.774.69-1.191l.735.267a.75.75 0 1 0 .512-1.41l-.734-.267c.115-.439.195-.892.237-1.356h.784Zm-2.657-3.06a6.744 6.744 0 0 0-1.19-2.053 6.784 6.784 0 0 0-1.82-1.51A6.705 6.705 0 0 0 12 5.25a6.8 6.8 0 0 0-1.225.11 6.7 6.7 0 0 0-2.15.793 6.784 6.784 0 0 0-2.952 3.489.76.76 0 0 1-.036.098A6.74 6.74 0 0 0 5.251 12a6.74 6.74 0 0 0 3.366 5.842l.009.005a6.704 6.704 0 0 0 2.18.798l.022.003a6.792 6.792 0 0 0 2.368-.004 6.704 6.704 0 0 0 2.205-.811 6.785 6.785 0 0 0 1.762-1.484l.009-.01.009-.01a6.743 6.743 0 0 0 1.18-2.066c.253-.707.39-1.469.39-2.263a6.74 6.74 0 0 0-.408-2.309Z" clip-rule="evenodd" />
                            </svg>
                            <p id="modalMaterialBefore" class="font-semibold text-sm mt-1">-</p>
                        </div>
                        <div class="text-2xl text-gray-400">→</div>
                        <div class="text-center">
                            <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-green-600 mx-auto my-2">
                                <path d="M17.004 10.407c.138.435-.216.842-.672.842h-3.465a.75.75 0 0 1-.65-.375l-1.732-3c-.229-.396-.053-.907.393-1.004a5.252 5.252 0 0 1 6.126 3.537ZM8.12 8.464c.307-.338.838-.235 1.066.16l1.732 3a.75.75 0 0 1 0 .75l-1.732 3c-.229.397-.76.5-1.067.161A5.23 5.23 0 0 1 6.75 12a5.23 5.23 0 0 1 1.37-3.536ZM10.878 17.13c-.447-.098-.623-.608-.394-1.004l1.733-3.002a.75.75 0 0 1 .65-.375h3.465c.457 0 .81.407.672.842a5.252 5.252 0 0 1-6.126 3.539Z" />
                                <path fill-rule="evenodd" d="M21 12.75a.75.75 0 1 0 0-1.5h-.783a8.22 8.22 0 0 0-.237-1.357l.734-.267a.75.75 0 1 0-.513-1.41l-.735.268a8.24 8.24 0 0 0-.689-1.192l.6-.503a.75.75 0 1 0-.964-1.149l-.6.504a8.3 8.3 0 0 0-1.054-.885l.391-.678a.75.75 0 1 0-1.299-.75l-.39.676a8.188 8.188 0 0 0-1.295-.47l.136-.77a.75.75 0 0 0-1.477-.26l-.136.77a8.36 8.36 0 0 0-1.377 0l-.136-.77a.75.75 0 1 0-1.477.26l.136.77c-.448.121-.88.28-1.294.47l-.39-.676a.75.75 0 0 0-1.3.75l.392.678a8.29 8.29 0 0 0-1.054.885l-.6-.504a.75.75 0 1 0-.965 1.149l.6.503a8.243 8.243 0 0 0-.689 1.192L3.8 8.216a.75.75 0 1 0-.513 1.41l.735.267a8.222 8.222 0 0 0-.238 1.356h-.783a.75.75 0 0 0 0 1.5h.783c.042.464.122.917.238 1.356l-.735.268a.75.75 0 0 0 .513 1.41l.735-.268c.197.417.428.816.69 1.191l-.6.504a.75.75 0 0 0 .963 1.15l.601-.505c.326.323.679.62 1.054.885l-.392.68a.75.75 0 0 0 1.3.75l.39-.679c.414.192.847.35 1.294.471l-.136.77a.75.75 0 0 0 1.477.261l.137-.772a8.332 8.332 0 0 0 1.376 0l.136.772a.75.75 0 1 0 1.477-.26l-.136-.771a8.19 8.19 0 0 0 1.294-.47l.391.677a.75.75 0 0 0 1.3-.75l-.393-.679a8.29 8.29 0 0 0 1.054-.885l.601.504a.75.75 0 0 0 .964-1.15l-.6-.503c.261-.375.492-.774.69-1.191l.735.267a.75.75 0 1 0 .512-1.41l-.734-.267c.115-.439.195-.892.237-1.356h.784Zm-2.657-3.06a6.744 6.744 0 0 0-1.19-2.053 6.784 6.784 0 0 0-1.82-1.51A6.705 6.705 0 0 0 12 5.25a6.8 6.8 0 0 0-1.225.11 6.7 6.7 0 0 0-2.15.793 6.784 6.784 0 0 0-2.952 3.489.76.76 0 0 1-.036.098A6.74 6.74 0 0 0 5.251 12a6.74 6.74 0 0 0 3.366 5.842l.009.005a6.704 6.704 0 0 0 2.18.798l.022.003a6.792 6.792 0 0 0 2.368-.004 6.704 6.704 0 0 0 2.205-.811 6.785 6.785 0 0 0 1.762-1.484l.009-.01.009-.01a6.743 6.743 0 0 0 1.18-2.066c.253-.707.39-1.469.39-2.263a6.74 6.74 0 0 0-.408-2.309Z" clip-rule="evenodd" />
                            </svg>
                            <p id="modalMaterialAfter" class="font-semibold text-sm mt-1">-</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station</p>
                            <p id="modalStation" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Shift</p>
                            <p id="modalShift" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Line Area</p>
                            <p id="modalLineArea" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Keterangan</p>
                            <p id="modalKeterangan" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Material</p>
                            <p id="modalMaterial" class="font-semibold text-sm truncate">-</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Serial Number Start</p>
                            <p id="modalSerialStart" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Serial Number End</p>
                            <p id="modalSerialEnd" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Time Start</p>
                            <p id="modalTimeStart" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Time End</p>
                            <p id="modalTimeEnd" class="font-semibold text-sm">-</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center px-4 py-2">
                    <div class="text-left">
                        <p class="text-xs text-gray-500">Mulai</p>
                        <p id="modalEffectiveDate" class="font-semibold text-lg">-</p>
                    </div>
                    <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Selesai</p>
                        <p id="modalEndDate" class="font-semibold text-lg">-</p>
                    </div>
                </div>

                <div id="modalLampiranSection" class="hidden pt-2">
                    <a id="modalLampiranLink" href="#" target="_blank" class="block bg-orange-600 hover:bg-orange-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                        Lihat Lampiran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
