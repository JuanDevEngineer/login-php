-- Migración 2.2 — la foto de perfil deja de guardarse como URL absoluta.
-- Ejecutar una sola vez, después de migration-2.1.sql.
--
-- Antes la columna `imagen_url` guardaba algo como
--     http://localhost/GITHUB-PRIVATE/login-php/assets/uploads/abc123.jpg
-- lo que significaba que mover el proyecto, cambiar de dominio o tocar
-- BASE_URL rompía todas las fotos ya subidas.
--
-- Ahora guarda únicamente el nombre del archivo ("abc123.jpg") y la URL se
-- arma al renderizar la vista.

USE `test`;

-- Antes de convertir, mirá qué hay:
--   SELECT id_usuario, imagen_url FROM usuario WHERE imagen_url IS NOT NULL;

-- Se queda con lo que haya después de la última barra. Las filas que ya
-- tuvieran solo el nombre no cambian, porque SUBSTRING_INDEX sobre una cadena
-- sin '/' devuelve la cadena entera.
UPDATE `usuario`
SET `imagen_url` = SUBSTRING_INDEX(`imagen_url`, '/', -1)
WHERE `imagen_url` IS NOT NULL
  AND `imagen_url` <> '';

-- Normaliza las cadenas vacías a NULL: "sin foto" debe ser un solo valor.
UPDATE `usuario` SET `imagen_url` = NULL WHERE `imagen_url` = '';

-- El nombre generado son 32 hex + extensión, así que 255 sobra de largo.
ALTER TABLE `usuario` MODIFY `imagen_url` VARCHAR(255) DEFAULT NULL
    COMMENT 'Solo el nombre del archivo en assets/uploads, nunca una URL';

-- Comprobación: no debería quedar ninguna fila con barras.
--   SELECT id_usuario, imagen_url FROM usuario WHERE imagen_url LIKE '%/%';
