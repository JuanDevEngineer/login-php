<?php

/**
 * Autoload simple de controladores. Usa rutas absolutas basadas en __DIR__
 * para evitar problemas cuando el CWD no es la raíz del proyecto.
 */
spl_autoload_register(function (string $classname): void {
    // Sólo autocargamos controladores aquí; los modelos/helpers se requieren
    // explícitamente donde hacen falta.
    $candidates = [
        __DIR__ . '/controllers/' . $classname . '.php',
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
