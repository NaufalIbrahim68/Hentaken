<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity Log - Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3 px-6">
                                        Nama Sebelum
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Nama Sesudah
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Station
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Line Area
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Tgl Efektif
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Tgl Selesai
                                    </th>
                                    <th scope="col" class="py-3 px-6">
                                        Tanggal Dibuat
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-4 px-6">
                                            {{ $log->nama ?? '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ $log->nama_after ?? '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{-- Ambil nama dari relasi 'station' --}}
                                            {{ $log->station->station_name ?? 'N/A' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ $log->line_area }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{-- Format tanggal agar rapi --}}
                                            {{ $log->effective_date ? \Carbon\Carbon::parse($log->effective_date)->format('d M Y') : '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ $log->end_date ? \Carbon\Carbon::parse($log->end_date)->format('d M Y') : '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ $log->created_at ? $log->updated_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b">
                                        <td colspan="7" class="py-4 px-6 text-center text-gray-500">
                                            Tidak ada data activity log.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Link untuk Pagination --}}
                    <div class="mt-4">
                        {{ $logs->links('vendor.pagination.tailwind-activity-manpower') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>