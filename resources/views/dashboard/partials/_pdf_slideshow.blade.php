


<div id="pdfSlideshowModal" class="pdf-slideshow-modal" style="display: none;">
    <div class="pdf-slideshow-overlay">
        
        <button onclick="closePdfSlideshow()" class="pdf-close-btn" title="Close (ESC)">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        
        <button onclick="previousPdf()" class="pdf-nav-btn pdf-nav-prev" title="Previous (←)">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>

        <button onclick="nextPdf()" class="pdf-nav-btn pdf-nav-next" title="Next (→)">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>

        
        <div class="pdf-viewer-container">
            <embed id="pdfViewer" type="application/pdf" width="100%" height="100%">
        </div>

        
        <div class="pdf-controls-bottom">
            <div class="pdf-slide-counter">
                <span id="currentSlide">1</span> / <span id="totalSlides">1</span>
            </div>
            <div class="pdf-progress-bar">
                <div id="pdfProgressFill" class="pdf-progress-fill"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Fullscreen Modal Overlay */
.pdf-slideshow-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.95);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pdf-slideshow-overlay {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* PDF Viewer */
.pdf-viewer-container {
    width: 90%;
    height: 90%;
    max-width: 1400px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

#pdfViewer {
    border: none;
}

/* Close Button */
.pdf-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.3s ease;
    z-index: 100000;
}

.pdf-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.1);
}

/* Navigation Buttons */
.pdf-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.3s ease;
    z-index: 100000;
}

.pdf-nav-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-50%) scale(1.1);
}

.pdf-nav-prev {
    left: 30px;
}

.pdf-nav-next {
    right: 30px;
}

/* Bottom Controls */
.pdf-controls-bottom {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    z-index: 100000;
}

/* Slide Counter */
.pdf-slide-counter {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 8px 24px;
    border-radius: 24px;
    color: white;
    font-size: 18px;
    font-weight: bold;
    font-family: monospace;
}

/* Progress Bar */
.pdf-progress-bar {
    width: 300px;
    height: 6px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
    overflow: hidden;
}

.pdf-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    width: 0%;
    transition: width 0.3s ease;
}

/* Fade-in Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.pdf-slideshow-modal {
    animation: fadeIn 0.5s ease;
}
</style>

<script>

const userRole = '{{ strtolower(str_replace(" ", "_", Auth::user()->role ?? "")) }}';

const pdfFilesByRole = {
    'leader_fa': [
        '/pdfs/slideshow/leader_fa/slide1.pdf',
        '/pdfs/slideshow/leader_fa/slide2.pdf',
        '/pdfs/slideshow/leader_fa/slide3.pdf',
    ],
    'leader_smt': [
        '/pdfs/slideshow/leader_smt/slide1.pdf',
        '/pdfs/slideshow/leader_smt/slide2.pdf',
        '/pdfs/slideshow/leader_smt/slide3.pdf',
    ],
    'leader_ppic': [
        '/pdfs/slideshow/leader_ppic/slide1.pdf',
        '/pdfs/slideshow/leader_ppic/slide2.pdf',
        '/pdfs/slideshow/leader_ppic/slide3.pdf',
    ],
    'leader_qc': [
        '/pdfs/slideshow/leader_qc/slide1.pdf',
        '/pdfs/slideshow/leader_qc/slide2.pdf',
        '/pdfs/slideshow/leader_qc/slide3.pdf',
    ]
};

const pdfFiles = pdfFilesByRole[userRole] || [];

const INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
const SLIDE_INTERVAL = 10 * 1000; // 10 seconds between slides

let inactivityTimer = null;
let slideTimer = null;
let currentSlideIndex = 0;
let progressInterval = null;

function resetInactivityTimer() {
    if (inactivityTimer) {
        clearTimeout(inactivityTimer);
    }

    if (document.getElementById('pdfSlideshowModal').style.display !== 'none') {
        closePdfSlideshow();
    }

    inactivityTimer = setTimeout(() => {
        showPdfSlideshow();
    }, INACTIVITY_TIMEOUT);
}

const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

activityEvents.forEach(event => {
    document.addEventListener(event, resetInactivityTimer);
});

function showPdfSlideshow() {
    if (pdfFiles.length === 0) {
        console.warn('No PDF files configured for slideshow for role: ' + userRole);
        return;
    }

    const modal = document.getElementById('pdfSlideshowModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    currentSlideIndex = 0;
    loadPdf(currentSlideIndex);
    updateSlideCounter();
    startAutoAdvance();
}

function closePdfSlideshow() {
    const modal = document.getElementById('pdfSlideshowModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';

    stopAutoAdvance();
    resetInactivityTimer(); // Restart inactivity timer
}

function loadPdf(index) {
    const viewer = document.getElementById('pdfViewer');
    viewer.src = pdfFiles[index];
    currentSlideIndex = index;
    updateSlideCounter();
    resetProgress();
}

function nextPdf() {
    currentSlideIndex = (currentSlideIndex + 1) % pdfFiles.length;
    loadPdf(currentSlideIndex);
    stopAutoAdvance();
    startAutoAdvance();
}

function previousPdf() {
    currentSlideIndex = (currentSlideIndex - 1 + pdfFiles.length) % pdfFiles.length;
    loadPdf(currentSlideIndex);
    stopAutoAdvance();
    startAutoAdvance();
}

function updateSlideCounter() {
    document.getElementById('currentSlide').textContent = currentSlideIndex + 1;
    document.getElementById('totalSlides').textContent = pdfFiles.length;
}

function startAutoAdvance() {
    stopAutoAdvance(); // Clear any existing timers

    let progress = 0;
    const progressBar = document.getElementById('pdfProgressFill');

    progressInterval = setInterval(() => {
        progress += (100 / SLIDE_INTERVAL) * 100;
        if (progress >= 100) {
            progress = 100;
        }
        progressBar.style.width = progress + '%';
    }, 100);

    slideTimer = setTimeout(() => {
        nextPdf();
    }, SLIDE_INTERVAL);
}

function stopAutoAdvance() {
    if (slideTimer) {
        clearTimeout(slideTimer);
    }
    if (progressInterval) {
        clearInterval(progressInterval);
    }
}

function resetProgress() {
    const progressBar = document.getElementById('pdfProgressFill');
    progressBar.style.width = '0%';
}

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('pdfSlideshowModal');
    if (modal.style.display === 'none') return;

    switch(e.key) {
        case 'Escape':
            closePdfSlideshow();
            break;
        case 'ArrowRight':
            nextPdf();
            break;
        case 'ArrowLeft':
            previousPdf();
            break;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    resetInactivityTimer();
    console.log('PDF Slideshow initialized for role: ' + userRole);
    console.log('Total PDF files: ' + pdfFiles.length);
    console.log('Inactivity timeout: ' + (INACTIVITY_TIMEOUT / 1000 / 60) + ' minutes');
});
</script>
