CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    product_api_id INTEGER NOT NULL CHECK (product_api_id > 0),
    author VARCHAR(120) NOT NULL CHECK (length(trim(author)) > 0),
    content TEXT NOT NULL CHECK (length(trim(content)) > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NULL
);
