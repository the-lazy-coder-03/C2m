# Deployment Checklist

## Entry Point

This is a normal PHP marketplace app.

- Main homepage: `index.php`
- Public app pages: `public/*.php`
- Admin pages: `admin/*.php`
- Vercel PHP front controller: `api/index.php`

## Files Changed

- `vercel.json` adds Vercel PHP runtime routing.
- `api/index.php` maps incoming requests to the existing PHP files without rewriting the app.
- `.gitignore` ignores local secrets and dependency folders.
- `DEPLOYMENT_CHECKLIST.md` documents deployment and hosting caveats.

## Vercel Deploy

From the project root, run:

```bash
vercel --prod
```

Use the production URL printed by the Vercel CLI after the deploy finishes. You can also find it in:

```text
Vercel Dashboard > Project > Deployments
```

Open the latest successful production deployment. If you see `DEPLOYMENT_NOT_FOUND`, check that you are using the current deployment URL and that the deployment did not fail or get deleted.

## Required Vercel Environment Variables

Set these in:

```text
Vercel Dashboard > Project > Settings > Environment Variables
```

Required:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
DB_SSLMODE
PUBLIC_WEB_BASE
JWT_SECRET
JWT_LIFETIME
JWT_COOKIE_NAME
```

If S3 image storage is enabled:

```text
PRODUCT_UPLOAD_RELATIVE_DIR
PRODUCT_IMAGE_MAX_BYTES
PRODUCT_IMAGE_MAX_COUNT
PRODUCT_IMAGE_S3_URL
PRODUCT_IMAGE_S3_REGION
PRODUCT_IMAGE_S3_URL_EXPIRES
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_SESSION_TOKEN
```

Do not commit `config/.env`.

## Vercel Caveats

Vercel does not run normal PHP apps by default. This project uses the community `vercel-php@0.7.4` runtime.

This app is still better suited to EC2, Apache, Nginx, or another traditional PHP host because it uses:

- PHP sessions for cart state, flash messages, and CSRF tokens.
- PostgreSQL connections from server-side PHP.
- Product image upload behavior. S3 is preferred for deployed environments.
- Multiple classic PHP pages rather than a single serverless-first entry point.

Use Vercel only if you are comfortable with the community PHP runtime and serverless limitations. For the most reliable production setup, deploy this app to EC2 with Apache/Nginx and PHP-FPM.

## Quick Checks

After deployment, test:

- `/`
- `/public/products.php`
- `/products.php`
- `/public/login.php`
- `/login.php`
- `/admin/login.php`

If static styling is missing, confirm `vercel.json` is present in the deployed project root and that the project root selected in Vercel is the repository root, not `public/`.
