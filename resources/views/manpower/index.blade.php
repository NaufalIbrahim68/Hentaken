<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Master Man Power</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto mt-10 p-4">
        <div class="bg-white p-8 rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Master Data Man Power</h1>
            </div>

            {{-- Menampilkan pesan sukses atau error setelah redirect --}}
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                    <p class="font-bold">Sukses</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                            <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Station</th>
                            <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Shift</th>
                            <th class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        {{-- Menggunakan variabel $man_powers dari controller --}}
                        @forelse ($man_powers as $man_power)
                            <tr class="hover:bg-gray-50 transition duration-200">
                                <td class="py-3 px-4">{{ $man_power->id }}</td>
                                <td class="py-3 px-4 font-medium">{{ $man_power->nama }}</td>
                                <td class="py-3 px-4">{{ $man_power->station_id }}</td>
                                <td class="py-3 px-4">{{ $man_power->shift }}</td>
                                <td class="py-3 px-4 text-center">
                                    {{-- Tombol aksi diubah menjadi form untuk membuat henkaten --}}
                                    <form action="{{ route('manpower.create-henkaten', $man_power->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition duration-300">
                                            Buat Henkaten
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data master man power.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

