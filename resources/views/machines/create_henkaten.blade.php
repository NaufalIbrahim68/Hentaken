<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- DIUBAH: Judul Dinamis --}}
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

                    {{-- DIUBAH: Form action dinamis --}}
                    @php
                        $formAction = isset($log)
                            ? route('activity.log.machine.update', $log->id)
                            : route('henkaten.machine.store');
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- DIUBAH: Tambah method spoofing jika edit --}}
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        {{-- DIUBAH: Input tersembunyi mengambil value dari $log jika ada --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}">
                        <input type="hidden" name="grup" value="{{ old('grup', $log->grup ?? ($currentGroup ?? '')) }}">

                        {{-- Wrapper Alpine.js --}}
                        {{-- DIUBAH: Konfigurasi x-data mengambil dari $log jika ada --}}
                        <div x-data="henkatenForm({
                                oldShift: '{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}',
                                oldGrup: '{{ old('grup', $log->grup ?? ($currentGroup ?? '')) }}',
                                oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
                                oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                oldCategory: '{{ old('category', $log->category ?? '') }}',
                                findStationsUrl: '{{ route('henkaten.stations.by_line') }}'
                            })" x-init="init()">

                            {{-- ========================================================== --}}
                            {{-- Menampilkan error jika Grup/Shift dari Sesi tidak ada --}}
                            {{-- ========================================================== --}}
                            <template x-if="grupError">
                                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md"
                                    role="alert">
                                    <p class="font-bold">Sesi Tidak Valid</p>
                                    <p x-text="grupError"></p>
                                </div>
                            </template>

                            {{-- ========================================================== --}}
                            {{-- Fieldset untuk menonaktifkan form jika ada error --}}
                            {{-- ========================================================== --}}
                            <fieldset :disabled="grupError">

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
                                                    {{-- DIUBAH: Tambah @selected untuk memuat value saat edit --}}
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
                                                {{-- Alpine akan mengisi ini berdasarkan 'oldStation' dari config --}}
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
                                                {{-- DIUBAH: Tambah @selected untuk memuat value saat edit --}}
                                                <option value="Program" @selected(old('category', $log->category ?? '') == 'Program')>Program</option>
                                                <option value="Machine & Jig" @selected(old('category', $log->category ?? '') == 'Machine & Jig')>Machine & Jig</option>
                                                <option value="Equipment" @selected(old('category', $log->category ?? '') == 'Equipment')>Equipment</option>
                                                <option value="Camera" @selected(old('category', $log->category ?? '') == 'Camera')>Camera</option>
                                            </select>
                                        </div>

                                    </div>

                                    {{-- Kolom Kanan (Tanggal & Waktu) --}}
                                    <div>
                                        <div class="mb-4">
                                            <label for="effective_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                            {{-- DIUBAH: Isi value dari $log jika ada --}}
                                            <input type="date" id="effective_date" name="effective_date"
                                                value="{{ old('effective_date', $log->effective_date ?? '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="end_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                            {{-- DIUBAH: Isi value dari $log jika ada --}}
                                            <input type="date" id="end_date" name="end_date"
                                                value="{{ old('end_date', $log->end_date ?? '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_start"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                            {{-- DIUBAH: Isi value dari $log jika ada --}}
                                            <input type="time" id="time_start" name="time_start"
                                                value="{{ old('time_start', $log->time_start ?? '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_end"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                            {{-- DIUBAH: Isi value dari $log jika ada --}}
                                            <input type="time" id="time_end" name="time_end"
                                                value="{{ old('time_end', $log->time_end ?? '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Before & After untuk Machine --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    {{-- Before (Manual Input) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="before_value" class="text-gray-700 text-sm font-bold">Kondisi Sebelum</label>
                                        {{-- DIUBAH: Isi value dari $log jika ada --}}
                                        <input type="text" id="before_value" name="before_value"
                                            value="{{ old('before_value', $log->before_value ?? '') }}"
                                            class="w-full py-3 px-4 border rounded bg-white text-gray-800"
                                            placeholder="Deskripsi/Versi/Part No. Sebelum" required>
                                        <p class="text-xs text-gray-500 mt-2 italic">Deskripsikan kondisi sebelum perubahan.</p>
                                    </div>

                                    {{-- After (Manual Input) --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="after_value" class="text-gray-700 text-sm font-bold">Kondisi Sesudah</label>
                                        {{-- DIUBAH: Isi value dari $log jika ada --}}
                                        <input type="text" id="after_value" name="after_value"
                                            value="{{ old('after_value', $log->after_value ?? '') }}"
                                            autocomplete="off" class="w-full py-3 px-4 border rounded"
                                            placeholder="Deskripsi/Versi/Part No. Sesudah" required>
                                        <p class="text-xs text-green-600 mt-2 italic">Deskripsikan kondisi setelah perubahan.</p>
                                    </div>
                                </div>

                                {{-- Keterangan --}}
                                <div class="mb-6 mt-6">
                                    <label for="keterangan"
                                        class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                    {{-- DIUBAH: Isi value dari $log jika ada --}}
                                    <textarea id="keterangan" name="keterangan" rows="4"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                        placeholder="Jelaskan alasan perubahan machine/program..."
                                        required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                </div>

                                {{-- Lampiran --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran"
                                        class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                                    <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        {{-- DIUBAH: 'required' hanya saat membuat baru --}}
                                        {{ isset($log) ? '' : 'required' }}>

                                    {{-- DIUBAH: Tampilkan file saat ini jika sedang edit --}}
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
                            {{-- ========================================================== --}}
                            {{-- AKHIR: Fieldset --}}
                            {{-- ========================================================== --}}


                            {{-- Tombol (DI LUAR FIELDSET) --}}
                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                {{-- DIUBAH: Link Batal ke halaman index log machine --}}
                                <a href="{{ route('activity.log.machine') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                    Batal
                                </a>
                                <button type="submit"
                                    :disabled="grupError"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md"
                                    :class="{ 'opacity-50 cursor-not-allowed': grupError }">
                                    {{-- DIUBAH: Teks tombol dinamis --}}
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
            grupError: null,
            selectedLineArea: config.oldLineArea || '',
            selectedStation: config.oldStation || '',
            selectedCategory: config.oldCategory || '',
            stationList: [],

            // URL
            findStationsUrl: config.findStationsUrl,

            // INIT
            init() {
                console.log('✅ Alpine initialized (Henkaten Machine)');

                {{-- DIUBAH: Pengecekan sesi hanya dilakukan saat mode 'create' --}}
                @if(!isset($log))
                // Cek jika grup/shift dari sesi tidak ada
                if (!'{{ $currentGroup }}' || !'{{ $currentShift }}') {
                    this.grupError = 'Data Grup atau Shift tidak ditemukan di Sesi. Harap logout dan login kembali.';
                    console.error(this.grupError);
                }
                @endif

                // Jika ada old data line area (baik dari 'edit' atau 'create' yg error),
                // fetch station-nya
                if (this.selectedLineArea) {
                    // false = jangan reset selectedStation, biarkan terisi 'oldStation' dari config
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
                    if (resetStation) {
                        this.selectedStation = '';
                    }
                    // Jika tidak reset, 'selectedStation' akan otomatis terikat ke old value
                } catch (err) {
                    console.error('❌ Gagal mengambil stations:', err);
                    this.stationList = [];
                }
            },

        }));
    });
    </script>
</x-app-layout>
