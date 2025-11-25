<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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

                    {{-- Notifikasi sukses (TIDAK DIUBAH) --}}
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
    // =========================================================
    // LOGIKA PHP UNTUK MENYESUAIKAN TAMPILAN
    // =========================================================

    $formAction = isset($log)
        ? route('activity.log.machine.update', $log->id)
        : route('henkaten.machine.store');

    $userRole = Auth::user()->role ?? 'Guest';
    $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

    // Tentukan Line Area berdasarkan Role
    $predefinedLineArea = match ($userRole) {
        'Leader QC' => 'Incoming',
        'Leader PPIC' => 'Delivery',
        default => null,
    };

    // 🟢 PERBAIKAN: Gunakan nullsafe (?) untuk mengambil id_machines
    $logMachineId = $log?->id_machines ?? null;
    
    // Pastikan variabel $machinesToDisplay ada, walaupun kosong, jika Controller lupa mengirim
    $machinesToDisplay = $machinesToDisplay ?? collect([]); 

    // Tentukan Station ID Default
    $predefinedStationId = $predefinedStationId ?? 143; // Pastikan ini juga aman

    // Asumsi $lineAreas dikirim dari Controller
    $lineAreas = $lineAreas ?? collect(['Incoming', 'Delivery', 'Assembly', 'Machining']);

    // Asumsi $machineCategories dikirim dari Controller
    $machineCategories = $machineCategories ?? [];

@endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="shift" value="{{ old('shift', $log?->shift ?? ($currentShift ?? '')) }}">

                        {{-- 🟢 PERUBAHAN X-DATA UNTUK MACHINE ID & DATA MAPPING --}}
                        <div x-data="henkatenForm({
                            // 🟢 Perbaikan nullsafe untuk Line Area
                            oldLineArea: '{{ old('line_area', $log?->station?->line_area ?? '') }}', 
                            
                            // 🟢 Perbaikan nullsafe untuk Station ID
                            oldStation: {{ $isPredefinedRole ? ($log?->station_id ?? $predefinedStationId) : (old('station_id', $log?->station_id ?? 'null')) }},
                            
                            // 🟢 TAMBAHKAN oldMachineId
                            oldMachineId: {{ old('id_machines', $logMachineId ?? 'null') }}, 
                            
                            findStationsUrl: '{{ route('henkaten.stations.by_line') }}',

                            predefinedLineArea: '{{ $predefinedLineArea ?? '' }}',
                            isPredefinedRole: {{ $isPredefinedRole ? 'true' : 'false' }},
                            
                            // 🟢 KIRIM DATA MESIN UNTUK MAPPING
                            machinesData: @json($machinesToDisplay),
                        })" x-init="init()">

                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- Kolom Kiri --}}
                                    <div>

                                        @if ($isPredefinedRole)
                                            {{-- MODE QC/PPIC (INPUT OTOMATIS/STATIC) --}}

                                            {{-- LINE AREA (Static) --}}
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Line Area</label>
                                                <input type="text" value="{{ $predefinedLineArea }}" readonly
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                                <input type="hidden" name="line_area" value="{{ $predefinedLineArea }}">
                                            </div>

                                            {{-- STATION NAME (Hidden) --}}
                                            <input type="hidden" name="station_name_predefined" value="{{ $predefinedLineArea }}">

                                            {{-- STATION ID (Hidden) --}}
                                            <input type="hidden" name="station_id" value="{{ $log?->station_id ?? $predefinedStationId }}">

                                        @else
                                            {{-- MODE DEFAULT (INPUT DINAMIS) --}}

                                            {{-- LINE AREA --}}
                                            <div class="mb-4">
                                                <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                                <select id="line_area" name="line_area" x-model="selectedLineArea"
                                                        @change="fetchStations"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                                        <option value="">-- Pilih Line Area --</option>
                                                        @foreach ($lineAreas as $area)
                                                            <option value="{{ $area }}" @selected(old('line_area', $log?->station?->line_area ?? '') == $area)>
                                                                {{ $area }}
                                                            </option>
                                                        @endforeach
                                                </select>
                                            </div>

                                            {{-- STATION --}}
                                            <div class="mb-4">
                                                <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                                <select id="station_id" name="station_id" x-model="selectedStation" required
                                                        :disabled="!stationList.length && !selectedLineArea"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                        <option value="">-- Pilih Station --</option>

                                                        {{-- Fallback/Initial load --}}
                                                        @if (isset($stations) && !old('line_area'))
                                                            @foreach ($stations as $station)
                                                                <option value="{{ $station->id }}" @selected(old('station_id', $log?->station_id ?? '') == $station->id)>
                                                                    {{ $station->station_name }}
                                                                </option>
                                                            @endforeach
                                                        @endif

                                                        <template x-for="station in stationList" :key="station.id">
                                                            <option :value="station.id" x-text="station.station_name"></option>
                                                        </template>
                                                </select>
                                            </div>
                                        @endif


                                        {{-- 🟢 PERUBAHAN UTAMA: KATEGORI/MACHINE (Menggunakan Machine ID sebagai value, Category Name sebagai label) --}}
                                        <div class="mb-4">
                                            <label for="id_machines" class="block text-sm font-medium text-gray-700">Kategori Machines</label>
                                            <select id="id_machines" name="id_machines" x-model="selectedMachineId"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    required>
                                                <option value="">-- Pilih Kategori --</option>

                                                {{-- Gunakan data $machinesToDisplay untuk mendapatkan ID unik --}}
                                                @foreach ($machinesToDisplay->unique('machines_category') as $machine)
                                                    <option value="{{ $machine->id }}" 
                                                            data-category="{{ $machine->machines_category }}"
                                                            @selected(old('id_machines', $logMachineId) == $machine->id)>
                                                        {{ $machine->machines_category }} 
                                                    </option>
                                                @endforeach
                                            </select>
                                            
                                            {{-- 🟢 HIDDEN INPUT: Wajib ada untuk validasi 'category' di Controller --}}
                                         <input type="hidden" id="hidden_category" name="category" 
    x-bind:value="getCategoryName(selectedMachineId)"
    {{-- 🟢 HANYA GUNAKAN X-BIND. Nilai lama akan dihandle oleh x-bind saat init. --}}
    value="">
                                        </div>

                                        {{-- SERIAL NUMBER START & END (TIDAK DIUBAH) --}}
                                        <div class="mb-4">
                                            <label for="serial_number_start" class="block text-sm font-medium text-gray-700">
                                                Serial Number Start
                                                @if(isset($log)) <span class="text-red-500">*</span> @else <span class="text-gray-500 text-xs"></span> @endif
                                            </label>
                                            <input type="text" id="serial_number_start" name="serial_number_start"
                                                value="{{ old('serial_number_start', $log?->serial_number_start ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number awal..."
                                                {{ isset($log) ? 'required' : '' }}>
                                        </div>

                                        <div class="mb-4">
                                            <label for="serial_number_end" class="block text-sm font-medium text-gray-700">
                                                Serial Number End
                                                @if(isset($log)) <span class="text-red-500">*</span> @else <span class="text-gray-500 text-xs"></span> @endif
                                            </label>
                                            <input type="text" id="serial_number_end" name="serial_number_end"
                                                value="{{ old('serial_number_end', $log?->serial_number_end ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number akhir..."
                                                {{ isset($log) ? 'required' : '' }}>
                                        </div>
                                    </div>

                                    {{-- Kolom Kanan (Tanggal & Waktu) (TIDAK DIUBAH) --}}
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

                                {{-- Before & After untuk Machine (TIDAK DIUBAH) --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="before_value" class="text-gray-700 text-sm font-bold">Kondisi Sebelum</label>
                                        <input type="text" id="before_value" name="before_value"
                                                 value="{{ old('before_value', $log?->description_before ?? '') }}"
                                                 class="w-full py-3 px-4 border rounded bg-white text-gray-800"
                                                 placeholder="Deskripsi/Versi/Part No. Sebelum" required>
                                        <p class="text-xs text-gray-500 mt-2 italic">Deskripsikan kondisi sebelum perubahan.</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="after_value" class="text-gray-700 text-sm font-bold">Kondisi Sesudah</label>
                                        <input type="text" id="after_value" name="after_value"
                                                 value="{{ old('after_value', $log?->description_after ?? '') }}"
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
                                                 required>{{ old('keterangan', $log?->keterangan ?? '') }}</textarea>
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
                            </fieldset>

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

                        </div>
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
            selectedLineArea: config.isPredefinedRole ? config.predefinedLineArea : (config.oldLineArea || ''),
            selectedStation: config.oldStation || null,
            // 🟢 UBAH: Menggunakan Machine ID sebagai state utama
            selectedMachineId: config.oldMachineId || null, 
            
            stationList: [], 
            
            // 🟢 BARU: Data Mesin untuk Mapping
            machinesData: config.machinesData, 

            // FLAGS
            isPredefinedRole: config.isPredefinedRole,

            // URL
            findStationsUrl: config.findStationsUrl,

            // INIT
            async init() {
                console.log(`✅ Alpine initialized (Henkaten Machine - Mode: ${this.isPredefinedRole ? 'Predefined' : 'Dynamic'})`);
                
                // Konversi ID Mesin ke integer jika ada
                if (this.selectedMachineId && typeof this.selectedMachineId === 'string') {
                    this.selectedMachineId = parseInt(this.selectedMachineId);
                }

                // Jika mode dynamic DAN ada Line Area lama, fetch stations
                if (!this.isPredefinedRole && this.selectedLineArea) {
                    await this.fetchStations(false);
                }

                // Jika role predefined dan mode edit, pastikan selectedStation di-set
                if (this.isPredefinedRole && config.oldStation) {
                    this.selectedStation = config.oldStation;
                }
            },

            // 🟢 FUNGSI BARU: Mencari Nama Kategori berdasarkan Machine ID
            getCategoryName(machineId) {
                if (!machineId) return '';
                // Cari objek mesin berdasarkan ID yang dipilih
                // Pastikan machinesData adalah array/collection
                const machine = this.machinesData.find(m => m.id == machineId);
                // Kembalikan nama kategori
                return machine ? machine.machines_category : '';
            },

            // FETCH STATIONS (Hanya untuk mode dinamis)
            async fetchStations(resetStation = true) {
                if (this.isPredefinedRole) return;

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