{{-- MACHINE QC SECTION --}}
<div class="bg-white shadow rounded p-4 flex flex-col mt-1">
    <h2 class="text-sm font-semibold mb-3 text-center">MACHINE</h2>

    {{-- Machine Table --}}
<div class="w-full">
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-50">
                @foreach ($machines as $mc)
                    @php
                        // Logika Header (Nama Stasiun)
                        $isHenkaten = ($mc->keterangan === 'HENKATEN');
                        $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                        $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                    @endphp
                    
                @endforeach
            </tr>
        </thead>
        <tbody>
            
            {{-- Row 1: Icon (TIDAK DIUBAH) --}}
            <tr>
                @foreach ($machines as $mc)
                    @php
                        $isHenkaten = ($mc->keterangan === 'HENKATEN');
                        $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                    @endphp
                    <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                        <div class="flex justify-center items-center">
                            {{-- START ICON CODE --}}
                            <div class="machine-status rounded-full flex items-center justify-center cursor-pointer" 
                                style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; background-color: #9333ea;"
                                onclick="toggleMachine(this)">
                                <svg class="text-white" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            {{-- END ICON CODE --}}
                        </div>
                    </td>
                @endforeach
            </tr>
            
            {{-- Pengecekan Role: Tampilkan Kategori Mesin hanya untuk Leader QC dan Sect Head QC --}}
            @php
                // Mengambil role pengguna yang sedang login
                $currentUserRole = auth()->user()->role ?? 'guest'; 
                $showMachineDetails = in_array($currentUserRole, ['Leader QC', 'Sect Head QC', 'Leader PPIC', 'Sect Head PPIC']);
            @endphp
                        
            @if ($showMachineDetails)
                {{-- Row 2: Kategori Mesin (Hanya untuk Leader QC & PPIC) --}}
                <tr>
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                            $textColor = $isHenkaten ? 'text-white' : 'text-gray-800';
                            
                            // Mengambil kategori mesin
                            $categoryName = $mc->machines_category ?? 'N/A'; 
                        @endphp
                        <td class="border border-gray-300 px-1 py-1 text-[9px] text-center {{ $bgColorCell }} {{ $textColor }} font-semibold">
                            <div class="leading-tight break-words">
                                {{ $categoryName }} 
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endif

            {{-- Row 3: Status Dot --}}
            <tr>
                @foreach ($machines as $mc)
                    @php
                        $isHenkaten = ($mc->keterangan === 'HENKATEN');
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



<div class="border-t mt-4 pt-4 overflow-x-auto scrollbar-hide">
    <div class="flex justify-center gap-3 p-2">
        @php
            $filteredMachineHenkatens = $machineHenkatens->filter(function ($henkaten) {
                return strtolower($henkaten->status) === 'pending';
            });
        @endphp
        
        {{-- ========================================================== --}}
        {{-- 🔔 PERUBAHAN DI SINI: Gunakan $filteredMachineHenkatens --}}
        {{-- ========================================================== --}}
        @forelse($filteredMachineHenkatens as $henkaten)
            <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 shadow-md cursor-pointer hover:bg-gray-100 transition"
                style="width: 220px;"
                onclick="showMachineHenkatenDetail(this)"
                
                {{-- Data untuk "Perubahan" --}}
                data-description-before="{{ $henkaten->description_before ?? 'N/A' }}"
                data-description-after="{{ $henkaten->description_after ?? 'N/A' }}"

                {{-- Data untuk Grid 1 --}}
                data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                data-shift="{{ $henkaten->shift ?? 'N/A' }}"
                data-line-area="{{ $henkaten->line_area ?? 'N/A' }}"
                data-keterangan="{{ $henkaten->keterangan ?? 'N/A' }}"
                data-machine="{{ $henkaten->machine ?? 'N/A' }}"

                {{-- Data untuk Grid 2 --}}
                data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                data-time-start="{{ $henkaten->time_start ?? '-' }}"
                data-time-end="{{ $henkaten->time_end ?? '-' }}"

                {{-- Data untuk Periode (Format disamakan dengan gambar) --}}
                data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d M Y') : '-' }}"
                data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d M Y') : 'Selanjutnya' }}"

                {{-- Data untuk Lampiran --}}
                data-lampiran="{{ $henkaten->lampiran ? asset($henkaten->lampiran) : '' }}">
                
                {{-- Tampilan Visual Kartu (disesuaikan dengan field baru) --}}
                <div class="flex items-center justify-center space-x-1.5">
                    <div class="text-center">
                        <div class="text-[8px] font-bold">OLD JIG</div>
                        <div class="text-xl my-0.5">⚙️</div>
                        <p class="text-[7px] font-semibold">{{ $henkaten->description_before ?? 'N/A' }}</p>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <div class="text-[8px] font-bold text-red-600">NEW JIG</div>
                        <div class="text-xl my-0.5">⚙️</div>
                        <p class="text-[7px] font-semibold">{{ $henkaten->description_after ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[7px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[7px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                        {{-- Format visual kartu tetap ringkas --}}
                        ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                    </div>
                </div>
            </div>
        @empty
            {{-- ========================================================== --}}
            {{-- 🔔 PERUBAHAN DI SINI: Teks pemberitahuan --}}
            {{-- ========================================================== --}}
            <div class="text-center text-xs text-gray-400 py-4 w-full">No Actived Machine Henkaten</div>
        @endforelse
    </div>
</div>
</div>

{{-- ============================================= --}}
{{-- MODAL UNTUK DETAIL MACHINE HENKATEN --}}
{{-- ============================================= --}}
<div id="henkatenModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    
<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl"> 
        
    {{-- 1️⃣ HEADER MODAL --}}
    <div class="flex justify-between items-center border-b p-4 bg-gradient-to-r from-emerald-500 to-teal-600">
    <h3 class="text-lg font-semibold text-white">Detail Henkaten Machine</h3>
    <button id="modalCloseButton" class="text-white hover:text-gray-200 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>

    {{-- 2️⃣ ISI MODAL --}}
    <div class="p-6 space-y-4">

        {{-- PERUBAHAN JIG/MACHINE --}}
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Jig / Machine</h4>
            <div class="flex items-center justify-around">
                
            {{-- SEBELUM --}}
<div class="text-center">
    <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
    
    {{-- IKON OBENG (Sebelum - Warna Abu) --}}
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-500 mx-auto my-2">
        <path d="M17.004 10.407c.138.435-.216.842-.672.842h-3.465a.75.75 0 0 1-.65-.375l-1.732-3c-.229-.396-.053-.907.393-1.004a5.252 5.252 0 0 1 6.126 3.537ZM8.12 8.464c.307-.338.838-.235 1.066.16l1.732 3a.75.75 0 0 1 0 .75l-1.732 3c-.229.397-.76.5-1.067.161A5.23 5.23 0 0 1 6.75 12a5.23 5.23 0 0 1 1.37-3.536ZM10.878 17.13c-.447-.098-.623-.608-.394-1.004l1.733-3.002a.75.75 0 0 1 .65-.375h3.465c.457 0 .81.407.672.842a5.252 5.252 0 0 1-6.126 3.539Z" />
        <path fill-rule="evenodd" d="M21 12.75a.75.75 0 1 0 0-1.5h-.783a8.22 8.22 0 0 0-.237-1.357l.734-.267a.75.75 0 1 0-.513-1.41l-.735.268a8.24 8.24 0 0 0-.689-1.192l.6-.503a.75.75 0 1 0-.964-1.149l-.6.504a8.3 8.3 0 0 0-1.054-.885l.391-.678a.75.75 0 1 0-1.299-.75l-.39.676a8.188 8.188 0 0 0-1.295-.47l.136-.77a.75.75 0 0 0-1.477-.26l-.136.77a8.36 8.36 0 0 0-1.377 0l-.136-.77a.75.75 0 1 0-1.477.26l.136.77c-.448.121-.88.28-1.294.47l-.39-.676a.75.75 0 0 0-1.3.75l.392.678a8.29 8.29 0 0 0-1.054.885l-.6-.504a.75.75 0 1 0-.965 1.149l.6.503a8.243 8.243 0 0 0-.689 1.192L3.8 8.216a.75.75 0 1 0-.513 1.41l.735.267a8.222 8.222 0 0 0-.238 1.356h-.783a.75.75 0 0 0 0 1.5h.783c.042.464.122.917.238 1.356l-.735.268a.75.75 0 0 0 .513 1.41l.735-.268c.197.417.428.816.69 1.191l-.6.504a.75.75 0 0 0 .963 1.15l.601-.505c.326.323.679.62 1.054.885l-.392.68a.75.75 0 0 0 1.3.75l.39-.679c.414.192.847.35 1.294.471l-.136.77a.75.75 0 0 0 1.477.261l.137-.772a8.332 8.332 0 0 0 1.376 0l.136.772a.75.75 0 1 0 1.477-.26l-.136-.771a8.19 8.19 0 0 0 1.294-.47l.391.677a.75.75 0 0 0 1.3-.75l-.393-.679a8.29 8.29 0 0 0 1.054-.885l.601.504a.75.75 0 0 0 .964-1.15l-.6-.503c.261-.375.492-.774.69-1.191l.735.267a.75.75 0 1 0 .512-1.41l-.734-.267c.115-.439.195-.892.237-1.356h.784Zm-2.657-3.06a6.744 6.744 0 0 0-1.19-2.053 6.784 6.784 0 0 0-1.82-1.51A6.705 6.705 0 0 0 12 5.25a6.8 6.8 0 0 0-1.225.11 6.7 6.7 0 0 0-2.15.793 6.784 6.784 0 0 0-2.952 3.489.76.76 0 0 1-.036.098A6.74 6.74 0 0 0 5.251 12a6.74 6.74 0 0 0 3.366 5.842l.009.005a6.704 6.704 0 0 0 2.18.798l.022.003a6.792 6.792 0 0 0 2.368-.004 6.704 6.704 0 0 0 2.205-.811 6.785 6.785 0 0 0 1.762-1.484l.009-.01.009-.01a6.743 6.743 0 0 0 1.18-2.066c.253-.707.39-1.469.39-2.263a6.74 6.74 0 0 0-.408-2.309Z" clip-rule="evenodd" />
    </svg>
    
    <p id="modalDescriptionBefore" class="font-semibold text-sm mt-1">-</p>
</div>

<div class="text-2xl text-gray-400">→</div>

{{-- SESUDAH --}}
<div class="text-center">
    <span class="text-xs bg-green-600 text-white px-2 py-0.5 rounded">Sesudah</span>
    
    {{-- IKON OBENG (Sesudah - Warna Merah) --}}
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-green-600 mx-auto my-2">
        <path d="M17.004 10.407c.138.435-.216.842-.672.842h-3.465a.75.75 0 0 1-.65-.375l-1.732-3c-.229-.396-.053-.907.393-1.004a5.252 5.252 0 0 1 6.126 3.537ZM8.12 8.464c.307-.338.838-.235 1.066.16l1.732 3a.75.75 0 0 1 0 .75l-1.732 3c-.229.397-.76.5-1.067.161A5.23 5.23 0 0 1 6.75 12a5.23 5.23 0 0 1 1.37-3.536ZM10.878 17.13c-.447-.098-.623-.608-.394-1.004l1.733-3.002a.75.75 0 0 1 .65-.375h3.465c.457 0 .81.407.672.842a5.252 5.252 0 0 1-6.126 3.539Z" />
        <path fill-rule="evenodd" d="M21 12.75a.75.75 0 1 0 0-1.5h-.783a8.22 8.22 0 0 0-.237-1.357l.734-.267a.75.75 0 1 0-.513-1.41l-.735.268a8.24 8.24 0 0 0-.689-1.192l.6-.503a.75.75 0 1 0-.964-1.149l-.6.504a8.3 8.3 0 0 0-1.054-.885l.391-.678a.75.75 0 1 0-1.299-.75l-.39.676a8.188 8.188 0 0 0-1.295-.47l.136-.77a.75.75 0 0 0-1.477-.26l-.136.77a8.36 8.36 0 0 0-1.377 0l-.136-.77a.75.75 0 1 0-1.477.26l.136.77c-.448.121-.88.28-1.294.47l-.39-.676a.75.75 0 0 0-1.3.75l.392.678a8.29 8.29 0 0 0-1.054.885l-.6-.504a.75.75 0 1 0-.965 1.149l.6.503a8.243 8.243 0 0 0-.689 1.192L3.8 8.216a.75.75 0 1 0-.513 1.41l.735.267a8.222 8.222 0 0 0-.238 1.356h-.783a.75.75 0 0 0 0 1.5h.783c.042.464.122.917.238 1.356l-.735.268a.75.75 0 0 0 .513 1.41l.735-.268c.197.417.428.816.69 1.191l-.6.504a.75.75 0 0 0 .963 1.15l.601-.505c.326.323.679.62 1.054.885l-.392.68a.75.75 0 0 0 1.3.75l.39-.679c.414.192.847.35 1.294.471l-.136.77a.75.75 0 0 0 1.477.261l.137-.772a8.332 8.332 0 0 0 1.376 0l.136.772a.75.75 0 1 0 1.477-.26l-.136-.771a8.19 8.19 0 0 0 1.294-.47l.391.677a.75.75 0 0 0 1.3-.75l-.393-.679a8.29 8.29 0 0 0 1.054-.885l.601.504a.75.75 0 0 0 .964-1.15l-.6-.503c.261-.375.492-.774.69-1.191l.735.267a.75.75 0 1 0 .512-1.41l-.734-.267c.115-.439.195-.892.237-1.356h.784Zm-2.657-3.06a6.744 6.744 0 0 0-1.19-2.053 6.784 6.784 0 0 0-1.82-1.51A6.705 6.705 0 0 0 12 5.25a6.8 6.8 0 0 0-1.225.11 6.7 6.7 0 0 0-2.15.793 6.784 6.784 0 0 0-2.952 3.489.76.76 0 0 1-.036.098A6.74 6.74 0 0 0 5.251 12a6.74 6.74 0 0 0 3.366 5.842l.009.005a6.704 6.704 0 0 0 2.18.798l.022.003a6.792 6.792 0 0 0 2.368-.004 6.704 6.704 0 0 0 2.205-.811 6.785 6.785 0 0 0 1.762-1.484l.009-.01.009-.01a6.743 6.743 0 0 0 1.18-2.066c.253-.707.39-1.469.39-2.263a6.74 6.74 0 0 0-.408-2.309Z" clip-rule="evenodd" />
    </svg>
    
    <p id="modalDescriptionAfter" class="font-semibold text-sm text-green-600 mt-1">-</p>
</div>
            </div>
            </div>
        
        {{-- DETAIL INFORMASI (Grid 1) --}}
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
               {{-- MACHINE (Full Width Box) --}}
    <div class="bg-orange-50 p-3 rounded-lg">
        <p class="text-xs text-gray-500">Machine</p>
        <p id="modalMachine" class="font-semibold text-sm">-</p>
    </div>
        </div>
        </div>

        {{-- DETAIL INFORMASI (Grid 2) --}}
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

        {{-- PERIODE --}}
        <div class="flex justify-between items-center px-4 py-2 border-t mt-4 pt-4">
            <div class="text-left">
                <p class="text-xs text-gray-500">Mulai</p>
                <p id="modalEffectiveDate" class="font-semibold text-lg">-</p>
            </div>

            <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>

            <div class="text-right">
                <p class="text-xs text-gray-500">Selesai</p>
                <p id="modalEndDate" class="font-semibold text-lg">-</p>
            </div>
        </div>

        {{-- LAMPIRAN --}}
        <div id="modalLampiranSection" class="hidden pt-2">
    <a id="modalLampiranLink" href="#" target="_blank"
        class="block bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm px-4 py-2 rounded-lg text-center transition-all duration-300 shadow-md hover:shadow-lg">
        Lihat Lampiran
    </a>
</div>

    </div>
</div>
</div>
