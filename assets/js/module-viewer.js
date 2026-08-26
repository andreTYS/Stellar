// ============================================================
// INNOVA-STEAM — Module Viewer (4 steps engine)
// ============================================================

const ModuleViewer = {
  state: {
    moduloId:         null,
    pasoActual:       1,
    quizCompletado:   false,
    entregableSubido: false,
    estrellas:        0,
  },

  init(moduloId, pasoInicial = 1) {
    this.state.moduloId   = moduloId;
    this.state.pasoActual = pasoInicial;
    this.renderStep(pasoInicial);
    this.updateProgressBar();
    this.updateNavButtons();
  },

  // ── Render step ──────────────────────────────────────────
  renderStep(paso) {
    document.querySelectorAll('.paso-container').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(`paso-${paso}`);
    if (target) target.classList.add('active');

    document.querySelectorAll('.step-item').forEach((item, i) => {
      item.classList.remove('active', 'done');
      const n = i + 1;
      if (n < paso)      item.classList.add('done');
      else if (n === paso) item.classList.add('active');
    });
  },

  // ── Update progress bar ──────────────────────────────────
  updateProgressBar() {
    const fill = document.getElementById('global-progress');
    if (fill) {
      const pct = ((this.state.pasoActual - 1) / 4) * 100;
      fill.style.width = pct + '%';
    }
  },

  // ── Update nav buttons ────────────────────────────────────
  updateNavButtons() {
    const prevBtn = document.getElementById('btn-prev');
    const nextBtn = document.getElementById('btn-next');

    if (prevBtn) prevBtn.disabled = this.state.pasoActual === 1;

    if (nextBtn) {
      if (this.state.pasoActual === 4) {
        nextBtn.textContent = 'Finalizar módulo';
        nextBtn.dataset.action = 'finish';
      } else {
        nextBtn.textContent = 'Continuar →';
        nextBtn.dataset.action = 'next';
      }
    }
  },

  // ── Validate current step ─────────────────────────────────
  validateCurrent() {
    const paso = this.state.pasoActual;
    if (paso === 3 && !this.state.quizCompletado) {
      showToast('Completa el quiz antes de continuar.', 'warning');
      return false;
    }
    if (paso === 4 && !this.state.entregableSubido) {
      showToast('Sube tu entregable antes de finalizar.', 'warning');
      return false;
    }
    return true;
  },

  // ── Go next step ──────────────────────────────────────────
  async next() {
    if (!this.validateCurrent()) return;

    // Save progress
    try {
      await apiPost((window.BASE_URL || '') + '/api/progreso.php', {
        modulo_id:  this.state.moduloId,
        paso:       this.state.pasoActual,
        completado: this.state.pasoActual === 4,
        estrellas:  this.state.estrellas,
      });
    } catch (e) {
      console.warn('Error guardando progreso:', e);
    }

    if (this.state.pasoActual < 4) {
      this.state.pasoActual++;
      this.renderStep(this.state.pasoActual);
      this.updateProgressBar();
      this.updateNavButtons();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      this.showCompletado();
    }
  },

  // ── Go prev step ──────────────────────────────────────────
  prev() {
    if (this.state.pasoActual > 1) {
      this.state.pasoActual--;
      this.renderStep(this.state.pasoActual);
      this.updateProgressBar();
      this.updateNavButtons();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  },

  // ── Show completion modal ─────────────────────────────────
  showCompletado() {
    const modal = document.getElementById('modal-completado');
    if (modal) {
      modal.classList.add('open');
      // Animate stars
      const stars = modal.querySelectorAll('.star-anim');
      stars.forEach((s, i) => {
        if (i < this.state.estrellas) {
          setTimeout(() => s.classList.add('animate'), i * 200);
        }
      });
    }
  },

  // ── Mark quiz completed ───────────────────────────────────
  setQuizCompletado(estrellas) {
    this.state.quizCompletado = true;
    this.state.estrellas      = estrellas;
  },

  // ── Mark entregable uploaded ──────────────────────────────
  setEntregableSubido() {
    this.state.entregableSubido = true;
    showToast('¡Entregable subido exitosamente!', 'success');
  },
};

// ── Event listeners ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const nextBtn = document.getElementById('btn-next');
  const prevBtn = document.getElementById('btn-prev');

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (nextBtn.dataset.action === 'finish') {
        ModuleViewer.next();
      } else {
        ModuleViewer.next();
      }
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => ModuleViewer.prev());
  }
});
