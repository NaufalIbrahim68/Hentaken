<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfirmasi Henkaten 4M') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8" x-data="henkatenApproval()">

        @foreach (['manpower', 'method', 'machine', 'material'] as $type)
            @php
                // Mengambil data dari variabel yang sesuai (misal: $manpowers, $methods, dll.)
                // Pastikan variabel ini di-pass dari controller Anda.
                $items = ${$type.'s'} ?? collect(); 
            @endphp

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 capitalize">Henkaten {{ $type }}</h3>

                @if ($items->isEmpty())
                    <p class="text-sm text-gray-500">Tidak ada data henkaten pending untuk dikonfirmasi.</p>
                @else
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Poin Perubahan / Masalah</th>
                                <th class="px-4 py-2 text-left">Line Area</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $item->id }}</td>
                                    {{-- Menggunakan field yang umum ada di henkaten --}}
                                    <td class="px-4 py-2">{{ $item->change_point ?? $item->problem ?? $item->deskripsi ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $item->line_area ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-800">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button 
                                            @click="openModal('{{ $type }}', {{ $item->id }})"
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
        @endforeach

        {{-- Modal Detail --}}
        <div x-show="showModal" 
             class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4"
             @click.away="showModal = false"
             x-cloak
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
<div class="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[80vh] overflow-y-auto" @click.stop>
                        <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Detail Henkaten (<span x-text="type" class="capitalize"></span>)</h3>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-800">&times;</button>
                </div>
                
                {{-- Konten Modal: Menampilkan data form henkaten --}}
                <div x-show="loading" class="text-center p-8">
                    <p class="text-gray-500">Loading data...</p>
                </div>


             <div x-show="!loading && Object.keys(detail).length > 0" class="text-sm">
    
    <h4 class="text-md font-semibold text-gray-800 mb-3">Informasi Umum</h4>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
        
        <div class="space-y-3">
            <div>
                <dt class="font-medium text-gray-500">ID Henkaten:</dt>
                <dd class="text-gray-900" x-text="detail.id || '-'"></dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Station / Line:</dt>
                <dd class="text-gray-900">
                    <span x-text="detail.station_id ? 'Station ' + detail.station_name : '-'"></span> / 
                    <span x-text="detail.line_area || 'N/A'"></span>
                </dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Shift:</dt>
                <dd class="text-gray-900" x-text="detail.shift || '-'"></dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Lampiran:</dt>
                <dd>
                    <template x-if="detail.lampiran && detail.lampiran !== '-'">
                        <a :href="`/storage/${detail.lampiran}`" 
                           target="_blank" 
                           class="text-blue-600 hover:underline"
                           x-text="getFileName(detail.lampiran)">
                        </a>
                    </template>
                    <template x-if="!detail.lampiran || detail.lampiran === '-'">
                        <span class="text-gray-900">-</span>
                    </template>
                </dd>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <dt class="font-medium text-gray-500">Waktu Mulai (Efektif):</dt>
                <dd class="text-gray-900">
                    <span x-text="formatDate(detail.effective_date)"></span> @ <span x-text="detail.time_start || 'N/A'"></span>
                </dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Waktu Selesai (Estimasi):</dt>
                <dd class="text-gray-900">
                    <span x-text="formatDate(detail.end_date)"></span> @ <span x-text="detail.time_end || 'N/A'"></span>
                </dd>
            </div>
           
        </div>
    </dl>

    <div class="mt-4 border-t pt-4">
        <h4 class="text-md font-semibold text-gray-800 mb-3">Detail Perubahan (<span x-text="type" class="capitalize"></span>)</h4>

        <template x-if="type === 'manpower'">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                <div>
                    <dt class="font-medium text-gray-500">Operator Awal:</dt>
                    <dd class="text-gray-900"><span x-text="detail.nama || 'N/A'"></span></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Operator Pengganti:</dt>
                    <dd class="text-gray-900"><span x-text="detail.nama_after || 'N/A'"></span></dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-medium text-gray-500">Keterangan:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.keterangan || '-'"></dd>
                </div>
            </dl>
        </template>

        <template x-if="type === 'machine'">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3">
                 <div>
                    <dt class="font-medium text-gray-500">Mesin:</dt>
                    <dd class="text-gray-900" x-text="detail.machine || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Deskripsi Sebelum:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.description_before || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Deskripsi Sesudah:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.description_after || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Keterangan:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.keterangan || '-'"></dd>
                </div>
            </dl>
        </template>

        <template x-if="type === 'material'">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                <div>
                    <dt class="font-medium text-gray-500">Material ID:</dt>
                    <dd class="text-gray-900" x-text="detail.material_id || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Status:</dt>
                    <dd class="text-gray-900 capitalize" x-text="detail.status || '-'"></dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-medium text-gray-500">Deskripsi Sebelum:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.description_before || '-'"></dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-medium text-gray-500">Deskripsi Sesudah:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.description_after || '-'"></dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-medium text-gray-500">Keterangan:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.keterangan || '-'"></dd>
                </div>
            </dl>
        </template>

        <template x-if="type === 'method'">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3">
                <div>
                    <dt class="font-medium text-gray-500">Status:</dt>
                    <dd class="text-gray-900 capitalize" x-text="detail.status || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Keterangan Sebelum:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.keterangan || '-'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Keterangan Sesudah:</dt>
                    <dd class="text-gray-900 whitespace-pre-wrap" x-text="detail.keterangan_after || '-'"></dd>
                </div>
            </dl>
        </template>
    </div>
</div>

<div x-show="!loading && Object.keys(detail).length === 0" class="text-center p-8">
    <p class="text-gray-500">Gagal memuat data atau data tidak ditemukan.</p>
</div>
                
                {{-- ================================================ --}}
                {{-- == AKHIR PERUBAHAN KONTEN MODAL == --}}
                {{-- ================================================ --}}


                {{-- Tombol Aksi --}}
                <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                    <form :action="`/henkaten/approval/${type}/${id}/revisi`" method="POST">
                        @csrf
                        <button type-="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow-sm">
                            Revisi
                        </button>
                    </form>
                    <form :action="`/henkaten/approval/${type}/${id}/approve`" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded shadow-sm">
                            Approve
                        </button>
                    </form>
                    <button @click="showModal = false" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded shadow-sm">
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
                type: '',
                id: '',
                detail: {},

                openModal(type, id) {
                    this.loading = true;
                    this.showModal = true;
                    this.type = type;
                    this.id = id;
                    this.detail = {}; // Reset detail

                    // Pastikan endpoint API ini ada dan mengembalikan JSON data henkaten
                    fetch(`/api/henkaten-detail/${type}/${id}`)
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return res.json();
                        })
                        .then(data => {
                            this.detail = data;
                            this.loading = false;
                        })
                        .catch(error => {
                            console.error('Error fetching henkaten detail:', error);
                            this.detail = { error: 'Gagal memuat data.' };
                            this.loading = false;
                        });
                },

                // == AWAL FUNGSI HELPER BARU ==
                
                /**
                 * 3. Memformat tanggal ISO (2025-11-04T...) menjadi dd-mm-yyyy
                 */
                formatDate(isoString) {
                    if (!isoString) return '-';
                    try {
                        const date = new Date(isoString);
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() 0-11
                        const year = date.getFullYear();
                        return `${day}-${month}-${year}`; // Hasil: 04-11-2025
                    } catch (e) {
                        console.error('Invalid date string:', isoString);
                        return isoString; // Kembalikan string asli jika error
                    }
                },

                /**
                 * 1. Mengambil nama file dari path (mis: 'folder/file.jpg' -> 'file.jpg')
                 */
                getFileName(path) {
                    if (!path) return '';
                    return path.split('/').pop();
                }

                // == AKHIR FUNGSI HELPER BARU ==
            }
        }
    </script>
</x-app-layout>