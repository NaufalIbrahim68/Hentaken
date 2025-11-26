<x-app-layout>
    {{-- HEADER SECTION --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($log) ? __('Edit Data Henkaten Method') : __('Buat Data Henkaten Method') }}
        </h2>
    </x-slot>

    {{-- MAIN CONTENT AREA --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- ERROR MESSAGE DISPLAY --}}
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">Terdapat kesalahan input:</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- SUCCESS MESSAGE DISPLAY --}}
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative" role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false" class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">&times;</button>
                        </div>
                    @endif

                    {{-- PHP VARIABLE SETUP --}}
                    @php
                        $formAction = isset($log)
                            ? route('activity.log.method.update', $log->id)
                            : route('henkaten.method.store');

                        $userRole = Auth::user()->role ?? 'Guest';
                        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

                        $predefinedLineArea = match ($userRole) {
                            'Leader QC' => 'Incoming',
                            'Leader PPIC' => 'Delivery',
                            default => null,
                        };

                        $currentShift = $currentShift ?? 1;

                        // PENTING: Untuk Predefined Role, ambil Station ID default
                        $predefinedStationId = null;
                        $predefinedStation = null;
                        if ($isPredefinedRole && $predefinedLineArea) {
                            $defaultStation = \App\Models\Station::where('line_area', $predefinedLineArea)->first();
                            $predefinedStationId = $defaultStation->id ?? null;
                            $predefinedStation = $defaultStation;
                            if (isset($log)) {
                                $predefinedStationId = $log->station_id;
                                $predefinedStation = $log->station ?? $predefinedStation;
                            }
                        }
                    @endphp

                    {{-- FORM START --}}
                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        @if(isset($log))
                            @method('PUT')
                        @endif

                        {{-- Hidden Shift Input --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? $currentShift) }}">

                        {{-- GRID CONTAINER (single Alpine scope) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="henkatenForm()" x-init="init()">

                            {{-- LEFT COLUMN: Line Area / Station / Method --}}
                            <div>
                              {{-- PREDEFINED ROLE (Leader QC / Leader PPIC) --}}
<template x-if="isPredefinedRole">
    <div>
        {{-- LINE AREA (readonly) --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Line Area</label>
            <input type="text" :value="predefinedLineArea" readonly
                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
            <input type="hidden" name="line_area" :value="predefinedLineArea">
        </div>

        {{-- HAPUS STATION FORM --}}
        {{-- Tidak ada input station sama sekali --}}

        {{-- METHOD (dropdown tetap) --}}
        <div class="mb-4">
            <label for="methods_name_input" class="block text-sm font-medium text-gray-700">Nama Method</label>
            <select id="methods_name_input" name="method_id" required
                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                <option value="">-- Pilih Method --</option>
                @foreach($methodList as $m)
                    <option value="{{ $m->id }}" {{ old('method_id', $log->method_id ?? '') == $m->id ? 'selected' : '' }}>
                        {{ $m->methods_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</template>

                                {{-- DYNAMIC MODE (all other roles) --}}
                                <template x-if="!isPredefinedRole">
                                    <div>
                                        {{-- LINE AREA (dropdown) --}}
                                        <div class="mb-4">
                                            <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                            <select id="line_area" name="line_area" x-model="selectedLineArea" @change="fetchStations()"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" required>
                                                <option value="">-- Pilih Line Area --</option>
                                                {{-- server-side list for initial options --}}
                                                @foreach ($lineAreas as $area)
                                                    <option value="{{ $area }}" {{ old('line_area', $selectedLineArea ?? '') == $area ? 'selected' : '' }}>
                                                        {{ $area }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- STATION (dropdown populated by AJAX or initial server data) --}}
                                        <div class="mb-4">
                                            <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                            <select id="station_id" name="station_id" x-model="selectedStation" @change="fetchMethods()"
                                                :disabled="stations.length === 0"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" required>
                                                <option value="">-- Pilih Station --</option>
                                                <template x-for="st in stations" :key="st.id">
                                                    <option :value="st.id" x-text="st.station_name" :selected="st.id == '{{ old('station_id', $log->station_id ?? '') }}'"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- METHOD (dropdown populated by AJAX) --}}
                                        <div class="mb-4">
                                            <label for="methods_name" class="block text-sm font-medium text-gray-700">Nama Method</label>
                                            <select id="methods_name" name="method_id" x-model="selectedMethodId"
                                                :disabled="methodList.length === 0"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" required>
                                                <option value="">-- Pilih Method --</option>
                                                <template x-for="m in methodList" :key="m.id">
                                                    <option :value="m.id" x-text="m.methods_name" :selected="m.id == '{{ old('method_id', $log->method_id ?? '') }}'"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </template>

                                {{-- SERIAL NUMBER START --}}
                                <div class="mb-4">
                                    <label for="serial_number_start" class="block text-sm font-medium text-gray-700">Serial Number Start</label>
                                    <input type="text" id="serial_number_start" name="serial_number_start"
                                        value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                </div>

                                {{-- SERIAL NUMBER END --}}
                                <div class="mb-4">
                                    <label for="serial_number_end" class="block text-sm font-medium text-gray-700">Serial Number End</label>
                                    <input type="text" id="serial_number_end" name="serial_number_end"
                                        value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div> {{-- end left column --}}

                            {{-- RIGHT COLUMN: Dates & Times --}}
                            <div>
                                {{-- EFFECTIVE DATE --}}
                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    <input type="date" id="effective_date" name="effective_date"
                                        value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- END DATE --}}
                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                    <input type="date" id="end_date" name="end_date"
                                        value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- TIME START --}}
                                <div class="mb-4">
                                    <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                    <input type="time" id="time_start" name="time_start"
                                        value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- TIME END --}}
                                <div class="mb-4">
                                    <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                    <input type="time" id="time_end" name="time_end"
                                        value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>
                            </div>

                        </div> {{-- End Grid/Alpine Scope --}}

                        {{-- KETERANGAN TEXTAREAS --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            {{-- KETERANGAN SEBELUM --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sebelum</label>
                                <textarea id="keterangan" name="keterangan" rows="4"
                                    placeholder="Jelaskan kondisi method sebelum perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2 italic">Data method sebelum perubahan</p>
                            </div>

                            {{-- KETERANGAN SESUDAH --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md">
                                <label for="keterangan_after" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sesudah Pergantian</label>
                                <textarea id="keterangan_after" name="keterangan_after" rows="4"
                                    placeholder="Jelaskan kondisi method setelah perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan_after', $log->keterangan_after ?? '') }}</textarea>
                                <p class="text-xs text-green-600 mt-2 italic">Data method setelah perubahan</p>
                            </div>
                        </div>

                        {{-- Lampiran --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran
                                (Wajib untuk Izin/Sakit)</label>
                            <input type="file" id="lampiran" name="lampiran"
                                accept=".png,.jpg,.jpeg,.zip,.rar,application/zip,application/x-rar-compressed"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                {{ !isset($log) ? 'required' : '' }}>
                            @if (isset($log) && $log->lampiran)
                                <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                    <p class="text-sm text-gray-700 font-medium mb-1">Lampiran saat ini:</p>
                                    <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">
                                        Lihat Lampiran ({{ basename($log->lampiran) }})
                                    </a>
                                    <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda
                                        ingin mengganti lampiran ini.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('activity.log.method') }}"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                {{ isset($log) ? 'Simpan Perubahan' : 'Simpan Data' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- ALPINE.JS SCRIPT --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("henkatenForm", () => ({

            // initial state populated from server-side variables
            lineAreas: @json($lineAreas ?? []),
            stations: @json($stations ?? []),
            methodList: @json($methodList ?? []),

            isPredefinedRole: @json($isPredefinedRole),
            predefinedLineArea: @json($predefinedLineArea),
            predefinedStationId: @json($predefinedStationId),
            predefinedStationName: @json(optional($predefinedStation)->station_name ?? ''),

         selectedLineArea: @json($selectedLineArea ?? old('line_area') ?? '') || '',
selectedStation: @json(old('station_id') ?? ($predefinedStationId ?? '')) || '',
selectedMethodId: @json(old('method_id') ?? ($log->method_id ?? '')) || '',

            // routes
            stationsByLineUrl: '{{ route('henkaten.stations.by_line') }}',
            methodsByStationUrl: '{{ route('henkaten.methods.by_station') }}',

            init() {
                // if dynamic mode and we have preloaded values, load dependent data
                if (!this.isPredefinedRole) {
                    if (this.selectedLineArea && (!this.stations || this.stations.length === 0)) {
                        this.fetchStations();
                    }
                    if (this.selectedStation && (!this.methodList || this.methodList.length === 0)) {
                        this.fetchMethods();
                    }
                }
            },

            async fetchStations() {
                if (!this.selectedLineArea) {
                    this.stations = [];
                    this.selectedStation = '';
                    this.methodList = [];
                    return;
                }

                try {
                    const q = encodeURIComponent(this.selectedLineArea);
                    const res = await fetch(`${this.stationsByLineUrl}?line_area=${q}`);
                    if (!res.ok) throw new Error('Network response not ok');
                    const data = await res.json();
                    this.stations = Array.isArray(data) ? data : (data.data ?? []);
                    this.selectedStation = '';
                    this.methodList = [];
                } catch (err) {
                    console.error('Gagal fetch stations:', err);
                    this.stations = [];
                    this.selectedStation = '';
                    this.methodList = [];
                }
            },

         async fetchMethods() {
    if (!this.selectedStation) {
        this.methodList = [];
        // Jangan reset selectedMethodId ketika edit
        return;
    }

    try {
        const id = encodeURIComponent(this.selectedStation);
        const res = await fetch(`${this.methodsByStationUrl}?station_id=${id}`);
        if (!res.ok) throw new Error('Network response not ok');

        const data = await res.json();
        this.methodList = Array.isArray(data) ? data : (data.data ?? []);

        // Kalau ini BUKAN edit (selectedMethodId kosong), reset.
        // Kalau edit (selectedMethodId sudah ada), JANGAN reset.
        if (!@json(isset($log))) {   // jika halaman CREATE
            this.selectedMethodId = '';
        }

    } catch (err) {
        console.error('Gagal fetch methods:', err);
        this.methodList = [];
        // Jangan reset selectedMethodId (penting untuk EDIT)
    }
}


        }));
    });
    </script>

</x-app-layout>
