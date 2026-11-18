<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CommissionRuleController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\DepositSchemeController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TargetManagementController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Agent\DownlineAgentController;
use App\Http\Controllers\Agent\LeadController as AgentLeadController;
use App\Http\Controllers\Agent\NotificationController as AgentNotificationController;
use App\Http\Controllers\Agent\PerformanceController as AgentPerformanceController;
use App\Http\Controllers\Agent\TargetController as AgentTargetController;
use App\Http\Controllers\BranchAdmin\DashboardController as BranchAdminDashboardController;
use App\Http\Controllers\BranchAdmin\NotificationController as BranchAdminNotificationController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\SuperAdmin\AgentRankController;
use App\Http\Controllers\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\BranchAdminController;
use App\Http\Controllers\SuperAdmin\BranchController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\DepositCommissionSchemeController;
use App\Http\Controllers\SuperAdmin\DepositInterestRateController;
use App\Http\Controllers\SuperAdmin\PasswordResetController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\ProfileController;
use App\Http\Controllers\SuperAdmin\PromotionRuleController;
use App\Http\Controllers\SuperAdmin\StaffPositionController;
use App\Mail\ResetPasswordMail;
use App\Mail\TestEmail;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / Root Redirection (Get Started landing page completely removed)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }
        if (auth()->user()->isBranchAdmin()) {
            return redirect()->route('branch-admin.dashboard');
        }
        if (auth()->user()->isAgent()) {
            return redirect()->route('agent.dashboard');
        }
        if (auth()->user()->isStaff()) {
            return redirect()->route('staff.dashboard');
        }
    }

    return redirect()->route('login');
})->name('welcome');

// Canonical root login & password reset routes
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => redirect()->route('superadmin.login'))->name('login');
    Route::post('/login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');

    Route::get('/forgot-password', fn () => redirect()->route('superadmin.password.request'))->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [SuperAdminAuthController::class, 'logout'])->name('logout');

if (app()->environment('local', 'testing')) {
    Route::get('/mail-preview', function () {
        return new TestEmail('Classic minimal design verification for Apple, Google, Samsung & major clients.');
    })->name('mail.preview');

    Route::get('/mail-preview/reset-password', function () {
        $dummyUser = new User([
            'name' => 'Demo User',
            'email' => 'demo.user@example.com',
        ]);

        return new ResetPasswordMail($dummyUser, 'sample-token-123', url('/reset-password/sample-token-123?email=demo.user@example.com'));
    })->name('mail.preview.reset-password');
}

/*
|--------------------------------------------------------------------------
| Admin — Operational Routes (Super Admin, Branch Admin, Agents)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Staff Module
    Route::resource('staff', StaffController::class);
    Route::post('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])
        ->name('staff.toggle-status');

    // Agent Module
    Route::get('agents/next-id', [AgentController::class, 'nextId'])
        ->name('agents.next-id');
    Route::resource('agents', AgentController::class);
    Route::post('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])
        ->name('agents.toggle-status');
    Route::post('agents/{agent}/accept', [AgentController::class, 'accept'])
        ->name('agents.accept');
    Route::post('agents/{agent}/verify', [AgentController::class, 'accept'])
        ->name('agents.verify');
    Route::post('agents/{agent}/reject', [AgentController::class, 'reject'])
        ->name('agents.reject');

    // Leads Module (Admin & Branch Admin view agent leads)
    Route::get('leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
    Route::patch('leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::delete('leads/{lead}', [AdminLeadController::class, 'destroy'])->name('leads.destroy');

    // Deposit Schemes Module
    Route::get('deposit-schemes/preview-code', [DepositSchemeController::class, 'previewCode'])
        ->name('deposit-schemes.preview-code');
    Route::get('deposit-schemes/{depositScheme}/rate-for-tenure', [DepositSchemeController::class, 'getRateForTenure'])
        ->name('deposit-schemes.rate-for-tenure');
    Route::get('deposit-schemes/{depositScheme}/rates', [DepositSchemeController::class, 'getRates'])
        ->name('deposit-schemes.rates');
    Route::get('deposit-schemes/{depositScheme}/clone', [DepositSchemeController::class, 'clone'])
        ->name('deposit-schemes.clone');
    Route::post('deposit-schemes/{depositScheme}/duplicate', [DepositSchemeController::class, 'duplicate'])
        ->name('deposit-schemes.duplicate');
    Route::resource('deposit-schemes', DepositSchemeController::class);
    Route::post('deposit-schemes/{depositScheme}/toggle-status', [DepositSchemeController::class, 'toggleStatus'])
        ->name('deposit-schemes.toggle-status');

    // Deposits Module
    Route::get('deposits/agent-leads/{agent}', [DepositController::class, 'getAgentLeads'])
        ->name('deposits.agent-leads');
    Route::get('deposits-history', [DepositController::class, 'history'])
        ->name('deposits.history');
    Route::resource('deposits', DepositController::class)->whereNumber('deposit');
    Route::post('deposits/{deposit}/verify', [DepositController::class, 'verify'])
        ->name('deposits.verify');
    Route::post('deposits/{deposit}/return-correction', [DepositController::class, 'returnForCorrection'])
        ->name('deposits.return-correction');
    Route::post('deposits/{deposit}/approve', [DepositController::class, 'approve'])
        ->name('deposits.approve');
    Route::post('deposits/{deposit}/reject', [DepositController::class, 'reject'])
        ->name('deposits.reject');
    Route::post('deposits/{deposit}/activate', [DepositController::class, 'activate'])
        ->name('deposits.activate');
    Route::post('deposits/{deposit}/mature', [DepositController::class, 'mature'])
        ->name('deposits.mature');
    Route::post('deposits/{deposit}/close', [DepositController::class, 'close'])
        ->name('deposits.close');

    // Commission Management Module
    Route::get('commissions/overview', [CommissionController::class, 'overview'])
        ->name('commissions.overview');
    Route::get('commissions/pending', [CommissionController::class, 'pending'])
        ->name('commissions.pending');
    Route::get('commissions/approved', [CommissionController::class, 'approved'])
        ->name('commissions.approved');
    Route::get('commissions/payable', [CommissionController::class, 'payable'])
        ->name('commissions.payable');
    Route::get('commissions/paid', [CommissionController::class, 'paid'])
        ->name('commissions.paid');
    Route::get('commissions/history', [CommissionController::class, 'history'])
        ->name('commissions.history');
    Route::post('commissions/{commission}/approve', [CommissionController::class, 'approve'])
        ->name('commissions.approve');
    Route::post('commissions/{commission}/reject', [CommissionController::class, 'reject'])
        ->name('commissions.reject');
    Route::post('commissions/{commission}/mark-payable', [CommissionController::class, 'markPayable'])
        ->name('commissions.mark-payable');
    Route::get('commissions/{commission}/pay', [CommissionController::class, 'showPaymentForm'])
        ->name('commissions.pay');
    Route::post('commissions/{commission}/pay', [CommissionController::class, 'processPayment'])
        ->name('commissions.process-payment');
    Route::resource('commissions', CommissionController::class)->only(['index', 'show']);

    // Commission Rules Module
    Route::resource('commission-rules', CommissionRuleController::class)->except(['destroy']);
    Route::post('commission-rules/{commissionRule}/toggle-status', [CommissionRuleController::class, 'toggleStatus'])
        ->name('commission-rules.toggle-status');

    // Career & Performance Module (Phase 7)
    Route::get('career/overview', [CareerController::class, 'overview'])
        ->name('career.overview');
    Route::get('career/performance', [CareerController::class, 'performance'])
        ->name('career.performance');
    Route::get('career/performance/{agent}', [CareerController::class, 'agentPerformance'])
        ->name('career.agent-performance');
    Route::get('career/promotion-requests', [CareerController::class, 'promotionRequests'])
        ->name('career.requests.index');
    Route::get('career/approved-promotions', [CareerController::class, 'approvedPromotions'])
        ->name('career.requests.approved');
    Route::get('career/history', [CareerController::class, 'history'])
        ->name('career.history');
    Route::get('career/requests/create/{agent}', [CareerController::class, 'createRequest'])
        ->name('career.requests.create');
    Route::post('career/requests/store/{agent}', [CareerController::class, 'storeRequest'])
        ->name('career.requests.store');
    Route::get('career/requests/{promotionRequest}', [CareerController::class, 'showRequest'])
        ->name('career.requests.show');
    Route::post('career/requests/{promotionRequest}/approve', [CareerController::class, 'approve'])
        ->name('career.requests.approve');
    Route::post('career/requests/{promotionRequest}/reject', [CareerController::class, 'reject'])
        ->name('career.requests.reject');

    // Financial Year Agent Target Management Module
    Route::prefix('targets')->name('targets.')->group(function () {
        Route::get('/', [TargetManagementController::class, 'index'])->name('index');
        Route::get('configurations', [TargetManagementController::class, 'configurations'])->name('configurations');
        Route::get('configurations/create', [TargetManagementController::class, 'createConfiguration'])->name('configurations.create');
        Route::post('configurations', [TargetManagementController::class, 'storeConfiguration'])->name('configurations.store');
        Route::get('configurations/{target}/edit', [TargetManagementController::class, 'editConfiguration'])->name('configurations.edit');
        Route::put('configurations/{target}', [TargetManagementController::class, 'updateConfiguration'])->name('configurations.update');
        Route::post('configurations/{target}/toggle-status', [TargetManagementController::class, 'toggleConfigurationStatus'])->name('configurations.toggle-status');

        Route::get('agents', [TargetManagementController::class, 'agentPerformance'])->name('agents.index');
        Route::get('agents/{agent}', [TargetManagementController::class, 'agentDetails'])->name('agents.show');
        Route::post('agents/{agent}/assign-custom', [TargetManagementController::class, 'assignCustomTarget'])->name('agents.assign-custom');

        Route::get('history', [TargetManagementController::class, 'history'])->name('history');

        Route::post('financial-years', [TargetManagementController::class, 'storeFinancialYear'])->name('financial-years.store');
        Route::post('financial-years/{financialYear}/set-current', [TargetManagementController::class, 'setCurrentFinancialYear'])->name('financial-years.set-current');
    });

    // Reports & Analytics Module (Phase 8)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'overview'])->name('overview');
        Route::get('customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('customers/export', [ReportController::class, 'exportCustomers'])->name('customers.export');
        Route::get('deposits', [ReportController::class, 'deposits'])->name('deposits');
        Route::get('deposits/export', [ReportController::class, 'exportDeposits'])->name('deposits.export');
        Route::get('commissions', [ReportController::class, 'commissions'])->name('commissions');
        Route::get('commissions/export', [ReportController::class, 'exportCommissions'])->name('commissions.export');
        Route::get('agents', [ReportController::class, 'agentPerformance'])->name('agents');
        Route::get('agents/export', [ReportController::class, 'exportAgentPerformance'])->name('agents.export');
        Route::get('career', [ReportController::class, 'career'])->name('career');
        Route::get('career/export', [ReportController::class, 'exportCareer'])->name('career.export');
        Route::get('audit', [ReportController::class, 'audit'])->name('audit');
        Route::get('audit/export', [ReportController::class, 'exportAudit'])->name('audit.export');
    });

    // Settings Module — Exclusively Profile Settings
    Route::get('settings', function () {
        return redirect()->route('superadmin.profile');
    })->name('settings.index');
});

/*
|--------------------------------------------------------------------------
| Super Admin — Guest Routes (login/logout)
|--------------------------------------------------------------------------
*/
Route::prefix('superadmin')->name('superadmin.')->group(function () {

    // Auth (guest only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [SuperAdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');

        Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    });

    Route::post('/logout', [SuperAdminAuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Super Admin — Protected Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('superadmin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Branch & Details — Branches
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])
            ->name('branches.toggle-status');

        // Branch & Details — Branch Admins
        Route::resource('branch-admins', BranchAdminController::class)->parameters([
            'branch-admins' => 'branchAdmin',
        ])->except(['show']);

        // Branch & Details — Staff Positions
        Route::resource('staff-positions', StaffPositionController::class)->parameters([
            'staff-positions' => 'staffPosition',
        ])->except(['show']);
        Route::post('staff-positions/{staffPosition}/toggle-status', [StaffPositionController::class, 'toggleStatus'])
            ->name('staff-positions.toggle-status');

        // Branch & Details — Agent Ranks
        Route::resource('agent-ranks', AgentRankController::class)->parameters([
            'agent-ranks' => 'agentRank',
        ])->except(['show']);
        Route::post('agent-ranks/{agentRank}/toggle-status', [AgentRankController::class, 'toggleStatus'])
            ->name('agent-ranks.toggle-status');

        // Permission Management & Staff Allocation (Super Admin only)
        Route::post('permissions/seed', [PermissionController::class, 'seed'])
            ->name('permissions.seed');
        Route::post('permissions/allocate-staff', [PermissionController::class, 'allocateStaff'])
            ->name('permissions.allocate-staff');
        Route::post('permissions/allocate-role', [PermissionController::class, 'allocateRole'])
            ->name('permissions.allocate-role');
        Route::resource('permissions', PermissionController::class)->except(['create', 'show', 'edit']);

        // Dynamic Promotion Rules (Super Admin only)
        Route::post('promotion-rules/global-targets', [PromotionRuleController::class, 'updateGlobalTargets'])
            ->name('promotion-rules.global-targets');
        Route::resource('promotion-rules', PromotionRuleController::class)->parameters([
            'promotion-rules' => 'promotionRule',
        ]);
        Route::post('promotion-rules/{promotionRule}/toggle-status', [PromotionRuleController::class, 'toggleStatus'])
            ->name('promotion-rules.toggle-status');

        // Dynamic Deposit Commission Schemes Management (Super Admin only)
        Route::get('fd-commissions/scheme-details/{fdCommissionScheme}', [DepositCommissionSchemeController::class, 'getSchemeDetails'])
            ->name('fd-commissions.scheme-details');
        Route::resource('fd-commissions', DepositCommissionSchemeController::class)->parameters([
            'fd-commissions' => 'fdCommissionScheme',
        ]);
        Route::post('fd-commissions/{fdCommissionScheme}/toggle-status', [DepositCommissionSchemeController::class, 'toggleStatus'])
            ->name('fd-commissions.toggle-status');

        // Dynamic Multi-Year Deposit Interest Rates (Super Admin only)
        Route::match(['get', 'post'], 'interest-rates/sync', [DepositInterestRateController::class, 'syncAll'])
            ->name('interest-rates.sync');
        Route::get('interest-rates/scheme/{depositScheme}', [DepositInterestRateController::class, 'getRatesForScheme'])
            ->name('interest-rates.by-scheme');
        Route::match(['post', 'patch'], 'interest-rates/{interestRate}/toggle-status', [DepositInterestRateController::class, 'toggleStatus'])
            ->name('interest-rates.toggle-status');
        Route::resource('interest-rates', DepositInterestRateController::class)->except(['show']);
    });

    // Settings — Profile (accessible to Super Admin and Branch Admins)
    Route::middleware('admin')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });
});

/*
|--------------------------------------------------------------------------
| Branch Admin — Dedicated Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('branch-admin')->name('branch-admin.')->middleware('branch-admin')->group(function () {
    Route::get('/dashboard', [BranchAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [BranchAdminNotificationController::class, 'index'])->name('notifications');
    Route::get('/targets', fn () => redirect()->route('admin.targets.index'))->name('targets');
});

/*
|--------------------------------------------------------------------------
| Agent — Dedicated Operational Routes
|--------------------------------------------------------------------------
*/
Route::prefix('agent')->name('agent.')->middleware('agent')->group(function () {
    Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/targets', [AgentTargetController::class, 'index'])->name('targets.index');
    Route::get('/performance', [AgentPerformanceController::class, 'index'])->name('performance');
    Route::get('/progress', [AgentPerformanceController::class, 'index'])->name('progress');
    Route::post('/promotion-request', [AgentPerformanceController::class, 'requestPromotion'])->name('promotion-request.store');
    Route::get('/promotion-history', [AgentPerformanceController::class, 'history'])->name('promotion-history');
    Route::get('/notifications', [AgentNotificationController::class, 'index'])->name('notifications');
    Route::patch('leads/{lead}/quick-status', [AgentLeadController::class, 'updateQuickStatus'])->name('leads.quick-status');
    Route::patch('leads/{lead}/status', [AgentLeadController::class, 'updateQuickStatus'])->name('leads.update-status');
    Route::resource('leads', AgentLeadController::class);

    // Downline Agents Module
    Route::get('agents/next-id', [DownlineAgentController::class, 'nextId'])
        ->name('agents.next-id');
    Route::resource('agents', DownlineAgentController::class)->only(['index', 'create', 'store', 'show']);
});

/*
|--------------------------------------------------------------------------
| Staff — Dedicated Operational Routes
|--------------------------------------------------------------------------
*/
Route::prefix('staff')->name('staff.')->middleware('admin')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
});
