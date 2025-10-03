<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Material') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- Tombol Tambah dan Search --}}
                    <div class="flex items-center justify-between mb-6">
                        <a href="{{ route('materials.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                            Tambah Data
                        </a>
                        <form action="{{ route('materials.index') }}" method="GET">
                            <div class="flex items-center">
                                <input type="text" name="search" placeholder="Cari material..." class="border rounded-l-md py-2 px-3 focus:outline-none" value="{{ request('search') }}">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-r-md">
                                    Cari
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Station</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Nama Material</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Lampiran</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($materials as $material)
                                    <tr class="hover:bg-gray-50 transition duration-200">
                                        <td class="py-3 px-4">{{ $material->station_code }}</td>
                                        <td class="py-3 px-4 font-medium">{{ $material->material_name }}</td>
                                        <td class="py-3 px-4">
                                            @if($material->lampiran)
                                                <a href="{{ Storage::url($material->lampiran) }}" target="_blank" class="text-blue-500 hover:underline">Lihat</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($material->status)
                                                <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">Aktif</span>
                                            @else
                                                <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="{{ route('materials.edit', $material->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Edit
                                                </a>
                                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" onsubmit="return confirm('Yakin ingin hapus data ini?');">
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
                                        <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data master material.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $materials->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>