# Inventory Management System (REST API + Fetch Frontend)

Plain PHP 8 + MySQL + Bootstrap 5. All data operations go through a JSON REST
API (`/api/...`); pages are thin shells that render data with `fetch()`.

## Stack
- PHP 8 (no framework), PDO with prepared statements
- MySQL — Aiven cloud (live) or XAMPP (local), switch via `DB_ENV` in `config/database.php`
- Bootstrap 5.3 + Bootstrap Icons + Chart.js (CDN)
- Session-based auth, bcrypt password hashing

## API overview
| Resource | Endpoints |
|---|---|
| Auth | `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me` |
| Categories / Suppliers / Customers | full CRUD `GET/POST/PUT/DELETE /api/{resource}[/{id}]` |
| Products | CRUD (multipart for image upload; update via `POST` + `_method=PUT`), filters: `?q=`, `?category_id=`, `?low_stock=1` |
| Sales | `GET`, `GET /{id}` (with items), `POST` (transactional, stock-checked), `DELETE` (restores stock) |
| Purchases | same as sales; adds stock and updates cost price |
| Dashboard | `GET /api/dashboard` — stats, low stock, 7-day chart, top products |
| Reports | `GET /api/reports/{sales|purchases|inventory}?from=&to=&format=csv` |
| Users | admin-only CRUD with self-lockout protection |

All list endpoints support `?page=`, `?per_page=`, `?q=`.
State-changing requests require the `X-Requested-With: fetch` header (CSRF guard).

## Setup

### 1. Database (Aiven)
1. In your Aiven MySQL service, open **Databases** and create `inventory_db`.
2. Download the **CA certificate** from the service overview page and save it
   as `config/ca.pem`.
3. Connect with HeidiSQL/DBeaver (host, port 26742, user `avnadmin`, SSL with
   `ca.pem`) and run `database/inventory_db.sql` against `inventory_db`.

### 2. Local (XAMPP)
1. Set `DB_ENV` to `'local'` in `config/database.php`.
2. Create `inventory_db` in phpMyAdmin and import `database/inventory_db.sql`.
3. Copy the project into `htdocs/`, ensure Apache `mod_rewrite` is on
   (`.htaccess` routes `/api/*`).

### 3. Login
Default account: `admin` / `admin123` — **change it after first login** (Users page).

## Security notes
- `config/database.php` and `config/ca.pem` are git-ignored; commit
  `config/database.example.php` instead.
- Passwords hashed with bcrypt; sessions are HttpOnly + SameSite=Lax.
- All queries use prepared statements; product images are MIME- and
  size-validated and stored with random names.
