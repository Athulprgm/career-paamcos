# Troubleshooting & Operations Guide: MultiCred

This guide records known issues, system symptoms, root cause diagnoses, and verified operational resolutions for the **MultiCred** application.

---

## 1. Database & Migration Issues

### Issue 1: `Table 'performance_schema.session_status' doesn't exist`
- **Symptom**: Running `php artisan db:show` or certain database diagnostics in local development fails with:
  ```
  Illuminate\Database\QueryException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'performance_schema.session_status' doesn't exist
  ```
- **Root Cause**: Default MariaDB / MySQL installations in XAMPP on Windows disable the `performance_schema` engine to reduce memory overhead.
- **Verified Solution**:
  - Do not rely on `php artisan db:show` for schema inspection on XAMPP.
  - Inspect individual model schemas using `php artisan model:show {ModelName}` (e.g. `php artisan model:show Deposit`).
  - Query table listings directly via tinker:
    ```bash
    php artisan tinker --execute 'echo json_encode(Illuminate\Support\Facades\Schema::getTableListing());'
    ```

---

### Issue 2: `SQLSTATE[HY000] [2002] Connection refused`
- **Symptom**: Application cannot connect to MySQL database during startup or migration.
- **Root Cause**: MySQL/MariaDB service is stopped or port `3306` is blocked.
- **Verified Solution**:
  1. Open XAMPP Control Panel and start the **MySQL** module.
  2. Verify credentials in `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=multicred-deposit-commission
     DB_USERNAME=root
     DB_PASSWORD=
     ```
  3. Verify database connectivity using Tinker:
     ```bash
     php artisan tinker --execute 'echo DB::connection()->getPdo() ? "Connected\n" : "Failed\n";'
     ```

---

## 2. Customer & Lead Pipeline Issues

### Issue 3: `HTTP 404: Customer registration has been removed`
- **Symptom**: Navigating to `/admin/customers/create` or submitting `POST /admin/customers` triggers an HTTP `404 Not Found` error.
- **Root Cause**: This is an **intentional architectural guard** implemented in `App\Http\Controllers\Admin\CustomerController::create` and `AdminMiddleware`. Direct manual customer creation without a deposit or lead was deprecated to prevent unassigned, orphaned customer records.
- **Verified Solution**:
  - Customer records must be created through the **Lead Conversion Pipeline** (`/agent/leads` &rarr; Convert) or during the **Deposit Creation Workflow** (`/admin/deposits/create`), where entering a customer name automatically generates the customer profile linked to the booking agent.

---

## 3. Frontend & UI Asset Issues

### Issue 4: `Unable to locate file in Vite manifest: resources/css/app.css`
- **Symptom**: Web pages fail to render with a Vite manifest exception, or styling appears unstyled/broken.
- **Root Cause**: Frontend production bundles have not been compiled, or the Vite dev server is not running.
- **Verified Solution**:
  - Build the production asset bundle:
    ```bash
    npm run build
    ```
  - Or, during local development, start the Vite development server:
    ```bash
    npm run dev
    ```

---

### Issue 5: Uploaded Avatars / KYC Documents Return 404
- **Symptom**: Agent profile photos or uploaded documents display broken image icons.
- **Root Cause**: The symbolic link connecting `public/storage` to `storage/app/public` is missing or severed.
- **Verified Solution**:
  - Regenerate the symbolic link via Artisan:
    ```bash
    php artisan storage:link
    ```
  - Ensure `storage/app/public` has read permissions for the web server user.

---

## 4. Commission Calculation & Synchronization Issues

### Issue 6: Multi-Year Commission Payout Schedule Discrepancies
- **Symptom**: After creating or updating a long-tenure Fixed Deposit scheme, annual commission payout records for Year 2 through Year 7 are missing or uncalculated.
- **Root Cause**: Deposit schemes configured before the multi-year commission update need their payout matrices synchronized.
- **Verified Solution**:
  - Run the dedicated synchronization command:
    ```bash
    # Preview synchronized schemes
    php artisan commission:sync-fd-schemes

    # Force synchronization and recalculate existing active deposit commissions
    php artisan commission:sync-fd-schemes --fix-deposits --force
    ```

---

## 5. Authentication & Session Issues

### Issue 7: `HTTP 429: Too Many Requests` on Login
- **Symptom**: Users are locked out of the login screen with a 429 response.
- **Root Cause**: The `throttle:10,1` middleware triggered after 10 failed login attempts within a 60-second window.
- **Verified Solution**:
  - Wait 60 seconds for the rate limiter window to reset.
  - Or clear the application cache in local development:
    ```bash
    php artisan cache:clear
    ```

---

### Issue 8: Inactive Staff Account Login Block
- **Symptom**: A staff member logs in with valid credentials but is immediately redirected back to `/superadmin/login` with:
  `"Your staff account is currently inactive. Please contact the administrator."`
- **Root Cause**: The staff member's record in `staff.status` is set to `inactive`. `AdminMiddleware` detects this, invalidates the session, and performs `auth()->logout()`.
- **Verified Solution**:
  - Log in as Super Admin or Branch Admin.
  - Navigate to `/admin/staff`.
  - Toggle the staff member's account status to **Active** (`POST /admin/staff/{staff}/toggle-status`).
