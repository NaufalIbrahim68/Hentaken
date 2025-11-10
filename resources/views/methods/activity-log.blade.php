<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- UBAH: Judul --}}
            {{ __('Activity Log - Method') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- PESAN SUKSES (Dari screenshot Anda) --}}
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                             x-init="setTimeout(() => show = false, 3000)"
                             class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md relative"
                             role="alert">
                            <span class="block font-semibold">{{ session('success') }}</span>
                            <button @click="show = false"
                                    class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">
                                &times;
                            </button>
                        </div>
                    @endif

                    {{-- FORM FILTER --}}
                    <div class="flex justify-end mb-4">
                        {{-- UBAH: Route ke 'activity.log.method' --}}
                        <form action="{{ route('activity.log.method') }}" method="GET"
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
                            {{-- UBAH: Route ke 'activity.log.method' --}}
                            <a href="{{ route('activity.log.method') }}" class="py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-md 
                                text-gray-700 bg-white hover:bg-gray-100 
                                focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Reset
                            </a>
                        </form>
                    </div>
                </div>

                {{-- NOTIFIKASI JIKA DATA TIDAK DITEMUKAN --}}
                @if ($logs->isEmpty() && $created_date)
                    <div class="mb-4 p-4 rounded-md bg-yellow-100 border border-yellow-400 text-yellow-700 mx-6">
                        {{-- UBAH: Teks --}}
                        Tidak ada data Henkaten Method untuk tanggal
                        <strong>{{ \Carbon\Carbon::parse($created_date)->format('d M Y') }}</strong>.
                    </div>
                @endif

                {{-- TABEL DATA --}}
                {{-- Gaya dari Man Power: overflow-x-auto, shadow-md, sm:rounded-lg --}}
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg mx-6 mb-6">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                {{-- UBAH: Header disesuaikan untuk 'Method' --}}
                                <th scope="col" class="py-3 px-3">Tanggal Dibuat</th>
                                <th scope="col" class="py-3 px-3">Keterangan Sebelum</th>
                                <th scope="col" class="py-3 px-3">Keterangan Sesudah</th>
                                <th scope="col" class="py-3 px-3">Station</th>
                                <th scope="col" class="py-3 px-3">Line Area</th>
                                <th scope="col" class="py-3 px-3">Tgl Efektif</th>
                                <th scope="col" class="py-3 px-3">Tgl Selesai</th>
                                
                                {{-- TAMBAH: Kolom Note (dari Man Power) --}}
                                <th scope="col" class="py-3 px-3">Note</th> 
                                
                                {{-- TAMBAH: Kolom Status (dari Man Power) --}}
                                <th scope="col" class="py-3 px-3">Status</th>

                                <th scope="col" class="py-3 px-3">Lampiran</th>
                                <th scope="col" class="py-3 px-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                {{-- UBAH: Tambah class 'text-xs' dari Man Power --}}
                                <tr class="bg-white border-b hover:bg-gray-50 text-xs">
                                    
                                    {{-- UBAH: class 'py-2 px-3' & data disesuaikan --}}
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        {{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-2 px-3 max-w-xs break-words">{{ $log->keterangan ?? '-' }}</td>
                                    <td class="py-2 px-3 max-w-xs break-words">{{ $log->keterangan_after ?? '-' }}</td>
                                    <td class="py-2 px-3">{{ $log->station->station_name ?? 'N/A' }}</td>
                                    <td class="py-2 px-3">{{ $log->station->line_area ?? '-' }}</td>
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        {{ $log->effective_date ? \Carbon\Carbon::parse($log->effective_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        {{ $log->end_date ? \Carbon\Carbon::parse($log->end_date)->format('d M Y') : '-' }}
                                    </td>
                                    
                                    {{-- TAMBAH: Kolom Note (dari Man Power) --}}
                                    <td class="py-2 px-3 max-w-xs break-words">
                                        {{-- Tampilkan note hanya jika status BUKAN 'Approve' --}}
                                        @if ($log->status != 'Approved')
                                            {{ $log->note ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    
                                    {{-- TAMBAH: Kolom Status (dari Man Power) --}}
                                    <td class="py-2 px-3">
                                        @php
                                            $status = $log->status;
                                            $badgeClass = '';

                                            if($status == 'Approved') {
                                                $badgeClass = 'bg-green-100 text-green-700';
                                            } elseif ($status == 'Pending') {
                                                $badgeClass = 'bg-yellow-100 text-yellow-700';
                                            } elseif ($status == 'Revisi') {
                                                $badgeClass = 'bg-red-100 text-red-700';
                                            } else {
                                                $badgeClass = 'bg-gray-100 text-gray-700';
                                            }
                                        @endphp

                                        <span class="text-xs font-semibold inline-block py-1 px-2 rounded-full whitespace-nowrap {{ $badgeClass }}">
                                            {{ $status ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="py-2 px-3">
                                        @if ($log->lampiran)
                                            {{-- UBAH: Style tombol disamakan dengan Man Power --}}
                                            <a href="{{ asset('storage/' . $log->lampiran) }}" target="_blank"
                                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-2 py-1 rounded-md transition whitespace-nowrap">
                                                Lihat Lampiran
                                            </a>
                                        @else
                                            <span class="text-gray-400">Tidak ada</span>
                                        @endif
                                    </td>
                                    
                                    <td class="py-2 px-3">
                                        <div class="flex space-x-1">
                                            {{-- UBAH: Style tombol & route disamakan --}}
                                            <a href="{{ route('activity.log.method.edit', $log->id) }}" 
                                               class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium px-2 py-1 rounded-md transition">
                                                Edit
                                            </a>
                                            {{-- UBAH: Style tombol & route disamakan --}}
                                            <form action="{{ route('activity.log.method.destroy', $log->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="inline-block bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-2 py-1 rounded-md transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b">
                                    {{-- UBAH: Colspan disesuaikan (11 kolom) --}}
                                    <td colspan="11" class="py-4 px-6 text-center text-gray-500">
                                        Tidak ada data Henkaten Method
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                <div class="mt-4 p-6 pt-0">
                    {{-- Menggunakan paginator default Tailwind dari Laravel --}}
                    {{ $logs->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>