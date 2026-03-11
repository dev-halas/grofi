// clients-carousel.js
import Swiper from 'swiper';
import { Autoplay, A11y, Navigation } from 'swiper/modules';
import 'swiper/css';

function initHeroSlider() {
  const el = document.querySelector('.heroSlider__swiper');
  if (!el) return;

  new Swiper(el, {
    modules: [Navigation, A11y],
    loop: true,
    speed: 600,
    a11y: { enabled: true },
    navigation: {
      nextEl: '.heroSlider__nav--next',
      prevEl: '.heroSlider__nav--prev',
    },
  });
}

export function initCarousel(_config) {
  const {
    selector = null, 
    reverse = false,
    breakpoints = {}
  } = _config;

  const el = document.querySelector(selector);
  if (!el) {
    return;
  }

  const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  const swiper = new Swiper(el, {
    modules: [Autoplay, A11y],
    loop: true,
    breakpoints,
    speed: prefersReduced ? 4000 : 8000,
    autoplay: {
      delay: 0,
      disableOnInteraction: false,
      pauseOnMouseEnter: false,
      reverseDirection: reverse,
    },

    allowTouchMove: true,
    grabCursor: true,
    a11y: { enabled: true },
  });

  return swiper;
}

const clientCarouselConfig = {
  selector: '.logo-carousel',
  breakpoints: {
    0: { 
      slidesPerView: 2, 
      spaceBetween: 16,
      // speed: 3000,
    },
    768: { 
      slidesPerView: 3,
      spaceBetween: 24,
      // speed: 3000,
    },
    1200: { 
      slidesPerView: 6, 
      spaceBetween: 32,
      // speed: 4000,
    },
  },
};

const projectsCarouselConfig = {
  selector: '.projects-swiper',
  reverse: true,
  breakpoints: {
    0: { 
      slidesPerView: 2, 
      spaceBetween: 16,
      speed: 3000,
    },
    768: { 
      slidesPerView: 3,
      spaceBetween: 24,
      speed: 3000,
    },
    1200: { 
      slidesPerView: 4, 
      spaceBetween: 32,
      speed: 4000,
    },
  },
};

if (typeof window !== 'undefined') {
  const run = () => {
    initHeroSlider();
    initCarousel(clientCarouselConfig);
    initCarousel(projectsCarouselConfig);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else run();
}
