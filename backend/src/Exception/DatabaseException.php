<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

final class DatabaseException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('Les commentaires sont temporairement indisponibles. Réessayez plus tard.', 0, $previous);
    }
}
