<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Data Henkaten Man Power') }}
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


                    <form action="{{ route('henkaten.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Kolom Kiri --}}
                            <div>
                                <div class="mb-4">
                                    <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                                    <select id="shift" name="shift"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                        <option value="1" {{ old('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                                        <option value="2" {{ old('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line
                                        Area</label>
                                    <input type="text" id="line_area" name="line_area" value="{{ old('line_area') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                </div>

                                <div class="mb-4">
                                    <label for="effective_date"
                                        class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    <input type="date" id="effective_date" name="effective_date"
                                        value="{{ old('effective_date') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                </div>
                            </div>

                            {{-- Kolom Kanan --}}
                            <div class="mb-4">
                                <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                <select id="station_id" name="station_id" required
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                    <option value="">-- Pilih Station --</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->id }}">{{ $station->station_name }}</option>
                                    @endforeach
                                </select>

                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal
                                        Berakhir</label>
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            {{-- Before --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md relative"
                                x-data="autocomplete('/manpower/search')">
                                <label class="text-gray-700 text-sm font-bold">Nama Karyawan Sebelumnya</label>
                                <input type="text" name="nama" x-model="query" @input.debounce.300="search()"
                                    autocomplete="off" class="w-full py-3 px-4 border rounded"
                                    placeholder="Masukkan Nama...">
                                <input type="hidden" name="man_power_id" x-model="selectedId">

                                <ul x-show="results.length > 0"
                                    class="absolute z-10 bg-white border w-full mt-1 rounded-md shadow-md max-h-60 overflow-auto">
                                    <template x-for="item in results" :key="item.id">
                                        <li @click="select(item)" class="px-4 py-2 cursor-pointer hover:bg-blue-100"
                                            x-text="item.nama"></li>
                                    </template>
                                </ul>
                                <p class="text-xs text-gray-500 mt-2 italic">Data karyawan sebelum perpindahan</p>
                            </div>

                            {{-- After --}}
                            <div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md relative"
                                x-data="autocomplete('/manpower/search')">
                                <label class="text-gray-700 text-sm font-bold">Nama Karyawan Sesudah</label>
                                <input type="text" name="nama_after" x-model="query" @input.debounce.300="search()"
                                    autocomplete="off" class="w-full py-3 px-4 border rounded"
                                    placeholder="Masukkan Nama...">
                                <input type="hidden" name="man_power_id_after" x-model="selectedId">

                                <ul x-show="results.length > 0"
                                    class="absolute z-10 bg-white border w-full mt-1 rounded-md shadow-md max-h-60 overflow-auto">
                                    <template x-for="item in results" :key="item.id">
                                        <li @click="select(item)" class="px-4 py-2 cursor-pointer hover:bg-green-100"
                                            x-text="item.nama"></li>
                                    </template>
                                </ul>
                                <p class="text-xs text-green-600 mt-2 italic">Data karyawan setelah perpindahan</p>
                            </div>

                        </div>

                        {{-- Keterangan --}}
                        <div class="mb-6 mt-6">
                            <label for="keterangan"
                                class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="4"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('keterangan') }}</textarea>
                        </div>

                        {{-- Lampiran --}}
                        <div class="mb-6">
                            <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
                            <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('manpower.index') }}"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">Batal</a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">Simpan
                                Data</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
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
                    this.query = item.nama;
                    this.selectedId = item.id;
                    this.results = [];
                }
            }
        }
    </script>
</x-app-layout>