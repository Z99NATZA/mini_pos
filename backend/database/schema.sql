-- Users table
CREATE TABLE IF NOT EXISTS users (
    id         SERIAL PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    name       VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20)  NOT NULL DEFAULT 'staff',
    image      VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    image      VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- Sizes
CREATE TABLE IF NOT EXISTS sizes (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- Types
CREATE TABLE IF NOT EXISTS types (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- Toppings
CREATE TABLE IF NOT EXISTS toppings (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id              SERIAL PRIMARY KEY,
    order_number    VARCHAR(20)   UNIQUE NOT NULL,
    cashier_name    VARCHAR(100)  NOT NULL,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    received_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    change_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT NOW()
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
    id              SERIAL PRIMARY KEY,
    order_id        INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    order_item_code VARCHAR(30)  NOT NULL,
    product_name    VARCHAR(100) NOT NULL,
    product_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
    size_name       VARCHAR(100) NOT NULL DEFAULT '',
    size_price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    type_name       VARCHAR(100) NOT NULL DEFAULT '',
    type_price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity        INTEGER      NOT NULL DEFAULT 1,
    amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT NOW()
);

-- Order item toppings
CREATE TABLE IF NOT EXISTS order_item_toppings (
    id              SERIAL PRIMARY KEY,
    order_id        INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    order_item_code VARCHAR(30)  NOT NULL,
    topping_name    VARCHAR(100) NOT NULL,
    topping_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT NOW()
);
