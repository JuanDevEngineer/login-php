-- Esquema de la base `test`.
--
-- Correcciones respecto al dump original:
--   * `use DATABASE ...` no es SQL válido -> CREATE DATABASE + USE.
--   * `MODIFY ... AUTO_INCREMENT, AUTO_INCREMENT=3` tenía una coma de más.
--   * Se agregan `estado` e `imagen_url`, que el código usaba pero no existían.
--   * `recover` pasa a 255 para guardar "selector:hash:expira".
--   * utf8mb4 en vez de utf8 (utf8 de MySQL no cubre todo Unicode).

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `test`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `test`;

-- ---------------------------------------------------------------- roles
CREATE TABLE IF NOT EXISTS `rol` (
    `id_rol`     INT(11) NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(50) NOT NULL,
    `es_sistema` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = rol del que depende el codigo; no se renombra ni se borra',
    PRIMARY KEY (`id_rol`),
    UNIQUE KEY `rol_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROL_ADMIN y ROL_USER estan cableados en el codigo (isAdmin() compara contra
-- 'ROL_ADMIN'; el registro publico asigna 'ROL_USER'). Se marcan como de
-- sistema para que la gestion de roles no pueda dejar la app sin admins.
INSERT INTO `rol` (`id_rol`, `nombre`, `es_sistema`) VALUES
    (1, 'ROL_ADMIN', 1),
    (2, 'ROL_USER', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `es_sistema` = 1;

-- ------------------------------------------------------------- usuarios
CREATE TABLE IF NOT EXISTS `usuario` (
    `id_usuario` INT(11) NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(200) NOT NULL,
    `email`      VARCHAR(200) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `rol_id`     INT(11) DEFAULT NULL,
    `registro`   DATE DEFAULT NULL,
    `recover`    VARCHAR(255) DEFAULT NULL COMMENT 'selector:hash_sha256:expira_unix',
    `estado`     TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 activo, 0 inactivo',
    `imagen_url` VARCHAR(255) DEFAULT NULL
        COMMENT 'Solo el nombre del archivo en assets/uploads, nunca una URL',
    PRIMARY KEY (`id_usuario`),
    UNIQUE KEY `usuario_username_unique` (`username`),
    UNIQUE KEY `usuario_email_unique` (`email`),
    KEY `usuario_rol_id` (`rol_id`),
    KEY `usuario_recover` (`recover`),
    CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
