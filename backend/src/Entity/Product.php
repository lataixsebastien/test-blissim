<?php

declare(strict_types=1);

namespace App\Entity;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $image,
        public string $description,
        public string $category,
    ) {
    }
}
