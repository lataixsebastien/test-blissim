<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\Dto\Input\UpdateCommentInput;
use App\Dto\Output\CommentOutput;
use App\UseCase\Comment\UpdateCommentUseCase;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comments/{id}', name: 'api_comments_update', requirements: ['id' => '[1-9][0-9]{0,17}'], methods: ['PUT'])]
final readonly class UpdateCommentController
{
    public function __construct(private UpdateCommentUseCase $updateCommentUseCase)
    {
    }

    public function __invoke(int $id, #[MapRequestPayload(acceptFormat: 'json')] UpdateCommentInput $input): CommentOutput
    {
        return ($this->updateCommentUseCase)($id, $input);
    }
}
