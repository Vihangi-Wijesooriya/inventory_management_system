-- =====================================================================
-- Web-Based Inventory Management System
-- Database Schema
-- =====================================================================
-- Run this in phpMyAdmin or via mysql CLI:
--   mysql -u root -p < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS inventory_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventory_db;

-- ---------------------------------------------------------------------
-- Drop in reverse dependency order (safe re-run)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- USERS  (admin + staff, role-based)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(100) NOT NULL UNIQUE,
  role           ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at  DATETIME NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  category_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(80)  NOT NULL UNIQUE,
  description  VARCHAR(255) NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SUPPLIERS
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
  supplier_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  contact_person VARCHAR(100) NULL,
  phone        VARCHAR(30)  NULL,
  email        VARCHAR(100) NULL,
  address      VARCHAR(255) NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_suppliers_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CUSTOMERS
-- ---------------------------------------------------------------------
CREATE TABLE customers (
  customer_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  NULL,
  email        VARCHAR(100) NULL,
  address      VARCHAR(255) NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customers_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCTS
-- ---------------------------------------------------------------------
CREATE TABLE products (
  product_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku           VARCHAR(50)  NOT NULL UNIQUE,
  name          VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  category_id   INT UNSIGNED NULL,
  unit_price    DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- selling price
  cost_price    DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- purchase price
  quantity      INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 10,
  expiry_date   DATE NULL,
  image_path    VARCHAR(255) NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(category_id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_products_name (name),
  INDEX idx_products_category (category_id),
  INDEX idx_products_quantity (quantity)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PURCHASES (stock in)  -- header + lines
-- ---------------------------------------------------------------------
CREATE TABLE purchases (
  purchase_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no  VARCHAR(30)  NOT NULL UNIQUE,
  supplier_id   INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,            -- who recorded it
  purchase_date DATE         NOT NULL,
  total_amount  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  notes         VARCHAR(255) NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id)
    REFERENCES suppliers(supplier_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_purchases_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_purchases_date (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
  purchase_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id      INT UNSIGNED NOT NULL,
  product_id       INT UNSIGNED NOT NULL,
  quantity         INT NOT NULL,
  unit_cost        DECIMAL(12,2) NOT NULL,
  subtotal         DECIMAL(14,2) NOT NULL,
  CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id)
    REFERENCES purchases(purchase_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_pi_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SALES (stock out)  -- header + lines
-- ---------------------------------------------------------------------
CREATE TABLE sales (
  sale_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(30)  NOT NULL UNIQUE,
  customer_id  INT UNSIGNED NULL,                 -- nullable: walk-in
  user_id      INT UNSIGNED NOT NULL,
  sale_date    DATE         NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  notes        VARCHAR(255) NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id)
    REFERENCES customers(customer_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sales_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_sales_date (sale_date)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  sale_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id      INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  quantity     INT NOT NULL,
  unit_price   DECIMAL(12,2) NOT NULL,
  subtotal     DECIMAL(14,2) NOT NULL,
  CONSTRAINT fk_si_sale FOREIGN KEY (sale_id)
    REFERENCES sales(sale_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_si_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_si_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STOCK MOVEMENTS  (audit trail of every quantity change)
-- ---------------------------------------------------------------------
CREATE TABLE stock_movements (
  movement_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id     INT UNSIGNED NOT NULL,
  movement_type  ENUM('purchase','sale','adjustment') NOT NULL,
  quantity_change INT NOT NULL,         -- +ve for in, -ve for out
  resulting_qty  INT NOT NULL,          -- snapshot after change
  reference_id   INT UNSIGNED NULL,     -- purchase_id or sale_id
  user_id        INT UNSIGNED NOT NULL,
  note           VARCHAR(255) NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sm_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_sm_product (product_id),
  INDEX idx_sm_date (created_at)
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================
-- Default admin: username = admin, password = Admin@123
-- Hash generated with PHP password_hash('Admin@123', PASSWORD_DEFAULT)
-- Both seed users have password: Admin@123  (change after first login!)
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin',  '$2y$10$XYlUQWsxo5zdNIEuC8jQu.3BYyv/GmDgYmTLjAKSHI/8pu8q3gWjO', 'System Administrator', 'admin@inventory.local', 'admin'),
('staff1', '$2y$10$XYlUQWsxo5zdNIEuC8jQu.3BYyv/GmDgYmTLjAKSHI/8pu8q3gWjO', 'Demo Staff',           'staff@inventory.local', 'staff');

INSERT INTO categories (name, description) VALUES
('Electronics', 'Phones, laptops, accessories'),
('Stationery', 'Pens, books, office supplies'),
('Groceries', 'Food and household items'),
('Clothing', 'Apparel and accessories');

INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('TechWorld Pvt Ltd', 'Ravi Perera', '+94 11 234 5678', 'sales@techworld.lk', 'Colombo 03'),
('PaperPlus Suppliers', 'Nimal Silva', '+94 11 998 7654', 'orders@paperplus.lk', 'Nugegoda'),
('FreshMart Distributors', 'Anoma Fernando', '+94 11 555 1212', 'info@freshmart.lk', 'Maharagama');

INSERT INTO customers (name, phone, email, address) VALUES
('Walk-in Customer', NULL, NULL, NULL),
('Saman Kumara', '+94 77 123 4567', 'saman@example.com', 'Kandy'),
('Dilini Jayawardena', '+94 71 987 6543', 'dilini@example.com', 'Galle');

INSERT INTO products (sku, name, description, category_id, unit_price, cost_price, quantity, reorder_level) VALUES
('SKU-0001', 'USB Flash Drive 32GB', 'SanDisk USB 3.0', 1, 1500.00, 1100.00, 50, 10),
('SKU-0002', 'Wireless Mouse',       'Logitech M170',    1, 2200.00, 1600.00, 30, 5),
('SKU-0003', 'A4 Notebook 200pg',    'Ruled, hard cover',2, 350.00,  220.00, 120, 20),
('SKU-0004', 'Ballpoint Pen (Blue)', 'Box of 10',        2, 250.00,  150.00, 8,   15), -- low stock demo
('SKU-0005', 'Basmati Rice 5kg',     'Premium grade',    3, 2800.00, 2300.00, 25, 10);
