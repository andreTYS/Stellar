import 'package:flutter/material.dart';

import 'api/cliente.dart';
import 'pantallas/login.dart';
import 'pantallas/inicio_estudiante.dart';
import 'pantallas/hijos_apoderado.dart';

/// Servidor al que se conecta la app.
///
/// Se puede cambiar sin tocar el código:
///   flutter run --dart-define=API_URL=http://192.168.1.42/innovasteam
///
/// El valor por defecto sirve para el emulador de Android, donde
/// 10.0.2.2 es el ordenador anfitrión (localhost apuntaría al propio
/// emulador). En el simulador de iOS usa http://localhost/innovasteam.
const String kApiUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'http://10.0.2.2/innovasteam',
);

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final api = ClienteApi(baseUrl: kApiUrl);
  await api.cargarSesion();

  runApp(AppInnovaSteam(api: api));
}

class AppInnovaSteam extends StatelessWidget {
  const AppInnovaSteam({super.key, required this.api});

  final ClienteApi api;

  // Mismo azul que el sistema de diseño de la web (--accent).
  static const Color azul = Color(0xFF4361EE);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'INNOVA-STEAM',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: azul),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: azul,
          brightness: Brightness.dark,
        ),
      ),
      home: Arranque(api: api),
    );
  }
}

/// Decide la primera pantalla: si hay un token guardado se comprueba
/// contra el servidor antes de confiar en él, porque pudo caducar o
/// haber sido revocado desde otro dispositivo.
class Arranque extends StatefulWidget {
  const Arranque({super.key, required this.api});

  final ClienteApi api;

  @override
  State<Arranque> createState() => _ArranqueState();
}

class _ArranqueState extends State<Arranque> {
  late Future<Usuario?> _sesion;

  @override
  void initState() {
    super.initState();
    _sesion = _comprobar();
  }

  Future<Usuario?> _comprobar() async {
    if (!widget.api.haySesion) return null;
    try {
      return await widget.api.yo();
    } on ErrorApi catch (e) {
      // Token inválido: se descarta y se pide login otra vez.
      if (e.requiereLogin) await widget.api.cerrarSesion();
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Usuario?>(
      future: _sesion,
      builder: (context, snap) {
        if (snap.connectionState != ConnectionState.done) {
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }
        final usuario = snap.data;
        if (usuario == null) {
          return PantallaLogin(api: widget.api);
        }
        return pantallaParaRol(widget.api, usuario);
      },
    );
  }
}

/// Cada rol entra a su propia pantalla. La app cubre estudiante y
/// apoderado; los demás roles siguen usando la web, donde una pantalla
/// grande es una ventaja para calificar y ver reportes.
Widget pantallaParaRol(ClienteApi api, Usuario usuario) {
  switch (usuario.rol) {
    case 'estudiante':
      return PantallaInicioEstudiante(api: api, usuario: usuario);
    case 'apoderado':
      return PantallaHijos(api: api, usuario: usuario);
    default:
      return PantallaRolNoSoportado(api: api, usuario: usuario);
  }
}

class PantallaRolNoSoportado extends StatelessWidget {
  const PantallaRolNoSoportado({
    super.key,
    required this.api,
    required this.usuario,
  });

  final ClienteApi api;
  final Usuario usuario;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('INNOVA-STEAM')),
      body: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.desktop_windows_outlined, size: 52),
            const SizedBox(height: 18),
            Text(
              'La app es para estudiantes y apoderados',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              'Tu cuenta es de ${usuario.rol}. Entra desde el navegador '
              'para gestionar aulas, calificar y ver reportes.',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 24),
            OutlinedButton(
              onPressed: () async {
                await api.cerrarSesion();
                if (context.mounted) {
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(builder: (_) => PantallaLogin(api: api)),
                  );
                }
              },
              child: const Text('Cerrar sesión'),
            ),
          ],
        ),
      ),
    );
  }
}
