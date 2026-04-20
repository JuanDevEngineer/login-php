<?php

/**
 * Devuelve el nombre de la página actual a partir de la URL para usarlo en <title>.
 */
function paginaActual() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $uri = strtok($uri, '?');
    $parts = array_values(array_filter(explode('/', $uri)));
    $last  = end($parts);
    if ($last === false || $last === '' || $last === 'index.php') {
        return 'Login';
    }
    // Sólo letras/números/guiones (defensa adicional aunque la salida ya se escape).
    return preg_replace('/[^A-Za-z0-9_\- ]/', '', $last) ?: 'Login';
}

/**
 * Escapa un valor para HTML. Reemplazo corto de htmlspecialchars.
 */
function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

