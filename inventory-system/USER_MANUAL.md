# User Manual — Inventory Management System

This guide explains how to use each part of the application.

---

## 1. Logging In

Visit the application URL. You'll see a login page.

- Enter your **username** and **password**
- Click **Sign in**

**Demo accounts (change passwords in production):**
- Admin: `admin` / `Admin@123`
- Staff: `staff1` / `Admin@123`

If your account is deactivated, login will fail with "Account is disabled."

---

## 2. Dashboard

The dashboard is the home page after login. It shows:

- **Active Products** — count of products marked active
- **Low Stock** — products at or below their reorder level
- **Today's Sales** — total revenue and number of transactions today
- **This Month** — running total of sales this calendar month
- **Sales Chart** — daily sales over the last 30 days
- **Low Stock Alerts** — top 8 products needing reorder (click to see all)
- **Top Products This Month** — best sellers by quantity
- **Quick Stats** — supplier / customer / out-of-stock counts

---

## 3. Categories

Used to group products (e.g. "Beverages", "Stationery").

- **Add:** Categories → New Category → fill name, optional description → Save
- **Edit:** click the pencil icon
- **Delete:** click the trash icon (admin only). Fails if products still reference the category.
- **Search:** by name or description using the search box

---

## 4. Products

The main inventory list.

### Adding a product

1. Products → New Product
2. Fill in:
   - **SKU** — your internal code (must be unique)
   - **Name** — product display name
   - **Category** — optional
   - **Cost Price** — what you pay
   - **Selling Price** — what you charge
   - **Initial Quantity** — current stock on hand
   - **Reorder Level** — alert threshold
   - **Expiry Date** — optional
   - **Image** — optional, 2 MB max (JPG, PNG, GIF, WebP)
3. Save

If you set an initial quantity > 0, the system records it as an "Initial stock on creation" adjustment in stock movements.

### Editing a product

Click the pencil icon. You can:
- Change any field
- Manually adjust the quantity (changes are logged as "Manual adjustment")
- Replace or remove the image

### Stock status

Each row shows a coloured badge:
- 🟢 **In stock** — qty above reorder level
- 🟡 **Low stock** — qty ≤ reorder level
- 🔴 **Out of stock** — qty ≤ 0

### Filtering

Use the filter bar:
- **Search** — by name or SKU
- **Category** — pick from active categories
- **Stock** — all / low stock / out of stock

### Deleting

Admin-only. Fails if the product has purchase or sale records — deactivate instead.

---

## 5. Suppliers & Customers

Same pattern for both:

- **Add:** click "New Supplier" / "New Customer"
- **Edit / Delete:** icons on the right of each row
- **Search:** by name, phone, or email

Fields:
- **Name** (required)
- **Phone**, **Email**, **Address** (optional)
- **Contact Person** (suppliers only)
- **Active** — uncheck to hide from the dropdowns in Purchase/Sale forms

Customers can be left blank on a sale (treated as **Walk-in**).

---

## 6. Recording a Purchase (Stock In)

Use this when you receive goods from a supplier.

1. Purchases → New Purchase
2. Pick the **Supplier**
3. Set the **Date**
4. Click **Add Line** for each product:
   - Pick the product (cost auto-fills)
   - Enter quantity received
   - Adjust unit cost if needed
5. The total updates automatically
6. Click **Save Purchase**

What happens behind the scenes:
- A unique reference is generated (e.g. `PUR-20260522-A1B2`)
- Each product's `quantity` is increased
- Each product's `cost_price` is updated to the most recent purchase cost
- A `stock_movements` entry is logged per line

After saving you see the read-only detail view.

---

## 7. Recording a Sale (Stock Out)

1. Sales → New Sale
2. Pick a customer (or leave blank for walk-in)
3. Set the **Date**
4. Click **Add Line** for each product:
   - Pick the product (current stock shown in the dropdown)
   - Enter quantity (cannot exceed available stock — the box turns red if it does)
   - Selling price auto-fills, override if needed
5. Click **Save Sale**

If stock is insufficient for any line, the entire sale is rolled back and an error is shown.

After saving you see the receipt view with a **Print** button.

---

## 8. Reports

Three reports are available: **Sales**, **Purchases**, **Stock**.

Each one has:
- **Filters** — date range (and supplier or stock filter where relevant)
- **Generate** — refresh the on-screen table
- **PDF** — download a formatted PDF (requires `composer install`)
- **CSV** — download for Excel
- **Print** — open a clean printable HTML view (you can also "Save as PDF" from your browser)

Use cases:
- **Sales report** — monthly revenue, transaction count
- **Purchase report** — what you've spent with each supplier
- **Stock report** — current inventory valuation; filter to low/out-of-stock for restocking lists

---

## 9. User Management (Admin Only)

The **Users** menu appears only for admins.

- **Add:** create users with admin or staff role, set initial password (min 6 chars)
- **Edit:** change details; leave the password field blank to keep the current one
- **Delete:** allowed except for your own account; fails if the user has recorded transactions (deactivate instead)

**Self-protection:** when editing your own account, you cannot change your role or deactivate yourself — this prevents accidental lockout.

---

## 10. Tips & Troubleshooting

- **Forgot password?** Ask an admin to set a new one via Users → Edit.
- **Wrong figures on dashboard?** The dashboard reads live from the database. Refresh the page.
- **Sale won't save with "Insufficient stock"?** Either record a Purchase to restock, or reduce the sale quantity.
- **Want to fix a wrong quantity?** Open the product, change the Quantity field — the change is logged as a manual adjustment.
- **Logging out:** click your name in the top-right → Sign out.

---

## Roles

| Action | Staff | Admin |
|---|---|---|
| View all data | ✓ | ✓ |
| Create/edit categories, products, suppliers, customers | ✓ | ✓ |
| Record purchases and sales | ✓ | ✓ |
| Generate reports | ✓ | ✓ |
| Delete records | — | ✓ |
| Manage users | — | ✓ |
