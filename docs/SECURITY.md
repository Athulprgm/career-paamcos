# Security, Compliance & Audit Guide: MultiCred

This document evaluates the security architecture, authentication controls, authorization boundaries, input sanitization, cryptographic standards, and financial audit capabilities implemented in the **MultiCred** application.

---

## 1. Authentication Security

### 1.1 Password Hashing Architecture
- **Primary Algorithm**: `Argon2id` (OWASP recommended password storage standard).
- **Argon2id Configuration** (`config/hashing.php`):
  - Memory: `16,384 KB` (16 MB — safe on shared-hosting / low-resource servers).
  - Time Cost: `2 iterations`.
  - Parallelism Threads: `1 thread`.
- **Bcrypt Fallback**:
  - Rounds: `12` (`BCRYPT_ROUNDS=12`).
  - Automatically supported when `PASSWORD_ARGON2ID` is unavailable.
- **Zero-Downtime Hash Upgrades**:
  - `rehash_on_login` is enabled (`true`).
  - On every successful authentication, `SuperAdmin\AuthController` invokes `Hash::needsRehash($user->password)`. Any legacy Bcrypt hash is transparently upgraded to the active Argon2id algorithm without requiring user intervention.

### 1.2 Multi-Identifier Login Sanitization
- Input is sanitized and trimmed (`trim((string) $request->input('login'))`).
- Case-insensitive database queries using raw SQL bindings (`LOWER(email) = ?`) prevent case-sensitivity bypasses while avoiding SQL injection vulnerabilities.

### 1.3 Rate Limiting & Brute Force Defense
- Login routes (`/login`, `/superadmin/login`) are protected by Laravel's built-in rate-limiting middleware:
  ```php
  Route::post('/login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:10,1');
  ```
- Maximum `10` attempts per minute per IP address. Exceeding the threshold triggers HTTP `429 Too Many Requests`.

---

## 2. Authorization & Role Boundaries

### 2.1 Multi-Tier Middleware Isolation
Access control is enforced at the routing and controller layers via dedicated middleware:
- **`SuperAdminMiddleware`**: Enforces strict `super_admin` role membership. Protects branches, global scheme definitions, master commission rates, rank rules, and staff permissions.
- **`BranchAdminMiddleware`**: Restricts access to users having the `admin` role with an assigned physical branch.
- **`AgentMiddleware`**: Enforces the `agent` role. Restricts field advisors to their personal dashboard, leads CRM, downline, and performance tabs.
- **`AdminMiddleware`**: Shared gate for administrative, staff, and agent users. Enforces:
  1. Deactivated staff accounts are immediately logged out (`auth()->logout()`).
  2. Non-super-admins cannot mutate deposit schemes or commission rules (returns `403 Forbidden`).
  3. Agents cannot access customer lists, deposit creation, commission approvals, or report exports (returns `403 Forbidden`).
  4. Direct customer registration is permanently disabled (`/admin/customers/create` returns `404 Not Found`).

### 2.2 Branch Scoping & Cross-Branch Data Leakage Prevention
Operational controllers inherit `getRestrictedBranchId()`:
- Super Admin: Returns `null` (global unrestricted access).
- Branch Admin: Returns `$user->branch_id`.
- All Eloquent queries for customers, deposits, staff, and collections apply `$query->where('branch_id', $restrictedBranchId)`. Cross-branch inspection attempts trigger `abort(403, 'Unauthorized access: You cannot view or modify records from another branch.')`.

### 2.3 Granular Staff Permission Matrix
- Operations staff members do not inherit blank administrative privileges.
- Permissions are defined as individual slugs stored in `staff.permissions` JSON array (e.g. `view-deposits`, `create-deposits`, `verify-collections`).
- `User::hasPermission($slug)` inspects the staff member's specific permissions array.

---

## 3. Financial Integrity & Audit Logging

### 3.1 Immutable Audit Log Subsystem
Every sensitive financial mutation writes to the `audit_logs` table via `AuditLog::record()`:
- **Tracked Events**:
  - `deposit_created`, `deposit_verified`, `deposit_approved`, `deposit_activated`, `deposit_matured`, `deposit_closed`, `deposit_rejected`.
  - `deposit_commission_calculated`, `commission_generated`, `commission_approved`, `commission_marked_payable`, `commission_paid`, `commission_rejected`.
  - `agent_promoted`, `permission_allocated`, `branch_created`.
- **Captured Telemetry**:
  - Acting user ID (`user_id`).
  - Assigned branch ID (`branch_id`).
  - Client IP address (`ip_address`).
  - Polymorphic entity target (`auditable_type`, `auditable_id`).
  - Transaction monetary amount (`amount`).
  - JSON snapshots of before (`old_values`) and after (`new_values`).
  - Plain-language audit narrative (`description`).

### 3.2 Concurrency & Transactional Safety
- Core financial workflows are wrapped inside `DB::transaction()`.
- To prevent double-crediting or duplicate vouchers during concurrent requests, models use pessimistic locking:
  ```php
  $existing = Commission::where('deposit_id', $deposit->id)
      ->where('payout_year', $year)
      ->lockForUpdate()
      ->first();
  ```

### 3.3 Soft Delete Data Preservation
Core financial entities implement Eloquent `SoftDeletes`:
- `deposits`, `deposit_collections`, `commissions`, `customers`, `agents`, `branches`.
- Accidental or malicious deletion retains the database record with a `deleted_at` timestamp, preserving audit history and balance integrity.

---

## 4. Input Validation & CSRF Defense

- **CSRF Protection**: All `POST`, `PUT`, `PATCH`, and `DELETE` routes are protected by Laravel's `VerifyCsrfToken` middleware.
- **Form Request Validation**: Controllers validate all user inputs against strict types, formats, string length limits, numeric bounds, and database existence:
  - `amount`: `['required', 'numeric', 'min:0.01']`.
  - `tenure`: `['required', 'integer', 'min:1']`.
  - `payment_mode`: `['required', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE,ONLINE_UPI,DEMAND_DRAFT']`.

---

## 5. File Upload Security

- **Avatar & Document Processing**: Managed by `App\Services\ProfileImageService`.
- **Validation Rules**: Restricts uploads to permitted image formats (`jpeg`, `png`, `webp`) and enforces maximum file size boundaries.
- **Storage Location**: Uploads are stored in `storage/app/public/` and accessed through the symbolic link `public/storage/`. Direct execution of uploaded scripts is prevented by web server MIME-type headers.

---

## 6. Security Findings & Recommendations (Audit Log)

> [!NOTE]
> In accordance with the documentation-only directive, these findings are recorded for engineering awareness and are **not** modified in this task.

| Finding ID | Area | Severity | Description | Recommended Remediation |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-001** | Local Environment | Low | Local `.env` uses MySQL `root` with no password on `127.0.0.1`. | Enforce dedicated database user with strong password in staging/production. |
| **SEC-002** | Mail Preview Routes | Low | Routes `/mail-preview` and `/mail-preview/welcome` are accessible in `local` and `testing` environments without auth. | Ensure `APP_ENV=production` is strictly set in live deployments to deactivate preview routes. |
| **SEC-003** | Public Storage Symlink | Info | Uploaded avatars depend on `storage:link`. | Verify symbolic link permissions (`chmod 755`) on deployment servers. |
| **SEC-004** | Session Driver | Info | `.env.example` defaults to `SESSION_DRIVER=database` or `SESSION_DRIVER=file`. | Ensure `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true` on production HTTPS domains. |
