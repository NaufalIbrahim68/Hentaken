<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- PERUBAHAN: Judul dinamis --}}
            {{ isset($log) ? 'Edit Data' : 'Buat Data' }} Henkaten Man Power
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Menampilkan pesan error validasi (Tidak berubah) --}}
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

                    {{-- Notifikasi sukses (Tidak berubah) --}}
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

                    {{-- PERUBAHAN: Action form dinamis --}}
                    <form action="{{ isset($log) ? route('activity.log.manpower.update', $log->id) : route('henkaten.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- PERUBAHAN: Tambah method PUT jika mode edit --}}
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        {{-- PERUBAHAN: Input tersembunyi sekarang mengambil data dari $log jika ada --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? $currentShift) }}">
                        <input type="hidden" name="grup" value="{{ old('grup', $log->grup ?? $currentGroup) }}">

                        {{-- Wrapper Alpine.js --}}
                        {{-- PERUBAHAN: 'old(...)' sekarang diisi default dari $log --}}
                        <div x-data="henkatenForm({
                        isEditing: {{ isset($log) ? 'true' : 'false' }},  {{-- <-- TAMBAHKAN INI --}}
                                oldShift: '{{ old('shift', $log->shift ?? $currentShift) }}',
                                oldGrup: '{{ old('grup', $log->grup ?? $currentGroup) }}',
                                oldLineArea: '{{ old('line_area', $log->line_area ?? '') }}',
                                oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                oldManPowerBeforeId: {{ old('man_power_id', $log->man_power_id ?? 'null') }},
                                oldManPowerBeforeName: '{{ old('nama', $log->manpowerBefore->nama ?? '') }}',
                                oldManPowerAfterId: {{ old('man_power_id_after', $log->man_power_id_after ?? 'null') }},
                                oldManPowerAfterName: '{{ old('nama_after', $log->manpowerAfter->nama ?? '') }}',
                                findManpowerUrl: '{{ route('henkaten.getManPower') }}',
                                searchManpowerUrl: '{{ route('manpower.search') }}',
                                findStationsUrl: '{{ route('henkaten.stations.by_line') }}'
                            })" x-init="init()">
                            
                            {{-- PERUBAHAN: Fieldset ini tidak lagi dinonaktifkan oleh grupError --}}
                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            {{-- Kolom Kiri --}}
<div>
    {{-- GRUP (HANYA TAMPIL DI MODE EDIT) --}}
    @if (isset($log))
        <div class="mb-4">
            <label for="grup" class="block text-sm font-medium text-gray-700">Grup</label>
            <select id="grup" name="grup" x-model="selectedGrup"
                @change="fetchManpowerBefore"
                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">-- Pilih Grup --</option>
                <option value="A">A</option>
                <option value="B">B</option>
            </select>
        </div>
    @endif
    {{-- AKHIR BLOK GRUP --}}
                                        {{-- LINE AREA --}}
                                        <div class="mb-4">
                                            <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
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
                                    </div> 
                                    
                                    {{-- Kolom Kanan --}}
                                    <div>
                                        <div class="mb-4">
                                            <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                            {{-- PERUBAHAN: value diisi dari $log --}}
                                            <input type="date" id="effective_date" name="effective_date"
value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"                                               
 class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                            {{-- PERUBAHAN: value diisi dari $log --}}
                                            <input type="date" id="end_date" name="end_date"
value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"                                                
class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                       <input type="time" id="time_start" name="time_start"
       value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                            {{-- PERUBAHAN: value diisi dari $log --}}
                                           <input type="time" id="time_end" name="time_end"
       value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Before & After (Tidak berubah, x-model akan mengisinya) --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Before (Otomatis) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="nama_before_display" class="text-gray-700 text-sm font-bold">Nama Karyawan Sebelum</label>
                                        <input type="text" id="nama_before_display" name="nama"
                                            x-model="manpowerBefore.nama" readonly
                                            class="w-full py-3 px-4 border rounded bg-gray-100 text-gray-600"
                                            placeholder="Nama Man Power Sebelum">
                                        <input type="hidden" name="man_power_id" x-model="manpowerBefore.id">
                                        <p class="text-xs text-gray-500 mt-2 italic">Data man power yang diganti (otomatis berdasarkan grup, line, & station)</p>
                                    </div>

                                    {{-- After (Autocomplete) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="nama_after" class="text-gray-700 text-sm font-bold">Nama Karyawan Sesudah</label>
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

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Kolom 1: Keterangan --}}
                                    <div>
                                        <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                        {{-- PERUBAHAN: value diisi dari $log --}}
                                        <textarea id="keterangan" name="keterangan" rows="6"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                    </div>

                                    {{-- Kolom 2: Syarat & Ketentuan (Tidak berubah) --}}
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Syarat & Ketentuan Lampiran</label>
                                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 text-sm text-gray-600 h-full">
                                            {{-- ... (Isi syarat & ketentuan tetap sama) ... --}}
                                            <p class="font-semibold mb-2">Dokumen yang wajib dilampirkan untuk Izin/Sakit:</p>
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>
                                                    <strong>Sakit:</strong> Wajib melampirkan Surat Keterangan Sakit (SKS) yang valid dari dokter atau klinik.
                                                </li>
                                                <li>
                                                    <strong>Izin Resmi:</strong> Wajib melampirkan surat izin yang telah disetujui oleh atasan (Supervisor/Foreman).
                                                </li>
                                                <li>
                                                    <strong>Darurat/Lainnya:</strong> Dokumen pendukung lain yang relevan (jika ada).
                                                </li>
                                            </ul>
                                            <p class="mt-3 italic text-xs">Pastikan lampiran foto/dokumen jelas dan dapat dibaca.</p>
                                        </div>
                                    </div>
                                </div>


                                {{-- Lampiran --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran"
                                        class="block text-gray-700 text-sm font-bold mb-2">Lampiran (Wajib untuk Izin/Sakit)</label>
                                    <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        {{-- PERUBAHAN: 'required' hanya jika BUKAN mode edit --}}
                                        {{ !isset($log) ? 'required' : '' }}>
                                    
                                    {{-- PERUBAHAN: Tampilkan lampiran saat ini jika ada --}}
                                    @if (isset($log) && $log->lampiran)
                                        <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                            <p class="text-sm text-gray-700 font-medium mb-1">Lampiran saat ini:</p>
                                            <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                                class="text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1-3a1 1 0 100 2h2a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                                                Lihat Lampiran ({{ basename($log->lampiran) }})
                                            </a>
                                            <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda ingin mengganti lampiran ini.</p>
                                        </div>
                                    @endif
                                </div>

                            </fieldset>
                            
                            {{-- Tombol --}}
                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                {{-- PERUBAHAN: Link Batal dinamis --}}
                                <a href="{{ isset($log) ? route('activity.log.manpower') : route('dashboard') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                    Batal
                                </a>
                                {{-- PERUBAHAN: Teks tombol dinamis --}}
                                <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                    {{ isset($log) ? 'Update Data' : 'Simpan Data' }}
                                </button>
                            </div>

                        </div> {{-- Akhir dari wrapper x-data --}}
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Alpine.js (PERUBAHAN PENTING PADA 'init') --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('henkatenForm', (config) => ({
            // STATE
            grupError: null, // Kita biarkan null, tidak ada cek lagi
            selectedGrup: config.oldGrup || '', // <-- TAMBAHKAN BARIS INI
            selectedLineArea: config.oldLineArea || '',
            selectedStation: config.oldStation || '',
            stationList: [],
            selectedManpowerAfter: config.oldManPowerAfterId || '',
            manpowerBefore: { id: config.oldManPowerBeforeId || '', nama: config.oldManPowerBeforeName || '' },
            autocompleteResults: [],
            autocompleteQuery: config.oldManPowerAfterName || '',

            // URL
            findManpowerUrl: config.findManpowerUrl,
            searchManpowerUrl: config.searchManpowerUrl,
            findStationsUrl: config.findStationsUrl,

            // INIT (PERUBAHAN: Dibuat 'async' untuk menangani mode edit)
       async init() {
    console.log('✅ Alpine initialized (Henkaten Man Power)');

    // Jika ada line_area, ambil stations dulu
    if (this.selectedLineArea) {
        await this.fetchStations(false);
    }

    // Kalau mode edit dan station sudah ada, jalankan fetchManpowerBefore()
    if (config.isEditing && this.selectedStation) {
        console.log('✳️ Mode Edit: Fetching manpower before berdasarkan data log');
        await this.fetchManpowerBefore();
    } 
    else if (!config.isEditing && this.selectedStation) {
        console.log('🏃 Mode Create: Menjalankan fetchManpowerBefore()');
        await this.fetchManpowerBefore();
    }
},


            // (Fungsi fetchStations, fetchManpowerBefore, searchAfter, selectAfter tetap sama persis seperti kode Anda)
            
            async fetchStations(resetStation = true) {
                if (!this.selectedLineArea) {
                    this.stationList = [];
                    this.selectedStation = '';
                    this.manpowerBefore = { id: '', nama: '' }; // Reset juga manpower before
                    return;
                }
                try {
                    const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                    const data = await res.json();
                    this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                    if (resetStation) {
                        this.selectedStation = '';
                        this.manpowerBefore = { id: '', nama: '' }; // Reset juga manpower before
                    }
                } catch (err) {
                    console.error(err);
                    this.stationList = [];
                }
            },

           async fetchManpowerBefore() {
            // GANTI INI:
            // if (!this.selectedStation || !this.selectedLineArea || !config.oldGrup) return;
            // MENJADI INI (gunakan this.selectedGrup):
            if (!this.selectedStation || !this.selectedLineArea || !this.selectedGrup) {
                console.warn('Menunggu Grup, Line, dan Station dipilih...');
                return;
            }

            try {
                const url = new URL(this.findManpowerUrl, window.location.origin);
                url.searchParams.append('station_id', this.selectedStation);
                url.searchParams.append('line_area', this.selectedLineArea);
                
                // GANTI INI:
                // url.searchParams.append('grup', config.oldGrup); 
                // MENJADI INI (gunakan this.selectedGrup):
                url.searchParams.append('grup', this.selectedGrup);

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
                
if (this.autocompleteQuery.length < 2 || !this.selectedGrup) {                    this.autocompleteResults = [];
                    return;
                }
                try {
                    const url = new URL(this.searchManpowerUrl, window.location.origin);
                    
                    // GANTI 'q' MENJADI 'query'
                    url.searchParams.append('query', this.autocompleteQuery);
                    
                    // Baris ini tidak akan berguna jika Anda tidak pakai Cara 1
                    url.searchParams.append('grup', this.selectedGrup); 
                    
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