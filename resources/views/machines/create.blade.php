<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Data Mesin Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900">

                    <form action="{{ route('machines.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 gap-6">
                            
                            <div>
                                <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                                <select id="station_id" name="station_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">-- Pilih Station --</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->id }}" {{ old('station_id') == $station->id ? 'selected' : '' }}>
                                            {{ $station->station_code }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('station_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Mesin</label>
                                <input type="text" name="deskripsi" id="deskripsi" value="{{ old('deskripsi') }}" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                @error('deskripsi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('keterangan') }}</textarea>
                                @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="foto_path" class="block text-sm font-medium text-gray-700">Upload Foto (Opsional)</label>
                                <input type="file" name="foto_path" id="foto_path" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @error('foto_path') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                        </div>

                        <div class="flex items-center justify-end mt-8 pt-5 border-t border-gray-200">
                            <a href="{{ route('machines.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md mr-3">
                                Batal
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">
                                Simpan Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>