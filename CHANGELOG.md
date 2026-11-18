# Changelog

All notable changes and architectural milestones for the **MultiCred Deposit & Commission Management System** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## Technical Baseline Disclosure

> [!NOTE]
> Git version history is currently unavailable in this local environment (`fatal: not a git repository`). The release milestones below are established directly from verified database migrations, domain service implementations, architectural milestones, and existing release records.

---

## [Phase 8] - 2026-10-28

### Added
- **Dynamic Multi-Year Deposit Interest Rate System**:
  - Implemented `DepositSchemeInterestRate` entity and associated controller (`DepositInterestRateController`).
  - Added multi-year interest rate syncing via `php artisan interest-rates:sync` and administrative endpoints.
  - Multi-year deposit rate filtering by scheme and effective date ranges.
- **Reporting & Business Intelligence Engine**:
  - Added `ReportService` and `ReportController` supporting CSV/Excel data export for Customers, Deposits, Commissions, Agent Performance, Career Progressions, and System Audit Logs.
  - Added branch-isolated scoping for reporting queries.

### Changed
- **Permission Matrix Streamlining**:
  - Detached legacy collection permissions (`remove_collections_permissions`, `detach_default_staff_role_permissions`) in favor of centralized operational controls.
  - Enforced strict least-privilege checks in `AdminMiddleware` for staff profile permission arrays.

---

## [Phase 7] - 2026-10-19 to 2026-10-22

### Added
- **Multi-Year FD Commission Distribution Engine**:
  - Configured per-year commission amortizations (Year 1 through Year 7) on long-tenure Fixed Deposit schemes.
  - Added `payout_year` column to `deposit_scheme_rank_commissions` and `commission_ledgers`.
  - Added custom Artisan CLI command `php artisan commission:sync-fd-schemes` to synchronize multi-year commission payout matrices.
  - Seeded multi-year commissions for standard Scheme 1311.
- **Agent Free-Floating Field Placement**:
  - Migrated `agents.branch_id` to nullable, allowing independent agents to operate across multiple branch jurisdictions.

---

## [Phase 6] - 2026-10-17 to 2026-10-18

### Added
- **12-Tier Standardized Career Progression Framework**:
  - Seeded 12 standardized ranks (Level 1 `Facilitator` to Level 12 `Deputy Director`) via `AgentRankSeeder`.
  - Created dynamic promotion rules supporting configurable qualification criteria: active customers, personal deposit volume, collection turnover, tenure duration, and qualifying downline agent tiers.
  - Added `PromotionEligibilityService` and `CareerPerformanceService` to calculate real-time promotion readiness.
  - Added self-service promotion request submission from the agent workstation.
- **Lead CRM Pipeline & 1-Click Conversion**:
  - Added `leads` table and multi-status workflow (`NEW`, `CONTACTED`, `INTERESTED`, `CONVERTED`, `NOT INTERESTED`).
  - Integrated 1-click lead conversion into deposit account creation, automatically linking deposit, agent, customer, and branch.

---

## [Phase 5] - 2026-10-14 to 2026-10-15

### Added
- **Deposit Scheme Commission Architecture**:
  - Added `fd_commission_schemes`, `deposit_scheme_rank_commissions`, and `deposit_scheme_commission_histories`.
  - Created `FdCommissionSchemeService` for decimal-safe commission percentage validation (`bcadd`) and transactional scheme generation.
  - Added profile avatar image cropping pipeline and public storage integration.

---

## [Phase 4] - 2026-10-12

### Added
- **Core Banking Deposit Lifecycle Engine**:
  - Added `scheme_types` and real-world banking calculation fields on `deposit_schemes` and `deposits`.
  - Implemented calculation models for Lump-Sum (FD), Recurring (RD), and Daily (DD / Pigmy) deposits.
  - Implemented deposit status state machine: `DRAFT` &rarr; `PENDING_APPROVAL` &rarr; `APPROVED` &rarr; `ACTIVE` &rarr; `MATURED` &rarr; `CLOSED`.
  - Added 2-step verification for deposits and counter collections.

---

## [Phase 3] - 2026-10-08

### Added
- **Unified Multi-Identifier Authentication**:
  - Implemented multi-identifier authentication supporting direct email, username shortcuts (`admin`, `superadmin`, `branchadmin`), branch codes (`BR001`), staff codes (`STF001`), and agent member codes (`AG000042`).
  - Implemented dual-algorithm password authentication: Argon2id with automatic runtime upgrade from legacy Bcrypt hashes on successful login.
  - Automated dashboard dispatch based on verified role.

---

## [Phase 2] - 2026-10-07

### Added
- **Organization Hierarchy & Base Entities**:
  - Provisioned core tables: `branches`, `positions`, `staff`, `agents`, `customers`, `customer_kyc`.
  - Added `audit_logs` table with JSON before/after snapshots, actor tracking, and financial transaction amounts.
  - Added `settings` table for system-wide configuration keys and sequential numbering parameters.

---

## [Phase 1] - Initial System Framework

### Added
- Laravel skeleton initialization on PHP 8.4 and Laravel framework.
- Core authentication tables (`users`, `roles`, `permissions`, `cache`, `jobs`).
- Initial database seeder provisioning root Super Administrator (`superadmin@multicred.com`).
