
<x-app-layout>



    
    <style>
        .machine-status {
            width: 40px;
            height: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #333;
            margin: 1px;
            font-size: 7px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
        }

        .machine-active {
            background-color: white;
            color: #333;
            border-color: #333;
        }

        .machine-inactive {
            background-color: #ef4444;
            color: white;
            border-color: #333;
        }

        .jig-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #e5e7eb, #9ca3af);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
            color: #374151;
            border: 2px solid #6b7280;
        }

        .arrow {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin: 0 8px;
        }

        .status-badge {
            background-color: #f59e0b;
            color: white;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 7px;
            font-weight: bold;
            margin-top: 3px;
            display: inline-block;
        }

        .document-icon {
            width: 40px;
            height: 40px;
            border: 2px solid #6b7280;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #6b7280;
            flex-direction: column;
            border-radius: 4px;
        }

        .status-text {
            padding: 1px 3px;
            border-radius: 2px;
            font-weight: bold;
        }

        .status-henkaten {
            background-color: red;
            color: white;
        }

        .nav-button {
            background-color: #e5e7eb;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 12px;
        }

        .nav-button:hover {
            background-color: #d1d5db;
        }

        .material-status {
            width: 100%;
            height: 40px;
        }

        .compact-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            height: calc(100vh - 120px);
        }

        .section-compact {
            display: flex;
            flex-direction: column;
        }

        .section-compact .overflow-x-auto {
            flex: 1;
            min-height: 0;
        }

        /* Optimasi untuk profile icons */
        .profile-icon {
            width: 6px;
            height: 6px;
        }

        .status-dot {
            width: 2px;
            height: 2px;
        }

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}


    </style>

    

 <div class="w-full h-screen flex flex-col px-3 py-1">
    {{-- HEADER - . --}}
    <div class="flex items-center justify-between border-b pb-1 mb-1 h-[8vh]">
        {{-- Kolom Kiri --}}
         <div class="w-1/3"></div>

        {{-- Title & Date --}}
        <div class="w-1/3 text-center">
            <h1 class="text-base font-bold">HENKATEN FA LINE 5</h1>
            <p class="text-[10px] text-gray-600" id="current-date"></p>
        </div>

        {{-- Time & Shift --}}
        <div class="w-1/3 text-right">
            <p class="font-mono text-sm" id="current-time"></p>
            <p class="text-xs" id="current-shift"></p>
        </div>
    </div>

    {{-- 4 SECTION GRID - . --}}
    <div class="grid grid-cols-2 gap-3 h-[92vh]">
 {{-- MAN POWER --}}
<div class="bg-white shadow rounded p-1 flex flex-col">
    <h2 class="text-xs font-semibold mb-2 text-center">MAN POWER</h2>

    {{-- ======================================================================= --}}
{{-- BAGIAN ATAS: DAFTAR SEMUA MAN POWER PADA SHIFT SAAT INI (DINAMIS) --}}
{{-- ======================================================================= --}}
<div class="relative">

    {{-- Tombol Scroll Kiri --}}
    <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
        <button id="scrollLeftManPower" class="w-4 h-4 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <div id="manPowerTableContainer" class="mx-6 overflow-x-auto scrollbar-hide scroll-smooth">
        <div class="flex gap-2 py-2">

            {{-- ============================================================ --}}
            {{-- CEK: APAKAH DATA MAN POWER KOSONG --}}
            {{-- ============================================================ --}}
            @if (isset($dataManPowerKosong) && $dataManPowerKosong)
                <div class="w-full text-center text-gray-500 py-10">
                    <p class="text-sm font-medium">Data Man Power belum di filter</p>
                </div>
            @else
                {{-- ============================================================ --}}
                {{-- LOOP STASIUN & PEKERJA --}}
                {{-- ============================================================ --}}
               @foreach($groupedManPower as $stationId => $stationWorkers)
    @foreach($stationWorkers as $currentWorker)

        @php
            $isHenkaten = ($currentWorker->status == 'Henkaten');
            $displayName = $currentWorker->nama;
            $statusText = $isHenkaten ? 'HENKATEN' : 'NORMAL';
            $statusColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
            // Ganti ke station_name
            $stationName = $currentWorker->station ? $currentWorker->station->station_name : 'Station ' . $stationId;
        @endphp

        {{-- ============================================================ --}}
        {{-- TAMPILAN IKON PEKERJA (DIPERKECIL) --}}
        {{-- ============================================================ --}}
        <div class="flex-shrink-0 text-center" style="min-width: 45px;">
            <p class="text-[8px] font-bold text-gray-800 mb-0.5 truncate" style="max-width: 45px;">{{ $stationName }}</p>

            <div class="relative mx-auto mb-1 w-6 h-6">
                <div class="w-full h-full rounded-full bg-purple-600 flex items-center justify-center text-white text-xs font-bold">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>

            <p class="text-[7px] font-medium mb-0.5 truncate px-0.5" style="max-width: 45px;" title="{{ $displayName }}">
                {{ $displayName }}
            </p>

            <div>
                <div class="w-2 h-2 rounded-full {{ $statusColor }} mx-auto" title="{{ $statusText }}"></div>
            </div>
        </div>

    @endforeach
@endforeach            
@endif
        </div>
    </div>

    {{-- Tombol Scroll Kanan --}}
    <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
        <button id="scrollRightManPower" class="w-4 h-4 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>
</div>

    {{-- ======================================================================= --}}
{{-- BAGIAN BAWAH: DETAIL HENKATEN (SATU KOTAK PER HENKATEN) --}}
{{-- ======================================================================= --}}
<div class="border-t mt-2 pt-2">
    <div class="flex items-center gap-1">
        <button id="scrollLeftShift" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        

        <div id="shiftChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">
           @php
    $currentGroup = $currentGroup ?? 'A';
    $filteredHenkatens = $activeManPowerHenkatens->filter(function ($henkaten) use ($currentGroup) {
        return optional($henkaten->manPower)->grup === $currentGroup;
    });
@endphp


            @if($filteredHenkatens->isNotEmpty())
                <div class="flex justify-center gap-3 min-w-full px-2">
                    @foreach($filteredHenkatens as $henkaten)
                        @php
                            $startDate = strtoupper($henkaten->effective_date->format('j/M/y'));
                            $endDate = $henkaten->end_date ? strtoupper($henkaten->end_date->format('j/M/y')) : 'SELANJUTNYA';
                        @endphp

                        {{-- KOTAK UTAMA UNTUK SETIAP HENKATEN --}}
                        <div class="flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border border-gray-300 shadow-md cursor-pointer hover:bg-orange-50 transition transform hover:scale-[1.02]"
                            style="width: 240px;"
                            onclick="showHenkatenDetail({{ $henkaten->id }})"
                            data-henkaten-id="{{ $henkaten->id }}"
                            data-nama="{{ $henkaten->nama }}"
                            data-nama-after="{{ $henkaten->nama_after }}"
                            data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                            data-shift="{{ $henkaten->shift }}"
                            data-keterangan="{{ $henkaten->keterangan }}"
                            data-line-area="{{ $henkaten->line_area }}"
                            data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y') : '-' }}"
                            data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y') : 'Selanjutnya' }}"
                            data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                            data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                            data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                            data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                            data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}"
                        >
                            {{-- Perubahan Pekerja --}}
                            <div class="flex items-center justify-center space-x-2">
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">👤</div>
                                    <p class="text-[8px] font-semibold truncate w-20">{{ $henkaten->nama }}</p>
                                    <div class="w-2 h-2 rounded-full bg-red-500 mx-auto mt-0.5" title="Before"></div>
                                </div>
                                <div class="text-sm text-gray-400 font-bold">→</div>
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">👤</div>
                                    <p class="text-[8px] font-semibold truncate w-20">{{ $henkaten->nama_after }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5" title="After"></div>
                                </div>
                            </div>

                            {{-- Serial Number --}}
                            <div class="grid grid-cols-2 gap-1">
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[8px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? '-' }}</span>
                                </div>
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[8px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? '-' }}</span>
                                </div>
                            </div>

                            {{-- Periode Aktif --}}
                            <div class="flex justify-center">
                                <div class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-[9px] font-semibold">
                                    {{ $startDate }} - {{ $endDate }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-xs text-gray-400 py-4">
                    No Actived Henkaten In this Group
                </div>
            @endif
        </div>
    

                    

    <button id="scrollRightShift" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>
    </div>
    </div>

    {{-- ============================================================= --}}
    {{-- MODAL DETAIL HENKATEN --}}
    {{-- ============================================================= --}}
    <div id="henkatenDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all scale-100">
            {{-- HEADER MODAL --}}
            <div class="sticky top-0 bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten</h3>
                <button onclick="closeHenkatenModal()" class="text-white hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- DIUBAH: Padding (p-6 -> p-4) dan Spasi Vertikal (space-y-6 -> space-y-4) dikurangi --}}
            <div class="p-4 space-y-4">
                {{-- PERUBAHAN PEKERJA --}}
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Pekerja</h4>
                    <div class="flex items-center justify-around">
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full bg-purple-600 flex items-center justify-center text-white text-xl mx-auto mb-1">👤</div>
                            <p id="modalNamaBefore" class="font-semibold text-sm"></p>
                            <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                        </div>
                        <div class="text-2xl text-gray-400">→</div>
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full bg-purple-600 flex items-center justify-center text-white text-xl mx-auto mb-1">👤</div>
                            <p id="modalNamaAfter" class="font-semibold text-sm"></p>
                            <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                        </div>
                    </div>
                </div>

                {{-- INFORMASI DETAIL --}}
             
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Station</p>
                        <p id="modalStation" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Shift</p>
                        <p id="modalShift" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Line Area</p>
                        <p id="modalLineArea" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Keterangan</p>
                        <p id="modalKeterangan" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number Start</p>
                        <p id="modalSerialStart" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number End</p>
                        <p id="modalSerialEnd" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time Start</p>
                        <p id="modalTimeStart" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time End</p>
                        <p id="modalTimeEnd" class="font-semibold text-sm"></p>
                    </div>
                </div>

                {{-- PERIODE --}}
                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500">Mulai</p>
                            <p id="modalEffectiveDate" class="font-semibold"></p>
                        </div>
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Selesai</p>
                            <p id="modalEndDate" class="font-semibold"></p>
                        </div>
                    </div>
                </div>

                {{-- LAMPIRAN --}}
                <div id="modalLampiranSection" class="hidden">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Lampiran</h4>
                    <a id="modalLampiranLink" href="#" target="_blank" class="block bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                        Lihat Lampiran
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>

      
  {{-- METHOD - Icon View --}}
<div class="bg-white shadow rounded p-4 flex flex-col">
    <h2 class="text-sm font-semibold mb-3 text-center">METHOD</h2>

   {{-- Icon Carousel Wrapper --}}
<div class="relative mb-4">
    {{-- Left Arrow --}}
    <button id="scrollLeftMethodIcon" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white shadow rounded p-0.5 hover:bg-gray-100">
        <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    {{-- Icon Container - Scrollable --}}
    <div id="methodScrollContainer" class="overflow-x-auto px-8 scrollbar-hide">
        {{-- TETAP flex-nowrap agar satu baris --}}
        <div class="flex gap-2 justify-start flex-nowrap">
            @foreach ($methods as $m)
                
                {{-- Logika untuk Henkaten --}}
              @php
$isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
$bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
$statusText = $m->status ?? 'NORMAL';
@endphp

                <div class="flex flex-col items-center flex-shrink-0">
                    {{-- Station Label --}}
                    <div class="mb-0.5 text-[8px] font-medium text-center text-gray-800 truncate" style="max-width: 45px;">
                        {{ $m->station->station_name ?? 'N/A' }}
                    </div>
                    
                    {{-- Station Icon --}}
                    <div class="relative">
                        <div class="w-8 h-8 bg-white rounded border border-gray-300 flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    
                    {{-- Status Badge --}}
                    <div class="mt-0.5 px-1.5 py-0.5 {{ $bgColor }} text-white text-[7px] rounded font-medium whitespace-nowrap">
                        {{ $statusText }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Right Arrow --}}
    <button id="scrollRightMethodIcon" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white shadow rounded p-0.5 hover:bg-gray-100">
        <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>

    
    {{-- ======================================================================= --}}
{{-- BAGIAN BAWAH: DETAIL HENKATEN (METODE) --}}
{{-- ======================================================================= --}}
<div class="border-t mt-2 pt-2">

    <div class="flex items-center gap-1">
        <button id="scrollLeftMethod" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        <div id="methodChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">
            @if($activeMethodHenkatens->isNotEmpty())
                <div class="flex justify-center gap-3 min-w-full px-2">
                    @foreach($activeMethodHenkatens as $henkaten)
                        @php
                            $startDate = strtoupper($henkaten->effective_date->format('j/M/y'));
                            $endDate = $henkaten->end_date ? strtoupper($henkaten->end_date->format('j/M/y')) : 'SELANJUTNYA';
                        @endphp

                        {{-- KOTAK UTAMA UNTUK SETIAP HENKATEN (METODE) --}}
                        <div class="method-card flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border border-gray-300 shadow-md cursor-pointer hover:bg-orange-50 transition transform hover:scale-[1.02]"
                            style="width: 240px;"
                            onclick="showMethodHenkatenDetail({{ $henkaten->id }})"
                            data-henkaten-id="{{ $henkaten->id }}"
                            data-keterangan="{{ $henkaten->keterangan }}"
                            data-keterangan-after="{{ $henkaten->keterangan_after }}"
                            data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                            data-shift="{{ $henkaten->shift }}"
                            data-line-area="{{ $henkaten->line_area }}"
                            data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y') : '-' }}"
                            data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y') : 'Selanjutnya' }}"
                            data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                            data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                            data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                            data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                            data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}"
                            >
                            
                            {{-- Perubahan Metode --}}
                            <div class="flex items-center justify-center space-x-2">
                                <div class="text-center">
                                    <p class="text-[8px] font-semibold truncate w-20" title="{{ $henkaten->keterangan }}">{{ $henkaten->keterangan }}</p>
                                    <div class="w-2 h-2 rounded-full bg-red-500 mx-auto mt-0.5" title="Before"></div>
                                </div>
                                <div class="text-sm text-gray-400 font-bold">→</div>
                                <div class="text-center">
                                    <p class="text-[8px] font-semibold truncate w-20" title="{{ $henkaten->keterangan_after }}">{{ $henkaten->keterangan_after }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5" title="After"></div>
                                </div>
                            </div>

                            {{-- Serial Number --}}
                            <div class="grid grid-cols-2 gap-1">
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[8px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? '-' }}</span>
                                </div>
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[8px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? '-' }}</span>
                                </div>
                            </div>

                            {{-- Periode Aktif --}}
                            <div class="flex justify-center">
                                <div class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-[9px] font-semibold">
                                    {{ $startDate }} - {{ $endDate }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-xs text-gray-400 py-4">No Active Henkaten</div>
            @endif
        </div>

        <button id="scrollRightMethod" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>
</div>

{{-- ============================================================= --}}
{{-- MODAL DETAIL HENKATEN (METODE) 
{{-- ============================================================= --}}
<div id="methodHenkatenDetailModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    
    {{-- 1. UKURAN DISESUAIKAN MENJADI max-w-3xl --}}
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100">

        {{-- 2. HEADER TETAP BIRU (TEMA METHOD) --}}
        <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten Metode</h3>
            <button onclick="closeMethodHenkatenModal()" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- 3. CONTENT MODAL DENGAN LAYOUT BARU --}}
        <div class="p-6 space-y-4"> {{-- Padding diubah ke p-6 agar seragam --}}
            
           {{-- PERUBAHAN METODE --}}
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Metode</h4>
                <div class="flex items-center justify-around">

                    {{-- Bagian "Sebelum" dimodifikasi --}}
                    <div class="text-center">
                        <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                        {{-- Data 'keterangan' dari tabel dimasukkan di sini --}}
                        <p id="modalKeteranganBefore" class="font-semibold text-sm mt-1">
                            {{ $data->keterangan ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="text-2xl text-gray-400">→</div>

                    {{-- Bagian "Sesudah" dimodifikasi --}}
                    <div class="text-center">
                        <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                        {{-- Data 'keterangan_after' dari tabel dimasukkan di sini --}}
                        <p id="modalKeteranganAfter" class="font-semibold text-sm mt-1">
                            {{ $data->keterangan_after ?? 'N/A' }}
                        </p>
                    </div>

                </div>
            </div>


            {{-- 4. INFORMASI DETAIL (LAYOUT GRID BARU 4 KOLOM) --}}
            <div class="space-y-3">
                {{-- Row 1: Station, Shift, Line Area, Keterangan --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Station</p>
                        <p id="modalStation" class="font-semibold text-sm truncate"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Shift</p>
                        <p id="modalShift" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Line Area</p>
                        <p id="modalLineArea" class="font-semibold text-sm"></p>
                    </div>
                    
                </div>

                {{-- Row 2: Serial Numbers & Times --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number Start</p>
                        <p id="modalSerialStart" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number End</p>
                        <p id="modalSerialEnd" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time Start</p>
                        <p id="modalTimeStart" class="font-semibold text-sm"></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time End</p>
                        <p id="modalTimeEnd" class="font-semibold text-sm"></p>
                    </div>
                </div>
            </div>

            {{-- 5. PERIODE (LAYOUT BARU SEPERTI MAN POWER) --}}
            <div class="flex justify-between items-center px-4 py-2">
                <div class="text-left">
                    <p class="text-xs text-gray-500">Mulai</p>
                    <p id="modalEffectiveDate" class="font-semibold text-lg"></p>
                </div>
                
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>

                <div class="text-right">
                    <p class="text-xs text-gray-500">Selesai</p>
                    <p id="modalEndDate" class="font-semibold text-lg"></p>
                </div>
            </div>


            {{-- LAMPIRAN --}}
            <div id="modalLampiranSection" class="hidden pt-2">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Lampiran</h4>
                <a id="modalLampiranLink" href="#" target="_blank"
                    class="block bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                    Lihat Lampiran
                </a>
            </div>
        </div>
    </div>
</div>
</div>

 {{-- ============================================= --}}
{{-- MACHINE SECTION --}}
{{-- ============================================= --}}
<div class="bg-white shadow rounded p-1 flex flex-col">
    <h2 class="text-xs font-semibold mb-2 text-center">MACHINE</h2>

    {{-- Machine Status Bar with Navigation --}}
    <div class="relative mt-2">
        {{-- Tombol Navigasi di Kiri --}}
        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollLeftMachine" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-blue-600 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        {{-- Machine Status Container --}}
        <div class="bg-white p-3 mx-8">
            <div class="flex justify-center items-center gap-4">
                @foreach ($machines as $mc)
                    @php $isHenkaten = ($mc->keterangan === 'HENKATEN'); @endphp
                    <div class="machine-status {{ $isHenkaten ? 'machine-inactive' : 'machine-active' }} flex flex-col items-center" 
                         style="width: 80px;"
                         onclick="toggleMachine(this)">
                        <div class="text-[9px] font-bold text-black mb-3 text-center">
                            {{ $mc->station->station_name ?? '-' }}
                        </div>
                        <div class="text-2xl mb-2">🏭</div>
                        <div class="text-[8px] font-bold px-2 py-1 rounded-full text-center whitespace-nowrap
                            {{ $isHenkaten ? 'bg-red-600 text-white' : 'bg-green-700 text-white' }}">
                            {{ $isHenkaten ? 'HENKATEN' : 'NORMAL' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tombol Navigasi di Kanan --}}
        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollRightMachine" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-blue-600 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
 {{-- MACHINE HENKATEN CARD SECTION --}}
    <div class="border-t mt-4 pt-4 overflow-x-auto scrollbar-hide">
        <div class="flex justify-center gap-3 p-2">
            @forelse($machineHenkatens as $henkaten)
                <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 shadow-md cursor-pointer hover:bg-gray-100 transition"
                     style="width: 220px;"
                     onclick="showHenkatenDetail({{ $henkaten->id }})"
                     data-henkaten-id="{{ $henkaten->id }}"
                     data-nama="Old Jig: {{ $henkaten->old_jig ?? 'N/A' }}"
                     data-nama-after="New Jig: {{ $henkaten->new_jig ?? 'N/A' }}"
                     data-station="{{ $henkaten->station->station_name ?? 'Machine Station' }}"
                     data-keterangan="{{ $henkaten->keterangan ?? 'Jig/Machine Change' }}"
                     data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y H:i') : '-' }}"
                     data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}">
                    <div class="flex items-center justify-center space-x-1.5">
                        <div class="text-center">
                            <div class="text-[8px] font-bold">OLD JIG</div>
                            <div class="text-xl my-0.5">⚙️</div>
                            <p class="text-[7px] font-semibold">{{ $henkaten->old_jig ?? 'N/A' }}</p>
                        </div>
                        <div class="text-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                        <div class="text-center">
                            <div class="text-[8px] font-bold text-red-600">NEW JIG</div>
                            <div class="text-xl my-0.5">⚙️</div>
                            <p class="text-[7px] font-semibold">{{ $henkaten->new_jig ?? 'N/A' }}</p>
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
                            ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-xs text-gray-400 py-4 w-full">No Active Machine Henkaten</div>
            @endforelse
        </div>
    </div>
</div>

{{-- ============================================= --}}
{{-- MATERIAL SECTION --}}
{{-- ============================================= --}}
<div class="bg-white shadow rounded p-1 flex flex-col mt-2">
    <h2 class="text-xs font-semibold mb-0.5 text-center">MATERIAL</h2>

    {{-- Material Table --}}
    <div class="relative flex-1">
        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollLeftMaterial" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-blue-600 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        <div id="materialTableContainer" class="mx-8 overflow-hidden">
            <table class="table-auto border-collapse border border-gray-300 w-full text-center text-[10px]">
                <thead>
                    <tr>
                        @foreach($stationStatuses as $station)
                            @php $isHenkaten = $station['status'] !== 'NORMAL'; @endphp
                            <th class="border border-gray-300 px-1 py-1 {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }} text-white text-[8px] font-semibold">
                                {{ $station['name'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach($stationStatuses as $station)
                            <td class="border border-gray-300 px-1 py-1">
                                <div class="material-status flex items-center justify-center bg-white text-gray-800 font-bold cursor-pointer" data-id="{{ $station['id'] }}"></div>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($stationStatuses as $station)
                            @php $isHenkaten = $station['status'] !== 'NORMAL'; @endphp
                            <td class="border border-gray-300 px-1 py-0.5 text-[8px] font-bold">
                                <div class="{{ $isHenkaten ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $station['status'] }}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollRightMaterial" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-blue-600 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- ============================================= --}}
{{--  MATERIAL HENKATEN CARD SECTION --}}
{{-- ============================================= --}}
<div class="border-t mt-2 pt-2">
    <div class="relative">
        {{-- Tombol Scroll Kiri --}}
        <button 
            onclick="scrollMaterialHenkaten('left')" 
           class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-700 rounded-full p-2 shadow-md border border-gray-200 transition"
            id="scrollLeftBtnMaterial">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Container Scroll --}}
        <div class="overflow-x-auto scrollbar-hide scroll-smooth" id="materialHenkatenContainer">
            <div class="flex justify-center gap-3 p-2">
                
                @if(isset($materialHenkatens) && $materialHenkatens->isNotEmpty())
                    @foreach($materialHenkatens as $henkaten)
                        <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition" 
                             style="width: 220px;"
                             onclick="showHenkatenDetail({{ $henkaten->id }})"
                             data-henkaten-id="{{ $henkaten->id }}"
                             data-nama="{{ $henkaten->material_name ?? 'N/A' }}"
                             data-nama-after="{{ $henkaten->material_after ?? 'N/A' }}"
                             data-station="{{ $henkaten->station->station_name ?? 'Material Station' }}"
                             data-shift="{{ $henkaten->shift ?? '-' }}"
                             data-keterangan="{{ $henkaten->keterangan ?? ($henkaten->material_after ?? 'Material Change') }}"
                             data-line-area="{{ $henkaten->line_area ?? '-' }}"
                             data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y H:i') : '-' }}"
                             data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}"
                             data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                             data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                             data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                              data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                            data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}"
                            >
                           {{-- 1. CURRENT & NEW PART (UPDATED) --}}
                            <div class="grid grid-cols-2 gap-1">
                                <div class="bg-white shadow rounded p-1 text-center">
                                    <h3 class="text-[8px] font-bold mb-0.5">CURRENT PART</h3>
                                    <p class="text-xs font-medium py-1">{{ $henkaten->material_name ?? 'N/A' }}</p>
                                </div>
                                <div class="bg-white shadow rounded p-1 text-center">
                                    <h3 class="text-[8px] font-bold mb-0.5 text-red-600">NEW PART</h3>
                                    <p class="text-xs font-medium py-1">{{ $henkaten->material_after ?? 'N/A' }}</p>
                                </div>
                            </div>
                            {{-- 2. SERIAL NUMBER --}}
                            <div class="grid grid-cols-2 gap-1">
                                <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? 'N/A' }}</span></div>
                                <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? 'N/A' }}</span></div>
                            </div>
                            {{-- 3. TANGGAL AKTIF --}}
                            <div class="flex justify-center">
                                <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                                    ACTIVE: {{ $henkaten->effective_date ? $henkaten->effective_date->format('j/M/y') : 'N/A' }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-xs text-gray-400 py-4 w-full">No Active Material Henkaten</div>
                @endif
            </div>
        </div>

        {{-- Tombol Scroll Kanan --}}
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

@push('scripts')
<script>
    
    /**
     * Update jam, tanggal, dan shift secara real-time.
     */
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', dateOptions);
        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');

        const hour = now.getHours();
        let shift;
        if (hour >= 7 && hour < 19) {
            shift = "Shift 2"; // 07:00 - 18:59
        } else {
            shift = "Shift 1"; // 19:00 - 06:59
        }
        document.getElementById("current-shift").textContent = shift;
    }

    // --- FUNGSI MODAL MAN POWER (DIPERBAIKI) ---
function showHenkatenDetail(henkatenId) {
    const card = document.querySelector(`[data-henkaten-id="${henkatenId}"]`);
    if (!card) {
        console.error('Card Man Power Henkaten tidak ditemukan!');
        return;
    }
    
    const data = card.dataset; // Ambil semua data- attributes

    // Mengisi bagian "Perubahan Pekerja"
    document.getElementById('modalNamaBefore').textContent = data.nama || '-';
    document.getElementById('modalNamaAfter').textContent = data.namaAfter || '-';

    
    // Mengisi bagian "Informasi Detail" (Grid)
    document.getElementById('modalStation').textContent = data.station || '-';
    document.getElementById('modalShift').textContent = data.shift || '-';
    document.getElementById('modalLineArea').textContent = data.lineArea || '-';
    document.getElementById('modalKeterangan').textContent = data.keterangan || '-';
    document.getElementById('modalSerialStart').textContent = data.serialNumberStart || '-';
    document.getElementById('modalSerialEnd').textContent = data.serialNumberEnd || '-';
    document.getElementById('modalTimeStart').textContent = data.timeStart || '-';
    document.getElementById('modalTimeEnd').textContent = data.timeEnd || '-'; 

    // Mengisi bagian "Periode"
    document.getElementById('modalEffectiveDate').textContent = data.effectiveDate || '-';
    document.getElementById('modalEndDate').textContent = data.endDate || 'Selanjutnya';
   

    // Mengurus Lampiran
    const lampiran = data.lampiran;
    const section = document.getElementById('modalLampiranSection');
    const link = document.getElementById('modalLampiranLink');
    
    if (lampiran) {
        section.classList.remove('hidden');
        link.href = lampiran;
    } else {
        section.classList.add('hidden');
    }

    // Tampilkan Modal
    document.getElementById('henkatenDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    }

   function closeHenkatenModal() {
        document.getElementById('henkatenDetailModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

   // --- FUNGSI MODAL METHOD (DIPERBAIKI) ---
function showMethodHenkatenDetail(henkatenId) {
    const card = document.querySelector(`.method-card[data-henkaten-id="${henkatenId}"]`);
    if (!card) {
        console.error('Elemen card Henkaten Metode tidak ditemukan untuk id:', henkatenId);
        return;
    }
    
    const modal = document.getElementById('methodHenkatenDetailModal');
    if (!modal) {
        console.error('Elemen modal Henkaten Metode tidak ditemukan');
        return;
    }

    const data = card.dataset; // Ambil semua data- attributes

   
    // Mengisi bagian "Informasi Detail" (Grid)
    modal.querySelector('#modalStation').textContent = data.station || '-';
    modal.querySelector('#modalShift').textContent = data.shift || '-';
    modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
    modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
    modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
    modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
    modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
    modal.querySelector('#modalKeteranganBefore').textContent = data.keterangan || '-';
    modal.querySelector('#modalKeteranganAfter').textContent = data.keteranganAfter || '-';

    // Mengisi bagian "Periode"
    modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
    modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya'; // Anda sudah punya ini
    
    // Mengurus Lampiran
    const lampiranSection = modal.querySelector('#modalLampiranSection');
    const lampiranLink = modal.querySelector('#modalLampiranLink');
    
    if (data.lampiran) {
        lampiranLink.href = data.lampiran;
        lampiranSection.classList.remove('hidden');
    } else {
        lampiranSection.classList.add('hidden');
    }

    // Tampilkan Modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

    function closeMethodHenkatenModal() {
        const modal = document.getElementById('methodHenkatenDetailModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = 'auto';
    }


    // ---  MODAL MATERIAL  ---
function showMaterialHenkatenDetail(henkatenId) {
    
    const card = document.querySelector(`.material-card[data-henkaten-id="${henkatenId}"]`); 
    if (!card) {
        console.error('Elemen card Henkaten Material tidak ditemukan untuk id:', henkatenId);
        return;
    }
    
  
    const modal = document.getElementById('materialHenkatenDetailModal'); 
    if (!modal) {
        console.error('Elemen modal Henkaten Material tidak ditemukan');
        return;
    }

    const data = card.dataset;

    // 3. Isi semua data (sesuaikan ID elemen di dalam modal jika perlu)
    modal.querySelector('#modalStation').textContent = data.station || '-';
    modal.querySelector('#modalShift').textContent = data.shift || '-';
    modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
    modal.querySelector('#modalKeterangan').textContent = data.keterangan || '-';
    modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
    modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
    modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
    modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
    modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
    modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';
    
    const lampiranSection = modal.querySelector('#modalLampiranSection');
    const lampiranLink = modal.querySelector('#modalLampiranLink');
    
    if (data.lampiran) {
        lampiranLink.href = data.lampiran;
        lampiranSection.classList.remove('hidden');
    } else {
        lampiranSection.classList.add('hidden');
    }

    // Tampilkan Modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}


function closeMaterialHenkatenModal() {
    const modal = document.getElementById('materialHenkatenDetailModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    document.body.style.overflow = 'auto';
}


document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHenkatenModal();
        closeMethodHenkatenModal();
        closeMaterialHenkatenModal(); 
    }
});
    // ==================================================
    // FUNGSI SCROLL UNTUK CARD HENKATEN (GLOBAL)
    // ==================================================
   
    function scrollMaterialHenkaten(direction) {
        const container = document.getElementById('materialHenkatenContainer');
        if (!container) return; 
        const scrollAmount = 240; 
        
        if (direction === 'left') {
            container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }

    

    // ==================================================
    // INISIALISASI SAAT HALAMAN DIMUAT (DOMContentLoaded)
    // ==================================================

    // Jalankan jam
    setInterval(updateDateTime, 1000);
    updateDateTime(); // Panggil sekali saat muat

    // Gabungkan semua listener dalam satu 'DOMContentLoaded'
    document.addEventListener('DOMContentLoaded', function() {

        /**
         * Fungsi scroll horizontal TERKONSOLIDASI (Untuk tabel-tabel).
         */
        function setupHorizontalScroll(containerId, leftBtnId, rightBtnId) {
            const scrollContainer = document.getElementById(containerId);
            const scrollLeftBtn = document.getElementById(leftBtnId);
            const scrollRightBtn = document.getElementById(rightBtnId);
            
            if (!scrollContainer || !scrollLeftBtn || !scrollRightBtn) {
                return;
            }
            const scrollAmount = 250; 
            scrollLeftBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
            function updateButtons() {
                const isAtStart = scrollContainer.scrollLeft <= 0;
                const isAtEnd = scrollContainer.scrollLeft + scrollContainer.clientWidth >= scrollContainer.scrollWidth - 1;
                scrollLeftBtn.style.visibility = isAtStart ? 'hidden' : 'visible';
                scrollRightBtn.style.visibility = isAtEnd ? 'hidden' : 'visible';
            }
            scrollContainer.addEventListener('scroll', updateButtons);
            updateButtons(); 
            scrollContainer.scrollLeft = scrollContainer.scrollWidth;
        }

        // Inisialisasi SEMUA bagian scroll tabel
        setupHorizontalScroll('materialTableContainer', 'scrollLeftMaterial', 'scrollRightMaterial');
        setupHorizontalScroll('manPowerTableContainer', 'scrollLeftManPower', 'scrollRightManPower');
        setupHorizontalScroll('machineTableContainer', 'scrollLeftMachine', 'scrollRightMachine');
        setupHorizontalScroll('shiftChangeContainer', 'scrollLeftShift', 'scrollRightShift');
        setupHorizontalScroll('methodChangeContainer', 'scrollLeftMethod', 'scrollRightMethod');
        setupHorizontalScroll('methodScrollContainer', 'scrollLeftMethodIcon', 'scrollRightMethodIcon');


        // ================================================================
        // INISIALISASI SCROLL UNTUK CARD (BAGIAN YANG DIPERBAIKI)
        // ================================================================
        
        
        const container = document.getElementById('materialHenkatenContainer');
        const leftBtn = document.getElementById('scrollLeftBtnMaterial');
        const rightBtn = document.getElementById('scrollRightBtnMaterial');

        if (container && leftBtn && rightBtn) {
            
            function checkScrollButtons() {
                const isAtStart = container.scrollLeft <= 0;
                const isAtEnd = container.scrollLeft >= (container.scrollWidth - container.clientWidth - 1); 

                leftBtn.style.display = isAtStart ? 'none' : 'block';
                rightBtn.style.display = isAtEnd ? 'none' : 'block';
            }

            // Pasang event listener-nya di sini
            container.addEventListener('scroll', checkScrollButtons);

            // Atur visibilitas awal
            setTimeout(checkScrollButtons, 100); 
        }

        // ================================================================
        // INISIALISASI SCROLL BUTTONS UNTUK METHOD ICON VIEW
        // ================================================================
        const methodContainer = document.getElementById('methodScrollContainer');
        const methodLeftBtn = document.querySelector('[onclick="scrollMethodLeft()"]');
        const methodRightBtn = document.querySelector('[onclick="scrollMethodRight()"]');

        if (methodContainer && methodLeftBtn && methodRightBtn) {
            function checkMethodScrollButtons() {
                const isAtStart = methodContainer.scrollLeft <= 0;
                const isAtEnd = methodContainer.scrollLeft >= (methodContainer.scrollWidth - methodContainer.clientWidth - 1);

                methodLeftBtn.style.visibility = isAtStart ? 'hidden' : 'visible';
                methodRightBtn.style.visibility = isAtEnd ? 'hidden' : 'visible';
            }

            methodContainer.addEventListener('scroll', checkMethodScrollButtons);
            setTimeout(checkMethodScrollButtons, 100);
        }
        
        // --- Event Listener untuk Modal Man Power ---
        const manPowerModal = document.getElementById('henkatenDetailModal');
        if (manPowerModal) {
            manPowerModal.addEventListener('click', function(e) {
                if (e.target === this) closeHenkatenModal();
            });
        }
        
        // --- Event Listener untuk Modal Method ---
        const methodModal = document.getElementById('methodHenkatenDetailModal');
        if (methodModal) {
            methodModal.addEventListener('click', function(e) {
                if (e.target === this) closeMethodHenkatenModal();
            });
        }

        // --- Event Listener Global untuk Tombol 'Escape' ---
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHenkatenModal();
                closeMethodHenkatenModal(); // Tutup kedua modal
            }
        });

    
    });
</script>
@endpush

</x-app-layout>