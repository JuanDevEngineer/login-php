-- Migración 2.4 — permisos por rol.
-- Ejecutar una sola vez, después de migration-2.3.sql.
--
-- El CATÁLOGO de permisos vive en el enum PHP App\Domain\ValueObject\Permission,
-- no en una tabla. Un permiso solo significa algo si hay código que lo
-- comprueba; tener además una tabla de catálogo daría dos fuentes de verdad que
-- se desincronizan. Acá solo se guarda QUÉ ROL TIENE CUÁL.

USE `test`;

CREATE TABLE IF NOT EXISTS `rol_permiso` (
    `rol_id`  INT(11) NOT NULL,
    `permiso` VARCHAR(60) NOT NULL COMMENT 'Codigo del enum Permission, ej. usuarios.crear',

    PRIMARY KEY (`rol_id`, `permiso`),
    KEY `rol_permiso_permiso` (`permiso`),

    -- Al borrar un rol se van sus permisos: no tiene sentido conservar filas
    -- que apuntan a nadie.
    CONSTRAINT `rol_permiso_ibfk_1` FOREIGN KEY (`rol_id`)
        REFERENCES `rol` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Siembra
--
-- ROL_ADMIN recibe todos los permisos aunque el código le dé acceso total por
-- otra vía (Role::can() lo deja pasar sin mirar el conjunto). Se siembran igual
-- para que los datos se expliquen solos y para que, si algún día se quitara ese
-- atajo, el rol siga funcionando.
-- ---------------------------------------------------------------------------
INSERT INTO `rol_permiso` (`rol_id`, `permiso`)
SELECT r.`id_rol`, p.`codigo`
FROM `rol` r
CROSS JOIN (
    SELECT 'panel.ver'              AS codigo UNION ALL
    SELECT 'perfil.editar'                    UNION ALL
    SELECT 'usuarios.ver'                     UNION ALL
    SELECT 'usuarios.crear'                   UNION ALL
    SELECT 'usuarios.editar'                  UNION ALL
    SELECT 'usuarios.cambiar_estado'          UNION ALL
    SELECT 'roles.ver'                        UNION ALL
    SELECT 'roles.gestionar'                  UNION ALL
    SELECT 'permisos.gestionar'
) p
WHERE r.`nombre` = 'ROL_ADMIN'
ON DUPLICATE KEY UPDATE `permiso` = VALUES(`permiso`);

-- ROL_USER: lo mínimo para entrar y administrar su propia cuenta.
INSERT INTO `rol_permiso` (`rol_id`, `permiso`)
SELECT r.`id_rol`, p.`codigo`
FROM `rol` r
CROSS JOIN (
    SELECT 'panel.ver' AS codigo UNION ALL
    SELECT 'perfil.editar'
) p
WHERE r.`nombre` = 'ROL_USER'
ON DUPLICATE KEY UPDATE `permiso` = VALUES(`permiso`);

-- ---------------------------------------------------------------------------
-- MANTENIMIENTO
-- Si en el futuro se elimina un caso del enum, quedarán filas con códigos que
-- ya no existen. La aplicación los ignora al leer, pero conviene limpiarlos.
-- Ajustar la lista antes de ejecutar:
--
--   DELETE FROM `rol_permiso`
--   WHERE `permiso` NOT IN (
--       'panel.ver','perfil.editar','usuarios.ver','usuarios.crear',
--       'usuarios.editar','usuarios.cambiar_estado','roles.ver',
--       'roles.gestionar','permisos.gestionar'
--   );
--
-- COMPROBACIÓN
--   SELECT r.nombre, COUNT(rp.permiso) AS permisos
--   FROM rol r LEFT JOIN rol_permiso rp ON rp.rol_id = r.id_rol
--   GROUP BY r.id_rol, r.nombre;
-- ---------------------------------------------------------------------------
