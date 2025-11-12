<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- UBAH: Judul dinamis --}}
            {{ isset($log) ? __('Edit Data Henkaten Machine') : __('Buat Data Henkaten Machine') }}
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

                    @php
                        $formAction = isset($log)
                            ? route('activity.log.machine.update', $log->id)
                            : route('henkaten.machine.store');
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}">

                        <div x-data="henkatenForm({
                                 oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
                                 oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                 oldCategory: '{{ old('category', $log->category ?? '') }}',
                                 findStationsUrl: '{{ route('henkaten.stations.by_line') }}'
                             })" x-init="init()">

                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- Kolom Kiri --}}
                                    <div>
                                        {{-- LINE AREA --}}
                                        <div class="mb-4">
                                            <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                            <select id="line_area" name="line_area" x-model="selectedLineArea"
                                                    @change="fetchStations"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- Pilih Line Area --</option>
                                                @foreach ($lineAreas as $area)
                                                    <option value="{{ $area }}" @selected(old('line_area', $log->station->line_area ?? '') == $area)>
                                                        {{ $area }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- STATION --}}
                                        <div class="mb-4">
                                            <label for="station_id"
                                                   class="block text-sm font-medium text-gray-700">Station</label>
                                            <select id="station_id" name="station_id" x-model="selectedStation"
                                                    :disabled="!stationList.length"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">-- Pilih Station --</option>
                                                <template x-for="station in stationList" :key="station.id">
                                                    <option :value="station.id" x-text="station.station_name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- Kategori Henkaten Machine --}}
                                        <div class="mb-4">
                                            <label for="category" class="block text-sm font-medium text-gray-700">Kategori Machines</label>
                                            <select id="category" name="category" x-model="selectedCategory"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    required>
                                                <option value="">-- Pilih Kategori --</option>
                                                <option value="Program" @selected(old('category', $log->category ?? '') == 'Program')>Program</option>
                                                <option value="Machine & Jig" @selected(old('category', $log->category ?? '') == 'Machine & Jig')>Machine & Jig</option>
                                                <option value="Equipment" @selected(old('category', $log->category ?? '') == 'Equipment')>Equipment</option>
                                                <option value="Camera" @selected(old('category', $log->category ?? '') == 'Camera')>Camera</option>
                                            </select>
                                        </div>
                                        
                                        {{-- 1. TAMBAHAN: Supplier Part Number Start --}}
                                        <div class="mb-4">
                                            <label for="serial_number_start" class="block text-gray-700 text-sm font-bold mb-2">Supplier Part Number Start</label>
                                            <input type="text" id="serial_number_start" name="serial_number_start"
                                                   value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                   {{-- Logika: Wajib diisi hanya saat EDIT --}}
                                                   {{ isset($log) ? 'required' : '' }}>
                                        </div>
                                
                                        {{-- 2. TAMBAHAN: Supplier Part Number End --}}
                                        <div class="mb-4">
                                            <label for="serial_number_end" class="block text-gray-700 text-sm font-bold mb-2">Supplier Part Number End</label>
                                            <input type="text" id="serial_number_end" name="serial_number_end"
                                                   value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                   {{-- Logika: Wajib diisi hanya saat EDIT --}}
                                                   {{ isset($log) ? 'required' : '' }}>
                                        </div>

                                    </div>

                                    {{-- Kolom Kanan (Tanggal & Waktu) --}}
                                    <div>
                                        <div class="mb-4">
                                            <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                            <input type="date" id="effective_date" name="effective_date"
                                                   value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                            <input type="date" id="end_date" name="end_date"
                                                   value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                            <input type="time" id="time_start" name="time_start"
                                                   value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                            <input type="time" id="time_end" name="time_end"
                                                   value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Before & After untuk Machine --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="before_value" class="text-gray-700 text-sm font-bold">Kondisi Sebelum</label>
                                        <input type="text" id="before_value" name="before_value"
                                               value="{{ old('before_value', $log->description_before ?? '') }}"
                                               class="w-full py-3 px-4 border rounded bg-white text-gray-800"
                                               placeholder="Deskripsi/Versi/Part No. Sebelum" required>
                                        <p class="text-xs text-gray-500 mt-2 italic">Deskripsikan kondisi sebelum perubahan.</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="after_value" class="text-gray-700 text-sm font-bold">Kondisi Sesudah</label>
                                        <input type="text" id="after_value" name="after_value"
                                               value="{{ old('after_value', $log->description_after ?? '') }}"
                                               autocomplete="off" class="w-full py-3 px-4 border rounded"
                                               placeholder="Deskripsi/Versi/Part No. Sesudah" required>
                                        <p class="text-xs text-green-600 mt-2 italic">Deskripsikan kondisi setelah perubahan.</p>
                                    </div>
                                </div>

                                {{-- Keterangan --}}
                                <div class="mb-6 mt-6">
                                    <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                    <textarea id="keterangan" name="keterangan" rows="4"
                                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                              placeholder="Jelaskan alasan perubahan machine/program..."
                                              required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                </div>

                                {{-- Lampiran --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                                    <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                           class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                           {{ isset($log) ? '' : 'required' }}>

                                    @if(isset($log) && $log->lampiran)
                                        <div class="mt-2 text-sm text-gray-600">
                                            <p>File saat ini:
                                                <a href="{{ asset('storage/' . $log->lampiran) }}"
                                                   target="_blank"
                                                   class="text-blue-600 hover:underline font-medium">
                                                   Lihat Lampiran
                                                </a>
                                            </p>
                                            <p class="text-xs italic text-gray-500">Kosongkan input file jika tidak ingin mengubah lampiran.</p>
                                        </div>
                                    @endif
                                </div>
                            </fieldset>

                            {{-- Tombol (DI LUAR FIELDSET) --}}
                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                <a href="{{ route('activity.log.machine') }}"
                                   class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                    Batal
                                </a>
                                <button type="submit"
                                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                    {{ isset($log) ? 'Simpan Perubahan' : 'Simpan Data' }}
                                </button>
                            </div>

                        </div> {{-- Akhir dari wrapper x-data --}}
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
 document.addEventListener('alpine:init', () => {
        Alpine.data('henkatenForm', (config) => ({
            // STATE
            selectedLineArea: config.oldLineArea || '',
            selectedStation: config.oldStation || null, 
            selectedCategory: config.oldCategory || null, 
            stationList: [],

            // URL
            findStationsUrl: config.findStationsUrl,

            // INIT
            async init() {
                console.log('✅ Alpine initialized (Henkaten Machine)');
                if (this.selectedLineArea) {
                    await this.fetchStations(false);
                }
            },

            async fetchStations(resetStation = true) {
                if (!this.selectedLineArea) {
                    this.stationList = [];
                    this.selectedStation = null;
                    return;
                }
                if(resetStation) {
                    this.selectedStation = null;
                }
                
                try {
                    const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                    const data = await res.json();
                    this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                } catch (err) {
                    console.error('❌ Gagal mengambil stations:', err);
                    this.stationList = [];
                }
            },

        }));
    });
    </script>
</x-app-layout>