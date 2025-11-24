<x-app-layout>
    {{-- HEADER SECTION --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($log) ? __('Edit Data Henkaten Method') : __('Buat Data Henkaten Method') }}
        </h2>
    </x-slot>

    {{-- MAIN CONTENT AREA --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- ERROR MESSAGE DISPLAY --}}
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

                    {{-- SUCCESS MESSAGE DISPLAY --}}
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative" role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false" class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">&times;</button>
                        </div>
                    @endif

                    {{-- PHP VARIABLE SETUP --}}
                    @php
                        $formAction = isset($log)
                            ? route('activity.log.method.update', $log->id)
                            : route('henkaten.method.store');

                        $userRole = Auth::user()->role ?? 'Guest';
                        $isPredefinedRole = in_array($userRole, ['Leader QC', 'Leader PPIC']);

                        $predefinedLineArea = match ($userRole) {
                            'Leader QC' => 'Incoming',
                            'Leader PPIC' => 'Delivery',
                            default => null,
                        };

                        $currentShift = $currentShift ?? 1;

                        // PENTING: Untuk Predefined Role, ambil Station ID default
                        $predefinedStationId = null;
                        if ($isPredefinedRole && $predefinedLineArea) {
                            // Anda harus memastikan logic ini (atau di Controller) mengambil ID yang benar.
                            // Contoh: ambil Station ID pertama untuk Line Area tersebut
                            $defaultStation = \App\Models\Station::where('line_area', $predefinedLineArea)->first();
                            $predefinedStationId = $defaultStation->id ?? null;
                            
                            // Jika ini mode edit, gunakan station_id dari log
                            if (isset($log)) {
                                $predefinedStationId = $log->station_id;
                            }
                        }

                        // Untuk Dynamic Mode, siapkan data awal untuk Alpine
                        $initialStations = [];
                        $initialMethods = [];
                        if (!$isPredefinedRole && (isset($log) || old('line_area'))) {
                            $initialLineArea = old('line_area', $log->station->line_area ?? '');
                            $initialStationId = old('station_id', $log->station_id ?? null);
                            
                            // Asumsi Anda telah memuat data ini di Controller
                            // Contoh: $initialStations = \App\Models\Station::where('line_area', $initialLineArea)->get();
                            // Contoh: $initialMethods = \App\Models\HenkatenMethod::where('station_id', $initialStationId)->get();
                        }
                    @endphp

                    {{-- FORM START --}}
                    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($log))
                            @method('PUT')
                        @endif

                        {{-- Hidden Shift Input --}}
                        <input type="hidden" name="shift" value="{{ old('shift', $log->shift ?? $currentShift) }}">

                        {{-- GRID CONTAINER (Alpine.js Scope) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
                            x-data="{
                                selectedLineArea: '{{ $isPredefinedRole ? $predefinedLineArea : old('line_area', $log->station->line_area ?? '') }}',
                                selectedStation: '{{ old('station_id', $log->station_id ?? $predefinedStationId ?? '') }}',
                                selectedMethodId: '{{ old('method_id', $log->method_id ?? '') }}',
                                
                                lineAreas: @json($lineAreas ?? []), 
                                stationList: @json($initialStations),
                                methodList: @json($initialMethods),
                                
                                isPredefinedRole: {{ $isPredefinedRole ? 'true' : 'false' }},
                                findStationsUrl: '{{ route('henkaten.stations.by_line') }}',
                                findMethodsUrl: '{{ route('henkaten.methods.by_station') }}',
                                
                                async fetchStations() {
                                    this.selectedStation = '';
                                    this.methodList = [];
                                    this.selectedMethodId = '';
                                    this.stationList = [];
                                    if (!this.selectedLineArea) return;

                                    try {
                                        const res = await fetch(`${this.findStationsUrl}?line_area=${encodeURIComponent(this.selectedLineArea)}`);
                                        const data = await res.json();
                                        this.stationList = Array.isArray(data) ? data : (data.data ?? []);
                                    } catch (err) {
                                        console.error('Gagal fetch stations:', err);
                                    }
                                },
                                
                                async fetchMethods() {
                                    this.methodList = [];
                                    this.selectedMethodId = '';
                                    if (!this.selectedStation) return;
                                    try {
                                        const res = await fetch(`${this.findMethodsUrl}?station_id=${this.selectedStation}`);
                                        const data = await res.json();
                                        this.methodList = Array.isArray(data) ? data : (data.data ?? []);
                                    } catch (err) {
                                        console.error('Gagal fetch methods:', err);
                                    }
                                }
                            }"
                            x-init="!isPredefinedRole && selectedLineArea && stationList.length === 0 && fetchStations(false)">

                            {{-- COLUMN KIRI: Dropdown & Serial Number --}}
                            <div>
                                {{-- PREDEFINED ROLE MODE (Leader QC/PPIC) --}}
                                @if ($isPredefinedRole)
                                    {{-- LINE AREA (Readonly) --}}
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <input type="text" value="{{ $predefinedLineArea }}" readonly
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                        <input type="hidden" name="line_area" value="{{ $predefinedLineArea }}">
                                    </div>

                                    {{-- STATION ID (Hidden) --}}
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Station</label>
                                        
                                        {{-- INI ADALAH PERBAIKAN KRITIS: Memastikan nilai ID stasiun ada --}}
                                        <input type="hidden" name="station_id" 
                                            value="{{ old('station_id', $log->station_id ?? $predefinedStationId ?? '') }}">
                                        
                                        {{-- Tampilkan station name untuk verifikasi --}}
                                        <input type="text" value="{{ $log->station->station_name ?? \App\Models\Station::find($predefinedStationId)->station_name ?? 'Station Not Set / Missing ID' }}" readonly
                                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                                    </div>

                                    {{-- METHOD NAME (Dropdown - For Predefined Roles) --}}
                                    <div class="mb-4">
                                        <label for="methods_name_input" class="block text-sm font-medium text-gray-700">Nama Method</label>
                                        <select id="methods_name_input" name="methods_name" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedMethodName">
                                            <option value="">-- Pilih Method --</option>
                                            @foreach($methodList as $method)
                                                <option value="{{ is_array($method) ? $method['methods_name'] : $method->methods_name }}" 
                                                    {{ old('methods_name', $log->methods_name ?? '') == (is_array($method) ? $method['methods_name'] : $method->methods_name) ? 'selected' : '' }}>
                                                    {{ is_array($method) ? $method['methods_name'] : $method->methods_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    {{-- DYNAMIC MODE (General User/Non-Predefined Role) --}}

                                    {{-- LINE AREA (Dropdown) --}}
                                    <div class="mb-4">
                                        <label for="line_area" class="block text-sm font-medium text-gray-700">Line Area</label>
                                        <select id="line_area" name="line_area" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedLineArea"
                                                @change="fetchStations()">
                                            <option value="">-- Pilih Line Area --</option>
                                            <template x-for="area in lineAreas" :key="area">
                                                <option :value="area" x-text="area" :selected="area == selectedLineArea"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- STATION ID (Dropdown) --}}
                                    <div class="mb-4">
                                        <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                        <select id="station_id" name="station_id" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedStation"
                                                @change="fetchMethods()"
                                                :disabled="stationList.length === 0">
                                            <option value="">-- Pilih Station --</option>
                                            <template x-for="station in stationList" :key="station.id">
                                                <option :value="station.id" x-text="station.station_name" :selected="station.id == selectedStation"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- METHOD ID (Dropdown) --}}
                                    <div class="mb-4">
                                        <label for="methods_name" class="block text-sm font-medium text-gray-700">Nama Method</label>
                                        <select id="methods_name" name="method_id" required
                                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm"
                                                x-model="selectedMethodId"
                                                :disabled="methodList.length === 0">
                                            <option value="">-- Pilih Method --</option>
                                            <template x-for="method in methodList" :key="method.id">
                                                <option :value="method.id" x-text="method.methods_name" :selected="method.id == selectedMethodId"></option>
                                            </template>
                                        </select>
                                    </div>
                                @endif

                                {{-- SERIAL NUMBER START --}}
                                <div class="mb-4">
                                    <label for="serial_number_start" class="block text-sm font-medium text-gray-700">Serial Number Start</label>
                                    <input type="text" id="serial_number_start" name="serial_number_start"
                                        value="{{ old('serial_number_start', $log->serial_number_start ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                </div>

                                {{-- SERIAL NUMBER END --}}
                                <div class="mb-4">
                                    <label for="serial_number_end" class="block text-sm font-medium text-gray-700">Serial Number End</label>
                                    <input type="text" id="serial_number_end" name="serial_number_end"
                                        value="{{ old('serial_number_end', $log->serial_number_end ?? '') }}"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>

                            {{-- COLUMN KANAN: Tanggal & Waktu --}}
                            <div>
                                {{-- EFFECTIVE DATE --}}
                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    <input type="date" id="effective_date" name="effective_date"
                                        value="{{ old('effective_date', isset($log) ? \Carbon\Carbon::parse($log->effective_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- END DATE --}}
                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                    <input type="date" id="end_date" name="end_date"
                                        value="{{ old('end_date', isset($log) ? \Carbon\Carbon::parse($log->end_date)->format('Y-m-d') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- TIME START --}}
                                <div class="mb-4">
                                    <label for="time_start" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai</label>
                                    <input type="time" id="time_start" name="time_start"
                                        value="{{ old('time_start', isset($log) ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>

                                {{-- TIME END --}}
                                <div class="mb-4">
                                    <label for="time_end" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir</label>
                                    <input type="time" id="time_end" name="time_end"
                                        value="{{ old('time_end', isset($log) ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                                </div>
                            </div>
                        </div> {{-- End Grid/Alpine Scope --}}

                        {{-- KETERANGAN TEXTAREAS --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            {{-- KETERANGAN SEBELUM --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sebelum</label>
                                <textarea id="keterangan" name="keterangan" rows="4"
                                    placeholder="Jelaskan kondisi method sebelum perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan', $log->keterangan ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2 italic">Data method sebelum perubahan</p>
                            </div>

                            {{-- KETERANGAN SESUDAH --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md">
                                <label for="keterangan_after" class="block text-gray-700 text-sm font-bold mb-2">Keterangan Sesudah Pergantian</label>
                                <textarea id="keterangan_after" name="keterangan_after" rows="4"
                                    placeholder="Jelaskan kondisi method setelah perubahan..."
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                    required>{{ old('keterangan_after', $log->keterangan_after ?? '') }}</textarea>
                                <p class="text-xs text-green-600 mt-2 italic">Data method setelah perubahan</p>
                            </div>
                        </div>

                        {{-- LAMPIRAN / FILE UPLOAD --}}
                        <div class="mb-6 mt-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                {{ isset($log) ? '' : 'required' }}>

                            @if(isset($log) && $log->lampiran)
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>File saat ini:
                                        <a href="{{ asset('storage/'. $log->lampiran) }}" target="_blank"
                                            class="text-blue-600 hover:underline font-medium">
                                            Lihat Lampiran
                                        </a>
                                    </p>
                                    <p class="text-xs italic text-gray-500">Kosongkan input file jika tidak ingin mengubah lampiran.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('activity.log.method') }}"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                {{ isset($log) ? 'Simpan Perubahan' : 'Simpan Data' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- ALPINE.JS SCRIPT --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

</x-app-layout>