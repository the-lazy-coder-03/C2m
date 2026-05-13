# Product Image Uploads and EC2 Migration

## Database Change

Use PostgreSQL. Store file paths only, not image binary data and not full URLs.

```sql
ALTER TABLE product_images
RENAME COLUMN image_url TO image_path;

ALTER TABLE product_images
ALTER COLUMN image_path SET NOT NULL;

ALTER TABLE product_images
DROP CONSTRAINT IF EXISTS product_images_product_id_fkey;

ALTER TABLE product_images
ADD CONSTRAINT product_images_product_id_fkey
FOREIGN KEY (product_id)
REFERENCES products(product_id)
ON DELETE CASCADE;
```

Run the SQL above directly in PostgreSQL when you need to migrate an existing database.

## Stored Path Format

The database stores paths like:

```text
uploads/products/product_25_abc123.webp
```

The database must not store paths like:

```text
/Users/name/project/public/uploads/products/image.jpg
/var/www/project/public/uploads/products/image.jpg
```

When S3 storage is enabled, the database still stores the same relative path. The app turns that path into an S3 URL when it renders product images.

## S3 Product Image Storage

To store new product photos in S3, set `PRODUCT_IMAGE_S3_URL` in `config/.env` to your bucket URL:

```text
PRODUCT_IMAGE_S3_URL=https://your-bucket.s3.af-south-1.amazonaws.com
PRODUCT_IMAGE_S3_REGION=af-south-1
PRODUCT_IMAGE_S3_URL_EXPIRES=3600
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_SESSION_TOKEN=
```

You can also paste a URL that already points at the product upload folder:

```text
PRODUCT_IMAGE_S3_URL=https://your-bucket.s3.af-south-1.amazonaws.com/uploads/products
```

The app uploads objects using keys like `uploads/products/product_25_abc123.webp`. Your bucket or prefix must allow `s3:PutObject` for uploads, `s3:DeleteObject` for listing cleanup, and `s3:GetObject` for private signed image URLs. `PRODUCT_IMAGE_S3_URL_EXPIRES` controls how long each generated browser image URL remains valid, in seconds.

Leave `PRODUCT_IMAGE_S3_URL` blank to keep using local filesystem storage in `public/uploads/products/`.

## Files Added

- `config/database.php`
- `app/helpers/product_image_helper.php`
- `public/sell_product.php`
- `public/products.php`
- `public/product.php`
- `public/assets/images/product-placeholder.svg`
- `public/assets/css/marketplace.css`
- `public/uploads/products/.gitkeep`

## Local Mac Setup

Use these `.env` values for the current project-root PHP server setup:

```text
DB_HOST=localhost
DB_PORT=5432
DB_NAME=eca
DB_USER=matthewanton
DB_PASS=
DB_SSLMODE=prefer
PUBLIC_WEB_BASE=/public
PRODUCT_UPLOAD_RELATIVE_DIR=uploads/products
PRODUCT_IMAGE_MAX_BYTES=10485760
PRODUCT_IMAGE_MAX_COUNT=8
PRODUCT_IMAGE_S3_URL=
PRODUCT_IMAGE_S3_REGION=
PRODUCT_IMAGE_S3_URL_EXPIRES=3600
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_SESSION_TOKEN=
```

Start the server from the project root:

```bash
php -d upload_max_filesize=10M -d post_max_size=80M -d max_file_uploads=20 -d memory_limit=256M -S localhost:8000
```

Open:

```text
http://localhost:8000/public/sell_product.php
http://localhost:8000/public/products.php
```

## EC2 Deployment Checklist

1. Copy the project to the EC2 server.
2. Set Apache or Nginx document root to the project `public/` folder if possible.
3. Update `.env` for the EC2 PostgreSQL host, database, username, and password.
4. If the EC2 document root is `public/`, set `PUBLIC_WEB_BASE=` in `.env`.
5. Copy existing uploads to `public/uploads/products/`.
6. Create the upload folder if it does not exist.
7. Set safe ownership and permissions so PHP can write uploaded files.
8. Run the PostgreSQL migration.
9. Test creating a product with multiple images.

## Copy Uploads to EC2

From your Mac, run this from the project root:

```bash
rsync -av public/uploads/products/ ubuntu@YOUR_EC2_IP:/var/www/localmarket/public/uploads/products/
```

Existing database paths continue working because they remain relative to `public/`.

## Create Upload Folder on Ubuntu

```bash
sudo mkdir -p /var/www/localmarket/public/uploads/products
```

## Safe Permissions

For Apache on Ubuntu:

```bash
sudo chown -R www-data:www-data /var/www/localmarket/public/uploads
sudo find /var/www/localmarket/public/uploads -type d -exec chmod 755 {} \;
sudo find /var/www/localmarket/public/uploads -type f -exec chmod 644 {} \;
```

For Nginx with PHP-FPM on Ubuntu, the PHP user is commonly `www-data`, but check your pool config:

```bash
ps aux | grep php-fpm
```

Then apply ownership using that PHP user.

Do not use `chmod 777` for normal deployment. It makes the folder writable by every user on the server. Use it only as a short temporary debugging test, then revert to safer ownership and permissions.

## What Changes Between Mac and EC2

Usually only `.env` changes:

```text
DB_HOST=
DB_PORT=
DB_NAME=
DB_USER=
DB_PASS=
DB_SSLMODE=
PUBLIC_WEB_BASE=
```

The image paths stored in PostgreSQL do not change.

For Vercel with Neon Postgres, set `DB_SSLMODE=require`. If you use `DATABASE_URL`, include Neon's `sslmode=require` query parameter. The app automatically derives `DB_ENDPOINT` from Neon hosts like `ep-example-123456.region.aws.neon.tech`, but you can set `DB_ENDPOINT=ep-example-123456` manually if Vercel still reports that the endpoint ID is missing.
