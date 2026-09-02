import 'dart:ui' show FontFeature;

import 'package:flutter/material.dart';

import '../api/cliente.dart';
import 'login.dart';

class PantallaHijos extends StatefulWidget {
  const PantallaHijos({super.key, required this.api, required this.usuario});

  final ClienteApi api;
  final Usuario usuario;

  @override
  State<PantallaHijos> createState() => _PantallaHijosState();
}

class _PantallaHijosState extends State<PantallaHijos> {
  late Future<List<Hijo>> _datos;

  @override
  void initState() {
    super.initState();
    _datos = widget.api.hijos();
  }

  Future<void> _recargar() async {
    final peticion = widget.api.hijos();
    setState(() => _datos = peticion);
    try {
      await peticion;
    } catch (_) {
      // El FutureBuilder ya lo muestra.
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
        title: const Text('Mis hijos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Cerrar sesión',
            onPressed: _salir,
          ),
        ],
      ),
      body: FutureBuilder<List<Hijo>>(
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
                          : 'No se pudo cargar la información.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 18),
                    OutlinedButton(
                      onPressed: _recargar,
                      child: const Text('Reintentar'),
                    ),
                  ],
                ),
              ),
            );
          }

          final hijos = snap.data!;
          if (hijos.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(28),
                child: Text(
                  'Todavía no hay estudiantes vinculados a tu cuenta.\n'
                  'Pídelo en la dirección del colegio.',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: _recargar,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: hijos.length,
              itemBuilder: (context, i) => _TarjetaHijo(hijo: hijos[i]),
            ),
          );
        },
      ),
    );
  }
}

class _TarjetaHijo extends StatelessWidget {
  const _TarjetaHijo({required this.hijo});

  final Hijo hijo;

  /// Traduce los días sin entrar a algo que un padre entienda de un
  /// vistazo, con el color como segunda señal además del texto.
  (String, Color) _actividad(BuildContext context) {
    final colores = Theme.of(context).colorScheme;
    final dias = hijo.diasSinEntrar;

    if (dias == null) return ('Nunca ha entrado', colores.error);
    if (dias <= 1) return ('Entró hoy', const Color(0xFF11864F));
    if (dias < 7) return ('Hace $dias días', colores.onSurfaceVariant);
    return ('Sin entrar hace $dias días', const Color(0xFFA85A00));
  }

  @override
  Widget build(BuildContext context) {
    final (texto, color) = _actividad(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  child: Text(
                    hijo.nombre.isNotEmpty
                        ? hijo.nombre.substring(0, 1).toUpperCase()
                        : '?',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        hijo.nombre,
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      Text(
                        texto,
                        style: TextStyle(
                          fontSize: 12.5,
                          color: color,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Row(
              children: [
                _Metrica(valor: hijo.completados, etiqueta: 'Módulos'),
                _Metrica(valor: hijo.estrellas, etiqueta: 'Estrellas'),
                _Metrica(valor: hijo.logros, etiqueta: 'Logros'),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Metrica extends StatelessWidget {
  const _Metrica({required this.valor, required this.etiqueta});

  final int valor;
  final String etiqueta;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(
            '$valor',
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              fontFeatures: [FontFeature.tabularFigures()],
            ),
          ),
          Text(
            etiqueta,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }
}
