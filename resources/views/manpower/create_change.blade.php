<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Form Perubahan Man Power (Permanen)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Catat Pergantian Man Power
                    </h3>

                    <p class="text-sm text-gray-600 mb-6">
                        Form ini akan mencatat Henkaten (perubahan) untuk Man Power. Data 'sebelum' diambil dari master. Silakan isi data 'sesudah' (pengganti) dan tanggal mulai.
                    </p>

                    <form action="{{ route('henkaten.manpower.storeChange') }}" method="POST">
                        @csrf

                        <input type="hidden" name="master_man_power_id" value="{{ $manPower->id }}">
                        
                        <input type="hidden" name="station_id" value="{{ $manPower->station_id }}">
                        
                        <input type="hidden" name="grup" value="{{ $manPower->grup }}">
                        
                        <input type="hidden" name="jenis_henkaten" value="PERMANEN">
                        
                        <input type="hidden" name="line_area" value="{{ $manPower->line_area }}">


                        <div class="space-y-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nama_sebelum" class="block text-sm font-medium text-gray-700">
                                        Nama Sebelum (Original)
                                    </label>
                                    <input type="text" id="nama_sebelum" name="nama_sebelum"
                                        value="{{ $manPower->nama }}"
                                        class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        readonly>
                                </div>
                                
                                <div>
                                    <label for="nama_sesudah" class="block text-sm font-medium text-gray-700">
                                        Nama Sesudah (Pengganti)
                                    </label>
                                    <input type="text" id="nama_sesudah" name="nama_sesudah"
                                        value="{{ old('nama_sesudah') }}"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required autofocus>
                                    @error('nama_sesudah')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="line_area_display" class="block text-sm font-medium text-gray-700">
                                        Line Area
                                    </label>
                                    <input type="text" id="line_area_display"
                                        value="{{ $manPower->line_area }}"
                                        class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        readonly>
                                </div>
                                
                                <div>
                                    <label for="station_display" class="block text-sm font-medium text-gray-700">
                                        Station
                                    </label>
                                    <input type="text" id="station_display"
                                        value="{{ $manPower->station?->station_name ?? 'N/A' }}"
                                        class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        readonly>
                                </div>

                                <div>
                                    <label for="grup_display" class="block text-sm font-medium text-gray-700">
                                        Grup
                                    </label>
                                    <input type="text" id="grup_display"
                                        value="{{ $manPower->grup }}"
                                        class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        readonly>
                                </div>
                            </div>

                            <div>
                                <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">
                                    Tanggal Mulai Pergantian
                                </label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                                    value="{{ old('tanggal_mulai', date('Y-m-d')) }}"
                                    class="mt-1 block w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    required>
                                @error('tanggal_mulai')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="keterangan" class="block text-sm font-medium text-gray-700">
                                    Keterangan (Opsional)
                                </label>
                                <textarea id="keterangan" name="keterangan" rows="3"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >{{ old('keterangan') }}</textarea>
                                @error('keterangan')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('manpower.index') }}"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md transition duration-300 mr-3">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                                Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>