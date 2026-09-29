<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

final readonly class Comment
{
    public function __construct(
        public int $id,
        public int $productApiId,
        public string $author,
        public string $content,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
