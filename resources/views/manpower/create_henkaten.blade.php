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

                            userRole: '{{ $userRole ?? 'Operator' }}',
                            roleLineArea: '{{ $roleLineArea ?? '' }}',

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
                            searchManpowerUrl: '{{ route('henkaten.manpower.search') }}',
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
                                                @if(isset($log))
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            <input type="text" id="serial_number_start" name="serial_number_start"
                                                value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number awal..."
                                                @if(isset($log)) required @endif>
                                        </div>

                                        {{-- Serial number end --}}
                                        <div class="mb-4">
                                            <label for="serial_number_end"
                                                class="block text-sm font-medium text-gray-700">
                                                Serial Number End
                                                @if(isset($log))
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            <input type="text" id="serial_number_end" name="serial_number_end"
                                                value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Masukkan serial number akhir..."
                                                @if(isset($log)) required @endif>
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

                                     {{-- UPDATED: Dropdown Autocomplete dengan Auto Load --}}
                                    <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md">
                                        <label for="nama_after" class="text-gray-700 text-sm font-bold mb-2 block">
                                            Nama Karyawan Sesudah <span class="text-red-500">*</span>
                                        </label>
                                        
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="nama_after" 
                                                name="nama_after"
                                                x-model="autocompleteQuery"
                                                @input.debounce.300ms="searchAfter()"
                                                @focus="openDropdown()"
                                                @blur="closeDropdown()"
                                                autocomplete="off"
                                                class="w-full py-3 px-4 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                                                :class="{'border-red-500': !afterValid}"
                                                placeholder="Klik untuk memilih...."
                                                required
                                            />
                                            
                                            <input type="hidden" name="man_power_id_after" x-model="selectedManpowerAfter">
                                            
                                            {{-- Dropdown Results --}}
                                            <div 
                                                x-show="isDropdownOpen && autocompleteResults.length > 0"
                                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                            >
                                                <template x-for="item in autocompleteResults" :key="item.id">
                                                    <div 
                                                        @click="selectAfter(item)"
                                                        class="px-4 py-3 cursor-pointer hover:bg-green-50 border-b border-gray-100 last:border-0 transition duration-150"
                                                    >
                                                        <div class="font-medium text-gray-900" x-text="item.nama"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            {{-- Loading State --}}
                                            <div 
                                                x-show="isDropdownOpen && !autocompleteResults.length && selectedGrup"
                                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg p-3"
                                            >
                                                <p class="text-sm text-gray-500 text-center">Memuat data...</p>
                                            </div>
                                            
                                            {{-- No Results --}}
                                            <div 
                                                x-show="!isDropdownOpen && autocompleteQuery.length >= 2 && !selectedManpowerAfter"
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                Tidak ada hasil ditemukan
                                            </div>
                                        </div>
                                        
                                        {{-- Validation Warning --}}
                                        <p x-show="!afterValid" class="text-red-500 text-xs mt-2">
                                            ⚠️ Man Power ini sudah bertugas pada waktu yang dipilih
                                        </p>
                                        
                                        <p class="text-xs text-green-600 mt-2 italic">
                                            Data man power pengganti (klik field untuk melihat daftar)
                                        </p>
                                    </div>
                                </div>

                                <button 
    type="button"
    @click="refreshAfterList()"
    class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 text-sm font-semibold"
>
    🔄Re-Check
</button>



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
                                            <p class="font-semibold mb-2">Dokumen yang wajib dilampirkan :</p>
                                            <ul class="list-disc list-inside space-y-1">
                                                <li><strong>Sakit :</strong> Wajib melampirkan SKS.</li>
                                                <li><strong>Cuti/Tugas Resmi :</strong> Wajib melampirkan surat tugas.</li>
                                                <li><strong>izin/Darurat :</strong> Dokumen penting lainnya</li>
                                            </ul>
                                            <p class="mt-3 italic text-xs">Pastikan lampiran jelas.</p>
                                        </div>
                                    </div>
                                </div>

                               {{-- Lampiran (3 Field) --}}
                                <div class="mb-6 mt-6">
                                    <h3 class="block text-gray-700 text-sm font-bold mb-4">Lampiran</h3>
                                    
                                    {{-- Lampiran 1  --}}
                                    <div class="mb-4">
                                        <label for="lampiran" class="block text-gray-700 text-sm font-medium mb-2">
                                            Lampiran 1 (Opsional)
                                        </label>
                                        <input type="file" id="lampiran" name="lampiran"
                                            accept=".png,.jpg,.jpeg,.pdf,.zip,.rar,application/zip,application/x-rar-compressed"
                                            class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        @if (isset($log) && $log->lampiran)
                                            <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                                <p class="text-sm text-gray-700 font-medium mb-1">Lampiran 1 saat ini:</p>
                                                <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline text-sm">
                                                    📄 Lihat Lampiran ({{ basename($log->lampiran) }})
                                                </a>
                                                <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda ingin mengganti lampiran ini.</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Lampiran 2 (Optional) --}}
                                    <div class="mb-4">
                                        <label for="lampiran_2" class="block text-gray-700 text-sm font-medium mb-2">
                                            Lampiran 2 (Opsional)
                                        </label>
                                        <input type="file" id="lampiran_2" name="lampiran_2"
                                            accept=".png,.jpg,.jpeg,.pdf,.zip,.rar,application/zip,application/x-rar-compressed"
                                            class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                        @if (isset($log) && $log->lampiran_2)
                                            <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                                <p class="text-sm text-gray-700 font-medium mb-1">Lampiran 2 saat ini:</p>
                                                <a href="{{ asset('storage/' . $log->lampiran_2) }}" target="_blank"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline text-sm">
                                                    📄 Lihat Lampiran ({{ basename($log->lampiran_2) }})
                                                </a>
                                                <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda ingin mengganti lampiran ini.</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Lampiran 3 (Optional) --}}
                                    <div class="mb-4">
                                        <label for="lampiran_3" class="block text-gray-700 text-sm font-medium mb-2">
                                            Lampiran 3 (Opsional)
                                        </label>
                                        <input type="file" id="lampiran_3" name="lampiran_3"
                                            accept=".png,.jpg,.jpeg,.pdf,.zip,.rar,application/zip,application/x-rar-compressed"
                                            class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                        @if (isset($log) && $log->lampiran_3)
                                            <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                                <p class="text-sm text-gray-700 font-medium mb-1">Lampiran 3 saat ini:</p>
                                                <a href="{{ asset('storage/' . $log->lampiran_3) }}" target="_blank"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline text-sm">
                                                    📄 Lihat Lampiran ({{ basename($log->lampiran_3) }})
                                                </a>
                                                <p class="text-xs italic text-gray-500 mt-1">Unggah file baru jika Anda ingin mengganti lampiran ini.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </fieldset>

                            <div class="flex items-center justify-end space-x-4 pt-4 border-t mt-6">
                                <a href="{{ isset($log) ? route('activity.log.manpower') : route('dashboard') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">Batal</a>

                                <button type="submit" :disabled="!afterValid"
    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
    {{ isset($log) ? 'Update Data' : 'Simpan Data' }}
</button>

<p x-show="!afterValid" class="text-red-500 text-sm mt-1">
    Karyawan ini sudah dijadwalkan sebagai Man Power After untuk shift ini.
</p>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>


   <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
document.addEventListener('alpine:init', () => {
    const initialStationsData = @json($stations ?? []);

    Alpine.data('henkatenForm', (config) => ({
        // --- Data Properties ---
        isEditing: config.isEditing,
        logId: config.logId,
        userRole: config.userRole,
        roleLineArea: config.roleLineArea,

        selectedGrup: config.oldGrup || '',
        selectedLineArea: config.oldLineArea || config.roleLineArea || '',
        selectedStation: config.oldStation || '',

        allStations: initialStationsData,
        
        manpowerBefore: {
            id: config.oldManPowerBeforeId || '',
            nama: config.oldManPowerBeforeName || ''
        },

        selectedManpowerAfter: config.oldManPowerAfterId ? (() => {
            let id = config.oldManPowerAfterId;
            if (typeof id === 'string' && id.startsWith('t-')) {
                return parseInt(id.slice(2));
            }
            return parseInt(id);
        })() : null,
        
        autocompleteQuery: config.oldManPowerAfterName || '',
        autocompleteResults: [],
        afterValid: true,
        isDropdownOpen: false,

        // URL Helpers
        findManpowerUrl: config.findManpowerUrl,
        searchManpowerUrl: config.searchManpowerUrl,
        findStationsUrl: config.findStationsUrl,
        checkAfterUrl: config.checkAfterUrl,

        // --- Computed Properties ---
        get isLeaderFAOrSMT() {
            return ['Leader FA', 'Leader SMT'].includes(this.userRole);
        },
        
        get isQCOrPPIC() {
            return ['QC', 'PPIC', 'Leader QC', 'Leader PPIC'].includes(this.userRole);
        },

        get filteredStationList() {
            let list = this.allStations;
            let lineArea = this.selectedLineArea;

            if (lineArea) {
                list = list.filter(st => st.line_area === lineArea);
            }
            
            if (this.isQCOrPPIC) {
                list = list.filter(st => {
                    const isMainOp = parseInt(st.is_main_operator);
                    return isMainOp === 1;
                });
            }
            
            return list;
        },

        get currentStationName() {
            if (this.selectedStation) {
                const st = this.allStations.find(s => s.id == this.selectedStation);
                return st ? st.station_name : 'Pilih Station';
            }
            return 'Pilih Station';
        },

        // --- Methods ---
        async init() {
            if (!this.isLeaderFAOrSMT && !this.isQCOrPPIC && this.roleLineArea) {
                this.selectedLineArea = this.roleLineArea;
            }

            if (this.isLeaderFAOrSMT || this.isQCOrPPIC) {
                this.fetchStations(false); 
            }

            if (this.selectedStation && this.selectedLineArea && (this.selectedGrup || this.isQCOrPPIC)) {
                await this.fetchManpowerBefore();
            }

            if (this.isEditing && this.selectedManpowerAfter) {
                this.validateAfter();
            }
            
            // FIXED: Tambahkan event listener untuk semua field yang mempengaruhi validasi
            const validateFields = ['effective_date', 'end_date', 'time_start', 'time_end'];
            validateFields.forEach(fieldId => {
                document.getElementById(fieldId)?.addEventListener('change', () => {
                    this.validateDateInputs();
                    // FIXED: Hanya validasi jika man_power_after sudah dipilih
                    if (this.selectedManpowerAfter) {
                        this.validateAfter();
                    }
                });
            });
        },

        fetchStations(resetStation = true) {
            if (resetStation) {
                const currentStationExists = this.filteredStationList.some(st => st.id == this.selectedStation);
                
                if (!currentStationExists) {
                    this.selectedStation = '';
                    this.manpowerBefore = { id: '', nama: '' };
                }
            }
        },

        async fetchManpowerBefore() {
            const requiresGroup = !this.isQCOrPPIC;
            if (!this.selectedStation || !this.selectedLineArea || (requiresGroup && !this.selectedGrup)) {
                this.manpowerBefore = { id: '', nama: '' };
                return;
            }
            this.manpowerBefore = { id: '', nama: 'Memuat...' };
            
            try {
                const url = new URL(this.findManpowerUrl, window.location.origin);
                url.searchParams.append('station_id', this.selectedStation);
                url.searchParams.append('line_area', this.selectedLineArea);
                if (this.selectedGrup) {
                    url.searchParams.append('grup', this.selectedGrup);
                }

                const res = await fetch(url);
                const data = await res.json();
                
                this.manpowerBefore = data && data.id ? {
                    id: data.id,
                    nama: data.nama
                } : { id: '', nama: 'Tidak ada Man Power bertugas' };
            } catch (e) {
                console.error('fetchManpowerBefore error', e);
                this.manpowerBefore = { id: '', nama: 'Gagal memuat data' };
            }
        },

        async searchAfter() {
            this.autocompleteResults = [];
            
            if (!this.selectedLineArea || (!this.selectedGrup && !this.isQCOrPPIC)) {
                return;
            }
            
            if (!this.isLeaderFAOrSMT && !this.isQCOrPPIC && !this.selectedStation) {
                return;
            }
            
            const stationIdToSend = this.selectedStation || ''; 

            try {
                const params = new URLSearchParams({
                    query: this.autocompleteQuery.trim(),
                    grup: this.selectedGrup || '',
                    line_area: this.selectedLineArea || '',
                    station_id: stationIdToSend 
                });

                const url = `${this.searchManpowerUrl}?${params.toString()}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Response Text:', errorText); 
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                this.autocompleteResults = await response.json();

            } catch (error) {
                console.error('Search error:', error);
                this.autocompleteResults = [];
            }
        },

        async openDropdown() {
            this.isDropdownOpen = true;
            
            const canLoad = this.selectedLineArea &&
                           (this.selectedGrup || this.isQCOrPPIC) &&
                           (this.isLeaderFAOrSMT || this.isQCOrPPIC || this.selectedStation);
            
            if (canLoad && this.autocompleteResults.length === 0) {
                await this.searchAfter();
            }
        },

        closeDropdown() {
            setTimeout(() => {
                this.isDropdownOpen = false;
            }, 200);
        },

        selectAfter(item) {
            this.autocompleteQuery = item.nama;
            
            let cleanId = item.id;
            if (typeof cleanId === 'string' && cleanId.startsWith('t-')) {
                cleanId = parseInt(cleanId.slice(2));
            } else {
                cleanId = parseInt(cleanId);
            }
            
            this.selectedManpowerAfter = isNaN(cleanId) ? null : cleanId;
            this.autocompleteResults = [];
            this.isDropdownOpen = false;
            this.validateAfter();
        },

        validateDateInputs() {
            const effectiveDate = document.getElementById('effective_date')?.value;
            const endDate = document.getElementById('end_date')?.value;
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            
            const effectiveInput = document.getElementById('effective_date');
            const endInput = document.getElementById('end_date');
            
            if (effectiveDate && !dateRegex.test(effectiveDate)) {
                effectiveInput?.classList.add('border-red-500');
                effectiveInput?.classList.remove('border-gray-300');
            } else {
                effectiveInput?.classList.remove('border-red-500');
                effectiveInput?.classList.add('border-gray-300');
            }
            
            if (endDate && !dateRegex.test(endDate)) {
                endInput?.classList.add('border-red-500');
                endInput?.classList.remove('border-gray-300');
            } else {
                endInput?.classList.remove('border-red-500');
                endInput?.classList.add('border-gray-300');
            }
        },

        // FIXED: Validasi input sebelum fetch
         async validateAfter() {
            const shift = document.querySelector('input[name="shift"]')?.value;
            const effectiveDate = document.getElementById('effective_date')?.value;
            const endDate = document.getElementById('end_date')?.value;
            
            // Validate that we have all required values
            if (!this.selectedManpowerAfter || !shift || !effectiveDate || !endDate) {
                this.afterValid = true;
                return;
            }

            // Validate date format (YYYY-MM-DD) to prevent malformed API calls
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(effectiveDate) || !dateRegex.test(endDate)) {
                console.warn('Invalid date format detected. Expected YYYY-MM-DD format.');
                this.afterValid = true; // Don't block form if dates are invalid, let backend validation handle it
                return;
            }

            // manpowerId sudah dalam bentuk integer, tidak perlu dibersihkan lagi
            let manpowerId = this.selectedManpowerAfter;

            try {
                const url = new URL(this.checkAfterUrl, window.location.origin);
                url.searchParams.append('man_power_id_after', manpowerId);
                url.searchParams.append('grup', this.selectedGrup);
                url.searchParams.append('shift', shift);
                url.searchParams.append('effective_date', effectiveDate);
                url.searchParams.append('end_date', endDate);
                
                // Fixed: use this.isEditing instead of config.isEditing
                if (this.logId && this.isEditing) {
                    url.searchParams.append('ignore_log_id', this.logId);
                }

                const res = await fetch(url);
                
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }

                const data = await res.json();
                this.afterValid = !data.exists; 
                
            } catch (e) {
                console.error("validateAfter error", e);
                // Set afterValid to true to prevent blocking the form
                // Backend validation will catch any real issues
                this.afterValid = true; 
            }
        },

        refreshAfterList() {
            this.autocompleteQuery = "";
            this.selectedManpowerAfter = null;
            this.autocompleteResults = [];
            this.isDropdownOpen = true;
            this.searchAfter();
        }
    }));
});
</script>
</x-app-layout>