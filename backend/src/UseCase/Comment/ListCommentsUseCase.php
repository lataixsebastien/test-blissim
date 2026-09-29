<?php

declare(strict_types=1);

namespace App\UseCase\Comment;

use App\Dto\Output\CommentOutput;
use App\Manager\CommentManager;
use App\Entity\Comment;

final readonly class ListCommentsUseCase
{
    public function __construct(private CommentManager $commentManager)
    {
    }

    /** @return list<CommentOutput> */
    public function __invoke(int $productId): array
    {
        $comments = $this->commentManager->getByProduct($productId);

        return array_map(static fn (Comment $comment): CommentOutput => new CommentOutput($comment), $comments);
    }
}
