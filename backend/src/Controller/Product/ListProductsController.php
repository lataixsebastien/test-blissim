<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Dto\Output\ProductOutput;
use App\UseCase\Product\ListProductsUseCase;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'api_products_list', methods: ['GET'])]
final readonly class ListProductsController
{
    public function __construct(private ListProductsUseCase $listProductsUseCase)
    {
    }

    /** @return list<ProductOutput> */
    public function __invoke(): array
    {
        return ($this->listProductsUseCase)();
    }
}
