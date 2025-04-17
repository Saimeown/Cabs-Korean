const menu = document.querySelector('#mobile-menu');
const menuLink = document.querySelector('.navbar__menu');

menu.addEventListener('click', function() {
  menu.classList.toggle('is-active');
  menuLink.classList.toggle('active');
})

let currentSlide = 0;
const slideInterval = 2000; // Interval in milliseconds (3 seconds)

function moveSlide(direction) {
  const slides = document.querySelectorAll('.carousel__img');
  const slideCount = slides.length;
  
  currentSlide += direction;

  if (currentSlide < 0) {
    currentSlide = slideCount - 1; // Wrap around to last slide
  } else if (currentSlide >= slideCount) {
    currentSlide = 0; // Wrap around to first slide
  }

  // Apply the sliding effect
  const carouselContainer = document.querySelector('.carousel__img-container');
  carouselContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
}

// Initialize the carousel with the first image visible
document.addEventListener('DOMContentLoaded', () => {
  moveSlide(0);
  
  // Set an interval to automatically slide every 3 seconds
  setInterval(() => {
    moveSlide(1); // Move to the next slide every 3 seconds
  }, slideInterval);
});
