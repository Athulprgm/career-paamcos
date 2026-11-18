# Business Rules & Financial Calculations: MultiCred

This document provides a verified, mathematically precise specification of all financial calculations, amortization models, state workflows, and business invariants implemented in the **MultiCred** codebase.

---

## 1. Banking Deposit Calculations

All deposit interest and maturity formulas are implemented server-side in `App\Models\Deposit::calculateMaturityAmount` and verified against unit and feature test suites.

### 1.1 Fixed Deposit (FD) / Lump-Sum Scheme
Fixed Deposits are one-time lump-sum contributions where interest compounds annually over the tenure.

- **Interest Method**: Annual Compound Interest.
- **Formula**:
  $$M = P \times \left(1 + \frac{r}{100}\right)^t$$
  Where:
  - $M$ = Maturity Amount (₹).
  - $P$ = Principal Deposit Amount (`deposits.amount` in ₹).
  - $r$ = Annual Interest Rate percentage (`deposits.interest_rate` in %).
  - $t$ = Tenure in Years ($t = \frac{\text{tenure in months}}{12}$ or $\frac{\text{tenure in days}}{365}$).
- **Total Accrued Interest**:
  $$I = M - P$$
- **Rounding Rule**: Rounded to 2 decimal places (`round($maturity, 2)`). Optional whole integer rounding (`round($maturity)`) when specified by the scheme.
- **Edge Cases**: If $r \le 0$ or $t \le 0$, maturity amount equals the principal $P$.
- **Verified Tests**: `tests/Feature/FixedDepositAnnualCompoundInterestTest.php`.

### 1.2 Recurring Deposit (RD) Scheme
Recurring Deposits involve regular, scheduled monthly installment contributions.

- **Interest Method**: Monthly Compound Ordinary Annuity Formula.
- **Formula**:
  $$r = \frac{\text{Annual Interest Rate}}{1200}$$
  $$M = P \times \frac{(1 + r)^n - 1}{r}$$
  $$\text{Principal} = P \times n$$
  $$I = M - \text{Principal}$$
  Where:
  - $P$ = Monthly Installment Contribution Amount (`deposits.amount` in ₹).
  - $n$ = Total number of monthly installments ($n = \max(1, \text{round}(tenureInMonths))$).
  - $r$ = Monthly interest rate fraction ($r = \frac{\text{Annual Interest Rate}}{1200}$).
  - $I$ = Total Accrued Interest earned across the tenure ($I = M - \text{Principal}$).
  - $M$ = Total Maturity Amount disbursed to customer.
- **Edge Cases**: If Annual Interest Rate $\le 0$, $M = \text{Principal} = P \times n$ and $I = 0.00$.
- **Rounding Rule**: Standard banking 2 decimal places (`round($maturity, 2)`).
- **Verified Tests**: `tests/Feature/RdDepositCalculationTest.php`.

### 1.3 Daily Deposit (DD / Pigmy Micro-Savings) Scheme
Daily deposits represent doorstep micro-savings collected on daily or weekly cycles.

- **Interest Method**: Daily Proportionate Simple Interest.
- **Formula**:
  $$Days = \begin{cases} tenure \times 365 & \text{if unit = YEARS} \\ tenure \times 30 & \text{if unit = MONTHS} \\ tenure & \text{if unit = DAYS} \end{cases}$$
  $$TotalPrincipal = P \times Days$$
  $$I = TotalPrincipal \times \frac{r}{100} \times \frac{Days}{365}$$
  $$M = TotalPrincipal + I$$
  Where:
  - $P$ = Daily collection installment amount (₹).
  - $Days$ = Total operational collection days across the tenure.
  - $r$ = Annual Interest Rate percentage.
  - $M$ = Final maturity settlement amount.
- **Rounding Rule**: Rounded to 2 decimal places.

### 1.4 Monthly Return Plan (MRP / Monthly Income Scheme - MIS)
Documented in `monthly_return_plan_mrp_scheme_logic.txt`:
- **Deposit Nature**: Upfront one-time lump-sum investment (e.g. ₹1,00,000).
- **Interest Disbursement**: Interest is paid out **monthly** to the member's account rather than compounding to maturity.
- **Maturity Settlement**: 100% of the initial contribution (Principal) is refunded at maturity.
- **Formulas**:
  - Annual Interest:
    $$I_{annual} = \frac{P \times r}{100}$$
  - Monthly Return Payout:
    $$Payout_{monthly} = \text{round}\left(\frac{P \times r}{1200}, 2\right)$$
  - Total Return Received by Customer:
    $$TotalReceived = (Payout_{monthly} \times \text{tenure in months}) + P$$
- **Standard Baseline MRP Schedule (on ₹1,00,000 Contribution)**:
  | Tenure Year | General Rate (%) | General Monthly Return | Senior Rate (%) | Senior Monthly Return |
  | :--- | :--- | :--- | :--- | :--- |
  | **Year 1** | 10.00% | ₹830 | 10.50% | ₹875 |
  | **Year 2** | 10.50% | ₹875 | 11.00% | ₹916 |
  | **Year 3** | 11.00% | ₹916 | 11.50% | ₹958 |
  | **Year 4** | 11.50% | ₹958 | 12.00% | ₹1,000 |
  | **Year 5** | 12.00% | ₹1,000 | 12.50% | ₹1,040 |
  | **Year 6** | 12.50% | ₹1,040 | 13.00% | ₹1,083 |

### 1.5 Senior Citizen Incentive Rule
- Eligible customers (defined by `is_senior_citizen = true` or `date_of_birth` indicating age $\ge 60$) receive an additional **+0.50%** annual interest rate incentive across all tenures (`senior_citizen_extra_rate`).

---

## 2. Agency Commission Engine & Multi-Year Payout Rules

Commission calculations are encapsulated in `App\Services\CommissionService` and `App\Services\FdCommissionSchemeService`.

### 2.1 Direct Agent Commission Calculation
When a deposit is booked or an installment collection is verified:
- **Calculation Basis**:
  - `BASIS_DEPOSIT_AMOUNT`: Commission computed on the total principal deposit amount (`deposits.amount`).
  - `BASIS_COLLECTION_AMOUNT`: Commission computed on the verified installment payment (`deposit_collections.paid_amount`).
- **Calculation Formula**:
  $$CommissionAmount = \text{round}\left(BaseAmount \times \frac{Rate}{100}, 2\right)$$

### 2.2 Multi-Year Commission Payout Amortization (FD Schemes)
For long-tenure Fixed Deposits (e.g. 7-year schemes), commissions are not paid entirely upfront. The system generates separate annual commission vouchers across the tenure:
- **Amortization Period**: Payout Year 1 through Payout Year $N$ (where $N \le \text{tenure in years}$).
- **Annual Schedule**: Each payout year has its own rank commission percentage defined in `deposit_scheme_rank_commissions` (`payout_year: 1..7`).
- **Fallback Rule**: If only Year 1 is explicitly configured for a multi-year deposit, the system divides the total rate evenly across tenure years:
  $$Rate_{annual} = \text{round}\left(\frac{Rate_{year1}}{TenureYears}, 2\right)$$
- **Voucher Ledger**: A separate `Commission` record is created for each payout year (`payout_year = 1, 2, ...`), allowing annual approvals.
- **CLI Sync Utility**: `php artisan commission:sync-fd-schemes --fix-deposits`.

### 2.3 Upline Downline Differential Override Rule
When a downline agent books a deposit, superior upline sponsors in the hierarchy tree earn a differential override:
- **Formula**:
  $$OverrideRate = \max(0.00, Rate_{upline\_rank} - Rate_{downline\_rank})$$
  $$OverrideAmount = \text{round}\left(BaseAmount \times \frac{OverrideRate}{100}, 2\right)$$
- If the booking agent holds equal or higher rank than the upline sponsor, the differential is zero (no negative commission).

### 2.4 Cumulative Commission Ceiling Invariant
- Decimal-safe precision is enforced using PHP `bcadd`.
- **System Invariant**: The total combined commission percentage across all eligible ranks for any scheme year cannot exceed **100.00%**:
  $$\sum_{rank=1}^{12} Rate_{rank} \le 100.00\%$$
- Any attempt to configure a scheme exceeding 100.00% throws a `ValidationException`.

---

## 3. Career Progression & 12-Rank Promotion Framework

Managed via `App\Services\PromotionEligibilityService` and `App\Services\CareerPerformanceService`.

### 3.1 The 12 Standardized Career Ranks
1. Level 1: `Facilitator` (`FACILITATOR`) — Entry-level agent mobilizing micro-savings.
2. Level 2: `Field Officer` (`FIELD_OFFICER`) — Guides direct facilitators.
3. Level 3: `Development Officer` (`DEVELOPMENT_OFFICER`) — Handles team portfolio.
4. Level 4: `Deputy Sales Manager` (`DEPUTY_SALES_MANAGER`) — Sub-branch supervisor.
5. Level 5: `Business Development Manager` (`BUSINESS_DEVELOPMENT_MANAGER`) — Branch business leader.
6. Level 6: `Area Development Manager` (`AREA_DEVELOPMENT_MANAGER`) — Multi-branch area coordinator.
7. Level 7: `Marketing Manager` (`MARKETING_MANAGER`) — Mobilization strategy leader.
8. Level 8: `Senior Marketing Manager` (`SENIOR_MARKETING_MANAGER`) — Strategic campaign director.
9. Level 9: `Divisional Manager` (`DIVISIONAL_MANAGER`) — Division operations executive.
10. Level 10: `Regional Development Manager` (`REGIONAL_DEVELOPMENT_MANAGER`) — Regional expansion director.
11. Level 11: `Zonal Development Manager` (`ZONAL_DEVELOPMENT_MANAGER`) — Zonal governance lead.
12. Level 12: `Deputy Director` (`DEPUTY_DIRECTOR`) — Senior executive overseeing society agency hierarchy.

### 3.2 Dynamic Promotion Qualification Criteria
To qualify for advancement from Rank $A$ to Rank $B$, an agent must satisfy all configured thresholds defined in `promotion_rules`:
1. **Active Customers**: Minimum number of introduced customers in `ACTIVE` status.
2. **Personal Deposit Volume**: Cumulative ₹ volume of active/approved deposits directly booked by the agent.
3. **Collection Turnover**: Cumulative ₹ volume of verified and paid installment collections.
4. **Service Tenure**: Minimum number of days since `agents.joining_date`.
5. **Qualifying Downline Agents**: Minimum count of active downline agents in the agent's subordinate tree holding specified minimum qualifying tiers (`qualifying_agent_tiers` JSON).

### 3.3 Composite Agent Performance Score (100-Point Formula)
Implemented in `CareerPerformanceService::calculateAgentPerformance`:
$$Score_{customer} = \min(25.0, ActiveCustomers \times 5.0)$$
$$Score_{deposit} = \min\left(25.0, \frac{DepositAmount}{10000} \times 5.0\right)$$
$$Score_{collection} = \min\left(30.0, \frac{CollectionAmount}{5000} \times 5.0\right)$$
$$Score_{tenure} = \min\left(20.0, \frac{TenureDays}{30} \times 5.0\right)$$
$$PerformanceScore = \text{round}(Score_{customer} + Score_{deposit} + Score_{collection} + Score_{tenure}, 2)$$
Maximum composite score is capped at **100.00**.

---

## 4. Entity Numbering & Identification Rules

Implemented in `App\Services\NumberingService` with atomic database row locks:
- **Field Agent**: `AG` + 6 digits &rarr; `AG000042`
- **Customer Number**: `CUST` + 6 digits &rarr; `CUST000108`
- **Deposit Account**: `DEP` + 6 digits &rarr; `DEP000523`
- **Installment Collection**: `COL` + 6 digits &rarr; `COL001249`
- **Commission Voucher**: `COM` + 6 digits &rarr; `COM000841`
- **Promotion Request**: `PR` + 6 digits &rarr; `PR000019`
- **Collection Payment**: `PAY` + 6 digits &rarr; `PAY003102`

---

## 5. Workflow State Machines & Invariants

### 5.1 Deposit Lifecycle Transitions
- Valid transitions:
  - `DRAFT` &rarr; `PENDING_APPROVAL`
  - `PENDING_APPROVAL` &rarr; `APPROVED` (or `REJECTED`, `RETURN_CORRECTION`)
  - `APPROVED` &rarr; `ACTIVE` (Triggers `CommissionService::calculateAndCreateForDeposit`)
  - `ACTIVE` &rarr; `MATURED`
  - `MATURED` &rarr; `CLOSED`
- **Invariant**: Commissions cannot be generated or approved for deposits that are in `DRAFT`, `PENDING_APPROVAL`, or `REJECTED` states.

### 5.2 Commission Voucher Lifecycle Transitions
- Valid transitions:
  - `PENDING` &rarr; `APPROVED` (or `REJECTED`)
  - `APPROVED` &rarr; `PAYABLE`
  - `PAYABLE` &rarr; `PAID`
- **Invariant**: Direct transitions from `PENDING` to `PAID` are prohibited. Every voucher must be approved and flagged payable before disbursement.
