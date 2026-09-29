<?php

declare(strict_types=1);

namespace App\UseCase\Comment;

use App\Dto\Input\CreateCommentInput;
use App\Dto\Output\CommentOutput;
use App\Manager\CommentManager;
use App\Manager\ProductManager;

final readonly class CreateCommentUseCase
{
    public function __construct(
        private CommentManager $commentManager,
        private ProductManager $productManager,
    ) {
    }

    public function __invoke(CreateCommentInput $input): CommentOutput
    {
        $this->productManager->getById($input->product_id);

        $comment = $this->commentManager->create($input->product_id, $input->author, $input->content);

        return new CommentOutput($comment);
    }
}
