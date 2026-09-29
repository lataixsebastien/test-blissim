<?php

declare(strict_types=1);

namespace App\Service;

interface ProductApiClientInterface
{
    /** @return list<mixed> */
    public function listProducts(): array;

    public function getProduct(int $id): mixed;
}
