<?php
// ============================================================
// INNOVA-STEAM — Importación masiva de usuarios desde CSV
//
// El alta de usuarios se hacía de una en una. Un colegio con cinco
// aulas de treinta son ciento cincuenta formularios a mano, y eso es
// lo que impedía desplegar la plataforma de verdad en agosto.
//
// Lo que llega de un colegio no es un CSV limpio: es lo que exportó
// SIAGIE o lo que alguien escribió en Excel. Por eso aquí se aceptan
// el punto y coma (Excel en español), el BOM que mete el Bloc de
// notas, Windows-1252, y cabeceras en cualquiera de las formas en que
// la gente las escribe ("Apellidos", "APELLIDO PATERNO", "DNI").
//
// El archivo NO se escribe nunca directamente: primero se planifica
// (qué se va a crear, qué se omite y por qué) y solo después de que
// alguien mire esa tabla se ejecuta. Una escritura masiva que no se
// puede revisar antes no se debe hacer.
// ============================================================

// ── Lectura del archivo ──────────────────────────────────────

/**
 * Normaliza un nombre de columna: minúsculas, sin tildes, sin espacios
 * ni signos. Así "Apellido Paterno", "APELLIDO_PATERNO" y "apellidopaterno"
 * son la misma columna.
 */
function importNormalizar(string $s): string
{
    $s = trim($s);
    $s = str_replace(
        ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'],
        ['a','e','i','o','u','u','n','a','e','i','o','u','u','n'],
        $s
    );
    $s = strtolower($s);
    return preg_replace('/[^a-z0-9]/', '', $s) ?? '';
}

/**
 * Sinónimos aceptados para cada campo. El primero de cada lista es el
 * nombre que sale en la plantilla que descarga el colegio.
 */
function importSinonimos(): array
{
    return [
        'nombre'      => ['nombre', 'nombres', 'nombredelestudiante'],
        'apellido'    => ['apellido', 'apellidos'],
        'apellido1'   => ['apellidopaterno', 'paterno'],
        'apellido2'   => ['apellidomaterno', 'materno'],
        'email'       => ['email', 'correo', 'correoelectronico', 'mail'],
        'codigo'      => ['codigo', 'codigoacceso', 'codigodeacceso', 'dni', 'documento'],
        'rol'         => ['rol', 'perfil', 'tipo'],
        'password'    => ['password', 'contrasena', 'clave'],
        'telefono'    => ['telefono', 'celular', 'telefonotutor', 'telefonodeltutor'],
        'universidad' => ['universidad', 'casadeestudios'],
    ];
}

/**
 * Convierte el contenido crudo del archivo en filas asociativas.
 *
 * Devuelve ['error' => string|null, 'columnas' => [...], 'filas' => [...]].
 * Cada fila lleva además '_linea', el número de línea del archivo, que es
 * lo único que le sirve a quien tiene que corregir el Excel.
 */
function importLeerCsv(string $bytes): array
{
    // El Bloc de notas de Windows guarda UTF-8 con BOM; sin quitarlo, la
    // primera cabecera se llama "\xEF\xBB\xBFnombre" y no casa con nada.
    if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
        $bytes = substr($bytes, 3);
    }
    // Excel en Windows guarda en Windows-1252 salvo que se le pida otra
    // cosa. Sin esto, "Ángela" llega rota y se guarda rota.
    if (!mb_check_encoding($bytes, 'UTF-8')) {
        $bytes = mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
    }
    $bytes = str_replace(["\r\n", "\r"], "\n", $bytes);

    if (trim($bytes) === '') {
        return ['error' => 'El archivo está vacío.', 'columnas' => [], 'filas' => []];
    }

    // Excel en español separa con punto y coma. Se decide mirando la
    // primera línea: gana el separador que más veces aparece en ella.
    $primera = strtok($bytes, "\n") ?: '';
    $conteos = [
        ','  => substr_count($primera, ','),
        ';'  => substr_count($primera, ';'),
        "\t" => substr_count($primera, "\t"),
    ];
    arsort($conteos);
    $sep = array_key_first($conteos);
    if ($conteos[$sep] === 0) {
        return [
            'error'    => 'No se reconoce ninguna columna. Revisa que la primera fila tenga los títulos separados por coma o punto y coma.',
            'columnas' => [], 'filas' => [],
        ];
    }

    // fgetcsv y no explode: un nombre entre comillas puede llevar dentro
    // el separador, e incluso un salto de línea.
    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $bytes);
    rewind($fh);

    $cabecera = fgetcsv($fh, 0, $sep, '"', '');
    if ($cabecera === false) {
        fclose($fh);
        return ['error' => 'No se pudo leer la primera fila.', 'columnas' => [], 'filas' => []];
    }

    // Cada columna del archivo se traduce a un campo conocido; las que no
    // se reconocen se ignoran, pero se devuelven para poder avisar.
    $sinonimos = importSinonimos();
    $mapa      = [];   // índice de columna => campo
    $sinUsar   = [];
    foreach ($cabecera as $i => $titulo) {
        $norm  = importNormalizar((string)$titulo);
        $campo = null;
        foreach ($sinonimos as $destino => $formas) {
            if (in_array($norm, $formas, true)) { $campo = $destino; break; }
        }
        if ($campo !== null && !in_array($campo, $mapa, true)) {
            $mapa[$i] = $campo;
        } elseif (trim((string)$titulo) !== '') {
            $sinUsar[] = trim((string)$titulo);
        }
    }

    $campos = array_values($mapa);
    $tieneApellido = in_array('apellido', $campos, true)
                  || in_array('apellido1', $campos, true);
    if (!in_array('nombre', $campos, true) || !$tieneApellido) {
        fclose($fh);
        return [
            'error' => 'Faltan columnas obligatorias. El archivo necesita al menos "nombre" y "apellido" '
                     . '(o "apellido paterno"). Se leyeron estos títulos: ' . implode(', ', array_map('strval', $cabecera)) . '.',
            'columnas' => [], 'filas' => [],
        ];
    }

    $filas = [];
    $linea = 1;
    while (($datos = fgetcsv($fh, 0, $sep, '"', '')) !== false) {
        $linea++;
        // Una línea de solo separadores no es un usuario en blanco: es el
        // final del archivo tal y como lo deja Excel.
        if ($datos === [null] || implode('', array_map(static fn($v) => trim((string)$v), $datos)) === '') {
            continue;
        }
        $fila = ['_linea' => $linea];
        foreach ($mapa as $i => $campo) {
            $fila[$campo] = trim((string)($datos[$i] ?? ''));
        }
        // Paterno + materno se unen en un solo apellido, que es como lo
        // guarda la tabla.
        if (!isset($fila['apellido']) || $fila['apellido'] === '') {
            $fila['apellido'] = trim(($fila['apellido1'] ?? '') . ' ' . ($fila['apellido2'] ?? ''));
        }
        unset($fila['apellido1'], $fila['apellido2']);
        $filas[] = $fila;
    }
    fclose($fh);

    if (!$filas) {
        return ['error' => 'El archivo tiene títulos pero ninguna fila con datos.', 'columnas' => [], 'filas' => []];
    }

    return ['error' => null, 'columnas' => $sinUsar, 'filas' => $filas];
}

// ── Contraseñas y códigos ────────────────────────────────────

/**
 * Contraseña generada para entregar en papel: una palabra del valle y
 * tres dígitos. Se puede dictar en voz alta a un aula de quinto de
 * primaria, que es para lo que sirve.
 */
function importPasswordSugerida(): string
{
    static $palabras = [
        'cerro', 'valle', 'olivo', 'palta', 'rio', 'andén', 'cielo', 'lluvia',
        'viento', 'arena', 'piedra', 'huerto', 'maiz', 'sol', 'luna', 'nube',
    ];
    $p = $palabras[random_int(0, count($palabras) - 1)];
    return $p . random_int(100, 999);
}

/**
 * Siguiente código libre con el prefijo dado, saltándose los que ya
 * existen en la base y los que se van a crear en esta misma pasada.
 */
function importSiguienteCodigo(PDO $pdo, string $prefijo, array &$reservados): string
{
    static $cache = [];
    if (!isset($cache[$prefijo])) {
        // El mayor número usado hasta ahora con ese prefijo. Se hace una
        // sola vez por importación y no una consulta por fila.
        $stmt = $pdo->prepare(
            'SELECT codigo_acceso FROM usuarios
              WHERE codigo_acceso LIKE ? ORDER BY LENGTH(codigo_acceso) DESC, codigo_acceso DESC LIMIT 1'
        );
        $stmt->execute([$prefijo . '%']);
        $ultimo = (string)($stmt->fetchColumn() ?: '');
        $cache[$prefijo] = (int)preg_replace('/\D/', '', substr($ultimo, strlen($prefijo)));
    }
    do {
        $cache[$prefijo]++;
        $codigo = $prefijo . str_pad((string)$cache[$prefijo], 3, '0', STR_PAD_LEFT);
    } while (isset($reservados[$codigo]));
    $reservados[$codigo] = true;
    return $codigo;
}

// ── Planificación ────────────────────────────────────────────

/**
 * Roles que cada quien puede dar de alta.
 *
 * Un director solo crea gente por debajo de él. Si pudiera poner "admin"
 * en una columna del Excel, la importación sería una escalada de
 * privilegios con formato de hoja de cálculo.
 */
function importRolesPermitidos(string $rolActor): array
{
    return $rolActor === 'admin'
        ? ['admin', 'admin_colegio', 'docente', 'practicante', 'estudiante', 'apoderado']
        : ['docente', 'practicante', 'estudiante', 'apoderado'];
}

/**
 * Decide qué se hará con cada fila SIN tocar la base.
 *
 * $opciones: rol_defecto, colegio_id, aula_id (0 = ninguna), rol_actor,
 *            permitir_homonimos (bool).
 * Devuelve ['filas' => [...], 'resumen' => ['crear'=>n,'omitir'=>n,'error'=>n]].
 */
function importPlanificar(PDO $pdo, array $filas, array $opciones): array
{
    $rolDefecto = $opciones['rol_defecto'] ?? 'estudiante';
    $colegioId  = (int)($opciones['colegio_id'] ?? 0) ?: null;
    $permitidos = importRolesPermitidos($opciones['rol_actor'] ?? 'admin_colegio');
    $homonimos  = !empty($opciones['permitir_homonimos']);

    // Un solo viaje a la base para todos los correos y códigos del
    // archivo, en vez de dos consultas por fila.
    $emails  = array_values(array_filter(array_map(
        static fn($f) => strtolower(trim((string)($f['email'] ?? ''))), $filas)));
    $codigos = array_values(array_filter(array_map(
        static fn($f) => trim((string)($f['codigo'] ?? '')), $filas)));

    $emailsEnUso  = importYaExisten($pdo, 'email', $emails);
    $codigosEnUso = importYaExisten($pdo, 'codigo_acceso', $codigos);

    // Los estudiantes suelen venir sin correo y sin DNI: entonces no hay
    // nada único con lo que reconocerlos, y volver a subir la misma lista
    // en marzo duplicaría el aula entera. El nombre no es una clave (hay
    // tocayos de verdad), así que solo se avisa y se omite; quien sabe
    // que son personas distintas marca la casilla y se crean igual.
    $nombresEnUso = $homonimos ? [] : importNombresEnUso($pdo, $colegioId);

    $vistosNombre = [];
    $vistosEmail  = [];
    $vistosCodigo = [];
    $reservados   = [];
    $plan         = [];
    $resumen      = ['crear' => 0, 'omitir' => 0, 'error' => 0];

    foreach ($filas as $f) {
        $p = [
            '_linea'   => $f['_linea'] ?? 0,
            'nombre'   => trim((string)($f['nombre'] ?? '')),
            'apellido' => trim((string)($f['apellido'] ?? '')),
            'email'    => strtolower(trim((string)($f['email'] ?? ''))),
            'codigo'   => trim((string)($f['codigo'] ?? '')),
            'telefono' => trim((string)($f['telefono'] ?? '')) ?: null,
            'universidad' => trim((string)($f['universidad'] ?? '')) ?: null,
            'password' => (string)($f['password'] ?? ''),
            'estado'   => 'crear',
            'motivo'   => '',
        ];

        // Rol: el de la columna si viene, si no el elegido en el formulario.
        $rolFila = importNormalizar((string)($f['rol'] ?? ''));
        $rolFila = match ($rolFila) {
            'admin', 'administrador'                  => 'admin',
            'admincolegio', 'director'                => 'admin_colegio',
            'docente', 'profesor', 'profesora'        => 'docente',
            'practicante'                             => 'practicante',
            'estudiante', 'alumno', 'alumna'          => 'estudiante',
            'apoderado', 'padre', 'madre', 'tutor'    => 'apoderado',
            ''                                        => $rolDefecto,
            default                                   => '?' . $rolFila,
        };
        $p['rol'] = $rolFila;

        if ($p['nombre'] === '' || $p['apellido'] === '') {
            $p['estado'] = 'error';
            $p['motivo'] = 'Falta el nombre o el apellido.';
        } elseif (str_starts_with($rolFila, '?')) {
            $p['estado'] = 'error';
            $p['motivo'] = 'Rol no reconocido: «' . substr($rolFila, 1) . '».';
        } elseif (!in_array($rolFila, $permitidos, true)) {
            $p['estado'] = 'error';
            $p['motivo'] = 'No tienes permiso para crear usuarios con el rol «' . $rolFila . '».';
        } elseif ($p['email'] !== '' && !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
            $p['estado'] = 'error';
            $p['motivo'] = 'El correo «' . $p['email'] . '» no es válido.';
        } elseif ($p['email'] !== '' && isset($emailsEnUso[$p['email']])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Ya hay una cuenta con ese correo.';
        } elseif ($p['codigo'] !== '' && isset($codigosEnUso[$p['codigo']])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Ya hay una cuenta con ese código.';
        } elseif ($p['email'] !== '' && isset($vistosEmail[$p['email']])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Repetido en el archivo (línea ' . $vistosEmail[$p['email']] . ').';
        } elseif ($p['codigo'] !== '' && isset($vistosCodigo[$p['codigo']])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Repetido en el archivo (línea ' . $vistosCodigo[$p['codigo']] . ').';
        } elseif ($p['email'] === '' && $rolFila !== 'estudiante') {
            // Un docente sin correo no tendría con qué entrar: solo los
            // estudiantes usan código de acceso.
            $p['estado'] = 'error';
            $p['motivo'] = 'Un ' . $rolFila . ' necesita correo para poder entrar.';
        } elseif (isset($vistosNombre[importClaveNombre($p['nombre'], $p['apellido'], $rolFila)])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Repetido en el archivo (línea '
                         . $vistosNombre[importClaveNombre($p['nombre'], $p['apellido'], $rolFila)] . ').';
        } elseif (isset($nombresEnUso[importClaveNombre($p['nombre'], $p['apellido'], $rolFila)])) {
            $p['estado'] = 'omitir';
            $p['motivo'] = 'Ya hay un/a ' . $rolFila . ' con ese nombre en el colegio.';
        }

        if ($p['estado'] === 'crear') {
            $vistosNombre[importClaveNombre($p['nombre'], $p['apellido'], $rolFila)] = $p['_linea'];
            if ($p['email'] !== '')  $vistosEmail[$p['email']]   = $p['_linea'];
            if ($p['codigo'] !== '') $vistosCodigo[$p['codigo']] = $p['_linea'];
            if ($p['codigo'] !== '') $reservados[$p['codigo']]   = true;

            // Un estudiante sin código no puede entrar: se le asigna uno.
            if ($p['codigo'] === '' && $rolFila === 'estudiante') {
                $p['codigo'] = importSiguienteCodigo($pdo, 'EST-', $reservados);
                $p['motivo'] = 'Código generado.';
            }
            if ($p['password'] === '') {
                $p['password'] = importPasswordSugerida();
                $p['motivo']   = trim($p['motivo'] . ' Contraseña generada.');
            } elseif (strlen($p['password']) < 6) {
                $p['estado'] = 'error';
                $p['motivo'] = 'La contraseña de esa fila tiene menos de 6 caracteres.';
            }
            $p['colegio_id'] = $colegioId;
        }

        $resumen[$p['estado']]++;
        $plan[] = $p;
    }

    return ['filas' => $plan, 'resumen' => $resumen];
}

/**
 * Clave con la que se reconoce a una persona cuando no hay correo ni
 * documento. Normalizada, para que "MAMANI QUISPE, Ángela" y
 * "Mamani  Quispe / angela" sean la misma.
 */
function importClaveNombre(string $nombre, string $apellido, string $rol): string
{
    return $rol . '|' . importNormalizar($apellido) . '|' . importNormalizar($nombre);
}

/**
 * Nombres que ya existen en ese colegio, como mapa clave => true.
 *
 * Se trae el colegio entero de una vez: es como mucho un par de miles de
 * filas y evita una consulta por línea del archivo.
 */
function importNombresEnUso(PDO $pdo, ?int $colegioId): array
{
    if ($colegioId === null) {
        $stmt = $pdo->query('SELECT nombre, apellido, rol FROM usuarios WHERE colegio_id IS NULL');
    } else {
        $stmt = $pdo->prepare('SELECT nombre, apellido, rol FROM usuarios WHERE colegio_id = ?');
        $stmt->execute([$colegioId]);
    }
    $mapa = [];
    foreach ($stmt->fetchAll() as $u) {
        $mapa[importClaveNombre($u['nombre'], $u['apellido'], $u['rol'])] = true;
    }
    return $mapa;
}

/**
 * Cuáles de esos valores ya están en usuarios.<columna>.
 * Devuelve un mapa valor => true para poder preguntar con isset().
 */
function importYaExisten(PDO $pdo, string $columna, array $valores): array
{
    $valores = array_values(array_unique(array_filter($valores)));
    if (!$valores) return [];

    // La columna no viene del usuario, viene de estas dos llamadas.
    $columna = $columna === 'email' ? 'email' : 'codigo_acceso';
    $usados  = [];
    // En trozos, porque un colegio grande manda mil filas y un IN con mil
    // marcadores es una consulta que el servidor puede rechazar.
    foreach (array_chunk($valores, 400) as $trozo) {
        $marcas = implode(',', array_fill(0, count($trozo), '?'));
        $stmt = $pdo->prepare("SELECT $columna FROM usuarios WHERE $columna IN ($marcas)");
        $stmt->execute($trozo);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $v) {
            $usados[$columna === 'email' ? strtolower((string)$v) : (string)$v] = true;
        }
    }
    return $usados;
}

// ── Ejecución ────────────────────────────────────────────────

/**
 * Crea las cuentas marcadas como 'crear' y, si se indicó un aula, las
 * matricula en ella. Todo dentro de una transacción: si algo falla a
 * mitad, no queda un colegio con media lista cargada.
 *
 * Devuelve ['creados' => [[nombre, apellido, rol, acceso, password], ...],
 *           'error'   => string|null].
 */
function importEjecutar(PDO $pdo, array $plan, int $aulaId = 0): array
{
    $aCrear = array_values(array_filter($plan, static fn($p) => $p['estado'] === 'crear'));
    if (!$aCrear) return ['creados' => [], 'error' => 'No hay ninguna fila que crear.'];

    $creados = [];
    $pdo->beginTransaction();
    try {
        $insUsuario = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, codigo_acceso, password_hash,
                                   rol, colegio_id, universidad, telefono_tutor)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $insAula = $pdo->prepare(
            'INSERT IGNORE INTO estudiante_aula (estudiante_id, aula_id) VALUES (?,?)'
        );

        foreach ($aCrear as $p) {
            $insUsuario->execute([
                $p['nombre'],
                $p['apellido'],
                // Cadena vacía y no NULL rompería la clave única en cuanto
                // hubiera un segundo usuario sin correo.
                $p['email']  !== '' ? $p['email']  : null,
                $p['codigo'] !== '' ? $p['codigo'] : null,
                password_hash($p['password'], PASSWORD_BCRYPT),
                $p['rol'],
                $p['colegio_id'] ?? null,
                $p['universidad'],
                $p['telefono'],
            ]);
            $id = (int)$pdo->lastInsertId();

            if ($aulaId > 0 && $p['rol'] === 'estudiante') {
                $insAula->execute([$id, $aulaId]);
            }

            $creados[] = [
                'nombre'   => $p['nombre'],
                'apellido' => $p['apellido'],
                'rol'      => $p['rol'],
                'acceso'   => $p['email'] !== '' ? $p['email'] : $p['codigo'],
                'password' => $p['password'],
            ];
        }

        $pdo->commit();
        return ['creados' => $creados, 'error' => null];
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('importEjecutar: ' . $e->getMessage());
        return ['creados' => [], 'error' => 'No se creó ninguna cuenta: la base rechazó la carga. Revisa el archivo y vuelve a intentarlo.'];
    }
}
