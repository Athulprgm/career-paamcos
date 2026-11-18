# Future AI & Engineering Development Workflow: MultiCred

This document establishes the mandatory 11-step development lifecycle for all future engineers and AI coding agents working on the **MultiCred** codebase.

---

## 1. Visual Lifecycle Diagram

```
         ┌──────────────────────────────────────┐
         │       UNDERSTAND REQUIREMENTS        │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │       READ AGENT INSTRUCTIONS        │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │    INSPECT EXISTING IMPLEMENTATION   │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │        REVIEW BUSINESS RULES         │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │      CREATE IMPLEMENTATION PLAN      │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │   IDENTIFY RISKS AND AFFECTED FILES  │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │      IMPLEMENT APPROVED CHANGES      │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │          RUN RELEVANT TESTS          │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │      REVIEW CODE AND REGRESSIONS     │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │     UPDATE AFFECTED DOCUMENTATION    │
         └──────────────────┬───────────────────┘
                            ▼
         ┌──────────────────────────────────────┐
         │       REPORT VERIFIED RESULTS        │
         └──────────────────────────────────────┘
```

---

## 2. Step-by-Step Protocol

### Step 1: Understand Requirements
- Read the user's objective completely.
- Clarify any ambiguous specifications (e.g. scheme tenure, interest payout frequency, commission amortization schedule).
- Distinguish new feature development from bug fixes or refactoring.

### Step 2: Read Agent Instructions
- Consult root instructions in [AGENTS.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/AGENTS.md) and the comprehensive [AGENTS_REFERENCE.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/AGENTS_REFERENCE.md).
- Adhere strictly to file protection guidelines and repository invariants.

### Step 3: Inspect Existing Implementation
- **Inspect before editing**: Locate and read sibling controllers, models, and views to understand existing naming, patterns, and conventions.
- Check if an existing service or component already solves the problem before writing new code.
- Confirm installed dependency versions via `composer.json` and `package.json`.

### Step 4: Review Business Rules
- Review [BUSINESS_RULES.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/BUSINESS_RULES.md) for relevant mathematical formulas, deposit state machines, and commission ceilings.
- Verify that proposed changes will not violate core invariants (e.g. cumulative commission cap $\le 100\%$, soft delete requirements).

### Step 5: Create Implementation Plan
- Formulate a clear, sequential step-by-step plan.
- For non-trivial modifications, present the plan to the user or team lead before modifying code.

### Step 6: Identify Risks and Affected Files
- Map out every file requiring creation or modification.
- Assess potential regression risks:
  - Will changing a commission rate alter existing active deposit vouchers?
  - Does modifying a route break existing named route references in Blade templates?
  - Does a migration change columns used by active feature tests?

### Step 7: Implement Approved Changes
- Follow [CODING_STANDARDS.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/development/CODING_STANDARDS.md):
  - PHP 8.4 strict typing and promoted constructor properties.
  - Curly braces for all control structures.
  - Domain math in dedicated services.
  - Transactions and row locking on financial tables.

### Step 8: Run Relevant Tests
- Design new feature or unit tests covering the new functionality.
- Execute the narrowest relevant test suite first:
  ```bash
  php artisan test --filter=YourNewFeatureTest
  ```
- Re-run existing regression suites:
  ```bash
  php artisan test --filter=CommissionEngineTest
  php artisan test --filter=DepositLifecycleManagementTest
  ```

### Step 9: Review Code and Regressions
- Run Laravel Pint to ensure code conforms to the repository's PSR-12 style:
  ```bash
  vendor/bin/pint --format agent
  ```
- If frontend Blade or JavaScript assets were changed, verify assets compile cleanly:
  ```bash
  npm run build
  ```

### Step 10: Update Affected Documentation
- Update relevant documentation in `docs/`:
  - Update `docs/API.md` when adding or altering routes.
  - Update `docs/DATABASE.md` when introducing migrations.
  - Update `docs/BUSINESS_RULES.md` when adjusting calculation formulas.
  - Update `CHANGELOG.md` with release notes.

### Step 11: Report Verified Results
- Deliver a clear, evidence-based final report.
- Report exact commands executed, test results, and file diffs.
- Never claim tests passed or builds succeeded without actual execution proof.
