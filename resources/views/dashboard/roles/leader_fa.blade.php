
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
        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b pb-1 mb-1 h-[8vh]">
            {{-- Kolom Kiri: Logo --}}
            <div class="w-1/3 flex items-center pl-20">
                <img src="{{ asset('assets/images/AVI.png') }}" alt="Logo AVI" class="h-10 w-auto" />
            </div>

       <div class="w-1/3 text-center">
    <form action="{{ url()->current() }}" method="GET" class="flex justify-center">
        
        <select name="line_area" 
                onchange="this.form.submit()"
                class="text-base font-bold border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            
            @php
                $hasFalLine = false; // Variabel bantu untuk melacak
            @endphp

            @foreach($lineAreas as $line)
                {{-- 
                    Kita cek apakah $line (cth: "FA L1", "SMT L1") 
                    diawali dengan string "FA L".
                    Kita gunakan namespace lengkap "Illuminate\Support\Str::" agar aman.
                --}}
                @if(Illuminate\Support\Str::startsWith($line, 'FA L'))
                    
                    @php $hasFalLine = true; @endphp {{-- Tandai bahwa kita menemukan setidaknya satu line FA L --}}

                    <option value="{{ $line }}" {{ $selectedLineArea == $line ? 'selected' : '' }}>
                        HENKATEN {{ $line }}
                    </option>
                @endif
            @endforeach

            {{-- 
                Jika setelah dicek semua, tidak ada satupun line FA L
                (misalnya $lineAreas hanya berisi "SMT L1" dan "SMT L2"),
                kita tampilkan pesan ini.
            --}}
            @if(!$hasFalLine)
                <option disabled {{ !$selectedLineArea ? 'selected' : '' }}>
                    Tidak ada Line FA L
                </option>
            @endif

        </select>
    </form>


    {{-- Ini tetap sama dari kode Anda --}}
    <p class="text-[10px] text-gray-600" id="current-date"></p>
</div>

            {{-- Time & Shift --}}
            <div class="w-1/3 text-right">
                <p class="font-mono text-sm" id="current-time"></p>
                <p class="text-xs" id="current-shift"></p>
            </div>
        </div>

        {{-- 4 SECTION GRID --}}
        <div class="grid grid-cols-2 gap-3 h-[92vh]">
            {{-- Your content sections here --}}
        
 
{{-- MAN POWER --}}
<div class="bg-white shadow rounded p-1 flex flex-col">
    {{-- ======================================================================= --}}
    {{-- BAGIAN HEADER: JUDUL DAN FILTER GRUP (DIUBAH JADI DROPDOWN) --}}
    {{-- ======================================================================= --}}
    <div class="flex items-center mb-2 px-2 pt-1">

    <div class="flex-1">
        </div>

     <h2 class="text-sm font-semibold mb-3 text-center">MAN POWER</h2>
    
    <div class="flex-1 flex justify-end items-center space-x-2">
        
        {{-- Dropdown Anda (sudah benar) --}}
        <div>
            <select id="grupFilterDropdown" 
                    {{-- Panggil JS setGrup HANYA JIKA nilainya bukan "" (bukan "Pilih Grup") --}}
                    onchange="if(this.value) { setGrup(this.value); }"
                    class="text-[10px] font-bold px-2 py-0.5 rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                    style="padding-right: 1.75rem;"> {{-- Styling untuk panah dropdown --}}

                {{-- Opsi "Pilih Grup" akan aktif jika session $currentGroup kosong --}}
                <option value="" {{ is_null($currentGroup ?? null) ? 'selected' : '' }} disabled>
                    Pilih Grup...
                </option>

                <option value="A" {{ ($currentGroup ?? null) == 'A' ? 'selected' : '' }}>
                    Grup A
                </option>

                <option value="B" {{ ($currentGroup ?? null) == 'B' ? 'selected' : '' }}>
                    Grup B
                </option>
            </select>
        </div>

        {{-- TOMBOL RESET BARU (sudah benar) --}}
        <div>
            <a href="{{ route('dashboard.resetGrup') }}" 
               class="text-[10px] text-gray-500 hover:text-red-600 underline" 
               title="Reset Pilihan Grup">
                Reset
            </a>
        </div>

    </div>
    
</div>

{{-- ======================================================================= --}}
{{-- BAGIAN ATAS: DAFTAR MAN POWER (TABEL HORIZONTAL) --}}
{{-- ======================================================================= --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    {{-- CEK: JIKA GRUP BELUM DIPILIH ATAU DATA KOSONG --}}
    @if (isset($dataManPowerKosong) && $dataManPowerKosong)
        <div class="w-full text-center text-gray-500 py-10">
            @if (!$currentGroup)
                <p class="text-sm font-medium">Silakan pilih Grup di atas</p>
            @else
                <p class="text-sm font-medium">Tidak ada Man Power untuk Grup {{ $currentGroup }}</p>
            @endif
        </div>
    @else
        {{-- TABEL MAN POWER FORMAT HORIZONTAL --}}
        <div class="w-full">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        {{-- Header untuk setiap station --}}
                        @foreach($groupedManPower as $stationId => $stationWorkers)
                            @foreach($stationWorkers as $currentWorker)
                                @php
                                    $stationName = $currentWorker->station ? $currentWorker->station->station_name : 'Station ' . $stationId;
                                    $isHenkaten = ($currentWorker->status == 'Henkaten');
                                    $bgColorHeader = $isHenkaten ? 'bg-red-500' : 'bg-gray-50';
                                    $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                                @endphp
                                <th class="border border-gray-300 px-1 py-2 text-[9px] font-medium {{ $bgColorHeader }} {{ $textColorHeader }}">
                                    <div class="text-center leading-tight break-words">{{ $stationName }}</div>
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Row untuk Icon/Avatar --}}
                    <tr>
                        @foreach($groupedManPower as $stationId => $stationWorkers)
                            @foreach($stationWorkers as $currentWorker)
                                @php
                                    $isHenkaten = ($currentWorker->status == 'Henkaten');
                                    $bgColorCell = $isHenkaten ? 'bg-red-500' : 'bg-white';
                                @endphp
                                <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                                    <div class="flex justify-center items-center">
                                        <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                    
                    {{-- Row untuk Nama --}}
                    <tr>
                        @foreach($groupedManPower as $stationId => $stationWorkers)
                            @foreach($stationWorkers as $currentWorker)
                                @php
                                    $displayName = $currentWorker->nama;
                                    $isHenkaten = ($currentWorker->status == 'Henkaten');
                                    $bgColorCell = $isHenkaten ? 'bg-red-500' : 'bg-white';
                                    $textColor = $isHenkaten ? 'text-white' : 'text-gray-700';
                                @endphp
                                <td class="border border-gray-300 px-1 py-1.5 text-center {{ $bgColorCell }}">
                                    <p class="text-[9px] font-semibold {{ $textColor }} break-words leading-tight" title="{{ $displayName }}">
                                        {{ $displayName }}
                                    </p>
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                    
                    {{-- Row untuk Status --}}
                    <tr>
                        @foreach($groupedManPower as $stationId => $stationWorkers)
                            @foreach($stationWorkers as $currentWorker)
                                @php
$isHenkaten = ($currentWorker->status == 'Henkaten' || $currentWorker->status == 'Approved'); // Asumsi 'Approved' juga harus merah
                                    $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                                @endphp
                                <td class="border border-gray-300 p-2 {{ $bgColor }}">
                                    <div class="flex justify-center">
                                        <div class="w-3 h-3 rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}"></div>
                                    </div>
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Legend --}}
        <div class="flex justify-center gap-4 mt-3 mb-3 text-[10px]">
            <div class="flex items-center gap-1">
                <div class="w-2 h-2 rounded-full bg-green-600"></div>
                <span class="text-gray-600">Normal</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="w-2 h-2 rounded-full bg-red-600"></div>
                <span class="text-gray-600">Henkaten</span>
            </div>
        </div>
    @endif
</div>

  {{-- ======================================================================= --}}
    {{-- BAGIAN BAWAH: DETAIL HENKATEN (OTOMATIS TERFILTER) --}}
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
    // Logika ini masih sama dan sudah benar
    if ($currentGroup) {
        $filteredHenkatens = $activeManPowerHenkatens->filter(function ($henkaten) use ($currentGroup) {
            
            // Menggunakan relasi manPower (Man Power LAMA) untuk validasi grup.
            // Walaupun sudah difilter di controller, ini memastikan data aman.
            $isCorrectGroup = optional($henkaten->manPower)->grup === $currentGroup; 
            
            $isApproved = strtolower($henkaten->status) === 'approved';
            
            return $isCorrectGroup && $isApproved;
        });
    } else {
        // Jika $currentGroup = null (belum dipilih), $filteredHenkatens akan kosong
        $filteredHenkatens = collect();
    }
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
                                {{-- Data attributes untuk modal --}}
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
                        {{-- Pesan ini akan tampil jika grup belum dipilih --}}
                        @if (!$currentGroup)
                            Pilih grup untuk melihat Henkaten
                        @else
                            No Actived Henkaten In this Group
                        @endif
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
    {{-- MODAL DETAIL HENKATEN (Tidak berubah) --}}
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

            {{-- KONTEN MODAL --}}
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
      
{{-- METHOD  --}}
<div class="bg-white shadow rounded p-4 flex flex-col">
    <h2 class="text-sm font-semibold mb-3 text-center">METHOD</h2>

    <div class="w-full">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                            $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <th class="border border-gray-300 px-1 py-2 text-[9px] font-medium {{ $bgColorHeader }} {{ $textColorHeader }}">
                            <div class="text-center leading-tight break-words">
                                {{ $m->station->station_name ?? 'N/A' }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Row untuk Icon --}}
                <tr>
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                            <div class="flex justify-center items-center">
                                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
                {{-- Row untuk Status --}}
                <tr>
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColor }}">
                            <div class="flex justify-center">
                                <div class="w-3 h-3 rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}"></div>
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


  {{-- ======================================================================= --}}
{{-- BAGIAN BAWAH: DETAIL HENKATEN (METODE) 
{{-- ======================================================================= --}}
<div class="border-t mt-2 pt-2">

    <div class="flex items-center gap-1">
        <button id="scrollLeftMethod" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        <div id="methodChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">
            @php
                // LOGIKA BARU: Filter koleksi untuk hanya menyertakan status 'approved'
                $filteredMethodHenkatens = $activeMethodHenkatens->filter(function ($henkaten) {
                    // Asumsi field status adalah 'status'
                    return strtolower($henkaten->status) === 'approved';
                });
            @endphp

            @if($filteredMethodHenkatens->isNotEmpty())
                <div class="flex justify-center gap-3 min-w-full px-2">
                    {{-- Loop menggunakan variabel baru yang sudah difilter --}}
                    @foreach($filteredMethodHenkatens as $henkaten)
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
                {{-- ========================================================== --}}
                {{-- 🔔 PERUBAHAN DI SINI: Teks pemberitahuan --}}
                {{-- ========================================================== --}}
                <div class="text-center text-xs text-gray-400 py-4">No Active & Approved Method Henkaten</div>
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
                            {{-- Ini akan diisi oleh JavaScript --}}
                        </p>
                    </div>

                    <div class="text-2xl text-gray-400">→</div>

                    {{-- Bagian "Sesudah" dimodifikasi --}}
                    <div class="text-center">
                        <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                        {{-- Data 'keterangan_after' dari tabel dimasukkan di sini --}}
                        <p id="modalKeteranganAfter" class="font-semibold text-sm mt-1">
                            {{-- Ini akan diisi oleh JavaScript --}}
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
<div class="bg-white shadow rounded p-4 flex flex-col mt-1">
    <h2 class="text-sm font-semibold mb-3 text-center">MACHINE</h2>

    {{-- Machine Table --}}
    <div class="w-full">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                            $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <th class="border border-gray-300 px-1 py-2 text-[9px] font-medium {{ $bgColorHeader }} {{ $textColorHeader }}">
                            <div class="text-center leading-tight break-words">
                                {{ $mc->station->station_name ?? '-' }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Row untuk Icon --}}
                <tr>
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                            <div class="flex justify-center items-center">
                                <div class="machine-status rounded-full flex items-center justify-center cursor-pointer" 
                                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; background-color: #9333ea;"
                                     onclick="toggleMachine(this)">
                                    <svg class="text-white" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
                {{-- Row untuk Status --}}
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



   {{-- MACHINE HENKATEN CARD SECTION --}}
<div class="border-t mt-4 pt-4 overflow-x-auto scrollbar-hide">
    <div class="flex justify-center gap-3 p-2">
        {{-- GANTI KARTU LAMA ANDA DENGAN INI --}}
        @php
            // LOGIKA BARU: Filter koleksi untuk hanya menyertakan status 'approved'
            $filteredMachineHenkatens = $machineHenkatens->filter(function ($henkaten) {
                // Asumsi field status adalah 'status'
                return strtolower($henkaten->status) === 'approved';
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
            <div class="text-center text-xs text-gray-400 py-4 w-full">No Active & Approved Machine Henkaten</div>
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
                <div class="bg-orange-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Machine</p>
                    <p id="modalMachine" class="font-semibold text-sm truncate">-</p>
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
{{-- ============================================= --}}
{{-- MATERIAL SECTION --}}
{{-- ============================================= --}}
<div class="bg-white shadow rounded p-4 flex flex-col mt-1">
    <h2 class="text-sm font-semibold mb-3 text-center">MATERIAL</h2>

    {{-- Material Table --}}
    <div class="w-full">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    @foreach($stationStatuses as $station)
                        @php
                            $isHenkaten = $station['status'] !== 'NORMAL';
                            $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                            $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <th class="border border-gray-300 px-1 py-2 text-[9px] font-medium {{ $bgColorHeader }} {{ $textColorHeader }}">
                            <div class="text-center leading-tight break-words">
                                {{ $station['name'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Row untuk Icon --}}
                <tr>
                    @foreach($stationStatuses as $station)
                        @php
                            $isHenkaten = $station['status'] !== 'NORMAL';
                            $bgColorCell = $isHenkaten ? 'bg-red-500' : 'bg-white';
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
                {{-- Row untuk Status --}}
                <tr>
                    @foreach($stationStatuses as $station)
                        @php
                            $isHenkaten = $station['status'] !== 'NORMAL';
                            $bgColor = $isHenkaten ? 'bg-red-600' : 'bg-green-500';
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


   {{-- ============================================= --}}
{{--  MATERIAL HENKATEN CARD SECTION      --}}
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
                
                {{-- =============================================== --}}
                {{-- LOGIKA BARU: FILTER STATUS 'APPROVED' --}}
                {{-- =============================================== --}}
                @php
                    // Inisialisasi koleksi kosong untuk keamanan
                    $filteredMaterialHenkatens = collect(); 

                    // Cek jika $materialHenkatens ada, baru lakukan filter
                    if (isset($materialHenkatens)) {
                        $filteredMaterialHenkatens = $materialHenkatens->filter(function ($henkaten) {
                            // Filter hanya tampilkan yang statusnya 'approved'
                            // Asumsi nama field adalah 'status'
                            return strtolower($henkaten->status) === 'approved';
                        });
                    }
                @endphp
                {{-- =============================================== --}}


                {{-- Gunakan koleksi BARU ($filteredMaterialHenkatens) untuk loop --}}
                @if($filteredMaterialHenkatens->isNotEmpty())
                    @foreach($filteredMaterialHenkatens as $henkaten)
                        <div 
                            class="material-card flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 border-shadow-500 shadow-md cursor-pointer hover:bg-gray-100 transition"
                            style="width: 220px;"
                            onclick="showMaterialHenkatenDetail(this)" 
                            
                            {{-- ====== Data Lengkap Untuk Modal ====== --}}
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
                            data-material="{{ $henkaten->material->material_name ?? '-' }}"
                        >

                            {{-- CURRENT & NEW PART --}}
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

                            {{-- SERIAL NUMBER --}}
                            <div class="grid grid-cols-2 gap-1">
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[7px] text-white font-medium">
                                        Start: {{ $henkaten->serial_number_start ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[7px] text-white font-medium">
                                        End: {{ $henkaten->serial_number_end ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            {{-- ACTIVE DATE --}}
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
                    {{-- Pesan @else diubah untuk mencerminkan filter --}}
                    <div class="text-center text-xs text-gray-400 py-4 w-full">No Active & Approved Material Henkaten</div>
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

{{-- ============================================================= --}}
{{-- MODAL DETAIL HENKATEN (MATERIAL) --}}
{{-- ============================================================= --}}
<div id="materialHenkatenDetailModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">

    {{-- 1️⃣ CARD UTAMA --}}
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100">

        {{-- 2️⃣ HEADER WARNA ORANGE --}}
        <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-yellow-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten Material</h3>
            <button onclick="closeMaterialHenkatenModal()" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

       {{-- 3️⃣ ISI MODAL --}}
       <div class="p-6 space-y-4">

            {{-- PERUBAHAN MATERIAL --}}
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Material</h4>
                <div class="flex items-center justify-around">
                    <div class="text-center">
                        <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                        
                        {{-- IKON OBENG (Sebelum - Warna Abu) --}}
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-gray-500 mx-auto my-2">
                            <rect x="4" y="4" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="13" y="4" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="4" y="13" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="13" y="13" width="7" height="7" rx="1" stroke-width="2"/>
                        </svg>
                        <p id="modalMaterialBefore" class="font-semibold text-sm mt-1">-</p>
                    </div>

                    <div class="text-2xl text-gray-400">→</div>

                    <div class="text-center">
                        <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                        
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-green-600 mx-auto my-2">
                            <rect x="4" y="4" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="13" y="4" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="4" y="13" width="7" height="7" rx="1" stroke-width="2"/>
                            <rect x="13" y="13" width="7" height="7" rx="1" stroke-width="2"/>
                        </svg>
                        
                        <p id="modalMaterialAfter" class="font-semibold text-sm mt-1">-</p>
                    </div>
                </div>
            </div>
               {{-- DETAIL INFORMASI --}}
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

                    {{-- ✅ Tambahan: Nama Material --}}
                    <div class="bg-orange-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Material</p>
                        <p id="modalMaterial" class="font-semibold text-sm truncate">
                            {{-- Ini akan diisi oleh JavaScript, jadi tidak perlu diubah --}}
                            -
                        </p>
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

            {{-- PERIODE --}}
            <div class="flex justify-between items-center px-4 py-2">
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
                    class="block bg-orange-600 hover:bg-orange-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                    Lihat Lampiran
                </a>
            </div>
        </div>
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

    /**
     * Set Grup
     */
    function setGrup(grup) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch("{{ route('dashboard.setGrup') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                grup: grup
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                console.error('Failed to set group.');
                alert('Gagal mengganti grup. Silakan coba lagi.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan. Pastikan Anda memiliki koneksi dan CSRF token valid.');
        });
    }

    // =============================================
    // --- FUNGSI MODAL MAN POWER (DIPERBAIKI) ---
    // =============================================
    function showHenkatenDetail(henkatenId) {
        const card = document.querySelector(`[data-henkaten-id="${henkatenId}"]`);
        if (!card) {
            console.error('Card Man Power Henkaten tidak ditemukan!');
            return;
        }
        
        // --- PERBAIKAN: Dapatkan modal spesifik ---
        const modal = document.getElementById('henkatenDetailModal');
        if (!modal) {
            console.error('Modal Man Power (henkatenDetailModal) tidak ditemukan!');
            return;
        }
        
        const data = card.dataset; // Ambil semua data- attributes

        // --- PERBAIKAN: Gunakan modal.querySelector() ---
        modal.querySelector('#modalNamaBefore').textContent = data.nama || '-';
        modal.querySelector('#modalNamaAfter').textContent = data.namaAfter || '-';
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
        
        // Mengurus Lampiran
        const lampiran = data.lampiran;
        const section = modal.querySelector('#modalLampiranSection'); // <-- Perbaikan
        const link = modal.querySelector('#modalLampiranLink'); // <-- Perbaikan
        
        if (lampiran) {
            section.classList.remove('hidden');
            link.href = lampiran;
        } else {
            section.classList.add('hidden');
        }

        // Tampilkan Modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeHenkatenModal() {
        document.getElementById('henkatenDetailModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // =============================================
    // --- FUNGSI MODAL METHOD ---
    // =============================================
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

        const data = card.dataset; 

        // (Kode ini sudah benar menggunakan modal.querySelector())
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
        modal.querySelector('#modalKeteranganBefore').textContent = data.keterangan || '-';
        modal.querySelector('#modalKeteranganAfter').textContent = data.keteranganAfter || '-';
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

    // =============================================
    // --- FUNGSI MODAL MATERIAL ---
    // =============================================
    function showMaterialHenkatenDetail(element) {
        const modal = document.getElementById('materialHenkatenDetailModal');
        if (!modal) {
            console.error('Modal Material tidak ditemukan');
            return;
        }

        // (Kode ini sudah benar menggunakan modal.querySelector())
        modal.querySelector('#modalMaterialBefore').textContent = element.getAttribute('data-nama');
        modal.querySelector('#modalMaterialAfter').textContent = element.getAttribute('data-nama-after');
        modal.querySelector('#modalStation').textContent = element.getAttribute('data-station');
        modal.querySelector('#modalShift').textContent = element.getAttribute('data-shift');
        modal.querySelector('#modalLineArea').textContent = element.getAttribute('data-line-area');
        modal.querySelector('#modalKeterangan').textContent = element.getAttribute('data-keterangan');
        modal.querySelector('#modalSerialStart').textContent = element.getAttribute('data-serial-number-start');
        modal.querySelector('#modalSerialEnd').textContent = element.getAttribute('data-serial-number-end');
        modal.querySelector('#modalTimeStart').textContent = element.getAttribute('data-time-start');
        modal.querySelector('#modalTimeEnd').textContent = element.getAttribute('data-time-end');
        modal.querySelector('#modalEffectiveDate').textContent = element.getAttribute('data-effective-date');
        modal.querySelector('#modalEndDate').textContent = element.getAttribute('data-end-date');
        modal.querySelector('#modalMaterial').textContent = element.getAttribute('data-material'); 

        const lampiran = element.getAttribute('data-lampiran');
        const lampiranSection = modal.querySelector('#modalLampiranSection');
        const lampiranLink = modal.querySelector('#modalLampiranLink');
        if (lampiran) {
            lampiranSection.classList.remove('hidden');
            lampiranLink.href = lampiran;
        } else {
            lampiranSection.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeMaterialHenkatenModal() {
        document.getElementById('materialHenkatenDetailModal').classList.add('hidden');
    }

    // =============================================
    // --- FUNGSI MODAL MACHINE (DI-UPDATE TOTAL) ---
    // =============================================
    function showMachineHenkatenDetail(element) {
        // 1. Dapatkan modal-nya
        const modal = document.getElementById('henkatenModal');
        if (!modal) {
            console.error('Modal Machine (henkatenModal) tidak ditemukan!');
            return;
        }

        // 2. Ambil semua data dari atribut data-*
        const data = element.dataset;

        // 3. Isi data ke modal menggunakan modal.querySelector()
        
        // "Perubahan" section
        modal.querySelector('#modalDescriptionBefore').textContent = data.descriptionBefore || '-';
        modal.querySelector('#modalDescriptionAfter').textContent = data.descriptionAfter || '-';

        // Grid 1
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalKeterangan').textContent = data.keterangan || '-';
        modal.querySelector('#modalMachine').textContent = data.machine || '-'; // ID baru

        // Grid 2
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';

        // Periode
        modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
        modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';

        // 4. Lampiran
        const lampiranSection = modal.querySelector('#modalLampiranSection');
        const lampiranLink = modal.querySelector('#modalLampiranLink');
        
        if (data.lampiran) {
            lampiranLink.href = data.lampiran;
            lampiranSection.classList.remove('hidden');
        } else {
            lampiranSection.classList.add('hidden');
            lampiranLink.href = '#'; // Reset href
        }

        // 5. Tampilkan modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Fungsi untuk menutup modal MACHINE
    function closeMachineHenkatenModal() {
        const modal = document.getElementById('henkatenModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = 'auto'; // Kembalikan scroll body
    }


    // ==================================================
    // ESC UNTUK MENUTUP SEMUA MODAL
    // ==================================================
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeHenkatenModal?.();
            closeMethodHenkatenModal?.();
            closeMaterialHenkatenModal?.();
            closeMachineHenkatenModal?.(); // <-- Pastikan ini ada
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


        // ... (Fungsi scroll card & method icon Anda yang lain) ...
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
            container.addEventListener('scroll', checkScrollButtons);
            setTimeout(checkScrollButtons, 100); 
        }

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
        
        // ================================================================
        // INISIALISASI SEMUA EVENT LISTENER MODAL
        // ================================================================
        
        // --- Event Listener untuk Modal Man Power ---
        const manPowerModal = document.getElementById('henkatenDetailModal');
        if (manPowerModal) {
            manPowerModal.addEventListener('click', function(e) {
                if (e.target === this) closeHenkatenModal();
            });
            // (Asumsi tombol close 'X' punya onclick="closeHenkatenModal()")
        }
        
        // --- Event Listener untuk Modal Method ---
        const methodModal = document.getElementById('methodHenkatenDetailModal');
        if (methodModal) {
            methodModal.addEventListener('click', function(e) {
                if (e.target === this) closeMethodHenkatenModal();
            });
        }

        // --- Event Listener untuk Modal Material ---
        const materialModal = document.getElementById('materialHenkatenDetailModal');
        if (materialModal) {
            materialModal.addEventListener('click', function(e) {
                if (e.target === this) closeMaterialHenkatenModal();
            });
            // (Asumsi tombol close 'X' punya onclick="closeMaterialHenkatenModal()")
        }
        
        // --- Event Listener untuk Modal MACHINE ---
        const machineModal = document.getElementById('henkatenModal');
        const machineCloseButton = machineModal.querySelector('#modalCloseButton'); // <-- Cari di dalam modal

        if (machineModal && machineCloseButton) {
            // Listener untuk tombol close 'X'
            machineCloseButton.addEventListener('click', closeMachineHenkatenModal);

            // Listener untuk klik di luar area modal
            machineModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeMachineHenkatenModal();
                }
            });
        }
        
    }); 
</script>
@endpush

</x-app-layout>