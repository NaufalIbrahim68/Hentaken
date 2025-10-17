
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
        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollLeftManPower" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        <div id="manPowerTableContainer" class="mx-8 overflow-x-auto scrollbar-hide scroll-smooth">
            <div class="flex gap-6 py-2">
                @foreach($groupedManPower as $stationId => $stationWorkers)
                 @php
    // Cari pekerja untuk shift saat ini
    $currentWorker = $stationWorkers->where('shift', $currentShift)->first();
    
    // Jika tidak ada pekerja di shift ini untuk stasiun ini, lewati
    if (!$currentWorker) continue;

    // ==========================================================
    // PERBAIKAN: Gunakan status yang sudah dihitung di Controller
    // Tidak perlu query database baru
    // ==========================================================
    $isHenkaten = ($currentWorker->status == 'Henkaten'); 
    
    $displayName = $currentWorker->nama; 
    $statusText = $isHenkaten ? 'HENKATEN' : 'NORMAL';
    $statusColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
    $stationCode = $currentWorker->station ? $currentWorker->station->station_code : 'ST-' . $stationId;
@endphp

                    <div class="flex-shrink-0 text-center" style="min-width: 80px;">
                        <p class="text-[10px] font-bold text-gray-800 mb-1">{{ $stationCode }}</p>

                        <div class="relative mx-auto mb-2 w-8 h-8">
                            <div class="w-full h-full rounded-full bg-purple-600 flex items-center justify-center text-white text-sm font-bold">
                                👤
                            </div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 rounded-full {{ $statusColor }} border-2 border-white"></div>
                        </div>

                        <p class="text-[10px] font-medium mb-1 truncate px-1" title="{{ $displayName }}">{{ $displayName }}</p>

                        <div>
                            <span class="inline-block px-2 py-1 text-[9px] font-semibold rounded text-white {{ $statusColor }}">
                                {{ $statusText }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
            <button id="scrollRightManPower" class="w-6 h-6 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            @if($activeManPowerHenkatens->isNotEmpty())
                    <div class="flex justify-center gap-3 min-w-full px-2">
                    @foreach($activeManPowerHenkatens as $henkaten)
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
        <div class="text-center text-xs text-gray-400 py-4">No Active Henkaten</div>
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
                {{-- DIUBAH: Grid diubah dari 2 kolom (grid-cols-2) menjadi 4 kolom (grid-cols-4) --}}
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

      {{-- METHOD - . --}}
<div class="bg-white shadow rounded p-1 flex flex-col">
    <h2 class="text-xs font-semibold mb-0.5 text-center">METHOD</h2>

    {{-- Table Wrapper Scroll - . --}}
    <div class="overflow-auto flex-1">
        <table class="w-full border-collapse text-[10px]">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-0.5 py-0.5 text-left">Station</th>
                    <th class="border px-0.5 py-0.5 text-center">Keterangan</th>
                    <th class="border px-0.5 py-0.5 text-center">Lampiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($methods as $m)
                    <tr>
                        <td class="border px-0.5 py-0.5">{{ $m->station->station_name ?? '-' }}</td>
                        <td class="border px-0.5 py-0.5 text-center">{{ $m->keterangan ?? '-' }}</td>
                        <td class="border px-0.5 py-0.5 text-center">{{ $m->foto_path ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination Compact --}}
    <div class="mt-2 flex justify-end">
        {{ $methods->onEachSide(1)->links('vendor.pagination.compact') }}
    </div>

    {{-- ============================================= --}}
    {{-- METHOD HENKATEN CARD SECTION (UPDATED) --}}
    {{-- ============================================= --}}
    <div class="border-t mt-2 pt-2 overflow-x-auto scrollbar-hide">
        <div class="flex justify-center gap-3 p-2">
            {{-- This should be inside a loop like: @foreach($methodHenkatens as $henkaten) --}}
            @if(isset($methodHenkatens) && $methodHenkatens->isNotEmpty())
                @foreach($methodHenkatens as $henkaten)
                    {{-- KOTAK UTAMA UNTUK SETIAP HENKATEN --}}
                    <div class="flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition" 
                         style="width: 240px;"
                         {{-- LOGIC ADDED: This now calls the Man Power modal function --}}
                         onclick="showHenkatenDetail({{ $henkaten->id }})"
                         data-henkaten-id="{{ $henkaten->id }}"
                         {{-- NOTE: These data attributes are placeholders. You will need to map your Method Henkaten data to them. --}}
                         data-nama="Method Change"
                         data-nama-after="{{ $henkaten->new_method ?? 'N/A' }}"
                         data-station="{{ $henkaten->new_station ?? 'N/A' }}"
                         data-shift="-"
                         data-keterangan="{{ $henkaten->keterangan ?? 'Method Details' }}"
                         data-line-area="-"
                         data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y H:i') : '-' }}"
                         data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}"
                         data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                         data-serial-number-start="{{ $henkaten->serial_start ?? '-' }}"
                         data-serial-number-end="{{ $henkaten->serial_end ?? '-' }}"
                         data-time-start="-"
                         data-time-end="-">

                        {{-- 1. CURRENT & NEW METHOD --}}
                        <div class="grid grid-cols-2 gap-1">
                            {{-- CURRENT --}}
                            <div class="bg-white shadow rounded p-1 text-center">
                                <h3 class="text-[9px] font-bold mb-0.5">CURRENT METHOD</h3>
                                <p class="text-[7px]"><span class="font-semibold">STATION :</span> {{ $henkaten->current_station ?? 'N/A' }}</p>
                                <p class="text-[7px]"><span class="font-semibold">METHOD :</span> {{ $henkaten->current_method ?? 'N/A' }}</p>
                            </div>
                            {{-- NEW --}}
                            <div class="bg-white shadow rounded p-1 text-center">
                                <h3 class="text-[9px] font-bold mb-0.5 text-red-600">NEW METHOD</h3>
                                <p class="text-[7px]"><span class="font-semibold">STATION :</span> {{ $henkaten->new_station ?? 'N/A' }}</p>
                                <p class="text-[7px]"><span class="font-semibold">METHOD :</span> {{ $henkaten->new_method ?? 'N/A' }}</p>
                            </div>
                        </div>

                        {{-- 2. SERIAL NUMBER --}}
                        <div class="grid grid-cols-2 gap-1">
                            <div class="bg-blue-400 text-center py-0.5 rounded">
                                <span class="text-[8px] text-white font-medium">Start: {{ $henkaten->serial_start ?? 'N/A' }}</span>
                            </div>
                            <div class="bg-blue-400 text-center py-0.5 rounded">
                                <span class="text-[8px] text-white font-medium">End: {{ $henkaten->serial_end ?? 'N/A' }}</span>
                            </div>
                        </div>

                        {{-- 3. TANGGAL AKTIF --}}
                        <div class="flex justify-center">
                            <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                                ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center text-xs text-gray-400 py-4">No Active Method Henkaten</div>
            @endif
        </div>
    </div>
</div>

        {{-- MACHINE - --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">MACHINE</h2>

            {{-- Machine Status Bar with Navigation - . --}}
            <div class="relative">
                {{-- Tombol Navigasi di Kiri - . --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollLeftMachine" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
                {{-- Machine Status Container - . --}}
                <div class="bg-white p-2 mx-8">
                    <div class="flex justify-center items-center space-x-1">
                        @foreach ($machines as $mc)
                            @php
                                $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            @endphp
                            <div class="machine-status {{ $isHenkaten ? 'machine-inactive' : 'machine-active' }}" onclick="toggleMachine(this)">
                                {{-- Station ID - . --}}
                                <div class="station-id text-[8px] font-bold text-black mb-0.5">
                                    ST {{ $mc->station_id }}
                                </div>

                                {{-- Ikon mesin - . --}}
                                <div style="font-size: 16px;">🏭</div>

                                {{-- Status Text - . --}}
                                <div class="status-text text-[7px] font-bold mt-0.5 px-0.5 py-0.5 rounded-full 
                                    {{ $isHenkaten ? 'bg-red-600 text-white ' : 'bg-green-700 text-white' }}">
                                    {{ $isHenkaten ? 'HENKATEN' : 'NORMAL' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tombol Navigasi di Kanan - . --}}
                <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollRightMachine" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

        {{-- ============================================= --}}
{{-- MACHINE HENKATEN CARD SECTION (UPDATED) --}}
{{-- ============================================= --}}
<div class="border-t mt-2 pt-2 overflow-x-auto scrollbar-hide">
    <div class="flex justify-center gap-3 p-2">
        
        {{-- ======================================================== --}}
        {{-- LOGIKA IF/ELSE DIHAPUS, HANYA MENYISAKAN FOREACH --}}
        {{-- ======================================================== --}}
        @foreach($machineHenkatens as $henkaten)
            <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition" 
                 style="width: 220px;"
                 {{-- Logika ini tetap berfungsi karena loop hanya berjalan jika ada data --}}
                 onclick="showHenkatenDetail({{ $henkaten->id }})"
                 data-henkaten-id="{{ $henkaten->id }}"
                 {{-- Data ini akan mengambil dari Man Power Henkaten sesuai controller --}}
                 data-nama="Old Jig: {{ $henkaten->old_jig ?? 'N/A' }}"
                 data-nama-after="New Jig: {{ $henkaten->new_jig ?? 'N/A' }}"
                 data-station="{{ $henkaten->station->station_name ?? 'Machine Station' }}"
                 data-shift="-"
                 data-keterangan="{{ $henkaten->keterangan ?? 'Jig/Machine Change' }}"
                 data-line-area="-"
                 data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y H:i') : '-' }}"
                 data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}"
                 data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                 data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                 data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                 data-time-start="-"
                 data-time-end="-">
                
                {{-- 1. JIG CHANGE --}}
                <div class="flex items-center justify-center space-x-1.5">
                    <div class="text-center">
                        <div class="text-[8px] font-bold">OLD JIG</div>
                        <div class="text-xl my-0.5">⚙️</div>
                        {{-- Karena data dari Man Power Henkaten, ini akan tampil N/A --}}
                        <p class="text-[7px] font-semibold">{{ $henkaten->old_jig ?? 'N/A' }}</p>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </div>
                    <div class="text-center">
                        <div class="text-[8px] font-bold text-red-600">NEW JIG</div>
                        <div class="text-xl my-0.5">⚙️</div>
                        {{-- Karena data dari Man Power Henkaten, ini akan tampil N/A --}}
                        <p class="text-[7px] font-semibold">{{ $henkaten->new_jig ?? 'N/A' }}</p>
                    </div>
                </div>
                {{-- 2. SERIAL NUMBER --}}
                <div class="grid grid-cols-2 gap-1">
                    {{-- Ini akan menampilkan serial number dari Man Power Henkaten --}}
                    <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? 'N/A' }}</span></div>
                    <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? 'N/A' }}</span></div>
                </div>
                {{-- 3. TANGGAL AKTIF --}}
                <div class="flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                        ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>
        </div>

        {{-- MATERIAL - . --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">MATERIAL</h2>

            <div class="relative flex-1">
                {{-- Tombol Navigasi di Kiri - . --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollLeftMaterial" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>

                {{-- Material Table Container - . --}}
                <div id="materialTableContainer" class="mx-8 overflow-hidden">
                    <table class="table-auto border-collapse border border-gray-300 w-full text-center text-[10px]">
                        <thead>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <th class="border border-gray-300 px-1 py-1 bg-green-600 text-white text-[8px] font-semibold">
                                        {{ $station['name'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <td class="border border-gray-300 px-1 py-1">
                                        <div class="material-status flex items-center justify-center bg-white text-gray-800 font-bold cursor-pointer" data-id="{{ $station['id'] }}">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <td class="border border-gray-300 px-1 py-0.5 text-[8px] font-bold">
                                        <div class="status-text text-green-600">
                                            {{ $station['status'] }}
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Tombol Navigasi di Kanan - . --}}
                <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollRightMaterial" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

          {{-- ============================================= --}}
    {{-- MATERIAL HENKATEN CARD SECTION (UPDATED) --}}
    {{-- ============================================= --}}
    <div class="border-t mt-2 pt-2 overflow-x-auto scrollbar-hide">
        <div class="flex justify-center gap-3 p-2">
            {{-- This should be inside a loop like: @foreach($materialHenkatens as $henkaten) --}}
            @if(isset($materialHenkatens) && $materialHenkatens->isNotEmpty())
                @foreach($materialHenkatens as $henkaten)
                    <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition" 
                         style="width: 220px;"
                         {{-- LOGIC ADDED: This now calls the Man Power modal function --}}
                         onclick="showHenkatenDetail({{ $henkaten->id }})"
                         data-henkaten-id="{{ $henkaten->id }}"
                         {{-- NOTE: These data attributes are placeholders. --}}
                         data-nama="Part No: {{ $henkaten->current_part_no ?? 'N/A' }}"
                         data-nama-after="Part No: {{ $henkaten->new_part_no ?? 'N/A' }}"
                         data-station="{{ $henkaten->station->station_name ?? 'Material Station' }}"
                         data-shift="-"
                         data-keterangan="{{ $henkaten->new_part_desc ?? 'Material Change' }}"
                         data-line-area="-"
                         data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y H:i') : '-' }}"
                         data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}"
                         data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                         data-serial-number-start="{{ $henkaten->serial_start ?? '-' }}"
                         data-serial-number-end="{{ $henkaten->serial_end ?? '-' }}"
                         data-time-start="-"
                         data-time-end="-">
                         
                        {{-- 1. CURRENT & NEW PART --}}
                        <div class="grid grid-cols-2 gap-1">
                            <div class="bg-white shadow rounded p-1 text-center">
                                <h3 class="text-[8px] font-bold mb-0.5">CURRENT PART</h3>
                                <p class="text-[7px]"><span class="font-semibold">PART NO:</span> {{ $henkaten->current_part_no ?? 'N/A' }}</p>
                                <p class="text-[7px]"><span class="font-semibold">DESC:</span> {{ $henkaten->current_part_desc ?? 'N/A' }}</p>
                            </div>
                            <div class="bg-white shadow rounded p-1 text-center">
                                <h3 class="text-[8px] font-bold mb-0.5 text-red-600">NEW PART</h3>
                                <p class="text-[7px]"><span class="font-semibold">PART NO:</span> {{ $henkaten->new_part_no ?? 'N/A' }}</p>
                                <p class="text-[7px]"><span class="font-semibold">DESC:</span> {{ $henkaten->new_part_desc ?? 'N/A' }}</p>
                            </div>
                        </div>
                        {{-- 2. SERIAL NUMBER --}}
                        <div class="grid grid-cols-2 gap-1">
                            <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">Start: {{ $henkaten->serial_start ?? 'N/A' }}</span></div>
                            <div class="bg-blue-400 text-center py-0.5 rounded"><span class="text-[7px] text-white font-medium">End: {{ $henkaten->serial_end ?? 'N/A' }}</span></div>
                        </div>
                        {{-- 3. TANGGAL AKTIF --}}
                        <div class="flex justify-center">
                            <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                                ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center text-xs text-gray-400 py-4">No Active Material Henkaten</div>
            @endif
        </div>
    </div>
</div>



                  @push('scripts')
                <script>
                    // Real-time Clock
                    function updateDateTime() {
                        const now = new Date();

                        // Format tanggal
                        const options = { day: '2-digit', month: 'long', year: 'numeric' };
                        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', options);

                        // Format waktu
                        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');

                        // Tentukan shift
                        const hour = now.getHours();
                        let shift;

                        if (hour >= 7 && hour < 19) {
                            shift = "Shift 2"; // 07:00 - 18:59
                        } else {
                            shift = "Shift 1"; // 19:00 - 06:59
                        }

                        document.getElementById("current-shift").textContent = shift;
                    }

                    // Update tiap detik
                    setInterval(updateDateTime, 1000);
                    updateDateTime();


                    function toggleMachine(element) {
                        const statusText = element.querySelector('.status-text');
                        if (element.classList.contains('machine-active')) {
                            element.classList.remove('machine-active');
                            element.classList.add('machine-inactive');
                            statusText.innerText = 'HENKATEN';
                            statusText.className = "status-text text-[10px] font-bold mt-1 px-1 py-0.5 rounded-full bg-red-600 text-white";
                        } else {
                            element.classList.remove('machine-inactive');
                            element.classList.add('machine-active');
                            statusText.innerText = 'NORMAL';
                            statusText.className = "status-text text-[10px] font-bold mt-1 px-1 py-0.5 rounded-full bg-green-700 text-white";
                        }
                    }

                    // Auto-update machine status (simulate real-time updates)
                    function updateMachineStatus() {
                        const machines = document.querySelectorAll('.machine-status');
                        machines.forEach((machine) => {
                            if (Math.random() > 0.95) { // 5% chance to change status
                                const statusText = machine.querySelector('.status-text');

                                if (machine.classList.contains('machine-active')) {
                                    machine.classList.remove('machine-active');
                                    machine.classList.add('machine-inactive');
                                    statusText.innerText = 'HENKATEN';
                                    statusText.classList.add('status-henkaten');
                                } else {
                                    machine.classList.remove('machine-inactive');
                                    machine.classList.add('machine-active');
                                    statusText.innerText = 'NORMAL';
                                    statusText.classList.remove('status-henkaten');
                                }
                            }
                        });
                    }

                    // logic materials
                 document.addEventListener('DOMContentLoaded', () => {
    const materials = document.querySelectorAll('.material-status');

    materials.forEach((material) => {
        material.addEventListener('click', () => {
            const tdMaterial = material.closest('td'); // td untuk kotak
            const tdStatus = tdMaterial.parentElement.nextElementSibling?.children[tdMaterial.cellIndex]; // td baris bawah
            const statusText = tdStatus?.querySelector('.status-text');

            // === Jika NORMAL → ubah jadi HENKATEN ===
            if (!material.classList.contains('bg-red-500')) {
                // Ubah box
                material.classList.remove('bg-white');
                  material.classList.add('bg-red-500', 'text-white');
                material.innerText = 'HENKATEN';

                // Ubah status text
                if (statusText) {
                    statusText.innerText = 'HENKATEN';
                    statusText.classList.remove('text-white');
                    statusText.classList.add('text-white');
                }

                // Warnai seluruh cell merah
                tdMaterial.classList.add('bg-red-500', 'text-white');
                tdStatus.classList.add('bg-red-500', 'text-white');
            } 
            // === Jika sudah HENKATEN → balik NORMAL ===
            else {
                // Ubah box
                material.classList.remove('bg-red-500', 'text-white');
                material.classList.add('bg-white');
                material.innerText = '';

                // Ubah status text
                if (statusText) {
                    statusText.innerText = 'NORMAL';
                    statusText.classList.remove('text-white');
                    statusText.classList.add('text-green-500');
                }

                // Kembalikan cell ke default
                tdMaterial.classList.remove('bg-red-500', 'text-white');
                tdStatus.classList.remove('bg-red-500', 'text-white');
            }
        });
    });
});

                  document.addEventListener("DOMContentLoaded", function () {
    function setupScroll(containerId, leftBtnId, rightBtnId) {
        const container = document.getElementById(containerId);
        const btnLeft = document.getElementById(leftBtnId);
        const btnRight = document.getElementById(rightBtnId);

        if (!container || !btnLeft || !btnRight) return;

        btnLeft.addEventListener("click", () => {
            container.scrollBy({ left: -200, behavior: "smooth" });
        });

        btnRight.addEventListener("click", () => {
            container.scrollBy({ left: 200, behavior: "smooth" });
        });
    }

    // Setup scroll untuk semua section
    setupScroll("materialTableContainer", "scrollLeftMaterial", "scrollRightMaterial");
    setupScroll("manPowerTableContainer", "scrollLeftManPower", "scrollRightManPower");
    setupScroll("machineTableContainer", "scrollLeftMachine", "scrollRightMachine");
});


// Man Power
document.addEventListener('DOMContentLoaded', function() {
        
        /**
         * Fungsi untuk menginisialisasi scroll horizontal pada sebuah elemen.
         * @param {string} containerId - ID dari elemen container yang bisa di-scroll.
         * @param {string} leftBtnId - ID dari tombol scroll ke kiri.
         * @param {string} rightBtnId - ID dari tombol scroll ke kanan.
         */
        function initializeHorizontalScroll(containerId, leftBtnId, rightBtnId) {
            const scrollContainer = document.getElementById(containerId);
            const scrollLeftBtn = document.getElementById(leftBtnId);
            const scrollRightBtn = document.getElementById(rightBtnId);
            
            // Hentikan eksekusi jika salah satu elemen tidak ditemukan
            if (!scrollContainer || !scrollLeftBtn || !scrollRightBtn) {
                // console.error('One or more elements not found for scrollable section:', containerId);
                return;
            }

            const scrollAmount = 250; // Jarak scroll dalam piksel

            scrollLeftBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            });

            scrollRightBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            });

            // Fungsi untuk menyembunyikan/menampilkan tombol berdasarkan posisi scroll
            function updateButtons() {
                // Sedikit toleransi (misal 1px) untuk perhitungan yang lebih akurat
                const isAtStart = scrollContainer.scrollLeft <= 0;
                const isAtEnd = scrollContainer.scrollLeft + scrollContainer.clientWidth >= scrollContainer.scrollWidth - 1;

                scrollLeftBtn.style.visibility = isAtStart ? 'hidden' : 'visible';
                scrollRightBtn.style.visibility = isAtEnd ? 'hidden' : 'visible';
            }

            // Panggil fungsi saat ada event scroll dan saat halaman pertama kali dimuat
            scrollContainer.addEventListener('scroll', updateButtons);
            updateButtons(); // Pengecekan awal
        }

        // Inisialisasi untuk bagian ATAS (Man Power List)
        initializeHorizontalScroll('manPowerTableContainer', 'scrollLeftManPower', 'scrollRightManPower');

        // Inisialisasi untuk bagian BAWAH (Shift / Henkaten Details)
        initializeHorizontalScroll('shiftChangeContainer', 'scrollLeftShift', 'scrollRightShift');

    });

    // Detail Henkaten Man power

    function showHenkatenDetail(henkatenId) {
    const card = document.querySelector(`[data-henkaten-id="${henkatenId}"]`);
    if (!card) return;

    document.getElementById('modalNamaBefore').textContent = card.dataset.nama;
    document.getElementById('modalNamaAfter').textContent = card.dataset.namaAfter;
    document.getElementById('modalStation').textContent = card.dataset.station;
    document.getElementById('modalShift').textContent = 'Shift ' + card.dataset.shift;
    document.getElementById('modalLineArea').textContent = card.dataset.lineArea;
    document.getElementById('modalKeterangan').textContent = card.dataset.keterangan;
    document.getElementById('modalEffectiveDate').textContent = card.dataset.effectiveDate;
    document.getElementById('modalEndDate').textContent = card.dataset.endDate;
    document.getElementById('modalSerialStart').textContent = card.dataset.serialNumberStart || '-';
    document.getElementById('modalSerialEnd').textContent = card.dataset.serialNumberEnd || '-';
    document.getElementById('modalTimeStart').textContent = card.dataset.timeStart || '-';
    document.getElementById('modalTimeEnd').textContent = card.dataset.timeEnd || '-';

    const lampiran = card.dataset.lampiran;
    const section = document.getElementById('modalLampiranSection');
    const link = document.getElementById('modalLampiranLink');

    if (lampiran) {
        section.classList.remove('hidden');
        link.href = lampiran;
    } else {
        section.classList.add('hidden');
    }

    document.getElementById('henkatenDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeHenkatenModal() {
    document.getElementById('henkatenDetailModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

document.getElementById('henkatenDetailModal').addEventListener('click', function(e) {
    if (e.target === this) closeHenkatenModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeHenkatenModal();
});
</script>



  @endpush
</x-app-layout>