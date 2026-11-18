# Project Structure: MultiCred Repository Map

This document details the file and folder layout of the **MultiCred** codebase, explaining the role of each directory, key components, and where developers should look when implementing or maintaining specific features.

---

## 1. Top-Level Repository Directory Map

```
multicred-deposit-commission/
├── app/                               # Core application backend source code
│   ├── Console/Commands/              # Custom Artisan CLI maintenance commands
│   ├── Http/
│   │   ├── Controllers/               # Grouped portal controllers (Admin, Agent, SuperAdmin, etc.)
│   │   └── Middleware/                # Authentication, authorization, and role guard middleware
│   ├── Mail/                          # Transactional Mailable classes and notification mailers
│   ├── Models/                        # 27 Eloquent models defining database entities & relations
│   └── Services/                      # 10 Domain services housing complex calculations & business logic
├── config/                            # Laravel configuration files (app, hashing, database, etc.)
├── database/
│   ├── factories/                     # Model factories for unit and feature testing
│   ├── migrations/                    # 42 database migrations defining table schemas and constraints
│   └── seeders/                       # Database seeders (roles, root superadmin, 12 career ranks)
├── docs/                              # Comprehensive technical documentation repository
│   ├── decisions/                     # Architecture Decision Records (ADRs)
│   └── development/                   # Coding standards and agent development workflows
├── public/                            # Web root: entry index.php, assets, uploaded storage symlink
├── resources/
│   ├── css/                           # Tailwind CSS v4 styling definitions and custom brand tokens
│   ├── js/                            # JavaScript client scripts and Vite entry points
│   └── views/                         # Blade templates partitioned by portal role
├── routes/
│   ├── console.php                    # Artisan console closures
│   └── web.php                        # Web route definitions (215 routes across 5 portals)
├── storage/                           # Compiled Blade views, cache, session storage, and uploads
├── tests/
│   ├── Feature/                       # 39 Feature test suites covering end-to-end workflows
│   ├── Unit/                          # 3 Unit test suites validating pure calculations and formatters
│   └── TestCase.php                   # Base test case configuring test environment
├── composer.json                      # PHP dependencies and project automation scripts
├── package.json                       # Frontend dependencies and Vite build scripts
├── phpunit.xml                        # PHPUnit test suite environment configuration
├── vite.config.js                     # Vite bundler build settings
├── README.md                          # Root system overview and quick-start guide (preserved)
├── AGENTS.md                          # Root Laravel Boost guidelines for coding agents (preserved)
├── CONTRIBUTING.md                    # Contributor prerequisites and coding standards
└── CHANGELOG.md                       # Historical release milestones and architectural phases
```

---

## 2. Deep Dive: `app/` Directory

### 2.1 `app/Console/Commands/` (Artisan Utilities)
- `ClearDatabaseCommand.php` (`php artisan db:clear`): Safely purges test transactions, dummy deposits, collections, and leads while strictly preserving master branches, staff, superadmin credentials, and the 12 career progression ranks.
- `SendTestMailCommand.php` (`php artisan mail:test {email}`): Diagnoses SMTP mail deliverability.
- `SyncFdCommissionSchemesCommand.php` (`php artisan commission:sync-fd-schemes`): Synchronizes multi-year deposit commission payout schedules across schemes and recalculates existing active deposit commissions when passed `--fix-deposits`.

### 2.2 `app/Http/Controllers/` (Controller Layer)
Controllers handle request validation, call domain services, and return responses or Blade views:
- **`Admin/`**: Operational controllers shared by Super Admins and Branch Admins:
  - `AgentController.php`: Agent onboarding, verification, document checks, and status toggles.
  - `CareerController.php`: Promotion request approvals, agent career history, and eligibility tracking.
  - `CommissionController.php`: Commission overview, approvals, payable marking, and payment processing.
  - `CommissionRuleController.php`: Management of fallback and scheme-specific commission rules.
  - `CustomerController.php`: Customer profile views and directory (direct creation disabled with 404).
  - `DepositController.php`: Deposit applications, 2-step verification, approvals, activations, and closures.
  - `DepositSchemeController.php`: Deposit scheme management (FD, RD, DD) and tenure-rate previews.
  - `LeadController.php`: Administrative view of agent leads across branches.
  - `ReportController.php`: Tabular reporting screens and CSV data export streams.
  - `StaffController.php`: Branch staff management and account deactivation.
- **`SuperAdmin/`**: Central society governance controllers:
  - `AgentRankController.php`: Configuration of the 12 career levels and descriptions.
  - `AuthController.php`: Multi-identifier login, password validation (Argon2id/Bcrypt), and dashboard routing.
  - `BranchController.php`: Branch provisioning, codes, address, and status toggles.
  - `BranchAdminController.php`: Creation and branch assignment of Branch Administrators.
  - `DashboardController.php`: System-wide statistics and financial summaries.
  - `DepositCommissionSchemeController.php` & `FdCommissionSchemeController.php`: Setup of scheme-level rank commission matrices.
  - `DepositInterestRateController.php`: Management of multi-year tenure interest rates.
  - `PermissionController.php`: Allocation of granular permissions to roles and staff profiles.
  - `ProfileController.php`: Administrative profile update and password changing.
  - `PromotionRuleController.php`: Dynamic promotion criteria configuration per rank.
  - `StaffPositionController.php`: Staff position titles and hierarchy ordering.
- **`BranchAdmin/`**:
  - `DashboardController.php`: Branch-scoped operational overview, branch performance, and pending items.
  - `NotificationController.php`: Alerts for pending deposits, commissions, and agent requests.
- **`Agent/`**:
  - `DashboardController.php`: Field agent personal metrics, milestones, and downline quick stats.
  - `DownlineAgentController.php`: Multi-tier downline agent tree explorer and downline onboarding.
  - `LeadController.php`: Lead CRM pipeline, follow-up scheduler, and status tracker.
  - `PerformanceController.php`: Real-time performance score, milestone tracker, and promotion request form.
  - `NotificationController.php`: Agent-specific notifications and approval updates.
- **`Staff/`**:
  - `DashboardController.php`: Counter operations overview for collections and customer support.

### 2.3 `app/Http/Middleware/` (Role & Security Guards)
- `AdminMiddleware.php`: Enforces access control for `/admin/*` and `/staff/*`. Restricts agents from admin operations, ensures branch isolation, deactivates inactive staff, and blocks `/admin/customers/create`.
- `SuperAdminMiddleware.php`: Restricts access exclusively to users with the `super_admin` role.
- `BranchAdminMiddleware.php`: Restricts access to users with the `admin` role and an assigned branch.
- `AgentMiddleware.php`: Restricts access to authenticated users having the `agent` role.

### 2.4 `app/Models/` (Eloquent Entity Layer)
Contains 27 Eloquent models. Key entities include:
- `Deposit.php`: Core deposit record with calculation helpers (`calculateMaturityAmount`), tenure normalization, and status hooks.
- `DepositScheme.php`: Master scheme parameters (FD, RD, DD), calculation type, and senior citizen bonus rates.
- `DepositSchemeInterestRate.php`: Multi-year interest rates linked to deposit schemes.
- `DepositSchemeRankCommission.php`: Rank-specific commission percentages across payout years (Year 1 to 7).
- `Commission.php`: Financial commission ledger record tracking calculation basis, rates, amounts, and statuses.
- `Agent.php`: Agent profile with member code, rank, branch, upline parent agent, and status.
- `CareerLevel.php` / `AgentRank.php`: Standardized 12 career levels with hierarchy sort order.
- `PromotionRule.php` & `PromotionRequest.php`: Dynamic promotion qualification rules and submitted requests.
- `Customer.php` & `CustomerKyc.php`: Customer profiles and KYC verification records.
- `Lead.php`: CRM prospective member leads with status tracking and conversion timestamps.
- `DepositCollection.php` & `CollectionPayment.php`: Installment collection receipts and payment tracking.
- `AuditLog.php`: Immutable event log capturing user, branch, event, IP, amount, and JSON diffs.
- `Branch.php`, `Staff.php`, `StaffPosition.php`: Organizational structure and branch personnel.
- `User.php`, `Role.php`, `Permission.php`: Authentication credentials and role-permission mappings.

### 2.5 `app/Services/` (Domain Business Logic Layer)
- `CommissionService.php`: Core commission calculation, multi-year payout scheduling, and status transitions.
- `FdCommissionSchemeService.php`: Scheme commission setup, `bcadd` validation, and multi-year sync.
- `PromotionEligibilityService.php`: Real-time scoring against promotion rule criteria.
- `CareerPerformanceService.php`: Agent productivity scoring (100-point composite score).
- `NumberingService.php`: Transaction-locked sequential number generator for all entities.
- `ReportService.php`: Aggregation engine for tabular data and streaming CSV exports.
- `CurrencyFormatter.php`: Indian numbering format (Lakhs, Crores, and words).
- `PasswordGeneratorService.php`: Cryptographically secure random password generation for new accounts.
- `ProfileImageService.php`: Image cropping, resizing, and public storage management.

---

## 3. `resources/` (Frontend & Views)

```
resources/
├── css/
│   └── app.css                        # Tailwind CSS v4 design tokens, color palette, and utilities
├── js/
│   └── app.js                         # JavaScript entry point with Vite bundling
└── views/
    ├── admin/                         # Shared operational screens (Agents, Deposits, Commissions, Reports)
    │   ├── agents/                    # Agent management and onboarding forms
    │   ├── career/                    # Promotion requests, performance overview, career histories
    │   ├── commissions/               # Commission ledger index, approvals, and payout forms
    │   ├── customers/                 # Customer directory and show views
    │   ├── deposit-schemes/           # Master scheme creation, interest rates, and previews
    │   ├── deposits/                  # Deposit creation, pending approval queue, and show views
    │   ├── leads/                     # Administrative lead monitoring
    │   ├── reports/                   # Reports index and export views
    │   └── staff/                     # Staff management screens
    ├── agent/                         # Dedicated Field Agent portal
    │   ├── dashboard.blade.php        # Agent workstation dashboard
    │   ├── downline/                  # Downline agent tree and enrollment
    │   ├── leads/                     # Lead CRM pipeline and status actions
    │   ├── performance/               # Performance score and promotion request
    │   └── notifications/             # Agent alerts
    ├── branch-admin/                  # Branch Admin portal views
    ├── staff/                         # Counter staff portal views
    ├── superadmin/                    # Super Admin master governance views
    │   ├── auth/login.blade.php       # Unified multi-identifier login interface
    │   ├── branches/                  # Physical branch management
    │   ├── permissions/               # Dynamic permission allocation matrix
    │   ├── promotion-rules/           # Dynamic promotion rules editor
    │   ├── agent-ranks/               # 12-rank level configurations
    │   └── layouts/                   # Superadmin dashboard layout wrapper
    └── emails/                        # Responsive HTML email templates (AccountWelcomeMail)
```

---

## 4. Feature Implementation Navigation Guide

When tasked with implementing or updating a specific feature, refer to this directory map:

| Feature Area | Models & Migrations | Controllers | Domain Services | Views |
| :--- | :--- | :--- | :--- | :--- |
| **Authentication & Logins** | `User`, `Role`, `Branch`, `Staff`, `Agent` | `SuperAdmin\AuthController` | `PasswordGeneratorService` | `superadmin/auth/login.blade.php` |
| **Deposit Calculations & Maturity** | `Deposit`, `DepositScheme`, `SchemeType` | `Admin\DepositController`, `Admin\DepositSchemeController` | `NumberingService`, `CurrencyFormatter` | `admin/deposits/`, `admin/deposit-schemes/` |
| **Multi-Year Interest Rates** | `DepositSchemeInterestRate` | `SuperAdmin\DepositInterestRateController` | — | `superadmin/interest-rates/` |
| **Commission Calculations & Payouts** | `Commission`, `DepositSchemeRankCommission`, `CommissionRule` | `Admin\CommissionController`, `SuperAdmin\DepositCommissionSchemeController` | `CommissionService`, `FdCommissionSchemeService` | `admin/commissions/` |
| **Agent Hierarchy & Downline** | `Agent`, `CareerLevel`, `Branch` | `Agent\DownlineAgentController`, `Admin\AgentController` | `NumberingService`, `ProfileImageService` | `agent/downline/`, `admin/agents/` |
| **Lead CRM Pipeline** | `Lead`, `Customer`, `Deposit` | `Agent\LeadController`, `Admin\LeadController` | — | `agent/leads/`, `admin/leads/` |
| **Career Progression & Promotions** | `PromotionRule`, `PromotionRequest`, `CareerHistory` | `SuperAdmin\PromotionRuleController`, `Admin\CareerController`, `Agent\PerformanceController` | `PromotionEligibilityService`, `CareerPerformanceService` | `superadmin/promotion-rules/`, `admin/career/`, `agent/performance/` |
| **Collections & Verifications** | `DepositCollection`, `CollectionPayment` | `Admin\DepositController` | `CommissionService`, `NumberingService` | `admin/deposits/` |
| **Reports & CSV Exports** | All reporting models | `Admin\ReportController` | `ReportService` | `admin/reports/` |
| **Permissions & Staff Allocation** | `Permission`, `Role`, `Staff` | `SuperAdmin\PermissionController` | — | `superadmin/permissions/` |
