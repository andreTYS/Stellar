<?php
// ============================================================
// INNOVA-STEAM — Ajustes propios de esta instalación
//
// Copia este archivo a config.local.php y edítalo. El de verdad está
// en .gitignore: no se sube nunca, y por eso este ejemplo no lleva
// ningún secreto real dentro.
//
//     cp includes/config.local.example.php includes/config.local.php
//
// config.php lo carga ANTES de sus propios define(), así que lo que se
// defina aquí gana y no hace falta tocar config.php en cada servidor.
// ============================================================

// ── Base de datos ────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'innovasteam');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Dónde vive la plataforma ─────────────────────────────────
// '/innovasteam' si está en una subcarpeta (XAMPP, htdocs/innovasteam).
// '' si sirves el dominio entero desde la raíz del proyecto.
define('BASE_URL', '/innovasteam');

// ── Secreto del asistente de estudio ─────────────────────────
// Con él se cifran las claves de API que guarda cada colegio. Tiene que
// vivir FUERA de la base: si alguien consigue un volcado, sin este valor
// las claves cifradas no le sirven de nada.
//
// Si no lo defines, la plataforma se NIEGA a guardar una clave de API en
// lugar de guardarla en claro; el resto sigue funcionando.
//
// Genera el tuyo y no reutilices este:
//     php -r "echo bin2hex(random_bytes(32));"
//
// define('CHATBOT_CLAVE_MAESTRA', 'pon-aqui-los-64-caracteres-que-te-dio-el-comando');
