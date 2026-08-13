<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raíz de todos los errores de negocio. Permite a la capa de presentación
 * distinguir "el usuario hizo algo inválido" de "el sistema falló".
 */
abstract class DomainException extends \RuntimeException
{
}
