<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DatabaseException;
use PDO;
use PDOException;

final class DatabaseConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $user,
        private readonly string $password,
    ) {
    }

    public function getConnection(): PDO
    {
        try {
            return $this->connection ??= new PDO($this->dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (PDOException $exception) {
            throw new DatabaseException($exception);
        }
    }
}
