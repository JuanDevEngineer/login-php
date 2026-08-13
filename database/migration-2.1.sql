-- Migración 2.1 — gestión de roles desde el panel.
-- Ejecutar una sola vez, después de migration-2.0.sql.

USE `test`;

-- Marca los roles de los que depende el código. ROL_ADMIN y ROL_USER no se
-- pueden renombrar ni eliminar desde la interfaz: si se fueran, isAdmin()
-- dejaría de reconocer a nadie como administrador y el registro público
-- fallaría al buscar el rol por defecto.
ALTER TABLE `rol`
    ADD COLUMN IF NOT EXISTS `es_sistema` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = rol del que depende el codigo; no se renombra ni se borra';

UPDATE `rol` SET `es_sistema` = 1 WHERE `nombre` IN ('ROL_ADMIN', 'ROL_USER');

-- Unicidad del nombre, por si la base viene de un dump viejo sin ese índice.
-- (Si falla, hay nombres repetidos que resolver a mano primero.)
ALTER TABLE `rol` ADD UNIQUE KEY `rol_nombre_unique` (`nombre`);

-- 200 caracteres para un nombre de rol es desproporcionado y no entra en el
-- validador del dominio (máximo 50).
ALTER TABLE `rol` MODIFY `nombre` VARCHAR(50) NOT NULL;

-- ---------------------------------------------------------------------------
-- COMPROBACIÓN PREVIA (ejecutar ANTES de las sentencias de arriba)
--
-- El dominio ahora valida el nombre del rol: mayúsculas, empezando por letra y
-- solo A-Z, 0-9 y guion bajo. Si alguna fila no cumple, la aplicación fallará
-- al hidratar cualquier usuario que tenga ese rol. Esta consulta las lista:
--
--   SELECT id_rol, nombre FROM rol WHERE nombre NOT REGEXP '^[A-Za-z][A-Za-z0-9_]*$';
--
-- Si devuelve filas, corregí esos nombres a mano antes de seguir. Por ejemplo:
--
--   UPDATE rol SET nombre = 'ROL_VENTAS' WHERE id_rol = 3;
--
-- Y normalizá todo a mayúsculas, que es como se guardan desde ahora:
--
--   UPDATE rol SET nombre = UPPER(nombre);
-- ---------------------------------------------------------------------------
