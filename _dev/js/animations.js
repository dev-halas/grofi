import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

gsap.registerPlugin(ScrollTrigger);

const animationsMap = {
  "animate-fade-up": { from: { y: 50, opacity: 0 }, to: { y: 0, opacity: 1 } },
  "animate-slide-left": { from: { x: 100, opacity: 0 }, to: { x: 0, opacity: 1 } },
  "animate-slide-right": { from: { x: -100, opacity: 0 }, to: { x: 0, opacity: 1 } },
  "animate-zoom-in": { from: { scale: 0.8, opacity: 0 }, to: { scale: 1, opacity: 1 } },
};

Object.entries(animationsMap).forEach(([className, animProps]) => {
  document.querySelectorAll(`.${className}`).forEach((el) => {
    let delay = parseFloat(el.dataset.delay);
    if (isNaN(delay)) {
      const parentWithDelay = el.closest("[data-delay]");
      delay = parentWithDelay ? parseFloat(parentWithDelay.dataset.delay) : 0.2;
    }

    // Nadpisz delay na urządzeniach mobilnych
    if (window.innerWidth <= 768) {
      delay = 0.2;
    }

    // Ustaw stan początkowy raz
    gsap.set(el, animProps.from);

    let currentTween = null;

    const show = () => {
      if (currentTween) currentTween.kill();
      currentTween = gsap.to(el, {
        ...animProps.to,
        duration: 1,
        delay,
        ease: "power2.out"
      });
    };

    const hide = () => {
      if (currentTween) currentTween.kill();
      currentTween = gsap.to(el, {
        ...animProps.from,
        duration: 1,
        ease: "power2.in"
      });
    };

    ScrollTrigger.create({
      trigger: el,
      start: "top 90%",
      end: "bottom 10%",
      onEnter: show,
      // onLeave: hide,
      // onEnterBack: show,
      onLeaveBack: hide,
    });
  });
});

