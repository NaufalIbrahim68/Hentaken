<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- Mengganti judul untuk Method --}}
            {{ __('Mulai Eksekusi Henkaten Method') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Notifikasi Sukses (Struktur dipertahankan) --}}
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">{{ session('success') }}</p>
                        </div>
                    @endif

                    <p class="mb-4 text-gray-600">
                        Isi Serial Number Start dan End untuk Henkaten yang sudah siap dieksekusi.
                    </p>

                    {{-- Ganti route ke controller yang sesuai untuk Method --}}
                    <form action="{{ route('henkaten.method.start.update') }}" method="POST">
                        @csrf
                        @method('PATCH') {{-- Menggunakan method PATCH untuk update --}}

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border">
                                <thead class="bg-gray-200">
                                    <tr>
                                        {{-- Menyesuaikan kolom berdasarkan methods_henkaten --}}
                                        <th class="py-2 px-4 border-b">Keterangan (Before)</th>
                                        <th class="py-2 px-4 border-b">Keterangan (After)</th>
                                        <th class="py-2 px-4 border-b">Station</th>
                                        <th class="py-2 px-4 border-b">Shift</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number Start</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Menggunakan variabel baru, misal $methodsHenkatens --}}
                                    @forelse ($methodsHenkatens as $item)
                                        <tr>
                                            {{-- Menampilkan data dari methods_henkaten --}}
                                            <td class="py-2 px-4 border-b">{{ $item->keterangan }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->keterangan_after }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->station->station_name ?? 'N/A' }}</td>
                                            <td class="py-2 px-4 border-b text-center">{{ $item->shift }}</td>
                                            
                                            {{-- Input untuk Serial Number Start (Struktur sama persis) --}}
                                            <td class="py-2 px-4 border-b">
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_start]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm"
                                                       value="{{ old('updates.' . $item->id . '.serial_number_start') }}"> {{-- Menambahkan old() --}}
                                            </td>
                                            
                                            {{-- Input untuk Serial Number End (Struktur sama persis) --}}
                                            <td class="py-2 px-4 border-b">
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_end]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm"
                                                       value="{{ old('updates.' . $item->id . '.serial_number_end') }}"> {{-- Menambahkan old() --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                {{-- Pesan disesuaikan untuk Method --}}
                                                Tidak ada data Henkaten Method yang perlu diupdate.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Hanya tampilkan tombol jika ada data --}}
                        @if ($methodsHenkatens->isNotEmpty())
                            <div class="flex justify-end mt-6">
                                <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md">
                                    Simpan Semua Perubahan
                                </button>
                            </div>
                        @endif
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>