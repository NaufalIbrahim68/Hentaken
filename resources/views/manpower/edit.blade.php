<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Henkaten</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto mt-10 max-w-2xl">
        <div class="bg-white p-8 rounded-lg shadow-xl">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Form Edit Henkaten Man Power</h1>

            <form action="{{ route('manpower.update', $henkaten->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Wajib untuk method update --}}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Kolom Kiri --}}
                    <div>
                        <div class="mb-4">
                            <label for="man_power_id" class="block text-gray-700 text-sm font-bold mb-2">Man Power ID (Awal)</label>
                            <input type="text" id="man_power_id" name="man_power_id" value="{{ old('man_power_id', $henkaten->man_power_id) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @error('man_power_id') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station ID (Awal)</label>
                            <input type="text" id="station_id" name="station_id" value="{{ old('station_id', $henkaten->station_id) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                             @error('station_id') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                            <input type="text" id="nama" name="nama" value="{{ old('nama', $henkaten->nama) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200 leading-tight focus:outline-none focus:shadow-outline" readonly>
                             @error('nama') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                            <select id="shift" name="shift" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="1" @if(old('shift', $henkaten->shift) == '1') selected @endif>Shift 1</option>
                                <option value="2" @if(old('shift', $henkaten->shift) == '2') selected @endif>Shift 2</option>
                                <option value="3" @if(old('shift', $henkaten->shift) == '3') selected @endif>Shift 3</option>
                            </select>
                             @error('shift') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>
                        
                         <div class="mb-4">
                            <label for="line_area" class="block text-gray-700 text-sm font-bold mb-2">Line Area</label>
                            <input type="text" id="line_area" name="line_area" value="{{ old('line_area', $henkaten->line_area) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                             @error('line_area') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Kolom Kanan --}}
                    <div>
                        <div class="mb-4">
                            <label for="man_power_id_after" class="block text-gray-700 text-sm font-bold mb-2">Man Power ID (Pengganti)</label>
                            <input type="text" id="man_power_id_after" name="man_power_id_after" value="{{ old('man_power_id_after', $henkaten->man_power_id_after) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label for="station_id_after" class="block text-gray-700 text-sm font-bold mb-2">Station ID (Pengganti)</label>
                            <input type="text" id="station_id_after" name="station_id_after" value="{{ old('station_id_after', $henkaten->station_id_after) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label for="effective_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Efektif</label>
                            <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', $henkaten->effective_date ? \Carbon\Carbon::parse($henkaten->effective_date)->format('Y-m-d') : '') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                             @error('effective_date') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $henkaten->end_date ? \Carbon\Carbon::parse($henkaten->end_date)->format('Y-m-d') : '') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>
                </div>

                {{-- Keterangan --}}
                <div class="mt-2 mb-6">
                    <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">{{ old('keterangan', $henkaten->keterangan) }}</textarea>
                    @error('keterangan') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                </div>
                
                <div class="flex items-center justify-end space-x-4">
                    <a href="{{ route('manpower.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Batal
                    </a>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
