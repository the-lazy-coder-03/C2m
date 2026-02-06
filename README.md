# C2M C2C E-commerce Project Structure

**Purpose**
This README explains the meaning of each top-level folder (e.g., `admin/`, `app/`, `config/`) and documents every file currently present and what is inside it.

**Top-Level Folders (What They Are For)**
- `admin/` The separate admin website. Only admins should access pages here. Holds admin entry point, admin pages, and admin-only assets.
- `public/` The public customer-facing website. Holds the public entry point, public pages, and public assets.
- `app/` Shared backend PHP application logic used by both the public and admin sites. This is where authentication, RBAC, CRUD, database access, and shared helpers live.
- `config/` Configuration and environment settings. Contains database credentials files and bootstrap config. This folder should remain outside the public web root for security.
- `storage/` Runtime files created by the app, such as logs and uploads.
- `docs/` Deliverable 2 documentation such as diagrams and screenshots.
- `scripts/` Utility scripts (setup, maintenance, etc.).
- `tests/` Test files for verifying functionality.

**Files And Contents**
- `admin/index.php` Admin site entry point placeholder. Currently empty (no PHP or HTML yet).
- `public/index.php` Public site entry point placeholder. Currently empty (no PHP or HTML yet).
- `public/test.php` PHP test page. Contains PHP error display settings, a greeting string, a sample array of items, a server time variable, and an HTML page that renders them.
- `config/.env` Environment variables for database credentials. Contains `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `DB_CHARSET` with placeholder values.
- `config/.env.example` Sample environment file for sharing/onboarding. Contains the same keys as `.env` with placeholder values.
- `config/config.php` Configuration bootstrap placeholder. Currently empty (no PHP yet).
- `README.md` This documentation file.

**Subfolders (What Goes Inside)**
- `admin/pages/` Admin HTML/PHP pages.
- `admin/assets/css/` Admin CSS files.
- `admin/assets/js/` Admin JavaScript files.
- `admin/assets/images/` Admin images.
- `public/pages/` Public HTML/PHP pages.
- `public/assets/css/` Public CSS files.
- `public/assets/js/` Public JavaScript files.
- `public/assets/images/` Public images.
- `app/auth/` Authentication logic (login, logout, password handling).
- `app/rbac/` Role-Based Access Control logic (roles, permissions, checks).
- `app/crud/` CRUD operations for core entities (listings, users, orders, etc.).
- `app/db/` Database access layer (connections, queries).
- `app/controllers/` Request handlers and business flow.
- `app/models/` Data models/entities.
- `app/views/` Shared PHP view templates.
- `app/helpers/` Helper utilities and reusable functions.
- `storage/logs/` Application logs.
- `storage/uploads/` User-uploaded files.
- `docs/diagrams/` Architecture and database diagrams.
- `docs/screenshots/` UI screenshots.
- `docs/deliverable-2/` Additional required deliverable documents.
- `scripts/` Utility scripts.
- `tests/` Test files.
