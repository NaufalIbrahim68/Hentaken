<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Method') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Tombol Tambah --}}
                    <div class="flex items-center justify-between mb-6">
                        <a href="{{ route('methods.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                            Tambah Data
                        </a>
                    </div>

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Station</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Keterangan</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">Foto</th>
                                    <th class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($methods as $method)
                                <tr class="hover:bg-gray-50 transition duration-200">
                                    <td class="py-3 px-4">{{ $method->station->station_name ?? 'N/A' }}</td>
                                    <td class="py-3 px-4 font-medium">{{ Str::limit($method->keterangan, 50) }}</td>
                                    <td class="py-3 px-4">
                                        @if($method->foto_path)
                                        <a href="{{ Storage::url($method->foto_path) }}" target="_blank" class="text-blue-500 hover:underline">Lihat Foto</a>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('methods.edit', $method->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                Edit
                                            </a>
                                            <form action="{{ route('methods.destroy', $method->id) }}" method="POST" onsubmit="return confirm('Yakin ingin hapus data ini?');">
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
                                    <td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data master method.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $methods->links('vendor.pagination.tailwind-index-methods') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
