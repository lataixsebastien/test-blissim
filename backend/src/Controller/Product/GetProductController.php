<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Dto\Output\ProductOutput;
use App\UseCase\Product\GetProductUseCase;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products/{id}', name: 'api_products_get', requirements: ['id' => '[1-9][0-9]{0,9}'], methods: ['GET'])]
final readonly class GetProductController
{
    public function __construct(private GetProductUseCase $getProductUseCase)
    {
    }

    public function __invoke(int $id): ProductOutput
    {
        return ($this->getProductUseCase)($id);
    }
}
