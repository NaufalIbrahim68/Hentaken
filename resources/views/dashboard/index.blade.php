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

    // Cek apakah pekerja saat ini memiliki Henkaten yang aktif
    $activeHenkaten = \App\Models\ManPowerHenkaten::where('man_power_id', $currentWorker->id)
        ->where('effective_date', '<=', now())
        ->where(function($q) {
            $q->where('end_date', '>=', now())->orWhereNull('end_date');
        })
        ->latest('effective_date')
        ->first();

    $isHenkaten = (bool)$activeHenkaten;
    
    // ==========================================================
    // PERUBAHAN DI SINI: Selalu tampilkan nama pekerja asli
    // ==========================================================
    $displayName = $currentWorker->nama; 
    
    // Status dan warna tetap ditentukan oleh adanya henkaten
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
    {{-- BAGIAN BAWAH: DETAIL HENKATEN (REVISI - SATU KOTAK PER HENKATEN) --}}
    {{-- ======================================================================= --}}
    <div class="border-t mt-2 pt-2">
        @php
            // Ambil SEMUA data henkaten yang sedang aktif
            $allActiveHenkatens = \App\Models\ManPowerHenkaten::where('effective_date', '<=', now())
                ->where(function($query) {
                    $query->where('end_date', '>=', now())->orWhereNull('end_date');
                })
                ->get();
        @endphp

        <div class="flex items-center gap-1">
            <button id="scrollLeftShift" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <div id="shiftChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">
                @if($allActiveHenkatens->isNotEmpty())
                    <div class="flex justify-center gap-3 min-w-full px-2">
                        {{-- Loop untuk setiap Henkaten yang aktif --}}
                        @foreach($allActiveHenkatens as $henkaten)
                            @php
                                // Kalkulasi tanggal untuk setiap henkaten di dalam loop
                                $startDate = strtoupper($henkaten->effective_date->format('j/M/y'));
                                $endDate = $henkaten->end_date ? strtoupper($henkaten->end_date->format('j/M/y')) : 'SELANJUTNYA';
                            @endphp

                            {{-- KOTAK UTAMA UNTUK SETIAP HENKATEN --}}
                            <div class="flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition" 
                                 style="width: 240px;"
                                 onclick="showHenkatenDetail({{ $henkaten->id }})"
                                 data-henkaten-id="{{ $henkaten->id }}"
                                 data-nama="{{ $henkaten->nama }}"
                                 data-nama-after="{{ $henkaten->nama_after }}"
                                 data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                                 data-shift="{{ $henkaten->shift }}"
                                 data-keterangan="{{ $henkaten->keterangan }}"
                                 data-line-area="{{ $henkaten->line_area }}"
                                 data-effective-date="{{ $henkaten->effective_date->format('d/m/Y H:i') }}"
                                 data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y H:i') : 'Selanjutnya' }}"
                                 data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}">

                                {{-- Perubahan Pekerja (Before -> After) --}}
                                <div class="flex items-center justify-center space-x-2">
                                    {{-- Kiri (Pekerja SEBELUM Henkaten) --}}
                                    <div class="text-center">
                                        <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">👤</div>
                                        <p class="text-[8px] font-semibold" title="{{ $henkaten->nama }}">{{ $henkaten->nama }}</p>
                                        <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5" title="Before"></div>
                                    </div>
                                    <div class="text-sm text-gray-400 font-bold">→</div>
                                    {{-- Kanan (Pekerja SETELAH Henkaten / Pengganti) --}}
                                    <div class="text-center">
                                        <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">👤</div>
                                        <p class="text-[8px] font-semibold" title="{{ $henkaten->nama_after }}">{{ $henkaten->nama_after }}</p>
                                        <div class="w-2 h-2 rounded-full bg-red-500 mx-auto mt-0.5" title="Henkaten"></div>
                                    </div>
                                </div>

                                {{-- 2. Serial Number --}}
                                <div class="grid grid-cols-2 gap-1">
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">Serial Start: K1ZVNA2018QX</span>
                                    </div>
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">Serial End: K1ZVNA2020QX</span>
                                    </div>
                                </div>

                                {{-- 3. Tanggal Aktif --}}
                                <div class="flex justify-center">
                                    <div class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-[10px] font-semibold">
                                        ACTIVE: {{ $startDate }} - {{ $endDate }}
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
</div>

{{-- MODAL DETAIL HENKATEN --}}
<div id="henkatenDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white">Detail Henkaten</h3>
            <button onclick="closeHenkatenModal()" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6">
            {{-- Perubahan Pekerja --}}
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Perubahan Pekerja
                </h4>
                <div class="flex items-center justify-around bg-gray-50 p-4 rounded-lg">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl mx-auto mb-2">👤</div>
                        <p class="font-semibold text-sm mb-1" id="modalNamaBefore"></p>
                        <span class="inline-block px-2 py-1 text-xs rounded bg-green-500 text-white">SEBELUM</span>
                    </div>
                    <div class="text-3xl text-gray-400">→</div>
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl mx-auto mb-2">👤</div>
                        <p class="font-semibold text-sm mb-1" id="modalNamaAfter"></p>
                        <span class="inline-block px-2 py-1 text-xs rounded bg-red-500 text-white">HENKATEN</span>
                    </div>
                </div>
            </div>

            {{-- Informasi Detail --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Station</p>
                    <p class="font-semibold text-sm" id="modalStation"></p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Shift</p>
                    <p class="font-semibold text-sm" id="modalShift"></p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Line Area</p>
                    <p class="font-semibold text-sm" id="modalLineArea"></p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Keterangan</p>
                    <p class="font-semibold text-sm" id="modalKeterangan"></p>
                </div>
            </div>

            {{-- Period Aktif --}}
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Periode Aktif
                </h4>
                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-600">Mulai</p>
                            <p class="font-semibold" id="modalEffectiveDate"></p>
                        </div>
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-600">Selesai</p>
                            <p class="font-semibold" id="modalEndDate"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lampiran --}}
            <div id="modalLampiranSection" class="mb-4 hidden">
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                    Lampiran
                </h4>
                <a id="modalLampiranLink" href="#" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Lihat Lampiran
                </a>
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



            {{-- Bottom sections untuk Method - . --}}
            <div class="mt-1">
                {{-- CURRENT PART & NEW PARTKT - . --}}
                <div class="grid grid-cols-2 gap-1">
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5">CURRENT METHOD</h3>
                        <p class="text-[7px]"><span class="font-semibold">STATION :</span> STATION 5</p>
                        <p class="text-[7px]"><span class="font-semibold">METHOD :</span> INNERCASE ASSY</p>
                    </div>
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5 text-red-600">NEW METHOD</h3>
                        <p class="text-[7px]"><span class="font-semibold">STATION :</span> STATION 5</p>
                        <p class="text-[7px]"><span class="font-semibold">METHOD :</span> INNERCASE ASSY</p>
                    </div>
                </div>

                {{-- SERIAL NUMBER & DATE - . --}}
                               <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>


                {{-- Tanggal Aktif - . --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
        </div>

        {{-- MACHINE - . --}}
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

            {{-- Bottom Section - Jig Change - . --}}
            <div class="flex p-2 bg-white-100 mt-2">
                <div class="flex-1">
                    <div class="flex items-center justify-center">
                        {{-- Old Jig - . --}}
                        <div class="text-center">
                            <div class="text-[8px] font-bold mb-0.5">Old jig</div>
                            <div class="jig-icon">
                                <span style="font-size: 10px;">⚙️</span>
                            </div>
                        </div>

                        {{-- Arrow - . --}}
                        <div class="arrow mx-4 text-lg font-bold text-blue-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        {{-- New Jig - . --}}
                        <div class="text-center">
                            <div class="text-[8px] font-bold mb-0.5">New jig</div>
                            <div class="jig-icon">
                                <span style="font-size: 10px;">⚙️</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom sections untuk Machine - . --}}
            <div class="mt-1">
               

                {{-- SERIAL NUMBER & DATE - . --}}
                                <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>


                {{-- Tanggal Aktif - . --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
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

            {{-- Bottom sections untuk Material - . --}}
            <div class="mt-1">
                {{-- CURRENT PART & NEW PARTKT - . --}}
                <div class="grid grid-cols-2 gap-1">
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5">CURRENT PART</h3>
                        <p class="text-[7px]"><span class="font-semibold">PART NUMBER :</span> VPGZKF-19N551-AA</p>
                        <p class="text-[7px]"><span class="font-semibold">DESC:</span> FLTR-VEN AIR (NITTO)</p>
                    </div>
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5 text-red-600">NEW PART</h3>
                        <p class="text-[7px]"><span class="font-semibold">PART NUMBER :</span> VPGZKF-19N551-AB</p>
                        <p class="text-[7px]"><span class="font-semibold">DESC:</span> FLTR-VEN AIR (BRADY)</p>
                    </div>
                </div>

                {{-- SERIAL NUMBER & DATE - . --}}
                <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>

                {{-- Tanggal Aktif - . --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
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
    
    // Populate modal dengan data dari attributes
    document.getElementById('modalNamaBefore').textContent = card.dataset.nama;
    document.getElementById('modalNamaAfter').textContent = card.dataset.namaAfter;
    document.getElementById('modalStation').textContent = card.dataset.station;
    document.getElementById('modalShift').textContent = 'Shift ' + card.dataset.shift;
    document.getElementById('modalLineArea').textContent = card.dataset.lineArea;
    document.getElementById('modalKeterangan').textContent = card.dataset.keterangan;
    document.getElementById('modalEffectiveDate').textContent = card.dataset.effectiveDate;
    document.getElementById('modalEndDate').textContent = card.dataset.endDate;
    
    // Handle lampiran
    const lampiran = card.dataset.lampiran;
    const lampiranSection = document.getElementById('modalLampiranSection');
    const lampiranLink = document.getElementById('modalLampiranLink');
    
    if (lampiran) {
        lampiranSection.classList.remove('hidden');
        lampiranLink.href = lampiran;
    } else {
        lampiranSection.classList.add('hidden');
    }
    
    // Show modal
    document.getElementById('henkatenDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function closeHenkatenModal() {
    document.getElementById('henkatenDetailModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

// Close modal when clicking outside
document.getElementById('henkatenDetailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeHenkatenModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHenkatenModal();
    }
});
</script>



  @endpush
</x-app-layout>