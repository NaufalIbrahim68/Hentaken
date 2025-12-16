{{-- METHOD QC-PPIC --}}
<div class="bg-white shadow rounded p-4 flex flex-col">
    <h2 class="text-sm font-semibold mb-3 text-center">METHOD</h2>

    <div class="w-full overflow-hidden">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColorHeader = $isHenkaten ? 'bg-red-600' : 'bg-gray-50';
                            $textColorHeader = $isHenkaten ? 'text-white' : 'text-gray-700';
                        @endphp
                        <th class="border border-gray-300 px-1 py-1.5 text-[9px] font-bold {{ $bgColorHeader }} {{ $textColorHeader }} text-center">
                            <div class="break-words leading-tight">{{ $m->station->station_name ?? 'N/A' }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Row 1: Icon --}}
                <tr>
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColorCell }}">
                            <div class="flex justify-center items-center">
                                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
                
                {{-- Row 2: Method Name (QC-PPIC specific) --}}
                <tr>
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColorCell = $isHenkaten ? 'bg-red-600' : 'bg-white';
                            $textColor = $isHenkaten ? 'text-white' : 'text-gray-800';
                            $methodName = $m->methods_name ?? 'N/A';
                        @endphp
                        <td class="border border-gray-300 px-1 py-1 text-[9px] text-center {{ $bgColorCell }} {{ $textColor }} font-semibold">
                            <div class="leading-tight break-words">
                                {{ $methodName }}
                            </div>
                        </td>
                    @endforeach
                </tr>

                {{-- Row 3: Status --}}
                <tr>
                    @foreach ($methods as $m)
                        @php
                            $isHenkaten = strtoupper($m->status ?? '') === 'HENKATEN';
                            $bgColor = $isHenkaten ? 'bg-red-500' : 'bg-green-500';
                        @endphp
                        <td class="border border-gray-300 p-2 {{ $bgColor }}">
                            <div class="flex justify-center">
                                <div class="w-3 h-3 rounded-full {{ $isHenkaten ? 'bg-red-600' : 'bg-green-600' }}"></div>
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

    {{-- DETAIL HENKATEN METHOD --}}
    <div class="border-t mt-2 pt-2">
        <div class="flex items-center gap-1">
            <button id="scrollLeftMethod" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <div id="methodChangeContainer" class="flex-grow overflow-x-auto scrollbar-hide scroll-smooth">
                @php
                    $filteredMethodHenkatens = $activeMethodHenkatens->filter(function ($henkaten) {
                        return strtolower($henkaten->status) === 'pending';
                    });
                @endphp

                @if($filteredMethodHenkatens->isNotEmpty())
                    <div class="flex justify-center gap-3 min-w-full px-2">
                        @foreach($filteredMethodHenkatens as $henkaten)
                            @php
                                $startDate = strtoupper($henkaten->effective_date->format('j/M/y'));
                                $endDate = $henkaten->end_date ? strtoupper($henkaten->end_date->format('j/M/y')) : 'SELANJUTNYA';
                            @endphp

                            <div class="method-card flex-shrink-0 flex flex-col space-y-2 p-2 rounded-lg border border-gray-300 shadow-md cursor-pointer hover:bg-orange-50 transition transform hover:scale-[1.02]"
                                style="width: 240px;"
                                onclick="showMethodHenkatenDetail({{ $henkaten->id }})"
                                data-henkaten-id="{{ $henkaten->id }}"
                                data-keterangan="{{ $henkaten->keterangan }}"
                                data-keterangan-after="{{ $henkaten->keterangan_after }}"
                                data-station="{{ $henkaten->station->station_name ?? 'N/A' }}"
                                data-shift="{{ $henkaten->shift }}"
                                data-line-area="{{ $henkaten->line_area }}"
                                data-effective-date="{{ $henkaten->effective_date ? $henkaten->effective_date->format('d/m/Y') : '-' }}"
                                data-end-date="{{ $henkaten->end_date ? $henkaten->end_date->format('d/m/Y') : 'Selanjutnya' }}"
                                data-lampiran="{{ $henkaten->lampiran ? asset('storage/' . $henkaten->lampiran) : '' }}"
                                data-serial-number-start="{{ $henkaten->serial_number_start ?? '-' }}"
                                data-serial-number-end="{{ $henkaten->serial_number_end ?? '-' }}"
                                data-time-start="{{ $henkaten->time_start ? \Carbon\Carbon::parse($henkaten->time_start)->format('H:i') : '-' }}"
                                data-time-end="{{ $henkaten->time_end ? \Carbon\Carbon::parse($henkaten->time_end)->format('H:i') : '-' }}">
                                
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="text-center">
                                        <p class="text-[8px] font-semibold truncate w-20" title="{{ $henkaten->keterangan }}">{{ $henkaten->keterangan }}</p>
                                        <div class="w-2 h-2 rounded-full bg-red-500 mx-auto mt-0.5" title="Before"></div>
                                    </div>
                                    <div class="text-sm text-gray-400 font-bold">→</div>
                                    <div class="text-center">
                                        <p class="text-[8px] font-semibold truncate w-20" title="{{ $henkaten->keterangan_after }}">{{ $henkaten->keterangan_after }}</p>
                                        <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5" title="After"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-1">
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">Start: {{ $henkaten->serial_number_start ?? '-' }}</span>
                                    </div>
                                    <div class="bg-blue-400 text-center py-0.5 rounded">
                                        <span class="text-[8px] text-white font-medium">End: {{ $henkaten->serial_number_end ?? '-' }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-center">
                                    <div class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-[9px] font-semibold">
                                        {{ $startDate }} - {{ $endDate }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-xs text-gray-400 py-4">No Active  Method Henkaten</div>
                @endif
            </div>

            <button id="scrollRightMethod" class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-white hover:bg-gray-100 rounded-full text-black shadow transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- METHOD MODAL (sama dengan Line group) --}}
    <div id="methodHenkatenDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white tracking-wide">Detail Henkaten Metode</h3>
                <button onclick="closeMethodHenkatenModal()" class="text-white hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Perubahan Metode</h4>
                    <div class="flex items-center justify-around">
                        <div class="text-center">
                            <span class="text-xs bg-gray-300 text-gray-700 px-2 py-0.5 rounded">Sebelum</span>
                            <p id="modalKeteranganBefore" class="font-semibold text-sm mt-1"></p>
                        </div>
                        <div class="text-2xl text-gray-400">→</div>
                        <div class="text-center">
                            <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">Sesudah</span>
                            <p id="modalKeteranganAfter" class="font-semibold text-sm mt-1"></p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station</p>
                            <p id="modalStation" class="font-semibold text-sm"></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Shift</p>
                            <p id="modalShift" class="font-semibold text-sm"></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Line Area</p>
                            <p id="modalLineArea" class="font-semibold text-sm"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Serial Number Start</p>
                            <p id="modalSerialStart" class="font-semibold text-sm"></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Serial Number End</p>
                            <p id="modalSerialEnd" class="font-semibold text-sm"></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Time Start</p>
                            <p id="modalTimeStart" class="font-semibold text-sm"></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Time End</p>
                            <p id="modalTimeEnd" class="font-semibold text-sm"></p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center px-4 py-2">
                    <div class="text-left">
                        <p class="text-xs text-gray-500">Mulai</p>
                        <p id="modalEffectiveDate" class="font-semibold text-lg"></p>
                    </div>
                    <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Selesai</p>
                        <p id="modalEndDate" class="font-semibold text-lg"></p>
                    </div>
                </div>

                <div id="modalLampiranSection" class="hidden pt-2">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Lampiran</h4>
                    <a id="modalLampiranLink" href="#" target="_blank"
                        class="block bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg text-center transition">
                        Lihat Lampiran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
