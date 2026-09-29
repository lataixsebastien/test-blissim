import { useEffect, useState } from "react";
import { Link } from "react-router";
import { api, ApiError, errorMessage, formatPrice } from "../services/api";
import { isCatalogueProduct } from "../utils/catalogue";
import ErrorNotice from "../components/ErrorNotice";
import ProductImage from "../components/ProductImage";
import Comments from "../components/Comments";

export default function ProductPage({ id }) {
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [notFound, setNotFound] = useState(false);
  const [reload, setReload] = useState(0);
  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError("");
    setNotFound(false);
    api
      .product(id, controller.signal)
      .then((value) => {
        if (!isCatalogueProduct(value)) {
          setProduct(null);
          setNotFound(true);
          setError("Ce produit ne fait pas partie du catalogue beauté.");
          document.title = "Produit introuvable · Blissim";
          return;
        }
        setProduct(value);
        document.title = `${value.title} · Blissim`;
      })
      .catch((e) => {
        if (!controller.signal.aborted) {
          setError(errorMessage(e));
          setNotFound(e instanceof ApiError && e.status === 404);
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });
    return () => controller.abort();
  }, [id, reload]);

  return (
    <>
      <Link className="back-link" to="/">
        ← Retour au catalogue
      </Link>
      {loading ? (
        <div role="status" className="loading-state">
          <span className="spinner" aria-hidden="true" />
          Chargement du produit…
        </div>
      ) : error ? (
        <section>
          <h1>
            {notFound ? "Produit introuvable" : "Le produit est indisponible"}
          </h1>
          <ErrorNotice
            message={error}
            onRetry={notFound ? undefined : () => setReload((v) => v + 1)}
          />
        </section>
      ) : (
        product && (
          <article className="product-detail">
            <ProductImage src={product.image} title={product.title} eager />
            <div className="product-info">
              <h1>{product.title}</h1>
              <p className="price detail-price">{formatPrice(product.price)}</p>
              <div className="product-description">
                <h2>À propos de ce produit</h2>
                <p>{product.description}</p>
              </div>
              <a
                className="button secondary"
                href="#comments-title"
                onClick={(event) => {
                  event.preventDefault();
                  document
                    .getElementById("comments-title")
                    ?.scrollIntoView({ behavior: "smooth" });
                }}
              >
                Voir les commentaires ↓
              </a>
            </div>
          </article>
        )
      )}
      {!notFound && (
        <Comments productId={id} canCreate={!!product && !loading && !error} />
      )}
    </>
  );
}
