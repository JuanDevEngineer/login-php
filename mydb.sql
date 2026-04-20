-- phpMyAdmin SQL Dump
-- Regenerated schema for `test` database.
-- Compatible con MySQL 5.7+ y 8.x.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Crear/usar la base de datos
--
CREATE DATABASE IF NOT EXISTS `test`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `test`;

-- --------------------------------------------------------

--
-- Tabla `rol`
--
CREATE TABLE IF NOT EXISTS `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `rol_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rol` (`id_rol`, `nombre`) VALUES
  (1, 'ROL_ADMIN'),
  (2, 'ROL_USER')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- --------------------------------------------------------

--
-- Tabla `usuario`
--
-- Cambios respecto al dump original:
--   * `recover` ampliado a 255 para poder guardar `hash|expiresUnix`.
--   * `estado` agregado (0 = inactivo, 1 = activo).
--   * `imagen_url` agregado (columna usada por UserGestor::uploadNameImage).
--
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `username`   varchar(200) NOT NULL,
  `email`      varchar(200) NOT NULL,
  `password`   varchar(255) NOT NULL,
  `rol_id`     int(11) DEFAULT NULL,
  `registro`   date DEFAULT NULL,
  `recover`    varchar(255) DEFAULT NULL,
  `estado`     tinyint(1) NOT NULL DEFAULT 1,
  `imagen_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario_username_unique` (`username`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (comentados por defecto)
-- INSERT INTO `usuario` (`id_usuario`, `username`, `email`, `password`, `rol_id`, `registro`, `recover`, `estado`) VALUES
-- (1, 'juan',    'cuadrosc99@gmail.com', '$2y$12$ngKwVipDolOaRs/Sun1qkeBZ5T4TY735RYOe2ZIeKUfGAVb7Cff1i', 1, '2020-10-15', NULL, 1),
-- (2, 'olid11',  'MB@gmail.com',         '$2y$12$k3XBKzVG8L/avPh1GtgilOwISAsZlzvS.XVECF33u2muIDLirq61y', 2, '2020-10-20', NULL, 1),
-- (3, 'carlos',  'cuadros@gmail.com',    '$2y$12$MtZoxjtIS28csew9Nw9Q3.tXH/ZDd8gXob0bA7Yp7Nhxqaa78w2q2', 2, '2020-10-20', NULL, 1),
-- (4, 'lYonier', 'yonier@gmail.com',     '$2y$12$qkLg7r2hHWXps/qMCNp8yu8m8C7X7UfeD.HLkXMHEwJ6iSNchz/Rm', 2, '2020-11-17', NULL, 1);
