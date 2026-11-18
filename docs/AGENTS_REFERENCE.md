# AI Coding Agent Reference & Operational Rules: MultiCred

> [!IMPORTANT]
> **MANDATORY FOR ALL AI CODING AGENTS**:
> Before formulating any implementation plan or modifying any file in this repository, you **MUST** read and adhere to the guidelines set forth in this document, in addition to the root guidelines in [AGENTS.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/AGENTS.md).

---

## 1. Project Context & Architectural Invariants

**MultiCred** is a financial core-banking and tiered commission management application running on **PHP 8.4** and **Laravel 13.x**. It is engineered for **Pristine Agro Co-operative Society**.

Because this application manages real-world financial balances, deposit lifecycles, and commission ledgers, you must uphold these four architectural invariants at all times:

1. **Decimal Precision & Decimal Safety**: Never use standard floating-point operations for financial math. Use explicit 2-decimal scale formatting, `bcadd`, and `round(..., 2)`.
2. **Transactional Integrity**: Every status change, commission approval, deposit disbursement, or multi-table update must be executed inside `DB::transaction()` with pessimistic row-locking (`lockForUpdate()`) to prevent race conditions.
3. **Audit Trail Logging**: Any mutation altering monetary balances, account statuses, promotion ranks, or staff permissions must log to `audit_logs` using `App\Models\AuditLog::record()`.
4. **Soft Delete Preservation**: Financial tables (`deposits`, `deposit_collections`, `commissions`, `customers`, `agents`) utilize soft deletes. Never run hard `DELETE` or `forceDelete()` queries on production tables.

---

## 2. Mandatory Pre-Modification Inspection Protocol

Before creating or editing any code file:

1. **Check Documentation First**:
   - For calculations & formulas: Consult [docs/BUSINESS_RULES.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/BUSINESS_RULES.md).
   - For database structure: Consult [docs/DATABASE.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/DATABASE.md).
   - For routing & endpoints: Consult [docs/API.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/API.md).
   - For module responsibilities: Consult [docs/MODULES.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/MODULES.md).
2. **Inspect Sibling Code**:
   - Check sibling controllers, services, and models to match existing naming conventions, parameter validation rules, and error responses.
   - Reuse existing domain services (`CommissionService`, `FdCommissionSchemeService`, `NumberingService`, `CareerPerformanceService`) rather than re-implementing logic.
3. **Confirm Dependencies & Versions**:
   - Inspect `composer.json` and `package.json` to confirm installed package versions. Never assume a package version or install unapproved third-party dependencies.

---

## 3. Code Organization & Naming Standards

- **Models**: `PascalCase` singular (`Deposit`, `Commission`, `CareerLevel`) in `app/Models/`.
- **Controllers**: Grouped by portal namespace (`Admin\`, `SuperAdmin\`, `BranchAdmin\`, `Agent\`, `Staff\`) in `app/Http/Controllers/`.
- **Domain Services**: `app/Services/` housing domain math and complex state transitions.
- **Blade Views**: Partitioned under `resources/views/{role}/`.
- **PHP 8.4 Rules**:
  - Always use curly braces for control structures, even for single-line bodies.
  - Use constructor property promotion (`public function __construct(protected Service $service) {}`).
  - Declare explicit parameter and return types on all functions and methods.
  - Format all modified PHP code with Laravel Pint:
    ```bash
    vendor/bin/pint --format agent
    ```

---

## 4. Business Logic Preservation

- **Do Not Re-Enable Direct Customer Registration**: `/admin/customers/create` intentionally returns `404`. Customers must be created through the Lead CRM Conversion or Deposit Application workflows.
- **Do Not Bypass Scheme Commission Ceilings**: Total commission percentages across all ranks for any payout year cannot exceed 100.00%.
- **Preserve Free-Floating Agent Flexibility**: `agents.branch_id` is nullable. Agents can operate across branches without strict physical branch lock-in.

---

## 5. Database Migration & Schema Safety

- Always create new migrations using `php artisan make:migration {name} --no-interaction`.
- Never modify existing committed migration files (`database/migrations/`).
- Use nullable foreign keys with appropriate cascade rules (prefer `nullOnDelete()` or restricted constraints over cascading deletes on financial tables).
- Always include `$table->softDeletes()` when creating primary financial or identity entities.

---

## 6. Authentication & Authorization Enforcement

- Adhere to the multi-identifier authentication pattern in `SuperAdmin\AuthController`.
- Do not bypass role middleware (`admin`, `superadmin`, `branchadmin`, `agent`).
- Branch Admins must always be restricted to their assigned branch (`getRestrictedBranchId()`).
- Operational staff capabilities must be evaluated against the staff profile permissions array (`$user->hasPermission($slug)`).

---

## 7. Testing & Quality Verification Expectations

- **Test Enforcement**: When modifying financial calculations, commission logic, or state machines, you **MUST** run the affected tests and confirm they pass:
  ```bash
  php artisan test --filter=CommissionEngineTest
  php artisan test --filter=DepositLifecycleManagementTest
  ```
- **Test Strategy**: Feature tests must use `Illuminate\Foundation\Testing\DatabaseTransactions` to ensure no persistent database state is altered during test runs.
- **Never Claim Unverified Results**: Do not state that tests passed unless you have executed them and inspected the output.

---

## 8. Strictly Prohibited Destructive Operations

The following actions are **strictly prohibited** for any AI coding agent:

1. **PROHIBITED**: Executing raw destructive SQL commands (`DROP TABLE`, `TRUNCATE`, `DELETE FROM` without WHERE clause).
2. **PROHIBITED**: Running `php artisan migrate:fresh` or `migrate:reset` on shared/persistent databases without explicit user consent.
3. **PROHIBITED**: Modifying database records outside of unit/feature test rollbacks without explicit user instruction.
4. **PROHIBITED**: Exposing passwords, secret keys, SMTP credentials, or sensitive customer PII in responses or documentation.
5. **PROHIBITED**: Overwriting or modifying existing documentation files (`README.md`, `AGENTS.md`, `CLAUDE.md`) when instructed to perform non-conflicting documentation creation.

---

## 9. Final Reporting Requirements

When completing any engineering task, provide a structured final report detailing:
1. Exact files created or modified with clickable Markdown links.
2. Verified business logic changes and architectural implications.
3. Test execution proof (commands run, pass/fail counts, duration).
4. Code styling verification (`vendor/bin/pint`).
5. Any identified risks, technical debt, or follow-up recommendations.
