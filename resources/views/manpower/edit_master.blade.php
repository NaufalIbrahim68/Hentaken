<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Man Power') }}
        </h2>
    </x-slot>

    @php
        $stationIds = $man_power->stations->pluck('id');
        if ($stationIds->isEmpty() && $man_power->station_id) {
            $stationIds = [$man_power->station_id];
        } else {
            $stationIds = $stationIds->all();
        }
    @endphp

    <div 
        x-data="manpowerEdit(
            '{{ $man_power->line_area }}',
            {{ json_encode($stationIds) }} 
        )"
        x-init="init()"
        class="py-12"
    >
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">
                        Edit Man Power: <span class="text-blue-600">{{ $man_power->nama }}</span>
                    </h3>

                    {{-- Pesan Error --}}
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                            <p class="font-bold">Terjadi Kesalahan</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- FORM EDIT --}}
                    <form action="{{ route('manpower.update', $man_power->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Nama --}}
                        <div class="mb-4">
                            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Karyawan</label>
                            <input type="text" id="nama" name="nama"
                                   value="{{ old('nama', $man_power->nama) }}"
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                        {{-- Line Area --}}
                        <div class="mb-4">
                            <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line Area</label>
                            <select id="line_area" name="line_area" x-model="selectedLineArea"
                                    @change="fetchStations()"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">Pilih Line Area</option>
                                @foreach ($lineAreas as $line)
                                    <option value="{{ $line }}">{{ $line }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Station --}}
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Station Name</label>

                            <div class="flex flex-col gap-2">
                                <template x-if="currentStations.length === 0">
                                    <p class="text-gray-500 text-sm italic">Belum ada station yang dipegang.</p>
                                </template>

                                <template x-for="station in currentStations" :key="station.id">
                                    <div class="flex items-center justify-between border rounded px-3 py-2 bg-gray-50">
                                        <span x-text="station.station_name" class="text-sm text-gray-800"></span>
                                        <button type="button" @click="deleteStation(station.id)"
                                                class="bg-red-500 text-white text-xs px-3 py-1 rounded hover:bg-red-600">
                                            Hapus
                                        </button>
                                    </div>
                                </template>

                                <div class="mt-2">
                                    <button type="button" @click="openStationModal()"
                                            class="bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600">
                                        Kelola
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Group --}}
                        <div class="mb-4">
                            <label for="group" class="block text-gray-700 text-sm font-bold mb-2">Group</label>
                            <select id="group" name="group"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">Pilih Group</option>
                                <option value="A" {{ old('group', $man_power->group) == 'A' ? 'selected' : '' }}>A</option>
                                <option value="B" {{ old('group', $man_power->group) == 'B' ? 'selected' : '' }}>B</option>
                            </select>
                        </div>

                        <div class="flex justify-end space-x-4 border-t pt-4">
                            <a href="{{ route('manpower.index') }}"
                               class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                Batal
                            </a>
                            <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md">
                                Update Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================ --}}
        {{-- MODAL TAMBAH STATION --}}
        {{-- ============================ --}}
        <div x-show="isStationModalOpen"
             x-cloak
             x-transition.opacity
             @keydown.escape.window="isStationModalOpen = false"
             @click.self="isStationModalOpen = false"
             class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">

            <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-lg" @click.stop>
                <h2 class="text-lg font-semibold mb-4 text-gray-800">
                    Tambah Station Baru di <span x-text="selectedLineArea"></span>
                </h2>

                <div class="flex gap-2 mb-4">
                    <select x-model="newStationId"
                            class="flex-1 border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Station --</option>
                        <template x-for="station in stationList" :key="station.id">
                            <option :value="station.id" x-text="station.station_name"></option>
                        </template>
                    </select>

                    <button type="button" @click="addStation()"
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Simpan
                    </button>
                </div>

                <div class="text-right">
                    <button type="button" @click="isStationModalOpen = false"
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- AlpineJS --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
    function manpowerEdit(oldLineArea = '', oldStationIds = []) {
        return {
            selectedLineArea: oldLineArea,
            oldStationIds: oldStationIds,
            stationList: [],
            currentStations: [],
            isStationModalOpen: false,
            newStationId: '',

            async init() {
                if (this.selectedLineArea) {
                    await this.fetchStations();
                    await this.loadCurrentStations();
                }
            },

            async fetchStations() {
                try {
                    const res = await fetch(`{{ route('manpower.master.stations.by_line') }}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                    this.stationList = await res.json();
                } catch (err) {
                    console.error('Fetch stations failed:', err);
                }
            },

            loadCurrentStations() {
                if (!Array.isArray(this.oldStationIds)) return;

                // Ubah semua 'oldStationIds' menjadi Angka (Number)
                const numericOldIds = this.oldStationIds.map(id => parseInt(id, 10));

                this.currentStations = this.stationList.filter(st =>
                    // Sekarang kita membandingkan Angka dengan Angka
                    numericOldIds.includes(st.id) 
                );
            },

            openStationModal() {
                if (!this.selectedLineArea) {
                    alert('Pilih Line Area terlebih dahulu!');
                    return;
                }
                this.isStationModalOpen = true;
            },

            async addStation() {
                if (!this.newStationId) return alert('Pilih station baru terlebih dahulu.');

                const selected = this.stationList.find(s => s.id == this.newStationId);
                if (!selected) return;
                if (this.currentStations.some(st => st.id === selected.id)) {
                    alert('Station ini sudah ditambahkan.');
                    return;
                }

                this.currentStations.push(selected);
                this.newStationId = '';
                this.isStationModalOpen = false;

                try {
                    await fetch(`/manpower/{{ $man_power->id }}/stations`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ station_id: selected.id })
                    });
                } catch (err) {
                    console.error('Gagal menyimpan station:', err);
                }
            },

            async deleteStation(id) {
                if (!confirm('Yakin ingin menghapus station ini?')) return;
                this.currentStations = this.currentStations.filter(s => s.id !== id);

                try {
                    await fetch(`/manpower/{{ $man_power->id }}/stations/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                } catch (err) {
                    console.error('Gagal menghapus station:', err);
                }
            }
        };
    }
    </script>
</x-app-layout>