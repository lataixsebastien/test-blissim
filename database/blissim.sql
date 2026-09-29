-- Partie SQL du test technique Blissim.
-- Dialecte cible : PostgreSQL 16.
-- Executer dans une base vide ; ce script ne supprime aucune table existante.

BEGIN;

CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    sku VARCHAR(64) NOT NULL UNIQUE CHECK (length(trim(sku)) > 0),
    name VARCHAR(255) NOT NULL CHECK (length(trim(name)) > 0),
    description TEXT,
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE CHECK (length(trim(email)) > 0),
    first_name VARCHAR(120) NOT NULL CHECK (length(trim(first_name)) > 0),
    last_name VARCHAR(120) NOT NULL CHECK (length(trim(last_name)) > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    status VARCHAR(32) NOT NULL DEFAULT 'paid',
    ordered_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT orders_status_check CHECK (status IN ('pending', 'paid', 'shipped', 'cancelled'))
);

CREATE TABLE order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(10, 2) NOT NULL CHECK (unit_price >= 0),
    -- Prix historique independant du prix courant dans products.
    -- Aucune unicite sur product_id : un produit peut etre commande plusieurs fois.
    total_price NUMERIC(20, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED
);

CREATE INDEX idx_orders_customer_id ON orders(customer_id);
CREATE INDEX idx_orders_ordered_at ON orders(ordered_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

-- Table utilisee par l'application Symfony/React.
-- Les produits affiches viennent de DummyJSON, donc product_api_id n'est pas une cle etrangere.
CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    product_api_id INTEGER NOT NULL CHECK (product_api_id > 0),
    author VARCHAR(120) NOT NULL CHECK (length(trim(author)) > 0),
    content TEXT NOT NULL CHECK (length(trim(content)) > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NULL
);

CREATE INDEX idx_comments_product_api_id ON comments(product_api_id);
CREATE INDEX idx_comments_created_at ON comments(created_at);

INSERT INTO products (id, sku, name, description, price) VALUES
    (1, 'BLISSIM-SERUM-001', 'Serum eclat vitamine C', 'Serum visage illuminateur.', 29.90),
    (2, 'BLISSIM-CREME-002', 'Creme hydratante douceur', 'Creme hydratante quotidienne.', 24.50),
    (3, 'BLISSIM-MASK-003', 'Masque nuit reparateur', 'Masque de nuit pour peau fatiguee.', 18.90),
    (4, 'BLISSIM-OIL-004', 'Huile demaquillante', 'Huile sensorielle pour le demaquillage.', 16.50),
    (5, 'BLISSIM-BAUME-005', 'Baume levres', 'Produit jamais commande.', 9.90);

INSERT INTO customers (id, email, first_name, last_name) VALUES
    (1, 'alice.martin@example.com', 'Alice', 'Martin'),
    (2, 'lea.bernard@example.com', 'Lea', 'Bernard'),
    (3, 'camille.durand@example.com', 'Camille', 'Durand'),
    (4, 'nora.petit@example.com', 'Nora', 'Petit'),
    (5, 'alice.martin2@example.com', 'Alice', 'Martin'),
    (6, 'emma.robert@example.com', 'Emma', 'Robert');

INSERT INTO orders (id, customer_id, status, ordered_at) VALUES
    (1, 1, 'paid', CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (2, 2, 'paid', CURRENT_TIMESTAMP - INTERVAL '5 days'),
    (3, 3, 'shipped', CURRENT_TIMESTAMP - INTERVAL '10 days'),
    (4, 4, 'paid', CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (5, 1, 'cancelled', CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (6, 1, 'paid', CURRENT_TIMESTAMP - INTERVAL '3 days'),
    (7, 5, 'shipped', CURRENT_TIMESTAMP - INTERVAL '7 days'),
    (8, 4, 'pending', CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (9, 4, 'paid', CURRENT_TIMESTAMP + INTERVAL '1 day');

INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
    (1, 1, 2, 27.90),
    (1, 2, 1, 24.50),
    (2, 1, 1, 29.90),
    (2, 3, 3, 18.90),
    (3, 4, 1, 16.50),
    (4, 2, 4, 24.50),
    (4, 3, 1, 18.90),
    (5, 1, 100, 25.90),
    (6, 1, 1, 28.90),
    (7, 1, 2, 27.90),
    (8, 4, 100, 16.50),
    (9, 4, 100, 16.50);

INSERT INTO comments (product_api_id, author, content, created_at) VALUES
    (1, 'Alice', 'Tres bon produit, texture agreable.', CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (1, 'Lea', 'Livraison rapide et produit conforme.', CURRENT_TIMESTAMP - INTERVAL '12 hours'),
    (2, 'Camille', 'La creme hydrate bien sans coller.', CURRENT_TIMESTAMP - INTERVAL '6 hours');

SELECT setval('products_id_seq', (SELECT MAX(id) FROM products));
SELECT setval('customers_id_seq', (SELECT MAX(id) FROM customers));
SELECT setval('orders_id_seq', (SELECT MAX(id) FROM orders));

-- Requete demandee 1 :
-- Recuperer le prenom et le nom des clients ayant commande le produit product_id = 1.
-- Ici "commande" inclut tous les statuts, sans restriction de date.
-- EXISTS renvoie chaque client une seule fois et preserve les clients homonymes.
-- Attendu : Lea Bernard, Alice Martin, Alice Martin (deux clients distincts).
SELECT c.first_name, c.last_name
FROM customers c
WHERE EXISTS (
    SELECT 1
    FROM orders o
    INNER JOIN order_items oi ON oi.order_id = o.id
    WHERE o.customer_id = c.id AND oi.product_id = 1
)
ORDER BY c.last_name, c.first_name, c.id;

-- Requete demandee 2 :
-- Recuperer les produits vendus au cours des 7 derniers jours,
-- avec leur nom et la somme totale des quantites vendues,
-- tries par quantite decroissante.
-- Une vente correspond ici a une commande payee ou expediee.
-- Fenetre glissante inclusive : [maintenant - 7 jours, maintenant].
-- CURRENT_TIMESTAMP reste constant dans cette transaction pour tester la borne.
-- Attendu : Serum eclat vitamine C = 6 ; Creme hydratante douceur = 5 ;
-- Masque nuit reparateur = 4. Commandes anciennes, futures, en attente et
-- annulees exclues ; produit jamais commande absent.
SELECT p.name, SUM(oi.quantity) AS total_quantity_sold
FROM products p
INNER JOIN order_items oi ON oi.product_id = p.id
INNER JOIN orders o ON o.id = oi.order_id
WHERE o.status IN ('paid', 'shipped')
  AND o.ordered_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'
  AND o.ordered_at <= CURRENT_TIMESTAMP
GROUP BY p.id, p.name
ORDER BY total_quantity_sold DESC, p.name ASC, p.id ASC;

COMMIT;
