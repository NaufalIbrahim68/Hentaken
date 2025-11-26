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
                        <div class="flex items-center justify-between mb-6">
                            
                            {{-- Tombol Tambah Data (Kiri) --}}
                            <a href="{{ route('manpower.create') }}" 
                                class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                                Tambah Data
                            </a>

                            {{-- BARU: Form Filter Dropdown (Kanan) --}}
                            <form method="GET" action="{{ route('manpower.index') }}" class="flex items-center space-x-2">
                                <label for="line_area" class="text-sm font-medium text-gray-700">Filter Area:</label>
                                <select name="line_area" id="line_area" 
                                        class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        onchange="this.form.submit()">
                                    <option value="">Semua Line Area</option>
                                    @foreach ($lineAreas as $area)
                                        <option value="{{ $area }}" {{ $selectedLineArea == $area ? 'selected' : '' }}>
                                            {{ $area }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">No</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Line Area</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Station</th>
                                    <th class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Grup</th>
                                    <th class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @forelse ($man_powers as $man_power)
                                    <tr class="hover:bg-gray-50 transition duration-200">
                                        <td class="py-3 px-4">
                                            {{ $loop->iteration + ($man_powers->currentPage() - 1) * $man_powers->perPage() }}
                                        </td>
                                        <td class="py-3 px-4 font-medium">{{ $man_power->nama }}</td>
                                        <td class="py-3 px-4 font-medium">{{ $man_power->line_area }}</td>
                                        <td class="py-3 px-4">{{ $man_power->station?->station_name }}</td>
                                        <td class="py-3 px-4">{{ $man_power->grup }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                
                                                <a href="{{ route('manpower.edit', $man_power->id) }}"
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Edit
                                                </a>

                                                <a href="{{ route('henkaten.manpower.createChange', $man_power->id) }}"
                                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Change
                                                </a>
                                               <form action="{{ route('manpower.master.destroy', $man_power->id) }}" 
      method="POST" 
      onsubmit="return confirm('Yakin ingin hapus?');">
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
                                        <td colspan="6" class="text-center py-4 text-gray-500">
                                            Tidak ada data master man power.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-6">
                            {{-- BARU: Menambahkan appends() agar filter tetap ada saat ganti halaman --}}
                            {{ $man_powers->appends(request()->query())->links('vendor.pagination.tailwind-index-mp') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>