# LocalMarket C2C Marketplace

LocalMarket is a PHP/PostgreSQL customer-to-customer marketplace where users can register, log in, create listings with images, browse products, filter by category, view account details, and delete their own listings.

## Main Entry Files
- `index.php` Public homepage. Loads the latest active listings from PostgreSQL, displays category links, includes the main navbar/footer, and links to product details.
- `includes/navbar.php` Homepage navbar. Reads the JWT cookie and switches between login/register links and account/listing/logout links.
- `includes/footer.php` Homepage footer. Loads Bootstrap JavaScript and `js/script.js`.
- `css/style.css` Homepage styles.
- `js/script.js` Homepage JavaScript for smooth scrolling, mobile nav, listing search, and button hover motion.

## Public Website
- `public/products.php` Browse page. Shows active products and supports category filtering with `?category=CategoryName`.
- `public/product.php` Product details page. Shows all images for a listing and shows a delete button only to the listing owner.
- `public/sell_product.php` Seller listing form. Requires login, creates a product, and uploads multiple images.
- `public/delete_listing.php` Owner-only listing delete handler. Validates POST and CSRF token, deletes the product, and removes uploaded image files.
- `public/my_listings.php` Logged-in seller page. Shows the current user's listings and delete buttons.
- `public/account.php` Logged-in account page. Shows profile details and listing counts.
- `public/register.php` Registration form.
- `public/register_process.php` Registration handler. Creates a user, hashes the password, and issues a JWT login cookie.
- `public/login.php` Login form.
- `public/login_process.php` Login handler. Verifies the password and issues a JWT login cookie.
- `public/logout.php` Clears the JWT cookie and PHP flash session.
- `public/includes/market_nav.php` Shared navbar for public pages.
- `public/assets/css/marketplace.css` Public page styles.
- `public/assets/js/marketplace.js` Seller image preview JavaScript.
- `public/assets/images/product-placeholder.svg` Default product image shown when a listing has no uploaded image.
- `public/uploads/products/.gitkeep` Keeps the uploads folder present in the project.
- `public/uploads/products/` Runtime folder where uploaded listing images are stored.

## Admin Website
- `admin/index.php` Admin entry redirect. Sends admins to `dashboard.php` if logged in or `login.php` if not.
- `admin/login.php` Admin login form.
- `admin/auth.php` Admin login handler. Reads admin username/password from config.
- `admin/logout.php` Admin logout handler.
- `admin/dashboard.php` Admin dashboard page.
- `admin/users.php` Admin users page.
- `admin/products.php` Admin products page.
- `admin/product_edit.php` Admin listing editor. Lets admins update seller listing details, price, category, status, and visibility.
- `admin/orders.php` Admin orders page.
- `admin/settings.php` Admin settings page.
- `admin/partials/header.php` Shared admin auth guard, sidebar, and page header.
- `admin/partials/footer.php` Shared admin footer and scripts.
- `admin/assets/css/admin.css` Admin-only styles.
- `admin/assets/js/admin.js` Admin-only JavaScript.
- `admin/assets/images/logo.svg` Admin logo.

## Application Helpers
- `app/helpers/jwt_helper.php` JWT creation, validation, cookie login state, and logged-in user guard.
- `app/helpers/session_helper.php` PHP session helpers, flash-session cleanup, and CSRF token helpers.
- `app/helpers/product_image_helper.php` Upload path helpers, product image validation, multi-image storage, placeholder URL handling, and uploaded-file deletion.

## Configuration
- `config/.env` Local environment values. Keep this private and do not commit real credentials.
- `config/.env.example` Safe sample environment values for setup.
- `config/config.php` Loads key-value pairs from `config/.env` and exposes `config_get()`.
- `config/database.php` Creates the PostgreSQL PDO connection.
- `config/sql code` PostgreSQL schema for users, categories, products, product images, orders, payments, and constraints.
- `.user.ini` PHP upload settings for shared hosting/PHP-FPM when the project root is scanned.
- `public/.user.ini` PHP upload settings for shared hosting/PHP-FPM when `public/` is the web root.
- `.gitignore` Keeps local secrets, macOS metadata, and runtime uploads out of version control.

## Documentation
- `docs/product-image-upload-and-ec2-migration.md` Notes for product image storage and moving uploads from local development to EC2.

## Running Locally
```bash
php -d upload_max_filesize=10M -d post_max_size=80M -d max_file_uploads=20 -d memory_limit=256M -S localhost:8000
```

Open:
```text
http://localhost:8000
```
