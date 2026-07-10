-- Inventory Management System schema
-- Run against `inventory_db` (create the database first on Aiven / phpMyAdmin).

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sale_items, sales, purchase_items, purchases, products, categories, suppliers, customers, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(30),
    email      VARCHAR(150),
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(30),
    email      VARCHAR(150),
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    sku           VARCHAR(50)  NOT NULL UNIQUE,
    price         DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
    quantity      INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 5,
    image         VARCHAR(100),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchases (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id   INT NOT NULL,
    user_id       INT NOT NULL,
    invoice_no    VARCHAR(40) NOT NULL UNIQUE,
    total         DECIMAL(14,2) NOT NULL DEFAULT 0,
    purchase_date DATETIME NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_purchases_user     FOREIGN KEY (user_id)     REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL,
    unit_cost   DECIMAL(12,2) NOT NULL,
    subtotal    DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_pitems_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    CONSTRAINT fk_pitems_product  FOREIGN KEY (product_id)  REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    user_id     INT NOT NULL,
    invoice_no  VARCHAR(40) NOT NULL UNIQUE,
    total       DECIMAL(14,2) NOT NULL DEFAULT 0,
    sale_date   DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_sales_user     FOREIGN KEY (user_id)     REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    sale_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal   DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_sitems_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id),
    CONSTRAINT fk_sitems_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username `admin`, password `admin123` (change after first login).
INSERT INTO users (name, username, password_hash, role) VALUES
('Administrator', 'admin', '$2y$10$KiSSiANa.59J0Avtw3AhAOPleIefmLlVwGPpJ1OQ/iCKF5depatJK', 'admin');

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Stationery',  'Office and school supplies');
