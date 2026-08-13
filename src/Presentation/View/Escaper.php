<?php

declare(strict_types=1);

namespace App\Presentation\View;

/** Helpers de escapado para las plantillas. */
final class Escaper
{
    /** Escapa para contexto HTML. Usar SIEMPRE al imprimir datos. */
    public static function html($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Escapa para usar dentro de un atributo de URL. */
    public static function url($value): string
    {
        return rawurlencode((string) ($value ?? ''));
    }

    /** Serializa a JSON seguro para incrustar en un <script>. */
    public static function json($value): string
    {
        $encoded = json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );

        return $encoded === false ? 'null' : $encoded;
    }
}
