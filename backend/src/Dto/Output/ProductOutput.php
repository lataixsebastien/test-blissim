<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\Product;

final readonly class ProductOutput
{
    public int $id;
    public string $title;
    public float $price;
    public string $image;
    public string $description;
    public string $category;

    public function __construct(Product $product)
    {
        $this->id = $product->id;
        $this->title = $product->title;
        $this->price = $product->price;
        $this->image = $product->image;
        $this->description = $product->description;
        $this->category = $product->category;
    }
}
