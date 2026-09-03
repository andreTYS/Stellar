import 'package:flutter/material.dart';

import '../api/cliente.dart';
import '../main.dart' show pantallaParaRol;

class PantallaLogin extends StatefulWidget {
  const PantallaLogin({super.key, required this.api});

  final ClienteApi api;

  @override
  State<PantallaLogin> createState() => _PantallaLoginState();
}

class _PantallaLoginState extends State<PantallaLogin> {
  final _formulario = GlobalKey<FormState>();
  final _usuario = TextEditingController();
  final _password = TextEditingController();

  bool _cargando = false;
  bool _probando = false;
  bool _verPassword = false;
  String? _error;
  String? _aviso;

  /// Comprueba que el servidor responde, sin usar credenciales. Separa
  /// "no llego al servidor" de "usuario o contraseña mal", que es la
  /// duda habitual cuando la app no entra.
  Future<void> _probarConexion() async {
    setState(() {
      _probando = true;
      _error = null;
      _aviso = null;
    });
    try {
      final resultado = await widget.api.probarConexion();
      if (mounted) setState(() => _aviso = resultado);
    } on ErrorApi catch (e) {
      if (mounted) setState(() => _error = e.mensaje);
    } finally {
      if (mounted) setState(() => _probando = false);
    }
  }

  @override
  void dispose() {
    _usuario.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _entrar() async {
    if (!_formulario.currentState!.validate()) return;

    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final usuario = await widget.api.iniciarSesion(
        _usuario.text.trim(),
        _password.text,
      );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => pantallaParaRol(widget.api, usuario)),
      );
    } on ErrorApi catch (e) {
      // El servidor ya devuelve mensajes listos para mostrar, incluido
      // el del bloqueo por intentos fallidos.
      if (mounted) setState(() => _error = e.mensaje);
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colores = Theme.of(context).colorScheme;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Form(
                key: _formulario,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Container(
                      height: 62,
                      width: 62,
                      decoration: BoxDecoration(
                        color: colores.primary,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      alignment: Alignment.center,
                      child: const Text(
                        'IS',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(height: 22),
                    Text(
                      'INNOVA-STEAM',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Educación STEAM — Moquegua',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: colores.onSurfaceVariant,
                          ),
                    ),
                    const SizedBox(height: 32),

                    TextFormField(
                      controller: _usuario,
                      autocorrect: false,
                      textInputAction: TextInputAction.next,
                      decoration: const InputDecoration(
                        labelText: 'Correo o código',
                        hintText: 'EST-001',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (v) => (v == null || v.trim().isEmpty)
                          ? 'Escribe tu correo o tu código'
                          : null,
                    ),
                    const SizedBox(height: 14),

                    TextFormField(
                      controller: _password,
                      obscureText: !_verPassword,
                      textInputAction: TextInputAction.done,
                      onFieldSubmitted: (_) => _cargando ? null : _entrar(),
                      decoration: InputDecoration(
                        labelText: 'Contraseña',
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_verPassword
                              ? Icons.visibility_off_outlined
                              : Icons.visibility_outlined),
                          tooltip: _verPassword ? 'Ocultar' : 'Mostrar',
                          onPressed: () =>
                              setState(() => _verPassword = !_verPassword),
                        ),
                      ),
                      validator: (v) => (v == null || v.isEmpty)
                          ? 'Escribe tu contraseña'
                          : null,
                    ),

                    if (_error != null) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: colores.errorContainer,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(Icons.error_outline,
                                size: 19, color: colores.onErrorContainer),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                _error!,
                                style: TextStyle(
                                    color: colores.onErrorContainer,
                                    fontSize: 13.5,
                                    height: 1.45),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    if (_aviso != null) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: colores.secondaryContainer,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.check_circle_outline,
                                size: 19, color: colores.onSecondaryContainer),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                _aviso!,
                                style: TextStyle(
                                    color: colores.onSecondaryContainer,
                                    fontSize: 13.5),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _cargando ? null : _entrar,
                      style: FilledButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 15),
                      ),
                      child: _cargando
                          ? const SizedBox(
                              height: 19,
                              width: 19,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white),
                            )
                          : const Text('Entrar'),
                    ),

                    // Diagnóstico: la causa habitual de que la app no
                    // entre es que apunte a una URL que no responde, y
                    // sin verla escrita no hay forma de darse cuenta.
                    const SizedBox(height: 20),
                    Text(
                      'Servidor configurado',
                      style: TextStyle(
                          fontSize: 11,
                          letterSpacing: .5,
                          color: colores.onSurfaceVariant),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 2),
                    SelectableText(
                      widget.api.baseUrl,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 12,
                        fontFamily: 'monospace',
                        color: colores.onSurfaceVariant,
                      ),
                    ),
                    TextButton(
                      onPressed: _probando ? null : _probarConexion,
                      child: Text(_probando
                          ? 'Probando…'
                          : 'Probar conexión con el servidor'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
