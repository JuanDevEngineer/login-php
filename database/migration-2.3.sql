-- Migración 2.3 — registro de accesos (inicios de sesión).
-- Ejecutar una sola vez, después de migration-2.2.sql.
--
-- Hasta ahora no se guardaba ningún login, así que la métrica de "ingresos del
-- mes" no era calculable. Esta tabla es un log append-only: una fila por cada
-- autenticación exitosa.

USE `test`;

CREATE TABLE IF NOT EXISTS `acceso` (
    `id_acceso`  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT(11) NOT NULL,
    `fecha`      DATETIME NOT NULL,
    PRIMARY KEY (`id_acceso`),

    -- El conteo del dashboard filtra por rango de fechas: sin este índice,
    -- cada carga haría un full scan de la tabla.
    KEY `acceso_fecha` (`fecha`),
    KEY `acceso_usuario_fecha` (`usuario_id`, `fecha`),

    -- Si se elimina un usuario, sus accesos se van con él: no tiene sentido
    -- conservar filas que apuntan a nadie.
    CONSTRAINT `acceso_ibfk_1` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de inicios de sesion exitosos. Solo id de usuario y fecha.';

-- ---------------------------------------------------------------------------
-- NOTA SOBRE PRIVACIDAD
-- Deliberadamente NO se guardan IP ni user-agent: son datos personales y la
-- métrica del dashboard no los necesita. Si algún día hacen falta para
-- auditoría, agregarlos es una decisión consciente, no un descuido.
--
-- NOTA SOBRE CRECIMIENTO
-- Esta tabla crece indefinidamente. Cuando estorbe, purgar por antigüedad.
-- La sentencia queda comentada a propósito: borrar histórico es una decisión
-- del negocio, no algo que deba pasar solo.
--
--   DELETE FROM `acceso` WHERE `fecha` < DATE_SUB(NOW(), INTERVAL 12 MONTH);
-- ---------------------------------------------------------------------------
