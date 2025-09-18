@extends('layouts.app')

@section('content')

<style>
    .machine-status {
        width: 60px;
        height: 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px solid #333;
        margin: 2px;
        font-size: 10px;
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
        width: 40px;
        height: 40px;
        background: linear-gradient(45deg, #e5e7eb, #9ca3af);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        color: #374151;
        border: 2px solid #6b7280;
    }
    .arrow {
        font-size: 24px;
        font-weight: bold;
        color: #374151;
        margin: 0 15px;
    }
    .status-badge {
        background-color: #f59e0b;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        margin-top: 10px;
        display: inline-block;
    }
    .document-icon {
        width: 60px;
        height: 60px;
        border: 2px solid #6b7280;
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #6b7280;
        flex-direction: column;
        border-radius: 4px;
    }
</style>

<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex justify-between items-center border-b pb-2 mb-6">
        <div class="flex items-center space-x-3">
            
            <div>
                <h1 class="text-2xl font-bold">HENKATEN FA</h1>
                <p class="text-sm text-gray-600" id="current-date"></p>
            </div>
        </div>
        <div class="text-right">
            <p class="font-mono text-lg" id="current-time"></p>
            <p class="text-sm">SHIFT 2</p>
        </div>
    </div>

    {{-- 4 SECTION GRID --}}
    <div class="grid grid-cols-2 gap-6">

       {{-- MAN POWER --}}
<div class="bg-white shadow rounded p-4">
    <h2 class="text-lg font-semibold mb-3 border-b pb-1">MAN POWER</h2>
    
    {{-- Top Row - All Stations Grid --}}
    <div class="grid grid-cols-11 gap-2 mb-6">
        @php
            $currentShift = 'Shift A'; // This should come from your controller
            $groupedManPower = $manPower->groupBy('station_id');
        @endphp

        @for($stationId = 1; $stationId <= 11; $stationId++)
            @php
                $stationWorkers = $groupedManPower->get($stationId, collect());
                $currentWorker = $stationWorkers->where('shift', $currentShift)->first();
                $displayName = $currentWorker ? $currentWorker->nama : 'No Worker';
                $isAbsent = in_array($stationId, [4, 8]); // You can modify this logic based on actual absence data
            @endphp
            
            <div class="text-center">
                {{-- Profile Icon --}}
                <div class="relative mx-auto mb-2">
                    <div class="w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white text-lg font-bold">
                        👤
                    </div>
                    {{-- Status Dot --}}
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full 
                        {{ $isAbsent ? 'bg-red-500' : 'bg-green-500' }} 
                        border-2 border-white"></div>
                </div>
                
                {{-- Name --}}
                <p class="text-xs font-medium truncate">{{ $displayName }}</p>
                
                {{-- Status Badge --}}
                <div class="mt-1">
                    <span class="px-2 py-1 text-xs rounded 
                        {{ $isAbsent ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                        {{ $isAbsent ? 'ABSENT' : 'NORMAL' }}
                    </span>
                </div>
            </div>
        @endfor
    </div>

    {{-- Bottom Section - Shift Changes --}}
    <div class="border-t pt-4">
        <div class="grid grid-cols-2 gap-6">
            {{-- First Shift Change - Station 4 --}}
            @php
                $station4Workers = $groupedManPower->get(4, collect());
                $shiftAWorker4 = $station4Workers->where('shift', 'Shift A')->first();
                $shiftBWorker4 = $station4Workers->where('shift', 'Shift B')->first();
            @endphp
            <div class="flex items-center justify-center space-x-4">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                        👤
                    </div>
                    <p class="font-semibold">{{ $shiftAWorker4 ? $shiftAWorker4->nama : 'No Worker' }}</p>
                    <div class="w-4 h-4 rounded-full bg-green-500 mx-auto mt-1"></div>
                </div>
                
                <div class="text-2xl text-gray-400">→</div>
                
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                        👤
                    </div>
                    <p class="font-semibold">{{ $shiftBWorker4 ? $shiftBWorker4->nama : 'No Worker' }}</p>
                    <div class="w-4 h-4 rounded-full bg-green-500 mx-auto mt-1"></div>
                </div>
            </div>

            {{-- Second Shift Change - Station 7 --}}
            @php
                $station7Workers = $groupedManPower->get(7, collect());
                $shiftAWorker7 = $station7Workers->where('shift', 'Shift A')->first();
                $shiftBWorker7 = $station7Workers->where('shift', 'Shift B')->first();
            @endphp
            <div class="flex items-center justify-center space-x-4">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                        👤
                    </div>
                    <p class="font-semibold">{{ $shiftAWorker7 ? $shiftAWorker7->nama : 'No Worker' }}</p>
                    <div class="w-4 h-4 rounded-full bg-green-500 mx-auto mt-1"></div>
                </div>
                
                <div class="text-2xl text-gray-400">→</div>
                
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                        👤
                    </div>
                    <p class="font-semibold">{{ $shiftBWorker7 ? $shiftBWorker7->nama : 'No Worker' }}</p>
                    <div class="w-4 h-4 rounded-full bg-green-500 mx-auto mt-1"></div>
                </div>
            </div>
        </div>

        {{-- Active Period Tags --}}
        <div class="grid grid-cols-2 gap-6 mt-4">
            <div class="text-center">
                <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    ACTIVE: 9/SEP/25 - 12/Sep/25
                </span>
            </div>
            <div class="text-center">
                <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    ACTIVE: 10/SEP/25
                </span>
            </div>
        </div>
    </div>
</div>
        {{-- METHOD --}}
        <div class="bg-white shadow rounded p-4">
            <h2 class="text-lg font-semibold mb-3 border-b pb-1">METHOD</h2>
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-2 py-1 text-left">Station</th>
                        <th class="border px-2 py-1 text-center">Keterangan</th>
                        <th class="border px-2 py-1 text-center">Lampiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($methods as $m)
                        <tr>
                            <td class="border px-2 py-1">{{ $m->station_id ?? '-' }}</td>
                            <td class="border px-2 py-1 text-center">{{ $m->keterangan ?? '-' }}</td>
                            <td class="border px-2 py-1 text-center">{{ $m->foto_path ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

 {{-- MACHINE --}}
<div class="bg-white shadow rounded p-4">
    {{-- Header --}}
    <div class="text-black text-center py-2">
        <h1 class="text-xl font-bold">MACHINE</h1>
    </div>

    {{-- Machine Status Bar with Green Background --}}
    <div class="bg-green-500 p-2">
    <div class="flex justify-center items-center space-x-4">
        @foreach ($machines as $mc)
            @php
                $isHenkaten = ($mc->keterangan === 'HENKATEN');
            @endphp
            <div class="machine-status {{ $isHenkaten ? 'machine-inactive' : 'machine-active' }}" 
                 onclick="toggleMachine(this)">

                  {{-- Station ID --}}
                <div class="station-id text-xs font-bold text-black mb-1">
                    ST {{ $mc->station_id }}
                </div>
                 
                {{-- Ikon mesin --}}
                <div style="font-size: 24px;">🏭</div>
                
                {{-- Status Text --}}
                <div class="status-text text-[10px] font-bold mt-1 px-2 py-0.5 rounded-full 
    {{ $isHenkaten ? 'bg-red-600 text-white' : 'bg-green-700 text-white' }}">
    {{ $isHenkaten ? 'HENKATEN' : 'NORMAL' }}
</div>
            </div>
        @endforeach
    </div>
</div>


    {{-- Bottom Section - Jig Change (PASTIKAN ADA DI SINI) --}}
    <div class="flex p-4 bg-gray-100 mt-4">
        <div class="flex-1">
            <div class="flex items-center justify-center">
                {{-- Old Jig --}}
                <div class="text-center">
                    <div class="text-sm font-bold mb-2">Old jig</div>
                    <div class="jig-icon">
                        <span style="font-size: 16px;">⚙️</span>
                    </div>
                </div>

                {{-- Arrow --}}
                <div class="arrow mx-6">→</div>

                {{-- New Jig --}}
                <div class="text-center">
                    <div class="text-sm font-bold mb-2">New jig</div>
                    <div class="jig-icon">
                        <span style="font-size: 16px;">⚙️</span>
                    </div>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="text-center mt-3">
                <div class="status-badge">
                    ACTIVE: 10/SEP/25
                </div>
            </div>
        </div>
    </div>
</div>



{{-- MATERIAL --}}
<div class="bg-white">
    {{-- Header --}}
   
        <h1 class="text-xl font-bold text-center">MATERIAL</h1>
    

    {{-- Material Status Bar with Green Background --}}
    <div class="bg-green-500 p-2">
        <div class="flex justify-center items-center space-x-2">
            {{-- Material status indicators --}}
            @for($i = 1; $i <= 10; $i++)
                @php
                    // Simulate one material having issue (red status)
                    $hasIssue = ($i == 3);
                @endphp
                <div class="w-12 h-12 {{ $hasIssue ? 'bg-red-500' : 'bg-green-600' }} border-2 border-white flex items-center justify-center text-white font-bold text-xs">
                    MAT
                </div>
            @endfor
        </div>
        
        {{-- Status Labels --}}
        <div class="flex justify-center items-center space-x-2 mt-1">
            @for($i = 1; $i <= 10; $i++)
                @php
                    $hasIssue = ($i == 3);
                @endphp
                <div class="text-center w-12">
                    <div class="text-xs font-bold text-white">
                        {{ $hasIssue ? 'HENKATEN' : 'NORMAL' }}
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Bottom Section - Parts Information --}}
    <div class="flex p-4 bg-gray-100">
        {{-- Left Side - Current Part --}}
        <div class="flex-1 pr-4">
            <div class="bg-white border-2 border-gray-300 p-3 rounded">
                <h3 class="font-bold text-sm mb-2 text-center">CURRENT PART</h3>
                <div class="space-y-1">
                    <div>
                        <span class="font-bold text-xs">PT NUMBER:</span>
                        <span class="text-xs">{{ $currentPart->number ?? 'VPGZKF-19N551-AA' }}</span>
                    </div>
                    <div>
                        <span class="font-bold text-xs">DESCRIPTION:</span>
                        <span class="text-xs">{{ $currentPart->description ?? 'FLTR-VEN AIR (NITTO)' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - New Part --}}
        <div class="flex-1 pl-4">
            <div class="bg-white border-2 border-gray-300 p-3 rounded">
                <h3 class="font-bold text-sm mb-2 text-center text-red-600">NEW PARTKT</h3>
                <div class="space-y-1">
                    <div>
                        <span class="font-bold text-xs text-red-600">NUMBER:</span>
                        <span class="text-xs text-red-600">{{ $newPart->number ?? 'VPGZKF-19N551-AB' }}</span>
                    </div>
                    <div>
                        <span class="font-bold text-xs text-red-600">DESCRIPTION:</span>
                        <span class="text-xs text-red-600">{{ $newPart->description ?? 'FLTR-VEN AIR (BRADY)' }}</span>
                    </div>
                </div>
            </div>
            
            
            
   


{{-- Real-time Clock Script --}}
{{-- Scripts --}}
<script>
    // Real-time Clock
    function updateDateTime() {
        const now = new Date();
        const options = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', options);
        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');
    }
    setInterval(updateDateTime, 1000);
    updateDateTime();

    function toggleMachine(element) {
    const statusText = element.querySelector('.status-text');
    if (element.classList.contains('machine-active')) {
        element.classList.remove('machine-active');
        element.classList.add('machine-inactive');
        statusText.innerText = 'HENKATEN';
        statusText.className = "status-text text-[10px] font-bold mt-1 px-2 py-0.5 rounded-full bg-red-600 text-white";
    } else {
        element.classList.remove('machine-inactive');
        element.classList.add('machine-active');
        statusText.innerText = 'NORMAL';
        statusText.className = "status-text text-[10px] font-bold mt-1 px-2 py-0.5 rounded-full bg-green-700 text-white";
    }
}

// Auto-update machine status (simulate real-time updates)
function updateMachineStatus() {
    const machines = document.querySelectorAll('.machine-status');
    machines.forEach((machine) => {
        if (Math.random() > 0.95) { // 5% chance to change status
            if (machine.classList.contains('machine-active')) {
                machine.classList.remove('machine-active');
                machine.classList.add('machine-inactive');
                machine.querySelector('.status-text').innerText = 'HENKATEN';
            } else {
                machine.classList.remove('machine-inactive');
                machine.classList.add('machine-active');
                machine.querySelector('.status-text').innerText = 'NORMAL';
            }
        }
    });
}

// Update machine status every 10 seconds
setInterval(updateMachineStatus, 10000);
</script>
@endsection
