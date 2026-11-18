# Testing Guide & Quality Assurance: MultiCred

This guide documents the automated testing framework, test organization, execution procedures, and regression verification standards for the **MultiCred** codebase.

---

## 1. Test Framework & Environment

- **Test Runner**: [PHPUnit](https://phpunit.de) version `12.5.12`.
- **Harness**: Laravel Testing Suite (`Tests\TestCase`).
- **PHP Version**: `8.4.25`.
- **Configuration File**: `phpunit.xml` in repository root.
- **Environment Overrides** (`phpunit.xml`):
  - `APP_ENV=testing`
  - `CACHE_STORE=array`
  - `MAIL_MAILER=array`
  - `QUEUE_CONNECTION=sync`
  - `SESSION_DRIVER=array`
  - `BCRYPT_ROUNDS=4` (accelerates test authentication)
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=multicred-deposit-commission`

---

## 2. Test Suite Organization

The test suite is structured into two main tiers under `tests/`:

```
tests/
├── TestCase.php                                      # Base test class
├── Unit/                                             # Pure logic & formatting tests (3 suites)
│   ├── CurrencyFormatterTest.php                     # Tests Lakhs, Crores, and word formatters
│   ├── DepositSchemeCodeGenerationTest.php           # Tests scheme code generation algorithms
│   └── ExampleTest.php                               # Basic framework smoke test
│
└── Feature/                                          # End-to-end workflow suites (39 suites)
    ├── AgentDownlineManagementTest.php               # Downline tree & recursive query validation
    ├── AgentFreePlacementTest.php                    # Free-floating field agent placement
    ├── AgentLeadManagementTest.php                   # Lead CRM workflow & status updates
    ├── AgentModuleTest.php                           # Agent onboarding, documents, acceptance
    ├── AgentProfileImageCropTest.php                 # Avatar crop & storage pipeline
    ├── AutoGeneratePasswordAndWelcomeMailTest.php    # Password generator & onboarding mailer
    ├── BranchAdminAccessTest.php                     # Branch admin portal access & isolation
    ├── BranchAdminLeadAndCustomerTest.php            # Branch admin lead and customer scoping
    ├── BranchAuthAndCreationTest.php                 # Branch creation & authentication
    ├── CareerAndPromotionManagementTest.php          # 12-rank scoring & promotion workflow
    ├── CommissionEngineTest.php                      # Direct commission & differential overrides
    ├── CommissionsIndexModernDesignTest.php          # Commission dashboard UI & filters
    ├── CustomerAndKycManagementTest.php              # Customer profiles & KYC document checks
    ├── CustomerLoginKycAndAddOptionRemovalTest.php   # Direct customer creation 404 enforcement
    ├── DepositCreateLeadAgentTest.php                # Deposit creation with lead conversion
    ├── DepositLifecycleManagementTest.php            # Full deposit state machine (Draft -> Closed)
    ├── DepositMultiYearInterestRateSystemTest.php    # Multi-year interest rate schedules
    ├── DepositSchemeCodeGenerationFeatureTest.php    # Scheme code uniqueness in database
    ├── DepositSchemeCommissionWorkflowTest.php       # Scheme commission configuration
    ├── DepositSchemeConfigurationTest.php            # FD, RD, DD scheme parameters
    ├── FdCommissionManagementTest.php                # Master FD commission scheme service
    ├── FixedDepositAnnualCompoundInterestTest.php    # FD compound interest formula validation
    ├── MultiYearDepositCommissionTest.php            # Multi-year commission amortization (Y1..Y7)
    ├── MultiYearDepositInterestRateTest.php          # Dynamic tenure interest rate matrices
    ├── RdDepositCalculationTest.php                  # RD installment compound interest formulas
    ├── ReportsAndAnalyticsTest.php                   # Tabular reports & CSV data exports
    ├── SecurityAndAuditLoggingTest.php               # AuditLog recording on mutations
    ├── SecurityAndFinancialAuditRemediationTest.php  # Audit trail financial data integrity
    ├── SendTestMailTest.php                          # SMTP mail testing CLI command
    ├── SimplifiedDepositWorkflowTest.php             # Streamlined deposit verification
    ├── StaffAndAgentAdministrationTest.php           # Staff administration & status toggling
    ├── StaffModuleTest.php                           # Staff profiles and positions
    ├── SuperAdminLeadTest.php                        # Super admin lead oversight
    ├── SuperAdminPermissionAndStaffAllocationTest.php# Permission matrix & staff allocations
    ├── SuperAdminProfileImageTest.php                # Admin profile avatar upload
    ├── SyncFdCommissionSchemesTest.php               # CLI commission synchronization command
    ├── SystemRefinementsTest.php                     # System refinements & regression checks
    ├── SystemSettingsManagementTest.php              # Global settings key-value persistence
    └── ExampleTest.php                               # Basic HTTP 200 welcome smoke test
```

---

## 3. Database Strategy: Zero State Mutation

All database feature tests employ Laravel's `DatabaseTransactions` trait:

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CommissionEngineTest extends TestCase
{
    use DatabaseTransactions;
    // Every test runs inside a transaction and is rolled back upon completion
}
```

> [!IMPORTANT]
> The `DatabaseTransactions` pattern ensures that automated test execution **never** pollutes or mutates persistent development or staging database data.

---

## 4. Test Execution Commands

### 4.1 Run the Full Test Suite
```bash
php artisan test
```

### 4.2 Run Specific Test Suites
```bash
# Run Unit Tests only
php artisan test tests/Unit

# Run specific feature test suites
php artisan test --filter=CurrencyFormatterTest
php artisan test --filter=CommissionEngineTest
php artisan test --filter=DepositLifecycleManagementTest
php artisan test --filter=CareerAndPromotionManagementTest
php artisan test --filter=FdCommissionManagementTest
php artisan test --filter=MultiYearDepositCommissionTest
```

### 4.3 Direct PHPUnit Invocation
```bash
vendor/bin/phpunit --filter=CurrencyFormatterTest
```

---

## 5. Verified Test Execution Evidence

During the audit of this repository, the unit test suite was executed directly against the local environment:

```
Command: php artisan test --filter=CurrencyFormatterTest
Output:
{"tool":"phpunit","result":"passed","tests":3,"passed":3,"assertions":40,"duration_ms":9}
```

- **Tests Run**: 3
- **Assertions Passed**: 40
- **Failures / Errors**: 0
- **Duration**: 9 ms

Tested cases verified:
1. `test_format_text_handles_crores_lakhs_and_thousands` (Verified formatting of 15 Crore, 10 Crore, 7.5 Crore, 1 Lakh, 25 Thousand, 500, 0).
2. `test_to_words_converts_correctly` (Verified word conversion for Indian currency figures).
3. `test_format_indian_number` (Verified Indian comma grouping notation: `15,00,00,000.00`).

---

## 6. Recommended Regression Testing Workflow

Before committing any modifications or deploying new releases, complete this quality checklist:

1. **Format Code**: Run Laravel Pint to ensure PSR-12 code compliance:
   ```bash
   vendor/bin/pint --format agent
   ```
2. **Run Affected Tests**: Run targeted tests for the modified module (e.g. `php artisan test --filter=CommissionEngineTest`).
3. **Run Full Regression Suite**: Run `php artisan test` to guarantee that existing banking rules remain intact.
4. **Audit Log Verification**: When testing financial state transitions, assert that an `audit_logs` record is created:
   ```php
   $this->assertDatabaseHas('audit_logs', [
       'event' => 'commission_approved',
       'auditable_id' => $commission->id,
   ]);
   ```
