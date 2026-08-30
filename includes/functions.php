<?php
// ============================================================
// INNOVA-STEAM — Funciones de negocio
// ============================================================
require_once __DIR__ . '/config.php';

// ── Usuarios ─────────────────────────────────────────────────
function getUserById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Límite de intentos de login ──────────────────────────────
// Sin esto, las cuentas de demo (contraseña "password", códigos
// correlativos tipo EST-001) se enumeran en segundos.
// El límite por cuenta es estricto. El límite por IP es mucho más
// alto a propósito: en un colegio toda el aula sale por la misma IP
// pública, así que un umbral bajo dejaría fuera a treinta estudiantes
// por culpa de uno que se equivoca de contraseña.
const LOGIN_MAX_INTENTOS     = 5;    // fallos por identificador…
const LOGIN_MAX_INTENTOS_IP  = 40;   // …y por IP compartida.
const LOGIN_VENTANA_MIN      = 15;   // dentro de esta ventana, en minutos.

function clientIp(): ?string
{
    // Sin proxy de confianza configurado, REMOTE_ADDR es el único
    // valor que no puede falsear el cliente. Las cabeceras
    // X-Forwarded-For se ignoran a propósito.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

/**
 * Fallos recientes de un identificador concreto.
 */
function loginIntentosFallidos(string $identificador): int
{
    try {
        $stmt = getDB()->prepare(
            'SELECT COUNT(*) FROM login_intentos
              WHERE exito = 0
                AND identificador = ?
                AND intentado_en > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$identificador, LOGIN_VENTANA_MIN]);
        return (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        // Si la tabla aún no existe (migración 007 sin aplicar), no
        // bloqueamos el acceso: se degrada al comportamiento previo.
        return 0;
    }
}

/**
 * Fallos recientes desde una IP, contra cuentas distintas. Detecta el
 * barrido de contraseñas sin castigar a quien comparte salida a
 * internet con el atacante.
 */
function loginIntentosFallidosIp(?string $ip): int
{
    if ($ip === null) return 0;
    try {
        $stmt = getDB()->prepare(
            'SELECT COUNT(*) FROM login_intentos
              WHERE exito = 0
                AND ip = INET6_ATON(?)
                AND intentado_en > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$ip, LOGIN_VENTANA_MIN]);
        return (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
    }
}

function loginRegistrarIntento(string $identificador, ?string $ip, bool $exito): void
{
    try {
        getDB()->prepare(
            'INSERT INTO login_intentos (identificador, ip, exito)
             VALUES (?, CASE WHEN ? IS NULL THEN NULL ELSE INET6_ATON(?) END, ?)'
        )->execute([mb_substr($identificador, 0, 120), $ip, $ip, $exito ? 1 : 0]);
    } catch (\Throwable $e) {}
}

/**
 * Minutos que faltan para que expire el bloqueo. 0 si no está bloqueado.
 */
function loginBloqueoRestante(string $identificador, ?string $ip): int
{
    $porCuenta = loginIntentosFallidos($identificador)    >= LOGIN_MAX_INTENTOS;
    $porIp     = loginIntentosFallidosIp($ip)             >= LOGIN_MAX_INTENTOS_IP;

    if (!$porCuenta && !$porIp) {
        return 0;
    }

    // El reloj corre desde el último fallo que provocó el bloqueo.
    $sql = $porCuenta
        ? 'SELECT MAX(intentado_en) FROM login_intentos
            WHERE exito = 0 AND identificador = ?
              AND intentado_en > (NOW() - INTERVAL ? MINUTE)'
        : 'SELECT MAX(intentado_en) FROM login_intentos
            WHERE exito = 0 AND ip = INET6_ATON(?)
              AND intentado_en > (NOW() - INTERVAL ? MINUTE)';

    try {
        $stmt = getDB()->prepare($sql);
        $stmt->execute([$porCuenta ? $identificador : $ip, LOGIN_VENTANA_MIN]);
        $ultimo = $stmt->fetchColumn();
        if (!$ultimo) return 0;

        $expira    = strtotime($ultimo) + LOGIN_VENTANA_MIN * 60;
        $restantes = (int)ceil(($expira - time()) / 60);
        return max(0, $restantes);
    } catch (\Throwable $e) {
        return 0;
    }
}

function loginUser(string $emailOrCodigo, string $password): ?array
{
    $db = getDB();
    // Intentar por email primero
    $stmt = $db->prepare(
        'SELECT * FROM usuarios WHERE (email = ? OR codigo_acceso = ?) AND activo = 1 LIMIT 1'
    );
    $stmt->execute([$emailOrCodigo, $emailOrCodigo]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    // Deja rastro de actividad: es lo que permite detectar rezago.
    try {
        $db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')
           ->execute([$user['id']]);
    } catch (\Throwable $e) {}

    return $user;
}

/**
 * Devuelve el código público de verificación de un certificado,
 * generándolo la primera vez que se pide.
 */
function certificadoCodigo(PDO $pdo, int $certId): string
{
    $stmt = $pdo->prepare('SELECT codigo_verificacion FROM certificados WHERE id = ?');
    $stmt->execute([$certId]);
    $codigo = $stmt->fetchColumn();
    if (!empty($codigo)) {
        return $codigo;
    }

    // Alfabeto sin caracteres ambiguos (0/O, 1/I) — el código se dicta
    // y se teclea a mano.
    $alfabeto = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($intento = 0; $intento < 6; $intento++) {
        $bloque = '';
        for ($i = 0; $i < 8; $i++) {
            if ($i === 4) $bloque .= '-';
            $bloque .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        $candidato = 'IS-' . $bloque;

        try {
            $upd = $pdo->prepare(
                'UPDATE certificados SET codigo_verificacion = ?
                  WHERE id = ? AND codigo_verificacion IS NULL'
            );
            $upd->execute([$candidato, $certId]);
            if ($upd->rowCount() > 0) {
                return $candidato;
            }
            // rowCount 0 = otra petición lo asignó primero; lo releemos.
            $stmt->execute([$certId]);
            $yaPuesto = $stmt->fetchColumn();
            if (!empty($yaPuesto)) {
                return $yaPuesto;
            }
        } catch (PDOException $e) {
            // Colisión con el índice único: se reintenta con otro código.
            continue;
        }
    }

    // Último recurso: no bloquear la descarga del certificado.
    return 'IS-' . str_pad((string)$certId, 8, '0', STR_PAD_LEFT);
}

// ── Cursos ───────────────────────────────────────────────────
function getAllCursos(): array
{
    return getDB()->query('SELECT * FROM cursos WHERE activo = 1 ORDER BY id')->fetchAll();
}

function getCursoBySlug(string $slug): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM cursos WHERE slug = ? AND activo = 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// ── Módulos ──────────────────────────────────────────────────
function getModulosByCurso(int $cursoId): array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM modulos WHERE curso_id = ? AND activo = 1 ORDER BY orden'
    );
    $stmt->execute([$cursoId]);
    return $stmt->fetchAll();
}

function getModuloById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT m.*, c.nombre as curso_nombre, c.color_hex, c.slug as curso_slug
        FROM modulos m JOIN cursos c ON c.id = m.curso_id WHERE m.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getPasosByModulo(int $moduloId): array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM modulo_pasos WHERE modulo_id = ? ORDER BY numero_paso'
    );
    $stmt->execute([$moduloId]);
    $pasos = $stmt->fetchAll();
    foreach ($pasos as &$paso) {
        $paso['contenido'] = jsonDecode($paso['contenido']);
    }
    return $pasos;
}

function getPasoById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM modulo_pasos WHERE id = ?');
    $stmt->execute([$id]);
    $paso = $stmt->fetch();
    if ($paso) $paso['contenido'] = jsonDecode($paso['contenido']);
    return $paso ?: null;
}

function getPreguntasByPaso(int $pasoId): array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM quiz_preguntas WHERE paso_id = ? ORDER BY orden'
    );
    $stmt->execute([$pasoId]);
    $preguntas = $stmt->fetchAll();
    foreach ($preguntas as &$p) {
        $p['opciones'] = jsonDecode($p['opciones']);
    }
    return $preguntas;
}

// ── Progreso del estudiante ──────────────────────────────────
function getProgreso(int $estudianteId, int $moduloId): ?array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM progreso_estudiante WHERE estudiante_id = ? AND modulo_id = ?'
    );
    $stmt->execute([$estudianteId, $moduloId]);
    return $stmt->fetch() ?: null;
}

/**
 * Progreso de un estudiante en TODOS los módulos de un curso, en una
 * sola consulta, indexado por modulo_id.
 *
 * getProgreso() pide un módulo por llamada. Usarlo dentro de un bucle
 * genera una consulta por módulo: un curso de veinte módulos son veinte
 * viajes a la base de datos para pintar una sola pantalla.
 *
 * @return array<int,array> modulo_id => fila de progreso
 */
function getProgresoPorCurso(int $estudianteId, int $cursoId): array
{
    $stmt = getDB()->prepare(
        'SELECT pe.*
           FROM progreso_estudiante pe
           JOIN modulos m ON m.id = pe.modulo_id
          WHERE pe.estudiante_id = ? AND m.curso_id = ?'
    );
    $stmt->execute([$estudianteId, $cursoId]);

    $porModulo = [];
    foreach ($stmt->fetchAll() as $fila) {
        $porModulo[(int)$fila['modulo_id']] = $fila;
    }
    return $porModulo;
}

function upsertProgreso(int $estudianteId, int $moduloId, int $paso, bool $completado = false, int $estrellas = 0): void
{
    $db = getDB();
    $existing = getProgreso($estudianteId, $moduloId);
    if ($existing) {
        $completadoEn = $completado ? date('Y-m-d H:i:s') : null;
        $stmt = $db->prepare(
            'UPDATE progreso_estudiante SET paso_actual=?, completado=?, completado_en=?, estrellas_quiz=?
             WHERE estudiante_id=? AND modulo_id=?'
        );
        $stmt->execute([$paso, (int)$completado, $completadoEn, $estrellas, $estudianteId, $moduloId]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO progreso_estudiante (estudiante_id, modulo_id, paso_actual, completado, estrellas_quiz)
             VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$estudianteId, $moduloId, $paso, (int)$completado, $estrellas]);
    }
}

function getProgresoEstudianteCompleto(int $estudianteId): array
{
    $stmt = getDB()->prepare(
        'SELECT pe.*, m.titulo, m.curso_id, c.nombre as curso_nombre, c.color_hex, c.slug as curso_slug
         FROM progreso_estudiante pe
         JOIN modulos m ON m.id = pe.modulo_id
         JOIN cursos c ON c.id = m.curso_id
         WHERE pe.estudiante_id = ?
         ORDER BY c.id, m.orden'
    );
    $stmt->execute([$estudianteId]);
    return $stmt->fetchAll();
}

function getEstrellasTotal(int $estudianteId): int
{
    $stmt = getDB()->prepare(
        'SELECT COALESCE(SUM(estrellas_quiz), 0) FROM progreso_estudiante WHERE estudiante_id = ?'
    );
    $stmt->execute([$estudianteId]);
    return (int)$stmt->fetchColumn();
}

function getModulosCompletadosByCurso(int $estudianteId, int $cursoId): int
{
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM progreso_estudiante pe
         JOIN modulos m ON m.id = pe.modulo_id
         WHERE pe.estudiante_id = ? AND m.curso_id = ? AND pe.completado = 1'
    );
    $stmt->execute([$estudianteId, $cursoId]);
    return (int)$stmt->fetchColumn();
}

// ── Aulas ────────────────────────────────────────────────────
function getAulasByPracticante(int $practicanteId): array
{
    $stmt = getDB()->prepare(
        'SELECT a.*, c.nombre as colegio_nombre
         FROM aulas a
         JOIN practicante_aula pa ON pa.aula_id = a.id
         JOIN colegios c ON c.id = a.colegio_id
         WHERE pa.practicante_id = ?'
    );
    $stmt->execute([$practicanteId]);
    return $stmt->fetchAll();
}

function getAulasByDocente(int $docenteId): array
{
    $stmt = getDB()->prepare(
        'SELECT a.*, c.nombre as colegio_nombre
         FROM aulas a JOIN colegios c ON c.id = a.colegio_id
         WHERE a.docente_id = ?'
    );
    $stmt->execute([$docenteId]);
    return $stmt->fetchAll();
}

function getAulasByColegio(int $colegioId): array
{
    $stmt = getDB()->prepare(
        'SELECT a.*, u.nombre as docente_nombre, u.apellido as docente_apellido
         FROM aulas a LEFT JOIN usuarios u ON u.id = a.docente_id
         WHERE a.colegio_id = ? ORDER BY a.grado, a.seccion'
    );
    $stmt->execute([$colegioId]);
    return $stmt->fetchAll();
}

function getEstudiantesByAula(int $aulaId): array
{
    $stmt = getDB()->prepare(
        'SELECT u.* FROM usuarios u
         JOIN estudiante_aula ea ON ea.estudiante_id = u.id
         WHERE ea.aula_id = ? AND u.activo = 1
         ORDER BY u.apellido, u.nombre'
    );
    $stmt->execute([$aulaId]);
    return $stmt->fetchAll();
}

function getModulosByAula(int $aulaId): array
{
    $stmt = getDB()->prepare(
        'SELECT m.*, c.nombre as curso_nombre, c.color_hex, c.slug as curso_slug, am.fecha_planificada
         FROM aula_modulos am
         JOIN modulos m ON m.id = am.modulo_id
         JOIN cursos c ON c.id = m.curso_id
         WHERE am.aula_id = ?
         ORDER BY c.id, m.orden'
    );
    $stmt->execute([$aulaId]);
    return $stmt->fetchAll();
}

// ── Sesiones ─────────────────────────────────────────────────
function getSesionesByAula(int $aulaId, int $limit = 10): array
{
    $stmt = getDB()->prepare(
        'SELECT s.*, m.titulo as modulo_titulo, u.nombre as practicante_nombre, u.apellido as practicante_apellido
         FROM sesiones s
         JOIN modulos m ON m.id = s.modulo_id
         JOIN usuarios u ON u.id = s.practicante_id
         WHERE s.aula_id = ?
         ORDER BY s.fecha_sesion DESC
         LIMIT ?'
    );
    $stmt->execute([$aulaId, $limit]);
    return $stmt->fetchAll();
}

function getSesionesByPracticante(int $practicanteId, int $limit = 10): array
{
    $stmt = getDB()->prepare(
        'SELECT s.*, m.titulo as modulo_titulo, a.grado, a.seccion, co.nombre as colegio_nombre
         FROM sesiones s
         JOIN modulos m ON m.id = s.modulo_id
         JOIN aulas a ON a.id = s.aula_id
         JOIN colegios co ON co.id = a.colegio_id
         WHERE s.practicante_id = ?
         ORDER BY s.fecha_sesion DESC
         LIMIT ?'
    );
    $stmt->execute([$practicanteId, $limit]);
    return $stmt->fetchAll();
}

// ── Entregables ──────────────────────────────────────────────
function getEntregablesByEstudiante(int $estudianteId): array
{
    $stmt = getDB()->prepare(
        'SELECT e.*, m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex
         FROM entregables e
         JOIN modulos m ON m.id = e.modulo_id
         JOIN cursos c ON c.id = m.curso_id
         WHERE e.estudiante_id = ?
         ORDER BY e.subido_en DESC'
    );
    $stmt->execute([$estudianteId]);
    return $stmt->fetchAll();
}

// ── Certificados ─────────────────────────────────────────────
function getCertificadosByEstudiante(int $estudianteId): array
{
    $stmt = getDB()->prepare(
        'SELECT cert.*, m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex
         FROM certificados cert
         JOIN modulos m ON m.id = cert.modulo_id
         JOIN cursos c ON c.id = m.curso_id
         WHERE cert.estudiante_id = ?
         ORDER BY cert.emitido_en DESC'
    );
    $stmt->execute([$estudianteId]);
    return $stmt->fetchAll();
}

// ── Métricas dashboard ───────────────────────────────────────
function getMetricasColegio(int $colegioId): array
{
    $db = getDB();

    $estudiantesActivos = $db->prepare(
        'SELECT COUNT(DISTINCT ea.estudiante_id) FROM estudiante_aula ea
         JOIN aulas a ON a.id = ea.aula_id WHERE a.colegio_id = ?'
    );
    $estudiantesActivos->execute([$colegioId]);

    $modulosCompletados = $db->prepare(
        'SELECT COUNT(*) FROM progreso_estudiante pe
         JOIN estudiante_aula ea ON ea.estudiante_id = pe.estudiante_id
         JOIN aulas a ON a.id = ea.aula_id
         WHERE a.colegio_id = ? AND pe.completado = 1'
    );
    $modulosCompletados->execute([$colegioId]);

    $practicantesAsignados = $db->prepare(
        'SELECT COUNT(DISTINCT pa.practicante_id) FROM practicante_aula pa
         JOIN aulas a ON a.id = pa.aula_id WHERE a.colegio_id = ?'
    );
    $practicantesAsignados->execute([$colegioId]);

    $sesionEstaSemana = $db->prepare(
        'SELECT COUNT(*) FROM sesiones s JOIN aulas a ON a.id = s.aula_id
         WHERE a.colegio_id = ? AND s.fecha_sesion >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );
    $sesionEstaSemana->execute([$colegioId]);

    return [
        'estudiantes_activos'   => (int)$estudiantesActivos->fetchColumn(),
        'modulos_completados'   => (int)$modulosCompletados->fetchColumn(),
        'practicantes'          => (int)$practicantesAsignados->fetchColumn(),
        'sesiones_semana'       => (int)$sesionEstaSemana->fetchColumn(),
    ];
}

// ── Formato ──────────────────────────────────────────────────
function formatDate(string $date): string
{
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
               'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($date);
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function formatoLabel(string $formato): string
{
    return match ($formato) {
        'dibujo_cientifico'  => 'Dibujo científico',
        'mural_digital'      => 'Mural digital',
        'cuento_ilustrado'   => 'Cuento ilustrado',
        'prototipo'          => 'Prototipo',
        'ficha'              => 'Ficha',
        default              => 'Otro',
    };
}

function estrellasHtml(int $estrellas, int $max = 3): string
{
    $filled = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:var(--gold);display:inline-block;vertical-align:middle"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    $empty  = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--bg-border);display:inline-block;vertical-align:middle"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        $html .= $i <= $estrellas ? $filled : $empty;
    }
    return $html;
}

function iconoLucideCurso(string $iconoSlug): string
{
    // Maps Lucide icon name → inline SVG path
    $paths = [
        'calculator'     => 'M4 2h16a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM8 6v4M12 6v4M16 6v4M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01',
        'flask-conical'  => 'M14 2v6l3.79 6.89A4 4 0 0 1 14.17 22H9.83a4 4 0 0 1-3.62-7.11L10 8V2M8.5 2h7',
        'monitor'        => 'M20 3H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1zM8 21h8M12 17v4',
        'palette'        => 'M12 2a10 10 0 1 0 10 10 4 4 0 0 1-4-4 4 4 0 0 1-4-4',
        'cog'            => 'M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
        'book-open'      => 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z',
    ];
    $path = $paths[$iconoSlug] ?? $paths['book-open'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
         . '<path d="' . $path . '"/></svg>';
}
