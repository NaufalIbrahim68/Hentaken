{{-- Image Auto-Slideshow Component --}}
{{-- Activates after 30 seconds of inactivity --}}
{{-- Accepts $role parameter to load role-specific images --}}
{{-- Supports: PNG, JPEG, JPG formats --}}
{{-- Dashboard is included as the first slide in rotation --}}
{{-- Henkaten cards auto-rotate when slideshow is active and cards > 2 --}}

<style>
/* Henkaten card focus highlight */
.henkaten-card-focused {
    transform: scale(1.05) !important;
    box-shadow: 0 0 20px 5px rgba(251, 146, 60, 0.8) !important;
    border: 3px solid #f97316 !important;
    z-index: 10 !important;
    position: relative !important;
}

.henkaten-card-unfocused {
    opacity: 0.6 !important;
    transform: scale(0.95) !important;
}

.henkaten-card {
    transition: all 0.5s ease-in-out !important;
}
</style>

@php
    // Get role parameter (default to 'general' if not provided)
    $role = $role ?? 'general';
    
    // Get all image files (PNG, JPEG, JPG) from public/slides/{role} directory
    $slidesPath = public_path('slides/' . $role);
    $slideImages = [];
    
    if (file_exists($slidesPath) && is_dir($slidesPath)) {
        $files = scandir($slidesPath);
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            // Support PNG, JPEG, and JPG formats
            if (in_array($extension, ['png', 'jpeg', 'jpg'])) {
                $slideImages[] = asset('slides/' . $role . '/' . $file);
            }
        }
    }
    
    // Total slides = 1 (dashboard) + number of images
    $totalSlides = 1 + count($slideImages);
@endphp

{{-- Only render if there are images, dashboard alone doesn't need slideshow --}}
@if(count($slideImages) > 0)

{{-- Overlay for image slides (hidden by default) --}}
<div id="imageSlideshowOverlay" class="hidden fixed inset-0 bg-black z-50 flex items-center justify-center">
    <div class="relative w-full h-full flex items-center justify-center">
        {{-- Close button --}}
        <!-- <button onclick="exitSlideshow()" class="absolute top-4 right-4 z-50 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-full p-3 transition">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button> -->

        {{-- Image slides container --}}
        <div id="imageSlidesContainer" class="w-full h-full relative" onclick="toggleSlideshowPause()" style="cursor: pointer;">
            @foreach($slideImages as $index => $image)
                <div class="image-slide absolute inset-0 flex items-center justify-center opacity-0" 
                     data-slide-index="{{ $index + 1 }}"
                     style="transition: opacity 1s ease-in-out;">
                    <img src="{{ $image }}" alt="Slide {{ $index + 1 }}" class="max-w-full max-h-full object-contain">
                </div>
            @endforeach
            
            {{-- Pause indicator --}}
            <div id="pauseIndicator" class="hidden absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-black bg-opacity-60 text-white px-8 py-4 rounded-lg pointer-events-none">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                    </svg>
                    <span class="text-xl font-semibold">PAUSED</span>
                </div>
                <p class="text-sm text-center mt-2 opacity-80">Click to resume</p>
            </div>
        </div>

        {{-- Navigation arrows --}}
        <!-- @if($totalSlides > 1)
            <button onclick="previousSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-full p-3 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-full p-3 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        @endif -->
    </div>
</div>

<script>
let inactivityTimer;
let slideTimer;
let cardRotateTimer;
let currentSlideIndex = 0;
let currentCardIndex = 0;
const totalSlides = {{ $totalSlides }};
const INACTIVITY_TIMEOUT = 30000; // 30 seconds
const SLIDE_INTERVAL = 5000; // 5 seconds per slide
const CARD_ROTATE_INTERVAL = 5000; // 5 seconds per card rotation
let slideshowActive = false;
let slideshowPaused = false;

// Henkaten card containers
let henkatenCardSections = [];
let totalCardsPerSection = [];

// Get references to dashboard and overlay
const dashboardContainer = document.querySelector('.w-full.h-screen.flex.flex-col');
const imageOverlay = document.getElementById('imageSlideshowOverlay');
const pauseIndicator = document.getElementById('pauseIndicator');

// Detect and initialize Henkaten card sections
function initHenkatenCards() {
    // Find all Henkaten card container IDs
    const containerIds = [
        'shiftChangeContainer',    // Man Power
        'methodChangeContainer',   // Method
        'machineChangeContainer',  // Machine (if exists)
        'materialChangeContainer'  // Material (if exists)
    ];
    
    henkatenCardSections = [];
    totalCardsPerSection = [];
    
    containerIds.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (container) {
            // Find all cards within this container
            const cards = container.querySelectorAll('[data-henkaten-id]');
            if (cards.length > 2) {
                henkatenCardSections.push({
                    container: container,
                    cards: Array.from(cards),
                    id: containerId
                });
                totalCardsPerSection.push(cards.length);
                
                // Add henkaten-card class to all cards for styling
                cards.forEach(card => {
                    card.classList.add('henkaten-card');
                });
            }
        }
    });
    
    console.log(`Initialized ${henkatenCardSections.length} card sections for auto-rotation`);
}

// Rotate Henkaten card focus
function rotateCardFocus() {
    if (!slideshowActive || slideshowPaused || currentSlideIndex !== 0) {
        return; // Only rotate when on dashboard slide and not paused
    }
    
    if (henkatenCardSections.length === 0) {
        return; // No sections to rotate
    }
    
    // Remove all highlights first
    henkatenCardSections.forEach(section => {
        section.cards.forEach(card => {
            card.classList.remove('henkaten-card-focused', 'henkaten-card-unfocused');
        });
    });
    
    // Apply highlight to current card index in all sections
    henkatenCardSections.forEach(section => {
        section.cards.forEach((card, index) => {
            if (index === currentCardIndex) {
                card.classList.add('henkaten-card-focused');
                // Scroll card into view smoothly
                card.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            } else {
                card.classList.add('henkaten-card-unfocused');
            }
        });
    });
    
    // Move to next card (use minimum card count across all sections)
    if (henkatenCardSections.length > 0) {
        const minCardCount = Math.min(...totalCardsPerSection);
        currentCardIndex = (currentCardIndex + 1) % minCardCount;
    }
}

// Start card rotation
function startCardRotation() {
    if (henkatenCardSections.length === 0) {
        return; // No cards to rotate
    }
    
    currentCardIndex = 0;
    rotateCardFocus(); // Immediately highlight first card
    cardRotateTimer = setInterval(rotateCardFocus, CARD_ROTATE_INTERVAL);
}

// Stop card rotation
function stopCardRotation() {
    clearInterval(cardRotateTimer);
    
    // Remove all highlights
    henkatenCardSections.forEach(section => {
        section.cards.forEach(card => {
            card.classList.remove('henkaten-card-focused', 'henkaten-card-unfocused');
        });
    });
}

// Activity detection
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    
    // Only reset timer if slideshow is not active
    if (!slideshowActive) {
        inactivityTimer = setTimeout(startSlideshow, INACTIVITY_TIMEOUT);
    }
}

// Start the slideshow
function startSlideshow() {
    if (totalSlides <= 1) return; // Don't start if only dashboard exists
    
    slideshowActive = true;
    currentSlideIndex = 0;
    showSlide(0); // Start with dashboard
    
    // Start auto-advance for slides
    slideTimer = setInterval(nextSlide, SLIDE_INTERVAL);
    
    // Start card rotation (only when on dashboard slide)
    startCardRotation();
}

// Exit the slideshow
function exitSlideshow() {
    slideshowActive = false;
    clearInterval(slideTimer);
    
    // Stop card rotation
    stopCardRotation();
    
    // Show dashboard, hide overlay
    if (dashboardContainer) {
        dashboardContainer.style.display = 'flex';
    }
    if (imageOverlay) {
        imageOverlay.classList.add('hidden');
        imageOverlay.classList.remove('flex');
    }
    
    // Hide all image slides
    const imageSlides = document.querySelectorAll('.image-slide');
    imageSlides.forEach(slide => {
        slide.classList.remove('opacity-100');
        slide.classList.add('opacity-0');
    });
    
    resetInactivityTimer();
}

// Show specific slide
function showSlide(index) {
    if (index === 0) {
        // Show dashboard (slide 0)
        if (dashboardContainer) {
            dashboardContainer.style.display = 'flex';
        }
        if (imageOverlay) {
            imageOverlay.classList.add('hidden');
            imageOverlay.classList.remove('flex');
        }
        
        // Hide all image slides
        const imageSlides = document.querySelectorAll('.image-slide');
        imageSlides.forEach(slide => {
            slide.classList.remove('opacity-100');
            slide.classList.add('opacity-0');
        });
        
        // Start card rotation when showing dashboard
        if (slideshowActive && !slideshowPaused) {
            startCardRotation();
        }
    } else {
        // Show image slide - stop card rotation
        stopCardRotation();
        
        if (dashboardContainer) {
            dashboardContainer.style.display = 'none';
        }
        if (imageOverlay) {
            imageOverlay.classList.remove('hidden');
            imageOverlay.classList.add('flex');
        }
        
        // Show specific image slide
        const imageSlides = document.querySelectorAll('.image-slide');
        imageSlides.forEach((slide, i) => {
            const slideIndex = parseInt(slide.getAttribute('data-slide-index'));
            if (slideIndex === index) {
                slide.classList.remove('opacity-0');
                slide.classList.add('opacity-100');
            } else {
                slide.classList.remove('opacity-100');
                slide.classList.add('opacity-0');
            }
        });
    }
    
    currentSlideIndex = index;
}

// Next slide
function nextSlide() {
    if (!slideshowPaused) {
        const nextIndex = (currentSlideIndex + 1) % totalSlides;
        showSlide(nextIndex);
    }
}

// Toggle pause/resume slideshow
function toggleSlideshowPause() {
    if (!slideshowActive) return;
    
    slideshowPaused = !slideshowPaused;
    
    if (slideshowPaused) {
        // Show pause indicator
        if (pauseIndicator) {
            pauseIndicator.classList.remove('hidden');
            // Auto-hide after 2 seconds but keep paused state
            setTimeout(() => {
                if (slideshowPaused && pauseIndicator) {
                    pauseIndicator.style.opacity = '0.5';
                }
            }, 2000);
        }
    } else {
        // Hide pause indicator and resume
        if (pauseIndicator) {
            pauseIndicator.classList.add('hidden');
            pauseIndicator.style.opacity = '1';
        }
    }
}

// Previous slide
function previousSlide() {
    const prevIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
    showSlide(prevIndex);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Henkaten card detection
    initHenkatenCards();
    
    // Set up activity listeners
    const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    
    activityEvents.forEach(event => {
        document.addEventListener(event, function(e) {
            // Skip if clicking on image container (handled by toggleSlideshowPause)
            if (event === 'click' && e.target.closest('#imageSlidesContainer')) {
                return;
            }
            
            // If slideshow is active and user interacts, exit it
            if (slideshowActive) {
                exitSlideshow();
            } else {
                resetInactivityTimer();
            }
        });
    });
    
    // Start the initial timer
    resetInactivityTimer();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    clearTimeout(inactivityTimer);
    clearInterval(slideTimer);
    clearInterval(cardRotateTimer);
});
</script>
@endif
