
<x-app-layout>
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

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div class="w-full h-screen flex flex-col px-3 py-1">
        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b pb-1 mb-1 h-[8vh]">
            {{-- Kolom Kiri: Logo --}}
            <div class="w-1/3 flex items-center pl-20">
                <img src="{{ asset('assets/images/AVI.png') }}" alt="Logo AVI" class="h-10 w-auto" />
            </div>

       <div class="w-1/3 text-center">
    <form action="{{ url()->current() }}" method="GET" class="flex justify-center">
        
        <select name="line_area" 
                onchange="this.form.submit()"
                class="text-base font-bold border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            
            @php
                $hasFalLine = false; // Variabel bantu untuk melacak
            @endphp

            @foreach($lineAreas as $line)
              
                @if(Illuminate\Support\Str::startsWith($line, 'FA L'))
                    
                    @php $hasFalLine = true; @endphp {{-- Tandai bahwa kita menemukan setidaknya satu line FA L --}}

                    <option value="{{ $line }}" {{ $selectedLineArea == $line ? 'selected' : '' }}>
                        HENKATEN {{ $line }}
                    </option>
                @endif
            @endforeach
            
            @if(!$hasFalLine)
                <option disabled {{ !$selectedLineArea ? 'selected' : '' }}>
                    Tidak ada Line FA L
                </option>
            @endif

        </select>
    </form>


    {{-- Ini tetap sama dari kode Anda --}}
    <p class="text-[10px] text-gray-600" id="current-date"></p>
</div>

            {{-- Time & Shift --}}
            <div class="w-1/3 text-right">
                <p class="font-mono text-sm" id="current-time"></p>
                <p class="text-xs" id="current-shift"></p>
            </div>
        </div>

        {{-- 4 SECTION GRID --}}
        <div class="grid grid-cols-2 gap-3 h-[92vh]">
            {{-- Your content sections here --}}
        
 
 
{{-- MAN POWER --}}
@include('dashboard.partials._man_power_line')
      
{{-- METHOD  --}}
@include('dashboard.partials._method_line')

{{-- MACHINE --}}
@include('dashboard.partials._machine_line')

{{-- MATERIAL --}}
@include('dashboard.partials._material_line')

            </div>
            </div>

    

{{-- PNG Auto-Slideshow --}}
@include('dashboard.partials._png_slideshow', ['role' => 'leader_fa'])

@push('scripts')
<script>
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', dateOptions);
        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');
        const hour = now.getHours();
        let shift = (hour >= 7 && hour < 19) ? "Shift 2" : "Shift 1";
        document.getElementById("current-shift").textContent = shift;
    }

    function setGrup(grup) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch("{{ route('dashboard.setGrup') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ grup: grup })
        })
        .then(response => response.ok ? response.json() : Promise.reject())
        .then(data => { if (data.status === 'success') location.reload(); })
        .catch(error => console.error('Error:', error));
    }

    function showHenkatenDetail(henkatenId) {
        const card = document.querySelector(`[data-henkaten-id="${henkatenId}"]`);
        const modal = document.getElementById('henkatenDetailModal');
        if (!card || !modal) return;
        const data = card.dataset;
        modal.querySelector('#modalNamaBefore').textContent = data.nama || '-';
        modal.querySelector('#modalNamaAfter').textContent = data.namaAfter || '-';
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalKeterangan').textContent = data.keterangan || '-';
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
        modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
        modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';
        const section = modal.querySelector('#modalLampiranSection');
        const link = modal.querySelector('#modalLampiranLink');
        if (data.lampiran) { section.classList.remove('hidden'); link.href = data.lampiran; } else { section.classList.add('hidden'); }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeHenkatenModal() {
        document.getElementById('henkatenDetailModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function showMethodHenkatenDetail(henkatenId) {
        const card = document.querySelector(`.method-card[data-henkaten-id="${henkatenId}"]`);
        const modal = document.getElementById('methodHenkatenDetailModal');
        if (!card || !modal) return;
        const data = card.dataset;
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
        modal.querySelector('#modalKeteranganBefore').textContent = data.keterangan || '-';
        modal.querySelector('#modalKeteranganAfter').textContent = data.keteranganAfter || '-';
        modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
        modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';
        const section = modal.querySelector('#modalLampiranSection');
        const link = modal.querySelector('#modalLampiranLink');
        if (data.lampiran) { link.href = data.lampiran; section.classList.remove('hidden'); } else { section.classList.add('hidden'); }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMethodHenkatenModal() {
        const modal = document.getElementById('methodHenkatenDetailModal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function showMaterialHenkatenDetail(element) {
        const modal = document.getElementById('materialHenkatenDetailModal');
        if (!modal) return;
        modal.querySelector('#modalMaterialBefore').textContent = element.getAttribute('data-nama');
        modal.querySelector('#modalMaterialAfter').textContent = element.getAttribute('data-nama-after');
        modal.querySelector('#modalStation').textContent = element.getAttribute('data-station');
        modal.querySelector('#modalShift').textContent = element.getAttribute('data-shift');
        modal.querySelector('#modalLineArea').textContent = element.getAttribute('data-line-area');
        modal.querySelector('#modalKeterangan').textContent = element.getAttribute('data-keterangan');
        modal.querySelector('#modalSerialStart').textContent = element.getAttribute('data-serial-number-start');
        modal.querySelector('#modalSerialEnd').textContent = element.getAttribute('data-serial-number-end');
        modal.querySelector('#modalTimeStart').textContent = element.getAttribute('data-time-start');
        modal.querySelector('#modalTimeEnd').textContent = element.getAttribute('data-time-end');
        modal.querySelector('#modalEffectiveDate').textContent = element.getAttribute('data-effective-date');
        modal.querySelector('#modalEndDate').textContent = element.getAttribute('data-end-date');
        modal.querySelector('#modalMaterial').textContent = element.getAttribute('data-material');
        const section = modal.querySelector('#modalLampiranSection');
        const link = modal.querySelector('#modalLampiranLink');
        if (element.getAttribute('data-lampiran')) { section.classList.remove('hidden'); link.href = element.getAttribute('data-lampiran'); } else { section.classList.add('hidden'); }
        modal.classList.remove('hidden');
    }

    function closeMaterialHenkatenModal() {
        document.getElementById('materialHenkatenDetailModal').classList.add('hidden');
    }

    function showMachineHenkatenDetail(element) {
        const modal = document.getElementById('henkatenModal');
        if (!modal) return;
        const data = element.dataset;
        modal.querySelector('#modalDescriptionBefore').textContent = data.descriptionBefore || '-';
        modal.querySelector('#modalDescriptionAfter').textContent = data.descriptionAfter || '-';
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalKeterangan').textContent = data.keterangan || '-';
        modal.querySelector('#modalMachine').textContent = data.machine || '-';
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';
        modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
        modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';
        const section = modal.querySelector('#modalLampiranSection');
        const link = modal.querySelector('#modalLampiranLink');
        if (data.lampiran) { link.href = data.lampiran; section.classList.remove('hidden'); } else { section.classList.add('hidden'); link.href = '#'; }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMachineHenkatenModal() {
        const modal = document.getElementById('henkatenModal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeHenkatenModal?.(); closeMethodHenkatenModal?.(); closeMaterialHenkatenModal?.(); closeMachineHenkatenModal?.();
        }
    });

    setInterval(updateDateTime, 1000);
    updateDateTime();

    document.addEventListener('DOMContentLoaded', function() {
        const manPowerModal = document.getElementById('henkatenDetailModal');
        if (manPowerModal) manPowerModal.addEventListener('click', function(e) { if (e.target === this) closeHenkatenModal(); });
        
        const methodModal = document.getElementById('methodHenkatenDetailModal');
        if (methodModal) methodModal.addEventListener('click', function(e) { if (e.target === this) closeMethodHenkatenModal(); });

        const materialModal = document.getElementById('materialHenkatenDetailModal');
        if (materialModal) materialModal.addEventListener('click', function(e) { if (e.target === this) closeMaterialHenkatenModal(); });
        
        const machineModal = document.getElementById('henkatenModal');
        const machineCloseButton = machineModal?.querySelector('#modalCloseButton');
        if (machineModal && machineCloseButton) {
            machineCloseButton.addEventListener('click', closeMachineHenkatenModal);
            machineModal.addEventListener('click', function(e) { if (e.target === this) closeMachineHenkatenModal(); });
        }
    }); 
</script>
@endpush

</x-app-layout>