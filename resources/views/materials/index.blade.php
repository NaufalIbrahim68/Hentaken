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

                    {{-- Header Actions (Tambah Data + Filter Area) --}}
<div class="flex items-center justify-between mb-6">

    {{-- Tombol Tambah Data (Kiri) --}}
    <a href="{{ route('materials.create') }}"
        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
        Tambah Data
    </a>

    {{-- Filter Line Area (Kanan) --}}
    <form method="GET" action="{{ route('materials.index') }}" class="flex items-center space-x-2">
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

                    {{-- Tabel Data --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">
                                        Station</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">
                                        Nama Material</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">
                                        Line Area</th>
                                    <th
                                        class="py-3 px-4 border-b text-left text-sm font-semibold text-gray-600 uppercase">
                                        Status</th>
                                    <th
                                        class="py-3 px-4 border-b text-center text-sm font-semibold text-gray-600 uppercase">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($materials as $material)
                                    <tr class="hover:bg-gray-50 transition duration-200">
                                        <td class="py-3 px-4">{{ $material->station->station_name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 font-medium">{{ $material->material_name }}</td>
                                       <td class="py-3 px-4 font-medium">{{ $material->station->line_area }}</td>
                                        <td class="py-3 px-4">
                                            @if($material->status == 'normal')
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs font-medium me-2 px-0.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">
                                                    Normal
                                                </span>
                                            @elseif($material->status == 'henkaten')
                                                <span
                                                    class="bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300">
                                                    Henkaten
                                                </span>
                                            @else
                                                <span
                                                    class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">
                                                    N/A
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="{{ route('materials.edit', $material->id) }}"
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded-md text-sm transition">
                                                    Edit
                                                </a>
                                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST"
                                                    onsubmit="return confirm('Yakin ingin hapus data ini?');">
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
                                        <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data master
                                            material.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                   <div class="mt-6">
    {{ $materials->links('vendor.pagination.tailwind-index-materials') }}
</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>