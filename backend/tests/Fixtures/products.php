<?php

// Isolated end-to-end fixture server; never used by the normal Docker stack.
header('Content-Type: application/json');
$products = [
    ['id' => 1, 'title' => 'Mascara — produit de test', 'price' => 9.99,
        'thumbnail' => 'https://cdn.dummyjson.com/product-images/beauty/essence-mascara-lash-princess/thumbnail.webp',
        'description' => 'Premier produit de test.', 'category' => 'beauty'],
    ['id' => 2, 'title' => 'Palette — produit de test', 'price' => 19.99,
        'thumbnail' => 'https://cdn.dummyjson.com/product-images/beauty/eyeshadow-palette-with-mirror/thumbnail.webp',
        'description' => 'Second produit de test.', 'category' => 'beauty'],
];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/products') {
    echo json_encode(['products' => $products, 'total' => count($products), 'skip' => 0, 'limit' => count($products)]);
} elseif (preg_match('#^/products/([0-9]+)$#', $path, $matches)) {
    $product = $products[(int) $matches[1] - 1] ?? null;
    http_response_code($product === null ? 404 : 200);
    echo json_encode($product ?? ['message' => 'Product not found']);
} else {
    http_response_code(404);
    echo 'null';
}
