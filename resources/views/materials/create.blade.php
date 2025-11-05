<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Data Material Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <form action="{{ route('materials.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Line/Area (Dropdown 1) --}}
                            <div class="mb-4">
                                <label for="line_area_filter" class="block text-gray-700 text-sm font-bold mb-2">Line/Area</label>
                                <select id="line_area_filter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">-- Pilih Line/Area --</option>
                                    {{-- Variabel $lineAreas ini didapat dari MaterialController@create --}}
                                    @foreach ($lineAreas as $lineArea)
                                        <option value="{{ $lineArea }}" {{ old('selected_line_area') == $lineArea ? 'selected' : '' }}>
                                            {{ $lineArea }}
                                        </option>
                                    @endforeach
                                </select>
                                {{-- Dropdown ini tidak perlu 'name' karena tidak di-submit --}}
                            </div>

                            {{-- Station (Dropdown 2) --}}
                            <div class="mb-4">
                                <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station</label>
                                <select id="station_id" name="station_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" disabled>
                                    <option value="">-- Pilih Line/Area Terlebih Dahulu --</option>
                                    {{-- Opsi akan diisi oleh JavaScript --}}
                                </select>
                                @error('station_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Nama Material --}}
                            <div class="mb-4">
                                <label for="material_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Material</label>
                                <input type="text" id="material_name" name="material_name" value="{{ old('material_name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                @error('material_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Keterangan --}}
                            <div class="mb-4 md:col-span-2">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('keterangan') }}</textarea>
                                @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Lampiran & Status --}}
                            <div class="mb-4">
                                <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran (JPG/PNG)</label>
                                <input type="file" id="lampiran" name="lampiran" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                @error('lampiran') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                           

                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <a href="{{ route('materials.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md">
                                Batal
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md">
                                Simpan
                            </button>
                        </div>
                    </form>

                    {{-- 
                       Kita perlu trik kecil untuk menyimpan 'old' value dari line_area 
                       jika terjadi error validasi, karena 'line_area' bukan bagian dari form.
                       Kita akan ambil 'station_id' yang lama, cari 'line_area'-nya.
                       Jika 'station_id' lama ada, kita simpan di input hidden.
                    --}}
                    @if(old('station_id'))
                        @php
                            $oldStation = \App\Models\Station::find(old('station_id'));
                        @endphp
                        @if($oldStation)
                            <input type="hidden" id="old_line_area" value="{{ $oldStation->line_area }}">
                            <input type="hidden" id="old_station_id" value="{{ $oldStation->id }}">
                        @endif
                    @endif

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lineAreaSelect = document.getElementById('line_area_filter');
            const stationSelect = document.getElementById('station_id');
            
            // --- Fungsi untuk mengambil data Station ---
            function fetchStations(lineArea, selectedStationId = null) {
                if (!lineArea) {
                    stationSelect.innerHTML = '<option value="">-- Pilih Line/Area Terlebih Dahulu --</option>';
                    stationSelect.disabled = true;
                    return;
                }
                
                // Tampilkan loading
                stationSelect.innerHTML = '<option value="">Memuat...</option>';
                stationSelect.disabled = false;

                // Ganti URL ini ke route yang kita buat
                // Kita gunakan encodeURIComponent untuk memastikan karakter seperti spasi aman di URL
                const url = `{{ route('get.stations.by.line.area') }}?line_area=${encodeURIComponent(lineArea)}`;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        stationSelect.innerHTML = '<option value="">-- Pilih Station --</option>'; // Reset
                        
                        data.forEach(station => {
                            const option = document.createElement('option');
                            option.value = station.id;
                            option.textContent = station.station_name; // Sesuaikan 'station_name'
                            stationSelect.appendChild(option);
                        });

                        // Jika ada value lama (misal saat gagal validasi), pilih kembali
                        if (selectedStationId) {
                            stationSelect.value = selectedStationId;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching stations:', error);
                        stationSelect.innerHTML = '<option value="">Gagal memuat data</option>';
                    });
            }

            // --- Event listener saat dropdown Line/Area berubah ---
            lineAreaSelect.addEventListener('change', function () {
                fetchStations(this.value);
            });

            // --- Trigger saat halaman dimuat (untuk handle 'old' value saat validasi error) ---
            const oldLineAreaInput = document.getElementById('old_line_area');
            const oldStationIdInput = document.getElementById('old_station_id');
            
            if (oldLineAreaInput && oldStationIdInput) {
                const oldLineArea = oldLineAreaInput.value;
                const oldStationId = oldStationIdInput.value;
                
                if (oldLineArea) {
                    // Set dropdown Line/Area ke value yang lama
                    lineAreaSelect.value = oldLineArea;
                    // Panggil fetchStations dengan data lama
                    fetchStations(oldLineArea, oldStationId);
                }
            }
        });
    </script>
    @endpush

</x-app-layout>