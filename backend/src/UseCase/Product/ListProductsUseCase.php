<?php

declare(strict_types=1);

namespace App\UseCase\Product;

use App\Dto\Output\ProductOutput;
use App\Manager\ProductManager;
use App\Entity\Product;

final readonly class ListProductsUseCase
{
    public function __construct(
        private ProductManager $productManager,
    ) {
    }

    /** @return list<ProductOutput> */
    public function __invoke(): array
    {
        $products = $this->productManager->getAll();

        return array_map(static fn (Product $product): ProductOutput => new ProductOutput($product), $products);
    }
}
