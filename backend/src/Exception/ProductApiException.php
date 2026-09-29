<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

final class ProductApiException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('Le catalogue est temporairement indisponible. Réessayez plus tard.', 0, $previous);
    }
}
