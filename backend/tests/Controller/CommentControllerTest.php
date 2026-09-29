<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Service\DatabaseConnection;
use App\Service\DatabaseConnectionInterface;
use App\Service\DummyJsonApiClient;
use App\Service\ProductApiClientInterface;
use App\Tests\Service\DummyJsonApiClientTest;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CommentControllerTest extends WebTestCase
{
    private KernelBrowser $browser;
    private DatabaseConnectionInterface $database;

    protected function setUp(): void
    {
        $this->browser = self::createClient(['debug' => false]);
        $this->browser->disableReboot();
        $this->database = new DatabaseConnection(getenv('DATABASE_DSN'), getenv('DATABASE_USER'), getenv('DATABASE_PASSWORD'));
        // A session-local table shadows the real table. No application row or sequence is touched.
        $this->database->getConnection()->exec('CREATE TEMPORARY TABLE comments (
            id BIGSERIAL PRIMARY KEY,
            product_api_id INTEGER NOT NULL CHECK (product_api_id > 0),
            author VARCHAR(120) NOT NULL CHECK (length(trim(author)) > 0),
            content TEXT NOT NULL CHECK (length(trim(content)) > 0),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NULL
        )');
        $connection = $this->createStub(DatabaseConnectionInterface::class);
        $connection->method('getConnection')->willReturn($this->database->getConnection());
        self::getContainer()->set(DatabaseConnectionInterface::class, $connection);
        self::getContainer()->set(ProductApiClientInterface::class, new DummyJsonApiClient(new MockHttpClient(
            static function (string $method, string $url): MockResponse {
                if (!preg_match('~/products/([12])$~', $url, $matches)) {
                    return new MockResponse('{"message":"Product not found"}', ['http_code' => 404]);
                }

                return new MockResponse(json_encode(
                    array_replace(DummyJsonApiClientTest::PRODUCT, ['id' => (int) $matches[1]]),
                    JSON_THROW_ON_ERROR,
                ));
            },
        ), 'https://dummyjson.com'));
    }

    public function testCompleteCrudPersistsThroughPdo(): void
    {
        $logs = new TestHandler();
        self::getContainer()->get('logger')->pushHandler($logs);

        $content = '<script>alert("test")</script> Une apostrophe : l\'avis.';
        $this->browser->jsonRequest('POST', '/api/v1/comments', ['product_id' => 1, 'author' => ' Alice ', 'content' => $content]);
        self::assertResponseStatusCodeSame(201);
        $comment = $this->response()['data'];
        self::assertSame('Alice', $comment['author']);
        self::assertSame($content, $comment['content']);
        self::assertSame(1, $comment['productApiId']);
        self::assertNull($comment['updatedAt']);
        $id = $comment['id'];
        self::assertGreaterThan(0, $id);
        self::assertSame($content, $this->database->getConnection()->query('SELECT content FROM comments')->fetchColumn());

        $entity = (new CommentRepository($this->database))->findByProduct(1)[0];
        self::assertInstanceOf(Comment::class, $entity);
        self::assertSame($id, $entity->id);
        self::assertSame(1, $entity->productApiId);
        self::assertSame($content, $entity->content);
        self::assertSame($comment['createdAt'], $entity->createdAt->format(DATE_ATOM));
        self::assertNull($entity->updatedAt);

        $this->browser->request('GET', '/api/v1/products/1/comments');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->response()['data']);
        $this->browser->request('GET', '/api/v1/products/2/comments');
        self::assertSame([], $this->response()['data']);

        $this->browser->jsonRequest('PUT', '/api/v1/comments/'.$id, ['author' => 'Bob', 'content' => 'Avis modifié']);
        self::assertResponseIsSuccessful();
        self::assertSame('Avis modifié', $this->response()['data']['content']);
        self::assertSame($comment['createdAt'], $this->response()['data']['createdAt']);
        self::assertNotNull($this->response()['data']['updatedAt']);

        $updatedEntity = (new CommentRepository($this->database))->findByProduct(1)[0];
        self::assertSame('Bob', $updatedEntity->author);
        self::assertSame('Avis modifié', $updatedEntity->content);
        self::assertSame($this->response()['data']['updatedAt'], $updatedEntity->updatedAt?->format(DATE_ATOM));

        $this->browser->request('DELETE', '/api/v1/comments/'.$id);
        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $this->browser->getResponse()->getContent());
        self::assertSame(0, (int) $this->database->getConnection()->query('SELECT count(*) FROM comments')->fetchColumn());
        $this->browser->request('DELETE', '/api/v1/comments/'.$id);
        self::assertResponseStatusCodeSame(404);
        self::assertSame('COMMENT_NOT_FOUND', $this->response()['error']['code']);

        $operations = array_values(array_filter(
            $logs->getRecords(),
            static fn ($record): bool => in_array($record->message, ['Comment created', 'Comment updated', 'Comment deleted'], true),
        ));
        self::assertSame(['Comment created', 'Comment updated', 'Comment deleted'], array_column($operations, 'message'));
        self::assertSame([
            ['comment_id' => $id, 'product_id' => 1],
            ['comment_id' => $id, 'product_id' => 1],
            ['comment_id' => $id],
        ], array_column($operations, 'context'));
    }

    #[DataProvider('invalidInputs')]
    public function testMapRequestPayloadRejectsInvalidInput(array $input, string $field): void
    {
        $this->browser->jsonRequest('POST', '/api/v1/comments', ['product_id' => 1] + $input);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALIDATION_ERROR', $this->response()['error']['code']);
        self::assertArrayHasKey($field, $this->response()['error']['details']);
        self::assertSame(0, (int) $this->database->getConnection()->query('SELECT count(*) FROM comments')->fetchColumn());
    }

    public static function invalidInputs(): iterable
    {
        yield 'blank name' => [['author' => '   ', 'content' => 'Avis'], 'author'];
        yield 'blank content' => [['author' => 'Alice', 'content' => " \n "], 'content'];
        yield 'missing content' => [['author' => 'Alice'], 'content'];
        yield 'long author' => [['author' => str_repeat('a', 121), 'content' => 'Avis'], 'author'];
        yield 'long content' => [['author' => 'Alice', 'content' => str_repeat('a', 2001)], 'content'];
        yield 'array instead of text' => [['author' => ['Alice'], 'content' => 'Avis'], 'author'];
        yield 'null content' => [['author' => 'Alice', 'content' => null], 'content'];
    }

    public function testMalformedJsonAndUnsupportedContentType(): void
    {
        $this->browser->request('POST', '/api/v1/comments', server: ['CONTENT_TYPE' => 'application/json'], content: '{');
        self::assertResponseStatusCodeSame(400);
        self::assertSame('INVALID_REQUEST', $this->response()['error']['code']);
        $this->browser->request('POST', '/api/v1/comments', server: ['CONTENT_TYPE' => 'text/plain'], content: 'text');
        self::assertResponseStatusCodeSame(415);
    }

    public function testCannotCreateForMissingProduct(): void
    {
        $this->browser->jsonRequest('POST', '/api/v1/comments', ['product_id' => 99, 'author' => 'Alice', 'content' => 'Avis']);
        self::assertResponseStatusCodeSame(404);
        self::assertSame('PRODUCT_NOT_FOUND', $this->response()['error']['code']);
        self::assertSame(0, (int) $this->database->getConnection()->query('SELECT count(*) FROM comments')->fetchColumn());
    }

    public function testCreateUsesProductIdFromBodyAndGeneratesCommentId(): void
    {
        $this->browser->jsonRequest('POST', '/api/v1/comments', ['product_id' => 2, 'author' => 'Alice', 'content' => 'Avis']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(2, $this->response()['data']['productApiId']);
        self::assertSame(1, $this->response()['data']['id']);
        self::assertSame(2, (int) $this->database->getConnection()->query('SELECT product_api_id FROM comments')->fetchColumn());
    }

    #[DataProvider('invalidProductIds')]
    public function testCreateRejectsInvalidProductIdBeforeWriting(array $input): void
    {
        $this->browser->jsonRequest('POST', '/api/v1/comments', $input + ['author' => 'Alice', 'content' => 'Avis']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALIDATION_ERROR', $this->response()['error']['code']);
        self::assertArrayHasKey('product_id', $this->response()['error']['details']);
        self::assertSame(0, (int) $this->database->getConnection()->query('SELECT count(*) FROM comments')->fetchColumn());
    }

    public static function invalidProductIds(): iterable
    {
        yield 'missing' => [[]];
        foreach ([null, 0, -1, 1.5, true, 'abc', '1', [], 2147483648] as $index => $value) {
            yield 'invalid '.$index => [['product_id' => $value]];
        }
    }

    public function testOldCreationRouteIsNotAccepted(): void
    {
        $this->browser->jsonRequest('POST', '/api/v1/products/1/comments', ['author' => 'Alice', 'content' => 'Avis']);

        self::assertResponseStatusCodeSame(405);
        self::assertSame(0, (int) $this->database->getConnection()->query('SELECT count(*) FROM comments')->fetchColumn());
    }

    public function testCannotUpdateMissingComment(): void
    {
        $this->browser->jsonRequest('PUT', '/api/v1/comments/999', ['author' => 'Alice', 'content' => 'Avis']);
        self::assertResponseStatusCodeSame(404);
        self::assertSame('COMMENT_NOT_FOUND', $this->response()['error']['code']);
    }

    public function testDatabaseQueryFailureIsNormalized(): void
    {
        $this->database->getConnection()->exec('ALTER TABLE pg_temp.comments RENAME COLUMN content TO unavailable_content');
        $this->browser->jsonRequest('PUT', '/api/v1/comments/1', ['author' => 'Alice', 'content' => 'Avis']);
        self::assertResponseStatusCodeSame(503);
        self::assertSame('DATABASE_UNAVAILABLE', $this->response()['error']['code']);
    }

    private function response(): array
    {
        return json_decode($this->browser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
