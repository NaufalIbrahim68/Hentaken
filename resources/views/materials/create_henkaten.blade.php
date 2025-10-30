<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Data Henkaten Material') }}
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

                    <form action="{{ route('henkaten.material.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ route('henkaten.material.create') }}">

                        {{-- ========================================================== --}}
                        {{-- BARU: Input tersembunyi untuk Shift dari Session --}}
                        {{-- ========================================================== --}}
                        <input type="hidden" name="shift" value="{{ $currentShift }}">
                        
                        {{-- Wrapper Alpine untuk dependent dropdowns --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
                             x-data="dependentDropdowns(
                                 '{{ old('line_area') }}', 
                                 {{ old('station_id') ?? 'null' }}, 
                                 @json($stations ?? []),
                                 {{ old('material_id') ?? 'null' }},
                                 @json($materials ?? []) 
                             )">

                            {{-- Kolom Kiri --}}
                            <div>
                                {{-- =================================== --}}
                                {{-- DIHAPUS: Dropdown Shift --}}
                                {{-- =================================== --}}
                                {{-- <div class="mb-4">
                                    <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                                    <select id="shift" name="shift" ...> ... </select>
                                </div> --}}

                                <div class="mb-4">
                                    <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                    <select id="line_area" name="line_area" required
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                            x-model="selectedLineArea"
                                            @change="fetchStations()">
                                        <option value="">-- Pilih Line Area --</option>
                                        
                                        @foreach ($lineAreas as $area)
                                            <option value="{{ $area }}" :selected="'{{ $area }}' === selectedLineArea">
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

                                {{-- BARU: Dropdown Material berdasarkan Station --}}
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
                                                    x-text="material.material_name"></option> {{-- Ganti 'material_name' jika nama kolom beda --}}
                                        </template>
                                        
                                    </select>
                                </div>
                            </div>

                            {{-- Kolom Kanan (Tidak berubah) --}}
                            <div>
                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    <input type="date" id="effective_date" name="effective_date"
                                           value="{{ old('effective_date') }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                    <input type="time" id="time_start" name="time_start"
                                           value="{{ old('time_start') }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                                </div>

                                <div class="mb-4">
                                    <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                    <input type="time" id="time_end" name="time_end"
                                           value="{{ old('time_end') }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                           required>
                        </div>
                            </div>
                        </div>

                        {{-- Before & After untuk Material (Tidak berubah) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md relative">
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sebelum</label>
                                
                                <input type="text" name="description_before" value="{{ old('description_before') }}"
                                       class="w-full py-3 px-4 border rounded"
                                       placeholder="Deskripsi kondisi/part sebelum perubahan..."
                                       required>
                                
                                <p class="text-xs text-gray-500 mt-2 italic">Deskripsi kondisi sebelum perubahan</p>
                            </div>

                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                
                                <label class="text-gray-700 text-sm font-bold mb-2 block">Sesudah</label>
                                
                                <input type="text" name="description_after" value="{{ old('description_after') }}"
                                       class="w-full py-3 px-4 border rounded"
                                       placeholder="Deskripsi kondisi/part setelah perubahan..."
                                       required>
                                
                                <p class="text-xs text-green-600 mt-2 italic">Deskripsi kondisi setelah perubahan</p>
                            </div>
                        </div>
                        
                        {{-- BLOK KETERANGAN (Tidak berubah) --}}
                        <div class="mb-6 mt-6">
                            <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="4"
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                      placeholder="Jelaskan alasan perubahan material..."
                                      required>{{ old('keterangan') }}</textarea>
                        </div>

                        {{-- Lampiran (Tidak berubah) --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                   class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   required>
                        </div>

                        {{-- Tombol (Tidak berubah) --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('dashboard') }}"
                               class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                Batal
                            </a>
                            <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                Simpan Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

   {{-- Alpine.js (Tidak berubah) --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Fungsi autocomplete (bisa dihapus jika tidak terpakai)
        function autocomplete(url) {
            return {
                query: '',
                results: [],
                selectedId: null,
                search() {
                    if (this.query.length < 1) { this.results = []; return; }
                    fetch(`${url}?q=${encodeURIComponent(this.query)}`)
                        .then(res => res.json())
                        .then(data => this.results = data);
                },
                select(item) {
                    this.query = item.material_name; // Sesuaikan 'material_name' jika perlu
                    this.selectedId = item.id;
                    this.results = [];
                }
            }
        }

        // Dependent Dropdown untuk Line -> Station -> Material
        function dependentDropdowns(oldLineArea, oldStation, initialStations, oldMaterial, initialMaterials) {
            return {
                selectedLineArea: oldLineArea || '',
                selectedStation: oldStation || null,
                stationList: initialStations || [],
                selectedMaterial: oldMaterial || null,
                materialList: initialMaterials || [],
                
                fetchStations() {
                    this.selectedStation = null; 
                    this.stationList = [];
                    this.selectedMaterial = null; // Reset material
                    this.materialList = [];      // Reset material list

                    if (!this.selectedLineArea) return;

                    fetch(`{{ route('henkaten.stations.by_line') }}?line_area=${encodeURIComponent(this.selectedLineArea)}`)
                        .then(res => res.json())
                        .then(data => this.stationList = data)
                        .catch(err => {
                            console.error('Gagal mengambil data station:', err);
                            this.stationList = [];
                        });
                },
                
                fetchMaterials() {
                    this.selectedMaterial = null;
                    this.materialList = [];

                    if (!this.selectedStation) return;
                    
                    fetch(`{{ route('henkaten.materials.by_station') }}?station_id=${this.selectedStation}`) 
                        .then(res => res.json()) 
                        .then(data => {
                            this.materialList = data;
                        })
                        .catch(err => {
                            console.error('Gagal mengambil data material:', err);
                            this.materialList = [];
                        });
                }
            }
        }
    </script>
</x-app-layout>