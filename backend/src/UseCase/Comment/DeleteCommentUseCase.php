<?php

declare(strict_types=1);

namespace App\UseCase\Comment;

use App\Manager\CommentManager;

final readonly class DeleteCommentUseCase
{
    public function __construct(private CommentManager $commentManager)
    {
    }

    public function __invoke(int $id): void
    {
        $this->commentManager->delete($id);
    }
}
