import { useEffect, useState } from "react";
import { Link } from "react-router";
import { api, errorMessage, formatPrice } from "../services/api";
import { isCatalogueProduct } from "../utils/catalogue";
import ErrorNotice from "../components/ErrorNotice";
import ProductImage from "../components/ProductImage";

export default function CataloguePage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [reload, setReload] = useState(0);
  const [query, setQuery] = useState("");
  useEffect(() => {
    document.title = "Le catalogue · Blissim";
    const controller = new AbortController();
    setLoading(true);
    setError("");
    api
      .products(controller.signal)
      .then((items) => setProducts(items.filter(isCatalogueProduct)))
      .catch((e) => {
        if (!controller.signal.aborted) setError(errorMessage(e));
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });
    return () => controller.abort();
  }, [reload]);
  const filtered = products.filter(
    (product) =>
      product.title
        .toLocaleLowerCase("fr")
        .includes(query.trim().toLocaleLowerCase("fr")),
  );

  return (
    <>
      <section className="catalogue-intro">
        <div>
          <p className="eyebrow">Le goût de la découverte</p>
          <h1>
            Vos prochaines
            <br />
            <em>belles trouvailles.</em>
          </h1>
          <p>
            Explorez le catalogue, découvrez chaque produit
            <br className="desktop-break" /> et partagez vos impressions.
          </p>
        </div>
        <div className="intro-note">
          <span aria-hidden="true">✳</span>
          <p>
            À découvrir.
            <br />À commenter.
            <br />
            <strong>À vous de choisir.</strong>
          </p>
        </div>
      </section>
      <section aria-labelledby="catalogue-title">
        <div className="section-heading">
          <h2 id="catalogue-title">Le catalogue</h2>
          {!loading && !error && (
            <span className="count-label" role="status">
              {filtered.length} produit{filtered.length !== 1 ? "s" : ""}
            </span>
          )}
        </div>
        {loading ? (
          <div role="status" className="loading-state">
            <span className="spinner" aria-hidden="true" />
            Chargement des découvertes…
          </div>
        ) : error ? (
          <ErrorNotice
            message={error}
            onRetry={() => setReload((value) => value + 1)}
          />
        ) : (
          <>
            <div className="catalogue-tools">
              <div className="search-field">
                <label htmlFor="product-search">Rechercher un produit</label>
                <input
                  id="product-search"
                  type="search"
                  value={query}
                  placeholder="Un titre, une envie…"
                  onChange={(event) => setQuery(event.target.value)}
                />
              </div>
            </div>
            {filtered.length > 0 ? (
              <ul className="product-grid">
                {filtered.map((product) => (
                  <li className="product-card" key={product.id}>
                    <Link to={`/products/${product.id}`}>
                      <ProductImage src={product.image} title={product.title} />
                      <div className="product-card-content">
                        <h3>{product.title}</h3>
                        <div className="card-bottom">
                          <span className="price">
                            {formatPrice(product.price)}
                          </span>
                          <span className="card-arrow" aria-hidden="true">
                            ↗
                          </span>
                        </div>
                      </div>
                    </Link>
                  </li>
                ))}
              </ul>
            ) : (
              <div className="empty-state">
                <h3>
                  {products.length
                    ? "Aucun produit ne correspond à votre recherche."
                    : "Le catalogue est vide pour le moment."}
                </h3>
                {products.length > 0 && (
                  <button
                    className="button secondary"
                    onClick={() => setQuery("")}
                  >
                    Effacer la recherche
                  </button>
                )}
              </div>
            )}
          </>
        )}
      </section>
    </>
  );
}
