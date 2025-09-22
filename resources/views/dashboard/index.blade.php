@extends('layouts.app')

@section('content')

    <style>
        .machine-status {
            width: 50px;
            height: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #333;
            margin: 1px;
            font-size: 8px;
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
            width: 30px;
            height: 30px;
            background: linear-gradient(45deg, #e5e7eb, #9ca3af);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            border: 2px solid #6b7280;
        }

        .arrow {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin: 0 10px;
        }

        .status-badge {
            background-color: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 5px;
            display: inline-block;
        }

        .document-icon {
            width: 50px;
            height: 50px;
            border: 2px solid #6b7280;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6b7280;
            flex-direction: column;
            border-radius: 4px;
        }

        .status-text {
            padding: 1px 4px;
            border-radius: 3px;
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
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        .nav-button:hover {
            background-color: #d1d5db;
        }

        .material-status {
            width: 100%;
            height: 50px;
        }

        .compact-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            height: calc(100vh - 200px);
        }

        .section-compact {
            display: flex;
            flex-direction: column;
        }

        .section-compact .overflow-x-auto {
            flex: 1;
            /* tabel fleksibel isi */
            min-height: 0;
            /* biar scroll aktif */
        }
    </style>

    <div class="container mx-auto px-4 py-4">
        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b pb-2 mb-4">

            {{-- Kolom Kiri (kosong untuk nge-balance biar judul tetap center) --}}
            <div class="w-1/3"></div>

            {{-- Title & Date di tengah --}}
            <div class="w-1/3 text-center">
                <h1 class="text-xl font-bold">HENKATEN FA</h1>
                <p class="text-xs text-gray-600" id="current-date"></p>
            </div>

            {{-- Time & Shift di kanan --}}
            <div class="w-1/3 text-right">
                <p class="font-mono text-base" id="current-time"></p>
                <p class="text-sm" id="current-shift"></p>
            </div>
        </div>
        {{-- WRAPPER FULL WIDTH TANPA PADDING --}}
        <div class="w-full">
            {{-- 4 SECTION GRID - COMPACT VERSION --}}
            <div class="grid grid-cols-2 gap-3 w-full">

                {{-- MAN POWER --}}
                <div class="bg-white shadow rounded p-4 section-compact overflow-auto">
                    <h2 class="text-lg font-semibold mb-3 border-b pb-2 text-center">MAN POWER</h2>

                    {{-- Top Row - All Stations Grid --}}
                    <div class="grid grid-cols-11 gap-2 mb-4">
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
                                {{-- Profile Icon --}}
                                <div class="relative mx-auto mb-1">
                                    <div
                                        class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                        👤
                                    </div>
                                    {{-- Status Dot --}}
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full 
                                        {{ $isAbsent ? 'bg-red-500' : 'bg-green-500' }} 
                                        border border-white"></div>
                                </div>

                                {{-- Name --}}
                                <p class="text-[10px] font-medium truncate">{{ $displayName }}</p>

                                {{-- Status Badge --}}
                                <div class="mt-0.5">
                                    <span class="px-1 py-0.5 text-[8px] rounded 
                                        {{ $isAbsent ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                                        {{ $isAbsent ? 'ABSENT' : 'NORMAL' }}
                                    </span>
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- Bottom Section - Shift Changes --}}
                    <div class="border-t pt-3 relative">
                        {{-- Tombol Navigasi di Kiri --}}
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollLeftManPower"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Grid Shift Changes --}}
                        <div class="mx-12">
                            <div class="grid grid-cols-2 gap-4">
                                {{-- First Shift Change - Station 4 --}}
                                @php
                                    $station4Workers = $groupedManPower->get(4, collect());
                                    $shiftAWorker4 = $station4Workers->where('shift', 'Shift A')->first();
                                    $shiftBWorker4 = $station4Workers->where('shift', 'Shift B')->first();
                                @endphp
                                <div class="flex items-center justify-center space-x-3 bg-gray-50 p-2 rounded-lg">
                                    <div class="text-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm mx-auto mb-1">
                                            👤
                                        </div>
                                        <p class="text-xs font-semibold">
                                            {{ $shiftAWorker4 ? $shiftAWorker4->nama : 'No Worker' }}</p>
                                        <div class="w-3 h-3 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                    </div>
                                    <div class="text-xl text-gray-400 font-bold">→</div>
                                    <div class="text-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm mx-auto mb-1">
                                            👤
                                        </div>
                                        <p class="text-xs font-semibold">
                                            {{ $shiftBWorker4 ? $shiftBWorker4->nama : 'No Worker' }}</p>
                                        <div class="w-3 h-3 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                    </div>
                                </div>

                                {{-- Second Shift Change - Station 7 --}}
                                @php
                                    $station7Workers = $groupedManPower->get(7, collect());
                                    $shiftAWorker7 = $station7Workers->where('shift', 'Shift A')->first();
                                    $shiftBWorker7 = $station7Workers->where('shift', 'Shift B')->first();
                                @endphp
                                <div class="flex items-center justify-center space-x-3 bg-gray-50 p-2 rounded-lg">
                                    <div class="text-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm mx-auto mb-1">
                                            👤
                                        </div>
                                        <p class="text-xs font-semibold">
                                            {{ $shiftAWorker7 ? $shiftAWorker7->nama : 'No Worker' }}</p>
                                        <div class="w-3 h-3 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                    </div>
                                    <div class="text-xl text-gray-400 font-bold">→</div>
                                    <div class="text-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm mx-auto mb-1">
                                            👤
                                        </div>
                                        <p class="text-xs font-semibold">
                                            {{ $shiftBWorker7 ? $shiftBWorker7->nama : 'No Worker' }}</p>
                                        <div class="w-3 h-3 rounded-full bg-green-500 mx-auto mt-0.5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Navigasi di Kanan --}}
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollRightManPower"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- METHOD --}}
                <div class="bg-white shadow rounded p-4 section-compact">
                    <h2 class="text-lg font-semibold mb-3 border-b pb-2 text-center">METHOD</h2>

                    {{-- Table Wrapper Scroll --}}
                    <div class="overflow-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-1 py-1 text-left">Station</th>
                                    <th class="border px-1 py-1 text-center">Keterangan</th>
                                    <th class="border px-1 py-1 text-center">Lampiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($methods as $m)
                                    <tr>
                                        <td class="border px-1 py-1">
                                            {{ $m->station->station_name ?? '-' }}
                                        </td>
                                        <td class="border px-1 py-1 text-center">{{ $m->keterangan ?? '-' }}</td>
                                        <td class="border px-1 py-1 text-center">{{ $m->foto_path ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-2 flex justify-center">
                    {{ $methods->links() }}
                    </div>
                </div>

                {{-- MACHINE --}}
                <div class="bg-white shadow rounded p-4 section-compact">
                    {{-- Header --}}
                    <div class="text-black text-center py-2">
                        <h2 class="text-lg font-bold">MACHINE</h2>
                    </div>

                    {{-- Machine Status Bar with Navigation --}}
                    <div class="relative">
                        {{-- Tombol Navigasi di Kiri --}}
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollLeftMachine"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        </div>
                        {{-- Machine Status Container --}}
                        <div class="bg-white p-3 mx-12">
                            <div class="flex justify-center items-center space-x-2">
                                @foreach ($machines as $mc)
                                            @php
                                                $isHenkaten = ($mc->keterangan === 'HENKATEN');
                                            @endphp
                                            <div class="machine-status {{ $isHenkaten ? 'machine-inactive' : 'machine-active' }}"
                                                onclick="toggleMachine(this)">

                                                {{-- Station ID --}}
                                                <div class="station-id text-[10px] font-bold text-black mb-1">
                                                    ST {{ $mc->station_id }}
                                                </div>

                                                {{-- Ikon mesin --}}
                                                <div style="font-size: 25px;">🏭</div>

                                                {{-- Status Text --}}
                                                <div class="status-text text-[10px] font-bold mt-1 px-1 py-0.5 rounded-full 
                                    {{ $isHenkaten ? 'bg-red-600 text-white ' : 'bg-green-700 text-white' }}">
                                                    {{ $isHenkaten ? 'HENKATEN' : 'NORMAL' }}
                                                </div>
                                            </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tombol Navigasi di Kanan --}}
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollRightMachine"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Bottom Section - Jig Change --}}
                    <div class="flex p-3 bg-white-100 mt-3">
                        <div class="flex-1">
                            <div class="flex items-center justify-center">
                                {{-- Old Jig --}}
                                <div class="text-center">
                                    <div class="text-xs font-bold mb-1">Old jig</div>
                                    <div class="jig-icon">
                                        <span style="font-size: 12px;">⚙️</span>
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <div class="arrow mx-6 text-2xl font-bold text-blue-500">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </div>

                                {{-- New Jig --}}
                                <div class="text-center">
                                    <div class="text-xs font-bold mb-1">New jig</div>
                                    <div class="jig-icon">
                                        <span style="font-size: 12px;">⚙️</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MATERIAL --}}
                <div class="bg-white shadow rounded-xl p-4 section-compact">
                    {{-- Header --}}
                    <div class="text-black text-center pb-3">
                        <h2 class="text-lg font-bold">MATERIAL</h2>
                    </div>

                    <div class="relative">
                        {{-- Tombol Navigasi di Kiri --}}
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollLeftMaterial"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Material Table Container --}}
                        <div id="materialTableContainer" class="mx-12 overflow-hidden">
                            <table class="table-auto border-collapse border border-gray-300 w-full text-center text-sm">
                                <thead>
                                    <tr>
                                        @foreach($stationStatuses as $station)
                                            <th
                                                class="border border-gray-300 px-2 py-2 bg-green-600 text-white text-xs font-semibold">
                                                {{ $station['name'] }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($stationStatuses as $station)
                                            <td class="border border-gray-300 px-2 py-2">
                                                <div class="material-status flex items-center justify-center 
                                                    bg-white text-gray-800 font-bold cursor-pointer"
                                                    data-id="{{ $station['id'] }}">
                                                    {{-- status HENKATEN bisa ditampilkan di sini --}}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach($stationStatuses as $station)
                                            <td class="border border-gray-300 px-2 py-1 text-[11px] font-bold">
                                                <div class="status-text text-green-600">
                                                    {{ $station['status'] }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Tombol Navigasi di Kanan --}}
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 z-10">
                            <button id="scrollRightMaterial"
                                class="w-9 h-9 flex items-center justify-center bg-white-500 hover:bg-blue-600 rounded-full text-black shadow transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>



                {{-- Scripts --}}
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
                                const statusText = material.closest('td').nextElementSibling?.querySelector('.status-text');

                                // Kondisi default putih → klik → jadi HENKATEN (merah)
                                if (!material.classList.contains('bg-red-500')) {
                                    material.classList.remove('bg-white'); // default putih
                                    material.classList.add('bg-red-500');
                                    material.innerText = 'HENKATEN';

                                    if (statusText) {
                                        statusText.innerText = 'HENKATEN';
                                        statusText.classList.remove('text-green-600');
                                        statusText.classList.add('text-red-600');
                                    }
                                }
                                // Kondisi HENKATEN (merah) → klik lagi → kembali ke putih
                                else {
                                    material.classList.remove('bg-red-500');
                                    material.classList.add('bg-white');
                                    material.innerText = '';

                                    if (statusText) {
                                        statusText.innerText = 'NORMAL';
                                        statusText.classList.remove('text-red-600');
                                        statusText.classList.add('text-green-600');
                                    }
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