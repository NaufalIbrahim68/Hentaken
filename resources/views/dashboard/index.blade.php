@extends('layouts.app')

@section('content')

    <style>
        .machine-status {
            width: 40px;
            height: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #333;
            margin: 1px;
            font-size: 7px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
        }

        .machine-active {
            background-color: white;
            color: #333;
            border-color: #333;
        }

        .machine-inactive {
            background-color: #ef4444;
            color: white;
            border-color: #333;
        }

        .jig-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #e5e7eb, #9ca3af);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
            color: #374151;
            border: 2px solid #6b7280;
        }

        .arrow {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin: 0 8px;
        }

        .status-badge {
            background-color: #f59e0b;
            color: white;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 7px;
            font-weight: bold;
            margin-top: 3px;
            display: inline-block;
        }

        .document-icon {
            width: 40px;
            height: 40px;
            border: 2px solid #6b7280;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #6b7280;
            flex-direction: column;
            border-radius: 4px;
        }

        .status-text {
            padding: 1px 3px;
            border-radius: 2px;
            font-weight: bold;
        }

        .status-henkaten {
            background-color: red;
            color: white;
        }

        .nav-button {
            background-color: #e5e7eb;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 12px;
        }

        .nav-button:hover {
            background-color: #d1d5db;
        }

        .material-status {
            width: 100%;
            height: 40px;
        }

        .compact-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            height: calc(100vh - 120px);
        }

        .section-compact {
            display: flex;
            flex-direction: column;
        }

        .section-compact .overflow-x-auto {
            flex: 1;
            min-height: 0;
        }

        /* Optimasi untuk profile icons */
        .profile-icon {
            width: 6px;
            height: 6px;
        }

        .status-dot {
            width: 2px;
            height: 2px;
        }
    </style>

 <div class="w-full h-screen flex flex-col px-3 py-1">
    {{-- HEADER - Diperkecil --}}
    <div class="flex items-center justify-between border-b pb-1 mb-1 h-[8vh]">
        {{-- Kolom Kiri --}}
         <div class="w-1/3"></div>

        {{-- Title & Date --}}
        <div class="w-1/3 text-center">
            <h1 class="text-base font-bold">HENKATEN FA LINE 5</h1>
            <p class="text-[10px] text-gray-600" id="current-date"></p>
        </div>

        {{-- Time & Shift --}}
        <div class="w-1/3 text-right">
            <p class="font-mono text-sm" id="current-time"></p>
            <p class="text-xs" id="current-shift"></p>
        </div>
    </div>

    {{-- 4 SECTION GRID - Diperkecil --}}
    <div class="grid grid-cols-2 gap-3 h-[92vh]">
        {{-- MAN POWER --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">MAN POWER</h2>

            {{-- Top Row - All Stations Grid - Diperkecil --}}
            <div class="flex-1 overflow">
                <div class="grid grid-cols-11 gap-2">
                    @php
                        $currentShift = 'Shift A';
                        $groupedManPower = $manPower->groupBy('station_id');
                    @endphp

                    @for($stationId = 1; $stationId <= 11; $stationId++)
                        @php
                            $stationWorkers = $groupedManPower->get($stationId, collect());
                            $currentWorker = $stationWorkers->where('shift', $currentShift)->first();
                            $displayName = $currentWorker ? $currentWorker->nama : 'No Worker';
                            $isAbsent = in_array($stationId, [4, 8]); 
                        @endphp

                        <div class="text-center">
                            {{-- Profile Icon - Diperkecil --}}
                            <div class="relative mx-auto mb-0.5 px-4">
                                <div class="w-5 h-5 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] font-bold">
                                    👤
                                </div>
                                {{-- Status Dot - Diperkecil --}}
                                <div class="absolute -bottom-0.5 w-2 h-2 rounded-full 
                                    {{ $isAbsent ? 'bg-red-500' : 'bg-green-500' }} 
                                    border border-white"></div>
                            </div>

                            {{-- Name - Diperkecil --}}
                            <p class="text-[8px] font-medium truncate">{{ $displayName }}</p>

                            {{-- Status Badge - Diperkecil --}}
                            <div class="mt-0.5">
                                <span class="px-0.5 py-0.5 text-[6px] rounded 
                                    {{ $isAbsent ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                                    {{ $isAbsent ? 'ABSENT' : 'NORMAL' }}
                                </span>
                            </div>
                        </div>
                    @endfor
                </div>

                {{-- Bottom Section - Shift Changes - Diperkecil --}}
                <div class="border-t pt-2 relative">
                    {{-- Tombol Navigasi di Kiri - Diperkecil --}}
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                        <button id="scrollLeftManPower" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Grid Shift Changes - Diperkecil --}}
                    <div class="mx-8">
                        <div class="grid grid-cols-2 gap-2">
                            {{-- First Shift Change - Station 4 - Diperkecil --}}
                            @php
                                $station4Workers = $groupedManPower->get(4, collect());
                                $shiftAWorker4 = $station4Workers->where('shift', 'Shift A')->first();
                                $shiftBWorker4 = $station4Workers->where('shift', 'Shift B')->first();
                            @endphp
                            <div class="flex items-center justify-center space-x-2 bg-gray-50 p-2 rounded-lg">
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                        👤
                                    </div>
                                    <p class="text-[8px] font-semibold">{{ $shiftAWorker4 ? $shiftAWorker4->nama : 'No Worker' }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                </div>
                                <div class="text-sm text-gray-400 font-bold">→</div>
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                        👤
                                    </div>
                                    <p class="text-[8px] font-semibold">{{ $shiftBWorker4 ? $shiftBWorker4->nama : 'No Worker' }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                </div>
                            </div>

                            {{-- Second Shift Change - Station 7 - Diperkecil --}}
                            @php
                                $station7Workers = $groupedManPower->get(7, collect());
                                $shiftAWorker7 = $station7Workers->where('shift', 'Shift A')->first();
                                $shiftBWorker7 = $station7Workers->where('shift', 'Shift B')->first();
                            @endphp
                            <div class="flex items-center justify-center space-x-2 bg-gray-50 p-1 rounded-lg">
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                        👤
                                    </div>
                                    <p class="text-[8px] font-semibold">{{ $shiftAWorker7 ? $shiftAWorker7->nama : 'No Worker' }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                </div>
                                <div class="text-sm text-gray-400 font-bold">→</div>
                                <div class="text-center">
                                    <div class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-[8px] mx-auto mb-0.5">
                                        👤
                                    </div>
                                    <p class="text-[8px] font-semibold">{{ $shiftBWorker7 ? $shiftBWorker7->nama : 'No Worker' }}</p>
                                    <div class="w-2 h-2 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Navigasi di Kanan - Diperkecil --}}
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                        <button id="scrollRightManPower" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- CURRENT PART & NEW PARTKT - Diperkecil --}}
                <div class="grid grid-cols-2 gap-1 mt-1">
                                   </div>

                {{-- SERIAL NUMBER & DATE - Diperkecil --}}
                                <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>


                {{-- Tanggal Aktif - Diperkecil --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
        </div>

        {{-- METHOD - Diperkecil --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">METHOD</h2>

            {{-- Table Wrapper Scroll - Diperkecil --}}
            <div class="overflow-auto flex-1">
                <table class="w-full border-collapse text-[10px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-0.5 py-0.5 text-left">Station</th>
                            <th class="border px-0.5 py-0.5 text-center">Keterangan</th>
                            <th class="border px-0.5 py-0.5 text-center">Lampiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($methods as $m)
                            <tr>
                                <td class="border px-0.5 py-0.5">{{ $m->station->station_name ?? '-' }}</td>
                                <td class="border px-0.5 py-0.5 text-center">{{ $m->keterangan ?? '-' }}</td>
                                <td class="border px-0.5 py-0.5 text-center">{{ $m->foto_path ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            
{{-- Pagination Compact --}}
<div class="mt-2 flex justify-end">
    {{ $methods->onEachSide(1)->links('vendor.pagination.compact') }}
</div>



            {{-- Bottom sections untuk Method - Diperkecil --}}
            <div class="mt-1">
                {{-- CURRENT PART & NEW PARTKT - Diperkecil --}}
                <div class="grid grid-cols-2 gap-1">
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5">CURRENT METHOD</h3>
                        <p class="text-[7px]"><span class="font-semibold">STATION :</span> STATION 5</p>
                        <p class="text-[7px]"><span class="font-semibold">METHOD :</span> INNERCASE ASSY</p>
                    </div>
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5 text-red-600">NEW METHOD</h3>
                        <p class="text-[7px]"><span class="font-semibold">STATION :</span> STATION 5</p>
                        <p class="text-[7px]"><span class="font-semibold">METHOD :</span> INNERCASE ASSY</p>
                    </div>
                </div>

                {{-- SERIAL NUMBER & DATE - Diperkecil --}}
                               <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>


                {{-- Tanggal Aktif - Diperkecil --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
        </div>

        {{-- MACHINE - Diperkecil --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">MACHINE</h2>

            {{-- Machine Status Bar with Navigation - Diperkecil --}}
            <div class="relative">
                {{-- Tombol Navigasi di Kiri - Diperkecil --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollLeftMachine" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>

                {{-- Machine Status Container - Diperkecil --}}
                <div class="bg-white p-2 mx-8">
                    <div class="flex justify-center items-center space-x-1">
                        @foreach ($machines as $mc)
                            @php
                                $isHenkaten = ($mc->keterangan === 'HENKATEN');
                            @endphp
                            <div class="machine-status {{ $isHenkaten ? 'machine-inactive' : 'machine-active' }}" onclick="toggleMachine(this)">
                                {{-- Station ID - Diperkecil --}}
                                <div class="station-id text-[8px] font-bold text-black mb-0.5">
                                    ST {{ $mc->station_id }}
                                </div>

                                {{-- Ikon mesin - Diperkecil --}}
                                <div style="font-size: 16px;">🏭</div>

                                {{-- Status Text - Diperkecil --}}
                                <div class="status-text text-[7px] font-bold mt-0.5 px-0.5 py-0.5 rounded-full 
                                    {{ $isHenkaten ? 'bg-red-600 text-white ' : 'bg-green-700 text-white' }}">
                                    {{ $isHenkaten ? 'HENKATEN' : 'NORMAL' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tombol Navigasi di Kanan - Diperkecil --}}
                <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollRightMachine" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Bottom Section - Jig Change - Diperkecil --}}
            <div class="flex p-2 bg-white-100 mt-2">
                <div class="flex-1">
                    <div class="flex items-center justify-center">
                        {{-- Old Jig - Diperkecil --}}
                        <div class="text-center">
                            <div class="text-[8px] font-bold mb-0.5">Old jig</div>
                            <div class="jig-icon">
                                <span style="font-size: 10px;">⚙️</span>
                            </div>
                        </div>

                        {{-- Arrow - Diperkecil --}}
                        <div class="arrow mx-4 text-lg font-bold text-blue-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>

                        {{-- New Jig - Diperkecil --}}
                        <div class="text-center">
                            <div class="text-[8px] font-bold mb-0.5">New jig</div>
                            <div class="jig-icon">
                                <span style="font-size: 10px;">⚙️</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom sections untuk Machine - Diperkecil --}}
            <div class="mt-1">
               

                {{-- SERIAL NUMBER & DATE - Diperkecil --}}
                                <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>


                {{-- Tanggal Aktif - Diperkecil --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
        </div>

        {{-- MATERIAL - Diperkecil --}}
        <div class="bg-white shadow rounded p-1 flex flex-col">
            <h2 class="text-xs font-semibold mb-0.5 text-center">MATERIAL</h2>

            <div class="relative flex-1">
                {{-- Tombol Navigasi di Kiri - Diperkecil --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollLeftMaterial" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>

                {{-- Material Table Container - Diperkecil --}}
                <div id="materialTableContainer" class="mx-8 overflow-hidden">
                    <table class="table-auto border-collapse border border-gray-300 w-full text-center text-[10px]">
                        <thead>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <th class="border border-gray-300 px-1 py-1 bg-green-600 text-white text-[8px] font-semibold">
                                        {{ $station['name'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <td class="border border-gray-300 px-1 py-1">
                                        <div class="material-status flex items-center justify-center bg-white text-gray-800 font-bold cursor-pointer" data-id="{{ $station['id'] }}">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($stationStatuses as $station)
                                    <td class="border border-gray-300 px-1 py-0.5 text-[8px] font-bold">
                                        <div class="status-text text-green-600">
                                            {{ $station['status'] }}
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Tombol Navigasi di Kanan - Diperkecil --}}
                <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                    <button id="scrollRightMaterial" class="w-6 h-6 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Bottom sections untuk Material - Diperkecil --}}
            <div class="mt-1">
                {{-- CURRENT PART & NEW PARTKT - Diperkecil --}}
                <div class="grid grid-cols-2 gap-1">
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5">CURRENT PART</h3>
                        <p class="text-[7px]"><span class="font-semibold">PART NUMBER :</span> VPGZKF-19N551-AA</p>
                        <p class="text-[7px]"><span class="font-semibold">DESC:</span> FLTR-VEN AIR (NITTO)</p>
                    </div>
                    <div class="bg-white shadow rounded p-1 text-center">
                        <h3 class="text-[9px] font-bold mb-0.5 text-red-600">NEW PART</h3>
                        <p class="text-[7px]"><span class="font-semibold">PART NUMBER :</span> VPGZKF-19N551-AB</p>
                        <p class="text-[7px]"><span class="font-semibold">DESC:</span> FLTR-VEN AIR (BRADY)</p>
                    </div>
                </div>

                {{-- SERIAL NUMBER & DATE - Diperkecil --}}
                <div class="grid grid-cols-2 gap-1 mt-1">
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number Start : K1ZVNA2018QX</span>
                    </div>
                    <div class="bg-blue-400 text-center py-0.5 rounded">
                        <span class="text-[8px] text-white font-medium">Serial Number End : K1ZVNA2020QX</span>
                    </div>
                </div>

                {{-- Tanggal Aktif - Diperkecil --}}
                <div class="mt-1 flex justify-center">
                    <div class="bg-orange-500 text-white px-1 py-0.5 rounded-full text-[8px] font-semibold">
                        ACTIVE: 9/SEP/25 - 12/SEP/25
                    </div>
                </div>
            </div>
        </div>
    </div>
 </div>




                
                <script>
                    // Real-time Clock
                    function updateDateTime() {
                        const now = new Date();

                        // Format tanggal
                        const options = { day: '2-digit', month: 'long', year: 'numeric' };
                        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', options);

                        // Format waktu
                        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');

                        // Tentukan shift
                        const hour = now.getHours();
                        let shift;

                        if (hour >= 7 && hour < 19) {
                            shift = "Shift 2"; // 07:00 - 18:59
                        } else {
                            shift = "Shift 1"; // 19:00 - 06:59
                        }

                        document.getElementById("current-shift").textContent = shift;
                    }

                    // Update tiap detik
                    setInterval(updateDateTime, 1000);
                    updateDateTime();


                    function toggleMachine(element) {
                        const statusText = element.querySelector('.status-text');
                        if (element.classList.contains('machine-active')) {
                            element.classList.remove('machine-active');
                            element.classList.add('machine-inactive');
                            statusText.innerText = 'HENKATEN';
                            statusText.className = "status-text text-[10px] font-bold mt-1 px-1 py-0.5 rounded-full bg-red-600 text-white";
                        } else {
                            element.classList.remove('machine-inactive');
                            element.classList.add('machine-active');
                            statusText.innerText = 'NORMAL';
                            statusText.className = "status-text text-[10px] font-bold mt-1 px-1 py-0.5 rounded-full bg-green-700 text-white";
                        }
                    }

                    // Auto-update machine status (simulate real-time updates)
                    function updateMachineStatus() {
                        const machines = document.querySelectorAll('.machine-status');
                        machines.forEach((machine) => {
                            if (Math.random() > 0.95) { // 5% chance to change status
                                const statusText = machine.querySelector('.status-text');

                                if (machine.classList.contains('machine-active')) {
                                    machine.classList.remove('machine-active');
                                    machine.classList.add('machine-inactive');
                                    statusText.innerText = 'HENKATEN';
                                    statusText.classList.add('status-henkaten');
                                } else {
                                    machine.classList.remove('machine-inactive');
                                    machine.classList.add('machine-active');
                                    statusText.innerText = 'NORMAL';
                                    statusText.classList.remove('status-henkaten');
                                }
                            }
                        });
                    }

                    // logic materials
                 document.addEventListener('DOMContentLoaded', () => {
    const materials = document.querySelectorAll('.material-status');

    materials.forEach((material) => {
        material.addEventListener('click', () => {
            const tdMaterial = material.closest('td'); // td untuk kotak
            const tdStatus = tdMaterial.parentElement.nextElementSibling?.children[tdMaterial.cellIndex]; // td baris bawah
            const statusText = tdStatus?.querySelector('.status-text');

            // === Jika NORMAL → ubah jadi HENKATEN ===
            if (!material.classList.contains('bg-red-500')) {
                // Ubah box
                material.classList.remove('bg-white');
                  material.classList.add('bg-red-500', 'text-white');
                material.innerText = 'HENKATEN';

                // Ubah status text
                if (statusText) {
                    statusText.innerText = 'HENKATEN';
                    statusText.classList.remove('text-white');
                    statusText.classList.add('text-white');
                }

                // Warnai seluruh cell merah
                tdMaterial.classList.add('bg-red-500', 'text-white');
                tdStatus.classList.add('bg-red-500', 'text-white');
            } 
            // === Jika sudah HENKATEN → balik NORMAL ===
            else {
                // Ubah box
                material.classList.remove('bg-red-500', 'text-white');
                material.classList.add('bg-white');
                material.innerText = '';

                // Ubah status text
                if (statusText) {
                    statusText.innerText = 'NORMAL';
                    statusText.classList.remove('text-white');
                    statusText.classList.add('text-green-500');
                }

                // Kembalikan cell ke default
                tdMaterial.classList.remove('bg-red-500', 'text-white');
                tdStatus.classList.remove('bg-red-500', 'text-white');
            }
        });
    });
});

                    document.addEventListener("DOMContentLoaded", function () {
                        function setupScroll(containerId, leftBtnId, rightBtnId) {
                            const container = document.getElementById(containerId);
                            const btnLeft = document.getElementById(leftBtnId);
                            const btnRight = document.getElementById(rightBtnId);

                            if (!container || !btnLeft || !btnRight) return;

                            btnLeft.addEventListener("click", () => {
                                container.scrollBy({ left: -200, behavior: "smooth" });
                            });

                            btnRight.addEventListener("click", () => {
                                container.scrollBy({ left: 200, behavior: "smooth" });
                            });
                        }

                        // sudah ada sebelumnya untuk Material
                        setupScroll("materialTableContainer", "scrollLeftMaterial", "scrollRightMaterial");

                        // tambahkan ini
                        setupScroll("manPowerTableContainer", "scrollLeftManPower", "scrollRightManPower");
                        setupScroll("machineTableContainer", "scrollLeftMachine", "scrollRightMachine");
                    });
                </script>
@endsection