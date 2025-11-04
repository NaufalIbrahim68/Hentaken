<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfirmasi Master Data') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        @foreach (['manpower', 'method', 'machine', 'material'] as $type)
            @php
                $items = ${$type.'s'};
            @endphp

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 capitalize">{{ $type }}</h3>

                @if ($items->isEmpty())
                    <p class="text-sm text-gray-500">Tidak ada data pending untuk dikonfirmasi.</p>
                @else
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Deskripsi</th>
                                <th class="px-4 py-2 text-left">Line Area</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $item->id }}</td>
                                    <td class="px-4 py-2">{{ $item->deskripsi ?? $item->nama ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $item->line_area ?? '-' }}</td>
                                    <td class="px-4 py-2 text-yellow-600 font-semibold">{{ $item->status }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <button 
                                            @click="openModal('{{ $type }}', {{ $item->id }})"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach

    </div>

    {{-- Modal Detail --}}
    <div x-data="{ showModal: false, type: '', id: '', detail: {} }">
        <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white rounded-lg p-6 w-1/2">
                <h3 class="text-lg font-semibold mb-4">Detail Data</h3>
                
                <template x-if="Object.keys(detail).length">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <template x-for="(val, key) in detail" :key="key">
                            <div>
                                <span class="font-semibold" x-text="key.replaceAll('_', ' ') + ':'"></span>
                                <span x-text="val"></span>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="mt-6 flex justify-end space-x-3">
                    <form :action="`/konfirmasi/master/${type}/${id}/revisi`" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Revisi</button>
                    </form>
                    <form :action="`/konfirmasi/master/${type}/${id}/approve`" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded">Approve</button>
                    </form>
                    <button @click="showModal = false" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded">Tutup</button>
                </div>
            </div>
        </div>

        <script>
            function openModal(type, id) {
                fetch(`/api/master-detail/${type}/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        document.querySelector('[x-data]').__x.$data.showModal = true;
                        document.querySelector('[x-data]').__x.$data.type = type;
                        document.querySelector('[x-data]').__x.$data.id = id;
                        document.querySelector('[x-data]').__x.$data.detail = data;
                    });
            }
        </script>
    </div>
</x-app-layout>
