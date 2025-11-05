<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfirmasi Master Data') }}
        </h2>
    </x-slot>

    <div x-data="masterConfirmPage()" class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

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
                                <th class="px-4 py-2 text-left">Station</th>
                                {{-- PERUBAHAN 1: Header lebih generik agar sesuai dengan semua tipe --}}
                                <th class="px-4 py-2 text-left">Nama</th>
                                <th class="px-4 py-2 text-left">Line Area</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr class="border-t">
                                    {{-- PERUBAHAN 2: Menghindari duplikasi @if/@else pada <td> yang sama --}}
                                    <td class="px-4 py-2">{{ $item->station->station_name ?? '-' }}</td>
                                    
                                    {{-- Kolom kedua kini dinamis berdasarkan $type --}}
                                    @if ($type === 'manpower')
                                        <td class="px-4 py-2">{{ $item->nama ?? '-' }}</td>
                                    @elseif ($type === 'material')
                                        {{-- Menggunakan 'material_name' sesuai modal, atau 'deskripsi' sbg fallback --}}
                                        <td class="px-4 py-2">{{ $item->material_name ?? $item->deskripsi ?? '-' }}</td>
                                    @else 
                                        {{-- Untuk 'method' dan 'machine' --}}
                                        <td class="px-4 py-2">{{ $item->deskripsi ?? '-' }}</td>
                                    @endif
                                    
                                    <td class="px-4 py-2">{{ $item->station->line_area ?? '-' }}</td>
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

        {{-- 💡 MODAL --}}
        
        <div>
            <div x-show="showModal" @click.away="showModal = false" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4" x-cloak>
                
                <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
                    <h3 class="text-lg font-semibold mb-4 capitalize" x-text="'Detail ' + type"></h3>
                    
                    {{-- TAMPILAN LOADING --}}
                    <div x-show="Object.keys(detail).length === 0" class="text-gray-500">
                        Loading data...
                    </div>

                    {{-- TAMPILAN MANPOWER --}}
                    <template x-if="type === 'manpower' && Object.keys(detail).length">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <span class="font-semibold text-gray-600">Nama:</span>
                                <span class="text-gray-900" x-text="detail.nama || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Grup:</span>
                                <span class="text-gray-900" x-text="detail.grup || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Station:</span>
                                <span class="text-gray-900" x-text="detail.station ? detail.station.station_name : '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Line Area:</span>
                                <span class="text-gray-900" x-text="detail.line_area ? detail.line_area : '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Tanggal Mulai:</span>
                                <span class="text-gray-900" x-text="detail.tanggal_mulai || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Waktu Mulai:</span>
                                <span class="text-gray-900" x-text="detail.waktu_mulai ? detail.waktu_mulai.substring(0, 5) : '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Status:</span>
                                <span class="text-yellow-600 font-semibold" x-text="detail.status || '-'"></span>
                            </div>
                        </div>
                    </template>

                    {{-- TAMPILAN MATERIAL --}}
                    <template x-if="type === 'material' && Object.keys(detail).length">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <span class="font-semibold text-gray-600">Material Name:</span>
                                <span class="text-gray-900" x-text="detail.material_name || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Keterangan:</span>
                                <span class="text-gray-900" x-text="detail.keterangan || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Station:</span>
                                <span class="text-gray-900" x-text="detail.station ? detail.station.station_name : '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Status:</span>
                                <span class="text-yellow-600 font-semibold" x-text="detail.status || '-'"></span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="font-semibold text-gray-600">Lampiran:</span>
                                
                                <template x-if="detail.lampiran_url">
                                    <a :href="detail.lampiran_url" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 hover:underline ml-2">
                                        Lihat/Unduh Lampiran
                                    </a>
                                </template>
                                <template x-if="!detail.lampiran_url">
                                    <span class="text-gray-900 ml-2">- (Tidak ada)</span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- PERUBAHAN 3: Template kustom untuk 'method' dan 'machine' --}}
                    <template x-if="(type === 'method' || type === 'machine') && Object.keys(detail).length">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <span class="font-semibold text-gray-600">Deskripsi:</span>
                                <span class="text-gray-900" x-text="detail.deskripsi || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Station:</span>
                                <span class="text-gray-900" x-text="detail.station ? detail.station.station_name : '-'"></span>
                            </div>
                             <div>
                                <span class="font-semibold text-gray-600">Line Area:</span>
                                <span class="text-gray-900" x-text="detail.line_area || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Keterangan:</span>
                                <span class="text-gray-900" x-text="detail.keterangan || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Status:</span>
                                <span class="text-yellow-600 font-semibold" x-text="detail.status || '-'"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Tombol Aksi (Hanya tampil jika data sudah ter-load) --}}
                    <div x-show="Object.keys(detail).length > 0" class="mt-6 flex justify-end space-x-3">
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

                    {{-- Tombol Tutup (Hanya tampil jika loading gagal) --}}
                     <div x-show="detail.error" class="mt-6 flex justify-end">
                         <button @click="showModal = false" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded">Tutup</button>
                    </div>

                </div>
            </div>
        </div>

    </div> 
    
    @push('scripts')
    <script>
        function masterConfirmPage() {
            return {
                // Data
                showModal: false,
                type: '',
                id: '',
                detail: {}, // Menjadi {} untuk 'loading' state

                // Method
                openModal(type, id) {
                    this.detail = {}; // Kosongkan detail untuk 'loading'
                    this.showModal = true;
                    this.type = type;
                    this.id = id;

                    fetch(`/api/master-detail/${type}/${id}`)
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('Data tidak ditemukan');
                            }
                            return res.json();
                        })
                        .then(data => {
                            this.detail = data; // Isi detail dengan data dari API
                        })
                        .catch(err => {
                            console.error(err);
                            this.detail = { error: 'Gagal memuat data.' };
                        });
                }
            }
        }
    </script>
    @endpush

</x-app-layout>