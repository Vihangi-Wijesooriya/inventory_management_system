# Web-Based Inventory Management System

**Project:** CTEC 43018 — Industrial Project
**Student:** W.M.V.S. Wijesooriya (CT/2020/050)
**Institution:** University of Kelaniya

A complete inventory management system for small businesses, built with PHP, MySQL, and Bootstrap 5.

---

## Features

- **Authentication** — username/password login with bcrypt, session security, role-based access (admin/staff)
- **Products** — CRUD with categories, SKU, cost/selling prices, image upload, reorder threshold, expiry date
- **Categories** — CRUD with product counts
- **Suppliers & Customers** — full CRUD with contact details
- **Purchases (Stock In)** — multi-line transactions, auto-update product stock, transactional with row locking
- **Sales (Stock Out)** — multi-line transactions, stock-availability check, walk-in customer support, printable receipt
- **Stock Movements** — every quantity change logged with audit trail (purchase / sale / adjustment)
- **Low Stock Alerts** — dashboard widget, filter on product list, dedicated stock report
- **Dashboard** — KPI cards, 30-day sales chart (Chart.js), top products, low-stock alerts
- **Reports** — Sales, Purchase, Stock — exportable as PDF (DomPDF), CSV, or printable HTML
- **User Management** — admin-only, with self-lockout protection

---

## Tech Stack

- PHP 8.0+ (PDO MySQL)
- MySQL 5.7+ / MariaDB 10+
- Bootstrap 5 + Bootstrap Icons (via CDN)
- Chart.js 4 (via CDN)
- DomPDF (via Composer, for PDF reports)

---

## Setup (XAMPP + VS Code)

### 1. Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- [VS Code](https://code.visualstudio.com/)
- (Optional, for PDF reports) [Composer](https://getcomposer.org/)

### 2. Drop into htdocs

Extract the project so the folder lives at:

```
C:\xampp\htdocs\inventory-system\
```

### 3. Start Apache + MySQL

Open XAMPP Control Panel → Start **Apache** and **MySQL**.

### 4. Import the database

1. Open http://localhost/phpmyadmin
2. Tab: **Import**
3. Choose file: `database/schema.sql`
4. Click **Import**

This creates the `inventory_db` database with seed data.

### 5. (Optional) Install Composer dependencies

PDF export requires DomPDF. From the project folder:

```bash
composer install
```

Without this, CSV export and the in-browser "Print" (Save as PDF) still work.

### 6. Visit the app

Open http://localhost/inventory-system/

**Demo credentials:**

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | `admin`  | `Admin@123` |
| Staff | `staff1` | `Admin@123` |

---

## Project Structure

```
inventory-system/
├── assets/
│   ├── css/app.css
│   └── js/app.js
├── config/
│   ├── app.php          ← BASE_URL, currency, paths
│   └── database.php     ← DB credentials
├── database/
│   └── schema.sql       ← Tables + seed data
├── includes/
│   ├── auth.php         ← Login, sessions, role checks
│   ├── bootstrap.php    ← Load config + start session
│   ├── helpers.php      ← e(), url(), csrf_*, money(), etc.
│   ├── header.php       ← Top nav + sidebar
│   └── footer.php
├── modules/
│   ├── auth/            ← login.php, logout.php
│   ├── dashboard/       ← Stats + charts
│   ├── categories/      ← CRUD
│   ├── products/        ← CRUD + image upload
│   ├── suppliers/       ← CRUD
│   ├── customers/       ← CRUD
│   ├── purchases/       ← Multi-line stock-in
│   ├── sales/           ← Multi-line stock-out
│   ├── reports/         ← Sales / Purchases / Stock — PDF/CSV/Print
│   └── users/           ← Admin only
├── public/
│   └── uploads/         ← Product images go here
├── vendor/              ← (after composer install)
├── composer.json
├── index.php            ← Entry — redirects based on auth
└── README.md
```

---

## End-to-End Test Workflow

1. **Login** as `admin / Admin@123`
2. **Categories** → New Category → "Stationery"
3. **Products** → New Product → SKU `PEN-001`, name "Blue Pen", category Stationery, cost 5, price 10, initial qty 50, reorder level 10
4. **Suppliers** → New Supplier → "Office World"
5. **Purchases** → New Purchase → supplier "Office World", add line: Blue Pen × 100 @ Rs. 5 → Save (product quantity becomes 150)
6. **Customers** → New Customer → "John Perera"
7. **Sales** → New Sale → customer "John Perera", add line: Blue Pen × 3 @ Rs. 10 → Save → print receipt (qty becomes 147)
8. **Dashboard** → today's sale appears, chart updates
9. **Reports → Sales Report** → set date range → click PDF / CSV
10. **Users** (admin only) → create a staff user → log out, log in as staff → confirm "Users" menu is hidden

---

## Security Notes

- All passwords hashed with `password_hash()` (bcrypt)
- All SQL via PDO prepared statements
- Session ID regenerated on login + every 30 minutes
- CSRF token on every POST form
- Session cookies marked `HttpOnly` and `SameSite=Lax`
- File uploads validated by MIME (not extension), size-capped at 2 MB
- `.htaccess` blocks direct browser access to `/config`, `/includes`, `/database`, `/vendor`

---

## Default Configuration

Edit `config/app.php` to change:

- `BASE_URL` — change if folder renamed or non-default port
- `CURRENCY_SYMBOL` — defaults to `Rs.`
- `LOW_STOCK_DEFAULT_THRESHOLD` — applied to new products by default

Edit `config/database.php` to change DB credentials (defaults: host=localhost, user=root, no password).
