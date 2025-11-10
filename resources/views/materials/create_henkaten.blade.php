<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- DIUBAH: Judul Dinamis --}}
            {{ isset($log) ? __('Edit Data Henkaten Material') : __('Buat Data Henkaten Material') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Menampilkan pesan error validasi --}}
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
                        <div
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 3000)"
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative"
                            role="alert"
                        >
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button
                                @click="show = false"
                                class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold"
                            >
                                &times;
                            </button>
                        </div>
                    @endif

                    {{-- DIUBAH: Form action dinamis --}}
                    @php
                        $formAction = isset($log)
                            ? route('activity.log.material.update', $log->id)
                            : route('henkaten.material.store');
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- DIUBAH: Tambah method spoofing jika edit --}}
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        {{-- Input 'redirect_to' bisa dihapus jika tidak digunakan di controller update --}}
                        <input type="hidden" name="redirect_to" value="{{ route('henkaten.material.create') }}">

                        {{-- DIUBAH: Input Shift tersembunyi mengambil data dari $log jika ada --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}">


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
     x-data="dependentDropdowns({
        oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
        oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
        oldMaterial: {{ old('material_id', $log->material_id ?? 'null') }},
        findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
        findMaterialsUrl: '{{ route('henkaten.materials.by_station') }}'
     })"
     x-init="init()">
                            {{-- Kolom Kiri --}}
                            <div>
                                <div class="mb-4">
                                    <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                    <select id="line_area" name="line_area" required
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                            x-model="selectedLineArea"
                                            @change="fetchStations()">
                                        <option value="">-- Pilih Line Area --</option>

                                        @foreach ($lineAreas as $area)
                                            <option value="{{ $area }}"
                                                {{-- DIUBAH: Tambah @selected --}}
                                                @selected(old('line_area', $log->station->line_area ?? '') == $area)>
                                                {{ $area }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                    <select id="station_id" name="station_id" required
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                            x-model="selectedStation"
                                            @change="fetchMaterials()"
                                            :disabled="stationList.length === 0">

                                        <option value="">-- Pilih Station --</option>

                                        <template x-for="station in stationList" :key="station.id">
                                            <option :value="station.id"
                                                    :selected="station.id == selectedStation"
                                                    x-text="station.station_name"></option>
                                        </template>

                                    </select>
                                </div>

                                {{-- Dropdown Material berdasarkan Station --}}
                                <div class="mb-4">
                                    <label for="material_id" class="block text-sm font-medium text-gray-700">Material</label>
                                    <select id="material_id" name="material_id" required
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                            x-model="selectedMaterial"
                                            :disabled="materialList.length === 0">

                                        <option value="">-- Pilih Material --</option>

                                        <template x-for="material in materialList" :key="material.id">
                                            <option :value="material.id"
                                                    :selected="material.id == selectedMaterial"
                                                    x-text="material.material_name"></option> {{-- Pastikan 'material_name' --}}
                                        </template>

                                    </select>
                                </div>
                            </div>

                            {{-- Kolom Kanan  --}}
                            <div>
                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    {{-- DIUBAH: Isi value dari $log --}}
                                    <input type="date" id="effective_date" name="effective_date"
value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"                                          
 class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                    {{-- DIUBAH: Isi value dari $log --}}
                                    <input type="date" id="end_date" name="end_date"
value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '')  }}"                                         
class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required> 
                                </div>

                                <div class="mb-4">
                                    <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                    {{-- DIUBAH: Isi value dari $log --}}
                                    <input type="time" id="time_start" name="time_start"
                                          value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                    {{-- DIUBAH: Isi value dari $log --}}
                                    <input type="time" id="time_end" name="time_end"
value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>
                            </div>
                        </div>

                        {{-- Before & After untuk Material --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sebelum</label>
                                {{-- DIUBAH: Isi value dari $log --}}
                                <input type="text" name="description_before"
                                       value="{{ old('description_before', $log->description_before ?? '') }}"
                                       class="w-full py-3 px-4 border rounded"
                                       placeholder="Deskripsi kondisi/part sebelum perubahan..."
                                       required>

                                <p class="text-xs text-gray-500 mt-2 italic">Deskripsi kondisi sebelum perubahan</p>
                            </div>

                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sesudah</label>
                                {{-- DIUBAH: Isi value dari $log --}}
                                <input type="text" name="description_after"
                                       value="{{ old('description_after', $log->description_after ?? '') }}"
                                       class="w-full py-3 px-4 border rounded"
                                       placeholder="Deskripsi kondisi/part setelah perubahan..."
                                       required>

                                <p class="text-xs text-green-600 mt-2 italic">Deskripsi kondisi setelah perubahan</p>
                            </div>
                        </div>

                        {{-- BLOK KETERANGAN --}}
                        <div class="mb-6 mt-6">
                            <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                            {{-- DIUBAH: Isi value dari $log --}}
                            <textarea id="keterangan" name="keterangan" rows="4"
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                      placeholder="Jelaskan alasan perubahan material..."
                                      required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                        </div>

                        {{-- Lampiran --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                   class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   {{-- DIUBAH: 'required' hanya saat create --}}
                                   {{ isset($log) ? '' : 'required' }}>

                            {{-- DIUBAH: Tampilkan file saat ini jika edit --}}
                            @if(isset($log) && $log->lampiran)
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>File saat ini:
                                        <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                           class="text-blue-600 hover:underline font-medium">
                                           Lihat Lampiran
                                        </a>
                                    </p>
                                    <p class="text-xs italic text-gray-500">Kosongkan input file jika tidak ingin mengubah lampiran.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Tombol --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            {{-- DIUBAH: Link Batal ke index log --}}
                            <a href="{{ route('activity.log.material') }}"
                               class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                Batal
                            </a>
                            <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                {{-- DIUBAH: Teks tombol dinamis --}}
                                {{ isset($log) ? 'Simpan Perubahan' : 'Simpan Data' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

 {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    {{-- 
      PERBAIKAN TOTAL: Mengadopsi logic 'async init'
      Disesuaikan untuk 3-level dropdown (Line -> Station -> Material)
    --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dependentDropdowns', (config) => ({
                
                // --- Properti Data (STATE) ---
                selectedLineArea: config.oldLineArea || '',
                selectedStation: config.oldStation || null,
                selectedMaterial: config.oldMaterial || null,
                stationList: [], // Selalu mulai kosong
                materialList: [], // Selalu mulai kosong

                // --- URL ---
                findStationsUrl: config.findStationsUrl,
                findMaterialsUrl: config.findMaterialsUrl,

                // --- FUNGSI INIT (dijalankan oleh x-init) ---
                async init() {
                    console.log('✅ Alpine Dropdowns Initialized (Material)');
                    
                    // 1. Jika line area ada (mode edit), fetch stations
                    if (this.selectedLineArea) {
                        console.log('🔁 Mode Edit: Mengambil stations untuk line:', this.selectedLineArea);
                        await this.fetchStations(false); // false = jangan reset selectedStation

                        // 2. Jika station juga ada (mode edit), fetch materials
                        if (this.selectedStation) {
                            console.log('🔁 Mode Edit: Mengambil materials untuk station:', this.selectedStation);
                            await this.fetchMaterials(false); // false = jangan reset selectedMaterial
                        }
                    }
                },

                // --- FUNGSI FETCH STATIONS (Dipakai oleh init() dan @change) ---
                async fetchStations(resetStation = true) {
                    if(resetStation) {
                        this.selectedStation = null;
                        this.selectedMaterial = null; // Reset material
                        this.materialList = [];      // Reset material list
                    }
                    this.stationList = [];

                    if (!this.selectedLineArea) return;

                    try {
                        const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                        const data = await res.json();
                        this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                    } catch (err) {
                        console.error('Gagal fetch stations:', err);
                        this.stationList = [];
                    }
                },

                // --- FUNGSI FETCH MATERIALS (Dipakai oleh init() dan @change) ---
                async fetchMaterials(resetMaterial = true) {
                    if(resetMaterial) {
                        this.selectedMaterial = null;
                    }
                    this.materialList = [];

                    if (!this.selectedStation) return;

                    try {
                        const res = await fetch(`${this.findMaterialsUrl}?station_id=${this.selectedStation}`);
                        const data = await res.json();
                        this.materialList = Array.isArray(data) ? data : (data.data ?? []);
                    } catch (err) {
                        console.error('Gagal fetch materials:', err);
                        this.materialList = [];
                    }
                }
            }));
        });
    </script>
</x-app-layout>
