// ============================================================
// INNOVA-STEAM — Cola de envíos sin conexión
//
// En las aulas de Moquegua la conexión se cae a media clase. Hasta
// ahora, un quiz respondido o un entregable subido sin señal se perdía
// en silencio: el fetch fallaba y el .catch() vacío se lo tragaba. El
// estudiante veía su modal de felicitación y su trabajo no existía.
//
// Ahora el envío se guarda en IndexedDB y se reintenta cuando vuelve la
// conexión. Cada envío lleva un identificador propio para que el
// servidor sepa distinguir un reintento de un envío nuevo.
// ============================================================
(function () {
  'use strict';

  const BD      = 'innova-cola';
  const ALMACEN = 'envios';
  const MAX_INTENTOS = 50;
  // Una semana, igual que la limpieza del servidor. Pasado ese tiempo
  // el reintento ya no tiene sentido y solo ocuparía espacio.
  const CADUCIDAD_MS = 7 * 24 * 60 * 60 * 1000;

  let bd = null;

  function abrir() {
    if (bd) return Promise.resolve(bd);
    return new Promise((ok, fallo) => {
      const req = indexedDB.open(BD, 1);
      req.onupgradeneeded = () => {
        const db = req.result;
        if (!db.objectStoreNames.contains(ALMACEN)) {
          db.createObjectStore(ALMACEN, { keyPath: 'uuid' });
        }
      };
      req.onsuccess = () => { bd = req.result; ok(bd); };
      req.onerror   = () => fallo(req.error);
    });
  }

  function tx(modo, fn) {
    return abrir().then(db => new Promise((ok, fallo) => {
      const t = db.transaction(ALMACEN, modo);
      const r = fn(t.objectStore(ALMACEN));
      t.oncomplete = () => ok(r && r.result !== undefined ? r.result : undefined);
      t.onerror    = () => fallo(t.error);
    }));
  }

  const uuid = () =>
    (crypto.randomUUID
      ? crypto.randomUUID()
      // Safari antiguo no trae randomUUID; con getRandomValues basta.
      : ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
          (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)));

  // ── Indicador ──────────────────────────────────────────────
  // Solo aparece cuando hay algo pendiente. Si el estudiante no lo ve,
  // cree que su trabajo se guardó cuando no lo hizo.
  let pill = null;

  function pintarIndicador(n) {
    if (n <= 0) { if (pill) { pill.remove(); pill = null; } return; }

    if (!pill) {
      pill = document.createElement('div');
      pill.className = 'cola-pendientes';
      pill.setAttribute('role', 'status');
      pill.setAttribute('aria-live', 'polite');
      document.body.appendChild(pill);
    }
    pill.textContent = n === 1
      ? '1 envío pendiente · se reintentará solo'
      : n + ' envíos pendientes · se reintentarán solos';
  }

  function refrescarIndicador() {
    return tx('readonly', s => s.count()).then(pintarIndicador).catch(() => {});
  }

  // ── API pública ────────────────────────────────────────────

  /**
   * Envía ahora; si no se puede, encola y reintenta más tarde.
   *
   * @param {string} url
   * @param {{json?: object, form?: FormData}} datos
   * @returns {Promise<{enviado: boolean, respuesta?: object}>}
   */
  async function enviar(url, datos) {
    const id = uuid();
    const item = await serializar(id, url, datos);

    // Sin conexión no se intenta siquiera: se encola y punto.
    if (navigator.onLine === false) {
      await guardar(item);
      return { enviado: false, encolado: true };
    }

    try {
      const res = await ejecutar(item);
      if (res.reintentable) {
        await guardar(item);
        return { enviado: false, encolado: true };
      }
      return { enviado: true, respuesta: res.datos };
    } catch (e) {
      await guardar(item);
      return { enviado: false, encolado: true };
    }
  }

  async function serializar(id, url, datos) {
    const base = { uuid: id, url: url, creado: Date.now(), intentos: 0 };

    if (datos.form) {
      // FormData no se puede guardar tal cual; se descompone en campos
      // planos y archivos. Los Blob sí van directos a IndexedDB.
      const campos = {}, archivos = [];
      for (const [k, v] of datos.form.entries()) {
        if (v instanceof File) {
          // Un archivo vacío es el input sin elegir nada.
          if (v.size > 0) archivos.push({ campo: k, blob: v, nombre: v.name });
        } else {
          campos[k] = v;
        }
      }
      campos.cliente_uuid = id;
      return Object.assign(base, { tipo: 'form', campos, archivos });
    }

    return Object.assign(base, {
      tipo: 'json',
      cuerpo: Object.assign({}, datos.json, { cliente_uuid: id }),
    });
  }

  function guardar(item) {
    return tx('readwrite', s => s.put(item)).then(refrescarIndicador).catch(() => {});
  }

  function borrar(id) {
    return tx('readwrite', s => s.delete(id)).then(refrescarIndicador).catch(() => {});
  }

  /**
   * Hace la petición. Devuelve si conviene reintentar o no.
   *
   * El token CSRF se toma en el momento del envío, no del que había
   * cuando se encoló: si el usuario volvió a entrar, el viejo ya no vale.
   */
  async function ejecutar(item) {
    const cabeceras = { 'X-CSRF-Token': window.CSRF_TOKEN || '' };
    let cuerpo;

    if (item.tipo === 'form') {
      const fd = new FormData();
      for (const k in item.campos) fd.append(k, item.campos[k]);
      (item.archivos || []).forEach(a => fd.append(a.campo, a.blob, a.nombre));
      cuerpo = fd;
    } else {
      cabeceras['Content-Type'] = 'application/json';
      cuerpo = JSON.stringify(item.cuerpo);
    }

    const res = await fetch(item.url, { method: 'POST', headers: cabeceras, body: cuerpo });

    // 5xx, 429 y 408 son transitorios. El 403 suele ser el CSRF de una
    // sesión caducada, que se arregla al volver a entrar. El resto de
    // 4xx no va a mejorar reintentando: la petición es inválida.
    const reintentable = res.status >= 500 || res.status === 429 ||
                         res.status === 408 || res.status === 403;

    let datos = null;
    try { datos = await res.json(); } catch (e) { /* respuesta no JSON */ }

    return { reintentable, datos, estado: res.status };
  }

  /** Procesa la cola entera. Se llama al cargar y al volver la conexión. */
  async function reintentar() {
    if (navigator.onLine === false) return;

    let items = [];
    try {
      items = await tx('readonly', s => s.getAll());
    } catch (e) { return; }
    if (!items || !items.length) return;

    for (const item of items) {
      const viejo = Date.now() - (item.creado || 0) > CADUCIDAD_MS;
      if (viejo || (item.intentos || 0) >= MAX_INTENTOS) {
        await borrar(item.uuid);
        continue;
      }

      try {
        const res = await ejecutar(item);
        if (res.reintentable) {
          item.intentos = (item.intentos || 0) + 1;
          await guardar(item);
        } else {
          // Se borra también cuando el servidor responde con un error
          // definitivo: reintentar eso mil veces no arregla nada.
          await borrar(item.uuid);
        }
      } catch (e) {
        item.intentos = (item.intentos || 0) + 1;
        await guardar(item);
        // Si falla la red, las siguientes también fallarán: se corta.
        break;
      }
    }

    refrescarIndicador();
  }

  window.Cola = { enviar, reintentar, pendientes: () => tx('readonly', s => s.count()) };

  window.addEventListener('online', reintentar);
  document.addEventListener('DOMContentLoaded', () => {
    refrescarIndicador();
    reintentar();
  });
  // Red intermitente: 'online' no siempre salta cuando la conexión
  // vuelve a medias, así que se reintenta también cada dos minutos.
  setInterval(reintentar, 120000);
})();
