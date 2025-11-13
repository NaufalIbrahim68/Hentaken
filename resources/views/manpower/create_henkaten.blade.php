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

                        {{-- PERUBAHAN: Input Shift tersembunyi, mengambil dari $log atau $currentShift --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? $currentShift) }}">
                        
<div x-data="henkatenForm({
    isEditing: {{ isset($log) ? 'true' : 'false' }},
    // TAMBAHAN: Kirim ID log saat ini jika mode edit
    logId: {{ isset($log) ? $log->id : 'null' }}, 
    
    oldGrup: '{{ old('grup', $log->grup ?? $currentGroup) }}',
    oldLineArea: '{{ old('line_area', $log->line_area ?? '') }}',
    oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
    
    oldManPowerBeforeId: {{ old('man_power_id', $log->man_power_id ?? 'null') }},
    oldManPowerBeforeName: '{{ old('nama', $log->nama ?? '') }}',
    
    oldManPowerAfterId: {{ old('man_power_id_after', $log->man_power_id_after ?? 'null') }},
    oldManPowerAfterName: '{{ old('nama_after', $log->nama_after ?? '') }}',

    findManpowerUrl: '{{ route('henkaten.getManPower') }}',
    searchManpowerUrl: '{{ route('manpower.search') }}',
    findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
    checkAfterUrl: '{{ route('henkaten.checkAfter') }}' 
})" x-init="init()">

                            
                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                {{-- Kolom Kiri --}}
                                <div>
                                    {{-- PERUBAHAN: Tampilkan dropdown Grup HANYA saat mode EDIT --}}
                                    @if (isset($log))
                                        <div class="mb-4">
                                            <label for="grup" class="block text-sm font-medium text-gray-700">Grup</label>
                                            <select id="grup" name="grup" x-model="selectedGrup"
                                                @change="fetchManpowerBefore" {{-- Mengubah grup akan me-refresh Man Power "Before" --}}
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- Pilih Grup --</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                            </select>
                                        </div>
                                    @else
                                        {{-- Mode CREATE: Grup diambil dari session & dikirim via hidden input --}}
                                        <input type="hidden" name="grup" value="{{ $currentGroup }}">
                                    @endif

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
                                    {{-- SERIAL NUMBER START --}}
                                    <div class="mb-4">
                                        <label for="serial_number_start" class="block text-sm font-medium text-gray-700">
                                            Serial Number Start
                                            {{-- Tanda Wajib (hanya di mode Edit) --}}
                                            @if(isset($log))
                                                <span class="text-red-500">*</span>
                                            @else
                                                <span class="text-gray-500 text-xs"></span>
                                            @endif
                                        </label>
                                        <input type="text" id="serial_number_start" name="serial_number_start"
                                            value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Masukkan serial number awal..."
                                            {{-- Atribut 'required' HANYA jika mode edit --}}
                                            {{ isset($log) ? 'required' : '' }}>
                                    </div>

                                    {{-- SERIAL NUMBER END --}}
                                    <div class="mb-4">
                                        <label for="serial_number_end" class="block text-sm font-medium text-gray-700">
                                            Serial Number End
                                            {{-- Tanda Wajib (hanya di mode Edit) --}}
                                            @if(isset($log))
                                                <span class="text-red-500">*</span>
                                            @else
                                                <span class="text-gray-500 text-xs"></span>
                                            @endif
                                        </label>
                                        <input type="text" id="serial_number_end" name="serial_number_end"
                                            value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Masukkan serial number akhir..."
                                            {{-- Atribut 'required' HANYA jika mode edit --}}
                                            {{ isset($log) ? 'required' : '' }}>
                                    </div>
                                </div> 
                                
                                {{-- Kolom Kanan (Tanggal & Waktu) --}}
                                <div>
                                    <div class="mb-4">
                                        <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                        {{-- PERUBAHAN: value diisi dari $log (diformat) atau old() --}}
                                        <input type="date" id="effective_date" name="effective_date"
                                            value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"                                
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
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
                                        <input type="time" id="time_end" name="time_end"
                                            value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            required>
                                    </div>
                                </div>
                                </div>

                                {{-- Before & After --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Before --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="nama_before_display" class="text-gray-700 text-sm font-bold">Nama Karyawan Sebelum</label>
                                        {{-- PERUBAHAN: Hapus name="nama". Data "Before" di-load dari x-data --}}
                                        <input type="text" id="nama_before_display"
                                            x-model="manpowerBefore.nama" readonly
                                            class="w-full py-3 px-4 border rounded bg-gray-100 text-gray-600"
                                            placeholder="Nama Man Power Sebelum">
                                        <input type="hidden" name="man_power_id" x-model="manpowerBefore.id">
                                        <p class="text-xs text-gray-500 mt-2 italic">Data man power yang diganti (otomatis berdasarkan grup, line, & station)</p>
                                    </div>

                                    {{-- After --}}
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

                                {{-- Keterangan & Syarat --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Kolom 1: Keterangan --}}
                                    <div>
                                        <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                        {{-- PERUBAHAN: value diisi dari $log atau old() --}}
                                        <textarea id="keterangan" name="keterangan" rows="6"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                    </div>

                                    {{-- Kolom 2: Syarat & Ketentuan (Tidak berubah) --}}
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Syarat & Ketentuan Lampiran</label>
                                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 text-sm text-gray-600 h-full">
                                            <p class="font-semibold mb-2">Dokumen yang wajib dilampirkan untuk Izin/Sakit:</p>
                                            <ul class="list-disc list-inside space-y-1">
                                                <li><strong>Sakit:</strong> Wajib melampirkan SKS.</li>
                                                <li><strong>Izin Resmi:</strong> Wajib melampirkan surat izin.</li>
                                                <li><strong>Darurat/Lainnya:</strong> Dokumen pendukung lain.</li>
                                            </ul>
                                            <p class="mt-3 italic text-xs">Pastikan lampiran jelas.</p>
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
                                                class="text-blue-600 hover:text-blue-800 hover:underline">
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
                                :disabled="!afterValid"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                {{ isset($log) ? 'Update Data' : 'Simpan Data' }}
                            </button>
                            <p x-show="!afterValid" class="text-red-500 text-sm mt-1">
                                Karyawan ini sudah dijadwalkan sebagai Man Power After untuk shift ini.
                            </p>


                            </div>

                        </div> {{-- Akhir dari wrapper x-data --}}
                    </form>

                </div>
            </div>
        </div>
    </div>

   <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('henkatenForm', (config) => ({
        selectedGrup: config.oldGrup || '',
        selectedLineArea: config.oldLineArea || '',
        selectedStation: config.oldStation || '',
        stationList: [],
        
        // TAMBAHAN: logId untuk pengecualian saat Edit
        logId: config.logId, 

        manpowerBefore: { id: config.oldManPowerBeforeId || '', nama: config.oldManPowerBeforeName || '' },
        selectedManpowerAfter: config.oldManPowerAfterId || '',
        autocompleteQuery: config.oldManPowerAfterName || '',
        autocompleteResults: [],
        afterValid: true, 

        findManpowerUrl: config.findManpowerUrl,
        searchManpowerUrl: config.searchManpowerUrl,
        findStationsUrl: config.findStationsUrl,
        checkAfterUrl: config.checkAfterUrl, 

        async init() {
            if (this.selectedLineArea) await this.fetchStations(false);
            if (!config.isEditing && this.selectedStation) await this.fetchManpowerBefore();
            
            // Tambahkan listener untuk memicu validasi saat tanggal berubah
            document.getElementById('effective_date')?.addEventListener('change', () => this.validateAfter());
            document.getElementById('end_date')?.addEventListener('change', () => this.validateAfter());
            
            // Panggil validateAfter() jika sedang mode edit dan data sudah ada
            if (config.isEditing && this.selectedManpowerAfter) {
                this.validateAfter();
            }
        },

        async fetchStations(resetStation = true) {
            if (!this.selectedLineArea) { this.stationList = []; this.selectedStation = ''; return; }
            try {
                const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                const data = await res.json();
                this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                if (resetStation) this.selectedStation = '';
                this.fetchManpowerBefore(); 
            } catch { this.stationList = []; }
        },

        async fetchManpowerBefore() {
            if (!this.selectedStation || !this.selectedLineArea || !this.selectedGrup) {
                this.manpowerBefore = { id: '', nama: '' };
                return;
            }
            try {
                const url = new URL(this.findManpowerUrl, window.location.origin);
                url.searchParams.append('station_id', this.selectedStation);
                url.searchParams.append('line_area', this.selectedLineArea);
                url.searchParams.append('grup', this.selectedGrup);

                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                this.manpowerBefore = data && data.nama ? { id: data.id, nama: data.nama } : { id: '', nama: '' };
            } catch { this.manpowerBefore = { id: '', nama: '' }; }
        },

        async searchAfter() {
            if (this.autocompleteQuery.length < 2 || !this.selectedGrup) { 
                this.autocompleteResults = []; 
                return; 
            }
            try {
                const url = new URL(this.searchManpowerUrl, window.location.origin);
                url.searchParams.append('query', this.autocompleteQuery);
                url.searchParams.append('grup', this.selectedGrup);

                const res = await fetch(url);
                const data = await res.json();
                const list = Array.isArray(data) ? data : (data.data ?? []);
                this.autocompleteResults = list;
            } catch { this.autocompleteResults = []; }
        },

        selectAfter(item) {
            this.autocompleteQuery = item.nama;
            this.selectedManpowerAfter = item.id;
            this.autocompleteResults = [];
            this.validateAfter(); // Panggil validasi setelah memilih
        },

        async validateAfter() {
            const effectiveDate = document.getElementById('effective_date')?.value;
            const endDate = document.getElementById('end_date')?.value;
            
            // 1. Cek Pre-requisites (ID Pengganti, URL, Tanggal Mulai & Akhir)
            if (!this.selectedManpowerAfter || !this.checkAfterUrl || !effectiveDate || !endDate) { 
                this.afterValid = true; 
                return; 
            }
            
            // 2. Cegah validasi jika pengganti sama dengan yang diganti (Jika ID diketahui)
            if (this.manpowerBefore.id && this.selectedManpowerAfter === this.manpowerBefore.id) {
                 this.afterValid = false;
                 return;
            }
            
            try {
                const url = new URL(this.checkAfterUrl, window.location.origin);
                
                // --- PERUBAHAN KRITIS: Mengirim ID, Grup, End Date, dan LOG ID (untuk pengecualian) ---
                url.searchParams.append('man_power_id_after', this.selectedManpowerAfter);
                url.searchParams.append('grup', this.selectedGrup);
                url.searchParams.append('shift', '{{ $currentShift }}');
                url.searchParams.append('effective_date', effectiveDate);
                url.searchParams.append('end_date', endDate);
                
                // Tambahkan ID log saat ini jika ada (mode edit)
                if (this.logId) { 
                    url.searchParams.append('ignore_log_id', this.logId);
                }
                // --------------------------------------------------------

                const res = await fetch(url);
                const data = await res.json();
                
                // Jika exists adalah TRUE, berarti ada konflik tanggal.
                this.afterValid = !data.exists;
            } catch (e) {
                console.error("Error validating 'After' Man Power:", e);
                this.afterValid = true; 
            }
        }
    }));
});
</script>

</x-app-layout>