# Project Overview: MultiCred

## 1. Executive Summary & Purpose

**MultiCred** is an enterprise-grade core banking, deposit mobilization, and multi-tier agency commission management platform engineered specifically for **Pristine Agro Co-operative Society**. The platform bridges traditional rural credit society operations with modern digital banking practices.

Traditional co-operative credit operations are burdened by labor-intensive manual bookkeeping:
- Calculating complex compounding interest across diverse deposit tenures.
- Amortizing agent commissions across multi-year fixed deposit schemes.
- Calculating differential upline overrides across dynamic downline hierarchy trees.
- Tracking agent career milestones and manual promotion criteria.
- Maintaining financial audit compliance across distributed physical branches.

MultiCred provides an automated, centralized, role-isolated web application that enforces strict financial invariants, records immutable audit trails, and empowers society administrators, branch officers, field agents, and operational staff.

---

## 2. Intended Users & Persona Profiles

The platform defines five distinct personas with tailored portals and permission boundaries:

```
+-------------------------------------------------------------------------+
|                    Pristine Agro Co-operative Society                   |
+-------------------------------------------------------------------------+
                                     │
      ┌──────────────────────────────┼──────────────────────────────┐
      ▼                              ▼                              ▼
+──────────────+              +──────────────+              +──────────────+
|  Super Admin |              | Branch Admin |              | Operations   |
|  Governance  |              | Regional Hub |              | Counter/Desk |
+──────────────+              +──────────────+              +──────────────+
      │                              │                              │
      └──────────────────────────────┼──────────────────────────────┘
                                     │
                    ┌────────────────┴────────────────┐
                    ▼                                 ▼
             +──────────────+                  +──────────────+
             | Field Agent  |                  | Customer     |
             | Agency Force |                  | Beneficiary  |
             +──────────────+                  +──────────────+
```

### 1. Super Administrator (Central Governance)
- **Portal**: `/superadmin/dashboard`
- **Scope**: Society-wide authority across all branches, accounts, and financial rules.
- **Key Duties**:
  - Provision and monitor physical branches.
  - Configure master deposit schemes (FD, RD, DD) and multi-year interest rate schedules.
  - Design multi-tier commission payout percentages and payout-year schedules.
  - Define criteria for the 12-rank career progression framework.
  - Review and allocate granular staff permissions.
  - Access society-wide business intelligence reports and audit trails.

### 2. Branch Administrator (Branch Operations Lead)
- **Portal**: `/branch-admin/dashboard` (with access to `/admin/*` scoped to their branch)
- **Scope**: Branch-level jurisdiction. Restricted from viewing or mutating data of other branches.
- **Key Duties**:
  - Supervise branch staff and assign operational duties.
  - Verify and approve agent onboarding applications.
  - Perform Level-2 verification and approval of customer deposits.
  - Review and authorize commission payout disbursements within branch budget.
  - Monitor branch lead conversion and collection performance.

### 3. Operations Staff (Counter & Cashier Desk)
- **Portal**: `/staff/dashboard` (and permitted `/admin/*` routes)
- **Scope**: Least-privilege operational workstation gated by dynamic permission toggles.
- **Key Duties**:
  - Record daily/weekly/monthly installment deposit collections.
  - Execute 2-step verification on field agent collection slips.
  - Inspect allowed customer account records and print transaction receipts.
  - Prohibited from accessing global system settings, deposit schemes, or commission rules.

### 4. Field Agent / Advisor (Agency Force)
- **Portal**: `/agent/dashboard`
- **Scope**: Personal sales workstation and downline agency hierarchy.
- **Key Duties**:
  - Mobilize deposits and enroll new members through the **Lead CRM Pipeline**.
  - Track personal downline networks (direct sponsors and multi-level subordinates).
  - Inspect real-time performance scores, active customer counts, and collection totals.
  - Monitor pending, approved, and paid commission vouchers.
  - Submit self-service promotion requests upon meeting milestone qualification criteria.
  - Operating Model: Free-floating field agents can sponsor members across branches without strict physical branch lock-in.

### 5. Customer / Member (Society Beneficiary)
- **Model**: Managed customer records (`customers`, `customer_kyc`).
- **Scope**: Customers are managed centrally by the society. Direct customer self-registration is disabled (`/admin/customers/create` returns `404`) to prevent unverified, orphaned customer accounts.
- **Key Duties**: Maintain verified KYC records, nominee nominations, deposit passbooks, and certificates.

---

## 3. Major Implemented Features

### 3.1 Unified Multi-Identifier Authentication
- Single sign-on gateway (`/` / `/superadmin/login` / `/login`) with automatic role detection and dashboard dispatch.
- Authentication supports 5 distinct identifier formats:
  1. Primary Email Address (`superadmin@multicred.com`, staff/agent email).
  2. Username Shortcuts (`admin`, `superadmin`, `branchadmin`).
  3. Branch Code (e.g. `BR001` resolves to assigned branch admin).
  4. Staff Code / Employee ID (e.g. `STF001`, `EMP042`).
  5. Agent Member Code / Agent ID (e.g. `AG000042`, `MBR10023`).
- Automatic algorithm migration: Verifies both Argon2id and legacy Bcrypt hashes, transparently upgrading stored passwords to Argon2id upon successful authentication (`Hash::needsRehash()`).
- Brute-force protection: Rate-limited via `throttle:10,1`.

### 3.2 Banking Deposit Lifecycle Engine
- Supports 3 core deposit models:
  - **Fixed Deposit (FD)**: Lump-sum investment over flexible terms (Months/Years) with annual compounding interest:
    $$Maturity = Amount \times (1 + \frac{Rate}{100})^{TenureYears}$$
  - **Recurring Deposit (RD)**: Scheduled installment savings (Monthly/Quarterly) with compound interest:
    $$Interest = Amount \times \frac{n(n+1)}{24} \times \frac{Rate}{100}$$
  - **Daily Deposit (DD / Pigmy)**: Doorstep micro-savings collected daily:
    $$Interest = (Amount \times Days) \times \frac{Rate}{100} \times \frac{Days}{365}$$
  - **Monthly Return Plan (MRP / MIS)**: Non-cumulative monthly income scheme with monthly interest payouts and 100% principal return at maturity.
- Configurable senior citizen bonus (+0.50% interest rate), lock-in periods, premature withdrawal penalty rules, and loan-against-deposit thresholds.
- Strict deposit state machine: `DRAFT` &rarr; `PENDING_APPROVAL` &rarr; `APPROVED` &rarr; `ACTIVE` &rarr; `MATURED` &rarr; `CLOSED`.

### 3.3 Multi-Tier & Multi-Year Commission Payout Engine
- **Multi-Year Payout Amortization**: For long-tenure schemes (e.g. 7-year FD schemes), commissions are distributed annually across Year 1 through Year 7.
- **Direct & Differential Upline Overrides**: Calculates booking agent commission and differential overrides for upline supervisors:
  $$DifferentialRate = UplineRate - DownlineRate$$
- Decimal-safe precision enforced via PHP `bcadd` to prevent floating-point rounding errors.
- Strict 4-step financial voucher lifecycle:
  `PENDING` &rarr; `APPROVED` &rarr; `PAYABLE` &rarr; `PAID` (or `REJECTED`).
- CLI Synchronization Command: `php artisan commission:sync-fd-schemes` to synchronize multi-year matrices across schemes.

### 3.4 Standardized 12-Tier Career Progression Framework
- 12 standardized ranks (Level 1 `Facilitator` to Level 12 `Deputy Director`).
- Dynamic qualification criteria configured per rank:
  - Minimum active customers.
  - Minimum personal deposit volume (₹).
  - Minimum installment collection turnover (₹).
  - Minimum service tenure days.
  - Minimum qualifying downline agents and required subordinate tiers.
- Automated eligibility engine (`PromotionEligibilityService`) scoring agents in real time.
- Self-service promotion submission with administrative approval workflow and permanent `career_histories` audit logging.

### 3.5 Agent Lead CRM Pipeline
- Mobile-responsive lead capture for field agents.
- Status workflow: `NEW` &rarr; `CONTACTED` &rarr; `INTERESTED` &rarr; `CONVERTED` (or `NOT INTERESTED`).
- **1-Click Lead Conversion**: Converting an interested lead pre-fills the deposit application form, creating the customer record and linking deposit, customer, lead, and agent in a single atomic transaction.

### 3.6 Financial Audit Logging & Security
- Immutable audit log (`audit_logs`) recording event type, actor ID, IP address, branch ID, timestamp, transaction amount, and full before/after JSON diffs.
- Granular permission matrix allowing Super Admin to toggle specific capabilities on a per-staff or per-role basis.
- Safe soft deletes (`deleted_at`) across all primary financial tables.

### 3.7 Business Intelligence & Exportable Reports
- Dedicated reporting controller (`ReportController`) and service (`ReportService`) providing tabular data and one-click **CSV / Excel data exports** for:
  - Customers Report
  - Deposits Portfolio Report
  - Commissions & Payouts Report
  - Agent Performance Report
  - Career Progression Report
  - System Audit Trail Report

---

## 4. Verified Technology Stack

| Layer | Technology | Verified Version | Purpose |
| :--- | :--- | :--- | :--- |
| **Backend Runtime** | PHP | `8.4.25` (ZTS VC++ 2022 x64) | Core application execution |
| **Framework** | Laravel | `13.17.x` / `12.x` | Application structure, routing, ORM |
| **Database Engine** | MySQL / MariaDB | MySQL `8.0+` / MariaDB `10.4+` | Relational data persistence |
| **Password Hashing** | Argon2id & Bcrypt | Argon2id (OWASP profile, 16MB/2t) | Secure credential storage |
| **Frontend Framework** | Blade + Dual CSS | Tailwind CSS `v4.0.0` + Bootstrap `5.3` | UI components and styling |
| **Asset Bundler** | Vite | `8.0.0` with `@tailwindcss/vite` | Modern frontend bundling |
| **Testing Framework** | PHPUnit | `12.5.12` | Automated feature and unit tests |
| **Code Formatter** | Laravel Pint | `1.27.x` (PSR-12 standard) | Automated style enforcement |
| **Package Manager** | Composer | `2.10.2` | PHP dependency management |
| **Node Runtime** | Node.js / npm | Node `v22.23.2` / npm `10.9.8` | Frontend toolchain |

---

## 5. External Dependencies & Integrations

- **Laravel Framework (`laravel/framework: ^13.17`)**: Core MVC framework, Eloquent ORM, validation, and session management.
- **Laravel Tinker (`laravel/tinker: ^3.0`)**: Interactive REPL for debugging.
- **Laravel Pint (`laravel/pint: ^1.27`)**: Zero-config PHP code style fixer for PHP 8.4.
- **Bootstrap 5.3 & Bootstrap Icons**: Responsive layout grid, modals, form controls, and icons.
- **Tailwind CSS v4 (`tailwindcss: ^4.0.0`)**: Modern utility-first CSS styling and custom color tokens.
- **Vite (`vite: ^8.0.0`)**: Frontend bundling and hot-module replacement.
- **PHP Mailer / Laravel Mail**: SMTP integration for sending onboarding credentials (`AccountWelcomeMail`).

---

## 6. Known Limitations & Technical Debt

1. **Git Working-Tree State**:
   - The project directory is currently not initialized as a Git repository (`fatal: not a git repository`). All historical releases are derived from dated migrations and seeder records.
2. **Direct Customer Registration Removed**:
   - The `/admin/customers/create` endpoint deliberately returns HTTP `404`. New customer accounts must originate from the Lead Conversion Pipeline or Deposit Creation workflows to preserve agent attribution.
3. **Database Show Performance Schema**:
   - Running `php artisan db:show` triggers a query against `performance_schema.session_status` which is disabled in default XAMPP MariaDB installations. Use `php artisan model:show` or schema inspection tools instead.
4. **Public Storage Symlink**:
   - Uploaded agent avatars and KYC attachments depend on the `public/storage` symlink (`php artisan storage:link`). In shared hosting environments without symlink support, custom public path configuration is required.
