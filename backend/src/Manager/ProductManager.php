<?php

declare(strict_types=1);

namespace App\Manager;

use App\Exception\ProductApiException;
use App\Entity\Product;
use App\Service\ProductApiClientInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use UnexpectedValueException;

final readonly class ProductManager
{
    public function __construct(
        private ProductApiClientInterface $products,
        private LoggerInterface $logger,
    ) {
    }

    public function getById(int $id): Product
    {
        $product = $this->validateProduct($this->products->getProduct($id), fullImage: true);
        if ($product->id !== $id) {
            throw new ProductApiException(new UnexpectedValueException('Mismatched product ID.'));
        }

        $this->logger->debug('Product retrieved', ['product_id' => $product->id]);

        return $product;
    }

    /** @return list<Product> */
    public function getAll(): array
    {
        $products = array_map($this->validateProduct(...), $this->products->listProducts());
        $this->logger->debug('Products retrieved', ['count' => count($products)]);

        return $products;
    }

    private function validateProduct(mixed $data, bool $fullImage = false): Product
    {
        if (!$data instanceof stdClass
            || !isset($data->id, $data->title, $data->price, $data->thumbnail, $data->description, $data->category)
            || !is_int($data->id) || $data->id < 1
            || !is_string($data->title) || trim($data->title) === ''
            || !(is_int($data->price) || is_float($data->price))
            || !is_finite((float) $data->price) || $data->price < 0
            || !is_string($data->thumbnail) || filter_var($data->thumbnail, FILTER_VALIDATE_URL) === false
            || parse_url($data->thumbnail, PHP_URL_SCHEME) !== 'https'
            || !is_string($data->description) || !is_string($data->category)
        ) {
            throw new ProductApiException(new UnexpectedValueException('Invalid product payload.'));
        }

        return new Product(
            id: $data->id,
            title: $data->title,
            price: (float) $data->price,
            image: $fullImage ? $data->images[0] : $data->thumbnail,
            description: $data->description,
            category: $data->category,
        );
    }
}
