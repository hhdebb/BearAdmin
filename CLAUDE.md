# CLAUDE.md — BearAdmin Codebase Guide

This document describes the BearAdmin codebase structure, development workflow, and conventions for AI assistants working in this repository.

---

## Project Overview

**BearAdmin** is a PHP admin panel framework built on **ThinkPHP 6.0** with **AdminLTE 3.2** frontend. It provides a production-ready backend management system with RBAC, operation logging, file management, and a RESTful API module.

- **Language:** PHP >= 7.4 (PHP 8.1+ for enum support)
- **Framework:** ThinkPHP 6.0
- **UI:** AdminLTE 3.2 (Bootstrap-based)
- **Database:** MySQL 5.7+
- **Default Language:** zh-cn (Simplified Chinese)
- **Demo:** https://demo.bearadmin.com/
- **Docs:** https://www.kancloud.cn/codebear/admin_tp6

---

## Repository Structure

```
BearAdmin/
├── app/                    # All application modules
│   ├── admin/              # Backend admin panel module
│   │   ├── config/         # Admin-specific config overrides
│   │   ├── controller/     # Admin HTTP controllers
│   │   ├── model/          # Admin models
│   │   ├── service/        # Admin business logic
│   │   ├── validate/       # Input validation rules
│   │   ├── traits/         # Reusable admin traits
│   │   ├── exception/      # Admin exception classes
│   │   ├── listener/       # Event listeners
│   │   └── view/           # Blade-like template files
│   ├── api/                # REST API module
│   │   ├── controller/     # API controllers
│   │   ├── middleware/      # API middleware (rate limiting, auth)
│   │   ├── service/        # API business logic
│   │   ├── traits/         # API-specific traits
│   │   └── exception/      # API exception classes
│   ├── common/             # Shared code across all modules
│   │   ├── model/          # Shared models
│   │   ├── service/        # Shared services
│   │   ├── validate/       # Shared validators
│   │   ├── exception/      # Base exceptions
│   │   ├── enum/           # PHP 8.1+ enumerations
│   │   └── taglib/         # Custom template tag library (Bear)
│   ├── index/              # Frontend/public module
│   └── command/            # CLI commands (5 commands)
├── config/                 # Global configuration files (15 files)
├── database/               # Migrations (10 migrations)
│   └── migrations/
├── extend/                 # Extended utilities and helpers (64 files)
├── public/                 # Web root (entry point, assets)
│   ├── index.php           # Application entry point
│   └── .htaccess           # Apache URL rewriting
├── route/                  # Route definition files
├── composer.json           # PHP dependency manifest
├── .env.example            # Environment variable template
└── think                   # CLI entry point: `php think <command>`
```

---

## Architecture

### Multi-App Pattern

ThinkPHP multi-app mode splits the application into isolated modules:

| Module | URL Prefix | Purpose |
|--------|-----------|---------|
| `admin` | `/admin` | Backend management panel |
| `api`   | `/api`   | RESTful API for mobile/SPA clients |
| `index` | `/`      | Public-facing frontend |
| `common`| —        | Shared code (no routes) |

### Layered Architecture (per module)

```
HTTP Request
    ↓
Controller  (handles HTTP, delegates to service)
    ↓
Service     (business logic, orchestration)
    ↓
Model       (data access via Think-ORM)
    ↓
Database    (MySQL)
```

**Traits** provide cross-cutting concerns mixed into controllers/models:
- `AdminAuthTrait` — authentication & permission checks
- `AdminTreeTrait` — tree-structured data (menus, categories)
- `AdminPhpOffice` — Excel import/export
- `AdminSettingForm` — settings form builder
- `SettingContent` — settings storage/retrieval

---

## Key Configuration Files

| File | Description |
|------|-------------|
| `config/app.php` | App name, timezone (`Asia/Shanghai`), debug mode |
| `config/database.php` | MySQL connection, UTF8MB4, auto timestamps |
| `config/route.php` | URL suffix `.html`, pathinfo separator `/` |
| `config/view.php` | Think template engine, `Bear` custom taglib |
| `config/lang.php` | Default language: `zh-cn` |
| `.env` | Runtime environment overrides (not committed) |
| `.env.example` | Template for `.env` — copy and fill before running |

### Environment Variable Structure (`.env`)

```ini
[APP]
DEBUG = false
TIMEZONE = Asia/Shanghai
API_KEY = your_key

[DATABASE]
HOSTNAME = 127.0.0.1
DATABASE = bearadmin
USERNAME = root
PASSWORD = password
CHARSET = utf8mb4

[CACHE]
DRIVER = file     # or: redis

[REDIS]
HOST = 127.0.0.1
PORT = 6379

[LANG]
DEFAULT_LANG = zh-cn

[API]
JWT_SECRET = your_jwt_secret
TOKEN_EXPIRE = 86400
```

---

## Development Setup

```bash
# 1. Clone the repository
git clone <repo-url>
cd BearAdmin

# 2. Install PHP dependencies
composer install

# 3. Set up environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Create database (UTF8MB4 charset required)
# mysql> CREATE DATABASE bearadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 5. Run database migrations
php think migrate:run

# 6. Initialize environment (generates keys)
php think init:env

# 7. Access the admin panel
# http://localhost/admin
# Default accounts: develop_admin, super_admin (passwords generated randomly)
```

### Useful CLI Commands

```bash
php think migrate:run              # Run pending migrations
php think migrate:rollback         # Roll back last migration batch
php think reset:admin_password     # Reset an admin user's password
php think generate:jwt_key         # Generate a new JWT secret
php think generate:app_key         # Generate the app encryption key
php think init:env                 # Initialize environment configuration
php think service:discover         # Discover registered services
php think vendor:publish           # Publish vendor assets
```

---

## Coding Conventions

### Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Namespaces | `app\{module}\{layer}` | `app\admin\controller` |
| Classes | PascalCase | `AdminUserController` |
| Methods | camelCase | `checkLogin()` |
| Properties | camelCase | `$adminUser` |
| Constants | UPPER_SNAKE_CASE | `BOOLEAN_TEXT` |
| Database tables | snake_case | `admin_user`, `admin_role` |
| Migration files | timestamp prefix | `20200804023050_admin_user.php` |

### Class Naming Pattern

- `Admin*Controller` — Admin module controllers (extend `AdminBaseController`)
- `Admin*Model` — Admin module models (extend `AdminBaseModel`)
- `Admin*Service` — Admin business logic services
- `Admin*Validate` — Admin validation rules
- `Api*Controller` — API module controllers (extend `ApiBaseController`)
- `Common*Model` — Shared models (extend `CommonBaseModel`)

### Base Class Hierarchy

```
think\Model
    └── CommonBaseModel      # Query scopes, soft delete, auto timestamps
            └── AdminBaseModel   # Admin-specific scopes, field filtering
            └── User / Setting / ... (common models)

think\Controller
    └── AdminBaseController  # Auth check, menu init, logging setup
            └── Admin*Controller

    └── ApiBaseController    # JWT auth, request parsing, pagination
            └── Api*Controller
```

---

## Authentication & Authorization

### Admin Panel (Session-based)

- Login handled in `app/admin/controller/AuthController.php`
- Auth logic in `app/admin/traits/AdminAuthTrait.php`
- `AdminBaseController::initialize()` calls `checkLogin()` and `checkAuth()` on every request
- Supports **single-device login** (concurrent session prevention)
- All admin actions are logged via `AdminLogService`

### API Module (JWT-based)

- JWT tokens issued by `app/api/controller/AuthController.php`
- Token validation in `ApiBaseController::initialize()`
- JWT utilities in `extend/` directory
- Configurable expiration via `API.TOKEN_EXPIRE` in `.env`

### RBAC Permission System

- `AdminRole` — Defines roles with associated menu/permission arrays
- `AdminMenu` — Hierarchical menu structure with permission nodes
- `AdminUser.role_id` — Assigned role for permission checks
- Super admin (`develop_admin`) bypasses all permission checks

---

## Database Conventions

- All tables use **UTF8MB4** charset
- All models enable **auto timestamps** (`create_time`, `update_time`)
- Soft deletes use `delete_time` (nullable timestamp)
- Primary keys: `id` (auto-increment integer)
- Foreign key style: `{table}_id` (e.g., `role_id`, `user_id`)

### Migration Files

Located in `database/migrations/`. Follow the existing naming pattern:

```php
// Format: YYYYMMDDHHmmss_table_name.php
class TableName extends \think\migration\Migrator
{
    public function change()
    {
        $table = $this->table('table_name');
        $table->addColumn('field', 'string', ['limit' => 50, 'comment' => '...']);
        $table->addTimestamps();
        $table->create();
    }
}
```

---

## Template System

- **Engine:** ThinkPHP native template engine (`.html` files)
- **Location:** `app/{module}/view/{controller}/{action}.html`
- **Custom Taglib:** `app\common\taglib\Bear` — registered in `config/view.php`
- **Template Variables:** Assigned via `$this->assign('key', $value)` in controllers
- **PJAX Support:** Partial page updates for admin navigation

---

## Key Models Reference

| Model | Table | Notes |
|-------|-------|-------|
| `AdminUser` | `admin_user` | Admin accounts, password auto-hashed |
| `AdminRole` | `admin_role` | RBAC roles with menu permissions |
| `AdminMenu` | `admin_menu` | Navigation tree, permission nodes |
| `AdminLog` | `admin_log` | Operation audit log headers |
| `AdminLogData` | `admin_log_data` | Audit log detail (request/response) |
| `User` | `user` | Frontend/API user accounts |
| `Setting` | `setting` | Key-value settings storage |
| `UserLevel` | `user_level` | User tier/level definitions |

---

## Adding New Admin Features

Typical workflow for adding a new admin CRUD module:

1. **Migration** — Create in `database/migrations/`
2. **Model** — Create in `app/admin/model/` extending `AdminBaseModel`
3. **Service** — Create in `app/admin/service/` extending `AdminBaseService`
4. **Validate** — Create in `app/admin/validate/` extending `AdminBaseValidate`
5. **Controller** — Create in `app/admin/controller/` extending `AdminBaseController`
6. **Views** — Create directory `app/admin/view/{controller}/` with `index.html`, `add.html`, `edit.html`
7. **Menu** — Add menu entry via the admin panel UI

The admin module includes a **code generator** in the extend utilities that can scaffold standard CRUD controllers.

---

## Git Workflow

- **Main branch:** `master`
- **Feature branches:** `claude/feature-name-XXXXX` (for AI-assisted work)
- Commit messages use Chinese (project convention): `feat: 功能描述`
- Common prefixes: `feat:` / `fear:` (feature), `fix:` (bug fix), `refactor:` (refactoring)

---

## Security Considerations

- Never commit `.env` — use `.env.example` as template
- Admin passwords are auto-hashed on save (do not pre-hash)
- CSRF tokens are validated for all state-changing requests
- Input is validated via `Validate` classes before reaching service/model
- SQL injection is prevented by ORM parameter binding
- See `SECURITY.md` for the vulnerability reporting policy
