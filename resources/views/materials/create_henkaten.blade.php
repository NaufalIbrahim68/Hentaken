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

                    {{-- Error & Success Messages --}}
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
                        
                        // Role & Line Area Logic
                        $userRole = Auth::user()->role ?? 'Guest';
                        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);
                        
                        $predefinedLineArea = match ($userRole) {
                            'Leader QC' => 'Incoming',
                            'Leader PPIC' => 'Delivery',
                            default => null,
                        };

                        // Station ID mapping for predefined roles
                        $predefinedStationId = null;
                        if ($isPredefinedRole) {
                            if (isset($log) && $log->station_id) {
                                // Edit mode: use existing station_id
                                $predefinedStationId = $log->station_id;
                            } else {
                                // Create mode: use default based on role
                                $predefinedStationId = match ($predefinedLineArea) {
                                    'Incoming' => 143,
                                    'Delivery' => 1150,
                                    default => null,
                                };
                            }
                        }
                        
                        // Static material list for predefined roles
                        $materialListStatic = [
                            'Incoming' => ['Document IPP', 'Special Acceptance', 'IRD Supplier'],
                            'Delivery' => ['Document IPP', 'Label IPP', 'Packing Customer', 'Tag Produksi'],
                        ];
                        
                        $defaultMaterialOptions = $isPredefinedRole && $predefinedLineArea 
                            ? ($materialListStatic[$predefinedLineArea] ?? []) 
                            : [];
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
                                @if (!$isPredefinedRole)
                                    oldLineArea: '{{ old('line_area', $log->station->line_area ?? '') }}',
                                    oldStation: {{ old('station_id', $log->station_id ?? 'null') }},
                                    oldMaterial: {{ old('material_id', $log->material_id ?? 'null') }},
                                    findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                                    findMaterialsUrl: '{{ route('henkaten.materials.by_station') }}',
                                @else
                                    selectedLineArea: '{{ $predefinedLineArea }}',
                                    selectedStation: {{ $predefinedStationId ?? 'null' }},
                                    selectedMaterial: '{{ old('material_name', $log->material_name ?? '') }}',
                                    materialStaticList: {{ json_encode($defaultMaterialOptions) }},
                                @endif
                                isPredefined: {{ $isPredefinedRole ? 'true' : 'false' }}
                            })"
                            x-init="init()">
                            
                            {{-- Left Column --}}
                            <div>
                                @if ($isPredefinedRole)
                                    {{-- PREDEFINED MODE (QC/PPIC) --}}
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <input type="text" value="{{ $predefinedLineArea }}" readonly
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                        <input type="hidden" name="line_area" value="{{ $predefinedLineArea }}">
                                    </div>
                                    
                                    {{-- Hidden Station ID --}}
                                    <input type="hidden" name="station_id" value="{{ $predefinedStationId }}">
                                    
                                    {{-- Material Name Selection --}}
                                    <div class="mb-4">
                                        <label for="material_name" class="block text-sm font-medium text-gray-700">
                                            Material <span class="text-red-500">*</span>
                                        </label>
                                        <select id="material_name" name="material_name" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                x-model="selectedMaterial">
                                            <option value="">-- Pilih Material --</option>
                                            @foreach ($defaultMaterialOptions as $materialName)
                                                <option value="{{ $materialName }}" 
                                                    @selected(old('material_name', $log->material_name ?? '') == $materialName)>
                                                    {{ $materialName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                @else
                                    {{-- DYNAMIC MODE (Default) --}}
                                    <div class="mb-4">
                                        <label for="line_area" class="block text-sm font-medium text-gray-700">
                                            Line Area <span class="text-red-500">*</span>
                                        </label>
                                        <select id="line_area" name="line_area" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                x-model="selectedLineArea"
                                                @change="fetchStations()">
                                            <option value="">-- Pilih Line Area --</option>
                                            @foreach ($lineAreas ?? [] as $area)
                                                <option value="{{ $area }}" @selected(old('line_area', $log->station->line_area ?? '') == $area)>
                                                    {{ $area }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="station_id" class="block text-sm font-medium text-gray-700">
                                            Station <span class="text-red-500">*</span>
                                        </label>
                                        <select id="station_id" name="station_id" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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
                                        <label for="material_id" class="block text-sm font-medium text-gray-700">
                                            Material <span class="text-red-500">*</span>
                                        </label>
                                        <select id="material_id" name="material_id" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                x-model="selectedMaterial"
                                                :disabled="materialList.length === 0">
                                            <option value="">-- Pilih Material --</option>
                                            <template x-for="material in materialList" :key="material.id">
                                                <option :value="material.id"
                                                    :selected="material.id == selectedMaterial"
                                                    x-text="material.material_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                @endif
                                
                                {{-- Serial Number Fields --}}
                                <div class="mb-4">
                                    <label for="serial_number_start" class="block text-sm font-medium text-gray-700">
                                        Serial Number Start
                                        @if(isset($log))<span class="text-red-500">*</span>@endif
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
                                        @if(isset($log))<span class="text-red-500">*</span>@endif
                                    </label>
                                    <input type="text" id="serial_number_end" name="serial_number_end"
                                        value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Masukkan serial number akhir..."
                                        {{ isset($log) ? 'required' : '' }}>
                                </div>
                            </div>

                            {{-- Right Column (Date & Time) --}}
                            <div>
                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">
                                        Tanggal Efektif <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" id="effective_date" name="effective_date"
                                        value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:ring-indigo-500 focus:border-indigo-500" 
                                        required>
                                </div>

                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">
                                        Tanggal Berakhir <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" id="end_date" name="end_date"
                                        value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:ring-indigo-500 focus:border-indigo-500" 
                                        required>
                                </div>

                                <div class="mb-4">
                                    <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">
                                        Waktu Mulai <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" id="time_start" name="time_start"
                                        value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:ring-indigo-500 focus:border-indigo-500" 
                                        required>
                                </div>

                                <div class="mb-4">
                                    <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">
                                        Waktu Berakhir <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" id="time_end" name="time_end"
                                        value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:ring-indigo-500 focus:border-indigo-500" 
                                        required>
                                </div>
                            </div>
                        </div>

                        {{-- Before & After Descriptions --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">
                                    Sebelum <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="description_before"
                                    value="{{ old('description_before', $log->description_before ?? '') }}"
                                    class="w-full py-3 px-4 border rounded focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Deskripsi kondisi/part sebelum perubahan..."
                                    required>
                                <p class="text-xs text-gray-500 mt-2 italic">Deskripsi kondisi sebelum perubahan</p>
                            </div>

                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">
                                    Sesudah <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="description_after"
                                    value="{{ old('description_after', $log->description_after ?? '') }}"
                                    class="w-full py-3 px-4 border rounded focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Deskripsi kondisi/part setelah perubahan..."
                                    required>
                                <p class="text-xs text-green-600 mt-2 italic">Deskripsi kondisi setelah perubahan</p>
                            </div>
                        </div>

                        {{-- Keterangan --}}
                        <div class="mb-6 mt-6">
                            <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">
                                Keterangan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="keterangan" name="keterangan" rows="4"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Jelaskan alasan perubahan material..."
                                required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                        </div>

                        {{-- Lampiran --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">
                                Lampiran @if(!isset($log))<span class="text-red-500">*</span>@endif
                            </label>
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

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('activity.log.material') }}"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md transition">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md transition">
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
                // State Properties
                selectedLineArea: config.selectedLineArea || config.oldLineArea || '',
                selectedStation: config.selectedStation || config.oldStation || null,
                selectedMaterial: config.selectedMaterial || config.oldMaterial || null,
                stationList: [],
                materialList: [],
                materialStaticList: config.materialStaticList || [],
                
                // URLs
                findStationsUrl: config.findStationsUrl || '',
                findMaterialsUrl: config.findMaterialsUrl || '',
                
                // Flags
                isPredefined: config.isPredefined || false,

                // Initialization
                async init() {
                    console.log('🚀 Alpine Dropdowns Initialized');
                    console.log('Mode:', this.isPredefined ? 'Predefined' : 'Dynamic');
                    console.log('Line Area:', this.selectedLineArea);
                    console.log('Station:', this.selectedStation);
                    console.log('Material:', this.selectedMaterial);
                    
                    if (!this.isPredefined) {
                        // Dynamic mode: fetch cascading data
                        if (this.selectedLineArea) {
                            await this.fetchStations(false);
                            if (this.selectedStation) {
                                await this.fetchMaterials(false);
                            }
                        }
                    } else {
                        // Predefined mode: data is already set from PHP
                        console.log('Static materials:', this.materialStaticList);
                    }
                },

                // Fetch Stations (Dynamic Mode Only)
                async fetchStations(resetValues = true) {
                    if (this.isPredefined) return;
                    
                    if (resetValues) {
                        this.selectedStation = null;
                        this.selectedMaterial = null;
                        this.materialList = [];
                    }
                    this.stationList = [];

                    if (!this.selectedLineArea) {
                        console.log('⚠️ No line area selected');
                        return;
                    }

                    console.log('📡 Fetching stations for:', this.selectedLineArea);

                    try {
                        const url = `${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`;
                        const res = await fetch(url);
                        
                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                        }
                        
                        const data = await res.json();
                        this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                        console.log('✅ Stations loaded:', this.stationList.length);
                    } catch (err) {
                        console.error('❌ Failed to fetch stations:', err);
                        this.stationList = [];
                    }
                },

                // Fetch Materials (Dynamic Mode Only)
                async fetchMaterials(resetMaterial = true) {
                    if (this.isPredefined) return;
                    
                    if (resetMaterial) {
                        this.selectedMaterial = null;
                    }
                    this.materialList = [];

                    if (!this.selectedStation) {
                        console.log('⚠️ No station selected');
                        return;
                    }

                    console.log('📡 Fetching materials for station:', this.selectedStation);

                    try {
                        const url = `${this.findMaterialsUrl}?station_id=${this.selectedStation}`;
                        const res = await fetch(url);
                        
                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                        }
                        
                        const data = await res.json();
                        this.materialList = Array.isArray(data) ? data : (data.data ?? []);
                        console.log('✅ Materials loaded:', this.materialList.length);
                    } catch (err) {
                        console.error('❌ Failed to fetch materials:', err);
                        this.materialList = [];
                    }
                }
            }));
        });
    </script>
</x-app-layout>