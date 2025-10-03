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

                <form action="{{ route('manpower.henkaten.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Kolom Kiri --}}
        <div>
            <div class="mb-4">
                <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                <select id="shift" name="shift" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <option value="1" {{ old('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                    <option value="2" {{ old('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line Area</label>
                <input type="text" id="line_area" name="line_area" value="{{ old('line_area') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
        </div>

        {{-- Kolom Kanan --}}
        <div>
            <div class="mb-4">
                <label for="station_id_after" class="block text-gray-700 text-sm font-bold mb-2">Station Code</label>
                <select id="station_id_after" name="station_id_after" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <option value="">-- Pilih Station --</option>
                    @foreach ($stations as $station)
                        <option value="{{ $station->id }}" {{ old('station_id_after') == $station->id ? 'selected' : '' }}>
                            {{ $station->station_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
        </div>
    </div>

     {{-- Nama Karyawan Before & After (Full Width) --}}
                        <div class="bg-gradient-to-r from-blue-50 to-green-50 p-6 rounded-lg border-2 border-blue-200 mt-6 mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Perpindahan Karyawan
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Before --}}
                                <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                    <div class="flex items-center mb-3">
                                        <div class="bg-blue-100 rounded-full p-2 mr-3">
                                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <label for="nama_before" class="text-gray-700 text-sm font-bold">Nama Karyawan Sebelumnya</label>
                                    </div>
                                    <input type="text" id="nama_before" name="nama_before" 
                value="{{ old('nama_before') }}"  class="w-full py-3 px-4 text-gray-700 bg-white border-2 border-green-300 rounded-lg font-semibold focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200 transition duration-200"
    >
                                    <p class="text-xs text-gray-500 mt-2 italic">Data karyawan sebelum perpindahan</p>
                                </div>

                             

                               {{-- After --}}
<div class="bg-white rounded-lg p-4 border-2 border-green-300 shadow-md">
    <div class="flex items-center mb-3">
        <div class="bg-green-100 rounded-full p-2 mr-3">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <label for="nama_after" class="text-gray-700 text-sm font-bold">Nama Karyawan Sesudah</label>
    </div>
    <input 
        type="text" 
        id="nama_after" 
        name="nama_after" 
        value="{{ old('nama_after') }}" 
        class="w-full py-3 px-4 text-gray-700 bg-white border-2 border-green-300 rounded-lg font-semibold focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200 transition duration-200"
    >
    @error('nama_after') 
        <p class="text-red-500 text-xs italic mt-2 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            {{ $message }}
        </p>
    @enderror
    <p class="text-xs text-green-600 mt-2 italic">Data karyawan setelah perpindahan</p>
</div>
                            </div>
                        </div>
    {{-- Keterangan --}}
    <div class="mb-6 mt-6">
        <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('keterangan') }}</textarea>
    </div>

    {{-- Lampiran --}}
    <div class="mb-6">
        <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran</label>
        <input type="file" id="lampiran" name="lampiran" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
    </div>

    <div class="flex items-center justify-end space-x-4 pt-4 border-t">
        <a href="{{ route('manpower.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md">Batal</a>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">Simpan Data</button>
    </div>
</form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>