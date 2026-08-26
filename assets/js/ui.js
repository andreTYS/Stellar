// ============================================================
// INNOVA-STEAM — ui.js
// Capa de movimiento y feedback. Se carga después de main.js.
// Todo es progresivo: si este script no corre, la página sigue
// siendo perfectamente usable.
// ============================================================

(() => {
  'use strict';

  // Evita doble inicialización si el script se incluye dos veces.
  if (window.__stellarUI) return;
  window.__stellarUI = true;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── Reveal on scroll ───────────────────────────────────────
  // Marca el <html> para que el CSS oculte los elementos solo
  // cuando sabemos que podemos revelarlos.
  function initReveal() {
    const targets = document.querySelectorAll('[data-reveal]');
    if (!targets.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
      targets.forEach(el => el.classList.add('is-visible'));
      return;
    }

    document.documentElement.classList.add('reveal-ready');

    const observer = new IntersectionObserver((entries, obs) => {
      // Escalona solo los elementos que entran juntos en el mismo tick.
      let shown = 0;
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.style.setProperty('--reveal-delay', `${Math.min(shown, 6) * 55}ms`);
        entry.target.classList.add('is-visible');
        obs.unobserve(entry.target);
        shown++;
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0.08 });

    targets.forEach(el => observer.observe(el));
  }

  // ── Count-up de valores numéricos ──────────────────────────
  // Anima .stat-value y cualquier [data-countup] desde 0 hasta su
  // valor final, conservando prefijos/sufijos ("85%", "S/ 1,200").
  function initCountUp() {
    const nodes = document.querySelectorAll('.stat-value, [data-countup]');
    if (!nodes.length || reduceMotion) return;

    const parse = text => {
      const match = text.match(/-?[\d.,]+/);
      if (!match) return null;
      const raw = match[0];
      // Quita separadores de miles, respeta el decimal.
      const numeric = parseFloat(raw.replace(/,/g, ''));
      if (!Number.isFinite(numeric)) return null;
      return {
        value: numeric,
        prefix: text.slice(0, match.index),
        suffix: text.slice(match.index + raw.length),
        decimals: (raw.split('.')[1] || '').length,
        grouped: raw.includes(','),
      };
    };

    const render = (el, n, spec) => {
      const fixed = n.toFixed(spec.decimals);
      const shown = spec.grouped
        ? Number(fixed).toLocaleString('es-PE', {
            minimumFractionDigits: spec.decimals,
            maximumFractionDigits: spec.decimals,
          })
        : fixed;
      el.textContent = spec.prefix + shown + spec.suffix;
    };

    const animate = el => {
      const spec = parse(el.textContent.trim());
      // Números muy grandes o de más de 4 cifras no se animan: el
      // conteo distrae más de lo que aporta.
      if (!spec || Math.abs(spec.value) > 9999) return;

      const duration = 900;
      const start = performance.now();
      render(el, 0, spec);

      const step = now => {
        const t = Math.min((now - start) / duration, 1);
        // easeOutExpo
        const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
        render(el, spec.value * eased, spec);
        if (t < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };

    if (!('IntersectionObserver' in window)) {
      nodes.forEach(animate);
      return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        animate(entry.target);
        obs.unobserve(entry.target);
      });
    }, { threshold: 0.4 });

    nodes.forEach(el => observer.observe(el));
  }

  // ── Progress rings ─────────────────────────────────────────
  // <div class="ring" data-pct="72" data-color="var(--green)"></div>
  function initRings() {
    document.querySelectorAll('.ring[data-pct]').forEach(ring => {
      if (ring.dataset.ringReady) return;
      ring.dataset.ringReady = '1';

      const pct = Math.max(0, Math.min(100, parseFloat(ring.dataset.pct) || 0));
      const color = ring.dataset.color;
      if (color) ring.style.setProperty('--ring-color', color);

      // El viewBox es fijo: el tamaño real lo controla el CSS.
      const R = 42;
      const circumference = 2 * Math.PI * R;

      ring.insertAdjacentHTML('afterbegin', `
        <svg viewBox="0 0 100 100" aria-hidden="true">
          <circle class="ring-track" cx="50" cy="50" r="${R}"></circle>
          <circle class="ring-value" cx="50" cy="50" r="${R}"
                  stroke-dasharray="${circumference}"
                  stroke-dashoffset="${circumference}"></circle>
        </svg>
      `);

      if (!ring.querySelector('.ring-label')) {
        const label = document.createElement('span');
        label.className = 'ring-label';
        label.innerHTML = `${Math.round(pct)}<small>%</small>`;
        ring.appendChild(label);
      }

      ring.setAttribute('role', 'img');
      ring.setAttribute('aria-label', `${Math.round(pct)} por ciento completado`);

      const arc = ring.querySelector('.ring-value');
      const target = circumference * (1 - pct / 100);

      if (reduceMotion) {
        arc.style.strokeDashoffset = target;
      } else {
        requestAnimationFrame(() => {
          setTimeout(() => { arc.style.strokeDashoffset = target; }, 60);
        });
      }
    });
  }

  // ── Estado de carga en formularios ─────────────────────────
  // Evita el doble submit y da feedback inmediato.
  function initFormLoading() {
    document.querySelectorAll('form').forEach(form => {
      if (form.dataset.noLoading !== undefined) return;

      form.addEventListener('submit', () => {
        // Si el navegador bloqueó el envío por validación, no marcamos nada.
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

        const btn = form.querySelector('button[type="submit"], button:not([type])');
        if (!btn || btn.classList.contains('is-loading')) return;

        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');

        // Red de seguridad: si la navegación no ocurre (error de red,
        // validación del servidor vía fetch), devolvemos el botón.
        setTimeout(() => {
          btn.classList.remove('is-loading');
          btn.removeAttribute('aria-busy');
        }, 8000);
      });
    });
  }

  // ── Topbar con sombra al hacer scroll ──────────────────────
  function initStickyTopbar() {
    const topbar = document.querySelector('.topbar');
    const scroller = document.querySelector('.page-content') || window;
    if (!topbar) return;

    const read = () =>
      scroller === window ? window.scrollY : scroller.scrollTop;

    let ticking = false;
    const update = () => {
      topbar.classList.toggle('is-stuck', read() > 8);
      ticking = false;
    };

    scroller.addEventListener('scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(update);
    }, { passive: true });

    update();
  }

  // ── Auto-reveal de tarjetas ────────────────────────────────
  // Marca las tarjetas del dashboard para que entren escalonadas,
  // sin tener que tocar cada plantilla PHP.
  function tagRevealables() {
    const selectors = ['.stat-card', '.card', '.curso-card', '.modulo-card'];
    document.querySelectorAll(selectors.join(',')).forEach(el => {
      if (!el.hasAttribute('data-reveal')) el.setAttribute('data-reveal', '');
    });
  }

  // ── Tablas anchas con scroll propio ────────────────────────
  // Evita que el <body> haga scroll horizontal en móvil.
  function wrapTables() {
    document.querySelectorAll('table').forEach(table => {
      const parent = table.parentElement;
      if (!parent || parent.classList.contains('table-scroll')) return;
      if (parent.scrollWidth <= parent.clientWidth && table.scrollWidth <= parent.clientWidth) return;

      const wrap = document.createElement('div');
      wrap.className = 'table-scroll';
      table.replaceWith(wrap);
      wrap.appendChild(table);
    });
  }

  // ── Init ───────────────────────────────────────────────────
  const boot = () => {
    tagRevealables();
    initReveal();
    initCountUp();
    initRings();
    initFormLoading();
    initStickyTopbar();
    wrapTables();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Expuesto para contenido cargado por AJAX.
  window.StellarUI = { initRings, initReveal, initCountUp };
})();
