<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use App\Entity\Comment;
use App\Service\DatabaseConnectionInterface;
use DateTimeImmutable;
use PDOException;
use PDOStatement;
use stdClass;

final readonly class CommentRepository
{
    public function __construct(private DatabaseConnectionInterface $database)
    {
    }

    /** @return list<Comment> */
    public function findByProduct(int $productId): array
    {
        $rows = $this->query(
            'SELECT * FROM comments WHERE product_api_id = :product ORDER BY created_at DESC, id DESC',
            ['product' => $productId]
        )->fetchAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function create(int $productId, string $author, string $content): Comment
    {
        $row = $this->query(
            'INSERT INTO comments (product_api_id, author, content) VALUES (:product, :author, :content) RETURNING *',
            ['product' => $productId, 'author' => $author, 'content' => $content]
        )->fetch();

        return $this->hydrate($row);
    }

    public function update(int $id, string $author, string $content): ?Comment
    {
        $row = $this->query(
            'UPDATE comments SET author = :author, content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING *',
            ['id' => $id, 'author' => $author, 'content' => $content]
        )->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function delete(int $id): bool
    {
        return $this->query('DELETE FROM comments WHERE id = :id', ['id' => $id])->rowCount() > 0;
    }

    private function hydrate(stdClass $row): Comment
    {
        return new Comment(
            id: (int) $row->id,
            productApiId: (int) $row->product_api_id,
            author: $row->author,
            content: $row->content,
            createdAt: new DateTimeImmutable($row->created_at),
            updatedAt: $row->updated_at === null ? null : new DateTimeImmutable($row->updated_at),
        );
    }

    /** @param array<string, int|string> $parameters */
    private function query(string $sql, array $parameters): PDOStatement
    {
        try {
            $statement = $this->database->getConnection()->prepare($sql);
            $statement->execute($parameters);

            return $statement;
        } catch (PDOException $exception) {
            throw new DatabaseException($exception);
        }
    }
}
