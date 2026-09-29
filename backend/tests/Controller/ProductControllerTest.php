<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\DummyJsonApiClient;
use App\Service\ProductApiClientInterface;
use App\Tests\Service\DummyJsonApiClientTest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ProductControllerTest extends WebTestCase
{
    #[DataProvider('routes')]
    public function testRoute(string $method, string $path, string $body, int $upstreamStatus, int $status, array $expected): void
    {
        $browser = self::createClient(['debug' => false]);
        self::getContainer()->set(ProductApiClientInterface::class, new DummyJsonApiClient(
            new MockHttpClient(new MockResponse($body, ['http_code' => $upstreamStatus])),
            'https://dummyjson.com',
        ));
        $browser->request($method, $path);

        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('content-type', 'application/json');
        $actual = json_decode($browser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (isset($expected['data'])) {
            self::assertSame($expected, $actual);
        } else {
            self::assertSame($expected['code'], $actual['error']['code']);
            self::assertNotEmpty($actual['error']['message']);
            self::assertArrayNotHasKey('trace', $actual['error']);
        }
        if ($status === 405) {
            self::assertResponseHeaderSame('allow', 'GET');
        }
    }

    public static function routes(): iterable
    {
        $product = DummyJsonApiClientTest::PRODUCT;
        $output = ['id' => 1, 'title' => 'Sac', 'price' => 19.5,
            'image' => $product['thumbnail'], 'description' => 'Un sac', 'category' => 'womens-bags'];
        $list = ['products' => [$product], 'total' => 1, 'skip' => 0, 'limit' => 1];
        yield 'list' => ['GET', '/api/v1/products', json_encode($list), 200, 200, ['data' => [$output]]];
        yield 'detail' => ['GET', '/api/v1/products/1', json_encode($product), 200, 200, ['data' => array_replace($output, ['image' => $product['images'][0]])]];
        yield 'missing product' => ['GET', '/api/v1/products/1', '{"message":"Product with id 1 not found"}', 404, 404, ['code' => 'PRODUCT_NOT_FOUND']];
        yield 'null product response' => ['GET', '/api/v1/products/1', 'null', 200, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'API down' => ['GET', '/api/v1/products', '', 503, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'invalid JSON' => ['GET', '/api/v1/products', '<html>', 200, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'invalid product in list' => ['GET', '/api/v1/products', '{"products":[{"id":1}],"total":1,"skip":0,"limit":1}', 200, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'invalid product detail' => ['GET', '/api/v1/products/1', '{"id":1}', 200, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'mismatched product ID' => ['GET', '/api/v1/products/2', json_encode($product), 200, 502, ['code' => 'PRODUCT_API_UNAVAILABLE']];
        yield 'zero ID' => ['GET', '/api/v1/products/0', '', 200, 404, ['code' => 'NOT_FOUND']];
        yield 'non-numeric ID' => ['GET', '/api/v1/products/abc', '', 200, 404, ['code' => 'NOT_FOUND']];
        yield 'overflow ID' => ['GET', '/api/v1/products/999999999999999999999', '', 200, 404, ['code' => 'NOT_FOUND']];
        yield 'unknown route' => ['GET', '/api/v1/unknown', '', 200, 404, ['code' => 'NOT_FOUND']];
        yield 'wrong method' => ['POST', '/api/v1/products', '', 200, 405, ['code' => 'METHOD_NOT_ALLOWED']];
    }
}
