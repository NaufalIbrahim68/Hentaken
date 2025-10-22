<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- Diubah ke Material --}}
            {{ __('Activity Log - Material') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- FORM FILTER - Rute diubah ke material --}}
                    <div class="flex justify-end mb-4">
                        <form action="{{ route('activity.log.material') }}" method="GET"
                            class="flex items-end space-x-2">

                            <div>
                                <label for="created_date" class="block text-xs font-medium text-gray-700">
                                    Filter Tanggal
                                </label>
                                <input type="date" name="created_date" id="created_date"
                                    value="{{ $created_date ?? '' }}" class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <button type="submit" class="py-2 px-6 border border-transparent shadow-sm text-sm font-medium 
                                    rounded-md text-white bg-blue-600 hover:bg-blue-700 
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Filter
                            </button>

                            {{-- Link Reset - Rute diubah ke material --}}
                            <a href="{{ route('activity.log.material') }}" class="py-4 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-md 
                                    text-gray-700 bg-white hover:bg-gray-100 
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Reset
                            </a>
                        </form>
                    </div>
                </div>


                {{-- NOTIFIKASI JIKA DATA TIDAK DITEMUKAN - Teks diubah --}}
                @if ($logs->isEmpty() && $created_date)
                    <div class="mb-4 p-4 rounded-md bg-yellow-100 border border-yellow-400 text-yellow-700">
                        Tidak ada data Henkaten Material untuk tanggal
                        <strong>{{ \Carbon\Carbon::parse($created_date)->format('d M Y') }}</strong>.
                    </div>
                @endif

                {{-- TABEL DATA --}}
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3 px-6">Tanggal Dibuat</th>
                                {{-- Kolom diubah ke Material --}}
                                <th scope="col" class="py-3 px-6">Nama Material Sebelum</th>
                                <th scope="col" class="py-3 px-6">Nama Material Sesudah</th>
                                <th scope="col" class="py-3 px-6">Station</th>
                                <th scope="col" class="py-3 px-6">Line Area</th>
                                <th scope="col" class="py-3 px-6">Tgl Efektif</th>
                                <th scope="col" class="py-3 px-6">Tgl Selesai</th>
                                <th scope="col" class="py-3 px-6">Lampiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="py-4 px-6">
                                        {{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}
                                    </td>
                                    {{-- Data diubah ke material_name / material_after --}}
                                    <td class="py-4 px-6">{{ $log->material_name ?? '-' }}</td>
                                    <td class="py-4 px-6">{{ $log->material_after ?? '-' }}</td>
                                    <td class="py-4 px-6">{{ $log->station->station_name ?? 'N/A' }}</td>
                                    <td class="py-4 px-6">{{ $log->line_area ?? '-' }}</td>
                                    <td class="py-4 px-6">
                                        {{ $log->effective_date ? \Carbon\Carbon::parse($log->effective_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-4 px-6">
                                        {{ $log->end_date ? \Carbon\Carbon::parse($log->end_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-4 px-6">
                                        @if ($log->lampiran)
                                            <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                                class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                                                Lihat Lampiran
                                            </a>
                                        @else
                                            <span class="text-gray-400">Tidak ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b">
                                    <td colspan="8" class="py-4 px-6 text-center text-gray-500">
                                        {{-- Teks diubah ke Material --}}
                                        Tidak ada data Henkaten Material
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{-- Pagination tetap sama, akan menggunakan style yang sama --}}
                    {{ $logs->appends(request()->query())->links('vendor.pagination.tailwind-activity-manpower') }}
                </div>

            </div>
        </div>
    </div>
    </div>
</x-app-layout>
