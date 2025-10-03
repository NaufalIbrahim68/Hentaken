<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Material') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <form action="{{ route('materials.update', $material->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="mb-4">
                                <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station</label>
                                <select id="station_id" name="station_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                    <option value="">-- Pilih Station --</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->id }}" {{ old('station_id', $material->station_id) == $station->id ? 'selected' : '' }}>
                                            {{ $station->station_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="material_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Material</label>
                                <input type="text" id="material_name" name="material_name" value="{{ old('material_name', $material->material_name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </div>

                            <div class="mb-4 md:col-span-2">
                                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                                <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('keterangan', $material->keterangan) }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label for="lampiran" class="block text-gray-700 text-sm font-bold mb-2">Lampiran Baru (Opsional)</label>
                                <input type="file" id="lampiran" name="lampiran" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                @if($material->lampiran)
                                    <p class="text-sm text-gray-600 mt-2">Lampiran saat ini: <a href="{{ Storage::url($material->lampiran) }}" target="_blank" class="text-blue-500 hover:underline">Lihat file</a></p>
                                @endif
                            </div>

                           <div class="mb-4">
    <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
    <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        <option value="normal" {{ old('status', $material->status) == 'normal' ? 'selected' : '' }}>
            Normal
        </option>
        <option value="henkaten" {{ old('status', $material->status) == 'henkaten' ? 'selected' : '' }}>
            Henkaten
        </option>
    </select>
    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
                        </div>

                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <a href="{{ route('materials.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md">
                                Batal
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>