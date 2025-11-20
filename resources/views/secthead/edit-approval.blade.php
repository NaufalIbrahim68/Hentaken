<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfirmasi Henkaten Manpower') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8" x-data="henkatenApproval()">

        {{-- ===== SECTION MANPOWER SAJA ===== --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Henkaten Manpower</h3>

            @if ($manpowers->isEmpty())
                <p class="text-sm text-gray-500">Tidak ada data henkaten pending untuk dikonfirmasi.</p>
            @else
                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Operator Awal</th>
                            <th class="px-4 py-2 text-left">Operator Pengganti</th>
                            <th class="px-4 py-2 text-left">Line Area</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($manpowers as $item)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $item->nama ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $item->nama_after ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $item->line_area ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-800">
                                        {{ $item->status ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button 
                                        @click="openModal('manpower', {{ $item->id }})"
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

        {{-- ===== MODAL DETAIL ===== --}}
        <div x-show="showModal" 
             class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4"
             @click.away="showModal = false"
             x-cloak>

            <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Detail Henkaten Manpower</h3>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                </div>

                {{-- LOADING --}}
                <div x-show="loading" class="text-center p-8">
                    <p class="text-gray-500">Loading data...</p>
                </div>

                {{-- DETAIL --}}
                <div x-show="!loading && Object.keys(detail).length > 0" class="text-sm">
                    
                    {{-- Informasi Umum --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b">
                        <div class="space-y-4">
                            <h4 class="text-md font-semibold mb-2 border-b pb-1">Informasi Umum</h4>

                            <div>
                                <dt class="text-gray-500">Station / Line:</dt>
                                <dd class="text-gray-900 font-semibold">
                                    <span x-text="detail.station?.station_name || '-'"></span> / 
                                    <span x-text="detail.line_area || '-'"></span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Shift:</dt>
                                <dd class="text-gray-900" x-text="detail.shift || '-'"></dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Lampiran:</dt>
                                <dd>
                                    <template x-if="detail.lampiran">
                                        <a :href="`/storage/${detail.lampiran}`" target="_blank" class="text-blue-600 underline">
                                            Lihat Lampiran
                                        </a>
                                    </template>
                                    <template x-if="!detail.lampiran">
                                        <span>-</span>
                                    </template>
                                </dd>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-md font-semibold mb-2 border-b pb-1">Waktu</h4>

                            <div>
                                <dt class="text-gray-500">Waktu Mulai:</dt>
                                <dd x-text="formatDate(detail.effective_date) + ' @ ' + formatTime(detail.time_start)"></dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Waktu Selesai:</dt>
                                <dd x-text="formatDate(detail.end_date) + ' @ ' + formatTime(detail.time_end)"></dd>
                            </div>
                        </div>
                    </div>

                    {{-- Detail Manpower --}}
                    <div>
                        <h4 class="text-md font-semibold mb-4 border-b pb-2">Detail Perubahan Manpower</h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-gray-500">Operator Awal:</dt>
                                <dd class="text-gray-900 font-semibold" x-text="detail.nama"></dd>

                                <dt class="text-gray-500 mt-4">Grup:</dt>
                                <dd class="text-gray-900" x-text="detail.man_power?.grup || '-'"></dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Operator Pengganti:</dt>
                                <dd class="text-gray-900 font-semibold" x-text="detail.nama_after"></dd>
                            </div>

                            <div class="md:col-span-2">
                                <dt class="text-gray-500">Keterangan:</dt>
                                <dd class="text-gray-900 bg-gray-50 p-3 rounded whitespace-pre-wrap" x-text="detail.keterangan || '-'"></dd>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ERROR --}}
                <div x-show="!loading && Object.keys(detail).length === 0" class="text-center p-8">
                    <p class="text-gray-500">Data tidak ditemukan.</p>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="mt-6 border-t pt-4 flex justify-end space-x-3">
                    <form :action="`/henkaten/approval/manpower/${id}/revisi`" method="POST" id="revision-form">
                        @csrf
                        <textarea name="revision_notes" class="hidden" x-ref="revisionNotes"></textarea>
                    </form>

                    <button
                        @click="$refs.revisionNotes.value = prompt('Catatan revisi (wajib)?'); if ($refs.revisionNotes.value) document.getElementById('revision-form').submit()"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Revisi
                    </button>

                    <form :action="`/henkaten/approval/manpower/${id}/approve`" method="POST">
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

    <script>
        function henkatenApproval() {
            return {
                showModal: false,
                loading: false,
                type: 'manpower',
                id: '',
                detail: {},

                openModal(type, id) {
                    this.id = id;
                    this.showModal = true;
                    this.loading = true;
                    this.detail = {};

                    fetch(`/api/henkaten-detail/${type}/${id}`)
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
                    const d = new Date(value);
                    return `${d.getDate().toString().padStart(2, '0')}-${(d.getMonth()+1).toString().padStart(2,'0')}-${d.getFullYear()}`;
                },

                formatTime(value) {
                    if (!value) return '-';
                    return value.substring(0,5);
                }
            }
        }
    </script>

</x-app-layout>
