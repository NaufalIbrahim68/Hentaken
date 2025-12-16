<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Master Data - Method') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- FORM FILTER --}}
                    <div class="flex justify-end mb-4">
                        <form action="{{ route('activity.log.master.method') }}" method="GET" class="flex items-end space-x-2">
                            
                            {{-- Filter Tanggal --}}
                            <div>
                                <label for="created_date" class="block text-xs font-medium text-gray-700">
                                    Filter Tanggal
                                </label>
                                <input type="date" name="created_date" id="created_date"
                                    value="{{ $created_date ?? '' }}" class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            {{-- Filter Action --}}
                            <div>
                                <label for="action" class="block text-xs font-medium text-gray-700">
                                    Filter Aksi
                                </label>
                                <select name="action" id="action" class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Semua Aksi</option>
                                    <option value="created" {{ ($action ?? '') == 'created' ? 'selected' : '' }}>Created</option>
                                    <option value="updated" {{ ($action ?? '') == 'updated' ? 'selected' : '' }}>Updated</option>
                                    <option value="deleted" {{ ($action ?? '') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                </select>
                            </div>

                            <button type="submit" class="py-2 px-6 border border-transparent shadow-sm text-sm font-medium 
                                    rounded-md text-white bg-blue-600 hover:bg-blue-700 
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Filter
                            </button>
                            <a href="{{ route('activity.log.master.method') }}" class="py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-md 
                                    text-gray-700 bg-white hover:bg-gray-100 
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Reset
                            </a>
                        </form>
                    </div>
                </div>

                {{-- NOTIFIKASI JIKA DATA TIDAK DITEMUKAN --}}
                @if ($logs->isEmpty() && ($created_date || $action))
                    <div class="mb-4 p-4 rounded-md bg-yellow-100 border border-yellow-400 text-yellow-700">
                        Tidak ada data log untuk filter yang dipilih.
                    </div>
                @endif

                {{-- TABEL DATA --}}
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3 px-3">Tanggal</th>
                                <th scope="col" class="py-3 px-3">User</th>
                                <th scope="col" class="py-3 px-3">Aksi</th>
                                <th scope="col" class="py-3 px-3">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="bg-white border-b hover:bg-gray-50 text-xs">
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        {{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td class="py-2 px-3">{{ $log->user->name ?? 'System' }}</td>
                                    <td class="py-2 px-3">
                                        @php
                                            $action = $log->action; 
                                            $badgeClass = '';

                                            if($action == 'created') {
                                                $badgeClass = 'bg-green-100 text-green-700';
                                                $text = 'Created';
                                            } elseif ($action == 'updated') {
                                                $badgeClass = 'bg-blue-100 text-blue-700';
                                                $text = 'Updated';
                                            } elseif ($action == 'deleted') {
                                                $badgeClass = 'bg-red-100 text-red-700';
                                                $text = 'Deleted';
                                            } else {
                                                $badgeClass = 'bg-gray-100 text-gray-700';
                                                $text = $action ?? '-';
                                            }
                                        @endphp
                                        <span class="text-xs font-semibold inline-block py-1 px-2 rounded-full whitespace-nowrap {{ $badgeClass }}">
                                            {{ $text }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 max-w-xs">
                                        @if($log->details)
                                            <div class="space-y-1">
                                                @foreach($log->details as $key => $value)
                                                    @if(!is_array($value))
                                                        <div class="text-xs"><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b">
                                    <td colspan="4" class="py-4 px-6 text-center text-gray-500">
                                        Tidak ada data log Method
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-6">
                    {{ $logs->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
