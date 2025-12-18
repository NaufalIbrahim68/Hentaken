
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
    {{-- HEADER - . --}}
    <div class="flex items-center justify-between border-b pb-1 mb-1 h-[8vh]">
        {{-- Kolom Kiri --}}
         <div class="w-1/3"></div>

       {{-- Title, Area, dan Tanggal (Pusat, w-1/3) --}}
{{-- Class diubah menjadi flex flex-col items-center untuk centering vertikal dan horizontal --}}
<div class="w-1/3 flex flex-col items-center">
    
    {{-- BARIS 1: HENKATEN DAN LINE AREA (Samping-sampingan) --}}
    <div class="flex items-center space-x-2"> 
        
        {{-- JUDUL UTAMA --}}
        <h1 class="text-base font-bold">
            HENKATEN
        </h1>

        {{-- AREA/DELIVERY (Teks Biasa) --}}
        {{-- Kita perlu menentukan Area yang terpilih untuk ditampilkan sebagai teks --}}
        <p class="text-base font-bold">
            {{ strtoupper($selectedLineArea) }}
        </p>
        
    </div>
    
    {{-- BARIS 2: TANGGAL (Di bawah Judul dan Area) --}}
    {{-- Anda dapat menambahkan variabel tanggal yang sudah diinisialisasi di sini --}}
    <p class="text-[10px] text-gray-600 mt-1" id="current-date"></p>
    
</div>

        {{-- Time & Shift --}}
        <div class="w-1/3 text-right">
            <p class="font-mono text-sm" id="current-time"></p>
            <p class="text-xs" id="current-shift"></p>
        </div>
    </div>

    {{-- 4 SECTION GRID --}}
    <div class="grid grid-cols-2 gap-3 h-[92vh]">
        {{-- MAN POWER --}}
        @include('dashboard.partials._manpower_qc_ppic')

        {{-- METHOD --}}
        @include('dashboard.partials._method_qc_ppic')

        {{-- MACHINE --}}
        @include('dashboard.partials._machine_qc_ppic')

        {{-- MATERIAL --}}
    @include('dashboard.partials._material_qc_ppic')
    </div>
</div>


@push('scripts')
<script>
    
    /**
     * Update jam, tanggal, dan shift secara real-time.
     */
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById("current-date").textContent = now.toLocaleDateString('en-GB', dateOptions);
        document.getElementById("current-time").textContent = now.toLocaleTimeString('en-GB');

        const hour = now.getHours();
        let shift;
        if (hour >= 7 && hour < 19) {
            shift = "Shift 2"; // 07:00 - 18:59
        } else {
            shift = "Shift 1"; // 19:00 - 06:59
        }
        document.getElementById("current-shift").textContent = shift;
    }

    /**
     * Set Grup
     */
    function setGrup(grup) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch("{{ route('dashboard.setGrup') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                grup: grup
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                console.error('Failed to set group.');
                alert('Gagal mengganti grup. Silakan coba lagi.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan. Pastikan Anda memiliki koneksi dan CSRF token valid.');
        });
    }

    // =============================================
    // --- FUNGSI MODAL MAN POWER (DIPERBAIKI) ---
    // =============================================
    function showHenkatenDetail(henkatenId) {
        const card = document.querySelector(`[data-henkaten-id="${henkatenId}"]`);
        if (!card) {
            console.error('Card Man Power Henkaten tidak ditemukan!');
            return;
        }
        
        // --- PERBAIKAN: Dapatkan modal spesifik ---
        const modal = document.getElementById('henkatenDetailModal');
        if (!modal) {
            console.error('Modal Man Power (henkatenDetailModal) tidak ditemukan!');
            return;
        }
        
        const data = card.dataset; // Ambil semua data- attributes

        // --- PERBAIKAN: Gunakan modal.querySelector() ---
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
        
        // Mengurus Lampiran
        const lampiran = data.lampiran;
        const section = modal.querySelector('#modalLampiranSection'); // <-- Perbaikan
        const link = modal.querySelector('#modalLampiranLink'); // <-- Perbaikan
        
        if (lampiran) {
            section.classList.remove('hidden');
            link.href = lampiran;
        } else {
            section.classList.add('hidden');
        }

        // Tampilkan Modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeHenkatenModal() {
        document.getElementById('henkatenDetailModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // =============================================
    // --- FUNGSI MODAL METHOD ---
    // =============================================
    function showMethodHenkatenDetail(henkatenId) {
        const card = document.querySelector(`.method-card[data-henkaten-id="${henkatenId}"]`);
        if (!card) {
            console.error('Elemen card Henkaten Metode tidak ditemukan untuk id:', henkatenId);
            return;
        }
        
        const modal = document.getElementById('methodHenkatenDetailModal');
        if (!modal) {
            console.error('Elemen modal Henkaten Metode tidak ditemukan');
            return;
        }

        const data = card.dataset; 

        // (Kode ini sudah benar menggunakan modal.querySelector())
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
        
        const lampiranSection = modal.querySelector('#modalLampiranSection');
        const lampiranLink = modal.querySelector('#modalLampiranLink');
        
        if (data.lampiran) {
            lampiranLink.href = data.lampiran;
            lampiranSection.classList.remove('hidden');
        } else {
            lampiranSection.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMethodHenkatenModal() {
        const modal = document.getElementById('methodHenkatenDetailModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = 'auto';
    }

    // =============================================
    // --- FUNGSI MODAL MATERIAL ---
    // =============================================
    function showMaterialHenkatenDetail(element) {
        const modal = document.getElementById('materialHenkatenDetailModal');
        if (!modal) {
            console.error('Modal Material tidak ditemukan');
            return;
        }

        // (Kode ini sudah benar menggunakan modal.querySelector())
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

        const lampiran = element.getAttribute('data-lampiran');
        const lampiranSection = modal.querySelector('#modalLampiranSection');
        const lampiranLink = modal.querySelector('#modalLampiranLink');
        if (lampiran) {
            lampiranSection.classList.remove('hidden');
            lampiranLink.href = lampiran;
        } else {
            lampiranSection.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeMaterialHenkatenModal() {
        document.getElementById('materialHenkatenDetailModal').classList.add('hidden');
    }

    // =============================================
    // --- FUNGSI MODAL MACHINE (DI-UPDATE TOTAL) ---
    // =============================================
    function showMachineHenkatenDetail(element) {
        // 1. Dapatkan modal-nya
        const modal = document.getElementById('henkatenModal');
        if (!modal) {
            console.error('Modal Machine (henkatenModal) tidak ditemukan!');
            return;
        }

        // 2. Ambil semua data dari atribut data-*
        const data = element.dataset;

        // 3. Isi data ke modal menggunakan modal.querySelector()
        
        // "Perubahan" section
        modal.querySelector('#modalDescriptionBefore').textContent = data.descriptionBefore || '-';
        modal.querySelector('#modalDescriptionAfter').textContent = data.descriptionAfter || '-';

        // Grid 1
        modal.querySelector('#modalStation').textContent = data.station || '-';
        modal.querySelector('#modalShift').textContent = data.shift || '-';
        modal.querySelector('#modalLineArea').textContent = data.lineArea || '-';
        modal.querySelector('#modalKeterangan').textContent = data.keterangan || '-';
        modal.querySelector('#modalMachine').textContent = data.machine || '-'; // ID baru

        // Grid 2
        modal.querySelector('#modalSerialStart').textContent = data.serialNumberStart || '-';
        modal.querySelector('#modalSerialEnd').textContent = data.serialNumberEnd || '-';
        modal.querySelector('#modalTimeStart').textContent = data.timeStart || '-';
        modal.querySelector('#modalTimeEnd').textContent = data.timeEnd || '-';

        // Periode
        modal.querySelector('#modalEffectiveDate').textContent = data.effectiveDate || '-';
        modal.querySelector('#modalEndDate').textContent = data.endDate || 'Selanjutnya';

        // 4. Lampiran
        const lampiranSection = modal.querySelector('#modalLampiranSection');
        const lampiranLink = modal.querySelector('#modalLampiranLink');
        
        if (data.lampiran) {
            lampiranLink.href = data.lampiran;
            lampiranSection.classList.remove('hidden');
        } else {
            lampiranSection.classList.add('hidden');
            lampiranLink.href = '#'; // Reset href
        }

        // 5. Tampilkan modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Fungsi untuk menutup modal MACHINE
    function closeMachineHenkatenModal() {
        const modal = document.getElementById('henkatenModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = 'auto'; // Kembalikan scroll body
    }


    // ==================================================
    // ESC UNTUK MENUTUP SEMUA MODAL
    // ==================================================
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeHenkatenModal?.();
            closeMethodHenkatenModal?.();
            closeMaterialHenkatenModal?.();
            closeMachineHenkatenModal?.(); // <-- Pastikan ini ada
        }
    });

    // ==================================================
    // FUNGSI SCROLL UNTUK CARD HENKATEN (GLOBAL)
    // ==================================================
    function scrollMaterialHenkaten(direction) {
        const container = document.getElementById('materialHenkatenContainer');
        if (!container) return; 
        const scrollAmount = 240; 
        
        if (direction === 'left') {
            container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }

    
    // ==================================================
    // INISIALISASI SAAT HALAMAN DIMUAT (DOMContentLoaded)
    // ==================================================

    // Jalankan jam
    setInterval(updateDateTime, 1000);
    updateDateTime(); // Panggil sekali saat muat

    document.addEventListener('DOMContentLoaded', function() {

        /**
         * Fungsi scroll horizontal TERKONSOLIDASI (Untuk tabel-tabel).
         */
        function setupHorizontalScroll(containerId, leftBtnId, rightBtnId) {
            const scrollContainer = document.getElementById(containerId);
            const scrollLeftBtn = document.getElementById(leftBtnId);
            const scrollRightBtn = document.getElementById(rightBtnId);
            
            if (!scrollContainer || !scrollLeftBtn || !scrollRightBtn) {
                return;
            }
            const scrollAmount = 250; 
            scrollLeftBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', function() {
                scrollContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
            function updateButtons() {
                const isAtStart = scrollContainer.scrollLeft <= 0;
                const isAtEnd = scrollContainer.scrollLeft + scrollContainer.clientWidth >= scrollContainer.scrollWidth - 1;
                scrollLeftBtn.style.visibility = isAtStart ? 'hidden' : 'visible';
                scrollRightBtn.style.visibility = isAtEnd ? 'hidden' : 'visible';
            }
            scrollContainer.addEventListener('scroll', updateButtons);
            updateButtons(); 
            scrollContainer.scrollLeft = scrollContainer.scrollWidth;
        }

        // Inisialisasi SEMUA bagian scroll tabel
        setupHorizontalScroll('materialTableContainer', 'scrollLeftMaterial', 'scrollRightMaterial');
        setupHorizontalScroll('manPowerTableContainer', 'scrollLeftManPower', 'scrollRightManPower');
        setupHorizontalScroll('machineTableContainer', 'scrollLeftMachine', 'scrollRightMachine');
        setupHorizontalScroll('shiftChangeContainer', 'scrollLeftShift', 'scrollRightShift');
        setupHorizontalScroll('methodChangeContainer', 'scrollLeftMethod', 'scrollRightMethod');
        setupHorizontalScroll('methodScrollContainer', 'scrollLeftMethodIcon', 'scrollRightMethodIcon');


        // ... (Fungsi scroll card & method icon Anda yang lain) ...
        const container = document.getElementById('materialHenkatenContainer');
        const leftBtn = document.getElementById('scrollLeftBtnMaterial');
        const rightBtn = document.getElementById('scrollRightBtnMaterial');

        if (container && leftBtn && rightBtn) {
            function checkScrollButtons() {
                const isAtStart = container.scrollLeft <= 0;
                const isAtEnd = container.scrollLeft >= (container.scrollWidth - container.clientWidth - 1); 
                leftBtn.style.display = isAtStart ? 'none' : 'block';
                rightBtn.style.display = isAtEnd ? 'none' : 'block';
            }
            container.addEventListener('scroll', checkScrollButtons);
            setTimeout(checkScrollButtons, 100); 
        }

        const methodContainer = document.getElementById('methodScrollContainer');
        const methodLeftBtn = document.querySelector('[onclick="scrollMethodLeft()"]');
        const methodRightBtn = document.querySelector('[onclick="scrollMethodRight()"]');

        if (methodContainer && methodLeftBtn && methodRightBtn) {
            function checkMethodScrollButtons() {
                const isAtStart = methodContainer.scrollLeft <= 0;
                const isAtEnd = methodContainer.scrollLeft >= (methodContainer.scrollWidth - methodContainer.clientWidth - 1);
                methodLeftBtn.style.visibility = isAtStart ? 'hidden' : 'visible';
                methodRightBtn.style.visibility = isAtEnd ? 'hidden' : 'visible';
            }
            methodContainer.addEventListener('scroll', checkMethodScrollButtons);
            setTimeout(checkMethodScrollButtons, 100);
        }
        
        // ================================================================
        // INISIALISASI SEMUA EVENT LISTENER MODAL
        // ================================================================
        
        // --- Event Listener untuk Modal Man Power ---
        const manPowerModal = document.getElementById('henkatenDetailModal');
        if (manPowerModal) {
            manPowerModal.addEventListener('click', function(e) {
                if (e.target === this) closeHenkatenModal();
            });
            // (Asumsi tombol close 'X' punya onclick="closeHenkatenModal()")
        }
        
        // --- Event Listener untuk Modal Method ---
        const methodModal = document.getElementById('methodHenkatenDetailModal');
        if (methodModal) {
            methodModal.addEventListener('click', function(e) {
                if (e.target === this) closeMethodHenkatenModal();
            });
        }

        // --- Event Listener untuk Modal Material ---
        const materialModal = document.getElementById('materialHenkatenDetailModal');
        if (materialModal) {
            materialModal.addEventListener('click', function(e) {
                if (e.target === this) closeMaterialHenkatenModal();
            });
            // (Asumsi tombol close 'X' punya onclick="closeMaterialHenkatenModal()")
        }
        
        // --- Event Listener untuk Modal MACHINE ---
        const machineModal = document.getElementById('henkatenModal');
        const machineCloseButton = machineModal.querySelector('#modalCloseButton'); // <-- Cari di dalam modal

        if (machineModal && machineCloseButton) {
            // Listener untuk tombol close 'X'
            machineCloseButton.addEventListener('click', closeMachineHenkatenModal);

            // Listener untuk klik di luar area modal
            machineModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeMachineHenkatenModal();
                }
            });
        }
        
    }); 
</script>
@endpush

{{-- PNG Auto-Slideshow --}}
@include('dashboard.partials._png_slideshow', ['role' => 'leader_qc'])

</x-app-layout>