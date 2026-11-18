# Coding Standards & Design Guidelines: MultiCred

This document specifies the technical standards, language conventions, architectural rules, and design system tokens mandated across the **MultiCred** codebase.

---

## 1. PHP 8.4 & Backend Standards

### 1.1 Strict Typing & Return Declarations
- Every function, method, and closure must define explicit parameter type hints and explicit return types:
  ```php
  public function calculateTotalCommission(array $ranksData): float
  public function isEligibleForCommission(DepositCollection $collection): array
  public function approve(Commission $commission, User $user): Commission
  ```
- Use nullable indicators (`?string`, `?int`) when null is a valid value.

### 1.2 Constructor Property Promotion
- Utilize PHP 8 constructor property promotion for service and controller dependencies. Avoid declaring redundant class property variables and empty constructor bodies:
  ```php
  // Recommended:
  public function __construct(
      protected CareerPerformanceService $careerPerformanceService,
      protected ?PromotionEligibilityService $eligibilityService = null
  ) {}
  ```

### 1.3 Control Structures & Syntax
- **Always use curly braces** for all control structure bodies, even single-line statements:
  ```php
  // Correct:
  if ($amount <= 0) {
      return 0.00;
  }

  // Prohibited:
  if ($amount <= 0) return 0.00;
  ```
- Use TitleCase for Enum keys and class constants:
  ```php
  public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
  public const PAYMENT_MODE_BANK_TRANSFER = 'BANK_TRANSFER';
  ```

### 1.4 Code Formatting with Laravel Pint
- All PHP code must adhere to the **PSR-12** standard enforced by **Laravel Pint**.
- Run Pint before submitting any code:
  ```bash
  vendor/bin/pint --format agent
  ```

---

## 2. Laravel Architectural Conventions

### 2.1 The "Do Things the Laravel Way" Principle
- Use `php artisan make:` generators when scaffolding new files (`make:model`, `make:controller`, `make:migration`, `make:test`). Always pass `--no-interaction`.
- Use Eloquent route model binding in controller signatures:
  ```php
  public function show(Deposit $deposit)
  ```
- Always generate URLs using named routes and the `route()` helper:
  ```php
  return redirect()->route('admin.deposits.show', $deposit);
  ```

### 2.2 Financial Calculations in Services
- **Strict Rule**: Controllers must never calculate interest rates, maturity payouts, commission overrides, or promotion scores.
- Delegate all domain math to the appropriate service in `app/Services/`:
  - Deposits & Maturity: `App\Models\Deposit::calculateMaturityAmount`
  - Commissions & Overrides: `App\Services\CommissionService`
  - FD Schemes: `App\Services\FdCommissionSchemeService`
  - Career Milestones: `App\Services\PromotionEligibilityService`
  - Productivity Scoring: `App\Services\CareerPerformanceService`
  - Sequential Numbering: `App\Services\NumberingService`

### 2.3 Decimal Safety & Concurrency
- Never use PHP floating-point operators (`+`, `-`, `*`, `/`) for cumulative financial sums.
- Use `bcadd` with explicit 2-decimal scale:
  ```php
  $total = bcadd($total, $pct, 2);
  ```
- Always wrap multi-row mutations inside `DB::transaction()` and apply `lockForUpdate()` when checking for duplicates.

---

## 3. Database & Migration Standards

- Table names must be plural snake_case: `deposit_collections`, `commission_ledgers`.
- Foreign keys must be descriptive and end in `_id`: `customer_id`, `branch_id`, `rank_id`.
- Monetary amounts must use `decimal(15,2)`:
  ```php
  $table->decimal('amount', 15, 2);
  $table->decimal('maturity_amount', 15, 2)->nullable();
  ```
- All primary financial tables must include `$table->softDeletes()`.

---

## 4. Frontend & Design System (Tailwind CSS v4 & Bootstrap 5.3)

### 4.1 Aesthetic Philosophy
The UI follows an agricultural co-operative brand identity combining modern glassmorphism, clean typography, and tactile contrast.

### 4.2 Brand Color Palette Tokens
Defined in `resources/css/app.css`:
- **Deep Forest Green** (`#14532D`): Primary buttons, call-to-action cards.
- **Deep Emerald** (`#064E3B`): Brand header, primary portal navigation bar.
- **Accent Lime** (`#65A30D` / `#D9F99D`): Active states, badge highlights, progress bars.
- **Metallic Gold** (`#D4A64A`): Status accents, top producer badges, senior citizen tags.
- **Canvas Background** (`#F8FAF7`): Neutral workspace body background.

### 4.3 Typography
- Body & Interface Font: **Inter** / **Instrument Sans** via Google Fonts.
- Icons: **Bootstrap Icons** (`bi bi-wallet2`, `bi bi-person-check`, `bi bi-graph-up`).

### 4.4 Form & Input Design Standards
- All form inputs must feature clear labels, placeholder text, focus rings, and explicit validation error alerts.
- Currency inputs must display the Indian Rupee symbol prefix (`₹`).
- Tables must feature alternating row highlights, sticky headers, and responsive horizontal scrolling wrappers (`overflow-x-auto`).
