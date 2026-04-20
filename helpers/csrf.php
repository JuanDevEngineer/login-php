<?php

/**
 * Devuelve (y genera si hace falta) el token CSRF de la sesión actual.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**
 * Verifica el token CSRF recibido. Devuelve true si es válido.
 */
function csrf_verify($token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!is_string($token) || empty($_SESSION['_csrf'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf'], $token);
}

/**
 * Renderiza un input hidden con el token. Útil en vistas.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
