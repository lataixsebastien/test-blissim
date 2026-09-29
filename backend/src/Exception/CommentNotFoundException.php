<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class CommentNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le commentaire demandé est introuvable.');
    }
}
