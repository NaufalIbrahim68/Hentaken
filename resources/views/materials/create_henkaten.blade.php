<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($log) ? __('Edit Data Henkaten Material') : __('Buat Data Henkaten Material') }}
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
                        $formAction = isset($log)
                            ? route('activity.log.material.update', $log->id)
                            : route('henkaten.material.store');
                        
                        // --- LOGIC ROLE & LINE AREA ---
                        $userRole = Auth::user()->role ?? 'Guest';
                        
                        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);
                        $predefinedLineArea = match ($userRole) {
                            'Leader QC' => 'Incoming',
                            'Leader PPIC' => 'Delivery',
                            default => null,
                        };

                        // Data material statis (hanya nama, TIDAK ADA ID)
                        $materialListStatic = [
                            'Incoming' => ['Document IPP', 'Special Acceptance', 'IRD Supplier'],
                            'Delivery' => ['Document IPP', 'Label IPP', 'Packing Customer', 'Tag Produksi'],
                        ];
                        
                        $defaultMaterialOptions = $isPredefinedRole ? ($materialListStatic[$predefinedLineArea] ?? []) : [];
                        
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="redirect_to" value="{{ route('henkaten.material.create') }}">
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? ($currentShift ?? '')) }}">


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
                            x-data="dependentDropdowns({
                                // ... (Alpine config lama) ...
                                @if (!$isPredefinedRole)
                                    oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
                                    oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                    oldMaterial: {{ old('material_id', $log->material_id ?? 'null') }},
                                    findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                                    findMaterialsUrl: '{{ route('henkaten.materials.by_station') }}'
                                @else
                                    // Untuk role predefined, set nilai statis
                                    selectedLineArea: '{{ $predefinedLineArea }}', 
                                    selectedStation: {{ $log->station_id ?? 'null' }},
                                    selectedMaterial: '{{ old('material_name', $log->material_name ?? '') }}', // Menggunakan material_name string
                                @endif
                                isPredefined: {{ $isPredefinedRole ? 'true' : 'false' }},
                                materialStaticList: {{ json_encode($defaultMaterialOptions) }} 
                            })"
                            x-init="init()">
                            
                            {{-- Kolom Kiri --}}
                            <div>
                                @if ($isPredefinedRole)
                                    {{-- MODE QC/PPIC (INPUT STATIS) --}}
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <input type="text" value="{{ $predefinedLineArea }}" readonly
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                        <input type="hidden" name="line_area" value="{{ $predefinedLineArea }}">
                                    </div>
                                    
                                    {{-- HIDDEN INPUT UNTUK STATION ID (akan dicari Controller) --}}
                                    <input type="hidden" name="station_id" :value="selectedStation"> 
                                    
                                    {{-- HIDDEN INPUT UNTUK MATERIAL ID (akan dicari Controller) --}}
                                    {{-- Ini adalah perbaikan utama untuk 'material id must be an integer' --}}
                                    <input type="hidden" name="material_id" :value="null"> 
                                    
                                    <div class="mb-4">
                                        <label for="material_name" class="block text-sm font-medium text-gray-700">Material</label>
                                        {{-- REVISI: Menggunakan name="material_name" dan value adalah string --}}
                                        <select id="material_name" name="material_name" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedMaterial">

                                            <option value="">-- Pilih Material --</option>
                                            
                                            {{-- List material statis (nama) --}}
                                            @foreach ($defaultMaterialOptions as $materialName)
                                                {{-- Value yang dikirim adalah STRING NAMA MATERIAL --}}
                                                <option value="{{ $materialName }}" 
                                                    @selected(old('material_name', $log->material_name ?? '') == $materialName)>
                                                    {{ $materialName }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>
                                    
                                @else
                                    {{-- MODE DEFAULT (DROPDOWN DINAMIS - TIDAK ADA PERUBAHAN SIGNIFIKAN DI SINI) --}}
                                    <div class="mb-4">
                                        <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <select id="line_area" name="line_area" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedLineArea"
                                                @change="fetchStations()">
                                            <option value="">-- Pilih Line Area --</option>
                                            {{-- Asumsi $lineAreas dikirim dari Controller --}}
                                            @foreach ($lineAreas ?? [] as $area)
                                                <option value="{{ $area }}" @selected(old('line_area', $log->station->line_area ?? '') == $area)>
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
                                                    x-text="material.material_name"></option>
                                            </template>
                                        </select>
                                        {{-- Tambahkan hidden field untuk material_name jika diperlukan oleh Controller --}}
                                        <input type="hidden" name="material_name" :value="materialList.find(m => m.id == selectedMaterial)?.material_name || ''">
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


                            {{-- Kolom Kanan (Tanggal & Waktu - TIDAK DIUBAH) --}}
                            <div>
                                {{-- ... (Kode Tanggal & Waktu) ... --}}
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

                        {{-- Before & After (Deskripsi - TIDAK DIUBAH) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sebelum</label>
                                <input type="text" name="description_before"
                                    value="{{ old('description_before', $log->description_before ?? '') }}"
                                    class="w-full py-3 px-4 border rounded"
                                    placeholder="Deskripsi kondisi/part sebelum perubahan..."
                                    required>
                                <p class="text-xs text-gray-500 mt-2 italic">Deskripsi kondisi sebelum perubahan</p>
                            </div>

                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sesudah</label>
                                <input type="text" name="description_after"
                                    value="{{ old('description_after', $log->description_after ?? '') }}"
                                    class="w-full py-3 px-4 border rounded"
                                    placeholder="Deskripsi kondisi/part setelah perubahan..."
                                    required>
                                <p class="text-xs text-green-600 mt-2 italic">Deskripsi kondisi setelah perubahan</p>
                            </div>
                        </div>

                        {{-- BLOK KETERANGAN & Lampiran & Tombol (TIDAK DIUBAH) --}}
                        <div class="mb-6 mt-6">
                            <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="4"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                placeholder="Jelaskan alasan perubahan material..."
                                required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                        </div>

                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                {{ isset($log) ? '' : 'required' }}>

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

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('activity.log.material') }}"
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

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dependentDropdowns', (config) => ({
                
                // --- Properti Data (STATE) ---
                selectedLineArea: config.selectedLineArea || config.oldLineArea || '',
                selectedStation: config.selectedStation || config.oldStation || null,
                // Menggunakan selectedMaterial untuk menampung string (material_name)
                selectedMaterial: config.selectedMaterial || config.oldMaterial || null, 
                stationList: [], 
                materialList: [], 
                
                // --- URL ---
                findStationsUrl: config.findStationsUrl || '',
                findMaterialsUrl: config.findMaterialsUrl || '',
                
                // --- FLAGS ---
                isPredefined: config.isPredefined,

                // --- FUNGSI INIT (dijalankan oleh x-init) ---
                async init() {
                    console.log(`✅ Alpine Dropdowns Initialized (Mode: ${this.isPredefined ? 'Predefined' : 'Dynamic'})`);
                    
                    if (!this.isPredefined) {
                        // MODE DINAMIS (Default/Edit mode)
                        if (this.selectedLineArea) {
                            await this.fetchStations(false); 
                            if (this.selectedStation) {
                                await this.fetchMaterials(false); 
                            }
                        }
                    } 
                    // Mode Predefined: selectedStation akan diserahkan ke Controller saat submit
                },

                // Fungsi ini HANYA digunakan di MODE PREDEFINED (QC/PPIC)
                // Karena Controller akan menangani pencarian ID
                async updateStationIdBasedOnMaterial(resetMaterial = true) {
                    // Cukup biarkan selectedStation null di mode create, Controller akan mencari
                    if (config.selectedStation) {
                        this.selectedStation = config.selectedStation;
                    } else {
                        this.selectedStation = null;
                    }
                },


                // --- FUNGSI FETCH STATIONS (HANYA UNTUK MODE DINAMIS) ---
                async fetchStations(resetValues = true) {
                    if(this.isPredefined) return;
                    
                    if(resetValues) {
                        this.selectedStation = null;
                        this.selectedMaterial = null; 
                        this.materialList = [];    
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

                // --- FUNGSI FETCH MATERIALS (HANYA UNTUK MODE DINAMIS) ---
                async fetchMaterials(resetMaterial = true) {
                    if(this.isPredefined) return;
                    
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