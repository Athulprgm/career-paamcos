@extends('superadmin.layouts.app')

@section('page-title', 'Super Admin Dashboard')

@section('content')
@include('partials.banking-dashboard-styles')

@php
    $fmt = \App\Services\CurrencyFormatter::class;
    $hasAlerts = ($stats['pending_agents_count'] ?? 0) > 0
        || ($stats['pending_promotions_count'] ?? 0) > 0
        || ($stats['pending_deposits_count'] ?? 0) > 0;

    $goalPct = max(0, min(100, (float) ($mobilizationGoal['pct'] ?? 0)));
    $arcRadius = 86;
    $arcLength = M_PI * $arcRadius; // semi-circle circumference (~270.18)
    $arcFilled = round(($goalPct / 100) * $arcLength, 2);

    $totalSubmittedCapital = max(1.0, (float) $treasury['active_aum'] + (float) $treasury['pending_approval_amount']);
    $activeSharePct = ($treasury['active_aum'] + $treasury['pending_approval_amount']) > 0
        ? round(($treasury['active_aum'] / $totalSubmittedCapital) * 100, 1)
        : 0;
@endphp

<div class="mx-shell">
    <!-- ─── Standard Institutional Page Header ─────────────────────────────── -->
    <div class="page-header mb-1">
        <div>
            <h1 class="page-title">Super Admin Dashboard</h1>
            <div class="page-breadcrumb d-flex flex-wrap align-items-center gap-2">
                <span>MultiCred Co-operative Bank &bull; Executive Operations</span>
                @if($currentFy)
                    <span class="mx-fy-badge">
                        <i class="bi bi-calendar3"></i> Financial Year: {{ $currentFy->name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('superadmin.branches.create') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-building-add me-1"></i> Add Branch
            </a>
            <a href="{{ route('superadmin.branch-admins.create') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-person-plus me-1"></i> Add Branch Admin
            </a>
            <a href="{{ route('admin.reports.overview') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Financial Reports
            </a>
            <a href="{{ route('admin.deposits.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Open Deposit Account
            </a>
        </div>
    </div>

    <!-- ─── Minimal Modern Date & Time Bar ─────────────────────────────────── -->
    <section class="mx-datetime-strip" id="mxLiveClockWidget" aria-label="Current Date and Time">
        <div class="mx-datetime-left">
            <div class="mx-analog-clock-wrap" aria-hidden="true">
                <svg class="mx-analog-clock-svg" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--border-strong)" stroke-width="1.2" opacity="0.65"/>
                    <line id="mxAnalogHourHand" class="mx-clock-hand-hour" x1="18" y1="18" x2="18" y2="10"/>
                    <line id="mxAnalogMinuteHand" class="mx-clock-hand-min" x1="18" y1="18" x2="18" y2="7.5"/>
                    <line id="mxAnalogSecondHand" class="mx-clock-hand-sec" x1="18" y1="21" x2="18" y2="6.5"/>
                    <circle cx="18" cy="18" r="1.6" fill="var(--color-copper)"/>
                </svg>
            </div>

            <div class="mx-datetime-meta">
                <div class="mx-datetime-eyebrow">
                    <span id="mxDashboardWeekday">{{ \Carbon\Carbon::now()->format('l') }}</span>
                    <span aria-hidden="true">&middot;</span>
                    <span id="mxClockTzLabel">Local Time</span>
                </div>
                <div class="mx-datetime-full-date" id="mxDashboardFullDate">{{ \Carbon\Carbon::now()->format('d F Y') }}</div>
            </div>
        </div>

        <div class="mx-datetime-right">
            <div class="mx-digital-clock" role="timer" aria-live="off">
                <span class="mx-clock-unit" id="mxClockHour">{{ \Carbon\Carbon::now()->format('h') }}</span>
                <span class="mx-clock-sep" aria-hidden="true">:</span>
                <span class="mx-clock-unit" id="mxClockMin">{{ \Carbon\Carbon::now()->format('i') }}</span>
                <span class="mx-clock-sep" aria-hidden="true">:</span>
                <span class="mx-clock-unit sec" id="mxClockSec">{{ \Carbon\Carbon::now()->format('s') }}</span>
                <span class="mx-clock-ampm" id="mxClockAmPm">{{ \Carbon\Carbon::now()->format('A') }}</span>
            </div>

            <div class="mx-clock-format-toggle" id="mxFormatToggle" role="group" aria-label="Time format">
                <button type="button" class="mx-clock-fmt-btn active" data-fmt="12">12H</button>
                <button type="button" class="mx-clock-fmt-btn" data-fmt="24">24H</button>
            </div>
        </div>
    </section>

    <!-- ─── Pending Governance Alerts Strip ────────────────────────────────── -->
    @if($hasAlerts)
    <div class="mx-alert-strip">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <i class="bi bi-bell-fill" style="color: var(--color-copper); font-size: 1rem;"></i>
            <div class="small fw-semibold" style="color: var(--text-heading);">
                Action Required (Maker-Checker Queue):
                @if(($stats['pending_deposits_count'] ?? 0) > 0)
                    {{ $stats['pending_deposits_count'] }} Deposit {{ Str::plural('Account', $stats['pending_deposits_count']) }} Pending Authorization
                @endif
                @if(($stats['pending_agents_count'] ?? 0) > 0)
                    {{ ($stats['pending_deposits_count'] ?? 0) > 0 ? ' • ' : '' }}{{ $stats['pending_agents_count'] }} Field {{ Str::plural('Advisor', $stats['pending_agents_count']) }} Pending Onboarding
                @endif
                @if(($stats['pending_promotions_count'] ?? 0) > 0)
                    {{ (($stats['pending_deposits_count'] ?? 0) > 0 || ($stats['pending_agents_count'] ?? 0) > 0) ? ' • ' : '' }}{{ $stats['pending_promotions_count'] }} Promotion {{ Str::plural('Advancement', $stats['pending_promotions_count']) }} Pending
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(($stats['pending_deposits_count'] ?? 0) > 0)
                <a href="{{ route('admin.deposits.index', ['status' => 'PENDING_APPROVAL']) }}" class="mx-pill-btn brand py-1 px-3" style="font-size: 0.72rem;">
                    Authorize Deposits
                </a>
            @endif
            @if(($stats['pending_agents_count'] ?? 0) > 0)
                <a href="{{ route('admin.agents.index', ['status' => 'pending']) }}" class="mx-pill-btn ghost py-1 px-3" style="font-size: 0.72rem;">
                    Review Advisors
                </a>
            @endif
            @if(($stats['pending_promotions_count'] ?? 0) > 0)
                <a href="{{ route('admin.career.requests.index') }}" class="mx-pill-btn ghost py-1 px-3" style="font-size: 0.72rem;">
                    Review Promotions
                </a>
            @endif
        </div>
    </div>
    @endif

    <!-- ─── Main Asymmetric Fintech Grid (8 Cols Workspace + 4 Cols Treasury Rail) ─ -->
    <div class="row g-3 g-xl-4 align-items-start">

        <!-- ═══ LEFT COLUMN (8 COLS): KPIs, Breakdown, Arc Goal, Cashflow, Ledger ═══ -->
        <div class="col-xl-8 d-flex flex-column gap-3">

            <!-- Row 1: 3 Horizontal KPI Cards -->
            <div class="row g-3">
                <!-- 1. Deposit Volume -->
                <div class="col-md-4">
                    <a href="{{ route('admin.deposits.index') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon"><i class="bi bi-piggy-bank"></i></span>
                                <span>Deposit Volume</span>
                            </div>
                            <div class="mx-metric-val">₹{{ $fmt::formatIndianNumber($stats['total_deposit_amount'], 2) }}</div>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                @if($treasury['mom_growth_pct'] !== null)
                                    <span class="mx-trend-pill {{ $treasury['mom_growth_pct'] >= 0 ? 'up' : 'warn' }}">
                                        {{ $treasury['mom_growth_pct'] >= 0 ? '+' : '' }}{{ $treasury['mom_growth_pct'] }}% MoM Inflow
                                    </span>
                                @else
                                    <span class="mx-trend-pill neutral">{{ number_format($stats['active_deposits']) }} Active Accounts</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>

                <!-- 2. Pending Commissions -->
                <div class="col-md-4">
                    <a href="{{ route('admin.commissions.pending') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon copper"><i class="bi bi-hourglass-split"></i></span>
                                <span>Pending Commissions</span>
                            </div>
                            <div class="mx-metric-val">₹{{ $fmt::formatIndianNumber($stats['pending_commissions_amount'], 2) }}</div>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="mx-trend-pill warn">{{ number_format($stats['pending_commissions_count']) }} Awaiting Authorization</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- 3. Active Advisors & Branches -->
                <div class="col-md-4">
                    <a href="{{ route('admin.agents.index') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon"><i class="bi bi-people"></i></span>
                                <span>Active Advisors</span>
                            </div>
                            <div class="mx-metric-val">{{ number_format($stats['active_agents']) }}</div>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="mx-trend-pill up">{{ number_format($stats['total_branches']) }} {{ Str::plural('Operating Branch', $stats['total_branches']) }} &bull; {{ number_format($stats['total_agents']) }} Total Advisors</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Row 2: Portfolio Breakdown (Donut) + Mobilization Goal (Semi-Circle Arc Gauge) -->
            <div class="row g-3">
                <!-- Left: Portfolio Breakdown -->
                <div class="col-md-6">
                    <div class="mx-card h-100">
                        <div class="mx-card-head">
                            <div>
                                <h2 class="mx-card-title">Portfolio Breakdown</h2>
                                <p class="mx-card-sub">{{ $portfolioByType->sum('count') }} Active Deposit {{ Str::plural('Contract', $portfolioByType->sum('count')) }} &bull; {{ $stats['active_schemes_count'] }}/{{ $stats['total_schemes_count'] }} {{ Str::plural('Scheme', $stats['total_schemes_count']) }} Active</p>
                            </div>
                            <a href="{{ route('admin.deposit-schemes.index') }}" class="mx-more-btn" title="Deposit Scheme Directory">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <div class="mx-breakdown-grid">
                            <div class="mx-donut-box">
                                <canvas id="portfolioDonutChart"></canvas>
                                <div class="mx-donut-center">
                                    <span class="mx-donut-cap">Total AUM</span>
                                    <span class="mx-donut-total">₹{{ $fmt::formatIndianNumber($portfolioByType->sum('amount'), 0) }}</span>
                                </div>
                            </div>

                            @php
                                $schemePalette = ['var(--mx-series-1)', 'var(--mx-series-2)', 'var(--mx-series-3)', 'var(--mx-series-4)', 'var(--mx-series-5)'];
                            @endphp
                            <div class="mx-legend-list">
                                @forelse($portfolioByType as $idx => $scheme)
                                    @php $dotColor = $schemePalette[$idx % count($schemePalette)]; @endphp
                                    <div class="mx-legend-row">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="mx-legend-dot" style="background: {{ $dotColor }};"></span>
                                            <span class="text-truncate fw-medium" style="color: var(--text-secondary);">{{ $scheme['type'] }}</span>
                                        </div>
                                        <span class="fw-bold num-tabular flex-shrink-0" style="color: var(--text-heading);">
                                            ₹{{ $fmt::formatIndianNumber($scheme['amount'], 0) }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="small" style="color: var(--text-muted);">
                                        No active deposit contracts recorded yet. Open a deposit account to view portfolio distribution.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Mobilization Goal (Semi-Circle Arc Gauge) -->
                <div class="col-md-6">
                    <div class="mx-card h-100">
                        <div class="mx-card-head">
                            <div>
                                <h2 class="mx-card-title">{{ $mobilizationGoal['title'] }}</h2>
                                <p class="mx-card-sub">{{ $currentFy?->name ?? 'Current Financial Year' }} &bull; {{ $stats['total_agents'] }} {{ Str::plural('Field Advisor', $stats['total_agents']) }}</p>
                            </div>
                            <a href="{{ route('admin.targets.index') }}" class="mx-more-btn" title="Mobilization Target Management">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <div class="mx-gauge-wrap">
                            <div class="mx-gauge-svg-box">
                                <svg width="210" height="114" viewBox="0 0 210 114" aria-label="Mobilization Goal Gauge">
                                    <defs>
                                        <linearGradient id="mxGoalGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" class="mx-goal-stop-start" />
                                            <stop offset="55%" class="mx-goal-stop-mid" />
                                            <stop offset="100%" class="mx-goal-stop-end" />
                                        </linearGradient>
                                    </defs>
                                    <!-- Background Track -->
                                    <path d="M 19 102 A 86 86 0 0 1 191 102"
                                          fill="none"
                                          stroke="var(--border-color)"
                                          stroke-width="16"
                                          stroke-linecap="round"
                                          stroke-dasharray="4 6" />
                                    <!-- Progress Arc -->
                                    <path d="M 19 102 A 86 86 0 0 1 191 102"
                                          fill="none"
                                          stroke="url(#mxGoalGrad)"
                                          stroke-width="16"
                                          stroke-linecap="round"
                                          stroke-dasharray="{{ $arcFilled }} {{ $arcLength }}" />
                                </svg>
                                <div class="mx-gauge-overlay">
                                    <div class="mx-gauge-pct">{{ number_format($goalPct, 0) }}%</div>
                                    <div class="mx-gauge-sub">{{ $mobilizationGoal['mode'] === 'fy_target' ? 'Target Achieved' : 'Mobilization Rate' }}</div>
                                </div>
                            </div>

                            <div class="mx-goal-stats">
                                <div>
                                    <div style="font-size: 0.68rem; color: var(--text-muted); font-weight: 600;">Mobilized Principal</div>
                                    <div class="fw-bold num-tabular" style="font-size: 0.88rem; color: var(--text-heading);">
                                        ₹{{ $fmt::formatIndianNumber($mobilizationGoal['achieved'], 0) }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size: 0.68rem; color: var(--text-muted); font-weight: 600;">
                                        {{ $mobilizationGoal['mode'] === 'fy_target' ? 'FY Target' : 'Submitted Principal' }}
                                    </div>
                                    <div class="fw-bold num-tabular" style="font-size: 0.88rem; color: var(--text-heading);">
                                        ₹{{ $fmt::formatIndianNumber($mobilizationGoal['target'], 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Diverging Cashflow Telemetry Chart -->
            <div class="mx-card" x-data="{ range: '12m', mode: 'cashflow' }">
                <div class="mx-card-head mb-2">
                    <div>
                        <h2 class="mx-card-title">Treasury Mobilization &amp; Cashflow Telemetry</h2>
                        <p class="mx-card-sub">Monthly deposit inflows vs. maturity disbursements &amp; commission outflows</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="mx-seg-group">
                            <button type="button" class="mx-seg-btn" :class="{ 'active': mode === 'cashflow' }"
                                    @click="mode = 'cashflow'; window.updateSuperAdminCashflow && window.updateSuperAdminCashflow(range, mode)">
                                Cashflow Volume
                            </button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': mode === 'accounts' }"
                                    @click="mode = 'accounts'; window.updateSuperAdminCashflow && window.updateSuperAdminCashflow(range, mode)">
                                Deposit Accounts
                            </button>
                        </div>
                        <div class="mx-seg-group">
                            <button type="button" class="mx-seg-btn" :class="{ 'active': range === '6m' }"
                                    @click="range = '6m'; window.updateSuperAdminCashflow && window.updateSuperAdminCashflow(range, mode)">
                                6 Months
                            </button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': range === '12m' }"
                                    @click="range = '12m'; window.updateSuperAdminCashflow && window.updateSuperAdminCashflow(range, mode)">
                                12 Months
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-2 small" style="color: var(--text-muted);" x-show="mode === 'cashflow'">
                    <span class="d-inline-flex align-items-center gap-1">
                        <span style="width:9px;height:9px;border-radius:50%;background:var(--color-primary);display:inline-block;"></span>
                        Inflows (Deposit Receipts)
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <span style="width:9px;height:9px;border-radius:50%;background:var(--color-copper);display:inline-block;"></span>
                        Outflows (Maturities &amp; Commissions)
                    </span>
                </div>

                <div style="height: 255px; position: relative;">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>

            <!-- Row 4: Recent Deposits / Branches Ledger Table -->
            <div class="mx-card p-0 overflow-hidden" x-data="{ viewTab: 'deposits', statusFilter: 'ALL' }">
                <div class="mx-card-head p-3 px-4 mb-0 border-bottom" style="border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 class="mx-card-title">Recent Deposits</h3>
                        <div class="mx-seg-group ms-1">
                            <button type="button" class="mx-seg-btn" :class="{ 'active': viewTab === 'deposits' }" @click="viewTab = 'deposits'">
                                Deposit Ledger
                            </button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': viewTab === 'branches' }" @click="viewTab = 'branches'">
                                Operating Branches ({{ $recent_branches->count() }})
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="mx-seg-group" x-show="viewTab === 'deposits'">
                            <button type="button" class="mx-seg-btn" :class="{ 'active': statusFilter === 'ALL' }" @click="statusFilter = 'ALL'">All Accounts</button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': statusFilter === 'ACTIVE' }" @click="statusFilter = 'ACTIVE'">Active Accounts</button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': statusFilter === 'PENDING_APPROVAL' }" @click="statusFilter = 'PENDING_APPROVAL'">Pending Authorization</button>
                        </div>
                        <a href="{{ route('admin.deposits.index') }}" class="mx-pill-btn brand py-1 px-3" style="font-size: 0.72rem;" x-show="viewTab === 'deposits'">
                            View Full Ledger
                        </a>
                        <a href="{{ route('superadmin.branches.index') }}" class="mx-pill-btn brand py-1 px-3" style="font-size: 0.72rem; display: none;" x-show="viewTab === 'branches'">
                            Branch Directory
                        </a>
                    </div>
                </div>

                <!-- Deposits Ledger Tab -->
                <div x-show="viewTab === 'deposits'">
                    @if($recentDeposits->isNotEmpty())
                        <div class="table-responsive border-0 shadow-none">
                            <table class="mx-table">
                                <thead>
                                    <tr>
                                        <th>Account No. / Account Holder</th>
                                        <th>Deposit Product</th>
                                        <th>Value Date</th>
                                        <th class="text-end">Principal Amount (₹)</th>
                                        <th>Account Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDeposits as $deposit)
                                        <tr x-show="statusFilter === 'ALL' || statusFilter === '{{ $deposit->status }}'">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="mx-metric-mini-icon"><i class="bi bi-wallet2"></i></span>
                                                    <div>
                                                        <span class="mx-code">{{ $deposit->deposit_number }}</span>
                                                        <div class="small fw-semibold mt-1" style="color: var(--text-heading);">
                                                            {{ $deposit->customer?->name ?? 'Account Holder' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="mx-trend-pill neutral">{{ $deposit->deposit_type ?? 'FD' }}</span></td>
                                            <td class="small text-nowrap" style="color: var(--text-muted);">
                                                {{ $deposit->created_at?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="text-end fw-bold num-tabular" style="color: var(--text-heading);">
                                                ₹{{ $fmt::formatIndianNumber((float) $deposit->amount, 2) }}
                                            </td>
                                            <td>
                                                @if($deposit->status === 'ACTIVE')
                                                    <span class="badge-active">Active</span>
                                                @elseif($deposit->status === 'PENDING_APPROVAL')
                                                    <span class="badge-pending">Pending Authorization</span>
                                                @elseif($deposit->status === 'MATURED')
                                                    <span class="badge-matured">Matured</span>
                                                @else
                                                    <span class="badge-inactive">{{ $deposit->status }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.deposits.show', $deposit) }}" class="mx-pill-btn ghost py-1 px-2" style="font-size: 0.7rem;">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mx-empty">
                            <i class="bi bi-wallet2 fs-2 d-block mb-2" style="color: var(--color-primary);"></i>
                            <div class="fw-bold mb-1" style="color: var(--text-heading);">No Deposit Accounts Recorded Yet</div>
                            <p class="small mb-3">Open a Fixed, Recurring, or Daily Deposit account to initialize the live transaction ledger.</p>
                            <a href="{{ route('admin.deposits.create') }}" class="mx-pill-btn primary">
                                <i class="bi bi-plus-lg"></i> Open Deposit Account
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Branches Tab -->
                <div x-show="viewTab === 'branches'" style="display: none;">
                    @if($recent_branches->isNotEmpty())
                        <div class="table-responsive border-0 shadow-none">
                            <table class="mx-table">
                                <thead>
                                    <tr>
                                        <th>Operating Branch</th>
                                        <th>Branch Manager / Admin</th>
                                        <th class="text-center">Advisors &amp; Members</th>
                                        <th class="text-end">Mobilized AUM (₹)</th>
                                        <th>Operating Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_branches as $branch)
                                        <tr>
                                            <td>
                                                <a href="{{ route('superadmin.branches.edit', $branch) }}" class="fw-bold text-decoration-none d-block" style="color: var(--text-heading);">
                                                    {{ $branch->name }}
                                                </a>
                                                <span class="mx-code mt-1 d-inline-block">{{ $branch->code }}</span>
                                            </td>
                                            <td class="small">{{ $branch->admins->first()?->name ?? 'Unassigned' }}</td>
                                            <td class="text-center small num-tabular" style="color: var(--text-muted);">
                                                {{ $branch->agents_count }} Advisors &bull; {{ $branch->customers_count }} Members
                                            </td>
                                            <td class="text-end fw-bold num-tabular" style="color: var(--text-heading);">
                                                ₹{{ $fmt::formatIndianNumber((float) ($branch->deposits_sum_amount ?? 0), 2) }}
                                            </td>
                                            <td>
                                                @if($branch->is_active)
                                                    <span class="badge-active">Active</span>
                                                @else
                                                    <span class="badge-inactive">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('superadmin.branches.edit', $branch) }}" class="mx-pill-btn ghost py-1 px-2" style="font-size: 0.7rem;">Manage Branch</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mx-empty">
                            <div class="fw-bold mb-1" style="color: var(--text-heading);">No Operating Branches Registered</div>
                            <a href="{{ route('superadmin.branches.create') }}" class="mx-pill-btn primary mt-2">Register First Branch</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ═══ RIGHT RAIL (4 COLS): Vault Card, Advisors, Horizons, Activity ═══ -->
        <div class="col-xl-4 d-flex flex-column gap-3">

            <!-- Right Card 1: Treasury Summary Card -->
            <div class="mx-card">
                <div class="mx-vault-card mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold" style="font-size: 0.92rem;">MultiCred Treasury</span>
                        <span class="mx-vault-chip"><i class="bi bi-bank"></i> Active Deposit AUM</span>
                    </div>

                    <div class="my-2">
                        <div style="font-size: 0.7rem; opacity: 0.85;">Net Mobilized Deposit Capital</div>
                        <div class="mx-vault-amount">₹{{ $fmt::formatIndianNumber($treasury['active_aum'], 2) }}</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between" style="font-size: 0.73rem; opacity: 0.92;">
                        <span>Weighted Yield: <strong>{{ number_format($treasury['weighted_avg_rate'], 2) }}% p.a.</strong></span>
                        <span>{{ $stats['active_deposits'] }} Active {{ Str::plural('Contract', $stats['active_deposits']) }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-baseline justify-content-between mb-1">
                    <span class="small fw-semibold" style="color: var(--text-muted);">Scheduled Maturity Liabilities</span>
                    <span class="fw-bold num-tabular" style="font-size: 1.05rem; color: var(--text-heading);">
                        ₹{{ $fmt::formatIndianNumber($treasury['maturity_liability'], 2) }}
                    </span>
                </div>
                <div class="mx-balance-bar-track mb-2">
                    <div class="mx-balance-bar-fill" style="width: {{ max(6, $activeSharePct) }}%;"></div>
                </div>
                <div class="d-flex justify-content-between small mb-3" style="color: var(--text-muted); font-size: 0.71rem;">
                    <span>Pending Authorization: ₹{{ $fmt::formatIndianNumber($treasury['pending_approval_amount'], 0) }}</span>
                    <span>Gross Spread: +₹{{ $fmt::formatIndianNumber($treasury['interest_spread'], 0) }}</span>
                </div>

                <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
                    <a href="{{ route('admin.deposits.create') }}" class="mx-pill-btn primary">
                        <i class="bi bi-plus-circle"></i> Open Deposit
                    </a>
                    <a href="{{ route('admin.commissions.overview') }}" class="mx-pill-btn ghost">
                        <i class="bi bi-percent"></i> Commission Payouts
                    </a>
                </div>
            </div>

            <!-- Right Card 2: Top Field Advisors -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Top Field Advisors</h3>
                        <p class="mx-card-sub">{{ number_format($stats['active_agents']) }} active sourcing {{ Str::plural('facilitator', $stats['active_agents']) }} across {{ number_format($stats['active_branches']) }} operating {{ Str::plural('branch', $stats['active_branches']) }}</p>
                    </div>
                    <a href="{{ route('admin.agents.index') }}" class="mx-more-btn" title="Field Advisor Directory">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                @if($topAgents->isNotEmpty())
                    <div class="mx-advisor-strip mb-3">
                        @foreach($topAgents as $agent)
                            <a href="{{ route('admin.agents.show', $agent) }}" class="mx-advisor-chip" title="{{ $agent->name }} • ₹{{ $fmt::formatIndianNumber((float) ($agent->deposits_sum_amount ?? 0), 0) }}">
                                <div class="mx-advisor-avatar">
                                    @if($agent->profile_image_url)
                                        <img src="{{ $agent->profile_image_url }}" alt="{{ $agent->name }}" class="w-100 h-100 object-fit-cover bg-white" onerror="this.style.display='none'; this.parentElement.innerText='{{ strtoupper(substr($agent->name, 0, 2)) }}';">
                                    @else
                                        {{ strtoupper(substr($agent->name, 0, 2)) }}
                                    @endif
                                </div>
                                <span class="mx-advisor-name">{{ ucfirst(explode(' ', trim($agent->name))[0]) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="small text-center py-2 mb-2" style="color: var(--text-muted);">
                        No active field advisors registered.
                    </div>
                @endif

                <a href="{{ route('admin.agents.create') }}" class="mx-pill-btn primary w-100">
                    <i class="bi bi-person-plus"></i> Register New Field Advisor
                </a>
            </div>

            <!-- Right Card 3: Maturity Liquidity Horizon & Commission Settlement Pipeline (2x2 Grid) -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Maturity Liquidity Horizon</h3>
                        <p class="mx-card-sub">Upcoming maturity disbursements &amp; payable commissions</p>
                    </div>
                    <a href="{{ route('admin.commissions.payable') }}" class="mx-more-btn" title="Commission Settlement Queue">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="mx-mini-grid">
                    <a href="{{ route('admin.deposits.index', ['status' => 'ACTIVE']) }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon"><i class="bi bi-calendar-event"></i></span>
                            <span class="mx-mini-sub">{{ $maturityHorizon['days_30']['count'] }} accounts</span>
                        </div>
                        <div class="mx-mini-title">Next 30 Days</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($maturityHorizon['days_30']['payout'], 2) }}</div>
                    </a>

                    <a href="{{ route('admin.deposits.index', ['status' => 'ACTIVE']) }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon green"><i class="bi bi-calendar3"></i></span>
                            <span class="mx-mini-sub">{{ $maturityHorizon['days_60']['count'] + $maturityHorizon['days_90']['count'] }} accounts</span>
                        </div>
                        <div class="mx-mini-title">31 - 90 Days</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($maturityHorizon['days_60']['payout'] + $maturityHorizon['days_90']['payout'], 0) }}</div>
                    </a>

                    <a href="{{ route('admin.commissions.payable') }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon"><i class="bi bi-cash-coin"></i></span>
                            <span class="mx-mini-sub">{{ $commissionPipeline['payable_count'] }} authorized</span>
                        </div>
                        <div class="mx-mini-title">Payable Comm.</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($commissionPipeline['payable_amount'], 0) }}</div>
                    </a>

                    <a href="{{ route('admin.commissions.paid') }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon green"><i class="bi bi-check2-circle"></i></span>
                            <span class="mx-mini-sub">{{ $commissionPipeline['paid_count'] }} disbursed</span>
                        </div>
                        <div class="mx-mini-title">Settled Comm.</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($commissionPipeline['paid_amount'], 0) }}</div>
                    </a>
                </div>
            </div>

            <!-- Right Card 4: Recent Activity -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Recent Activity</h3>
                        <p class="mx-card-sub">Immutable operational audit trail &amp; authorization events</p>
                    </div>
                    <a href="{{ route('admin.reports.audit') }}" class="mx-more-btn" title="Comprehensive Audit Ledger">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="mx-activity-list">
                    @forelse($recentAudits as $idx => $audit)
                        <div class="mx-activity-item">
                            <span class="mx-activity-dot {{ $idx % 2 === 0 ? 'brand' : '' }}">
                                <i class="bi {{ $idx % 2 === 0 ? 'bi-shield-check' : 'bi-journal-text' }}"></i>
                            </span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="small fw-semibold" style="color: var(--text-heading); line-height: 1.35;">
                                    {{ Str::limit($audit->description, 68) }}
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mt-1" style="font-size: 0.7rem; color: var(--text-muted);">
                                    <span>{{ $audit->user_name ?? ($audit->user?->name ?? 'System Officer') }}</span>
                                    <span>{{ $audit->created_at?->diffForHumans(short: true) ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="small text-center py-3" style="color: var(--text-muted);">
                            No operational audit events recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Minimal Modern Dashboard Clock Driver ────────────────────────────── -->
<script>
(function initDashboardLiveClock() {
    const widget = document.getElementById('mxLiveClockWidget');
    if (!widget) return;

    const weekdayEl = document.getElementById('mxDashboardWeekday');
    const fullDateEl = document.getElementById('mxDashboardFullDate');
    const hourEl = document.getElementById('mxClockHour');
    const minEl = document.getElementById('mxClockMin');
    const secEl = document.getElementById('mxClockSec');
    const amPmEl = document.getElementById('mxClockAmPm');
    const tzLabelEl = document.getElementById('mxClockTzLabel');
    const fmtBtns = document.querySelectorAll('#mxFormatToggle .mx-clock-fmt-btn');

    const hourHand = document.getElementById('mxAnalogHourHand');
    const minHand = document.getElementById('mxAnalogMinuteHand');
    const secHand = document.getElementById('mxAnalogSecondHand');

    const fullDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const fullMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const pad = (n) => String(n).padStart(2, '0');

    let is24Hour = false;
    try {
        is24Hour = localStorage.getItem('multicred_clock_24h') === '1';
    } catch (e) {}

    function syncFormatButtons() {
        fmtBtns.forEach((btn) => {
            btn.classList.toggle('active', (btn.dataset.fmt === '24') === is24Hour);
        });
        if (amPmEl) {
            amPmEl.style.display = is24Hour ? 'none' : '';
        }
    }

    fmtBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            is24Hour = btn.dataset.fmt === '24';
            try {
                localStorage.setItem('multicred_clock_24h', is24Hour ? '1' : '0');
            } catch (e) {}
            prevVals.h = null;
            syncFormatButtons();
            renderFrame(new Date());
        });
    });

    const prevVals = { h: null, m: null, s: null, amPm: null, date: null, weekday: null };
    let cumulativeSecDeg = null;
    let lastSecVal = null;
    let timerId = null;

    function triggerTick(el, nextText, key) {
        if (!el || prevVals[key] === nextText) return;
        el.textContent = nextText;
        el.classList.remove('is-ticking');
        void el.offsetWidth;
        el.classList.add('is-ticking');
        prevVals[key] = nextText;
    }

    function resolveTimezoneLabel(now) {
        try {
            const parts = new Intl.DateTimeFormat(undefined, { timeZoneName: 'short' }).formatToParts(now);
            return parts.find((p) => p.type === 'timeZoneName')?.value || 'Local';
        } catch (e) {
            return 'Local';
        }
    }

    function renderFrame(now) {
        const h24 = now.getHours();
        const mins = now.getMinutes();
        const secs = now.getSeconds();
        const displayHour = is24Hour ? h24 : (h24 % 12 || 12);
        const amPm = h24 >= 12 ? 'PM' : 'AM';

        const weekdayStr = fullDays[now.getDay()];
        if (weekdayEl && prevVals.weekday !== weekdayStr) {
            weekdayEl.textContent = weekdayStr;
            prevVals.weekday = weekdayStr;
        }

        const dateStr = `${pad(now.getDate())} ${fullMonths[now.getMonth()]} ${now.getFullYear()}`;
        if (fullDateEl && prevVals.date !== dateStr) {
            fullDateEl.textContent = dateStr;
            prevVals.date = dateStr;
        }

        triggerTick(hourEl, pad(displayHour), 'h');
        triggerTick(minEl, pad(mins), 'm');
        triggerTick(secEl, pad(secs), 's');
        if (!is24Hour) {
            triggerTick(amPmEl, amPm, 'amPm');
        }

        if (tzLabelEl && !tzLabelEl.dataset.resolved) {
            tzLabelEl.textContent = resolveTimezoneLabel(now);
            tzLabelEl.dataset.resolved = '1';
        }

        const hourDeg = ((h24 % 12) + mins / 60) * 30;
        const minDeg = (mins + secs / 60) * 6;
        if (cumulativeSecDeg === null) {
            cumulativeSecDeg = secs * 6;
        } else if (lastSecVal !== secs) {
            const delta = (secs - lastSecVal + 60) % 60 || 1;
            cumulativeSecDeg += delta * 6;
        }
        lastSecVal = secs;

        if (hourHand) hourHand.style.transform = `rotate(${hourDeg.toFixed(2)}deg)`;
        if (minHand) minHand.style.transform = `rotate(${minDeg.toFixed(2)}deg)`;
        if (secHand) secHand.style.transform = `rotate(${cumulativeSecDeg}deg)`;
    }

    function scheduleTick() {
        const now = new Date();
        renderFrame(now);
        clearTimeout(timerId);
        timerId = setTimeout(scheduleTick, 1000 - now.getMilliseconds() + 8);
    }

    syncFormatButtons();
    scheduleTick();
})();
</script>

<!-- ─── Chart.js Fintech Visualizations ───────────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        return;
    }

    const twelveMonthData = @json($twelveMonthTrends ?? []);
    const sixMonthData = @json($monthlyTrends ?? []);
    const portfolioData = @json($portfolioByType ?? []);

    const isDarkMode = () =>
        document.documentElement.classList.contains('dark') ||
        document.documentElement.getAttribute('data-theme') === 'dark';

    const formatINR = (val) => '₹' + Number(Math.abs(val || 0)).toLocaleString('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    const compactINR = (val) => {
        const sign = val < 0 ? '-' : '';
        const abs = Math.abs(val);
        if (abs >= 10000000) return sign + '₹' + (abs / 10000000).toFixed(1) + 'Cr';
        if (abs >= 100000) return sign + '₹' + (abs / 100000).toFixed(1) + 'L';
        if (abs >= 1000) return sign + '₹' + (abs / 1000).toFixed(0) + 'K';
        return sign + '₹' + abs;
    };

    const getPalette = (dark) => ({
        inflow: dark ? '#10B981' : '#14532D',
        inflowSoft: dark ? 'rgba(16, 185, 129, 0.35)' : 'rgba(20, 83, 45, 0.25)',
        outflow: dark ? '#FB923C' : '#C2410C',
        outflowSoft: dark ? 'rgba(251, 146, 60, 0.32)' : 'rgba(194, 65, 12, 0.24)',
        grid: dark ? '#1E2C24' : '#E2E8E4',
        ticks: dark ? '#94A3B8' : '#64748B',
        tooltipBg: dark ? '#0B1A12' : '#0F172A',
        donutEmpty: dark ? '#1E2C24' : '#E2E8E4',
        donutBorder: dark ? '#111A15' : '#FFFFFF',
        schemeColors: dark
            ? ['#10B981', '#34D399', '#FB923C', '#6EE7B7', '#FBBF24']
            : ['#14532D', '#059669', '#C2410C', '#15803D', '#D97706']
    });

    let currentRange = '12m';
    let currentMode = 'cashflow';
    let cashflowChart = null;
    let donutChart = null;

    const buildCashflowData = (range, mode, p) => {
        const rows = range === '6m' ? sixMonthData : twelveMonthData;
        const labels = rows.length ? rows.map(r => r.short_label || r.label) : ['No Data'];

        if (mode === 'accounts') {
            const counts = rows.length ? rows.map(r => Number(r.accounts || 0)) : [0];
            const maxAcc = Math.max(0, ...counts);
            return {
                labels,
                suggestedMax: maxAcc === 0 ? 10 : undefined,
                suggestedMin: 0,
                datasets: [{
                    label: 'Deposit Accounts Opened',
                    data: counts,
                    backgroundColor: p.inflow,
                    borderRadius: 8,
                    maxBarThickness: 28
                }]
            };
        }

        const inflows = rows.length ? rows.map(r => Number(r.deposits || 0)) : [0];
        const outflows = rows.length ? rows.map(r => -Math.abs(Number(r.outflows || 0))) : [0];
        const maxIn = Math.max(0, ...inflows);
        const maxOut = Math.max(0, ...outflows.map(Math.abs));

        return {
            labels,
            suggestedMax: maxIn === 0 ? 10000 : undefined,
            suggestedMin: maxOut === 0 ? -5000 : undefined,
            datasets: [
                {
                    label: 'Inflows (Deposits)',
                    data: inflows,
                    backgroundColor: inflows.map((v, idx) => idx === inflows.length - 1 || v === maxIn ? p.inflow : p.inflowSoft),
                    hoverBackgroundColor: p.inflow,
                    borderRadius: 7,
                    maxBarThickness: 28
                },
                {
                    label: 'Outflows (Maturities & Comm.)',
                    data: outflows,
                    backgroundColor: outflows.map((v) => Math.abs(v) === maxOut && maxOut > 0 ? p.outflow : p.outflowSoft),
                    hoverBackgroundColor: p.outflow,
                    borderRadius: 7,
                    maxBarThickness: 28
                }
            ]
        };
    };

    const cashCtx = document.getElementById('monthlyTrendsChart');
    if (cashCtx) {
        const p = getPalette(isDarkMode());
        const cfg = buildCashflowData(currentRange, currentMode, p);

        cashflowChart = new Chart(cashCtx, {
            type: 'bar',
            data: { labels: cfg.labels, datasets: cfg.datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: p.tooltipBg,
                        titleColor: '#FFFFFF',
                        bodyColor: '#E2E8F0',
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                if (currentMode === 'accounts') {
                                    return ' ' + ctx.dataset.label + ': ' + (ctx.raw || 0);
                                }
                                return ' ' + ctx.dataset.label + ': ' + formatINR(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { color: p.ticks, font: { size: 11, weight: '500' } }
                    },
                    y: {
                        stacked: true,
                        suggestedMax: cfg.suggestedMax,
                        suggestedMin: cfg.suggestedMin,
                        border: { display: false, dash: [4, 4] },
                        grid: { color: p.grid },
                        ticks: {
                            color: p.ticks,
                            maxTicksLimit: 6,
                            font: { size: 11 },
                            callback: (val) => currentMode === 'accounts' ? val : compactINR(val)
                        }
                    }
                }
            }
        });
    }

    window.updateSuperAdminCashflow = (range, mode) => {
        currentRange = range;
        currentMode = mode;
        if (!cashflowChart) return;
        const p = getPalette(isDarkMode());
        const cfg = buildCashflowData(range, mode, p);
        cashflowChart.data.labels = cfg.labels;
        cashflowChart.data.datasets = cfg.datasets;
        cashflowChart.options.scales.y.suggestedMax = cfg.suggestedMax;
        cashflowChart.options.scales.y.suggestedMin = cfg.suggestedMin;
        cashflowChart.update();
    };

    const donutCtx = document.getElementById('portfolioDonutChart');
    if (donutCtx) {
        const hasPortfolio = portfolioData.some(item => Number(item.amount || 0) > 0);
        const p = getPalette(isDarkMode());

        donutChart = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: hasPortfolio ? portfolioData.map(i => i.label) : ['No Active Deposits'],
                datasets: [{
                    data: hasPortfolio ? portfolioData.map(i => Number(i.amount || 0)) : [1],
                    backgroundColor: hasPortfolio ? portfolioData.map((_, idx) => p.schemeColors[idx % p.schemeColors.length]) : [p.donutEmpty],
                    borderColor: p.donutBorder,
                    borderWidth: 3,
                    hoverOffset: hasPortfolio ? 5 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: hasPortfolio,
                        backgroundColor: p.tooltipBg,
                        callbacks: { label: (ctx) => ' ' + ctx.label + ': ' + formatINR(ctx.raw) }
                    }
                }
            }
        });
    }

    window.addEventListener('theme-changed', function(e) {
        const dark = e.detail ? e.detail.theme === 'dark' : isDarkMode();
        const p = getPalette(dark);

        if (cashflowChart) {
            const cfg = buildCashflowData(currentRange, currentMode, p);
            cashflowChart.data.datasets = cfg.datasets;
            cashflowChart.options.plugins.tooltip.backgroundColor = p.tooltipBg;
            cashflowChart.options.scales.x.ticks.color = p.ticks;
            cashflowChart.options.scales.y.ticks.color = p.ticks;
            cashflowChart.options.scales.y.grid.color = p.grid;
            cashflowChart.update();
        }

        if (donutChart) {
            const hasPortfolio = portfolioData.some(item => Number(item.amount || 0) > 0);
            donutChart.options.plugins.tooltip.backgroundColor = p.tooltipBg;
            donutChart.data.datasets[0].borderColor = p.donutBorder;
            donutChart.data.datasets[0].backgroundColor = hasPortfolio
                ? portfolioData.map((_, idx) => p.schemeColors[idx % p.schemeColors.length])
                : [p.donutEmpty];
            donutChart.update();
        }
    });
});
</script>
@endsection
