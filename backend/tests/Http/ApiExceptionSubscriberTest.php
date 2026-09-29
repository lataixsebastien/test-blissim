<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Exception\ProductApiException;
use App\Exception\DatabaseException;
use App\Http\ApiExceptionSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testUnexpectedErrorIsLoggedWithoutLeakingDetails(): void
    {
        $exception = new \RuntimeException('Internal connection details');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('INTERNAL_ERROR', [
            'exception' => $exception,
            'method' => 'GET',
            'path' => '/api/v1/products',
            'route' => null,
            'route_parameters' => [],
        ]);
        $event = $this->event($exception);

        (new ApiExceptionSubscriber($logger))->onException($event);

        self::assertSame(500, $event->getResponse()->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Une erreur interne est survenue.']],
            json_decode($event->getResponse()->getContent(), true)
        );
        self::assertStringNotContainsString('Internal connection details', $event->getResponse()->getContent());
    }

    public function testUpstreamErrorIsLoggedOnce(): void
    {
        $exception = new ProductApiException(new \RuntimeException('HTTP 523'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('PRODUCT_API_UNAVAILABLE', [
            'exception' => $exception,
            'method' => 'GET',
            'path' => '/api/v1/products/42',
            'route' => 'api_products_get',
            'route_parameters' => ['id' => '42'],
        ]);
        $request = Request::create('/api/v1/products/42?token=private');
        $request->attributes->set('_route', 'api_products_get');
        $request->attributes->set('_route_params', ['id' => '42', '_controller' => 'InternalController']);
        $event = $this->event($exception, $request);

        (new ApiExceptionSubscriber($logger))->onException($event);

        self::assertSame(502, $event->getResponse()->getStatusCode());
        self::assertStringNotContainsString('HTTP 523', $event->getResponse()->getContent());
    }

    #[DataProvider('commentPayloads')]
    public function testDatabaseErrorIsLoggedOnceWithoutCommentData(string $payload, array $identifiers): void
    {
        $exception = new DatabaseException(new \RuntimeException('Internal database details'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('DATABASE_UNAVAILABLE', [
            'exception' => $exception,
            'method' => 'POST',
            'path' => '/api/v1/comments',
            'route' => 'api_comments_create',
            'route_parameters' => [],
        ] + $identifiers);
        $request = Request::create('/api/v1/comments', 'POST', content: $payload);
        $request->attributes->set('_route', 'api_comments_create');
        $event = $this->event($exception, $request);

        (new ApiExceptionSubscriber($logger))->onException($event);

        self::assertSame(503, $event->getResponse()->getStatusCode());
        self::assertStringNotContainsString('Internal database details', $event->getResponse()->getContent());
    }

    public static function commentPayloads(): iterable
    {
        yield 'valid body' => ['{"product_id":42,"author":"Alice","content":"Private comment"}', ['product_id' => 42]];
        yield 'malformed body does not mask the original error' => ['{', []];
        yield 'invalid ID is not logged as an identifier' => ['{"product_id":{"content":"Private comment"}}', []];
    }

    private function event(\Throwable $exception, ?Request $request = null): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request ?? Request::create('/api/v1/products'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
