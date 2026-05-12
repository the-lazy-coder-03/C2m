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

The active copies live under `public/css`, `public/js`, `public/assets/admin`, `public/.user.ini`, and `DEPLOYMENT.md`.

## Apache / cPanel

Set the document root to the `public/` directory. The file `public/.htaccess` rewrites clean URLs to `public/front_controller.php` while allowing real static files to load normally.

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

## Vercel

Vercel does not run normal PHP apps by default. This repo keeps `vercel.json` and `api/index.php` as a lightweight compatibility bridge using the community `vercel-php` runtime. Static assets are served from `public/`, and all app routes are forwarded to the same `public/front_controller.php` router.

In Vercel project settings, keep the Root Directory set to the repository root. Do not set it to `public/`. If Vercel uses `public/` as the project root, it will treat PHP files like static files and browsers may download them instead of showing the app.

For the most reliable production hosting, prefer Apache/cPanel, Nginx/PHP-FPM on VPS/EC2, or another PHP-friendly host.
