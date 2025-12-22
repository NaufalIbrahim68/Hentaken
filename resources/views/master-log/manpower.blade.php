<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Master Data - Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <div class="flex justify-end mb-4">
                    <form action="{{ route('master.log.manpower') }}" method="GET" class="flex items-end space-x-2">
                        <div>
                            <label for="created_date" class="block text-xs font-medium text-gray-700">Filter Tanggal</label>
                            <input type="date" name="created_date" id="created_date" value="{{ $created_date }}" 
                                   class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="action" class="block text-xs font-medium text-gray-700">Filter Aksi</label>
                            <select name="action" id="action" class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Semua Aksi</option>
                                <option value="created" {{ $action == 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ $action == 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="deleted" {{ $action == 'deleted' ? 'selected' : '' }}>Deleted</option>
                            </select>
                        </div>
                        <button type="submit" class="py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition">
                            Filter
                        </button>
                        <a href="{{ route('master.log.manpower') }}" class="py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100 focus:outline-none transition">
                            Reset
                        </a>
                    </form>
                </div>

                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3 px-3">Waktu</th>
                                <th scope="col" class="py-3 px-3">User</th>
                                <th scope="col" class="py-3 px-3">Aksi</th>
                                <th scope="col" class="py-3 px-3">Detail</th>
                                <th scope="col" class="py-3 px-3 text-center">Opsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="bg-white border-b hover:bg-gray-50 text-xs">
                                    <td class="py-2 px-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2 px-3">{{ $log->user->name ?? 'System' }}</td>
                                    <td class="py-2 px-3">
                                        @php
                                            $badgeColor = match($log->action) {
                                                'created' => 'bg-green-100 text-green-700',
                                                'updated' => 'bg-blue-100 text-blue-700',
                                                'deleted' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="text-xs font-semibold inline-block py-1 px-2 rounded-full whitespace-nowrap {{ $badgeColor }}">
                                            {{ strtoupper($log->action) }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3">
                                        <div class="max-w-md overflow-hidden text-xs">
                                            @if($log->action === 'updated')
                                                @php
                                                    $changes = $log->details['changes'] ?? [];
                                                    $stationAdded = $changes['station_added']['new'] ?? ($log->details['single_station'] ?? null);
                                                    $stationRemoved = $changes['station_removed']['old'] ?? null;
                                                    
                                                    // Detect if it's a status change for a specific station
                                                    $statusStationKey = collect(array_keys($changes))->first(fn($k) => strpos($k, 'status_') === 0);
                                                    $statusStationName = $statusStationKey ? str_replace('status_', '', $statusStationKey) : null;
                                                    
                                                    $isStationAction = $stationAdded || $stationRemoved || $statusStationKey;
                                                @endphp

                                                <div class="mb-2 p-2 bg-gray-50 rounded border border-gray-100">
                                                    <p><strong>Nama:</strong> {{ $log->details['nama'] ?? '-' }}</p>
                                                    <p><strong>Grup:</strong> {{ $log->details['grup'] ?? '-' }}</p>
                                                    <p><strong>Line Area:</strong> {{ $log->details['line_area'] ?? '-' }}</p>
                                                    
                                                    @if($isStationAction)
                                                        @if($stationAdded)
                                                            <p><strong>Penambahan Station:</strong> <span class="text-blue-600 font-semibold">{{ $stationAdded }}</span></p>
                                                            <p><strong>Status:</strong> <span class="px-1.5 py-0.5 rounded text-[10px] bg-yellow-100 text-yellow-800">PENDING</span></p>
                                                        @elseif($stationRemoved)
                                                            <p><strong>Penghapusan Station:</strong> <span class="text-red-600 font-semibold">{{ $stationRemoved }}</span></p>
                                                        @elseif($statusStationName)
                                                            <p><strong>Status Station:</strong> <span class="text-blue-600 font-semibold">{{ $statusStationName }}</span></p>
                                                            <p><strong>Status:</strong> <span class="px-1.5 py-0.5 rounded text-[10px] {{ ($changes[$statusStationKey]['new'] ?? '') === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ strtoupper($changes[$statusStationKey]['new'] ?? '-') }}</span></p>
                                                        @endif
                                                    @else
                                                        <p><strong>Status:</strong> <span class="px-1.5 py-0.5 rounded text-[10px] {{ ($log->details['status'] ?? '') === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ strtoupper($log->details['status'] ?? '-') }}</span></p>
                                                        @if(!empty($log->details['additional_stations']))
                                                            <p><strong>Station Tambahan:</strong> {{ is_array($log->details['additional_stations']) ? implode(', ', $log->details['additional_stations']) : $log->details['additional_stations'] }}</p>
                                                        @endif
                                                    @endif
                                                </div>

                                                @if(!$isStationAction)
                                                    <hr class="my-1 border-gray-200">
                                                    <p class="font-bold text-[10px] text-gray-400 uppercase">Detail Perubahan:</p>
                                                    <ul class="list-disc list-inside">
                                                        @foreach($changes as $field => $change)
                                                            <li class="text-[11px]">
                                                                <strong>{{ ucwords(str_replace('_', ' ', $field)) }}</strong>: 
                                                                <span class="text-red-500">{{ is_array($change['old']) ? json_encode($change['old']) : (empty($change['old']) ? '-' : $change['old']) }}</span> 
                                                                &rarr; 
                                                                <span class="text-green-500">{{ is_array($change['new']) ? json_encode($change['new']) : (empty($change['new']) ? '-' : $change['new']) }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            @else
                                                <div class="mb-2 p-2 bg-gray-50 rounded border border-gray-100">
                                                    @if($log->action === 'created')
                                                        <p><strong>Nama:</strong> {{ $log->details['nama'] ?? '-' }}</p>
                                                        <p><strong>Grup:</strong> {{ $log->details['grup'] ?? '-' }}</p>
                                                        <p><strong>Line Area:</strong> {{ $log->details['line_area'] ?? '-' }}</p>
                                                        <p><strong>Waktu Mulai:</strong> {{ isset($log->details['tanggal_mulai']) ? \Carbon\Carbon::parse($log->details['tanggal_mulai'])->translatedFormat('l, d F Y') : '-' }} | {{ $log->details['waktu_mulai'] ?? '-' }}</p>
                                                        <p><strong>Status:</strong> <span class="px-1.5 py-0.5 rounded text-[10px] {{ ($log->details['status'] ?? '') === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ strtoupper($log->details['status'] ?? (isset($log->details['new_data']['status']) ? $log->details['new_data']['status'] : 'PENDING')) }}</span></p>
                                                    @elseif($log->action === 'deleted')
                                                        <p><strong>Nama:</strong> {{ $log->details['nama'] ?? '-' }}</p>
                                                        <p><strong>Grup:</strong> {{ $log->details['grup'] ?? '-' }}</p>
                                                        <p><strong>Line Area:</strong> {{ $log->details['line_area'] ?? '-' }}</p>
                                                        <p><strong>Waktu Hapus:</strong> {{ $log->created_at->translatedFormat('l, d F Y | H:i') }}</p>
                                                        <p><strong>Status:</strong> <span class="px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-800">DELETED</span></p>
                                                    @else
                                                        <p>{{ $log->details['message'] ?? '-' }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2 px-3">
                                        <div class="flex justify-center">
                                            <form action="{{ route('master.log.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Hapus log ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-block bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-2 py-1 rounded-md transition shadow-sm">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b">
                                    <td colspan="5" class="py-4 px-6 text-center text-gray-500">
                                        Belum ada log aktivitas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
