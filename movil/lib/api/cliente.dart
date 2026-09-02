import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Error que la app puede mostrar tal cual al usuario.
class ErrorApi implements Exception {
  final String mensaje;
  final int codigo;

  const ErrorApi(this.mensaje, this.codigo);

  /// El token caducó o fue revocado: hay que volver a la pantalla de login.
  bool get requiereLogin => codigo == 401;

  @override
  String toString() => mensaje;
}

/// Cliente de la API de INNOVA-STEAM.
///
/// Guarda el token en el dispositivo y lo añade a cada petición como
/// `Authorization: Bearer`. La web usa cookies de sesión; aquí no
/// servirían, y por eso el backend tiene una capa aparte.
class ClienteApi {
  ClienteApi({required this.baseUrl, http.Client? cliente})
      : _http = cliente ?? http.Client();

  /// Raíz del servidor, sin barra final.
  ///
  /// En desarrollo local depende de dónde corre la app:
  ///
  ///   emulador Android   http://10.0.2.2/innovasteam
  ///   simulador iOS      http://localhost/innovasteam
  ///   móvil real         http://192.168.x.x/innovasteam  (IP del PC en la red)
  ///
  /// `localhost` desde un emulador Android apunta al propio emulador,
  /// no al ordenador: es el error más común al conectar por primera vez.
  final String baseUrl;

  final http.Client _http;

  static const _claveToken = 'innovasteam_token';
  String? _token;

  String? get token => _token;
  bool get haySesion => _token != null;

  /// Recupera el token guardado. Llamar al arrancar la app.
  Future<void> cargarSesion() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_claveToken);
  }

  Future<void> _guardarToken(String? valor) async {
    _token = valor;
    final prefs = await SharedPreferences.getInstance();
    if (valor == null) {
      await prefs.remove(_claveToken);
    } else {
      await prefs.setString(_claveToken, valor);
    }
  }

  Map<String, String> get _cabeceras => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  /// Decodifica la respuesta y convierte los errores del servidor en
  /// ErrorApi, para que las pantallas no tengan que mirar códigos HTTP.
  Map<String, dynamic> _procesar(http.Response r) {
    late final Map<String, dynamic> datos;
    try {
      datos = jsonDecode(utf8.decode(r.bodyBytes)) as Map<String, dynamic>;
    } on FormatException {
      // Si llega HTML en vez de JSON suele ser una URL mal formada
      // apuntando a una página web en lugar de a la API.
      throw ErrorApi(
        'El servidor no devolvió JSON. Revisa que baseUrl apunte a la '
        'instalación correcta.',
        r.statusCode,
      );
    }

    if (r.statusCode >= 400 || datos['ok'] != true) {
      throw ErrorApi(
        datos['error'] as String? ?? 'Error inesperado del servidor.',
        r.statusCode,
      );
    }
    return datos;
  }

  Future<Map<String, dynamic>> _get(String ruta) async {
    try {
      final r = await _http
          .get(Uri.parse('$baseUrl/api/$ruta'), headers: _cabeceras)
          .timeout(const Duration(seconds: 20));
      return _procesar(r);
    } on SocketException {
      throw const ErrorApi('Sin conexión con el servidor.', 0);
    }
  }

  Future<Map<String, dynamic>> _post(String ruta, Map<String, dynamic> cuerpo) async {
    try {
      final r = await _http
          .post(Uri.parse('$baseUrl/api/$ruta'),
              headers: _cabeceras, body: jsonEncode(cuerpo))
          .timeout(const Duration(seconds: 20));
      return _procesar(r);
    } on SocketException {
      throw const ErrorApi('Sin conexión con el servidor.', 0);
    }
  }

  // ── Sesión ───────────────────────────────────────────────

  /// `usuario` acepta el correo o el código de acceso (por ejemplo EST-001),
  /// igual que el formulario de la web.
  Future<Usuario> iniciarSesion(String usuario, String password) async {
    final datos = await _post('auth.php?accion=login', {
      'usuario': usuario,
      'password': password,
      'dispositivo': Platform.operatingSystem,
    });
    await _guardarToken(datos['token'] as String);
    return Usuario.desdeJson(datos['usuario'] as Map<String, dynamic>);
  }

  Future<Usuario> yo() async {
    final datos = await _get('auth.php?accion=yo');
    return Usuario.desdeJson(datos['usuario'] as Map<String, dynamic>);
  }

  Future<void> cerrarSesion() async {
    try {
      await _post('auth.php?accion=logout', const {});
    } on ErrorApi {
      // Si el token ya no vale, cerrar sesión en local igualmente.
    }
    await _guardarToken(null);
  }

  // ── Datos ────────────────────────────────────────────────

  Future<Inicio> inicio() async =>
      Inicio.desdeJson(await _get('movil.php?recurso=inicio'));

  Future<DetalleCurso> curso(int id) async =>
      DetalleCurso.desdeJson(await _get('movil.php?recurso=curso&id=$id'));

  Future<List<Hijo>> hijos() async {
    final datos = await _get('movil.php?recurso=hijos');
    return (datos['hijos'] as List)
        .map((h) => Hijo.desdeJson(h as Map<String, dynamic>))
        .toList();
  }
}

// ── Modelos ────────────────────────────────────────────────
// Se construyen a mano en vez de con generación de código: son pocos
// y así el proyecto no necesita build_runner para compilar.

class Usuario {
  final int id;
  final String nombre;
  final String apellido;
  final String rol;
  final String? codigo;

  const Usuario({
    required this.id,
    required this.nombre,
    required this.apellido,
    required this.rol,
    this.codigo,
  });

  String get nombreCompleto => '$nombre $apellido'.trim();

  factory Usuario.desdeJson(Map<String, dynamic> j) => Usuario(
        id: j['id'] as int,
        nombre: j['nombre'] as String? ?? '',
        apellido: j['apellido'] as String? ?? '',
        rol: j['rol'] as String? ?? '',
        codigo: j['codigo'] as String?,
      );
}

class Curso {
  final int id;
  final String nombre;
  final String? descripcion;
  final String color;
  final int modulos;
  final int completados;
  final int progresoPct;

  const Curso({
    required this.id,
    required this.nombre,
    required this.descripcion,
    required this.color,
    required this.modulos,
    required this.completados,
    required this.progresoPct,
  });

  factory Curso.desdeJson(Map<String, dynamic> j) => Curso(
        id: j['id'] as int,
        nombre: j['nombre'] as String? ?? '',
        descripcion: j['descripcion'] as String?,
        color: j['color'] as String? ?? '#4361ee',
        modulos: j['modulos'] as int? ?? 0,
        completados: j['completados'] as int? ?? 0,
        progresoPct: j['progreso_pct'] as int? ?? 0,
      );
}

class Inicio {
  final Usuario usuario;
  final int estrellas;
  final int modulosTotales;
  final int modulosCompletados;
  final int progresoPct;
  final List<Curso> cursos;

  const Inicio({
    required this.usuario,
    required this.estrellas,
    required this.modulosTotales,
    required this.modulosCompletados,
    required this.progresoPct,
    required this.cursos,
  });

  factory Inicio.desdeJson(Map<String, dynamic> j) {
    final resumen = j['resumen'] as Map<String, dynamic>;
    return Inicio(
      usuario: Usuario.desdeJson(j['usuario'] as Map<String, dynamic>),
      estrellas: resumen['estrellas'] as int? ?? 0,
      modulosTotales: resumen['modulos_totales'] as int? ?? 0,
      modulosCompletados: resumen['modulos_completados'] as int? ?? 0,
      progresoPct: resumen['progreso_pct'] as int? ?? 0,
      cursos: (j['cursos'] as List)
          .map((c) => Curso.desdeJson(c as Map<String, dynamic>))
          .toList(),
    );
  }
}

class Modulo {
  final int id;
  final String titulo;
  final int numero;
  final bool completado;
  final bool enProgreso;
  final bool desbloqueado;
  final int estrellas;

  const Modulo({
    required this.id,
    required this.titulo,
    required this.numero,
    required this.completado,
    required this.enProgreso,
    required this.desbloqueado,
    required this.estrellas,
  });

  factory Modulo.desdeJson(Map<String, dynamic> j) => Modulo(
        id: j['id'] as int,
        titulo: j['titulo'] as String? ?? '',
        numero: j['numero'] as int? ?? 0,
        completado: j['completado'] as bool? ?? false,
        enProgreso: j['en_progreso'] as bool? ?? false,
        desbloqueado: j['desbloqueado'] as bool? ?? false,
        estrellas: j['estrellas'] as int? ?? 0,
      );
}

class DetalleCurso {
  final Curso curso;
  final List<Modulo> modulos;

  const DetalleCurso({required this.curso, required this.modulos});

  factory DetalleCurso.desdeJson(Map<String, dynamic> j) {
    final c = j['curso'] as Map<String, dynamic>;
    return DetalleCurso(
      // El endpoint de detalle no repite los contadores del listado.
      curso: Curso(
        id: c['id'] as int,
        nombre: c['nombre'] as String? ?? '',
        descripcion: c['descripcion'] as String?,
        color: c['color'] as String? ?? '#4361ee',
        modulos: 0,
        completados: 0,
        progresoPct: 0,
      ),
      modulos: (j['modulos'] as List)
          .map((m) => Modulo.desdeJson(m as Map<String, dynamic>))
          .toList(),
    );
  }
}

class Hijo {
  final int id;
  final String nombre;
  final String? relacion;
  final int completados;
  final int estrellas;
  final int logros;

  /// null significa que nunca ha entrado a la plataforma.
  final int? diasSinEntrar;

  const Hijo({
    required this.id,
    required this.nombre,
    required this.relacion,
    required this.completados,
    required this.estrellas,
    required this.logros,
    required this.diasSinEntrar,
  });

  factory Hijo.desdeJson(Map<String, dynamic> j) => Hijo(
        id: j['id'] as int,
        nombre: j['nombre'] as String? ?? '',
        relacion: j['relacion'] as String?,
        completados: j['completados'] as int? ?? 0,
        estrellas: j['estrellas'] as int? ?? 0,
        logros: j['logros'] as int? ?? 0,
        diasSinEntrar: j['dias_sin_entrar'] as int?,
      );
}
