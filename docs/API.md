# API & Web Routing Reference: MultiCred

This document provides a comprehensive specification of all 215 web and operational API endpoints registered in the **MultiCred** application.

---

## 1. Authentication & Public Gateway

| Method | URI | Route Name | Action | Auth / Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | — | Closure | None | Root entry: redirects authenticated users to their role portal, or unauthenticated users to `/superadmin/login`. |
| `GET` | `/login` | `login` | `SuperAdmin\AuthController@showLogin` | `guest` | Canonical login interface. |
| `POST` | `/login` | `login.post` | `SuperAdmin\AuthController@login` | `guest`, `throttle:10,1` | Process credentials (email, username, branch code, staff code, agent code). |
| `GET` | `/superadmin/login` | `superadmin.login` | `SuperAdmin\AuthController@showLogin` | `guest` | Super Admin login alias. |
| `POST` | `/superadmin/login` | `superadmin.login.post` | `SuperAdmin\AuthController@login` | `guest`, `throttle:10,1` | Super Admin login submission. |
| `POST` | `/superadmin/logout` | `superadmin.logout` | `SuperAdmin\AuthController@logout` | `auth` | Invalidate current session and log user out. |

### Login Payload Specification
- **Request Body**:
  - `login` (or `email`): `string`, required. (Accepts email, username, `BR001`, `STF001`, or `AG000042`).
  - `password`: `string`, required.
- **Success Response**: Redirects (`302`) to the appropriate portal:
  - Super Admin &rarr; `/superadmin/dashboard`
  - Branch Admin &rarr; `/branch-admin/dashboard`
  - Agent &rarr; `/agent/dashboard`
  - Staff &rarr; `/staff/dashboard`
- **Error Response**: Redirect back (`302`) with flash validation errors in `session('errors')`.

---

## 2. Super Admin Governance Routes (`/superadmin/*`)

Protected by `web`, `auth`, and `superadmin` middleware.

### 2.1 Dashboard & Settings
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/dashboard` | `superadmin.dashboard` | `DashboardController@index` | Central executive metrics & KPI summaries. |
| `GET` | `/superadmin/profile` | `superadmin.profile` | `ProfileController@show` | View Super Admin profile & credentials. |
| `PUT` | `/superadmin/profile` | `superadmin.profile.update` | `ProfileController@update` | Update admin profile details. |
| `PUT` | `/superadmin/profile/password`| `superadmin.profile.password` | `ProfileController@updatePassword` | Update admin password. |

### 2.2 Physical Branches (`/superadmin/branches`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/branches` | `superadmin.branches.index` | `BranchController@index` | List all physical society branches. |
| `GET` | `/superadmin/branches/create` | `superadmin.branches.create` | `BranchController@create` | Form to provision a new branch. |
| `POST` | `/superadmin/branches` | `superadmin.branches.store` | `BranchController@store` | Store new branch (validates name, code, phone). |
| `GET` | `/superadmin/branches/{branch}/edit` | `superadmin.branches.edit` | `BranchController@edit` | Edit branch form. |
| `PUT` | `/superadmin/branches/{branch}` | `superadmin.branches.update` | `BranchController@update` | Update branch details. |
| `DELETE`| `/superadmin/branches/{branch}` | `superadmin.branches.destroy` | `BranchController@destroy` | Soft-delete branch. |
| `POST` | `/superadmin/branches/{branch}/toggle-status` | `superadmin.branches.toggle-status` | `BranchController@toggleStatus` | Toggle branch `active`/`inactive`. |

### 2.3 Branch Administrators (`/superadmin/branch-admins`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/branch-admins` | `superadmin.branch-admins.index` | `BranchAdminController@index` | List branch administrative accounts. |
| `POST` | `/superadmin/branch-admins` | `superadmin.branch-admins.store` | `BranchAdminController@store` | Provision branch admin and assign branch. |
| `PUT` | `/superadmin/branch-admins/{branchAdmin}` | `superadmin.branch-admins.update` | `BranchAdminController@update` | Update branch admin credentials. |
| `DELETE`| `/superadmin/branch-admins/{branchAdmin}` | `superadmin.branch-admins.destroy` | `BranchAdminController@destroy` | Remove branch administrator access. |

### 2.4 Staff Positions & Career Ranks
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/staff-positions` | `superadmin.staff-positions.index` | `StaffPositionController@index` | List staff job positions. |
| `POST` | `/superadmin/staff-positions` | `superadmin.staff-positions.store` | `StaffPositionController@store` | Create staff position. |
| `POST` | `/superadmin/staff-positions/{staffPosition}/toggle-status` | `superadmin.staff-positions.toggle-status` | `StaffPositionController@toggleStatus` | Toggle position status. |
| `GET` | `/superadmin/agent-ranks` | `superadmin.agent-ranks.index` | `AgentRankController@index` | List 12 career progression ranks. |
| `PUT` | `/superadmin/agent-ranks/{agentRank}` | `superadmin.agent-ranks.update` | `AgentRankController@update` | Update rank descriptions and titles. |
| `POST` | `/superadmin/agent-ranks/{agentRank}/toggle-status` | `superadmin.agent-ranks.toggle-status` | `AgentRankController@toggleStatus` | Toggle rank eligibility status. |

### 2.5 Dynamic Promotion Rules (`/superadmin/promotion-rules`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/promotion-rules` | `superadmin.promotion-rules.index` | `PromotionRuleController@index` | List promotion criteria rules. |
| `POST` | `/superadmin/promotion-rules` | `superadmin.promotion-rules.store` | `PromotionRuleController@store` | Configure rank qualification criteria. |
| `PUT` | `/superadmin/promotion-rules/{promotionRule}` | `superadmin.promotion-rules.update` | `PromotionRuleController@update` | Modify criteria thresholds. |
| `POST` | `/superadmin/promotion-rules/{promotionRule}/toggle-status` | `superadmin.promotion-rules.toggle-status` | `PromotionRuleController@toggleStatus` | Toggle rule status. |

### 2.6 Dynamic Deposit Commission Schemes (`/superadmin/fd-commissions`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/fd-commissions` | `superadmin.fd-commissions.index` | `DepositCommissionSchemeController@index` | List scheme rank commission matrices. |
| `POST` | `/superadmin/fd-commissions` | `superadmin.fd-commissions.store` | `DepositCommissionSchemeController@store` | Create scheme with rank percentages. |
| `GET` | `/superadmin/fd-commissions/scheme-details/{fdCommissionScheme}` | `superadmin.fd-commissions.scheme-details` | `DepositCommissionSchemeController@getSchemeDetails` | Fetch JSON details of scheme. |
| `POST` | `/superadmin/fd-commissions/{fdCommissionScheme}/toggle-status` | `superadmin.fd-commissions.toggle-status` | `DepositCommissionSchemeController@toggleStatus` | Toggle scheme commission status. |

### 2.7 Multi-Year Interest Rates (`/superadmin/interest-rates`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/interest-rates` | `superadmin.interest-rates.index` | `DepositInterestRateController@index` | List multi-year interest schedules. |
| `POST` | `/superadmin/interest-rates` | `superadmin.interest-rates.store` | `DepositInterestRateController@store` | Create tenure interest rate schedule. |
| `GET` | `/superadmin/interest-rates/scheme/{depositScheme}` | `superadmin.interest-rates.by-scheme` | `DepositInterestRateController@getRatesForScheme` | JSON rate schedule by scheme. |
| `MATCH`| `/superadmin/interest-rates/sync` | `superadmin.interest-rates.sync` | `DepositInterestRateController@syncAll` | Synchronize interest schedules. |
| `MATCH`| `/superadmin/interest-rates/{interestRate}/toggle-status` | `superadmin.interest-rates.toggle-status` | `DepositInterestRateController@toggleStatus` | Toggle rate schedule status. |

### 2.8 Staff Permission Allocation Matrix (`/superadmin/permissions`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/superadmin/permissions` | `superadmin.permissions.index` | `PermissionController@index` | Permission matrix management UI. |
| `POST` | `/superadmin/permissions/allocate-staff` | `superadmin.permissions.allocate-staff` | `PermissionController@allocateStaff` | Assign specific permission array to a staff member. |
| `POST` | `/superadmin/permissions/allocate-role` | `superadmin.permissions.allocate-role` | `PermissionController@allocateRole` | Assign permissions to a system role. |

---

## 3. Operational Administration Routes (`/admin/*`)

Protected by `web`, `auth`, and `admin` middleware. Access is structurally scoped to the user's assigned branch.

### 3.1 Field Agents (`/admin/agents`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/agents` | `admin.agents.index` | `AgentController@index` | List field agents (filterable by branch/rank). |
| `GET` | `/admin/agents/next-id` | `admin.agents.next-id` | `AgentController@nextId` | Returns JSON next autogenerated Agent ID. |
| `POST` | `/admin/agents` | `admin.agents.store` | `AgentController@store` | Onboard agent, generate password, send email. |
| `GET` | `/admin/agents/{agent}` | `admin.agents.show` | `AgentController@show` | View agent profile and downline metrics. |
| `POST` | `/admin/agents/{agent}/accept` | `admin.agents.accept` | `AgentController@accept` | Accept pending agent onboarding. |
| `POST` | `/admin/agents/{agent}/verify` | `admin.agents.verify` | `AgentController@accept` | Verify agent documents. |
| `POST` | `/admin/agents/{agent}/reject` | `admin.agents.reject` | `AgentController@reject` | Reject agent onboarding with reason. |
| `POST` | `/admin/agents/{agent}/toggle-status` | `admin.agents.toggle-status` | `AgentController@toggleStatus` | Deactivate/activate agent. |

### 3.2 Lead Oversight (`/admin/leads`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/leads` | `admin.leads.index` | `AdminLeadController@index` | View branch/society-wide prospect leads. |
| `GET` | `/admin/leads/{lead}` | `admin.leads.show` | `AdminLeadController@show` | View lead details and conversion history. |
| `PATCH`| `/admin/leads/{lead}/status` | `admin.leads.update-status` | `AdminLeadController@updateStatus` | Update lead status (`CONTACTED`, `INTERESTED`). |

### 3.3 Customer Management (`/admin/customers`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/customers` | `admin.customers.index` | `CustomerController@index` | List customer accounts. |
| `GET` | `/admin/customers/create` | `admin.customers.create` | `CustomerController@create` | **Aborts with 404** (registration removed). |
| `POST` | `/admin/customers` | `admin.customers.store` | `CustomerController@store` | **Aborts with 404** (registration removed). |
| `GET` | `/admin/customers/{customer}` | `admin.customers.show` | `CustomerController@show` | View customer profile, KYC, and deposits. |
| `PUT` | `/admin/customers/{customer}` | `admin.customers.update` | `CustomerController@update` | Update customer address and details. |
| `POST` | `/admin/customers/{customer}/toggle-status`| `admin.customers.toggle-status` | `CustomerController@toggleStatus` | Toggle customer status. |

### 3.4 Deposit Schemes (`/admin/deposit-schemes`)
*Super Admin only (blocked for Branch Admins in `AdminMiddleware`).*
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/deposit-schemes` | `admin.deposit-schemes.index` | `DepositSchemeController@index` | List deposit schemes. |
| `GET` | `/admin/deposit-schemes/preview-code` | `admin.deposit-schemes.preview-code` | `DepositSchemeController@previewCode` | Generate preview scheme code. |
| `GET` | `/admin/deposit-schemes/{depositScheme}/rate-for-tenure` | `admin.deposit-schemes.rate-for-tenure` | `DepositSchemeController@getRateForTenure` | Returns JSON interest rate for tenure. |
| `POST` | `/admin/deposit-schemes` | `admin.deposit-schemes.store` | `DepositSchemeController@store` | Create master deposit scheme. |

### 3.5 Deposits & Banking Lifecycle (`/admin/deposits`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/deposits` | `admin.deposits.index` | `DepositController@index` | List deposits (filtered by branch/status). |
| `GET` | `/admin/deposits/pending-approval` | `admin.deposits.pending-approval` | `DepositController@pendingApproval` | Pending verification and approval queue. |
| `GET` | `/admin/deposits/create` | `admin.deposits.create` | `DepositController@create` | Application form (supports `?lead_id=X`). |
| `POST` | `/admin/deposits` | `admin.deposits.store` | `DepositController@store` | Store deposit, convert lead, create customer. |
| `GET` | `/admin/deposits/{deposit}` | `admin.deposits.show` | `DepositController@show` | Detailed account statement & voucher view. |
| `POST` | `/admin/deposits/{deposit}/verify` | `admin.deposits.verify` | `DepositController@verify` | Level-1 deposit verification. |
| `POST` | `/admin/deposits/{deposit}/return-correction` | `admin.deposits.return-correction` | `DepositController@returnForCorrection` | Send back for document corrections. |
| `POST` | `/admin/deposits/{deposit}/approve` | `admin.deposits.approve` | `DepositController@approve` | Authorize deposit application. |
| `POST` | `/admin/deposits/{deposit}/activate` | `admin.deposits.activate` | `DepositController@activate` | Activate account & generate commission schedules. |
| `POST` | `/admin/deposits/{deposit}/mature` | `admin.deposits.mature` | `DepositController@mature` | Settle matured deposit balance. |
| `POST` | `/admin/deposits/{deposit}/close` | `admin.deposits.close` | `DepositController@close` | Premature or completed account closure. |

### 3.6 Commission Management (`/admin/commissions`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/commissions/overview` | `admin.commissions.overview` | `CommissionController@overview` | High-level commission payout summaries. |
| `GET` | `/admin/commissions/pending` | `admin.commissions.pending` | `CommissionController@pending` | Unapproved commission vouchers. |
| `GET` | `/admin/commissions/approved` | `admin.commissions.approved` | `CommissionController@approved` | Approved vouchers awaiting payable marking. |
| `GET` | `/admin/commissions/payable` | `admin.commissions.payable` | `CommissionController@payable` | Ready for cashier disbursement. |
| `GET` | `/admin/commissions/paid` | `admin.commissions.paid` | `CommissionController@paid` | Disbursed payment history. |
| `POST` | `/admin/commissions/{commission}/approve` | `admin.commissions.approve` | `CommissionController@approve` | Approve commission voucher (`APPROVED`). |
| `POST` | `/admin/commissions/{commission}/reject` | `admin.commissions.reject` | `CommissionController@reject` | Reject commission voucher with remarks. |
| `POST` | `/admin/commissions/{commission}/mark-payable` | `admin.commissions.mark-payable` | `CommissionController@markPayable` | Move voucher from `APPROVED` &rarr; `PAYABLE`. |
| `POST` | `/admin/commissions/{commission}/pay` | `admin.commissions.process-payment` | `CommissionController@processPayment` | Disburse payment (`PAID`) with payment mode. |

### 3.7 Career & Promotion Approvals (`/admin/career`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/career/overview` | `admin.career.overview` | `CareerController@overview` | Agent career progression distribution. |
| `GET` | `/admin/career/performance` | `admin.career.performance` | `CareerController@performance` | Society-wide agent productivity rankings. |
| `GET` | `/admin/career/promotion-requests` | `admin.career.requests.index` | `CareerController@promotionRequests` | Pending agent promotion requests queue. |
| `POST` | `/admin/career/requests/{promotionRequest}/approve` | `admin.career.requests.approve` | `CareerController@approve` | Approve promotion, promote agent, log history. |
| `POST` | `/admin/career/requests/{promotionRequest}/reject` | `admin.career.requests.reject` | `CareerController@reject` | Reject promotion request with feedback. |

### 3.8 Reports & Streaming CSV Exports (`/admin/reports`)
| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/reports` | `admin.reports.overview` | `ReportController@overview` | Reports dashboard and summary charts. |
| `GET` | `/admin/reports/customers` | `admin.reports.customers` | `ReportController@customers` | Tabular customer demographics report. |
| `GET` | `/admin/reports/customers/export` | `admin.reports.customers.export` | `ReportController@exportCustomers` | **CSV Export**: Customer data stream. |
| `GET` | `/admin/reports/deposits` | `admin.reports.deposits` | `ReportController@deposits` | Tabular deposit portfolio report. |
| `GET` | `/admin/reports/deposits/export` | `admin.reports.deposits.export` | `ReportController@exportDeposits` | **CSV Export**: Deposit portfolio stream. |
| `GET` | `/admin/reports/commissions` | `admin.reports.commissions` | `ReportController@commissions` | Tabular commission disbursement report. |
| `GET` | `/admin/reports/commissions/export` | `admin.reports.commissions.export` | `ReportController@exportCommissions` | **CSV Export**: Commission payout stream. |
| `GET` | `/admin/reports/agents` | `admin.reports.agents` | `ReportController@agentPerformance` | Tabular agent productivity rankings. |
| `GET` | `/admin/reports/agents/export` | `admin.reports.agents.export` | `ReportController@exportAgentPerformance`| **CSV Export**: Agent performance stream. |
| `GET` | `/admin/reports/career` | `admin.reports.career` | `ReportController@career` | Tabular promotions and milestone report. |
| `GET` | `/admin/reports/career/export` | `admin.reports.career.export` | `ReportController@exportCareer` | **CSV Export**: Career history stream. |
| `GET` | `/admin/reports/audit` | `admin.reports.audit` | `ReportController@audit` | Tabular chronological audit log. |
| `GET` | `/admin/reports/audit/export` | `admin.reports.audit.export` | `ReportController@exportAudit` | **CSV Export**: Immutable audit trail stream. |

---

## 4. Field Agent Dedicated Portal (`/agent/*`)

Protected by `web`, `auth`, and `agent` middleware.

| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/agent/dashboard` | `agent.dashboard` | `AgentDashboardController@index` | Agent personal KPI metrics and recent activity. |
| `GET` | `/agent/performance` | `agent.performance` | `AgentPerformanceController@index` | 100-point performance score & promotion progress. |
| `POST` | `/agent/promotion-request` | `agent.promotion-request.store` | `AgentPerformanceController@requestPromotion` | Submit self-service promotion request. |
| `GET` | `/agent/promotion-history` | `agent.promotion-history` | `AgentPerformanceController@history` | History of promotion applications. |
| `GET` | `/agent/notifications` | `agent.notifications` | `AgentNotificationController@index` | Agent alerts and commission notifications. |
| `GET` | `/agent/leads` | `agent.leads.index` | `AgentLeadController@index` | Personal Lead CRM pipeline. |
| `POST` | `/agent/leads` | `agent.leads.store` | `AgentLeadController@store` | Add new prospect lead. |
| `PATCH`| `/agent/leads/{lead}/quick-status` | `agent.leads.quick-status` | `AgentLeadController@updateQuickStatus` | Inline AJAX status update for lead. |
| `GET` | `/agent/agents` | `agent.agents.index` | `DownlineAgentController@index` | Multi-tier downline network explorer. |
| `GET` | `/agent/agents/create` | `agent.agents.create` | `DownlineAgentController@create` | Onboard direct downline agent. |
| `POST` | `/agent/agents` | `agent.agents.store` | `DownlineAgentController@store` | Register sponsored downline agent. |

---

## 5. Branch Admin & Staff Portals

| Method | URI | Route Name | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/branch-admin/dashboard` | `branch-admin.dashboard` | `BranchAdminDashboardController@index` | Branch executive dashboard (`branch-admin` middleware). |
| `GET` | `/branch-admin/notifications` | `branch-admin.notifications` | `BranchAdminNotificationController@index`| Branch alerts queue. |
| `GET` | `/staff/dashboard` | `staff.dashboard` | `StaffDashboardController@index` | Operations counter workstation (`admin` middleware). |
