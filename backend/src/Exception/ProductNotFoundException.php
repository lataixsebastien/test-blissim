<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le produit demandé est introuvable.');
    }
}
