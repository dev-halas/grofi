// clients-carousel.js
import Swiper from 'swiper';
import { Autoplay, A11y } from 'swiper/modules';
import 'swiper/css';

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
  selector: '.clients-swiper',
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
      slidesPerView: 4, 
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
    initCarousel(clientCarouselConfig);
    initCarousel(projectsCarouselConfig);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else run();
}
