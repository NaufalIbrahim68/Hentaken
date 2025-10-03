<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master Data Mesin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    {{-- Tombol Tambah dan Search --}}
                    <div class="flex items-center justify-between mb-6">
                        <a href="{{ route('machines.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                            Tambah Data
                        </a>
                        <form action="{{ route('machines.index') }}" method="GET">
                            
                        </form>
                    </div>

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Station</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Deskripsi</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Keterangan</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Foto</th>
                                    <th class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($machines as $machine)
                                    <tr class="hover:bg-gray-50 transition duration-200">
                                        <td class="py-3 px-4">{{ $machine->station->station_name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 font-medium">{{ $machine->deskripsi }}</td>
                                        <td class="py-3 px-4">{{ $machine->keterangan ?: '-' }}</td>
                                        <td class="py-3 px-4">
                                            @if($machine->foto_path)
                                                <a href="{{ Storage::url($machine->foto_path) }}" target="_blank" class="text-blue-500 hover:underline">Lihat Foto</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="{{ route('machines.edit', $machine->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Edit
                                                </a>
                                                <form action="{{ route('machines.destroy', $machine->id) }}" method="POST" onsubmit="return confirm('Yakin ingin hapus data ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data master mesin.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $machines->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>