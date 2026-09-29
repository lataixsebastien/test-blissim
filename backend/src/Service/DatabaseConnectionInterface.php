<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

interface DatabaseConnectionInterface
{
    public function getConnection(): PDO;
}
