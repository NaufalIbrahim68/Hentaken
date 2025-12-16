{{-- MATERIAL SECTION - LINE ROLES (FA, SMT, Secthead Produksi) --}}
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
               {{-- Row untuk Status --}}
              <tr>
    @foreach ($stationStatuses as $station)
        @php
            // $station adalah array, bukan object
            $isHenkaten = ($station['status'] === 'HENKATEN');
            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
        @endphp

        <td class="border border-gray-300 p-2 {{ $bgColor }}">
            <div class="flex justify-center">
                <div class="rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}"
                     style="width: 12px; height: 12px; min-width: 12px; min-height: 12px;">
                </div>
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
                {{-- LOGIKA BARU: FILTER STATUS 'PENDING' --}}
                {{-- =============================================== --}}
                @php
                    // Inisialisasi koleksi kosong untuk keamanan
                    $filteredMaterialHenkatens = collect(); 

                    // Cek jika $materialHenkatens ada, baru lakukan filter
                    if (isset($materialHenkatens)) {
                        $filteredMaterialHenkatens = $materialHenkatens->filter(function ($henkaten) {
                            // Asumsi nama field adalah 'status'
                            return strtolower($henkaten->status) === 'pending';
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
                                        Start: {{ $henkaten->serial_number_start ?? '-' }}
                                    </span>
                                </div>
                                <div class="bg-blue-400 text-center py-0.5 rounded">
                                    <span class="text-[7px] text-white font-medium">
                                        End: {{ $henkaten->serial_number_end ?? '-' }}
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
                    <div class="text-center text-xs text-gray-400 py-4 w-full">No Actived Material Henkaten</div>
                @endif
            </div>
        </div>

        {{-- Tombol Scroll Kanan --}}
        <button 
            onclick="scrollMaterialHenkaten('right')" 
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-700 rounded-full p-2 shadow-md border border-gray-200 transition"
            id="scrollRightBtnMaterial"}}>
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

</div>
