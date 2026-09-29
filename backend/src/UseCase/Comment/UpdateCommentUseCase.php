<?php

declare(strict_types=1);

namespace App\UseCase\Comment;

use App\Dto\Input\UpdateCommentInput;
use App\Dto\Output\CommentOutput;
use App\Manager\CommentManager;

final readonly class UpdateCommentUseCase
{
    public function __construct(private CommentManager $commentManager)
    {
    }

    public function __invoke(int $id, UpdateCommentInput $input): CommentOutput
    {
        $comment = $this->commentManager->update($id, $input->author, $input->content);

        return new CommentOutput($comment);
    }
}
