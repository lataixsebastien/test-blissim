<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\UseCase\Comment\DeleteCommentUseCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comments/{id}', name: 'api_comments_delete', requirements: ['id' => '[1-9][0-9]{0,17}'], defaults: ['_api_status' => Response::HTTP_NO_CONTENT], methods: ['DELETE'])]
final readonly class DeleteCommentController
{
    public function __construct(private DeleteCommentUseCase $deleteCommentUseCase)
    {
    }

    public function __invoke(int $id): void
    {
        ($this->deleteCommentUseCase)($id);
    }
}
