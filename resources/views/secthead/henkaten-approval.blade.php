<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfirmasi Henkaten 4M') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8" x-data="henkatenApproval()">

    @foreach (['method', 'machine', 'material'] as $type)
            @php
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
                                {{-- HEADER DINAMIS --}}
                                @if ($type === 'manpower')
                                    <th class="px-4 py-2 text-left">Operator Awal</th>
                                    <th class="px-4 py-2 text-left">Operator Pengganti</th>
                                @elseif ($type === 'method')
                                    <th class="px-4 py-2 text-left">Keterangan Awal</th>
                                    <th class="px-4 py-2 text-left">Keterangan Sesudah</th>
                                @elseif ($type === 'material')
                                    <th class="px-4 py-2 text-left">Material</th>
                                    <th class="px-4 py-2 text-left">Deskripsi Sebelum</th>
                                    <th class="px-4 py-2 text-left">Deskripsi Sesudah</th>
                                @elseif ($type === 'machine')
                                    <th class="px-4 py-2 text-left">Mesin</th>
                                    <th class="px-4 py-2 text-left">Deskripsi Sebelum</th>
                                    <th class="px-4 py-2 text-left">Deskripsi Sesudah</th>
                                @endif

                                {{-- HEADER UMUM --}}
                                <th class="px-4 py-2 text-left">Line Area</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr class="border-t">
                                    {{-- DATA DINAMIS --}}
                                    @if ($type === 'manpower')
                                        <td class="px-4 py-2">{{ $item->nama ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->nama_after ?? '-' }}</td>
                                    @elseif ($type === 'method')
                                        <td class="px-4 py-2">{{ $item->keterangan ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->keterangan_after ?? '-' }}</td>
                                    @elseif ($type === 'material')
                                        {{-- Menggunakan "?->" untuk "optional chaining" jika $item->material mungkin null --}}
                                        <td class="px-4 py-2">{{ $item->material?->material_name ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->description_before ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->description_after ?? '-' }}</td>
                                    @elseif ($type === 'machine')
                                        <td class="px-4 py-2">{{ $item->machine ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->description_before ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $item->description_after ?? '-' }}</td>
                                    @endif

                                    {{-- DATA UMUM --}}
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
        {{-- Modal Detail dengan Layout Kanan-Kiri --}}
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
            
            <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Detail Henkaten (<span x-text="type" class="capitalize"></span>)</h3>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                </div>
                
                {{-- Loading State --}}
                <div x-show="loading" class="text-center p-8">
                    <p class="text-gray-500">Loading data...</p>
                </div>

                {{-- Konten Modal dengan Layout 2 Kolom --}}
                <div x-show="!loading && Object.keys(detail).length > 0" class="text-sm">
                    
                    {{-- Informasi Umum - Layout 2 Kolom --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b">
                        {{-- Kolom Kiri --}}
                        <div class="space-y-4">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">Informasi Umum</h4>
                            
                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Station / Line:</dt>
                                <dd class="text-gray-900">
                                    <span x-text="detail.station?.station_name || '-'"></span> / 
                                    <span x-text="detail.line_area || '-'"></span>
                                </dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Shift:</dt>
                                <dd class="text-gray-900" x-text="detail.shift || '-'"></dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Lampiran:</dt>
                                <dd>
                                    <template x-if="detail.lampiran && detail.lampiran !== '-'">
                                        <a :href="`/storage/${detail.lampiran}`" 
                                           target="_blank" 
                                           class="text-blue-600 hover:underline inline-flex items-center">
                                           <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                           </svg>
                                           Unduh Lampiran
                                        </a>
                                    </template>
                                    <template x-if="!detail.lampiran || detail.lampiran === '-'">
                                        <span class="text-gray-900">-</span>
                                    </template>
                                </dd>
                            </div>
                        </div>

                        {{-- Kolom Kanan --}}
                        <div class="space-y-4">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">Waktu</h4>
                            
                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Waktu Mulai (Efektif):</dt>
                                <dd class="text-gray-900">
                                    <span x-text="formatDate(detail.effective_date)"></span> @ <span x-text="formatTime(detail.time_start)"></span>
                                </dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Waktu Selesai (Estimasi):</dt>
                                <dd class="text-gray-900">
                                    <span x-text="formatDate(detail.end_date)"></span> @ <span x-text="formatTime(detail.time_end)"></span>
                                </dd>
                            </div>
                        </div>
                    </div>

                    {{-- Detail Perubahan - Layout 2 Kolom --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- MANPOWER --}}
                        <template x-if="type === 'manpower'">
                            <div class="md:col-span-2">
                                <h4 class="text-md font-semibold text-gray-800 mb-4 border-b pb-2">Detail Perubahan Manpower</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Kolom Kiri --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Operator Awal:</dt>
                                            <dd class="text-gray-900 font-semibold" x-text="detail.nama || 'N/A'"></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Grup:</dt>
                                            <dd class="text-gray-900" x-text="detail.man_power?.grup || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Kolom Kanan --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Operator Pengganti:</dt>
                                            <dd class="text-gray-900 font-semibold" x-text="detail.nama_after || 'N/A'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Keterangan Full Width --}}
                                    <div class="md:col-span-2">
                                        <dt class="font-medium text-gray-500 mb-1">Keterangan:</dt>
                                        <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.keterangan || '-'"></dd>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- MACHINE --}}
                        <template x-if="type === 'machine'">
                            <div class="md:col-span-2">
                                <h4 class="text-md font-semibold text-gray-800 mb-4 border-b pb-2">Detail Perubahan Machine</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Kolom Kiri --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Mesin:</dt>
                                            <dd class="text-gray-900 font-semibold" x-text="detail.machine || '-'"></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Deskripsi Sebelum:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.description_before || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Kolom Kanan --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Deskripsi Sesudah:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-green-50 p-3 rounded" x-text="detail.description_after || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Keterangan Full Width --}}
                                    <div class="md:col-span-2">
                                        <dt class="font-medium text-gray-500 mb-1">Keterangan:</dt>
                                        <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.keterangan || '-'"></dd>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- MATERIAL --}}
                        <template x-if="type === 'material'">
                            <div class="md:col-span-2">
                                <h4 class="text-md font-semibold text-gray-800 mb-4 border-b pb-2">Detail Perubahan Material</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Kolom Kiri --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Material:</dt>
                                            <dd class="text-gray-900 font-semibold" x-text="detail.material?.material_name || '-'"></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Status:</dt>
                                            <dd class="text-gray-900 capitalize" x-text="detail.status || '-'"></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Deskripsi Sebelum:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.description_before || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Kolom Kanan --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Deskripsi Sesudah:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-green-50 p-3 rounded" x-text="detail.description_after || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Keterangan Full Width --}}
                                    <div class="md:col-span-2">
                                        <dt class="font-medium text-gray-500 mb-1">Keterangan:</dt>
                                        <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.keterangan || '-'"></dd>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- METHOD --}}
                        <template x-if="type === 'method'">
                            <div class="md:col-span-2">
                                <h4 class="text-md font-semibold text-gray-800 mb-4 border-b pb-2">Detail Perubahan Method</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Kolom Kiri --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Status:</dt>
                                            <dd class="text-gray-900 capitalize" x-text="detail.status || '-'"></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Keterangan Sebelum:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded" x-text="detail.keterangan || '-'"></dd>
                                        </div>
                                    </div>
                                    
                                    {{-- Kolom Kanan --}}
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="font-medium text-gray-500 mb-1">Keterangan Sesudah:</dt>
                                            <dd class="text-gray-900 whitespace-pre-wrap bg-green-50 p-3 rounded" x-text="detail.keterangan_after || '-'"></dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Error State --}}
                <div x-show="!loading && Object.keys(detail).length === 0" class="text-center p-8">
                    <p class="text-gray-500">Gagal memuat data atau data tidak ditemukan.</p>
                </div>

                {{-- Tombol Aksi --}}
                        <div class="mt-6 border-t pt-4">

                            {{-- Form untuk Revisi (hanya berisi textarea) --}}
                            <form :action="`/henkaten/approval/${type}/${id}/revisi`" method="POST" id="revision-form">
                                @csrf
                                <div class="mb-4">
                                    <label for="revision_notes" class="block text-sm font-medium text-gray-700 mb-1">
                                        Catatan Revisi 
                                        <span class="text-gray-500">(Wajib diisi jika ada Revisi)</span>
                                    </label>
                                    <textarea 
                                        id="revision_notes" 
                                        name="revision_notes" {{-- 'name' ini penting untuk dikirim ke backend --}}
                                        rows="3" 
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                                        placeholder="Contoh: Lampiran tidak sesuai, mohon diperbaiki..."
                                        required {{-- Tambahkan 'required' agar wajib diisi saat form disubmit --}}
                                    ></textarea>
                                </div>
                            </form>

                            {{-- Baris Tombol Aksi --}}
                            <div class="flex justify-end space-x-3">
                                
                                {{-- Tombol Revisi: Mensubmit form 'revision-form' di atas --}}
                                <button 
                                    type="submit" 
                                    form="revision-form" {{-- Atribut 'form' ini akan menyambungkan tombol ke form --}}
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow-sm">
                                    Revisi
                                </button>
                                
                                {{-- Form Approve (terpisah) --}}
                                <form :action="`/henkaten/approval/${type}/${id}/approve`" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded shadow-sm">
                                        Approve
                                    </button>
                                </form>
                                
                                {{-- Tombol Tutup --}}
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
                    this.detail = {};

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

                formatDate(isoString) {
                    if (!isoString) return '-';
                    try {
                        const date = new Date(isoString);
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const year = date.getFullYear();
                        return `${day}-${month}-${year}`;
                    } catch (e) {
                        console.error('Invalid date string:', isoString);
                        return isoString;
                    }
                },

formatTime(timeString) {
                    if (!timeString || timeString === 'N/A') return '-';

                    // 1. Gunakan Regex untuk mencari pola "HH:MM" (dua digit:dua digit)
                    //    Ini akan cocok dengan "07:00" dari string apapun, termasuk:
                    //    - "07:00:00.0000000" (SQL Server time)
                    //    - "1900-01-01 07:00:00" (SQL Server datetime)
                    //    - "07:00:00"
                    //    - "07:00"
                    const match = String(timeString).match(/(\d{2}:\d{2})/);

                    // 2. Jika pola "HH:MM" ditemukan, kembalikan itu
                    if (match) {
                        return match[0]; // Hasilnya akan "07:00"
                    }

                    // 3. Jika tidak ada pola yang cocok, kembalikan string aslinya
                    return timeString;
                },

                getFileName(path) {
                    if (!path) return '';
                    return path.split('/').pop();
                }
            }
        }
    </script>
</x-app-layout>