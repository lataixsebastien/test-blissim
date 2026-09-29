<?php

declare(strict_types=1);

namespace App\Manager;

use App\Exception\CommentNotFoundException;
use App\Repository\CommentRepository;
use App\Entity\Comment;
use Psr\Log\LoggerInterface;

final readonly class CommentManager
{
    public function __construct(
        private CommentRepository $comments,
        private LoggerInterface $logger,
    ) {
    }

    /** @return list<Comment> */
    public function getByProduct(int $productId): array
    {
        $comments = $this->comments->findByProduct($productId);
        $this->logger->debug('Comments retrieved', ['product_id' => $productId, 'count' => count($comments)]);

        return $comments;
    }

    public function create(int $productId, string $author, string $content): Comment
    {
        $comment = $this->comments->create($productId, $author, $content);
        $this->logger->info('Comment created', [
            'comment_id' => $comment->id,
            'product_id' => $comment->productApiId,
        ]);

        return $comment;
    }

    public function update(int $id, string $author, string $content): Comment
    {
        $comment = $this->comments->update($id, $author, $content) ?? throw new CommentNotFoundException();
        $this->logger->info('Comment updated', [
            'comment_id' => $comment->id,
            'product_id' => $comment->productApiId,
        ]);

        return $comment;
    }

    public function delete(int $id): void
    {
        if (!$this->comments->delete($id)) {
            throw new CommentNotFoundException();
        }

        $this->logger->info('Comment deleted', ['comment_id' => $id]);
    }
}
