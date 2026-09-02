import 'dart:ui' show FontFeature;

import 'package:flutter/material.dart';

import '../api/cliente.dart';
import 'login.dart';

/// Convierte '#4361ee' del backend en un Color de Flutter.
Color colorDesdeHex(String hex) {
  final limpio = hex.replaceFirst('#', '');
  final valor = int.tryParse(limpio, radix: 16);
  if (valor == null) return const Color(0xFF4361EE);
  return Color(limpio.length == 6 ? 0xFF000000 | valor : valor);
}

class PantallaInicioEstudiante extends StatefulWidget {
  const PantallaInicioEstudiante({
    super.key,
    required this.api,
    required this.usuario,
  });

  final ClienteApi api;
  final Usuario usuario;

  @override
  State<PantallaInicioEstudiante> createState() =>
      _PantallaInicioEstudianteState();
}

class _PantallaInicioEstudianteState extends State<PantallaInicioEstudiante> {
  late Future<Inicio> _datos;

  @override
  void initState() {
    super.initState();
    _datos = widget.api.inicio();
  }

  Future<void> _recargar() async {
    final peticion = widget.api.inicio();
    setState(() => _datos = peticion);
    // RefreshIndicator necesita esperar a que termine para ocultar el
    // indicador. El error ya lo pinta el FutureBuilder, así que aquí se
    // absorbe: volver a lanzarlo lo dejaría sin capturar.
    try {
      await peticion;
    } catch (_) {
      // Mostrado por el FutureBuilder.
    }
  }

  Future<void> _salir() async {
    await widget.api.cerrarSesion();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => PantallaLogin(api: widget.api)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mis cursos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Cerrar sesión',
            onPressed: _salir,
          ),
        ],
      ),
      body: FutureBuilder<Inicio>(
        future: _datos,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _Fallo(
              mensaje: snap.error is ErrorApi
                  ? (snap.error as ErrorApi).mensaje
                  : 'No se pudieron cargar tus cursos.',
              alReintentar: _recargar,
            );
          }

          final inicio = snap.data!;
          return RefreshIndicator(
            onRefresh: _recargar,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                _Cabecera(inicio: inicio),
                const SizedBox(height: 20),
                Text('Cursos', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 10),
                ...inicio.cursos.map((c) => _TarjetaCurso(api: widget.api, curso: c)),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _Cabecera extends StatelessWidget {
  const _Cabecera({required this.inicio});

  final Inicio inicio;

  @override
  Widget build(BuildContext context) {
    final colores = Theme.of(context).colorScheme;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: colores.primaryContainer,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '¡Hola, ${inicio.usuario.nombre}!',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: colores.onPrimaryContainer,
                ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              _Dato(
                valor: '${inicio.progresoPct}%',
                etiqueta: 'Progreso',
                color: colores.onPrimaryContainer,
              ),
              _Dato(
                valor: '${inicio.estrellas}',
                etiqueta: 'Estrellas',
                color: colores.onPrimaryContainer,
              ),
              _Dato(
                valor: '${inicio.modulosCompletados}/${inicio.modulosTotales}',
                etiqueta: 'Módulos',
                color: colores.onPrimaryContainer,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Dato extends StatelessWidget {
  const _Dato({required this.valor, required this.etiqueta, required this.color});

  final String valor;
  final String etiqueta;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            valor,
            style: TextStyle(
              fontSize: 21,
              fontWeight: FontWeight.w800,
              color: color,
              fontFeatures: const [FontFeature.tabularFigures()],
            ),
          ),
          Text(
            etiqueta,
            style: TextStyle(fontSize: 12, color: color.withValues(alpha: .75)),
          ),
        ],
      ),
    );
  }
}

class _TarjetaCurso extends StatelessWidget {
  const _TarjetaCurso({required this.api, required this.curso});

  final ClienteApi api;
  final Curso curso;

  @override
  Widget build(BuildContext context) {
    final color = colorDesdeHex(curso.color);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => PantallaCurso(api: api, cursoId: curso.id),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    height: 40,
                    width: 40,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      curso.nombre.isNotEmpty
                          ? curso.nombre.substring(0, 1).toUpperCase()
                          : '?',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      curso.nombre,
                      style: Theme.of(context)
                          .textTheme
                          .titleSmall
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  Text(
                    '${curso.progresoPct}%',
                    style: TextStyle(
                      color: color,
                      fontWeight: FontWeight.w700,
                      fontFeatures: const [FontFeature.tabularFigures()],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: curso.progresoPct / 100,
                  minHeight: 6,
                  color: color,
                  backgroundColor: color.withValues(alpha: .15),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                '${curso.completados} de ${curso.modulos} módulos',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Lista de módulos de un curso, con el bloqueo por avance que ya
/// aplica la web: cada módulo se abre al completar el anterior.
class PantallaCurso extends StatefulWidget {
  const PantallaCurso({super.key, required this.api, required this.cursoId});

  final ClienteApi api;
  final int cursoId;

  @override
  State<PantallaCurso> createState() => _PantallaCursoState();
}

class _PantallaCursoState extends State<PantallaCurso> {
  late Future<DetalleCurso> _datos;

  @override
  void initState() {
    super.initState();
    _datos = widget.api.curso(widget.cursoId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Módulos')),
      body: FutureBuilder<DetalleCurso>(
        future: _datos,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _Fallo(
              mensaje: snap.error is ErrorApi
                  ? (snap.error as ErrorApi).mensaje
                  : 'No se pudo cargar el curso.',
              alReintentar: () async =>
                  setState(() => _datos = widget.api.curso(widget.cursoId)),
            );
          }

          final detalle = snap.data!;
          final color = colorDesdeHex(detalle.curso.color);

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: detalle.modulos.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, i) {
              final m = detalle.modulos[i];
              return ListTile(
                tileColor: Theme.of(context).colorScheme.surfaceContainerHighest,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                enabled: m.desbloqueado,
                leading: CircleAvatar(
                  backgroundColor: m.completado
                      ? color
                      : color.withValues(alpha: m.desbloqueado ? .22 : .10),
                  child: m.completado
                      ? const Icon(Icons.check, color: Colors.white, size: 19)
                      : m.desbloqueado
                          ? Text('${m.numero}',
                              style: TextStyle(
                                  color: color, fontWeight: FontWeight.w700))
                          : const Icon(Icons.lock_outline, size: 17),
                ),
                title: Text(
                  m.titulo,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: m.desbloqueado ? null : Theme.of(context).disabledColor,
                  ),
                ),
                subtitle: Text(
                  m.completado
                      ? 'Completado · ${m.estrellas}/3 estrellas'
                      : m.enProgreso
                          ? 'En progreso'
                          : m.desbloqueado
                              ? 'Sin empezar'
                              : 'Completa el módulo anterior',
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _Fallo extends StatelessWidget {
  const _Fallo({required this.mensaje, required this.alReintentar});

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
            OutlinedButton(
              onPressed: alReintentar,
              child: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }
}
