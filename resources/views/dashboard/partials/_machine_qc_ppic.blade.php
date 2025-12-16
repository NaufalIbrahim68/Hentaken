{{-- MACHINE QC-PPIC --}}
<div class="bg-white shadow rounded p-4 flex flex-col mt-1">
    <h2 class="text-sm font-semibold mb-3 text-center">MACHINE</h2>

    {{-- Machine Table --}}
    <div class="w-full">
        <table class="w-full border-collapse">
            <tbody>
                {{-- Row Icon --}}
                <tr>
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                            <div class="flex justify-center items-center">
                                <div class="machine-status rounded-full flex items-center justify-center cursor-pointer" 
                                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; background-color: #9333ea;"
                                     onclick="toggleMachine(this)">
                                    <svg class="text-white" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
                
                {{-- Row Name (Below Icon) --}}
                <tr>
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                            $textColor = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <td class="border border-gray-300 px-1 py-1.5 {{ $bgColorCell }}">
                            <div class="text-center">
                                <p class="text-[9px] font-semibold {{ $textColor }} leading-tight break-words">
                                    {{ $mc->machines_category ?? $mc->station->station_name ?? '-' }}
                                </p>
                            </div>
                        </td>
                    @endforeach
                </tr>
                
                {{-- Row Status --}}
                <tr>
                    @foreach ($machines as $mc)
                        @php
                            $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColor }}">
                            <div class="flex justify-center">
                                <div class="rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}" style="width: 12px; height: 12px; min-width: 12px; min-height: 12px;"></div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="flex justify-center gap-4 mt-3 text-[10px]">
        <div class="flex items-center gap-1">
            <div class="w-2 h-2 rounded-full bg-green-600"></div>
            <span class="text-gray-600">Normal</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-2 h-2 rounded-full bg-red-600"></div>
            <span class="text-gray-600">Henkaten</span>
        </div>
    </div>


    {{-- MACHINE HENKATEN CARDS --}}
    <div class="border-t mt-4 pt-4 overflow-x-auto scrollbar-hide">
        <div class="flex justify-center gap-3 p-2">
            @forelse($machineHenkatens as $henkaten)
                <div class="flex-shrink-0 flex flex-col space-y-1 p-1.5 rounded-lg border-2 shadow-md cursor-pointer hover:bg-gray-100 transition"
                     style="width: 220px;"
                     onclick="showMachineHenkatenDetail(this)"
                     data-description-before="{{ $henkaten->description_before ?? 'N/A' }}"
                     data-description-after="{{ $henkaten->description_after ?? 'N/A' }}"
                     data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                     data-shift="{{ $henkaten->shift ?? 'N/A' }}"
                     data-line-area="{{ $henkaten->line_area ?? 'N/A' }}"
                     data-keterangan="{{ $henkaten->keterangan ?? 'N/A' }}"
                     data-machine="{{ $henkaten->machine ?? 'N/A' }}"
                     data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                     data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                     data-time-start="{{ $henkaten->time_start ?? '-' }}"
                     data-time-end="{{ $henkaten->time_end ?? '-' }}"
                     data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d M Y') : '-' }}"
                     data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d M Y') : 'Selanjutnya' }}"
                     data-lampiran="{{ $henkaten->lampiran ? asset($henkaten->lampiran) : '' }}">
                     
                    <div class="flex items-center justify-center space-x-1.5">
                        <div class="text-center">
                            <div class="text-[8px] font-bold">OLD JIG</div>
                            <div class="text-xl my-0.5">⚙️</div>
                            <p class="text-[7px] font-semibold">{{ $henkaten->description_before ?? 'N/A' }}</p>
                        </div>
                        <div class="text-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                        <div class="text-center">
                            <div class="text-[8px] font-bold text-red-600">NEW JIG</div>
                            <div class="text-xl my-0.5">⚙️</div>
                            <p class="text-[7px] font-semibold">{{ $henkaten->description_after ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                        <div class="bg-blue-400 text-center py-0.5 rounded">
                            <span class="text-[7px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? 'N/A' }}</span>
                        </div>
                        <div class="bg-blue-400 text-center py-0.5 rounded">
                            <span class="text-[7px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[7px] font-semibold">
                            ACTIVE: {{ $henkaten->effective_date->format('j/M/y') }} - {{ $henkaten->end_date ? $henkaten->end_date->format('j/M/y') : '...' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-xs text-gray-400 py-4 w-full">No Actived Machine Henkaten</div>
            @endforelse
        </div>
    </div>

    {{-- MACHINE MODAL --}}
    <div id="henkatenModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl"> 
            <div class="flex justify-between items-center border-b p-4 bg-pink-500">
                <h3 class="text-lg font-semibold text-gray-800">Detail Henkaten Machine</h3>
                <button id="modalCloseButton" class="text-gray-500 hover:text-gray-800 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Jig / Machine</h4>
                    <div class="flex items-center justify-around">
                        <div class="text-center">
                            <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                            <div class="text-3xl text-gray-500 mx-auto my-2">🏭</div>
                            <p id="modalDescriptionBefore" class="font-semibold text-sm mt-1">-</p>
                        </div>
                        <div class="text-2xl text-gray-400">→</div>
                        <div class="text-center">
                            <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                            <div class="text-3xl text-red-600 mx-auto my-2">🏭</div>
                            <p id="modalDescriptionAfter" class="font-semibold text-sm text-red-600 mt-1">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station</p>
                            <p id="modalStation" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Shift</p>
                            <p id="modalShift" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Line Area</p>
                            <p id="modalLineArea" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Keterangan</p>
                            <p id="modalKeterangan" class="font-semibold text-sm">-</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Machine</p>
                            <p id="modalMachine" class="font-semibold text-sm truncate">-</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="bg-orange-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number Start</p>
                        <p id="modalSerialStart" class="font-semibold text-sm">-</p>
                    </div>
                    <div class="bg-orange-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Serial Number End</p>
                        <p id="modalSerialEnd" class="font-semibold text-sm">-</p>
                    </div>
                    <div class="bg-orange-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time Start</p>
                        <p id="modalTimeStart" class="font-semibold text-sm">-</p>
                    </div>
                    <div class="bg-orange-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Time End</p>
                        <p id="modalTimeEnd" class="font-semibold text-sm">-</p>
                    </div>
                </div>

                <div class="flex justify-between items-center px-4 py-2 border-t mt-4 pt-4">
                    <div class="text-left">
                        <p class="text-xs text-gray-500">Mulai</p>
                        <p id="modalEffectiveDate" class="font-semibold text-lg">-</p>
                    </div>
                    <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Selesai</p>
                        <p id="modalEndDate" class="font-semibold text-lg">-</p>
                    </div>
                </div>

                <div id="modalLampiranSection" class="hidden pt-2">
                    <a id="modalLampiranLink" href="#" target="_blank"
                        class="block bg-pink-600 hover:bg-orange-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                        Lihat Lampiran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
