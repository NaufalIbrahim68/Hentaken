<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Data Henkaten Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Menampilkan pesan error validasi DARI BACKEND LARAVEL --}}
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

                    {{-- Notifikasi sukses --}}
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                            x-init="setTimeout(() => show = false, 3000)"
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative"
                            role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false"
                                class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">
                                &times;
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('henkaten.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Input tersembunyi untuk Shift dan Grup dari Session --}}
                        <input type="hidden" name="shift" value="{{ $currentShift }}">
                        <input type="hidden" name="grup" value="{{ $currentGroup }}">

                        {{-- Wrapper Alpine.js --}}
                        <div x-data="henkatenForm({
                                oldShift: '{{ old('shift', $currentShift) }}',
                                oldGrup: '{{ old('grup', $currentGroup) }}',
                                oldLineArea: '{{ old('line_area') }}',
                                oldStation: {{ old('station_id') ?? 'null' }},
                                oldManPowerBeforeId: {{ old('man_power_id') ?? 'null' }},
                                oldManPowerBeforeName: '{{ old('nama') }}',
                                oldManPowerAfterId: {{ old('man_power_id_after') ?? 'null' }},
                                oldManPowerAfterName: '{{ old('nama_after') }}',
                                findManpowerUrl: '{{ route('henkaten.getManPower') }}',
                                searchManpowerUrl: '{{ route('manpower.search') }}',
                                findStationsUrl: '{{ route('henkaten.stations.by_line') }}'
                            })" x-init="init()">
                            {{-- ========================================================== --}}
                            {{-- BARU: Menampilkan error jika Grup/Shift dari Sesi tidak ada --}}
                            {{-- ========================================================== --}}
                            <template x-if="grupError">
                                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md"
                                    role="alert">
                                    <p class="font-bold">Sesi Tidak Valid</p>
                                    <p x-text="grupError"></p>
                                </div>
                            </template>

                            {{-- ========================================================== --}}
                            {{-- BARU: Fieldset untuk menonaktifkan form jika ada error --}}
                            {{-- ========================================================== --}}
                            <fieldset :disabled="grupError">



                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- Kolom Kiri --}}
                                    <div>
                                        {{-- LINE AREA --}}
                                        <div class="mb-4">
                                            <label for="line_area" class="block text-sm font-medium text-gray-700">Line
                                                Area</label>
                                            <select id="line_area" name="line_area" x-model="selectedLineArea"
                                                @change="fetchStations"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- Pilih Line Area --</option>
                                                @foreach ($lineAreas as $area)
                                                    <option value="{{ $area }}">{{ $area }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- STATION --}}
                                        <div class="mb-4">
                                            <label for="station_id"
                                                class="block text-sm font-medium text-gray-700">Station</label>
                                            <select id="station_id" name="station_id" x-model="selectedStation"
                                                @change="fetchManpowerBefore" :disabled="!stationList.length"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- Pilih Station --</option>
                                                <template x-for="station in stationList" :key="station.id">
                                                    <option :value="station.id" x-text="station.station_name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div> {{-- Kolom Kanan (Tidak berubah) --}}
                                    <div>
                                        <div class="mb-4">
                                            <label for="effective_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal
                                                Efektif</label>
                                            <input type="date" id="effective_date" name="effective_date"
                                                value="{{ old('effective_date') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="end_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal
                                                Berakhir</label>
                                            <input type="date" id="end_date" name="end_date"
                                                value="{{ old('end_date') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_start"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                            <input type="time" id="time_start" name="time_start"
                                                value="{{ old('time_start') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_end"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu
                                                Berakhir</label>
                                            <input type="time" id="time_end" name="time_end"
                                                value="{{ old('time_end') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Before & After untuk Man Power (Tidak berubah) --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Before (Otomatis) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="nama_before_display" class="text-gray-700 text-sm font-bold">Nama
                                            Karyawan Sebelum</label>
                                        <input type="text" id="nama_before_display" name="nama"
                                            x-model="manpowerBefore.nama" readonly
                                            class="w-full py-3 px-4 border rounded bg-gray-100 text-gray-600"
                                            placeholder="Nama Man Power Sebelum">
                                        <input type="hidden" name="man_power_id" x-model="manpowerBefore.id">
                                        <p class="text-xs text-gray-500 mt-2 italic">Data man power yang diganti
                                            (otomatis berdasarkan grup, line, & station)</p>
                                    </div>

                                    {{-- After (Autocomplete) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="nama_after" class="text-gray-700 text-sm font-bold">Nama Karyawan
                                            Sesudah</label>
                                        <input type="text" id="nama_after" name="nama_after" x-model="autocompleteQuery"
                                            @input.debounce.300="searchAfter()" @click.away="autocompleteResults = []"
                                            autocomplete="off" class="w-full py-3 px-4 border rounded"
                                            placeholder="Masukkan Nama Man Power Pengganti..." required>
                                        <input type="hidden" name="man_power_id_after"
                                            x-model="selectedManpowerAfter">
                                        <ul x-show="autocompleteResults.length > 0"
                                            class="absolute z-10 bg-white border w-full mt-1 rounded-md shadow-md max-h-60 overflow-auto">
                                            <template x-for="item in autocompleteResults" :key="item.id">
                                                <li @click="selectAfter(item)"
                                                    class="px-4 py-2 cursor-pointer hover:bg-green-100"
                                                    x-text="item.nama"></li>
                                            </template>
                                        </ul>
                                        <p class="text-xs text-green-600 mt-2 italic">Data man power pengganti</p>
                                    </div>
                                </div>

                                {{-- Keterangan (Tidak berubah) --}}
                                <div class="mb-6 mt-6">
                                    <label for="keterangan"
                                        class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                    <textarea id="keterangan" name="keterangan" rows="4"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                        placeholder="Jelaskan alasan perubahan man power..."
                                        required>{{ old('keterangan') }}</textarea>
                                </div>

                                {{-- Lampiran (Tidak berubah) --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran"
                                        class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                                    <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        required>
                                </div>

                            </fieldset>
                            {{-- ========================================================== --}}
                            {{-- AKHIR BARU: Fieldset --}}
                            {{-- ========================================================== --}}


                            {{-- Tombol (SEKARANG DI LUAR FIELDSET) --}}
                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                <a href="{{ route('dashboard') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                    Batal
                                </a>
                                <button type="submit" {{-- DIUBAH: Tambahkan :disabled dan styling --}}
                                    :disabled="grupError"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md"
                                    :class="{ 'opacity-50 cursor-not-allowed': grupError }">
                                    Simpan Data
                                </button>
                            </div>

                        </div> {{-- Akhir dari wrapper x-data --}}
                    </form>

           
    

    </div>
    </div>
    </div>
    </div>

    {{-- Alpine.js (Tidak ada perubahan di sini) --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('henkatenForm', (config) => ({
        // STATE
        grupError: null,
        selectedLineArea: config.oldLineArea || '',
        selectedStation: config.oldStation || '',
        stationList: [],
        selectedManpowerBefore: config.oldManPowerBeforeId || '',
        selectedManpowerAfter: config.oldManPowerAfterId || '',
        manPowerBeforeList: [],
        manPowerAfterList: [],
        manpowerBefore: { id: config.oldManPowerBeforeId || '', nama: config.oldManPowerBeforeName || '' },
        selectedManpowerAfterObj: { id: config.oldManPowerAfterId || '', nama: config.oldManPowerAfterName || '' },
        namaBefore: config.oldManPowerBeforeName || '',
        namaAfter: config.oldManPowerAfterName || '',
        autocompleteResults: [],
        autocompleteQuery: config.oldManPowerAfterName || '',

        // URL
        findManpowerUrl: config.findManpowerUrl,
        searchManpowerUrl: config.searchManpowerUrl,
        findStationsUrl: config.findStationsUrl,

        // INIT
        init() {
            console.log('✅ Alpine initialized (Henkaten Man Power)');
            if (this.selectedLineArea) {
                this.fetchStations(false);
            }
        },

        async fetchStations(resetStation = true) {
            if (!this.selectedLineArea) {
                this.stationList = [];
                this.selectedStation = '';
                return;
            }
            try {
                const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                const data = await res.json();
                this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                if (resetStation) this.selectedStation = '';
            } catch (err) {
                console.error(err);
                this.stationList = [];
            }
        },

       async fetchManpowerBefore() {
    if (!this.selectedStation || !this.selectedLineArea) return;

    try {
        const url = new URL(this.findManpowerUrl, window.location.origin);
        url.searchParams.append('station_id', this.selectedStation);
        url.searchParams.append('line_area', this.selectedLineArea);
        url.searchParams.append('grup', '{{ $currentGroup }}'); // atau ambil dari config

        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await res.json();
        console.log('📦 Manpower Before response:', data);

        this.manpowerBefore = {
            id: data.id ?? '',
            nama: data.nama ?? ''
        };

    } catch (err) {
        console.error('❌ Gagal mengambil manpower sebelum:', err);
        this.manpowerBefore = { id: '', nama: '' };
    }
},



       async searchAfter() {
            if (this.autocompleteQuery.length < 2) {
                this.autocompleteResults = [];
                return;
            }
            try {
                // MEMBANGUN URL DENGAN PARAMETER YANG TEPAT
                const url = new URL(this.searchManpowerUrl, window.location.origin);
                url.searchParams.append('q', this.autocompleteQuery); // 'q' untuk query
                url.searchParams.append('grup', this.currentGrup);  // 'grup' untuk grup
                
                // Menggunakan URL yang sudah dibuat
                const res = await fetch(url);
                
                const data = await res.json();
                this.autocompleteResults = Array.isArray(data) ? data : (data.data ?? []);
            } catch (err) {
                console.error('❌ Gagal mencari man power:', err);
                this.autocompleteResults = [];
            }
        },

        selectAfter(item) {
            this.autocompleteQuery = item.nama;
            this.selectedManpowerAfter = item.id;
            this.autocompleteResults = [];
        }
    }));
});

</script>




</x-app-layout>