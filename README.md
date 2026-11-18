# Pristine Agro — MultiCred Deposit & Commission Management System

[![Laravel Framework](https://img.shields.io/badge/Laravel-13.x%20%7C%2012.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-8.0-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![MySQL / SQLite](https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![PHPUnit Tests](https://img.shields.io/badge/Tests-30%2B%20Passing-success?style=for-the-badge&logo=phpunit&logoColor=white)](https://phpunit.de)

> **MultiCred** is an enterprise-grade micro-credit, deposit mobilization, and tiered agency commission management platform engineered for **Pristine Agro Co-operative Society**. It powers end-to-end multi-tier agent hierarchies, dynamic deposit scheme lifecycles (FD, RD, DD), multi-year commission payout distribution, agent lead pipelines, automated career promotions, collections scheduling, and comprehensive financial audit logging.

---

## Table of Contents

- [1. System Overview](#1-system-overview)
- [2. Multi-Role Portals & Access Control](#2-multi-role-portals--access-control)
- [3. Key Architectural Features](#3-key-architectural-features)
  - [3.1 Unified Multi-Identifier Authentication](#31-unified-multi-identifier-authentication)
  - [3.2 Deposit Schemes & Banking Lifecycle Engine](#32-deposit-schemes--banking-lifecycle-engine)
  - [3.3 Multi-Tier & Multi-Year Commission Engine](#33-multi-tier--multi-year-commission-engine)
  - [3.4 Agent Downline Hierarchy & Placement](#34-agent-downline-hierarchy--placement)
  - [3.5 Agent Lead CRM Pipeline](#35-agent-lead-crm-pipeline)
  - [3.6 Automated Career Progression & Promotion Engine](#36-automated-career-progression--promotion-engine)
  - [3.7 Collections Scheduling & Verifications](#37-collections-scheduling--verifications)
  - [3.8 Business Intelligence & Exportable Reports](#38-business-intelligence--exportable-reports)
  - [3.9 Enterprise Security, Audit Logs & Permission Matrix](#39-enterprise-security-audit-logs--permission-matrix)
- [4. Technology Stack & Design System](#4-technology-stack--design-system)
- [5. Repository Directory Structure](#5-repository-directory-structure)
- [6. Installation & Local Development Setup](#6-installation--local-development-setup)
- [7. Default Demo Accounts & Seeded Credentials](#7-default-demo-accounts--seeded-credentials)
- [8. Custom Artisan CLI Commands](#8-custom-artisan-cli-commands)
- [9. Automated Test Suite & Quality Assurance](#9-automated-test-suite--quality-assurance)
- [10. License & Support](#10-license--support)

---

## 1. System Overview

MultiCred bridges traditional agricultural credit societies with modern digital banking practices. The platform eliminates administrative bottlenecks by automating complex calculations such as **multi-year commission amortizations**, **rank differential overrides**, **tenure-based deposit interest schedules**, and **performance-based agent promotions**.

```
                           +-------------------------------------+
                           |      Pristine Agro MultiCred        |
                           |   Core Banking & Commission Engine  |
                           +------------------+------------------+
                                              |
     +-------------------+--------------------+--------------------+-------------------+
     |                   |                    |                    |                   |
     v                   v                    v                    v                   v
+------------+   +---------------+   +------------------+   +--------------+   +---------------+
| SuperAdmin |   | Branch Admins |   | Operations Staff |   | Agency Force |   | Customers     |
| Governance |   | Regional Hubs |   | Collections/Desk |   | Downline CRM |   | KYC & Portf.  |
+------------+   +---------------+   +------------------+   +--------------+   +---------------+
```

---

## 2. Multi-Role Portals & Access Control

MultiCred enforces strict role segregation across 5 primary portals with role-tailored navigation and distinct permission scopes:

| Role | Portal Route | Primary Capabilities |
| :--- | :--- | :--- |
| **Super Administrator** | `/superadmin/dashboard` | Global governance, branch provisioning, master FD/RD/DD scheme creation, commission matrices, 12-rank progression rules, permission matrix, system-wide financial reports, and global audit trails. |
| **Branch Administrator** | `/branch-admin/dashboard` | Branch-isolated operational administration, branch staff management, agent on-boarding & document verification, local deposit approvals, and branch commission payout oversight. |
| **Operations Staff** | `/staff/dashboard` | Permission-gated operational duties: daily/weekly/monthly deposit collection recording, payment verification, counter customer assistance, and transaction voucher printing. |
| **Agent / Advisor** | `/agent/dashboard` | Field agent workstation: personal & team deposit metrics, multi-tier downline network explorer, lead CRM pipeline, commission earnings ledger, milestone progress tracker, and self-service promotion requests. |
| **Customer** | Direct Ledger | Managed customer account with KYC verification status, nominee records, deposit certificates, and payment receipts. |

---

## 3. Key Architectural Features

### 3.1 Unified Multi-Identifier Authentication
MultiCred features an intelligent, unified authentication gateway (`/superadmin/login` / `/`) equipped with automated role routing and brute-force throttling:
- **Flexible Login Identifiers**: Users can authenticate using:
  - **Email Address**: `superadmin@multicred.com`, staff email, or agent email.
  - **Username Shortcuts**: `admin`, `superadmin`, `branchadmin`.
  - **Branch Code**: e.g., `BR001`, `BR002` (resolves to the assigned Branch Admin).
  - **Staff / Employee Code**: e.g., `STF001`, `EMP042`.
  - **Agent Member Code / ID**: e.g., `AG000042`, `MBR10023`.
- **Automatic Dashboard Dispatch**: Inspects user role upon login and immediately redirects to the appropriate portal.
- **Brute Force Protection**: Built-in rate limiting (`throttle:10,1`) to protect administrative accounts.

---

### 3.2 Deposit Schemes & Banking Lifecycle Engine
MultiCred supports configurable deposit schemes tailored for co-operative credit operations:

- **Supported Scheme Classes**:
  - **Fixed Deposit (FD)**: Lump-sum investment over flexible tenures (Months to Years) with maturity payout.
  - **Recurring Deposit (RD)**: Scheduled periodic contributions (Monthly / Quarterly) with compounding interest.
  - **Daily Deposit (DD / Pigmy)**: Doorstep micro-savings collected daily or weekly by field agents.
- **Banking Parameters**:
  - Flexible calculation types (Compounding, Simple, Flat).
  - Senior Citizen bonus interest rates.
  - Mandatory lock-in periods & premature withdrawal penalty percentages.
  - Loan against deposit eligibility thresholds (e.g., up to 80% or 90% of principal).
  - Grace periods for installment collections.
- **Deposit State Machine**:
  ```
  [ DRAFT ] ──> [ PENDING_APPROVAL ] ──> [ APPROVED ] ──> [ ACTIVE ] ──> [ MATURED ] ──> [ CLOSED ]
                       │                      │
                       v                      v
             [ RETURN_CORRECTION ]        [ REJECTED ]
  ```

---

### 3.3 Multi-Tier & Multi-Year Commission Engine
MultiCred includes a sophisticated commission engine (`CommissionService` and `FdCommissionSchemeService`) designed to handle complex multi-tier compensation:

- **12 Standardized Career Levels**:
  From Level 1 (`Facilitator`) to Level 12 (`Deputy Director`).
- **Multi-Year Commission Payout Schedule**:
  For long-tenure schemes (e.g., 7-year FD schemes), commissions can be configured on a per-year basis (Year 1 through Year 7). The system automatically generates annual commission schedules amortized across the tenure.
- **Direct & Differential Hierarchy Overrides**:
  When a downline agent books a deposit, the system evaluates the upline tree and calculates differential overrides between the booking agent's rank and superior upline ranks.
- **Full Financial Ledger & Payment Workflow**:
  ```
  [ PENDING ] ──> [ APPROVED ] ──> [ PAYABLE ] ──> [ PAID ]
         │
         v
   [ REJECTED ]
  ```
- **Payment Method Tracking**:
  Records payment mode (Cash, Bank Transfer, Cheque, Online/UPI, Demand Draft), voucher reference numbers, payment dates, and disbursing officer audit stamps.

---

### 3.4 Agent Downline Hierarchy & Placement
- **Unlimited Tree Depth**: Agents can sponsor downline agents, establishing clear parent-child relationships.
- **Flexible Branch Placement**:
  - **Branch-Affiliated Agents**: Assigned to a specific physical branch.
  - **Free-Floating / Unassigned Field Agents**: Independent agents who can place business across branches without strict branch locks.
- **Automated Provisioning & Welcome Kit**:
  - Secure random password generation via `PasswordGeneratorService`.
  - Professional HTML onboarding emails (`AccountWelcomeMail`) delivering member codes and login instructions.
- **Profile Image & Avatar Cropping**: Integrated image crop pipeline storing avatars in public storage.

---

### 3.5 Agent Lead CRM Pipeline
- **Lead Capture**: Agents log prospective customer inquiries directly from desktop or mobile devices.
- **Fields Captured**: Contact details, address, interested deposit scheme, target investment amount, follow-up dates, and activity notes.
- **Status Workflow**:
  `NEW` &rarr; `CONTACTED` &rarr; `INTERESTED` &rarr; `CONVERTED` or `NOT INTERESTED`.
- **One-Click Lead Conversion**:
  Converting an `INTERESTED` lead instantly pre-fills the customer creation and deposit application forms with zero duplicate data entry.
- **Supervision**: Branch Admins and Super Admins have unified oversight over all open and converted leads in their jurisdiction.

---

### 3.6 Automated Career Progression & Promotion Engine
- **Dynamic Rule Criteria** (Configurable by Super Admin per rank):
  - Minimum active customers.
  - Minimum personal deposit volume.
  - Minimum collection turnover.
  - Minimum service tenure days.
  - Minimum downline qualifying agent counts and tiers.
- **Automated Eligibility Scoring**:
  `PromotionEligibilityService` calculates agent metrics against promotion criteria in real time.
- **Self-Service Promotion Flow**:
  1. Agent views their milestone progress bar on their dashboard.
  2. Once 100% criteria are met, the agent submits a promotion request with remarks.
  3. Branch Admin and Super Admin review achievements and approve or reject with comments.
  4. On approval, the agent's rank is updated and recorded in the permanent `CareerHistory` ledger.

---

### 3.7 Collections Scheduling & Verifications
- **Automated Collection Schedules**:
  Generates collection schedules based on frequency (Daily, Weekly, Monthly, Quarterly).
- **Two-Step Verification**:
  Field agents submit collections, which are verified and reconciled by Branch Operations Staff before updating deposit ledgers and triggering commission calculations.

---

### 3.8 Business Intelligence & Exportable Reports
MultiCred provides high-level dashboard metrics and detailed tabular reports with one-click **CSV / Excel data exports**:
- **Customer Reports**: Demographics, KYC statuses, branch distribution.
- **Deposit Portfolio Reports**: Active balances, maturity forecast, scheme breakdown.
- **Collection Reports**: Daily/monthly collection totals, payment mode reconciliation.
- **Commission Reports**: Pending vs. paid amounts, agent breakdown, tax/payout summaries.
- **Agent Performance Reports**: Top producers, team volumes, active downline count.
- **Career & Promotion Reports**: Promotion timelines, rank distribution.
- **System Audit Logs**: Historical chronological event log for compliance.

---

### 3.9 Enterprise Security, Audit Logs & Permission Matrix
- **Immutable Financial Audit Trail**: Every sensitive change (deposit status change, commission approval, rule alteration, role update) writes to `audit_logs` storing the actor ID, IP address, branch ID, event type, and JSON before/after snapshots.
- **Granular Staff Permissions**: Super Admins can toggle individual permissions (e.g., `view-deposits`, `verify-collections`, `approve-commissions`, `view-reports`) per staff role or user.
- **Safe Soft Deletes**: Core financial and membership tables implement Eloquent Soft Deletes (`deleted_at`) to ensure data preservation.

---

## 4. Technology Stack & Design System

### Backend Architecture
- **Framework**: Laravel 13.x / 12.x on PHP 8.4+
- **Database Engine**: MySQL 8.x / MariaDB or SQLite
- **Architecture Pattern**: Controller-Service-Repository pattern with explicit Form Requests and Transactional Boundaries (`DB::transaction`).
- **Code Standards**: PSR-12 enforced via **Laravel Pint**.

### Frontend & Styling
- **CSS Architecture**: Dual-layer system with **Tailwind CSS v4** & **Bootstrap 5.3** for component consistency.
- **Color Palette & Brand Identity**:
  - Forest Green (`#14532D`): Primary buttons and actions.
  - Deep Emerald (`#064E3B`): Primary navigation and brand header.
  - Accent Lime (`#65A30D` / `#D9F99D`): Highlights and active states.
  - Metallic Gold (`#D4A64A`): Badges and status accents.
  - Clean Background (`#F8FAF7`): Workspace body canvas.
- **Typography**: Google Fonts Inter & Instrument Sans.
- **Icons**: Bootstrap Icons (`bi-*`).
- **Build Tool**: Vite 8.0 with `@tailwindcss/vite` and `laravel-vite-plugin`.

---

## 5. Repository Directory Structure

```plaintext
multicred-deposit-commission/
├── app/
│   ├── Console/Commands/              # Custom Artisan CLI commands
│   │   ├── ClearDatabaseCommand.php
│   │   ├── SendTestMailCommand.php
│   │   └── SyncFdCommissionSchemesCommand.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                 # Operational controllers (Staff, Agents, Deposits, Commissions)
│   │   │   ├── Agent/                 # Agent portal (Leads, Performance, Downline, Notifications)
│   │   │   ├── BranchAdmin/           # Branch admin portal (Dashboard, Approvals)
│   │   │   ├── Staff/                 # Staff workstation portal
│   │   │   └── SuperAdmin/            # Super Admin governance (Branches, Schemes, Rules, Ranks)
│   │   └── Middleware/                # Admin, SuperAdmin, BranchAdmin, Agent role middleware
│   ├── Mail/                          # AccountWelcomeMail, TestEmail templates
│   ├── Models/                        # 26 Eloquent entities (Deposit, Commission, Agent, Lead, etc.)
│   └── Services/                      # Domain services (CommissionService, CareerPerformanceService, etc.)
├── database/
│   ├── factories/                     # Model factories for testing
│   ├── migrations/                    # 38 database migration definitions
│   └── seeders/                       # DatabaseSeeder, AgentRankSeeder (12 standardized ranks)
├── resources/
│   ├── css/app.css                    # Tailwind CSS v4 design tokens and brand styling
│   ├── js/app.js                      # Frontend scripts and Vite entry points
│   └── views/
│       ├── admin/                     # Shared administrative views (Reports, Schemes, Commissions)
│       ├── agent/                     # Dedicated Agent portal views
│       ├── branch-admin/              # Dedicated Branch Admin views
│       ├── emails/                    # Responsive HTML email templates
│       ├── staff/                     # Dedicated Staff views
│       └── superadmin/                # Super Admin master control views & layouts
├── routes/
│   ├── console.php                    # Artisan console closures
│   └── web.php                        # Portal route mappings & middleware groups
├── tests/
│   ├── Feature/                       # 33 comprehensive Feature test suites
│   └── Unit/                          # Architectural and unit validation tests
├── vite.config.js                     # Vite build configuration
└── composer.json                      # PHP dependencies and automation scripts
```

---

## 6. Installation & Local Development Setup

Follow these steps to configure and run MultiCred in your local development environment:

### Prerequisites
- **PHP**: `^8.3` or `^8.4` with `pdo`, `mbstring`, `openssl`, `bcmath`, `gd`, `fileinfo` extensions enabled.
- **Composer**: `^2.2` or later.
- **Node.js & npm**: Node 18+ and npm 9+.
- **Database**: MySQL 8.0+ / MariaDB 10.4+ or SQLite 3.35+.
- **Local Server**: XAMPP, Herd, Valet, or PHP's built-in CLI server.

### Setup Instructions

#### 1. Clone the Repository
```bash
git clone https://github.com/your-org/multicred-deposit-commission.git
cd multicred-deposit-commission
```

#### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install
```

#### 3. Environment Configuration
Copy the example environment file and generate the application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` in your editor and configure your database and mail credentials:
```env
APP_NAME="Pristine Agro"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multicred_db
DB_USERNAME=root
DB_PASSWORD=
```
*(Alternatively, set `DB_CONNECTION=sqlite` for zero-configuration testing.)*

#### 4. Run Migrations & Seeders
Execute the database schema creation and seed standard roles, permissions, and 12 career progression ranks:
```bash
php artisan migrate --seed
```

#### 5. Link Public Storage
Ensure avatars and KYC documents are accessible via the web:
```bash
php artisan storage:link
```

#### 6. Build Frontend Assets
```bash
# Build for production
npm run build

# Or run Vite in hot-reloading development mode
npm run dev
```

#### 7. Start the Local Server
```bash
php artisan serve
```
Visit `http://localhost:8000` in your web browser. You will be greeted by the MultiCred login gateway.

---

## 7. Default Demo Accounts & Seeded Credentials

When running `php artisan db:seed`, the system automatically provisions the root Super Administrator and standard role hierarchies:

| Portal Role | Login Identifier | Default Password | Initial Scope |
| :--- | :--- | :--- | :--- |
| **Super Administrator** | `superadmin@multicred.com` *(or `admin`)* | `password` | Full global master access |
| **Branch Administrator** | Assigned branch email *(or branch code `BR001`)* | Set upon creation | Branch-scoped access |
| **Field Agent** | Agent email *(or member code e.g. `AG000042`)* | Auto-emailed on invite | Personal & downline access |
| **Staff Member** | Staff email *(or employee ID e.g. `STF001`)* | Set upon creation | Counter & collection access |

> [!TIP]
> You can also use the username shortcut `admin` or `superadmin` with password `password` at the login page.

---

## 8. Custom Artisan CLI Commands

MultiCred provides tailored CLI utilities to manage operations and maintain system integrity:

### 1. Synchronize FD Commission Schemes
Calculates and synchronizes 1-Year through 6-Year deposit commission schedules based on the master 7-Year FD schedule:
```bash
# Preview and run synchronization
php artisan commission:sync-fd-schemes

# Run non-interactively and recalculate existing active deposit commissions
php artisan commission:sync-fd-schemes --fix-deposits --force
```

### 2. Clear Dummy & Transactional Data
Safely clears test deposits, collections, leads, and customer transactions while preserving all Super Admin credentials, branches, staff positions, roles, and the 12-rank progression framework:
```bash
# Interactive mode (prompts for confirmation)
php artisan db:clear

# Non-interactive / CI execution
php artisan db:clear --force
```

### 3. SMTP Mail System Diagnostics
Validates the local mail delivery pipeline and verifies that transactional emails (such as welcome credentials) reach their destination:
```bash
php artisan mail:test your-email@domain.com
```

---

## 9. Automated Test Suite & Quality Assurance

MultiCred maintains high reliability with 33 feature and unit test suites covering the entire business workflow:

```bash
# Run all tests
php artisan test

# Run a specific feature test suite
php artisan test --filter=CommissionEngineTest
php artisan test --filter=DepositLifecycleManagementTest
php artisan test --filter=AgentDownlineManagementTest
php artisan test --filter=CareerAndPromotionManagementTest

# Format PHP code according to project style guidelines
vendor/bin/pint --format agent
```

### Test Coverage Highlights:
- **Deposit Lifecycle**: Validation of states (`PENDING`, `APPROVED`, `ACTIVE`, `MATURED`, `CLOSED`).
- **Commission Engine**: Payout schedules, multi-tier downline overrides, multi-year FD splits.
- **Agent Hierarchies**: Downline tree structures, unassigned/free placement agent scenarios.
- **Lead Pipeline**: Fast-status updates, lead-to-deposit customer conversion.
- **Financial Audit Trail**: Immutable logging on status updates and payout disbursements.

---

## 10. License & Support

The MultiCred software is proprietary and confidential, developed for **Pristine Agro Co-operative Society**. Unauthorized copying, modification, distribution, or reproduction of this software via any medium is strictly prohibited.

For technical inquiries, feature proposals, or security disclosures:
- **Administration**: Pristine Agro Technology & Information Systems Division
- **Internal Helpdesk**: [support@pristineagro.org](mailto:support@pristineagro.org)
- **Portal URL**: [http://localhost:8000](http://localhost:8000)
