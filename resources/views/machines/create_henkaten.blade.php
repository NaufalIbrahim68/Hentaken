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

                    {{-- Notifikasi sukses --}}
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                             x-init="setTimeout(() => show = false, 3000)"
                             class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative"
                             role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false"
                                    class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">&times;</button>
                        </div>
                    @endif

                    @php
                        $formAction = isset($log)
                            ? route('activity.log.machine.update', $log->id)
                            : route('henkaten.machine.store');

                        $userRole = Auth::user()->role ?? 'Guest';
                        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);
                        $isLeaderFA = ($userRole === 'Leader FA');
                        $predefinedLineArea = match ($userRole) {
                            'Leader QC' => 'Incoming',
                            'Leader PPIC' => 'Delivery',
                            default => null,
                        };
                        $logMachineId = $log?->id_machines ?? null;
                        $machinesToDisplay = $machinesToDisplay ?? collect([]);
                        if ($isLeaderFA) {
                            $allowedCategoriesFA = ['PROGRAM', 'Machine & JIG', 'Equipement', 'Kamera'];
                            $machinesToDisplay = $machinesToDisplay->filter(fn($m) => in_array($m->machines_category, $allowedCategoriesFA));
                        }
                        $allowedLineAreasFA = ['FA L1', 'FA L2', 'FA L3', 'FA L5', 'FA L6'];
                        $lineAreas = $isLeaderFA ? collect($allowedLineAreasFA) : ($lineAreas ?? collect(['Incoming','Delivery','Assembly','Machining']));
                        $predefinedStationId = $predefinedStationId ?? 143;
                    @endphp

                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="shift" value="{{ old('shift', $log?->shift ?? ($currentShift ?? '')) }}">

                        <div x-data="henkatenFormData()" x-init="initialize()">

                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- Kolom Kiri --}}
                                    <div>
                                        @if ($isPredefinedRole && !$isLeaderFA)
                                            {{-- Untuk Leader QC dan Leader PPIC --}}
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Line Area</label>
                                                <input type="text" value="{{ $predefinedLineArea }}" readonly
                                                       class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                                <input type="hidden" name="line_area" value="{{ $predefinedLineArea }}">
                                            </div>
                                            <input type="hidden" name="station_id" value="{{ $log?->station_id ?? $predefinedStationId }}">
                                        @else
                                            {{-- Untuk Leader FA dan role lainnya --}}
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
                                                    <template x-for="station in stationList" :key="station.id">
                                                        <option :value="station.id" x-text="station.station_name" :selected="station.id == selectedStation"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        @endif

                                        {{-- KATEGORI/MACHINE --}}
                                        <div class="mb-4">
                                            <label for="id_machines" class="block text-sm font-medium text-gray-700">Kategori Machines</label>
                                            @if ($isLeaderFA)
                                                {{-- Untuk Leader FA: hanya kirim category (string), bukan id_machines --}}
                                                <select id="category" name="category"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                        required>
                                                    <option value="">-- Pilih Kategori --</option>
                                                    @foreach ($machinesToDisplay as $machine)
                                                        <option value="{{ $machine->machines_category }}" @selected(old('category', $log?->category ?? '') == $machine->machines_category)>
                                                            {{ $machine->machines_category }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                {{-- Untuk role lain: kirim id_machines (integer) --}}
                                                <select id="id_machines" name="id_machines" x-model="selectedMachineId"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                        required>
                                                    <option value="">-- Pilih Kategori --</option>
                                                    @foreach ($machinesToDisplay as $machine)
                                                        <option value="{{ $machine->id }}" @selected(old('id_machines', $logMachineId) == $machine->id)>
                                                            {{ $machine->machines_category }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                {{-- Hidden input untuk category --}}
                                                <input type="hidden" id="hidden_category" name="category" x-bind:value="getCategoryName(selectedMachineId)">
                                            @endif
                                        </div>

                                        {{-- SERIAL NUMBER --}}
                                        <div class="mb-4">
                                            <label for="serial_number_start" class="block text-sm font-medium text-gray-700">Serial Number Start</label>
                                            <input type="text" id="serial_number_start" name="serial_number_start"
                                                   value="{{ old('serial_number_start', $log?->serial_number_start ?? '') }}"
                                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                   placeholder="Masukkan serial number awal...">
                                        </div>
                                        <div class="mb-4">
                                            <label for="serial_number_end" class="block text-sm font-medium text-gray-700">Serial Number End</label>
                                            <input type="text" id="serial_number_end" name="serial_number_end"
                                                   value="{{ old('serial_number_end', $log?->serial_number_end ?? '') }}"
                                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                   placeholder="Masukkan serial number akhir...">
                                        </div>
                                    </div>

                                    {{-- Kolom Kanan --}}
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

                                {{-- Kondisi Sebelum & Sesudah --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="before_value" class="text-gray-700 text-sm font-bold">Kondisi Sebelum</label>
                                        <input type="text" id="before_value" name="before_value"
                                               value="{{ old('before_value', $log?->description_before ?? '') }}"
                                               class="w-full py-3 px-4 border rounded bg-white text-gray-800"
                                               placeholder="Deskripsi/Versi/Part No. Sebelum" required>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="after_value" class="text-gray-700 text-sm font-bold">Kondisi Sesudah</label>
                                        <input type="text" id="after_value" name="after_value"
                                               value="{{ old('after_value', $log?->description_after ?? '') }}"
                                               autocomplete="off" class="w-full py-3 px-4 border rounded"
                                               placeholder="Deskripsi/Versi/Part No. Sesudah" required>
                                    </div>
                                </div>

                                {{-- Keterangan --}}
                                <div class="mb-6 mt-6">
                                    <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                    <textarea id="keterangan" name="keterangan" rows="4"
                                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                              placeholder="Jelaskan alasan perubahan machine/program..." required>{{ old('keterangan', $log?->keterangan ?? '') }}</textarea>
                                </div>

                                {{-- Lampiran --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                                    <input type="file" id="lampiran" name="lampiran"
                                         accept=".png,.jpg,.jpeg,.zip,.rar,application/zip,application/x-rar-compressed"
                                         class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                         {{ !isset($log) ? 'required' : '' }}>
                                </div>
                            </fieldset>

                            {{-- Tombol Aksi --}}
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
    <script>
        function henkatenFormData() {
            return {
                // Data awal dari server
                oldLineArea: '{{ old('line_area', $log?->station?->line_area ?? '') }}',
                oldStation: {{ ($isPredefinedRole && !$isLeaderFA) ? ($log?->station_id ?? $predefinedStationId) : (old('station_id', $log?->station_id ?? 'null')) }},
                oldMachineId: {{ old('id_machines', $logMachineId ?? 'null') }},
                findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                machinesData: @json($machinesToDisplay),
                role: '{{ Auth::user()->role }}',
                
                // Data reactive
                selectedLineArea: '',
                selectedStation: '',
                selectedMachineId: '',
                stationList: [],

                // Initialize
                initialize() {
                    // Set nilai awal
                    this.selectedLineArea = this.oldLineArea;
                    this.selectedStation = this.oldStation;
                    this.selectedMachineId = this.oldMachineId;

                    // Fetch stations jika line area sudah ada
                    if (this.selectedLineArea) {
                        this.fetchStations();
                    }

                    // Watch perubahan line area
                    this.$watch('selectedLineArea', (value) => {
                        if (value !== this.oldLineArea) {
                            this.selectedStation = '';
                        }
                        this.fetchStations();
                    });
                },

                // Fetch stations berdasarkan line area
                fetchStations() {
                    if (!this.selectedLineArea) {
                        this.stationList = [];
                        return;
                    }

                    fetch(this.findStationsUrl + '?line_area=' + encodeURIComponent(this.selectedLineArea))
                        .then(res => {
                            if (!res.ok) throw new Error('Network response was not ok');
                            return res.json();
                        })
                        .then(data => {
                            this.stationList = data;
                            
                            // Jika sedang edit dan station lama ada di list, restore
                            if (this.oldStation && this.stationList.find(s => s.id == this.oldStation)) {
                                this.selectedStation = this.oldStation;
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching stations:', err);
                            this.stationList = [];
                        });
                },

                // Get category name dari machine id
                getCategoryName(id) {
                    if (!id) return '';
                    const machine = this.machinesData.find(m => m.id == id);
                    return machine ? machine.machines_category : '';
                }
            }
        }
    </script>
</x-app-layout>