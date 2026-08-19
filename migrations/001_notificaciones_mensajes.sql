-- ============================================================
-- INNOVA-STEAM — Migration 001
-- Adds: notificaciones, mensajes tables
-- Run once on existing installs:
--   mysql -u root innovasteam < migrations/001_notificaciones_mensajes.sql
-- ============================================================

USE innovasteam;

-- ── Notificaciones ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notificaciones (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  tipo        ENUM('modulo','mensaje','sistema','alerta') NOT NULL DEFAULT 'sistema',
  titulo      VARCHAR(200) NOT NULL,
  mensaje     TEXT,
  url         VARCHAR(500),
  leida       TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuario (usuario_id),
  INDEX idx_leida   (usuario_id, leida),
  CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mensajes ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mensajes (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  remitente_id  INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NOT NULL,
  asunto        VARCHAR(300) NOT NULL,
  cuerpo        TEXT NOT NULL,
  leido         TINYINT(1) NOT NULL DEFAULT 0,
  archivado_rem TINYINT(1) NOT NULL DEFAULT 0,
  archivado_dest TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dest    (destinatario_id, leido),
  INDEX idx_remit   (remitente_id),
  CONSTRAINT fk_msg_rem  FOREIGN KEY (remitente_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_dest FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
