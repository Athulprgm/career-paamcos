# Contributing to MultiCred

Thank you for your interest in contributing to the **MultiCred Deposit & Commission Management System** for **Pristine Agro Co-operative Society**. This document outlines technical prerequisites, recommended development practices, code review expectations, and verification procedures.

---

## 1. Important Repository & Project Notice

> [!NOTE]
> The current working directory is not initialized as a Git repository (`git status` reports `fatal: not a git repository`). The version control conventions detailed below are **recommendations** for when the repository is hosted under Git/GitHub/GitLab.

---

## 2. Technical Prerequisites

Ensure your local development environment meets the verified project specifications:

- **PHP**: `^8.3` or `^8.4` (verified runtime: PHP `8.4.25`).
  - Required PHP Extensions: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `gd`, `fileinfo`, `curl`.
- **Composer**: `^2.2` or later (verified runtime: Composer `2.10.2`).
- **Node.js & npm**: Node.js `^18.0` or `^20.0` / `^22.0` (verified runtime: Node `v22.23.2`, npm `10.9.8`).
- **Database Engine**: MySQL `8.0+` or MariaDB `10.4+` (default local port `3306`, database `multicred-deposit-commission`).
- **Web Server**: Apache / Nginx / PHP CLI built-in server (`php artisan serve`).

---

## 3. Local Development Setup

Follow these verified steps to run the application locally:

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure environment
# Copy .env.example to .env if not already present
cp .env.example .env
php artisan key:generate

# 3. Configure database settings in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=multicred-deposit-commission
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Run database migrations and seeders
php artisan migrate
php artisan db:seed --class=AgentRankSeeder

# 5. Link public storage for avatar and document uploads
php artisan storage:link

# 6. Install and build frontend assets
npm install
npm run build
# Or for hot-reloading development:
# npm run dev

# 7. Start the local server
php artisan serve
```

---

## 4. Recommended Branching & Commit Conventions

When version control is enabled, please follow standard **Conventional Commits**:

### Branch Naming Recommendations
- `feature/<ticket-or-short-name>`: New functionality or workflows (e.g. `feature/mrp-scheme-integration`).
- `bugfix/<ticket-or-short-name>`: Bug fixes (e.g. `bugfix/commission-rounding`).
- `refactor/<short-name>`: Code refactoring without changing behavior.
- `docs/<short-name>`: Documentation updates or additions.

### Commit Message Guidelines
Format commits using the Conventional Commits specification:
```
<type>(<scope>): <short description in present tense>

[optional body explaining rationale and background]

[optional footer, e.g., Closes #123]
```
Examples:
- `feat(commission): add monthly return plan interest schedule calculation`
- `fix(deposit): resolve rounding discrepancy on premature closure`
- `docs(api): document new staff allocation endpoints`

---

## 5. Coding Standards & Code Quality

All code must strictly adhere to the project's established conventions:

1. **PHP 8.4 Conventions**:
   - Explicit parameter and return type declarations for every method.
   - Constructor property promotion for service and controller dependencies.
   - Always use curly braces for control structures, even for single-line bodies.
   - Use TitleCase for Enum keys (`STATUS_ACTIVE`, `PAYMENT_CASH`).
2. **Pint Code Formatting**:
   - Before submitting changes, always run the project's formatting tool:
     ```bash
     vendor/bin/pint --format agent
     ```
3. **No Direct Customer Self-Registration**:
   - Customer records must be created through the **Lead Conversion Pipeline** or the **Deposit Creation Workflow**. Direct registration routes (`/admin/customers/create`) are intentionally disabled.
4. **Financial Calculations in Services**:
   - Calculations for deposits, interests, commissions, and promotions must reside in dedicated service classes (`CommissionService`, `FdCommissionSchemeService`, `CareerPerformanceService`), never directly in Blade templates or raw controller bodies.
5. **Database Transaction Safety**:
   - Multi-step writes, status transitions, and commission payouts must be wrapped in `DB::transaction()` with pessimistic locking (`lockForUpdate()`) where duplicate submissions could occur.

---

## 6. Testing Requirements

Every change that affects business logic, commission calculations, or state transitions must be backed by automated tests:

- **Run the full test suite**:
  ```bash
  php artisan test
  ```
- **Run targeted test suites**:
  ```bash
  php artisan test --filter=CommissionEngineTest
  php artisan test --filter=DepositLifecycleManagementTest
  php artisan test --filter=CareerAndPromotionManagementTest
  ```
- **Testing Database Invariant**: Feature tests must use `Illuminate\Foundation\Testing\DatabaseTransactions` to ensure test state is completely rolled back without mutating persistent testbed data.

---

## 7. Documentation Expectations

- When adding or modifying features, update the corresponding documentation in `docs/`:
  - `docs/BUSINESS_RULES.md` for calculation or threshold changes.
  - `docs/API.md` for endpoint or route updates.
  - `docs/DATABASE.md` for migration and schema changes.
- Ensure all relative links between documentation files remain valid.
- Never document hypothetical features as implemented.

---

## 8. Pull Request & Review Process

1. Verify that all tests pass (`php artisan test`).
2. Verify code style with Pint (`vendor/bin/pint --format agent`).
3. Ensure no `.env` files, credentials, or personal secrets are staged.
4. Open a pull request against the `main` or development branch with a clear summary of changes, affected files, and verification proof.
