import 'package:flutter/material.dart';

import '../api/cliente.dart';

/// Asistencia desde el celular, para el practicante.
///
/// Es el rol que trabaja de pie en el aula: pedirle que abra una
/// computadora para marcar quién vino no tiene sentido. El celular ya
/// lo lleva encima.
class PantallaAsistencia extends StatefulWidget {
  const PantallaAsistencia({
    super.key,
    required this.api,
    required this.usuario,
  });

  final ClienteApi api;
  final Usuario usuario;

  @override
  State<PantallaAsistencia> createState() => _PantallaAsistenciaState();
}

class _PantallaAsistenciaState extends State<PantallaAsistencia> {
  late Future<DatosAsistencia> _datos;

  AulaPracticante? _aula;
  ModuloCatalogo? _modulo;
  DateTime _fecha = DateTime.now();

  /// Quién está presente. Se arranca con todos marcados: en un aula
  /// normal faltan dos o tres, así que se desmarca menos de lo que se
  /// marcaría al revés.
  final Set<int> _presentes = {};
  final _notas = TextEditingController();

  bool _guardando = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  void _cargar() {
    _datos = widget.api.aulas()
      ..then((d) {
        if (!mounted || d.aulas.isEmpty) return;
        setState(() {
          _aula = d.aulas.first;
          _modulo = d.modulos.isNotEmpty ? d.modulos.first : null;
          _presentes
            ..clear()
            ..addAll(d.aulas.first.estudiantes.map((e) => e.id));
        });
      });
  }

  @override
  void dispose() {
    _notas.dispose();
    super.dispose();
  }

  String get _fechaIso =>
      '${_fecha.year.toString().padLeft(4, '0')}-'
      '${_fecha.month.toString().padLeft(2, '0')}-'
      '${_fecha.day.toString().padLeft(2, '0')}';

  Future<void> _guardar() async {
    final aula = _aula, modulo = _modulo;
    if (aula == null || modulo == null) {
      setState(() => _error = 'Elige el aula y el módulo trabajado.');
      return;
    }

    setState(() { _guardando = true; _error = null; });
    try {
      final n = await widget.api.registrarAsistencia(
        aulaId: aula.id,
        moduloId: modulo.id,
        fecha: _fechaIso,
        presentes: _presentes.toList(),
        notas: _notas.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sesión registrada con $n presentes.')),
      );
      _notas.clear();
    } on ErrorApi catch (e) {
      if (!mounted) return;
      setState(() => _error = e.mensaje);
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Asistencia'),
        actions: [
          IconButton(
            tooltip: 'Cerrar sesión',
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await widget.api.cerrarSesion();
              if (context.mounted) Navigator.of(context).pop();
            },
          ),
        ],
      ),
      body: FutureBuilder<DatosAsistencia>(
        future: _datos,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(28),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.cloud_off_outlined, size: 44),
                    const SizedBox(height: 14),
                    Text(
                      snap.error is ErrorApi
                          ? (snap.error as ErrorApi).mensaje
                          : 'No se pudieron cargar tus aulas.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 18),
                    OutlinedButton(
                      onPressed: () => setState(_cargar),
                      child: const Text('Reintentar'),
                    ),
                  ],
                ),
              ),
            );
          }

          final d = snap.data!;
          if (d.aulas.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(28),
                child: Text(
                  'Todavía no tienes aulas asignadas. Habla con el '
                  'administrador de tu colegio.',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          final aula = _aula ?? d.aulas.first;
          final total = aula.estudiantes.length;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (d.aulas.length > 1)
                DropdownButtonFormField<int>(
                  value: aula.id,
                  decoration: const InputDecoration(labelText: 'Aula'),
                  items: [
                    for (final a in d.aulas)
                      DropdownMenuItem(value: a.id, child: Text(a.nombre)),
                  ],
                  onChanged: (id) {
                    final nueva = d.aulas.firstWhere((a) => a.id == id);
                    setState(() {
                      _aula = nueva;
                      _presentes
                        ..clear()
                        ..addAll(nueva.estudiantes.map((e) => e.id));
                    });
                  },
                )
              else
                Text(aula.nombre,
                    style: Theme.of(context).textTheme.titleMedium),

              const SizedBox(height: 14),
              DropdownButtonFormField<int>(
                value: _modulo?.id,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'Módulo trabajado'),
                items: [
                  for (final m in d.modulos)
                    DropdownMenuItem(
                      value: m.id,
                      child: Text('${m.curso} · ${m.titulo}',
                          overflow: TextOverflow.ellipsis),
                    ),
                ],
                onChanged: (id) => setState(
                    () => _modulo = d.modulos.firstWhere((m) => m.id == id)),
              ),

              const SizedBox(height: 14),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.event_outlined),
                title: const Text('Fecha de la sesión'),
                subtitle: Text(_fechaIso),
                trailing: TextButton(
                  child: const Text('Cambiar'),
                  onPressed: () async {
                    final elegida = await showDatePicker(
                      context: context,
                      initialDate: _fecha,
                      // No se registran sesiones del futuro; hacia atrás,
                      // un mes cubre cualquier registro pendiente.
                      firstDate: DateTime.now().subtract(const Duration(days: 31)),
                      lastDate: DateTime.now(),
                    );
                    if (elegida != null) setState(() => _fecha = elegida);
                  },
                ),
              ),

              const Divider(height: 26),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${_presentes.length} de $total presentes',
                      style: Theme.of(context).textTheme.titleMedium),
                  TextButton(
                    onPressed: () => setState(() {
                      if (_presentes.length == total) {
                        _presentes.clear();
                      } else {
                        _presentes
                          ..clear()
                          ..addAll(aula.estudiantes.map((e) => e.id));
                      }
                    }),
                    child: Text(_presentes.length == total
                        ? 'Desmarcar todos'
                        : 'Marcar todos'),
                  ),
                ],
              ),

              for (final e in aula.estudiantes)
                CheckboxListTile(
                  value: _presentes.contains(e.id),
                  title: Text(e.nombre),
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  onChanged: (v) => setState(() {
                    if (v == true) {
                      _presentes.add(e.id);
                    } else {
                      _presentes.remove(e.id);
                    }
                  }),
                ),

              const SizedBox(height: 14),
              TextField(
                controller: _notas,
                maxLines: 3,
                maxLength: 500,
                decoration: const InputDecoration(
                  labelText: 'Notas de la sesión (opcional)',
                  alignLabelWithHint: true,
                ),
              ),

              if (_error != null) ...[
                const SizedBox(height: 8),
                Text(_error!,
                    style: TextStyle(color: Theme.of(context).colorScheme.error)),
              ],

              const SizedBox(height: 12),
              FilledButton(
                onPressed: _guardando ? null : _guardar,
                child: Text(_guardando ? 'Guardando…' : 'Registrar sesión'),
              ),
              const SizedBox(height: 30),
            ],
          );
        },
      ),
    );
  }
}
