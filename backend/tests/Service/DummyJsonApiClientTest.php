<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\ProductApiException;
use App\Exception\ProductNotFoundException;
use App\Service\DummyJsonApiClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DummyJsonApiClientTest extends TestCase
{
    public const PRODUCT = ['id' => 1, 'title' => 'Sac', 'price' => 19.5,
        'images' => ['https://cdn.dummyjson.com/product-images/test/1.webp'],
        'thumbnail' => 'https://cdn.dummyjson.com/product-images/test/thumbnail.webp', 'description' => 'Un sac', 'category' => 'womens-bags'];

    public function testListReturnsDecodedPayloadAndConfiguresNetworkLimits(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://dummyjson.com/products?limit=0&select=id,title,price,thumbnail,description,category', $url);
            self::assertSame(5.0, (float) $options['timeout']);
            self::assertSame(10.0, (float) $options['max_duration']);
            self::assertSame(0, $options['max_redirects']);
            return new MockResponse(json_encode(['products' => [self::PRODUCT], 'total' => 1, 'skip' => 0, 'limit' => 1], JSON_THROW_ON_ERROR));
        });
        $products = (new DummyJsonApiClient($http, 'https://dummyjson.com/'))->listProducts();
        self::assertCount(1, $products);
        self::assertInstanceOf(\stdClass::class, $products[0]);
        self::assertSame('Sac', $products[0]->title);
        self::assertSame(19.5, $products[0]->price);
    }

    #[DataProvider('invalidLists')]
    public function testInvalidResponsesFailExplicitly(string $body, int $status): void
    {
        $api = $this->client($body, $status);
        $this->expectException(ProductApiException::class);
        $api->listProducts();
    }

    public static function invalidLists(): iterable
    {
        yield 'HTTP 503' => ['[]', 503];
        yield 'redirect' => ['', 302];
        yield 'HTML' => ['<html>Blocked</html>', 200];
        yield 'missing products' => ['{}', 200];
        yield 'bare list' => ['[]', 200];
        yield 'products is not a list' => ['{"products":{},"total":0,"skip":0}', 200];
        yield 'products is null' => ['{"products":null,"total":0,"skip":0}', 200];
        yield 'incomplete catalogue' => ['{"products":[],"total":1,"skip":0}', 200];
        yield 'missing total' => ['{"products":[],"skip":0}', 200];
        yield 'invalid total' => ['{"products":[],"total":"0","skip":0}', 200];
        yield 'wrong offset' => ['{"products":[],"total":0,"skip":30}', 200];
        yield 'null' => ['null', 200];
    }

    public function testEmptyCatalogueIsValid(): void
    {
        self::assertSame([], $this->client('{"products":[],"total":0,"skip":0,"limit":0}')->listProducts());
    }

    public function testMissingProduct(): void
    {
        $this->expectException(ProductNotFoundException::class);
        $this->client('{"message":"Product with id 1 not found"}', 404)->getProduct(1);
    }

    public function testEmptySuccessBodyIsAnApiError(): void
    {
        $this->expectException(ProductApiException::class);
        $this->client('')->getProduct(1);
    }

    public function testDetailReturnsDecodedPayload(): void
    {
        $product = $this->client(json_encode(self::PRODUCT, JSON_THROW_ON_ERROR))->getProduct(1);
        self::assertInstanceOf(\stdClass::class, $product);
        self::assertSame(self::PRODUCT, (array) $product);
    }

    public function testTransportFailureIsWrapped(): void
    {
        $http = new MockHttpClient(static function (): never {
            throw new TransportException('Connection timed out');
        });
        $this->expectException(ProductApiException::class);
        (new DummyJsonApiClient($http, 'https://dummyjson.com'))->listProducts();
    }

    private function client(string $body, int $status = 200): DummyJsonApiClient
    {
        return new DummyJsonApiClient(
            new MockHttpClient(new MockResponse($body, ['http_code' => $status])),
            'https://dummyjson.com'
        );
    }
}
