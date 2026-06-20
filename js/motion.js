const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion) {
  const selectors = [
    '.hero-content',
    '.page-banner .container',
    '.section-header',
    '.feature-card',
    '.course-card',
    '.split-cta',
    '.testimonial-card',
    '.faq-item',
    '.exam-main',
    '.question-nav',
    '.dashboard-card',
    '.stat-card',
    '.teacher-hero',
    '.enrollment-card',
    '.teacher-info-card',
    '.auth-copy',
    '.auth-card',
    '.terms-card'
  ].join(',');

  const elements = Array.from(document.querySelectorAll(selectors));

  const prepare = (el) => {
    el.style.opacity = '0';
    el.style.willChange = 'opacity';
  };

  const cleanup = (el) => {
    el.style.opacity = '1';
    el.style.willChange = '';
  };

  const fallbackAnimate = (el, delay = 0) => {
    prepare(el);
    const animation = el.animate(
      [{ opacity: 0 }, { opacity: 1 }],
      {
        duration: 420,
        delay: delay * 1000,
        easing: 'ease-out',
        fill: 'forwards'
      }
    );
    animation.onfinish = () => cleanup(el);
  };

  const revealWithObserver = (animateFn) => {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || entry.target.dataset.revealed === 'true') return;

        const el = entry.target;
        const siblings = Array.from(el.parentElement ? el.parentElement.children : []);
        const index = Math.max(0, siblings.indexOf(el));
        const delay = Math.min(index * 0.035, 0.14);

        el.dataset.revealed = 'true';
        requestAnimationFrame(() => animateFn(el, delay));
        observer.unobserve(el);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -4% 0px' });

    elements.forEach((el) => observer.observe(el));
  };

  const startFallback = () => revealWithObserver(fallbackAnimate);

  import('https://cdn.jsdelivr.net/npm/framer-motion@12.40.0/+esm')
    .then(({ animate }) => {
      if (typeof animate !== 'function') {
        startFallback();
        return;
      }

      document.documentElement.dataset.motionEngine = 'framer-motion';
      revealWithObserver((el, delay) => {
        prepare(el);
        const controls = animate(
          el,
          { opacity: [0, 1] },
          { duration: 0.42, delay, ease: 'easeOut' }
        );

        if (controls && typeof controls.then === 'function') {
          controls.then(() => cleanup(el));
        } else {
          window.setTimeout(() => cleanup(el), (0.42 + delay) * 1000 + 60);
        }
      });
    })
    .catch(startFallback);
}
