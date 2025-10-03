<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Data Material Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <form action="{{ route('materials.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Station --}}
                            <div class="mb-4">
                                <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station</label>
                                <select id="station_id" name="station_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">-- Pilih Station --</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->id }}" {{ old('station_id') == $station->id ? 'selected' : '' }}>
                                            {{ $station->station_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Nama Material --}}
                            <div class="mb-4">
                                <label for="material_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Material</label>
                                <input type="text" id="material_name" name="material_name" value="{{ old('material_name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </div>

                            {{-- Keterangan --}}
                            <div class="mb-4 md:col-span-2">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('keterangan') }}</textarea>
                            </div>

                            {{-- Lampiran --}}
                            <div class="mb-4">
                                <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran (JPG/PNG)</label>
                                <input type="file" id="lampiran" name="lampiran" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </div>

                            {{-- Status --}}
                            <div class="mb-4">
                                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                    <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <a href="{{ route('materials.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md">
                                Batal
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>