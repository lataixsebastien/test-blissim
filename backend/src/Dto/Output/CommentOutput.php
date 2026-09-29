<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\Comment;

final readonly class CommentOutput
{
    public int $id;
    public int $productApiId;
    public string $author;
    public string $content;
    public string $createdAt;
    public ?string $updatedAt;

    public function __construct(Comment $comment)
    {
        $this->id = $comment->id;
        $this->productApiId = $comment->productApiId;
        $this->author = $comment->author;
        $this->content = $comment->content;
        $this->createdAt = $comment->createdAt->format(DATE_ATOM);
        $this->updatedAt = $comment->updatedAt?->format(DATE_ATOM);
    }
}
