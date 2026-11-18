# Architecture Decision Records (ADRs): MultiCred

This directory contains records of significant architectural and design decisions made throughout the evolution of the **MultiCred** platform.

---

## Index of Architectural Decisions

| ADR Number | Title | Status | Date / Phase |
| :--- | :--- | :--- | :--- |
| **[ADR-001](#adr-001-multi-identifier-authentication-strategy)** | Unified Multi-Identifier Authentication Gateway | Accepted | Phase 3 (2026-10-08) |
| **[ADR-002](#adr-002-dual-layer-frontend-styling-tailwind-v4--bootstrap-53)** | Dual-Layer Styling Architecture (Tailwind CSS v4 + Bootstrap 5.3) | Accepted | Phase 1 (Initial Setup) |
| **[ADR-003](#adr-003-multi-year-commission-amortization-model)** | Multi-Year Commission Payout Amortization (FD Schemes) | Accepted | Phase 7 (2026-10-19) |
| **[ADR-004](#adr-004-lead-driven-customer-creation-pipeline)** | Lead-to-Deposit Conversion Pattern replacing Direct Customer Registration | Accepted | Phase 6 (2026-10-17) |
| **[ADR-005](#adr-005-service-layer-isolation-for-banking-and-commission-math)** | Service-Layer Isolation for Banking & Commission Math | Accepted | Phase 4 (2026-10-12) |
| **[ADR-006](#adr-006-immutable-financial-audit-logging-subsystem)** | Immutable Audit Logging with JSON Before/After Snapshots | Accepted | Phase 2 (2026-10-07) |
| **[ADR-007](#adr-007-standardized-12-tier-career-progression-framework)** | Standardized 12-Tier Career Progression Framework | Accepted | Phase 6 (2026-10-17) |

---

## ADR-001: Multi-Identifier Authentication Strategy

### Context
In rural and semi-urban co-operative banking environments, users identify themselves differently depending on their role:
- System Administrators remember their corporate email or shorthand username (`admin`, `superadmin`).
- Branch Administrators frequently log in using their physical branch code (`BR001`, `BR002`).
- Operational staff are assigned employee codes (`STF001`, `EMP042`).
- Field agents operate with printed member codes (`AG000042`, `MBR10023`).

Requiring all users to recall their exact registered email address caused severe login friction.

### Decision
Implement an intelligent, multi-identifier resolver in `App\Http\Controllers\SuperAdmin\AuthController::login` that inspects the single `login` input across:
1. `User.email`
2. Static username shortcuts (`admin`, `superadmin`, `branchadmin`)
3. `Branch.code`
4. `Staff.staff_code` and `Staff.employee_id`
5. `Agent.member_code` and `Agent.agent_id`

Upon authentication, the controller automatically inspects the user's role and redirects them to their designated portal.

### Consequences
- **Positive**: Seamless login experience across all 5 user roles without requiring separate login URLs.
- **Security Mitigations**: Rate-limited (`throttle:10,1`) to prevent brute-force attacks across identifiers. Passwords undergo automatic Argon2id rehashing.

---

## ADR-002: Dual-Layer Frontend Styling (Tailwind v4 + Bootstrap 5.3)

### Context
The application requires high-velocity development of complex banking grids, data tables, modal dialogs, and printable receipts, alongside a modern, customized brand identity tailored for Pristine Agro.

### Decision
Adopt a hybrid frontend architecture:
1. **Tailwind CSS v4** (`resources/css/app.css`): Defines brand color tokens (Forest Green, Emerald, Lime, Metallic Gold), typography scales, spacing tokens, and card containers.
2. **Bootstrap 5.3**: Used for robust responsive grids, table layouts, accessible modal dialogs, and collapse components.
3. **Vite 8.0**: Manages the build pipeline with `@tailwindcss/vite` and `laravel-vite-plugin`.

### Consequences
- **Positive**: Modern, bespoke brand appearance combined with battle-tested responsive UI components.
- **Maintenance**: Developers must ensure CSS class precedence does not conflict between Bootstrap and Tailwind utilities.

---

## ADR-003: Multi-Year Commission Amortization Model

### Context
Fixed Deposits in co-operative societies frequently span multi-year tenures (e.g. 5 to 7 years). Paying out an agent's entire multi-year commission upfront places unsustainable liquidity strain on the society and disincentivizes agents from servicing the account throughout its tenure.

### Decision
Amortize commission payouts annually across the life of the deposit:
- Introduce `payout_year` (1 through 7) on `deposit_scheme_rank_commissions` and `commission_ledgers`.
- Generate separate commission records for each tenure year (`payout_year = 1, 2, ...`).
- Provide an administrative approval and payment workflow allowing commissions to be authorized on an annual schedule.
- Implement `php artisan commission:sync-fd-schemes` to synchronize multi-year payout matrices.

### Consequences
- **Positive**: Sustainable financial cash flow for the society; clear incentive structure for long-term customer relationship management.
- **Complexity**: Multiple commission records per deposit account requiring clear ledger indexing and filtering.

---

## 4. ADR-004: Lead-Driven Customer Creation Pipeline

### Context
Direct manual customer creation (`/admin/customers/create`) frequently led to orphaned customer records with no assigned booking agent, missing KYC proofs, or duplicate entries.

### Decision
Deprecate direct customer registration (`/admin/customers/create` permanently returns HTTP `404`). All new customer accounts must be created through:
1. **Agent Lead CRM Conversion**: An agent logs a prospect in `/agent/leads`, and converting the lead pre-fills the deposit application.
2. **Deposit Application**: Direct entry of customer name and mobile during `/admin/deposits/create` automatically provisions the customer profile linked to the booking agent.

### Consequences
- **Positive**: Guaranteed provenance; every customer is attributed to an introducing agent and branch. Eliminates orphaned records.

---

## 5. ADR-005: Service-Layer Isolation for Banking & Commission Math

### Context
Financial calculation logic (compound interest, monthly income plan schedules, differential commission overrides, and promotion qualification scoring) was becoming scattered across controllers, models, and Blade views, causing code duplication and testing difficulties.

### Decision
Extract all financial math and domain rules into dedicated service classes in `app/Services/`:
- `CommissionService`
- `FdCommissionSchemeService`
- `PromotionEligibilityService`
- `CareerPerformanceService`
- `NumberingService`
- `ReportService`

### Consequences
- **Positive**: High testability, reusable logic, strict decimal precision (`bcadd`), and lean HTTP controllers.

---

## 6. ADR-006: Immutable Financial Audit Logging Subsystem

### Context
Co-operative credit regulations require tamper-proof records of all monetary transactions, status transitions, and administrative approvals.

### Decision
Implement a centralized `AuditLog::record()` method writing to `audit_logs`:
- Captures acting user, branch, event, IP address, monetary transaction amount, and full before/after JSON attribute diffs.
- Stored permanently with soft-deletes and no user-facing delete or update endpoints.

### Consequences
- **Positive**: Complete regulatory compliance and forensic auditability for financial reconciliation.

---

## 7. ADR-007: Standardized 12-Tier Career Progression Framework

### Context
Co-operative societies historically maintained subjective, manual promotion systems resulting in administrative confusion and disputes over agency hierarchy.

### Decision
Establish an immutable 12-rank ladder (Level 1 `Facilitator` to Level 12 `Deputy Director`) seeded via `AgentRankSeeder`, with dynamic qualification rules configured by the Super Admin. `PromotionEligibilityService` computes eligibility objectively, and agents submit requests via a self-service dashboard.

### Consequences
- **Positive**: Transparent, merit-based career progression; automated milestone calculation eliminates administrative overhead.
