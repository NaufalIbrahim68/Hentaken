<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-2xl font-bold text-gray-900 mb-6">
                        Edit Man Power: <span class="text-blue-600">{{ $man_power->nama }}</span>
                    </h3>

                    {{-- Menampilkan error validasi jika ada --}}
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">Terjadi Kesalahan</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                  <form action="{{ route('manpower.update', $man_power->id) }}" method="POST"
    x-data="dependentDropdowns('{{ old('line_area', $man_power->line_area) }}', '{{ old('station_id', $man_power->station_id) }}')"
    x-init="init()">
    @csrf
    @method('PUT')

                        <div class="mb-4">
                            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Karyawan</label>
                            <input type="text" 
                                   id="nama" 
                                   name="nama" 
                                   value="{{ old('nama', $man_power->nama) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            @error('nama') 
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> 
                            @enderror
                        </div>

                      {{-- Dropdown Line Area --}}
    <div class="mb-4">
        <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line Area</label>
        <select 
            id="line_area" 
            name="line_area" 
            x-model="selectedLineArea"
            @change="fetchStations()" 
            class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
        >
            <option value="">Pilih Line Area</option>
            @foreach ($lineAreas as $line)
                <option value="{{ $line }}">{{ $line }}</option>
            @endforeach
        </select>
    </div>

    {{-- Dropdown Station Name --}}
    <div class="mb-4">
        <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station Name</label>
        <select 
            id="station_id" 
            name="station_id" 
            x-model="selectedStation"
            class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
        >
            <option value="">Pilih Station</option>
            <template x-for="station in stationList" :key="station.id">
                <option :value="station.id" x-text="station.station_name"></option>
            </template>
        </select>
    </div>
{{-- </div> <-- DIV YANG SALAH POSISI SUDAH DIHAPUS DARI SINI --}}

<div class="mb-4">
    <label for="group" class="block text-gray-700 text-sm font-bold mb-2">Group</label>
    <select 
        id="group" 
        name="group" 
        class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        required
    >
        <option value="">Pilih Group</option>
        <option value="A" {{ old('group', $man_power->group) == 'A' ? 'selected' : '' }}>A</option>
        <option value="B" {{ old('group', $man_power->group) == 'B' ? 'selected' : '' }}>B</option>
    </select>
    @error('group')
        <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
    @enderror
</div>

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('manpower.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Update Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

   <script>
    // Alpine Component untuk Dependent Dropdown (reusable)
    function dependentDropdowns(oldLineArea = '', oldStation = null) {
        return {
            selectedLineArea: oldLineArea,
            selectedStation: oldStation,
            stationList: [],

            init() {
                // Jika sudah ada line_area lama (saat edit), ambil daftar station
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

                        // Jika ada station lama dari DB, pertahankan pilihannya
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