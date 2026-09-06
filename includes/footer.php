    </main><!-- /.page-content -->
  </div><!-- /.main-content -->
</div><!-- /.layout -->

<?php
// ── Asistente ────────────────────────────────────────────────────
// Estudiantes y practicantes para estudiar; el docente para preparar
// clase, con otro prompt y otro tope. Los demás roles no lo necesitan y
// cada consulta gasta saldo del colegio.
//
// Esto solo decide si se pinta el widget: quién puede consultar de
// verdad lo comprueba api/chat.php.
$rolAsistente = currentRole();
if (in_array($rolAsistente, ['estudiante', 'practicante', 'docente'], true)):
    $esDocente = $rolAsistente === 'docente';
?>
<div x-data="asistente()" x-cloak>
  <button type="button" class="asistente-boton" @click="abrir = !abrir"
          :aria-expanded="abrir" aria-controls="panel-asistente"
          aria-label="Abrir el asistente de estudio">
    <i :data-lucide="abrir ? 'x' : 'sparkles'" style="width:20px;height:20px"></i>
  </button>

  <div class="asistente-panel" id="panel-asistente" x-show="abrir" x-transition
       role="dialog" aria-label="Asistente de estudio">
    <header>
      <div>
        <strong><?= $esDocente ? 'Asistente de aula' : 'Asistente de estudio' ?></strong>
        <span x-show="restantes !== null" x-text="restantes + ' preguntas hoy'"></span>
      </div>
      <button type="button" @click="abrir = false" aria-label="Cerrar">&times;</button>
    </header>

    <div class="asistente-hilo" x-ref="hilo">
      <template x-if="turnos.length === 0">
        <p class="asistente-vacio">
          <?php if ($esDocente): ?>
          Pídame adaptar una actividad a los materiales que tenga, dividir un
          módulo en sesiones, criterios de rúbrica o ideas de refuerzo y
          ampliación.
          <?php else: ?>
          Pregúntame sobre matemática, comunicación, arte, ingeniería, inglés,
          ciencia o astronomía. Te oriento paso a paso, no te doy la respuesta
          hecha.
          <?php endif; ?>
        </p>
      </template>
      <template x-for="(t, i) in turnos" :key="i">
        <div :class="'asistente-turno ' + t.rol" x-text="t.texto"></div>
      </template>
      <div class="asistente-turno assistant" x-show="cargando">Pensando…</div>
    </div>

    <form @submit.prevent="enviar" data-no-loading>
      <label for="asistente-entrada" class="sr-only">Tu pregunta</label>
      <input id="asistente-entrada" type="text" x-model="texto"
             placeholder="Escribe tu pregunta…" autocomplete="off"
             :disabled="cargando" maxlength="2000"/>
      <button type="submit" :disabled="cargando || !texto.trim()">Enviar</button>
    </form>
  </div>
</div>

<script>
function asistente() {
  return {
    abrir: false, texto: '', turnos: [], cargando: false, restantes: null,

    async enviar() {
      const pregunta = this.texto.trim();
      if (!pregunta || this.cargando) return;

      this.turnos.push({ rol: 'user', texto: pregunta });
      this.texto = '';
      this.cargando = true;
      this.$nextTick(() => this.alFinal());

      try {
        const r = await fetch(window.BASE_URL + '/api/chat.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || '',
          },
          body: JSON.stringify({
            pregunta,
            // Solo los últimos turnos: el servidor recorta igualmente, y
            // mandar la conversación entera encarece cada pregunta.
            historial: this.turnos.slice(-6),
            csrf_token: window.CSRF_TOKEN || '',
          }),
        });
        const datos = await r.json();

        if (datos.ok) {
          this.turnos.push({ rol: 'assistant', texto: datos.respuesta });
          this.restantes = datos.restantes;
        } else {
          // El servidor manda mensajes ya listos para leer.
          this.turnos.push({ rol: 'error', texto: datos.error || 'No se pudo responder.' });
        }
      } catch (e) {
        this.turnos.push({ rol: 'error', texto: 'Sin conexión con el servidor.' });
      } finally {
        this.cargando = false;
        this.$nextTick(() => this.alFinal());
        if (window.lucide) lucide.createIcons();
      }
    },

    alFinal() {
      const h = this.$refs.hilo;
      if (h) h.scrollTop = h.scrollHeight;
    },
  };
}
</script>
<?php endif; ?>

<?php /* main.js y ui.js se cargan con defer desde header.php.
        toggleSidebar()/closeSidebar() viven en main.js — no duplicar aquí. */ ?>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $js ?>"></script>
<?php endforeach; endif; ?>
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>
</body>
</html>
