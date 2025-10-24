<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Data Man Power Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Pesan Error --}}
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

                    <form action="{{ route('manpower.master.store') }}" method="POST">
                        @csrf

                        {{-- Alpine.js untuk Dependent Dropdown --}}
                        <div 
                            x-data="dependentDropdowns('{{ old('line_area') }}', {{ old('station_id') ?? 'null' }})"
                            class="grid grid-cols-1 gap-6"
                        >

                            {{-- Nama Man Power --}}
                            <div>
                                <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                                <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
                            </div>

                            {{-- Line Area --}}
                            <div>
                                <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                <select id="line_area" name="line_area" required
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                    x-model="selectedLineArea"
                                    @change="fetchStations">
                                    <option value="">-- Pilih Line Area --</option>
                                    @foreach ($lineAreas as $area)
                                        <option value="{{ $area }}">{{ $area }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Station --}}
                            <div>
                                <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                <select id="station_id" name="station_id" required
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                    x-model="selectedStation"
                                    :disabled="stationList.length === 0">
                                    <option value="">-- Pilih Station --</option>
                                    <template x-for="station in stationList" :key="station.id">
                                        <option :value="station.id" x-text="station.station_name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Grup --}}
                            <div>
                                <label for="grup" class="block text-gray-700 text-sm font-bold mb-2">Grup</label>
                                <select id="grup" name="grup"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
                                    <option value="">-- Pilih Grup --</option>
                                    <option value="A" {{ old('grup') == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ old('grup') == 'B' ? 'selected' : '' }}>B</option>
                                </select>
                            </div>

                            {{-- Tombol --}}
                            <div class="flex items-center justify-end space-x-4 mt-6">
                                <a href="{{ route('manpower.index') }}"
                                   class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md">
                                    Batal
                                </a>
                                <button type="submit"
                                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md">
                                    Simpan
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Script Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        // Alpine Component untuk Dependent Dropdown
        function dependentDropdowns(oldLineArea = '', oldStation = null) {
            return {
                selectedLineArea: oldLineArea,
                selectedStation: oldStation,
                stationList: [],

                init() {
                    // Jika sudah ada line_area lama, ambil data station-nya
                    if (this.selectedLineArea) {
                        this.fetchStations();
                    }
                },

                fetchStations() {
                    if (!this.selectedLineArea) {
                        this.stationList = [];
                        this.selectedStation = null;
                        return;
                    }

                    fetch(`{{ route('stations.by_line') }}?line_area=${encodeURIComponent(this.selectedLineArea)}`)
                        .then(res => res.json())
                        .then(data => {
                            this.stationList = data;
                            // Jika ada station lama, pastikan tetap terpilih
                            if (this.selectedStation) {
                                const exists = this.stationList.some(s => s.id == this.selectedStation);
                                if (!exists) this.selectedStation = null;
                            }
                        })
                        .catch(err => {
                            console.error('Gagal mengambil data station:', err);
                            this.stationList = [];
                        });
                }
            }
        }
    </script>
</x-app-layout>
