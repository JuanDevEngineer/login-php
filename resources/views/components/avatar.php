<?php
/**
 * Muestra el avatar de un usuario.
 *
 * Recibe el NOMBRE del archivo (lo que hay en base de datos) y arma la URL acá.
 * Ese es el punto: la base no sabe nada de dominios ni de rutas, así que mover
 * el proyecto no rompe ninguna foto.
 *
 * El parámetro se llama $avatarFile y no $file a propósito: los nombres
 * genéricos son los que chocan con las variables internas del renderizador.
 *
 * @var string|null $avatarFile nombre del archivo, o null si no tiene foto
 * @var string      $baseUrl
 * @var string      $size       ancho/alto en px
 * @var string      $classes    clases extra del <img>
 * @var string      $alt
 */
$avatarFile = $avatarFile ?? null;
$size       = $size       ?? '160';
$classes    = $classes    ?? 'img-circle';
$alt        = $alt        ?? 'Avatar';

$defaultAvatar = $baseUrl . '/assets/admin/dist/img/user2-160x160.jpg';

// En base de datos solo debe haber un nombre de archivo. Si llega algo con
// barras o dos puntos es un valor heredado (una URL absoluta antigua) o un
// error de programación: nos quedamos con el último segmento y, si aun así no
// parece un nombre válido, caemos al avatar por defecto en vez de generar un
// <img> roto.
$src = $defaultAvatar;

if (is_string($avatarFile) && $avatarFile !== '') {
    $name = basename(str_replace('\\', '/', $avatarFile));

    if (preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp)$/i', $name) === 1) {
        $src = $baseUrl . '/assets/uploads/' . rawurlencode($name);
    }
}
?>
<img src="<?= e($src) ?>"
     alt="<?= e($alt) ?>"
     class="<?= e($classes) ?>"
     width="<?= e($size) ?>"
     height="<?= e($size) ?>"
     style="object-fit: cover;">
