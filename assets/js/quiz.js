// ============================================================
// INNOVA-STEAM — Quiz Engine
// ============================================================

const QuizEngine = {
  preguntas:    [],
  current:      0,
  correctas:    0,
  respondidas:  [],
  pasoId:       null,

  init(preguntas, pasoId) {
    this.preguntas   = preguntas;
    this.pasoId      = pasoId;
    this.current     = 0;
    this.correctas   = 0;
    this.respondidas = [];
    this.renderPregunta();
    this.updateQuizProgress();
  },

  // ── Render current question ───────────────────────────────
  renderPregunta() {
    const q    = this.preguntas[this.current];
    const cont = document.getElementById('quiz-content');
    if (!cont || !q) return;

    const letras = ['A', 'B', 'C', 'D'];
    const optsHtml = q.opciones.map((opt, i) => `
      <button class="quiz-option" data-index="${i}" onclick="QuizEngine.responder(${i})">
        <span class="quiz-option-letter">${letras[i]}</span>
        ${opt.texto}
      </button>
    `).join('');

    cont.innerHTML = `
      <p class="quiz-question-text">${q.texto}</p>
      <div class="quiz-options" id="quiz-options">${optsHtml}</div>
      <div class="quiz-feedback" id="quiz-feedback"></div>
    `;
  },

  // ── Update progress counter ───────────────────────────────
  updateQuizProgress() {
    const el = document.getElementById('quiz-progress');
    if (el) {
      el.textContent = `Pregunta ${this.current + 1} de ${this.preguntas.length}`;
    }
  },

  // ── Handle answer ─────────────────────────────────────────
  async responder(opcionIndex) {
    const q       = this.preguntas[this.current];
    const esCorrecta = q.opciones[opcionIndex].correcta;

    // Disable all options
    document.querySelectorAll('.quiz-option').forEach(btn => {
      btn.style.pointerEvents = 'none';
    });

    // Style selected option
    const selectedBtn = document.querySelector(`.quiz-option[data-index="${opcionIndex}"]`);
    if (selectedBtn) {
      selectedBtn.classList.add('selected', esCorrecta ? 'correct' : 'incorrect');
    }

    // Show correct if wrong
    if (!esCorrecta) {
      const correctIndex = q.opciones.findIndex(o => o.correcta);
      const correctBtn   = document.querySelector(`.quiz-option[data-index="${correctIndex}"]`);
      if (correctBtn) correctBtn.classList.add('selected', 'correct');
    } else {
      this.correctas++;
    }

    // Feedback
    const feedback = document.getElementById('quiz-feedback');
    if (feedback) {
      feedback.className = `quiz-feedback show ${esCorrecta ? 'correct' : 'incorrect'}`;
      feedback.textContent = esCorrecta
        ? '¡Correcto! Muy bien.'
        : `Incorrecto. La respuesta correcta era: ${q.opciones.find(o => o.correcta)?.texto}`;
    }

    // Save response
    this.respondidas.push({ pregunta_id: q.id, opcion: opcionIndex, correcta: esCorrecta });

    // Send to server
    try {
      await apiPost((window.BASE_URL || '') + '/api/quiz.php', {
        pregunta_id:   q.id,
        opcion_elegida: opcionIndex,
        es_correcta:   esCorrecta ? 1 : 0,
      });
    } catch (e) {
      console.warn('Error guardando respuesta:', e);
    }

    // Next question after delay
    setTimeout(() => {
      this.current++;
      if (this.current < this.preguntas.length) {
        this.renderPregunta();
        this.updateQuizProgress();
      } else {
        this.showResult();
      }
    }, 1500);
  },

  // ── Calculate stars ────────────────────────────────────────
  calcularEstrellas() {
    const pct = (this.correctas / this.preguntas.length) * 100;
    if (pct >= 90) return 3;
    if (pct >= 60) return 2;
    return 1;
  },

  // ── Show final result ─────────────────────────────────────
  showResult() {
    const estrellas = this.calcularEstrellas();
    const cont      = document.getElementById('quiz-content');
    if (!cont) return;

    const starHtml = Array.from({ length: 3 }, (_, i) =>
      `<span class="star-anim" style="color:${i < estrellas ? '#f5c842' : '#2a2a3a'};font-size:40px;opacity:${i < estrellas ? 0 : 1}" data-index="${i}">★</span>`
    ).join('');

    cont.innerHTML = `
      <div style="text-align:center;padding:20px 0">
        <div class="stars-display" id="stars-result">${starHtml}</div>
        <p style="font-size:22px;font-weight:800;font-family:'Syne',sans-serif;margin-bottom:8px">
          ${this.correctas} / ${this.preguntas.length} correctas
        </p>
        <p style="color:var(--text-secondary);margin-bottom:24px">
          ${estrellas === 3 ? '¡Excelente! Dominas este tema.' :
            estrellas === 2 ? '¡Muy bien! Sigue practicando.' :
            'Buen intento. Puedes revisar el contenido y volver a intentarlo.'}
        </p>
      </div>
    `;

    // Animate earned stars
    document.querySelectorAll('#stars-result .star-anim').forEach((star, i) => {
      if (i < estrellas) {
        setTimeout(() => {
          star.style.opacity = '1';
          star.classList.add('animate');
        }, i * 300);
      }
    });

    // Update quiz progress label
    const progressEl = document.getElementById('quiz-progress');
    if (progressEl) progressEl.textContent = '¡Quiz completado!';

    // Notify ModuleViewer
    if (typeof ModuleViewer !== 'undefined') {
      ModuleViewer.setQuizCompletado(estrellas);
    }
  },
};
