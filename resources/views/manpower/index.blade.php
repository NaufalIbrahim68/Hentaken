<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Menampilkan pesan sukses atau error --}}
                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md"
                            role="alert">
                            <p class="font-bold">Sukses</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">Error</p>
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                      
                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <div class="flex items-center justify-between mb-6">
                        <a href="{{ route('manpower.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                            Tambah Data
                        </a>

                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        ID</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        Nama</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        Station</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        Shift</th>
                                    <th
                                        class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($man_powers as $man_power)
                                    <tr class="hover:bg-gray-50 transition duration-200">
                                      <td class="py-3 px-4">{{ $man_power->station_id }}</td>
                                        <td class="py-3 px-4 font-medium">{{ $man_power->nama }}</td>
                                        <td class="py-3 px-4">{{ $man_power->station?->station_code }}</td>
                                        <td class="py-3 px-4">{{ $man_power->shift }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                               
                                                <a href="{{ route('manpower.edit', $man_power->id) }}"
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Edit
                                                </a>
                                                <form action="{{ route('manpower.destroy', $man_power->id) }}"
                                                    method="POST" onsubmit="return confirm('Yakin ingin hapus?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data master man
                                            power.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>


<div class="mt-6">
    {{ $man_powers->links('vendor.pagination.tailwind-index-mp') }}
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>