<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Approval Matrix Man Power') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8" x-data="ommApproval()">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Daftar Approval Man Power </h3>

            {{-- Asumsi data untuk tabel ini adalah $manpowerStations --}}
            @if ($manpowerStations->isEmpty())
                <p class="text-sm text-gray-500">Tidak ada data One Man Can Many Stations pending untuk di konfirmasi.</p>
            @else
                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Nama Operator</th>
                            <th class="px-4 py-2 text-left">Station Yang Diajukan</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($manpowerStations as $item)
                            <tr class="border-t">
                                {{-- Asumsi: $item->man_power memiliki field nama --}}
<td class="px-4 py-2">{{ $item->manPower?->nama ?? '-' }}</td>                                {{-- Asumsi: $item->station memiliki field station_name --}}
                                <td class="px-4 py-2">{{ $item->station->station_name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-800">
                                        {{ $item->status ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button 
                                        @click="openModal({{ $item->id }})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs shadow-sm">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ===== MODAL DETAIL OMM ===== --}}
        <div x-show="showModal" 
             class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4"
             @click.away="showModal = false"
             x-cloak>

            <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Detail Approval OMM</h3>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                </div>

                {{-- LOADING --}}
                <div x-show="loading" class="text-center p-8">
                    <p class="text-gray-500">Loading data...</p>
                </div>

                {{-- DETAIL --}}
                <div x-show="!loading && Object.keys(detail).length > 0" class="text-sm">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b">
                        <div class="space-y-4">
    <h4 class="text-md font-semibold mb-2 border-b pb-1">Informasi Operator</h4>

    <div>
        <dt class="text-gray-500">Nama Operator:</dt>
        <dd class="text-gray-900 font-semibold" x-text="detail.manpower?.nama || '-'"></dd>
    </div>

    <div>
        <dt class="text-gray-500">Grup:</dt>
        <dd class="text-gray-900" x-text="detail.manpower?.grup || '-'"></dd>
    </div>
    
    <div> 
        <dt class="text-gray-500">Status :</dt>
        <dd class="text-gray-900">
            <span class="px-2 py-1 text-xs font-semibold rounded-full" 
                :class="{'bg-yellow-200 text-yellow-800': detail.status === 'Pending', 
                         'bg-green-200 text-green-800': detail.status === 'Approved'}">
                <span x-text="detail.status || '-'"></span>
            </span>
        </dd>
    </div>
    </div>

                        <div class="space-y-4">
                            <h4 class="text-md font-semibold mb-2 border-b pb-1">Informasi Station</h4>

                            <div>
                                <dt class="text-gray-500">Station Diajukan:</dt>
                                <dd class="text-gray-900 font-semibold" x-text="detail.station?.station_name || '-'"></dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Tanggal Pengajuan:</dt>
                                <dd x-text="formatDate(detail.created_at)"></dd>
                            </div>
                        </div>
                    </div>

                    
                    
                </div>

                {{-- ERROR --}}
                <div x-show="!loading && Object.keys(detail).length === 0" class="text-center p-8">
                    <p class="text-gray-500">Data detail OMM tidak ditemukan.</p>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="mt-6 border-t pt-4 flex justify-end space-x-3">
                    {{-- Form Revisi --}}
                    <form :action="`/approval/omm/${id}/revisi`" method="POST" id="revision-form">
    @csrf
    <textarea name="revision_notes" class="hidden" x-ref="revisionNotes"></textarea>
</form>

                    <button
    @click="$refs.revisionNotes.value = prompt('Catatan revisi (wajib)?'); if ($refs.revisionNotes.value) document.getElementById('revision-form').submit()"
    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
    Revisi
</button>

                    {{-- Form Approve --}}
                   <form :action="`/approval/omm/${id}/approve`" method="POST">
    @csrf
    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
        Approve
    </button>
</form>

                    <button @click="showModal = false" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Script Alpine.js --}}
    <script>
        function ommApproval() {
            return {
                showModal: false,
                loading: false,
                id: '',
                detail: {},

                openModal(id) {
                    this.id = id;
                    this.showModal = true;
                    this.loading = true;
                    this.detail = {};

                    // Sesuaikan endpoint API ini dengan rute Anda
                    fetch(`/api/omm-detail/${id}`) 
                        .then(res => res.json())
                        .then(data => {
                            this.detail = data;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.detail = {};
                            this.loading = false;
                        });
                },

                formatDate(value) {
                    if (!value) return '-';
                    // Mengambil tanggal dari created_at, dan hanya format tanggalnya
                    const d = new Date(value);
                    return `${d.getDate().toString().padStart(2, '0')}-${(d.getMonth()+1).toString().padStart(2,'0')}-${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
                },

                formatTime(value) {
                    if (!value) return '-';
                    return value.substring(0,5);
                }
            }
        }
    </script>

</x-app-layout>