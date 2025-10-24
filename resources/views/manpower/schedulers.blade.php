<x-app-layout>
    {{-- Header Halaman --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Scheduler - Manpower') }}
        </h2>
    </x-slot>

    {{-- Konten Halaman --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Logic form sudah benar: action=dashboard, method=GET --}}
                    <form action="{{ route('dashboard') }}" method="GET">
                        {{-- @csrf tidak diperlukan untuk method GET --}}
                        
                        <div class="space-y-6">

                            {{-- Grid Tanggal & Waktu --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div>
                                    <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                        {{-- Tambahkan value untuk mengingat nilai --}}
                                        value="{{ request('tanggal_mulai') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="waktu_mulai" class="block text-sm font-medium text-gray-700">Waktu Mulai</label>
                                    <input type="time" name="waktu_mulai" id="waktu_mulai"
                                        {{-- Tambahkan value untuk mengingat nilai --}}
                                        value="{{ request('waktu_mulai') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="tanggal_berakhir" class="block text-sm font-medium text-gray-700">Tanggal Berakhir</label>
                                    <input type="date" name="tanggal_berakhir" id="tanggal_berakhir"
                                        {{-- Tambahkan value untuk mengingat nilai --}}
                                        value="{{ request('tanggal_berakhir') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="waktu_berakhir" class="block text-sm font-medium text-gray-700">Waktu Berakhir</label>
                                    <input type="time" name="waktu_berakhir" id="waktu_berakhir"
                                        {{-- Tambahkan value untuk mengingat nilai --}}
                                        value="{{ request('waktu_berakhir') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            {{-- Grid Shift & Grup --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                                    <select id="shift" name="shift" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="" disabled {{ !request('shift') ? 'selected' : '' }}>Pilih Shift...</option>
                                        {{-- Tambahkan pengecekan 'selected' --}}
                                        <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>1</option>
                                        <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>2</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="grup" class="block text-sm font-medium text-gray-700">Grup</label>
                                    <select id="grup" name="grup" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="" disabled {{ !request('grup') ? 'selected' : '' }}>Pilih Grup...</option>
                                        {{-- Tambahkan pengecekan 'selected' --}}
                                        <option value="A" {{ request('grup') == 'A' ? 'selected' : '' }}>A</option>
                                        <option value="B" {{ request('grup') == 'B' ? 'selected' : '' }}>B</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Tombol Submit --}}
                            <div class="flex justify-end">
                                
                                <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Filter
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>