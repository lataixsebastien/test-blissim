import { useEffect } from "react";
import { Link, Route, Routes, useLocation, useParams } from "react-router";
import CataloguePage from "./pages/CataloguePage";
import ProductPage from "./pages/ProductPage";

export default function App() {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);
  const catalogue = pathname === "/" || pathname === "/catalogue";

  return (
    <>
      <a
        className="skip-link"
        href="#main"
        onClick={(e) => {
          e.preventDefault();
          document.getElementById("main")?.focus();
        }}
      >
        Aller au contenu
      </a>
      <header className="site-header">
        <div className="header-inner">
          <Link to="/" className="brand" aria-label="Blissim, accueil">
            <img src="/images/blissim-logo.svg" alt="Blissim" width="132" height="41" />
          </Link>
          <nav aria-label="Navigation principale">
            <Link to="/" aria-current={catalogue ? "page" : undefined}>
              Le catalogue
            </Link>
            <a href="/api/doc" target="_blank" rel="noreferrer">
              Documentation API <span aria-hidden="true">↗</span>
            </a>
          </nav>
        </div>
      </header>
      <main id="main" className="page-shell" tabIndex={-1}>
        <Routes>
          <Route path="/" element={<CataloguePage />} />
          <Route path="/catalogue" element={<CataloguePage />} />
          <Route path="/products/:id" element={<ProductRoute />} />
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </main>
      <footer className="site-footer">
        <span className="brand small">
          <img src="/images/blissim-logo.svg" alt="Blissim" width="104" height="32" />
        </span>
        <p>Des découvertes à partager.</p>
        <span>Catalogue de démonstration · DummyJSON</span>
      </footer>
    </>
  );
}

function ProductRoute() {
  const { id } = useParams();
  return /^[1-9][0-9]{0,8}$/.test(id)
    ? <ProductPage key={id} id={Number(id)} />
    : <NotFoundPage />;
}

function NotFoundPage() {
  useEffect(() => {
    document.title = "Page introuvable · Blissim";
  }, []);

  return (
    <section className="not-found">
      <p className="eyebrow">404</p>
      <h1>Cette page est introuvable.</h1>
      <Link className="button" to="/">Retour au catalogue</Link>
    </section>
  );
}
