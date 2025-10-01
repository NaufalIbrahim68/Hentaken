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

                    <form action="{{ route('manpower.henkaten.store', $man_power->id) }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Kolom Kiri --}}
                            <div>
                                <div class="mb-4">
                                    <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                                    <input type="text" id="nama" name="nama" value="{{ $man_power->nama }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200 leading-tight focus:outline-none focus:shadow-outline" readonly>
                                </div>

                                <div class="mb-4">
                                    <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                                    <select id="shift" name="shift" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                        <option value="1" {{ old('shift', $man_power->shift) == '1' ? 'selected' : '' }}>Shift 1</option>
                                        <option value="2" {{ old('shift', $man_power->shift) == '2' ? 'selected' : '' }}>Shift 2</option>
                                    </select>
                                    @error('shift') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>
                                
                                <div class="mb-4">
                                    <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line Area</label>
                                    <input type="text" id="line_area" name="line_area" value="{{ old('line_area', $man_power->line_area) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                    @error('line_area') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            {{-- Kolom Kanan --}}
                            <div>
                                <div class="mb-4">
                                    <label for="man_power_id_after" class="block text-gray-700 text-sm font-bold mb-2">Man Power ID (Pengganti)</label>
                                    <input type="text" id="man_power_id_after" name="man_power_id_after" value="{{ old('man_power_id_after') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                    @error('man_power_id_after') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="station_id_after" class="block text-gray-700 text-sm font-bold mb-2">Station Code (Pengganti)</label>
                                    <select id="station_id_after" name="station_id_after" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Station --</option>
                                        @foreach ($stations as $station)
                                            @if ($station->station_code)
                                                <option value="{{ $station->id }}" {{ old('station_id_after') == $station->id ? 'selected' : '' }}>
                                                    {{ $station->station_code }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('station_id_after') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                                    <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                    @error('effective_date') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                                    @error('end_date') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Keterangan (Full Width) --}}
                        <div class="mt-2 mb-6">
                            <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
                            @error('keterangan') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>
                        
                        {{-- Tombol Aksi --}}
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('manpower.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Batal
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Simpan Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>