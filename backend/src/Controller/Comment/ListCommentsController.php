<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\Dto\Output\CommentOutput;
use App\UseCase\Comment\ListCommentsUseCase;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products/{id}/comments', name: 'api_comments_list', requirements: ['id' => '[1-9][0-9]{0,8}'], methods: ['GET'])]
final readonly class ListCommentsController
{
    public function __construct(private ListCommentsUseCase $listCommentsUseCase)
    {
    }

    /** @return list<CommentOutput> */
    public function __invoke(int $id): array
    {
        return ($this->listCommentsUseCase)($id);
    }
}
