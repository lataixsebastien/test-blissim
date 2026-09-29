<?php

declare(strict_types=1);

namespace App\UseCase\Product;

use App\Dto\Output\ProductOutput;
use App\Manager\ProductManager;

final readonly class GetProductUseCase
{
    public function __construct(
        private ProductManager $productManager,
    ) {
    }

    public function __invoke(int $id): ProductOutput
    {
        $product = $this->productManager->getById($id);

        return new ProductOutput($product);
    }
}
