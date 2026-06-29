# Deployment Notes

## Public Web Root

Use `public/` as the web root for this app.

Local run command:

```bash
php -S localhost:8000 -t public public/front_controller.php
```

Then test:

```text
http://localhost:8000/
http://localhost:8000/products
http://localhost:8000/login
http://localhost:8000/register
http://localhost:8000/dashboard
http://localhost:8000/create-product
http://localhost:8000/product/1
http://localhost:8000/this-route-does-not-exist
```

## Files Removed During Routing Cleanup

The app now uses `public/` as the web root, so duplicate non-public asset folders were removed:

- `css/`
- `js/`
- `admin/assets/`
- root `.user.ini`
- duplicate `DEPLOYMENT_CHECKLIST.md`

The active frontend assets live under `public/assets`.

## Apache / cPanel

For an optional Apache deployment, set the document root to `public/` and create an `.htaccess` file with:

```apache
DirectoryIndex front_controller.php
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ front_controller.php [QSA,L]
```

## Nginx / VPS / EC2

Example server block snippet:

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/localmarket/public;
    index front_controller.php;

    location / {
        try_files $uri $uri/ /front_controller.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

Adjust the PHP-FPM socket path for your installed PHP version.

## GitHub Actions CI/CD

This repo deploys to EC2 from `.github/workflows/deploy.yml`.

The workflow runs PHP syntax linting on pushes and pull requests to `master`.
After a successful push to `master`, it SSHes into the EC2 instance and runs a
fast-forward `git pull` in `/var/www/localmarket`, then validates and reloads
nginx.

Add these repository secrets in GitHub Actions:

```text
EC2_SSH_KEY   Private SSH key with access to the EC2 instance. Required.
EC2_HOST      EC2 public IP or hostname. Optional; defaults to 35.166.153.71.
EC2_USER      SSH username. Optional; defaults to ubuntu.
EC2_APP_DIR   App directory. Optional; defaults to /var/www/localmarket.
```

The current EC2 deployment expects:

```text
Host: 35.166.153.71
User: ubuntu
App directory: /var/www/localmarket
Web root: /var/www/localmarket/public
```

## Vercel

Vercel does not run normal PHP apps by default. This repo keeps `vercel.json` and `api/index.php` as a lightweight compatibility bridge using the community `vercel-php` runtime. Static assets are served from `public/`, and all app routes are forwarded to the same `public/front_controller.php` router.

In Vercel project settings, keep the Root Directory set to the repository root. Do not set it to `public/`. If Vercel uses `public/` as the project root, it will treat PHP files like static files and browsers may download them instead of showing the app.

If the deployed site shows `404: NOT_FOUND`, Vercel is usually not reading the root `vercel.json`. Check **Settings → General → Root Directory** in Vercel and set it to the repository root, often shown as `./` or left blank. Then redeploy the latest commit with a cleared build cache.

For Neon Postgres on Vercel, set the database environment variables in **Settings → Environment Variables**. The app supports either separate `DB_*` values or one `DATABASE_URL`. Neon requires SSL, so use `DB_SSLMODE=require` when using separate values. If the host looks like `ep-example-123456.region.aws.neon.tech`, the app automatically passes the Neon endpoint ID from the first part of the host. You can also set `DB_ENDPOINT=ep-example-123456` explicitly if needed.

For the most reliable production hosting, prefer Apache/cPanel, Nginx/PHP-FPM on VPS/EC2, or another PHP-friendly host.
