<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** Un value object recibió un valor que viola su invariante. */
final class InvalidArgumentException extends DomainException
{
}
