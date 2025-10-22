<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- Judul diubah ke Material --}}
            {{ __('Mulai Eksekusi Henkaten Material') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Notifikasi Sukses (Struktur sama) --}}
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">{{ session('success') }}</p>
                        </div>
                    @endif

                    <p class="mb-4 text-gray-600">
                        Isi Serial Number Start dan End untuk Henkaten yang sudah siap dieksekusi.
                    </p>

                    {{-- Form action diubah ke route 'material' --}}
                    <form action="{{ route('henkaten.material.start.update') }}" method="POST">
                        @csrf
                        @method('PATCH') {{-- Menggunakan method PATCH untuk update --}}

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border">
                                <thead class="bg-gray-200">
                                    <tr>
                                        {{-- Header kolom diubah ke Material --}}
                                        <th class="py-2 px-4 border-b">Nama Material (Before)</th>
                                        <th class="py-2 px-4 border-b">Nama Material (After)</th>
                                        <th class="py-2 px-4 border-b">Station</th>
                                        <th class="py-2 px-4 border-b">Shift</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number Start</th>
                                        <th class="py-2 px-4 border-b text-center">Serial Number End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Menggunakan variabel baru: $materialHenkatens --}}
                                    @forelse ($materialHenkatens as $item)
                                        <tr>
                                            {{-- Data diubah ke material_name & material_after --}}
                                            <td class="py-2 px-4 border-b">{{ $item->material_name }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->material_after }}</td>
                                            <td class="py-2 px-4 border-b">{{ $item->station->station_name ?? 'N/A' }}</td>
                                            <td class="py-2 px-4 border-b text-center">{{ $item->shift }}</td>
                                            
                                            {{-- Input untuk Serial Number Start (Struktur sama) --}}
                                            <td class="py-2 px-4 border-b">
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_start]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm"
                                                       value="{{ old('updates.' . $item->id . '.serial_number_start') }}">
                                            </td>
                                            
                                            {{-- Input untuk Serial Number End (Struktur sama) --}}
                                            <td class="py-2 px-4 border-b">
                                                <input type="text"
                                                       name="updates[{{ $item->id }}][serial_number_end]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm"
                                                       value="{{ old('updates.' . $item->id . '.serial_number_end') }}">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                {{-- Pesan disesuaikan untuk Material --}}
                                                Tidak ada data Henkaten Material yang perlu diupdate.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Kondisi disesuaikan ke $materialHenkatens --}}
                        @if ($materialHenkatens->isNotEmpty())
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
