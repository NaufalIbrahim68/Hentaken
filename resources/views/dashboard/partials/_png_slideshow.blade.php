






<style>
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
    $role = $role ?? 'general';
    
    $slidesPath = public_path('slides/' . $role);
    $slideImages = [];
    
    if (file_exists($slidesPath) && is_dir($slidesPath)) {
        $files = scandir($slidesPath);
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpeg', 'jpg'])) {
                $slideImages[] = asset('slides/' . $role . '/' . $file);
            }
        }
    }
    
    $totalSlides = 1 + count($slideImages);
@endphp


@if(count($slideImages) > 0)


<div id="imageSlideshowOverlay" class="hidden fixed inset-0 bg-black z-50 flex items-center justify-center">
    <div class="relative w-full h-full flex items-center justify-center">
        
      
        
        <div id="imageSlidesContainer" class="w-full h-full relative" onclick="toggleSlideshowPause()" style="cursor: pointer;">
            @foreach($slideImages as $index => $image)
                <div class="image-slide absolute inset-0 flex items-center justify-center opacity-0" 
                     data-slide-index="{{ $index + 1 }}"
                     style="transition: opacity 1s ease-in-out;">
                    <img src="{{ $image }}" alt="Slide {{ $index + 1 }}" class="max-w-full max-h-full object-contain">
                </div>
            @endforeach
            
            
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
const INACTIVITY_TIMEOUT = 10000; // 10 seconds
const SLIDE_INTERVAL = 5000; // 5 seconds per slide
const CARD_ROTATE_INTERVAL = 5000; // 5 seconds per card rotation
let slideshowActive = false;
let slideshowPaused = false;

let henkatenCardSections = [];
let totalCardsPerSection = [];

const dashboardContainer = document.querySelector('.w-full.h-screen.flex.flex-col');
const imageOverlay = document.getElementById('imageSlideshowOverlay');
const pauseIndicator = document.getElementById('pauseIndicator');

function initHenkatenCards() {
    const containerIds = [
        'shiftChangeContainer',    
        'methodChangeContainer',  
        'machineChangeContainer',  
        'materialChangeContainer'  
    ];
    
    henkatenCardSections = [];
    totalCardsPerSection = [];
    
    containerIds.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (container) {
            const cards = container.querySelectorAll('[data-henkaten-id]');
            if (cards.length > 2) {
                henkatenCardSections.push({
                    container: container,
                    cards: Array.from(cards),
                    id: containerId
                });
                totalCardsPerSection.push(cards.length);
                
                cards.forEach(card => {
                    card.classList.add('henkaten-card');
                });
            }
        }
    });
    
    console.log(`Initialized ${henkatenCardSections.length} card sections for auto-rotation`);
}

function rotateCardFocus() {
    if (!slideshowActive || slideshowPaused || currentSlideIndex !== 0) {
        return;
    }
    
    if (henkatenCardSections.length === 0) {
        return; 
    }
    
    henkatenCardSections.forEach(section => {
        section.cards.forEach(card => {
            card.classList.remove('henkaten-card-focused', 'henkaten-card-unfocused');
        });
    });
    
    henkatenCardSections.forEach(section => {
        section.cards.forEach((card, index) => {
            if (index === currentCardIndex) {
                card.classList.add('henkaten-card-focused');
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
    
    if (henkatenCardSections.length > 0) {
        const minCardCount = Math.min(...totalCardsPerSection);
        currentCardIndex = (currentCardIndex + 1) % minCardCount;
    }
}

function startCardRotation() {
    if (henkatenCardSections.length === 0) {
        return; 
    }
    
    currentCardIndex = 0;
    rotateCardFocus(); 
    cardRotateTimer = setInterval(rotateCardFocus, CARD_ROTATE_INTERVAL);
}

function stopCardRotation() {
    clearInterval(cardRotateTimer);
    
    henkatenCardSections.forEach(section => {
        section.cards.forEach(card => {
            card.classList.remove('henkaten-card-focused', 'henkaten-card-unfocused');
        });
    });
}

function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    
    if (!slideshowActive) {
        inactivityTimer = setTimeout(startSlideshow, INACTIVITY_TIMEOUT);
    }
}

function startSlideshow() {
    if (totalSlides <= 1) return; 
    
    slideshowActive = true;
    currentSlideIndex = 0;
    showSlide(0); 
    
    slideTimer = setInterval(nextSlide, SLIDE_INTERVAL);
    
    startCardRotation();
}

function exitSlideshow() {
    slideshowActive = false;
    clearInterval(slideTimer);
    
    stopCardRotation();
    
    if (dashboardContainer) {
        dashboardContainer.style.display = 'flex';
    }
    if (imageOverlay) {
        imageOverlay.classList.add('hidden');
        imageOverlay.classList.remove('flex');
    }
    
    const imageSlides = document.querySelectorAll('.image-slide');
    imageSlides.forEach(slide => {
        slide.classList.remove('opacity-100');
        slide.classList.add('opacity-0');
    });
    
    resetInactivityTimer();
}

function showSlide(index) {
    if (index === 0) {
        if (dashboardContainer) {
            dashboardContainer.style.display = 'flex';
        }
        if (imageOverlay) {
            imageOverlay.classList.add('hidden');
            imageOverlay.classList.remove('flex');
        }
        
        const imageSlides = document.querySelectorAll('.image-slide');
        imageSlides.forEach(slide => {
            slide.classList.remove('opacity-100');
            slide.classList.add('opacity-0');
        });
        
        if (slideshowActive && !slideshowPaused) {
            startCardRotation();
        }
    } else {
        stopCardRotation();
        
        if (dashboardContainer) {
            dashboardContainer.style.display = 'none';
        }
        if (imageOverlay) {
            imageOverlay.classList.remove('hidden');
            imageOverlay.classList.add('flex');
        }
        
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

function nextSlide() {
    if (!slideshowPaused) {
        const nextIndex = (currentSlideIndex + 1) % totalSlides;
        showSlide(nextIndex);
    }
}

function toggleSlideshowPause() {
    if (!slideshowActive) return;
    
    slideshowPaused = !slideshowPaused;
    
    if (slideshowPaused) {
        if (pauseIndicator) {
            pauseIndicator.classList.remove('hidden');
            setTimeout(() => {
                if (slideshowPaused && pauseIndicator) {
                    pauseIndicator.style.opacity = '0.5';
                }
            }, 2000);
        }
    } else {
        if (pauseIndicator) {
            pauseIndicator.classList.add('hidden');
            pauseIndicator.style.opacity = '1';
        }
    }
}

function previousSlide() {
    const prevIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
    showSlide(prevIndex);
}

document.addEventListener('DOMContentLoaded', function() {
    initHenkatenCards();
    
    const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    
    activityEvents.forEach(event => {
        document.addEventListener(event, function(e) {
            if (event === 'click' && e.target.closest('#imageSlidesContainer')) {
                return;
            }
            
            if (slideshowActive) {
                exitSlideshow();
            } else {
                resetInactivityTimer();
            }
        });
    });
    
    resetInactivityTimer();
});

window.addEventListener('beforeunload', function() {
    clearTimeout(inactivityTimer);
    clearInterval(slideTimer);
    clearInterval(cardRotateTimer);
});
</script>
@endif
