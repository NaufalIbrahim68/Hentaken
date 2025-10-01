<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Master Man Power</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto mt-10 p-4" style="max-width: 600px;">
        <div class="bg-white p-8 rounded-lg shadow-xl">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                Edit Man Power: <span class="text-blue-600">{{ $man_power->nama }}</span>
            </h1>

            {{-- Menampilkan error validasi jika ada --}}
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md">
                    <p class="font-bold">Terjadi Kesalahan</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('manpower.master.update', $man_power->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Method PUT untuk proses update --}}

                <div class="mb-4">
                    <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Karyawan:</label>
                    <input type="text" id="nama" name="nama" 
                           value="{{ old('nama', $man_power->nama) }}" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required>
                </div>

                <div class="mb-4">
                    <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station:</label>
                    <input type="text" id="station_id" name="station_id"
                           value="{{ old('station_id', $man_power->station_id) }}"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required>
                </div>

                <div class="mb-6">
                    <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift:</label>
                    <select id="shift" name="shift" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                        
                        <option value="Shift A" {{ old('shift', $man_power->shift) == 'Shift A' ? 'selected' : '' }}>
                            Shift A
                        </option>
                        <option value="Shift B" {{ old('shift', $man_power->shift) == 'Shift B' ? 'selected' : '' }}>
                            Shift B
                        </option>
                       
                         {{-- Tambahkan shift lain jika ada --}}
                    </select>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Data
                    </button>
                    <a href="{{ route('manpower.index') }}" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>