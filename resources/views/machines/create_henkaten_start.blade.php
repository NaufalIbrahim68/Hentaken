<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- DIUBAH: Judul Halaman --}}
            {{ __('Mulai Eksekusi Henkaten Machine') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Notifikasi Sukses --}}
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">{{ session('success') }}</p>
                        </div>
                    @endif

                    <p class="mb-4 text-gray-600">
                        Isi Serial Number Start dan End untuk Henkaten yang sudah siap dieksekusi.
                    </p>

                    {{-- DIUBAH: Form action ke route machine --}}
                    <form action="{{ route('henkaten.machine.start.update') }}" method="POST">
                        @csrf
                        @method('PATCH') {{-- Menggunakan method PATCH untuk update --}}

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border">
                                <thead class="bg-gray-200">
                                    <tr>
                                        {{-- DIUBAH: Kolom disesuaikan untuk Machine --}}
                                        <th class="py-2 px-4 border-b">Kategori</th>
                                        <th class="py-2 px-4 border-b">Deskripsi (Before)</th>
                                        <th class="py-2 px-4 border-b">Deskripsi (After)</th>
                                        <th class="py-2 px-4 border-b">Station</th>
                                        <th class="py-2 px-4 border-b">Shift</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number Start</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Asumsi controller mengirim variabel $henkatens --}}
                                    @forelse ($henkatens as $item)
                                        <tr>
                                            {{-- DIUBAH: Data disesuaikan untuk Machine --}}
                                            <td class="py-2 px-4 border-b">{{ $item->machine }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->description_before }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->description_after }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->station->station_name ?? 'N/A' }}</td>
                                            <td class="py-2 px-4 border-b text-center">{{ $item->shift }}</td>
                                            <td class="py-2 px-4 border-b">
                                                {{-- Input untuk Serial Number Start --}}
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_start]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm">
                                            </td>
                                            <td class="py-2 px-4 border-b">
                                                {{-- Input untuk Serial Number End --}}
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_end]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            {{-- DIUBAH: Colspan disesuaikan jadi 7 --}}
                                            <td colspan="7" class="text-center py-4">
                                                Tidak ada data Henkaten Machine yang perlu diupdate.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Hanya tampilkan tombol jika ada data --}}
                        @if ($henkatens->isNotEmpty())
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