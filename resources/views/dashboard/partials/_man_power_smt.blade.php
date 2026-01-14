{{-- MAN POWER SECTION --}}
<div class="bg-white shadow rounded p-1 flex flex-col">
    {{-- ======================================================================= --}}
    {{-- BAGIAN HEADER: JUDUL DAN FILTER GRUP (DIUBAH JADI DROPDOWN) --}}
    {{-- ======================================================================= --}}
    <div class="grid grid-cols-3 items-center mb-2 px-2 pt-1">
        {{-- Kolom Kiri (Kosong) --}}
        <div></div>

        {{-- Kolom Tengah (Judul) --}}
        <h2 class="text-sm font-semibold text-center">MAN POWER</h2>

        {{-- Kolom Kanan (Filter) --}}
        <div class="flex justify-end items-center space-x-2">
            @if (!$isAutoGroupA)
                {{-- Dropdown Grup --}}
                <div>
                    <select id="grupFilterDropdown" {{-- Panggil JS setGrup HANYA JIKA nilainya bukan "" (bukan "Pilih Grup") --}}
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

                {{-- TOMBOL RESET --}}
                <div>
                    <a href="{{ route('dashboard.resetGrup') }}"
                        class="text-[10px] text-gray-500 hover:text-red-600 underline" title="Reset Pilihan Grup">
                        Reset
                    </a>
                </div>
            @endif
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
            <div class="relative">
                {{-- Tombol Scroll Kiri (Floating) --}}
                <button
                    onclick="document.getElementById('manPowerTableScroll').scrollBy({left: -200, behavior: 'smooth'})"
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 bg-white/90 hover:bg-purple-500 hover:text-white text-gray-600 rounded-full flex items-center justify-center shadow-lg border border-gray-200 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                </button>

                <div id="manPowerTableScroll" class="w-full overflow-x-auto scrollbar-hide scroll-smooth px-8">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                {{-- Header untuk setiap station --}}
                                @foreach ($groupedManPower as $stationId => $stationWorkers)
                                    @foreach ($stationWorkers as $currentWorker)
                                        @php
                                            $stationName = $currentWorker->station
                                                ? $currentWorker->station->station_name
                                                : 'Station ' . $stationId;
                                            $isHenkaten = $currentWorker->status == 'Henkaten';
                                            $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                                            $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                                        @endphp
                                        <th
                                            class="border border-gray-300 px-1 py-2 text-[9px] font-medium {{ $bgColorHeader }} {{ $textColorHeader }}">
                                            <div class="text-center leading-tight break-words">{{ $stationName }}</div>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Row untuk Icon/Avatar --}}
                            <tr>
                                @foreach ($groupedManPower as $stationId => $stationWorkers)
                                    @foreach ($stationWorkers as $currentWorker)
                                        @php
                                            // Lebih akurat: pakai status dari controller
                                            $isHenkaten = ($currentWorker->status ?? '') === 'Henkaten';
                                            $bgIcon = 'bg-purple-600';
                                        @endphp



                                        <td
                                            class="border border-gray-300 p-2 {{ $isHenkaten ? 'bg-red-600' : 'bg-white' }}">
                                            <div class="flex justify-center items-center">
                                                <div
                                                    class="w-8 h-8 rounded-full {{ $bgIcon }} flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>

                            {{-- Row untuk Nama --}}
                            <tr>
                                @foreach ($groupedManPower as $stationId => $stationWorkers)
                                    @foreach ($stationWorkers as $currentWorker)
                                        @php
                                            $displayName = $currentWorker->nama; // SELALU nama asli
                                            $isHenkaten = $currentWorker->status == 'Henkaten';
                                        @endphp
                                        <td
                                            class="border border-gray-300 px-1 py-1.5 text-center {{ $isHenkaten ? 'bg-red-600' : 'bg-white' }}">
                                            <p
                                                class="text-[9px] font-semibold {{ $isHenkaten ? 'text-white' : 'text-gray-700' }} break-words leading-tight">
                                                {{ $displayName }}
                                            </p>
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>

                            {{-- Row untuk Status --}}
                            <tr>
                                @foreach ($groupedManPower as $stationId => $stationWorkers)
                                    @foreach ($stationWorkers as $currentWorker)
                                        @php
                                            $isHenkaten =
                                                $currentWorker->status == 'Henkaten' ||
                                                $currentWorker->status == 'PENDING';
                                            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                                        @endphp
                                        <td class="border border-gray-300 p-2 {{ $bgColor }}">
                                            <div class="flex justify-center">
                                                <div
                                                    class="w-3 h-3 rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}">
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Tombol Scroll Kanan (Floating) --}}
                <button
                    onclick="document.getElementById('manPowerTableScroll').scrollBy({left: 200, behavior: 'smooth'})"
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 bg-white/90 hover:bg-purple-500 hover:text-white text-gray-600 rounded-full flex items-center justify-center shadow-lg border border-gray-200 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
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


            <div id="shiftChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">

                @php
                    if ($currentGroup) {
                        $filteredHenkatens = $activeManPowerHenkatens->filter(function ($henkaten) use ($currentGroup) {
                            $isCorrectGroup = optional($henkaten->manPower)->grup === $currentGroup;

                            $isPending = strtolower($henkaten->status) === 'pending';

                            return $isCorrectGroup && $isPending;
                        });
                    } else {
                        $filteredHenkatens = collect();
                    }
                @endphp


                @if ($filteredHenkatens->isNotEmpty())
                    <div class="flex justify-center gap-3 min-w-full px-2">
                        @foreach ($filteredHenkatens as $henkaten)
                            @php
                                $startDate = strtoupper($henkaten->effective_date->format('j/M/y'));
                                $endDate = $henkaten->end_date
                                    ? strtoupper($henkaten->end_date->format('j/M/y'))
                                    : 'SELANJUTNYA';
                            @endphp

                            {{-- KOTAK UTAMA UNTUK SETIAP HENKATEN --}}
                            <div class="flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border border-gray-300 shadow-md cursor-pointer hover:bg-orange-50 transition transform hover:scale-[1.02]"
                                style="width: 240px;" onclick="showHenkatenDetail({{ $henkaten->id }})"
                                {{-- Data attributes untuk modal --}} data-henkaten-id="{{ $henkaten->id }}"
                                data-nama="{{ $henkaten->nama }}" data-nama-after="{{ $henkaten->nama_after }}"
                                data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                                data-shift="{{ $henkaten->shift }}" data-keterangan="{{ $henkaten->keterangan }}"
                                data-line-area="{{ $henkaten->line_area }}"
                                data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y') : '-' }}"
                                data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y') : 'Selanjutnya' }}"
                                data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                                data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                                data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                                data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                                data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}">
                                {{-- Perubahan Pekerja --}}
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="text-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                            👤</div>
                                        <p class="text-[8px] font-semibold truncate w-20">{{ $henkaten->nama }}</p>
                                        <div class="w-2 h-2 rounded-full bg-red-500 mx-auto mt-0.5" title="Before">
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-400 font-bold">→</div>
                                    <div class="text-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                            👤</div>
                                        <p class="text-[8px] font-semibold truncate w-20">{{ $henkaten->nama_after }}
                                        </p>
                                        <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5" title="After">
                                        </div>
                                    </div>
                                </div>

                                {{-- Serial Number --}}
                                <div class="grid grid-cols-2 gap-1">
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">Start:
                                            {{ $henkaten->serial_number_start ?? '-' }}</span>
                                    </div>
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">End:
                                            {{ $henkaten->serial_number_end ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Periode Aktif --}}
                                <div class="flex justify-center">
                                    <div
                                        class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-[9px] font-semibold">
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


        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- MODAL DETAIL HENKATEN  --}}
    {{-- ============================================================= --}}
    <div id="henkatenDetailModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all scale-100">
            {{-- HEADER MODAL --}}
            <div
                class="sticky top-0 bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten</h3>
                <button onclick="closeHenkatenModal()" class="text-white hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12">
                        </path>
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
                            <div
                                class="w-14 h-14 rounded-full bg-purple-600 flex items-center justify-center text-white text-xl mx-auto mb-1">
                                👤</div>
                            <p id="modalNamaBefore" class="font-semibold text-sm"></p>
                            <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                        </div>
                        <div class="text-2xl text-gray-400">→</div>
                        <div class="text-center">
                            <div
                                class="w-14 h-14 rounded-full bg-purple-600 flex items-center justify-center text-white text-xl mx-auto mb-1">
                                👤</div>
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
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
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
                    <a id="modalLampiranLink" href="#" target="_blank"
                        class="block bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                        Lihat Lampiran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
