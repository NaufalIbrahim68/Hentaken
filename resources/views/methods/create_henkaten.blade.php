<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- UBAH: Judul dinamis --}}
            {{ isset($log) ? __('Edit Data Henkaten Method') : __('Buat Data Henkaten Method') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Menampilkan pesan error validasi & notifikasi sukses (TIDAK DIUBAH) --}}
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

                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative" role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false" class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">&times;</button>
                        </div>
                    @endif

                    @php
                        // =========================================================
                        // DEFINISI VARIABEL YANG DIPERLUKAN (ASUMSI DARI CONTROLLER)
                        // =========================================================
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

                        // ASUMSI: $methodList (daftar method name unik) dikirim dari Controller
                        // Fallback jika tidak ada, gunakan array kosong
                        $methodList = $methodList ?? [];
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}">

                        {{-- Wrapper Alpine untuk dependent dropdowns --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
                            x-data="dependentDropdowns({
                                oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
                                oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                oldMethod: '{{ old('method_id', $log->method_id ?? 'null') }}', // Asumsi ada old method ID
                                findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                                findMethodsUrl: '{{ route('henkaten.methods.by_station') }}', // Asumsi ada endpoint untuk method
                                
                                // VARIABEL DARI CONTROLLER UNTUK MODE PREDEFINED
                                predefinedLineArea: '{{ $predefinedLineArea ?? '' }}', 
                                isPredefinedRole: {{ $isPredefinedRole ? 'true' : 'false' }},
                            })"
                            x-init="init()">

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

    {{-- STATION NAME (Hidden, untuk pencarian di Controller) --}}
    <input type="hidden" name="station_name_predefined" value="{{ $predefinedLineArea }}"> 
    
    {{-- STATION ID & METHOD ID (Hidden, NILAI AKAN DIISI CONTROLLER) --}}
    <input type="hidden" name="station_id" value="{{ old('station_id', $log->station_id ?? '') }}">
    <input type="hidden" name="method_id" value="{{ old('method_id', $log->method_id ?? '') }}">
    
    {{-- NEW: Dropdown Method Name (untuk QC/PPIC) --}}
    <div class="mb-4">
        <label for="methods_name_input" class="block text-sm font-medium text-gray-700">Nama Method</label>
        {{-- Menggunakan nama berbeda agar tidak bentrok dengan mode dinamis --}}
        <select id="methods_name_input" name="methods_name" required
                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                x-model="selectedMethodName">

            <option value="">-- Pilih Method --</option>
            
            {{-- List method statis (Asumsi $predefinedMethodNames dikirim dari Controller) --}}
            @foreach ($methodList as $methodName)
                <option value="{{ $methodName }}" 
                    @selected(old('methods_name', $log->methods_name ?? '') == $methodName)>
                    {{ $methodName }}
                </option>
            @endforeach
        </select>
    </div>
    
@else
                                
                                    
                                    {{-- LINE AREA --}}
                                    <div class="mb-4">
                                        <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <select id="line_area" name="line_area" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedLineArea"
                                                @change="fetchStations()">
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
                                        <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                        <select id="station_id" name="station_id" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedStation"
                                                @change="fetchMethods()"
                                                :disabled="stationList.length === 0">

                                            <option value="">-- Pilih Station --</option>
                                            @if (isset($stations) && $stations->isNotEmpty() && !old('line_area'))
                                                @foreach ($stations as $station)
                                                    <option value="{{ $station->id }}" @selected(old('station_id', $log->station_id ?? '') == $station->id)>
                                                        {{ $station->station_name }}
                                                    </option>
                                                @endforeach
                                            @endif

                                            <template x-for="station in stationList" :key="station.id">
                                                <option :value="station.id" x-text="station.station_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    
                                    {{-- NEW: Dropdown Method Name (untuk role lain) --}}
                                    <div class="mb-4">
                                        <label for="methods_name" class="block text-sm font-medium text-gray-700">Nama Method</label>
                                        <select id="methods_name" name="methods_name" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedMethodName"
                                                :disabled="methodList.length === 0">

                                            <option value="">-- Pilih Method --</option>
                                            <template x-for="method in methodList" :key="method.id">
                                                <option :value="method.id" x-text="method.methods_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                @endif
                                
                                {{-- SERIAL NUMBER START & END (TIDAK DIUBAH) --}}
                                <div class="mb-4">
                                    <label for="serial_number_start" class="block text-sm font-medium text-gray-700">
                                        Serial Number Start
                                        @if(isset($log))<span class="text-red-500">*</span>@else<span class="text-gray-500 text-xs"></span>@endif
                                    </label>
                                    <input type="text" id="serial_number_start" name="serial_number_start"
                                        value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Masukkan serial number awal..."
                                        {{ isset($log) ? 'required' : '' }}>
                                </div>

                                <div class="mb-4">
                                    <label for="serial_number_end" class="block text-sm font-medium text-gray-700">
                                        Serial Number End
                                        @if(isset($log))<span class="text-red-500">*</span>@else<span class="text-gray-500 text-xs"></span>@endif
                                    </label>
                                    <input type="text" id="serial_number_end" name="serial_number_end"
                                        value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
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
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
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

                        {{-- Before & After (Keterangan) (TIDAK DIUBAH) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sebelum</label>
                                <textarea id="keterangan" name="keterangan" rows="4"
                                    placeholder="Jelaskan kondisi method sebelum perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2 italic">Data method sebelum perubahan</p>
                            </div>

                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md">
                                <label for="keterangan_after" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sesudah Pergantian</label>
                                <textarea id="keterangan_after" name="keterangan_after" rows="4"
                                    placeholder="Jelaskan kondisi method setelah perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan_after', $log->keterangan_after ?? '') }}</textarea>
                                <p class="text-xs text-green-600 mt-2 italic">Data method setelah perubahan</p>
                            </div>
                        </div>

                        {{-- Lampiran & Tombol (TIDAK DIUBAH) --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                {{ isset($log) ? '' : 'required' }}>

                            @if(isset($log) && $log->lampiran)
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>File saat ini:
                                        <a href="{{ asset('storage/'. $log->lampiran) }}" target="_blank"
                                            class="text-blue-600 hover:underline font-medium">
                                            Lihat Lampiran
                                        </a>
                                    </p>
                                    <p class="text-xs italic text-gray-500">Kosongkan input file jika tidak ingin mengubah lampiran.</p>
                                </div>
                            @endif
                        </div>

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

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dependentDropdowns', (config) => ({
                
                // --- STATE ---
                selectedLineArea: config.isPredefinedRole ? config.predefinedLineArea : (config.oldLineArea || ''),
                selectedStation: config.oldStation || null,
                selectedMethodName: config.oldMethodName || null, 
                stationList: [], 
                methodList: [], // Baru: Data list method (Model objects)

                // --- FLAGS/URL ---
                isPredefinedRole: config.isPredefinedRole,
                findStationsUrl: config.findStationsUrl,
                findMethodsUrl: config.findMethodsUrl, // Baru

                // --- INIT ---
                async init() {
                    console.log('✅ Alpine Dropdowns Initialized (Method)');
                    
                    if (!this.isPredefinedRole) {
                        // MODE DINAMIS (Role lain)
                        if (this.selectedLineArea) {
                            await this.fetchStations(false); 
                            if (this.selectedStation) {
                                await this.fetchMethods(false); 
                            }
                        }
                    } else {
                         // MODE PREDEFINED (QC/PPIC)
                         // Karena station_id di hidden, kita harus memastikan selectedStation diisi di mode edit
                        if (config.oldStation) {
                            this.selectedStation = config.oldStation;
                        }
                    }
                },

                // --- FETCH STATIONS (Semua mode, tapi hanya aktif di non-predefined) ---
                async fetchStations(resetStation = true) {
                    if (this.isPredefinedRole) return;
                    
                    if (!this.selectedLineArea) {
                        this.stationList = [];
                        this.selectedStation = null;
                        this.methodList = [];
                        this.selectedMethodName = null;
                        return;
                    }
                    if(resetStation) {
                        this.selectedStation = null;
                        this.methodList = [];
                        this.selectedMethodName = null; 
                    }
                    try {
                        const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                        const data = await res.json();
                        this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                    } catch (err) {
                        console.error('Gagal fetch stations:', err);
                        this.stationList = [];
                    }
                },

                // --- FETCH METHODS (Hanya untuk MODE DINAMIS) ---
                async fetchMethods(resetMethod = true) {
                    if (this.isPredefinedRole) return;
                    
                    if(resetMethod) {
                        this.selectedMethodName = null;
                    }
                    this.methodList = [];

                    if (!this.selectedStation) return;

                    try {
                        // Asumsi endpoint mengembalikan daftar Method berdasarkan station_id
                        const res = await fetch(`${this.findMethodsUrl}?station_id=${this.selectedStation}`);
                        const data = await res.json();
                        this.methodList = Array.isArray(data) ? data : (data.data ?? []);
                    } catch (err) {
                        console.error('Gagal fetch methods:', err);
                        this.methodList = [];
                    }
                },
                
                async updateStationIdBasedOnMethod() {
                    if (!this.isPredefinedRole) return;
                    
                    
                }
            }));
        });
    </script>
</x-app-layout>