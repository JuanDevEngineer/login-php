<?php

declare(strict_types=1);

use App\Presentation\View\Escaper;

if (!function_exists('e')) {
    /** Atajo de escapado HTML para las plantillas. */
    function e($value): string
    {
        return Escaper::html($value);
    }
}

if (!function_exists('json_attr')) {
    function json_attr($value): string
    {
        return Escaper::json($value);
    }
}
