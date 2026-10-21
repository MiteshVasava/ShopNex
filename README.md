# ShopNex Online Shopping Center — PHP + MySQL

ShopNex is a responsive PHP/MySQL e-commerce application using Bootstrap 5. It includes a public storefront, customer accounts, an authenticated admin area, permission-limited staff accounts, and a WordPress-style Manage Site editor.

## Included features

### Customer storefront
- Responsive home page, category browsing, product search/filtering, product details and product image gallery.
- Customer registration and login, separate customer session, profile editing, saved addresses, password changes and account-deletion/anonymization.
- Cart, checkout, coupon application, order history/details/invoices, wishlist, product reviews and support tickets.
- Editable public footer, contact email/phone, hero and up to three promotion banners.
- Three theme presets (Modern Purple, Premium Blue, Vibrant Gradient), separate fonts for body/headings/hero/product cards, image positioning and previews in Manage Site.

### Admin
- Private admin login, dashboard, products and multiple product-image upload (at least one image for a new product, up to 8 per upload), inventory, orders, customers, categories, coupons, reports, support, Manage Site, staff management and permission assignment.
- Admin profile and password change; admin/staff workspace has its own header/footer.
- Server-side role checks and active-account checks, CSRF-protected forms where implemented, password hashing and separate customer/operations sessions.

### Staff
- Private staff login and profile.
- Dashboard cards, quick actions, and statistics are filtered to the permissions granted by the admin.
- Server-side permissions are checked by protected staff pages; staff can only grant the limited permission set offered by their own team-management screen.

### Password recovery
- One-time, hashed, 60-minute password-reset links. ShopNex never sends an existing password by email.
- Email sending depends on PHP `mail()`/SMTP being configured on the server.

## Local install (XAMPP)

1. Back up any existing ShopNex files and database.
2. Put the `online-shopping-center` folder inside `htdocs`.
3. Start Apache and MySQL.
4. Create a database named `online_shopping_center` in phpMyAdmin.
5. Select that database and import `database/online_shopping_center.sql`. This schema is idempotent: it uses `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`, and no longer drops existing tables.
6. Open `http://localhost/online-shopping-center/`.

Demo accounts seeded by the SQL file:
- Admin: `admin@shopnex.local` / `Admin@123`
- Staff: `staff@shopnex.local` / `Staff@123`
- Customer: `customer@shopnex.local` / `Customer@123`

Change all demo passwords before deploying publicly. If an existing database already has users with these emails, `INSERT IGNORE` preserves the existing accounts/passwords.

## InfinityFree deployment

1. Back up the live files and export your database first.
2. Upload the project contents into `htdocs` so `index.php`, `admin/`, `includes/`, `assets/`, and `uploads/` are directly inside `htdocs`.
3. In InfinityFree Control Panel, create/select the MySQL database. Import `database/online_shopping_center.sql` **into the selected database** using phpMyAdmin. Do not create a database from inside the SQL file; select the database in phpMyAdmin first.
4. Edit `includes/config.php`. Set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` to the exact MySQL details shown by InfinityFree. Do not use the XAMPP defaults (`127.0.0.1`, `root`, blank password) on InfinityFree.
5. Ensure `uploads/site/` and `uploads/products/` are writable by PHP if the hosting control panel permits folder permissions.
6. Open `https://shopnex.infinityfreeapp.com/`. The base URL is detected automatically for root deployment and for a local subfolder.
7. Test login, products, upload, cart, checkout, orders, Manage Site and staff permissions using test accounts before using the store for real customers.

If you are updating an already-populated live database, back it up first. The included SQL no longer drops tables, but `CREATE TABLE IF NOT EXISTS` does not alter incompatible old columns; review schema differences if you have customized your database.

## Private login URLs — owner/developer README only

Do not link these in the public navigation. Knowing a URL does not grant access; authentication and server-side role checks are still required.

- **Owner / Administrator:** `https://shopnex.infinityfreeapp.com/nex-owner-console-7f3a9c.php`
- **Staff:** `https://shopnex.infinityfreeapp.com/nex-staff-console-2d8b6e.php`
- **Normal customer login:** `https://shopnex.infinityfreeapp.com/login.php`

For local XAMPP:
- `http://localhost/online-shopping-center/nex-owner-console-7f3a9c.php`
- `http://localhost/online-shopping-center/nex-staff-console-2d8b6e.php`
- `http://localhost/online-shopping-center/login.php`

These private URLs are intentionally absent from the public site's navigation. For production security, also use strong unique passwords, HTTPS, a current PHP version, least-privilege database credentials, and ideally server/IP/VPN restrictions for the admin routes.

## Email configuration

The forgot-password feature creates a one-time reset token and calls PHP `mail()`. On InfinityFree/XAMPP, email may not work until the hosting mail transport is configured. Configure a verified sender and SMTP/mail transport, then test reset delivery. Never configure the site to email stored passwords.

## Important notes

- The included checkout supports the application's configured payment methods but does not include a third-party payment gateway. Do not claim online card payment is active unless a provider is integrated.
- No software can be guaranteed glitch-free without testing against the actual hosting account, PHP version, database schema, mail configuration and browser workflows. The package has been syntax-checked, but it still needs end-to-end testing on the target host.
