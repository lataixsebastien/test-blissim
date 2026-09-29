<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\Dto\Input\CreateCommentInput;
use App\Dto\Output\CommentOutput;
use App\UseCase\Comment\CreateCommentUseCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comments', name: 'api_comments_create', defaults: ['_api_status' => Response::HTTP_CREATED], methods: ['POST'])]
final readonly class CreateCommentController
{
    public function __construct(private CreateCommentUseCase $createCommentUseCase)
    {
    }

    public function __invoke(#[MapRequestPayload(acceptFormat: 'json')] CreateCommentInput $input): CommentOutput
    {
        return ($this->createCommentUseCase)($input);
    }
}
