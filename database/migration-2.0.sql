-- Migración para una base `test` que ya existe con el esquema viejo.
-- Ejecutar una sola vez.

USE `test`;

-- Columnas que el código usaba pero no estaban declaradas.
ALTER TABLE `usuario`
    ADD COLUMN IF NOT EXISTS `estado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `recover`,
    ADD COLUMN IF NOT EXISTS `imagen_url` VARCHAR(255) DEFAULT NULL AFTER `estado`;

-- El token nuevo ("selector:hash:expira") no entra en 200 caracteres.
ALTER TABLE `usuario` MODIFY `password` VARCHAR(255) NOT NULL;
ALTER TABLE `usuario` MODIFY `recover`  VARCHAR(255) DEFAULT NULL;

-- Los tokens con el formato viejo no son válidos para el flujo nuevo.
UPDATE `usuario` SET `recover` = NULL WHERE `recover` IS NOT NULL;

-- Unicidad de correo, que antes no estaba garantizada.
-- (Si falla, hay correos duplicados que hay que resolver a mano primero.)
ALTER TABLE `usuario` ADD UNIQUE KEY `usuario_email_unique` (`email`);

ALTER TABLE `usuario` ADD INDEX `usuario_recover` (`recover`);
