import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/cliente.dart';

/// Un módulo completo en el celular: historia, actividad, quiz y
/// entregable.
///
/// Hasta ahora la app solo listaba módulos y había que abrir el
/// navegador para estudiarlos. El celular es el aparato que los
/// estudiantes sí tienen.
class PantallaModulo extends StatefulWidget {
  const PantallaModulo({
    super.key,
    required this.api,
    required this.moduloId,
    required this.titulo,
  });

  final ClienteApi api;
  final int moduloId;
  final String titulo;

  @override
  State<PantallaModulo> createState() => _PantallaModuloState();
}

class _PantallaModuloState extends State<PantallaModulo> {
  late Future<DetalleModulo> _datos;
  DetalleModulo? _modulo;

  int _paso = 0;                       // índice dentro de la lista de pasos
  final Map<int, int> _respuestas = {}; // pregunta -> opción elegida
  ResultadoQuiz? _resultado;
  bool _enviando = false;
  String? _error;

  // Entregable
  XFile? _foto;
  String _formato = 'ficha';
  bool _subiendo = false;
  bool _terminado = false;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  void _cargar() {
    _datos = widget.api.modulo(widget.moduloId)
      ..then((m) {
        if (!mounted) return;
        setState(() {
          _modulo = m;
          // Retoma donde lo dejó. paso_actual es 1-based; la lista, 0.
          _paso = (m.pasoActual - 1).clamp(0, m.pasos.length - 1);
        });
      });
  }

  Future<void> _avanzar() async {
    final m = _modulo;
    if (m == null) return;

    final actual = m.pasos[_paso];

    // El servidor lleva la cuenta; si falla, se avanza igual en pantalla
    // para no dejar al estudiante encerrado por un problema de red.
    try {
      await widget.api.avanzarPaso(m.id, actual.numero);
    } on ErrorApi catch (e) {
      if (mounted) _aviso(e.mensaje);
    }

    if (!mounted) return;
    if (_paso < m.pasos.length - 1) {
      setState(() => _paso++);
    }
  }

  Future<void> _enviarQuiz(Paso paso) async {
    if (_respuestas.length < paso.preguntas.length) {
      _aviso('Responde todas las preguntas antes de enviar.');
      return;
    }

    setState(() { _enviando = true; _error = null; });
    try {
      final r = await widget.api.enviarQuiz(widget.moduloId, _respuestas);
      if (!mounted) return;
      setState(() => _resultado = r);
    } on ErrorApi catch (e) {
      if (!mounted) return;
      setState(() => _error = e.mensaje);
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  Future<void> _elegirFoto(ImageSource origen) async {
    try {
      final x = await ImagePicker().pickImage(
        source: origen,
        // Se reduce antes de subir: el servidor rechaza más de 5 MB y
        // una foto de celular moderna los pasa de sobra.
        maxWidth: 1600,
        imageQuality: 82,
      );
      if (x != null && mounted) setState(() => _foto = x);
    } catch (e) {
      _aviso('No se pudo abrir la cámara o la galería.');
    }
  }

  Future<void> _subir() async {
    final foto = _foto;
    if (foto == null) {
      _aviso('Toma o elige una foto de tu trabajo.');
      return;
    }

    setState(() { _subiendo = true; _error = null; });
    try {
      await widget.api.subirEntregable(
        moduloId: widget.moduloId,
        formato: _formato,
        rutaArchivo: foto.path,
      );
      await widget.api.avanzarPaso(widget.moduloId, 4);
      if (!mounted) return;
      setState(() => _terminado = true);
    } on ErrorApi catch (e) {
      if (!mounted) return;
      setState(() => _error = e.mensaje);
    } finally {
      if (mounted) setState(() => _subiendo = false);
    }
  }

  void _aviso(String texto) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(texto)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.titulo)),
      body: FutureBuilder<DetalleModulo>(
        future: _datos,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _FalloModulo(
              mensaje: snap.error is ErrorApi
                  ? (snap.error as ErrorApi).mensaje
                  : 'No se pudo cargar el módulo.',
              alReintentar: () async => setState(_cargar),
            );
          }

          final m = _modulo ?? snap.data!;
          if (m.pasos.isEmpty) {
            return const Center(child: Text('Este módulo todavía no tiene contenido.'));
          }
          if (_terminado) return _Celebracion(estrellas: m.estrellas);

          final paso = m.pasos[_paso.clamp(0, m.pasos.length - 1)];

          return Column(
            children: [
              _BarraPasos(pasos: m.pasos, actual: _paso),
              const Divider(height: 1),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(18),
                  child: switch (paso.tipo) {
                    'historia'   => _Historia(paso: paso),
                    'actividad'  => _Actividad(paso: paso),
                    'quiz'       => _Quiz(
                        paso: paso,
                        respuestas: _respuestas,
                        resultado: _resultado,
                        enviando: _enviando,
                        error: _error,
                        alElegir: (p, o) => setState(() => _respuestas[p] = o),
                        alEnviar: () => _enviarQuiz(paso),
                      ),
                    'entregable' => _Entregable(
                        paso: paso,
                        foto: _foto,
                        formato: _formato,
                        subiendo: _subiendo,
                        error: _error,
                        alCambiarFormato: (f) => setState(() => _formato = f),
                        alElegirFoto: _elegirFoto,
                        alSubir: _subir,
                      ),
                    _ => Text('Paso no reconocido: ${paso.tipo}'),
                  },
                ),
              ),
              if (paso.tipo != 'entregable')
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(18, 8, 18, 12),
                    child: SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        // El quiz no deja seguir hasta haberlo enviado:
                        // si no, se saltaría sin responder.
                        onPressed: (paso.tipo == 'quiz' && _resultado == null)
                            ? null
                            : _avanzar,
                        child: Text(paso.tipo == 'quiz'
                            ? 'Continuar al entregable'
                            : 'Continuar'),
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

// ── Barra de pasos ─────────────────────────────────────────

class _BarraPasos extends StatelessWidget {
  const _BarraPasos({required this.pasos, required this.actual});

  final List<Paso> pasos;
  final int actual;

  static const _etiquetas = {
    'historia': 'Historia',
    'actividad': 'Actividad',
    'quiz': 'Quiz',
    'entregable': 'Entrega',
  };

  @override
  Widget build(BuildContext context) {
    final c = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          for (var i = 0; i < pasos.length; i++)
            Expanded(
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 14,
                    backgroundColor: i <= actual ? c.primary : c.surfaceContainerHighest,
                    child: Text(
                      '${i + 1}',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: i <= actual ? c.onPrimary : c.onSurfaceVariant,
                      ),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    _etiquetas[pasos[i].tipo] ?? pasos[i].tipo,
                    style: TextStyle(
                      fontSize: 10.5,
                      color: i <= actual ? c.primary : c.onSurfaceVariant,
                      fontWeight: i == actual ? FontWeight.w700 : FontWeight.w400,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

// ── Pasos ──────────────────────────────────────────────────

class _Historia extends StatelessWidget {
  const _Historia({required this.paso});
  final Paso paso;

  @override
  Widget build(BuildContext context) {
    final pregunta = paso.texto('pregunta_disparadora');
    final conceptos = paso.lista('conceptos_clave');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(paso.texto('narrativa'),
            style: const TextStyle(fontSize: 15.5, height: 1.65)),
        if (conceptos.isNotEmpty) ...[
          const SizedBox(height: 20),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [for (final c in conceptos) Chip(label: Text(c))],
          ),
        ],
        if (pregunta.isNotEmpty) ...[
          const SizedBox(height: 22),
          _Recuadro(
            titulo: 'Para pensar',
            icono: Icons.psychology_outlined,
            hijo: Text(pregunta, style: const TextStyle(fontSize: 15, height: 1.5)),
          ),
        ],
      ],
    );
  }
}

class _Actividad extends StatelessWidget {
  const _Actividad({required this.paso});
  final Paso paso;

  @override
  Widget build(BuildContext context) {
    final materiales = paso.lista('materiales');
    final pasos = paso.lista('instrucciones');
    final minutos = paso.contenido['minutos'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (minutos != null)
          Text('Tiempo estimado: $minutos minutos',
              style: Theme.of(context).textTheme.bodySmall),
        if (materiales.isNotEmpty) ...[
          const SizedBox(height: 14),
          _Recuadro(
            titulo: 'Qué necesitas',
            icono: Icons.inventory_2_outlined,
            hijo: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (final mat in materiales)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text('· $mat'),
                  ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 18),
        for (var i = 0; i < pasos.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 12,
                  child: Text('${i + 1}', style: const TextStyle(fontSize: 11)),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(pasos[i],
                      style: const TextStyle(fontSize: 15, height: 1.5)),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _Quiz extends StatelessWidget {
  const _Quiz({
    required this.paso,
    required this.respuestas,
    required this.resultado,
    required this.enviando,
    required this.error,
    required this.alElegir,
    required this.alEnviar,
  });

  final Paso paso;
  final Map<int, int> respuestas;
  final ResultadoQuiz? resultado;
  final bool enviando;
  final String? error;
  final void Function(int pregunta, int opcion) alElegir;
  final VoidCallback alEnviar;

  @override
  Widget build(BuildContext context) {
    final r = resultado;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (r != null)
          _Recuadro(
            titulo: r.correctas == r.total ? '¡Perfecto!' : 'Resultado',
            icono: Icons.emoji_events_outlined,
            hijo: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${r.correctas} de ${r.total} correctas',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                Row(children: [
                  for (var i = 0; i < 3; i++)
                    Icon(i < r.estrellas ? Icons.star : Icons.star_border,
                        color: Colors.amber, size: 26),
                ]),
              ],
            ),
          ),
        for (final p in paso.preguntas) ...[
          const SizedBox(height: 18),
          Text(p.texto,
              style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          for (var i = 0; i < p.opciones.length; i++)
            RadioListTile<int>(
              value: i,
              groupValue: respuestas[p.id],
              // Una vez enviado no se puede cambiar: reenviar contaría
              // otro intento y ya se vio la respuesta.
              onChanged: r != null ? null : (v) => alElegir(p.id, v!),
              title: Text(p.opciones[i]),
              contentPadding: EdgeInsets.zero,
              dense: true,
            ),
        ],
        if (error != null) ...[
          const SizedBox(height: 14),
          Text(error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
        ],
        if (r == null) ...[
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: FilledButton.tonal(
              onPressed: enviando ? null : alEnviar,
              child: Text(enviando ? 'Enviando…' : 'Enviar respuestas'),
            ),
          ),
        ],
      ],
    );
  }
}

class _Entregable extends StatelessWidget {
  const _Entregable({
    required this.paso,
    required this.foto,
    required this.formato,
    required this.subiendo,
    required this.error,
    required this.alCambiarFormato,
    required this.alElegirFoto,
    required this.alSubir,
  });

  final Paso paso;
  final XFile? foto;
  final String formato;
  final bool subiendo;
  final String? error;
  final void Function(String) alCambiarFormato;
  final Future<void> Function(ImageSource) alElegirFoto;
  final VoidCallback alSubir;

  static const _formatos = {
    'ficha': 'Ficha',
    'dibujo_cientifico': 'Dibujo científico',
    'mural_digital': 'Mural',
    'cuento_ilustrado': 'Cuento ilustrado',
    'prototipo': 'Prototipo',
    'otro': 'Otro',
  };

  @override
  Widget build(BuildContext context) {
    // El servidor solo admite los formatos de su ENUM; si el módulo
    // declara otros, se ignoran en vez de fallar al subir.
    final permitidos = paso
        .lista('formatos')
        .where(_formatos.containsKey)
        .toList();
    final opciones = permitidos.isEmpty ? _formatos.keys.toList() : permitidos;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(paso.texto('consigna'),
            style: const TextStyle(fontSize: 15.5, height: 1.6)),
        const SizedBox(height: 18),
        Text('¿Qué formato entregas?',
            style: Theme.of(context).textTheme.labelLarge),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          children: [
            for (final f in opciones)
              ChoiceChip(
                label: Text(_formatos[f] ?? f),
                selected: formato == f,
                onSelected: (_) => alCambiarFormato(f),
              ),
          ],
        ),
        const SizedBox(height: 20),
        if (foto != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(File(foto!.path), height: 200, fit: BoxFit.cover),
          ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: subiendo ? null : () => alElegirFoto(ImageSource.camera),
                icon: const Icon(Icons.photo_camera_outlined),
                label: const Text('Tomar foto'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: subiendo ? null : () => alElegirFoto(ImageSource.gallery),
                icon: const Icon(Icons.photo_library_outlined),
                label: const Text('Galería'),
              ),
            ),
          ],
        ),
        if (error != null) ...[
          const SizedBox(height: 14),
          Text(error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
        ],
        const SizedBox(height: 18),
        SizedBox(
          width: double.infinity,
          child: FilledButton(
            onPressed: (subiendo || foto == null) ? null : alSubir,
            child: Text(subiendo ? 'Subiendo…' : 'Entregar y completar'),
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

// ── Piezas compartidas ─────────────────────────────────────

class _Recuadro extends StatelessWidget {
  const _Recuadro({required this.titulo, required this.icono, required this.hijo});

  final String titulo;
  final IconData icono;
  final Widget hijo;

  @override
  Widget build(BuildContext context) {
    final c = Theme.of(context).colorScheme;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: c.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(icono, size: 17, color: c.primary),
            const SizedBox(width: 7),
            Text(titulo,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    letterSpacing: .4,
                    color: c.primary)),
          ]),
          const SizedBox(height: 10),
          hijo,
        ],
      ),
    );
  }
}

class _Celebracion extends StatelessWidget {
  const _Celebracion({required this.estrellas});
  final int estrellas;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.celebration_outlined, size: 64, color: Colors.amber),
            const SizedBox(height: 18),
            Text('¡Módulo completado!',
                style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 10),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var i = 0; i < 3; i++)
                  Icon(i < estrellas ? Icons.star : Icons.star_border,
                      color: Colors.amber, size: 32),
              ],
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Volver al curso'),
            ),
          ],
        ),
      ),
    );
  }
}

class _FalloModulo extends StatelessWidget {
  const _FalloModulo({required this.mensaje, required this.alReintentar});

  final String mensaje;
  final Future<void> Function() alReintentar;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_outlined, size: 44),
            const SizedBox(height: 14),
            Text(mensaje, textAlign: TextAlign.center),
            const SizedBox(height: 18),
            OutlinedButton(onPressed: alReintentar, child: const Text('Reintentar')),
          ],
        ),
      ),
    );
  }
}
