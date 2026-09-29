<?php

declare(strict_types=1);

namespace App\Tests\Manager;

use App\Exception\ProductApiException;
use App\Manager\ProductManager;
use App\Entity\Product;
use App\Service\ProductApiClientInterface;
use App\Tests\Service\DummyJsonApiClientTest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ProductManagerTest extends TestCase
{
    public function testReturnsTypedProduct(): void
    {
        $product = $this->manager((object) DummyJsonApiClientTest::PRODUCT)->getById(1);
        self::assertInstanceOf(Product::class, $product);
        self::assertSame([
            'id' => 1,
            'title' => 'Sac',
            'price' => 19.5,
            'image' => DummyJsonApiClientTest::PRODUCT['images'][0],
            'description' => 'Un sac',
            'category' => 'womens-bags',
        ], get_object_vars($product));
    }

    public function testListMapsUpstreamPayloadsToProducts(): void
    {
        $products = $this->manager((object) array_replace(DummyJsonApiClientTest::PRODUCT, ['price' => 19]))->getAll();

        self::assertCount(1, $products);
        self::assertInstanceOf(Product::class, $products[0]);
        self::assertSame(19.0, $products[0]->price);
        self::assertSame(DummyJsonApiClientTest::PRODUCT['thumbnail'], $products[0]->image);
    }

    #[DataProvider('invalidPayloads')]
    public function testRejectsInvalidPayload(mixed $payload): void
    {
        $this->expectException(ProductApiException::class);
        $this->manager($payload)->getAll();
    }

    public static function invalidPayloads(): iterable
    {
        yield 'null' => [null];
        yield 'scalar' => [42];
        yield 'missing fields' => [(object) ['id' => 1]];
        foreach (['price' => -1, 'id' => '1', 'title' => '', 'thumbnail' => 'javascript:alert(1)'] as $field => $value) {
            yield $field => [(object) array_replace(DummyJsonApiClientTest::PRODUCT, [$field => $value])];
        }
    }

    private function manager(mixed $payload): ProductManager
    {
        $client = $this->createStub(ProductApiClientInterface::class);
        $client->method('getProduct')->willReturn($payload);
        $client->method('listProducts')->willReturn([$payload]);

        return new ProductManager($client, new NullLogger());
    }
}
