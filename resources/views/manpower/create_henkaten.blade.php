<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($log) ? 'Edit Data' : 'Buat Data' }} Henkaten Man Power
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

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
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative"
                            role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false"
                                class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">
                                &times;
                            </button>
                        </div>
                    @endif

                    <form
                        action="{{ isset($log) ? route('activity.log.manpower.update', $log->id) : route('henkaten.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($log))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? $currentShift) }}">

                        <div x-data="henkatenForm({
                            isEditing: {{ isset($log) ? 'true' : 'false' }},
                            logId: {{ isset($log) ? $log->id : 'null' }},

                            // nilai dari controller
                            userRole: '{{ $userRole ?? 'Operator' }}',
                            roleLineArea: '{{ $roleLineArea ?? '' }}',

                            // flags dari controller (pastikan controller mengirimkan ini)
                            isMainOperator: {{ isset($isMainOperator) && $isMainOperator ? 'true' : 'false' }},
                            showStationDropdown: {{ isset($showStationDropdown) && $showStationDropdown ? 'true' : 'false' }},

                            oldGrup: '{{ old('grup', $log->grup ?? $currentGroup) }}',
                            oldLineArea: '{{ old('line_area', $log->line_area ?? '') }}',
                            oldStation: {{ old('station_id', $log->station_id ?? 'null') }},

                            oldManPowerBeforeId: {{ old('man_power_id', $log->man_power_id ?? 'null') }},
                            oldManPowerBeforeName: '{{ old('nama', $log->nama ?? '') }}',

                            oldManPowerAfterId: {{ old('man_power_id_after', $log->man_power_id_after ?? 'null') }},
                            oldManPowerAfterName: '{{ old('nama_after', $log->nama_after ?? '') }}',

                            findManpowerUrl: '{{ route('henkaten.getManPower') }}',
                            searchManpowerUrl: '{{ route('manpower.search') }}',
                            findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                            checkAfterUrl: '{{ route('henkaten.checkAfter') }}'
                        })" x-init="init()">

                            <fieldset>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- KIRI --}}
                                    <div>
                                        {{-- Grup --}}
                                        @if (isset($log))
                                            <div class="mb-4">
                                                <label for="grup"
                                                    class="block text-sm font-medium text-gray-700">Grup
                                                    <span class="text-red-500">*</span></label>
                                                <select id="grup" name="grup" x-model="selectedGrup"
                                                    @change="fetchManpowerBefore"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                                    <option value="">-- Pilih Grup --</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" name="grup" value="{{ $currentGroup }}">
                                        @endif

                                        {{-- LINE AREA (Tergantung Role) --}}
                                        <div class="mb-4">
                                            <label for="line_area" class="block text-sm font-medium text-gray-700">Line
                                                Area
                                                <span x-show="isLeaderFAOrSMT || isQCOrPPIC" class="text-red-500">*</span>
                                            </label>

                                            <template x-if="(isLeaderFAOrSMT || isQCOrPPIC)">
                                                <select id="line_area_leader" name="line_area"
                                                    x-model="selectedLineArea" @change="fetchStations"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                                    <option value="">-- Pilih Line Area --</option>
                                                    @foreach ($lineAreas as $area)
                                                        <option value="{{ $area }}">{{ $area }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </template>

                                            <template x-if="!(isLeaderFAOrSMT || isQCOrPPIC)">
                                                <div>
                                                    <input type="text" id="line_area_display"
                                                        :value="selectedLineArea" readonly
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-600">
                                                    <input type="hidden" name="line_area" :value="selectedLineArea">
                                                </div>
                                            </template>

                                        </div>

                                        {{-- STATION: tampilkan dropdown --}}
                                        <div class="mb-4">
                                            <label for="station_id"
                                                class="block text-sm font-medium text-gray-700">Station
                                                <span x-show="isLeaderFAOrSMT || isQCOrPPIC" class="text-red-500">*</span>
                                            </label>

                                            <template x-if="(isLeaderFAOrSMT || isQCOrPPIC)">
                                                <select id="station_id_dropdown" name="station_id"
                                                    x-model="selectedStation" @change="fetchManpowerBefore"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                                    <option value="">-- Pilih Station --</option>
                                                    <template x-for="st in filteredStationList" :key="st.id">
                                                        <option :value="st.id" x-text="st.station_name">
                                                        </option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="!isLeaderFAOrSMT && !isQCOrPPIC">
                                                <div>
                                                    <input type="text" id="station_name_display"
                                                        :value="currentStationName" readonly
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-600">
                                                    <input type="hidden" name="station_id" x-model="selectedStation">
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Serial number start --}}
                                        <div class="mb-4">
                                            <label for="serial_number_start"
                                                class="block text-sm font-medium text-gray-700">
                                                Serial Number Start
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="serial_number_start" name="serial_number_start"
                                                value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number awal..."
                                                required>
                                        </div>

                                        {{-- Serial number end --}}
                                        <div class="mb-4">
                                            <label for="serial_number_end"
                                                class="block text-sm font-medium text-gray-700">
                                                Serial Number End
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="serial_number_end" name="serial_number_end"
                                                value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number akhir..."
                                                required>
                                        </div>
                                    </div>

                                    {{-- KANAN: tanggal & waktu --}}
                                    <div>
                                        <div class="mb-4">
                                            <label for="effective_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal
                                                Efektif <span class="text-red-500">*</span></label>
                                            <input type="date" id="effective_date" name="effective_date"
                                                value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="end_date"
                                                class="block text-gray-700 text-sm font-bold mb-2">Tanggal
                                                Berakhir <span class="text-red-500">*</span></label>
                                            <input type="date" id="end_date" name="end_date"
                                                value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_start"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai <span class="text-red-500">*</span></label>
                                            <input type="time" id="time_start" name="time_start"
                                                value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="time_end"
                                                class="block text-gray-700 text-sm font-bold mb-2">Waktu
                                                Berakhir <span class="text-red-500">*</span></label>
                                            <input type="time" id="time_end" name="time_end"
                                                value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Before & After --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <label for="nama_before_display" class="text-gray-700 text-sm font-bold">Nama
                                            Karyawan Sebelum</label>
                                        <input type="text" id="nama_before_display" x-model="manpowerBefore.nama"
                                            readonly class="w-full py-3 px-4 border rounded bg-gray-100 text-gray-600"
                                            placeholder="Nama Man Power Sebelum">
                                        <input type="hidden" name="man_power_id" x-model="manpowerBefore.id">
                                        <p class="text-xs text-gray-500 mt-2 italic">Data man power yang diganti
                                            (otomatis berdasarkan grup, line, & station)</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative">
                                        <label for="nama_after" class="text-gray-700 text-sm font-bold">Nama Karyawan
                                            Sesudah <span class="text-red-500">*</span></label>
                                        <input type="text" id="nama_after" name="nama_after"
                                            x-model="autocompleteQuery" @input.debounce.300="searchAfter()"
                                            @click.away="autocompleteResults = []" autocomplete="off"
                                            class="w-full py-3 px-4 border rounded"
                                            placeholder="Masukkan Nama Man Power Pengganti..." required>
                                        <input type="hidden" name="man_power_id_after"
                                            x-model="selectedManpowerAfter">
                                        <ul x-show="autocompleteResults.length > 0"
                                            class="absolute z-10 bg-white border w-full mt-1 rounded-md shadow-md max-h-60 overflow-auto">
                                            <template x-for="item in autocompleteResults" :key="item.id">
                                                <li @click="selectAfter(item)"
                                                    class="px-4 py-2 cursor-pointer hover:bg-green-100"
                                                    x-text="item.nama"></li>
                                            </template>
                                        </ul>
                                        <p class="text-xs text-green-600 mt-2 italic">Data man power pengganti</p>
                                    </div>
                                </div>

                                {{-- Keterangan & Syarat --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label for="keterangan"
                                            class="block text-gray-700 text-sm font-bold mb-2">Keterangan <span class="text-red-500">*</span></label>
                                        <textarea id="keterangan" name="keterangan" rows="6"
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Syarat & Ketentuan
                                            Lampiran</label>
                                        <div
                                            class="bg-gray-50 p-4 rounded-md border border-gray-200 text-sm text-gray-600 h-full">
                                            <p class="font-semibold mb-2">Dokumen yang wajib dilampirkan untuk
                                                Izin/Sakit:</p>
                                            <ul class="list-disc list-inside space-y-1">
                                                <li><strong>Sakit:</strong> Wajib melampirkan SKS.</li>
                                                <li><strong>Izin Resmi:</strong> Wajib melampirkan surat izin.</li>
                                                <li><strong>Darurat/Lainnya:</strong> Dokumen pendukung lain.</li>
                                            </ul>
                                            <p class="mt-3 italic text-xs">Pastikan lampiran jelas.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Lampiran --}}
                                <div class="mb-6 mt-6">
                                    <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran
                                        (Wajib untuk Izin/Sakit)
                                        @if (!isset($log) || (isset($log) && !$log->lampiran))
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="file" id="lampiran" name="lampiran"
                                        accept=".png,.jpg,.jpeg,.zip,.rar,application/zip,application/x-rar-compressed"
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        {{ !isset($log) ? 'required' : '' }}>
                                    @if (isset($log) && $log->lampiran)
                                        <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                            <p class="text-sm text-gray-700 font-medium mb-1">Lampiran saat ini:</p>
                                            <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                                class="text-blue-600 hover:text-blue-800 hover:underline">
                                                Lihat Lampiran ({{ basename($log->lampiran) }})
                                            </a>
                                            <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda
                                                ingin mengganti lampiran ini.</p>
                                        </div>
                                    @endif
                                </div>

                            </fieldset>

                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                <a href="{{ isset($log) ? route('activity.log.manpower') : route('dashboard') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">Batal</a>

                                <button type="submit" :disabled="!afterValid"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                    {{ isset($log) ? 'Update Data' : 'Simpan Data' }}
                                </button>
                                <p x-show="!afterValid" class="text-red-500 text-sm mt-1">Karyawan ini sudah
                                    dijadwalkan sebagai Man Power After untuk shift ini.</p>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('alpine:init', () => {
            // Deklarasi data stasiun mentah awal (data dari Controller)
            const initialStationsData = @json($stations);
            console.log('--- Initial Stations Data (allStations) ---');
            console.log(initialStationsData);

            Alpine.data('henkatenForm', (config) => ({
                userRole: config.userRole,
                roleLineArea: config.roleLineArea,
                isMainOperator: config.isMainOperator,
                showStationDropdown: config.showStationDropdown,

                selectedGrup: config.oldGrup || '',
                selectedLineArea: config.oldLineArea || '',
                selectedStation: config.oldStation || '',

                allStations: initialStationsData,
                stationList: initialStationsData,

                logId: config.logId,
                manpowerBefore: {
                    id: config.oldManPowerBeforeId || '',
                    nama: config.oldManPowerBeforeName || ''
                },
                selectedManpowerAfter: config.oldManPowerAfterId || '',
                autocompleteQuery: config.oldManPowerAfterName || '',
                autocompleteResults: [],
                afterValid: true,

                findManpowerUrl: config.findManpowerUrl,
                searchManpowerUrl: config.searchManpowerUrl,
                findStationsUrl: config.findStationsUrl,
                checkAfterUrl: config.checkAfterUrl,

                get isLeaderFAOrSMT() {
                    return this.userRole === 'Leader FA' || this.userRole === 'Leader SMT';
                },
                get isQCOrPPIC() {
                    return this.userRole === 'Leader QC' || this.userRole === 'Leader PPIC';
                },

                // REVISI LOGIKA FILTER UTAMA
                // REVISI LOGIKA FILTER UTAMA
                get filteredStationList() {
                    let list = this.stationList;
                    let lineArea = this.selectedLineArea;

                    // Asumsi: Line Area sudah terisi di init()

                    // 1. Filter berdasarkan Line Area yang dipilih/terisi
                    if (lineArea) {
                        list = list.filter(st => st.line_area === lineArea);
                    }

                    if (this.isQCOrPPIC) {
                        // Filter Main Operator menggunakan konversi string yang kuat
                        const filteredByMainOp = list.filter(st => {
                            const val = st.is_main_operator;

                            // Cek apakah nilai ada dan, jika dikonversi menjadi string dan di-trim, sama dengan '1'
                            if (val != null) {
                                const isMain = String(val).trim() === '1';
                                return isMain;
                            }
                            return false; // Abaikan jika is_main_operator null/undefined
                        });

                        // DEBUGGING OUTPUT FINAL
                        // console.log(`[QC/PPIC Filter] Stations by Main Op (${filteredByMainOp.length}):`, filteredByMainOp);

                        return filteredByMainOp;
                    }

                    // Untuk Leader FA/SMT dan default
                    return list;
                },


                get currentStationName() {
                    if (this.selectedStation) {
                        const st = this.allStations.find(s => s.id == this.selectedStation);
                        return st ? st.station_name : 'Memuat...';
                    }
                    return 'Pilih Station';
                },

                async init() {
                    // 1. Set Line Area otomatis untuk QC/PPIC atau Operator yang Line Area-nya fix
                    if (!this.isLeaderFAOrSMT && this.roleLineArea) {
                        this.selectedLineArea = this.roleLineArea;
                    }

                    // 2. Cek apakah ini Leader FA/SMT dengan Line Area yang sudah dipilih (dari old data)
                    if (this.isLeaderFAOrSMT && this.selectedLineArea) {
                        await this.fetchStations(false, false);
                    }

                    // 3. Panggil computed property sekali untuk memicu debugging output di console
                    this.filteredStationList;

                    // 4. Jika selectedStation sudah ada (dari old data), fetch manpower before
                    if (this.selectedStation) {
                        await this.fetchManpowerBefore();
                    }

                    // Event listeners dan validasi
                    document.getElementById('effective_date')?.addEventListener('change', () => this
                        .validateAfter());
                    document.getElementById('end_date')?.addEventListener('change', () => this
                        .validateAfter());

                    if (config.isEditing && this.selectedManpowerAfter) {
                        this.validateAfter();
                    }
                },

                // Fungsi fetchStations dan Man Power tidak diubah
                async fetchStations(resetStation = true, loadFirstStation = false) {
                    if (!this.selectedLineArea || !this.isLeaderFAOrSMT) {
                        this.stationList = this.allStations;
                        if (resetStation) this.selectedStation = '';
                        this.manpowerBefore = {
                            id: '',
                            nama: ''
                        };
                        return;
                    }

                    try {
                        const url = new URL(this.findStationsUrl, window.location.origin);
                        url.searchParams.append('line_area', this.selectedLineArea);
                        url.searchParams.append('role', this.userRole);

                        const res = await fetch(url);
                        const data = await res.json();

                        this.stationList = Array.isArray(data) ? data : (data.data ?? []);

                        if (resetStation) this.selectedStation = '';

                        if (this.selectedStation) {
                            this.fetchManpowerBefore();
                        } else {
                            this.manpowerBefore = {
                                id: '',
                                nama: ''
                            };
                        }
                    } catch (e) {
                        console.error('fetchStations error', e);
                        this.stationList = [];
                    }
                },

                async fetchManpowerBefore() {
                    if (!this.selectedStation || !this.selectedLineArea || !this.selectedGrup) {
                        this.manpowerBefore = {
                            id: '',
                            nama: ''
                        };
                        return;
                    }
                    try {
                        const url = new URL(this.findManpowerUrl, window.location.origin);
                        url.searchParams.append('station_id', this.selectedStation);
                        url.searchParams.append('line_area', this.selectedLineArea);
                        url.searchParams.append('grup', this.selectedGrup);

                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        this.manpowerBefore = data && data.nama ? {
                            id: data.id,
                            nama: data.nama
                        } : {
                            id: '',
                            nama: ''
                        };
                    } catch (e) {
                        console.error('fetchManpowerBefore error', e);
                        this.manpowerBefore = {
                            id: '',
                            nama: ''
                        };
                    }
                },

                async searchAfter() {
                    if (this.autocompleteQuery.length < 2 || !this.selectedGrup) {
                        this.autocompleteResults = [];
                        return;
                    }
                    try {
                        const url = new URL(this.searchManpowerUrl, window.location.origin);
                        url.searchParams.append('query', this.autocompleteQuery);
                        url.searchParams.append('grup', this.selectedGrup);

                        const res = await fetch(url);
                        const data = await res.json();
                        const list = Array.isArray(data) ? data : (data.data ?? []);
                        this.autocompleteResults = list;
                    } catch (e) {
                        console.error('searchAfter error', e);
                        this.autocompleteResults = [];
                    }
                },

                selectAfter(item) {
                    this.autocompleteQuery = item.nama;
                    this.selectedManpowerAfter = item.id;
                    this.autocompleteResults = [];
                    this.validateAfter();
                },

                async validateAfter() {
                    const effectiveDate = document.getElementById('effective_date')?.value;
                    const endDate = document.getElementById('end_date')?.value;

                    if (!this.selectedManpowerAfter || !this.checkAfterUrl || !effectiveDate || !
                        endDate) {
                        this.afterValid = true;
                        return;
                    }

                    if (this.manpowerBefore.id && this.selectedManpowerAfter === this.manpowerBefore
                        .id) {
                        this.afterValid = false;
                        return;
                    }

                    try {
                        const url = new URL(this.checkAfterUrl, window.location.origin);
                        url.searchParams.append('man_power_id_after', this.selectedManpowerAfter);
                        url.searchParams.append('grup', this.selectedGrup);
                        url.searchParams.append('shift', '{{ $currentShift }}');
                        url.searchParams.append('effective_date', effectiveDate);
                        url.searchParams.append('end_date', endDate);
                        if (this.logId) url.searchParams.append('ignore_log_id', this.logId);

                        const res = await fetch(url);
                        const data = await res.json();
                        this.afterValid = !data.exists;
                    } catch (e) {
                        console.error("validateAfter error", e);
                        this.afterValid = true;
                    }
                }
            }));
        });
    </script>
</x-app-layout>