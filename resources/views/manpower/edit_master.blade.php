<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Man Power') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-2xl font-bold text-gray-900 mb-6">
                        Edit Man Power: <span class="text-blue-600">{{ $man_power->nama }}</span>
                    </h3>

                    {{-- Menampilkan error validasi jika ada --}}
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p class="font-bold">Terjadi Kesalahan</p>
                            <ul class="list-disc list-inside mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('manpower.master.update', $man_power->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Karyawan</label>
                            <input type="text" 
                                   id="nama" 
                                   name="nama" 
                                   value="{{ old('nama', $man_power->nama) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            @error('nama') 
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> 
                            @enderror
                        </div>

                       {{-- Dropdown untuk Station Code, hanya muncul jika ada data station --}}
@if($stations->count() > 0)
    <div class="mb-4">
        <label for="station_id" class="block text-gray-700 text-sm font-bold mb-2">Station Code</label>
        <select id="station_id" 
                name="station_id" 
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
            {{-- Jika station_id null atau tidak ada, tampilkan opsi 'Pilih Station' --}}
            @if(is_null($man_power->station_id))
                <option value="" selected>Pilih Station</option>
            @endif
            
            {{-- Loop melalui semua stasiun yang tersedia --}}
            @foreach($stations as $station)
                <option value="{{ $station->id }}" 
                        {{ old('station_id', $man_power->station_id) == $station->id ? 'selected' : '' }}>
                    {{ $station->station_code }}
                </option>
            @endforeach
        </select>
        @error('station_id') 
            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> 
        @enderror
    </div>

  
@endif


                        <div class="mb-6">
                            <label for="shift" class="block text-gray-700 text-sm font-bold mb-2">Shift</label>
                            <select id="shift" 
                                    name="shift" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="Shift A" {{ old('shift', $man_power->shift) == 'Shift A' ? 'selected' : '' }}>
                                    Shift A
                                </option>
                                <option value="Shift B" {{ old('shift', $man_power->shift) == 'Shift B' ? 'selected' : '' }}>
                                    Shift B
                                </option>
                            </select>
                            @error('shift') 
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> 
                            @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <a href="{{ route('manpower.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md transition duration-200">
                                Update Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script>
        // Script untuk memperbarui tampilan Station ID saat pilihan di dropdown berubah
        document.addEventListener('DOMContentLoaded', function() {
            const stationSelect = document.getElementById('station_id');
            const stationIdDisplay = document.getElementById('station_id_display');

            stationSelect.addEventListener('change', function() {
                // Update station ID display dengan nilai yang dipilih
                stationIdDisplay.value = this.value || '';
            });
        });
    </script>
</x-app-layout>