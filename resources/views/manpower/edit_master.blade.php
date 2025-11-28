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
            {{ json_encode($stationIds) }},
            {{ json_encode($man_power->station_id) }}
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
                                    <option value="{{ $line }}" {{ $line === $man_power->line_area ? 'selected' : '' }}>{{ $line }}</option>
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
                                        <div>
                                            <span x-text="station.station_name" class="text-sm text-gray-800"></span>
                                            <template x-if="station.id === mainStationId">
                                                <span class="ml-2 text-xs font-semibold text-green-600">(Main Station)</span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <template x-if="station.id !== mainStationId">
                                                <button type="button" @click="deleteStation(station.id)"
                                                        class="bg-red-500 text-white text-xs px-3 py-1 rounded hover:bg-red-600">
                                                    Hapus
                                                </button>
                                            </template>
                                            <template x-if="station.id === mainStationId">
                                                <span class="text-xs text-gray-500 italic">Tidak bisa dihapus</span>
                                            </template>
                                        </div>
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
    function manpowerEdit(oldLineArea = '', oldStationIds = [], mainStationId = null) {
        return {
            selectedLineArea: oldLineArea,
            oldStationIds: oldStationIds,
            mainStationId: mainStationId !== null ? Number(mainStationId) : null,
            stationList: [],
            currentStations: [],
            isStationModalOpen: false,
            newStationId: '',

            async init() {
                // Jika line area sudah ada, ambil daftar station sesuai line area
                if (this.selectedLineArea) {
                    await this.fetchStations();
                } else {
                    // jika tidak ada selectedLineArea, kita tetap coba load currentStations
                    // jika stationList belum ada, currentStations akan kosong
                    this.loadCurrentStations();
                }
            },

            async fetchStations() {
                try {
                    const res = await fetch(`{{ route('manpower.master.stations.by_line') }}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                    // asumsikan server mengembalikan array station { id, station_name, ... }
                    this.stationList = await res.json();

                    // Setelah daftar station diterima, load current stations berdasar oldStationIds
                    this.loadCurrentStations();
                } catch (err) {
                    console.error('Fetch stations failed:', err);
                    // tetap panggil loadCurrentStations untuk fallback
                    this.loadCurrentStations();
                }
            },

            loadCurrentStations() {
                if (!Array.isArray(this.oldStationIds)) {
                    this.currentStations = [];
                    return;
                }

                // Ubah semua 'oldStationIds' menjadi Number untuk perbandingan aman
                const numericOldIds = this.oldStationIds.map(id => Number(id));

                // Jika stationList sudah ada (dari fetch), gunakan itu untuk detail station
                if (Array.isArray(this.stationList) && this.stationList.length > 0) {
                    this.currentStations = this.stationList.filter(st => numericOldIds.includes(Number(st.id)));
                    // Namun mungkin ada station yang ada di oldStationIds tapi tidak ada di stationList (mis. sudah berubah line).
                    // Untuk safety, jika ada id yang belum dimuat, tambahkan placeholder sederhana.
                    numericOldIds.forEach(id => {
                        if (!this.currentStations.some(s => Number(s.id) === id)) {
                            // Tambahkan placeholder minimal (nama tidak lengkap)
                            this.currentStations.push({ id: id, station_name: 'Station #' + id + ' (tidak di-list)' });
                        }
                    });
                } else {
                    // Jika stationList belum tersedia, buat placeholder saja dari oldStationIds
                    this.currentStations = numericOldIds.map(id => ({ id: id, station_name: 'Station #' + id }));
                }

                // Pastikan tipe id sebagai Number
                this.currentStations = this.currentStations.map(s => ({ ...s, id: Number(s.id) }));
            },

            openStationModal() {
                if (!this.selectedLineArea) {
                    alert('Pilih Line Area terlebih dahulu!');
                    return;
                }
                this.isStationModalOpen = true;
            },

            // =============================================
            // addStation()
            // =============================================
            async addStation() {
                if (!this.newStationId) return alert('Pilih station baru terlebih dahulu.');

                const selected = this.stationList.find(s => Number(s.id) === Number(this.newStationId));
                if (!selected) return alert('Station tidak ditemukan pada daftar.');

                if (this.currentStations.some(st => Number(st.id) === Number(selected.id))) {
                    alert('Station ini sudah ditambahkan.');
                    return;
                }

                // Jika station yang ditambahkan sama dengan mainStationId, biarkan (boleh ditambahkan)
                this.currentStations.push({ ...selected, id: Number(selected.id) });
                this.newStationId = '';
                this.isStationModalOpen = false;

                try {
                    await fetch(`{{ route('manpower.master.stations.store') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            station_id: Number(selected.id),
                            man_power_id: {{ $man_power->id }}
                        })
                    });
                } catch (err) {
                    console.error('Gagal menyimpan station:', err);
                    // Jika gagal, hapus station dari tampilan
                    this.currentStations = this.currentStations.filter(s => Number(s.id) !== Number(selected.id));
                    alert('Gagal menyimpan station ke server.');
                }
            },

            // =============================================
            // deleteStation()
            // =============================================
            async deleteStation(id) {
                // Pastikan id numerik
                const numericId = Number(id);

                // Cegah hapus station utama
                if (this.mainStationId !== null && numericId === Number(this.mainStationId)) {
                    alert('Station utama tidak boleh dihapus.');
                    return;
                }

                if (!confirm('Yakin ingin menghapus station ini?')) return;

                // Simpan station yang akan dihapus untuk kemungkinan rollback
                const stationToUndo = this.currentStations.find(s => Number(s.id) === numericId);

                // Hapus dari UI dulu
                this.currentStations = this.currentStations.filter(s => Number(s.id) !== numericId);

                try {
                    const deleteUrl = `{{ route('manpower.master.stations.destroy', ['id' => ':id']) }}`.replace(':id', numericId);

                    await fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            man_power_id: {{ $man_power->id }}
                        })
                    });
                } catch (err) {
                    console.error('Gagal menghapus station:', err);
                    // rollback UI jika gagal
                    if (stationToUndo) this.currentStations.push(stationToUndo);
                    alert('Gagal menghapus station dari server.');
                }
            }
        };
    }
    </script>
</x-app-layout>
