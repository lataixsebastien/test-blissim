export function isCatalogueProduct(product) {
  return ["beauty", "fragrances", "skin-care"].includes(product.category);
}
