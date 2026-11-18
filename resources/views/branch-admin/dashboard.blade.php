@extends('superadmin.layouts.app')

@section('page-title', 'Branch Dashboard')

@section('content')
@include('partials.banking-dashboard-styles')

@php
    $fmt = \App\Services\CurrencyFormatter::class;
    $branchName = auth()->user()->branch->name ?? 'Assigned Branch';
    $branchCode = auth()->user()->branch->code ?? 'BR';

    $goalPct = max(0, min(100, (float) ($mobilizationGoal['pct'] ?? 0)));
    $arcRadius = 86;
    $arcLength = M_PI * $arcRadius;
    $arcFilled = round(($goalPct / 100) * $arcLength, 2);

    $totalSubmittedCapital = max(1.0, (float) $treasury['active_aum'] + (float) $treasury['pending_approval_amount']);
    $activeSharePct = ($treasury['active_aum'] + $treasury['pending_approval_amount']) > 0
        ? round(($treasury['active_aum'] / $totalSubmittedCapital) * 100, 1)
        : 0;
@endphp

<div class="mx-shell">
    <!-- ─── Branch Admin Page Header ───────────────────────────────────────── -->
    <div class="page-header mb-1">
        <div>
            <h1 class="page-title">Branch Operational Dashboard</h1>
            <div class="page-breadcrumb d-flex flex-wrap align-items-center gap-2">
                <span>Branch Admin / {{ $branchName }} / Overview</span>
                @if($currentFy)
                    <span class="mx-fy-badge">
                        <i class="bi bi-calendar3"></i> {{ $currentFy->name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-arrow-up-circle me-1"></i> Career Requests
            </a>
            <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-briefcase me-1"></i> Agent Leads
            </a>
            <a href="{{ route('admin.staff.create') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-person-workspace me-1"></i> Add Staff Officer
            </a>
            <a href="{{ route('branch-admin.notifications') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-bell me-1"></i> Alerts
            </a>
            <a href="{{ route('admin.deposits.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-piggy-bank me-1"></i> Open Deposit Account
            </a>
        </div>
    </div>

    <!-- ─── Main Asymmetric Fintech Grid (8 Cols Workspace + 4 Cols Branch Vault) ─ -->
    <div class="row g-3 g-xl-4 align-items-start">

        <!-- ═══ LEFT COLUMN (8 COLS) ═════════════════════════════════════════ -->
        <div class="col-xl-8 d-flex flex-column gap-3">

            <!-- Row 1: 3 Primary Metric Cards -->
            <div class="row g-3">
                <!-- 1. Total Deposits -->
                <div class="col-md-4">
                    <a href="{{ route('admin.deposits.index') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon"><i class="bi bi-piggy-bank"></i></span>
                                <span>Total Deposits</span>
                            </div>
                            <div class="mx-metric-val">₹{{ $fmt::formatIndianNumber($stats['total_deposits_volume'], 2) }}</div>
                            <div>
                                <span class="mx-trend-pill up">{{ number_format($stats['total_deposits']) }} {{ Str::plural('Account', $stats['total_deposits']) }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- 2. Pending Commission -->
                <div class="col-md-4">
                    <a href="{{ route('admin.commissions.pending') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon copper"><i class="bi bi-hourglass-split"></i></span>
                                <span>Pending Commissions</span>
                            </div>
                            <div class="mx-metric-val">₹{{ $fmt::formatIndianNumber($stats['pending_commission_amount'], 2) }}</div>
                            <div>
                                <span class="mx-trend-pill warn">{{ number_format($stats['pending_commission']) }} Awaiting Authorization</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- 3. Active Advisors & Leads -->
                <div class="col-md-4">
                    <a href="{{ route('admin.agents.index') }}" class="mx-metric-link">
                        <div class="mx-metric-card">
                            <div class="mx-metric-label-row">
                                <span class="mx-metric-mini-icon"><i class="bi bi-people"></i></span>
                                <span>Active Advisors</span>
                            </div>
                            <div class="mx-metric-val">{{ number_format($stats['active_agents']) }}</div>
                            <div>
                                <span class="mx-trend-pill neutral">{{ number_format($stats['total_leads']) }} Business {{ Str::plural('Lead', $stats['total_leads']) }}</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Row 2: Deposit Summary (Donut + Breakdown) & Mobilization Goal (Arc Gauge) -->
            <div class="row g-3">
                <!-- Left: Deposit Summary -->
                <div class="col-md-6">
                    <div class="mx-card h-100">
                        <div class="mx-card-head">
                            <div>
                                <h2 class="mx-card-title">Deposit Portfolio Summary</h2>
                                <p class="mx-card-sub">{{ $branchName }} deposit portfolio distribution</p>
                            </div>
                            <a href="{{ route('admin.deposits.index') }}" class="mx-more-btn" title="Deposit Accounts Registry">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <div class="mx-breakdown-grid">
                            <div class="mx-donut-box">
                                <canvas id="branchPortfolioDonut"></canvas>
                                <div class="mx-donut-center">
                                    <span class="mx-donut-cap">Active AUM</span>
                                    <span class="mx-donut-total">₹{{ $fmt::formatIndianNumber($depositSummary['active_volume'], 0) }}</span>
                                </div>
                            </div>

                            <div class="mx-legend-list">
                                <div class="mx-legend-row">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mx-legend-dot" style="background: var(--mx-series-1);"></span>
                                        <span class="fw-medium" style="color: var(--text-secondary);">Active Contracts ({{ $depositSummary['active_count'] }})</span>
                                    </div>
                                    <span class="fw-bold num-tabular" style="color: var(--text-heading);">₹{{ $fmt::formatIndianNumber($depositSummary['active_volume'], 0) }}</span>
                                </div>
                                <div class="mx-legend-row">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mx-legend-dot" style="background: var(--mx-series-3);"></span>
                                        <span class="fw-medium" style="color: var(--text-secondary);">Pending Authorization ({{ $depositSummary['pending_approval_count'] }})</span>
                                    </div>
                                    <span class="fw-bold num-tabular" style="color: var(--text-heading);">₹{{ $fmt::formatIndianNumber($depositSummary['pending_approval_volume'], 0) }}</span>
                                </div>
                                <div class="mx-legend-row">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="mx-legend-dot" style="background: var(--mx-series-2);"></span>
                                        <span class="fw-medium" style="color: var(--text-secondary);">Matured Contracts ({{ $depositSummary['matured_count'] }})</span>
                                    </div>
                                    <span class="fw-bold num-tabular" style="color: var(--text-heading);">₹{{ $fmt::formatIndianNumber($depositSummary['matured_volume'], 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Mobilization Goal Arc Gauge -->
                <div class="col-md-6">
                    <div class="mx-card h-100">
                        <div class="mx-card-head">
                            <div>
                                <h2 class="mx-card-title">{{ $mobilizationGoal['title'] }}</h2>
                                <p class="mx-card-sub">{{ $currentFy?->name ?? 'Branch Target' }} &bull; {{ $stats['total_leads'] }} Leads (+{{ $stats['new_leads'] }} new)</p>
                            </div>
                            <a href="{{ route('admin.leads.index') }}" class="mx-more-btn" title="Agent Leads">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <div class="mx-gauge-wrap">
                            <div class="mx-gauge-svg-box">
                                <svg width="210" height="114" viewBox="0 0 210 114" aria-label="Branch Mobilization Goal">
                                    <defs>
                                        <linearGradient id="mxBranchGoalGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" class="mx-goal-stop-start" />
                                            <stop offset="55%" class="mx-goal-stop-mid" />
                                            <stop offset="100%" class="mx-goal-stop-end" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M 19 102 A 86 86 0 0 1 191 102"
                                          fill="none"
                                          stroke="var(--border-color)"
                                          stroke-width="16"
                                          stroke-linecap="round"
                                          stroke-dasharray="4 6" />
                                    <path d="M 19 102 A 86 86 0 0 1 191 102"
                                          fill="none"
                                          stroke="url(#mxBranchGoalGrad)"
                                          stroke-width="16"
                                          stroke-linecap="round"
                                          stroke-dasharray="{{ $arcFilled }} {{ $arcLength }}" />
                                </svg>
                                <div class="mx-gauge-overlay">
                                    <div class="mx-gauge-pct">{{ number_format($goalPct, 0) }}%</div>
                                    <div class="mx-gauge-sub">{{ $mobilizationGoal['mode'] === 'fy_target' ? 'Target Achieved' : 'Activated Ratio' }}</div>
                                </div>
                            </div>

                            <div class="mx-goal-stats">
                                <div>
                                    <div style="font-size: 0.68rem; color: var(--text-muted); font-weight: 600;">Mobilized</div>
                                    <div class="fw-bold num-tabular" style="font-size: 0.88rem; color: var(--text-heading);">
                                        ₹{{ $fmt::formatIndianNumber($mobilizationGoal['achieved'], 0) }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size: 0.68rem; color: var(--text-muted); font-weight: 600;">
                                        {{ $mobilizationGoal['mode'] === 'fy_target' ? 'Branch Target' : 'Submitted Pool' }}
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

            <!-- Row 3: Branch Cashflow Chart -->
            <div class="mx-card" x-data="{ range: '12m' }">
                <div class="mx-card-head mb-2">
                    <div>
                        <h2 class="mx-card-title">Branch Deposit Mobilization &amp; Commission Flow</h2>
                        <p class="mx-card-sub">Monthly deposit inflows vs. maturity disbursements &amp; commission outflows</p>
                    </div>
                    <div class="mx-seg-group">
                        <button type="button" class="mx-seg-btn" :class="{ 'active': range === '6m' }"
                                @click="range = '6m'; window.updateBranchCashflow && window.updateBranchCashflow('6m')">6 Months</button>
                        <button type="button" class="mx-seg-btn" :class="{ 'active': range === '12m' }"
                                @click="range = '12m'; window.updateBranchCashflow && window.updateBranchCashflow('12m')">12 Months</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-2 small" style="color: var(--text-muted);">
                    <span class="d-inline-flex align-items-center gap-1">
                        <span style="width:9px;height:9px;border-radius:50%;background:var(--color-primary);display:inline-block;"></span>
                        Inflows (Deposit Receipts)
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <span style="width:9px;height:9px;border-radius:50%;background:var(--color-copper);display:inline-block;"></span>
                        Outflows (Maturities &amp; Commissions)
                    </span>
                </div>

                <div style="height: 245px; position: relative;">
                    <canvas id="branchTrendsChart"></canvas>
                </div>
            </div>

            <!-- Row 4: Agent Performance & Recent Deposits Tabbed Table -->
            <div class="mx-card p-0 overflow-hidden" x-data="{ tab: 'agents' }">
                <div class="mx-card-head p-3 px-4 mb-0 border-bottom" style="border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 class="mx-card-title">Branch Field Performance</h3>
                        <div class="mx-seg-group ms-1">
                            <button type="button" class="mx-seg-btn" :class="{ 'active': tab === 'agents' }" @click="tab = 'agents'">Field Advisors</button>
                            <button type="button" class="mx-seg-btn" :class="{ 'active': tab === 'deposits' }" @click="tab = 'deposits'">Recent Deposits</button>
                        </div>
                    </div>
                    <a href="{{ route('admin.agents.index') }}" class="mx-pill-btn brand py-1 px-3" style="font-size: 0.72rem;" x-show="tab === 'agents'">Advisor Directory</a>
                    <a href="{{ route('admin.deposits.index') }}" class="mx-pill-btn brand py-1 px-3" style="font-size: 0.72rem; display: none;" x-show="tab === 'deposits'">Deposit Ledger</a>
                </div>

                <!-- Agent Performance Tab -->
                <div x-show="tab === 'agents'">
                    <div class="table-responsive border-0 shadow-none">
                        <table class="mx-table">
                            <thead>
                                <tr>
                                    <th>Field Advisor</th>
                                    <th>Rank / Grade</th>
                                    <th>Account Holders</th>
                                    <th>Active Deposit AUM</th>
                                    <th class="text-end">Disbursed Comm.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agentPerformance as $ag)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.agents.show', $ag) }}" class="fw-bold text-decoration-none" style="color: var(--text-heading);">
                                                {{ $ag->name }}
                                            </a>
                                            <div class="font-monospace" style="font-size:0.72rem; color: var(--text-muted);">{{ $ag->agent_id }}</div>
                                        </td>
                                        <td><span class="mx-trend-pill neutral">{{ $ag->rank?->name ?? 'Advisor' }}</span></td>
                                        <td class="num-tabular">{{ $ag->active_customers_count }}</td>
                                        <td>
                                            <div class="fw-bold num-tabular" style="color: var(--text-heading);">₹{{ $fmt::formatIndianNumber((float) ($ag->active_deposits_sum ?? 0), 2) }}</div>
                                            <div style="font-size:0.72rem; color: var(--text-muted);">{{ $ag->active_deposits_count }} {{ Str::plural('account', $ag->active_deposits_count) }}</div>
                                        </td>
                                        <td class="text-end fw-bold num-tabular" style="color: var(--color-primary);">
                                            ₹{{ $fmt::formatIndianNumber((float) ($ag->paid_commissions_sum ?? 0), 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color: var(--text-muted);">
                                            No field advisors active in this branch yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Deposits Tab -->
                <div x-show="tab === 'deposits'" style="display: none;">
                    <div class="table-responsive border-0 shadow-none">
                        <table class="mx-table">
                            <thead>
                                <tr>
                                    <th>Account No. / Account Holder</th>
                                    <th>Deposit Product</th>
                                    <th class="text-end">Principal Amount (₹)</th>
                                    <th>Account Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDeposits as $dep)
                                    <tr>
                                        <td>
                                            <span class="mx-code">{{ $dep->deposit_number }}</span>
                                            <div class="small fw-semibold mt-1" style="color: var(--text-heading);">{{ $dep->customer?->name ?? 'Account Holder' }}</div>
                                        </td>
                                        <td><span class="mx-trend-pill neutral">{{ $dep->deposit_type ?? 'FD' }}</span></td>
                                        <td class="text-end fw-bold num-tabular" style="color: var(--text-heading);">₹{{ $fmt::formatIndianNumber((float) $dep->amount, 2) }}</td>
                                        <td>
                                            @if($dep->status === 'ACTIVE')
                                                <span class="badge-active">Active</span>
                                            @elseif($dep->status === 'PENDING_APPROVAL')
                                                <span class="badge-pending">Pending Authorization</span>
                                            @elseif($dep->status === 'MATURED')
                                                <span class="badge-matured">Matured</span>
                                            @else
                                                <span class="badge-inactive">{{ $dep->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.deposits.show', $dep) }}" class="mx-pill-btn ghost py-1 px-2" style="font-size: 0.7rem;">View Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color: var(--text-muted);">No deposit accounts recorded in this branch yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ RIGHT RAIL (4 COLS) ══════════════════════════════════════════ -->
        <div class="col-xl-4 d-flex flex-column gap-3">

            <!-- Right Card 1: Branch Vault Summary -->
            <div class="mx-card">
                <div class="mx-vault-card mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold" style="font-size: 0.92rem;">{{ $branchName }} Vault</span>
                        <span class="mx-vault-chip"><i class="bi bi-building"></i> {{ $branchCode }}</span>
                    </div>

                    <div class="my-2">
                        <div style="font-size: 0.7rem; opacity: 0.85;">Branch Mobilized AUM</div>
                        <div class="mx-vault-amount">₹{{ $fmt::formatIndianNumber($treasury['active_aum'], 2) }}</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between" style="font-size: 0.73rem; opacity: 0.92;">
                        <span>Weighted Yield: <strong>{{ number_format($treasury['weighted_avg_rate'], 2) }}% p.a.</strong></span>
                        <span>{{ $depositSummary['active_count'] }} Active {{ Str::plural('Contract', $depositSummary['active_count']) }}</span>
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
                    <span>Pending Authorization: ₹{{ $fmt::formatIndianNumber($depositSummary['pending_approval_volume'], 0) }}</span>
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

            <!-- Right Card 2: Active Agents Strip -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Active Field Advisors</h3>
                        <p class="mx-card-sub">{{ number_format($stats['active_agents']) }} active {{ Str::plural('advisor', $stats['active_agents']) }} assigned to {{ $branchName }}</p>
                    </div>
                    <a href="{{ route('admin.agents.index') }}" class="mx-more-btn" title="View Directory">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                @if($agentPerformance->isNotEmpty())
                    <div class="mx-advisor-strip mb-3">
                        @foreach($agentPerformance as $ag)
                            <a href="{{ route('admin.agents.show', $ag) }}" class="mx-advisor-chip" title="{{ $ag->name }}">
                                <div class="mx-advisor-avatar">
                                    @if($ag->profile_image_url)
                                        <img src="{{ $ag->profile_image_url }}" alt="{{ $ag->name }}" class="w-100 h-100 object-fit-cover bg-white" onerror="this.style.display='none'; this.parentElement.innerText='{{ strtoupper(substr($ag->name, 0, 2)) }}';">
                                    @else
                                        {{ strtoupper(substr($ag->name, 0, 2)) }}
                                    @endif
                                </div>
                                <span class="mx-advisor-name">{{ ucfirst(explode(' ', trim($ag->name))[0]) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="small text-center py-2 mb-2" style="color: var(--text-muted);">
                        No active advisors assigned yet.
                    </div>
                @endif

                <a href="{{ route('admin.agents.index') }}" class="mx-pill-btn brand w-100">
                    <i class="bi bi-people"></i> Open Field Advisor Directory
                </a>
            </div>

            <!-- Right Card 3: Upcoming Maturities & Commissions (2x2 Mini Cards) -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Upcoming Maturities</h3>
                        <p class="mx-card-sub">Upcoming maturity disbursements &amp; payable commissions</p>
                    </div>
                    <a href="{{ route('admin.deposits.index', ['status' => 'ACTIVE']) }}" class="mx-more-btn" title="Deposit Accounts Registry">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="mx-mini-grid">
                    <a href="{{ route('admin.deposits.index', ['status' => 'ACTIVE']) }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon"><i class="bi bi-calendar-event"></i></span>
                            <span class="mx-mini-sub">{{ $stats['upcoming_maturities'] }} {{ Str::plural('account', $stats['upcoming_maturities']) }}</span>
                        </div>
                        <div class="mx-mini-title">Next 30 Days</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($stats['upcoming_maturities_amount'], 0) }}</div>
                    </a>

                    <a href="{{ route('admin.deposits.index', ['status' => 'ACTIVE']) }}" class="mx-mini-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="mx-mini-icon green"><i class="bi bi-calendar3"></i></span>
                            <span class="mx-mini-sub">{{ $maturityHorizon['days_60']['count'] + $maturityHorizon['days_90']['count'] }} {{ Str::plural('account', $maturityHorizon['days_60']['count'] + $maturityHorizon['days_90']['count']) }}</span>
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
                            <span class="mx-mini-sub">{{ $commissionPipeline['paid_count'] }} paid</span>
                        </div>
                        <div class="mx-mini-title">Paid Comm.</div>
                        <div class="mx-mini-val">₹{{ $fmt::formatIndianNumber($commissionPipeline['paid_amount'], 0) }}</div>
                    </a>
                </div>
            </div>

            <!-- Right Card 4: Recent Branch Activity -->
            <div class="mx-card">
                <div class="mx-card-head mb-2">
                    <div>
                        <h3 class="mx-card-title">Recent Branch Activity</h3>
                        <p class="mx-card-sub">{{ $branchName }} activity log</p>
                    </div>
                </div>

                <div class="mx-activity-list">
                    @forelse($recentActivity as $idx => $log)
                        <div class="mx-activity-item">
                            <span class="mx-activity-dot {{ $idx % 2 === 0 ? 'brand' : '' }}">
                                <i class="bi {{ $idx % 2 === 0 ? 'bi-shield-check' : 'bi-clock-history' }}"></i>
                            </span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="small fw-semibold" style="color: var(--text-heading); line-height: 1.35;">
                                    {{ Str::limit($log->description, 68) }}
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mt-1" style="font-size: 0.7rem; color: var(--text-muted);">
                                    <span>{{ $log->user_name ?? ($log->user?->name ?? 'System') }}</span>
                                    <span>{{ $log->created_at?->diffForHumans(short: true) ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="small text-center py-3" style="color: var(--text-muted);">
                            No recent activity recorded for this branch.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

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
            ? ['#10B981', '#FB923C', '#34D399', '#6EE7B7', '#FBBF24']
            : ['#14532D', '#C2410C', '#059669', '#15803D', '#D97706']
    });

    let currentRange = '12m';
    let cashflowChart = null;
    let donutChart = null;

    const buildSeries = (range, p) => {
        const rows = range === '6m' ? sixMonthData : twelveMonthData;
        const labels = rows.length ? rows.map(r => r.short_label || r.label) : ['No Data'];
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
                    label: 'Inflows',
                    data: inflows,
                    backgroundColor: inflows.map((v, idx) => idx === inflows.length - 1 || v === maxIn ? p.inflow : p.inflowSoft),
                    hoverBackgroundColor: p.inflow,
                    borderRadius: 7,
                    maxBarThickness: 28
                },
                {
                    label: 'Outflows',
                    data: outflows,
                    backgroundColor: outflows.map((v) => Math.abs(v) === maxOut && maxOut > 0 ? p.outflow : p.outflowSoft),
                    hoverBackgroundColor: p.outflow,
                    borderRadius: 7,
                    maxBarThickness: 28
                }
            ]
        };
    };

    const trendCtx = document.getElementById('branchTrendsChart');
    if (trendCtx) {
        const p = getPalette(isDarkMode());
        const cfg = buildSeries(currentRange, p);

        cashflowChart = new Chart(trendCtx, {
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
                        callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': ' + formatINR(ctx.raw) }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: p.ticks, font: { size: 11 } } },
                    y: {
                        stacked: true,
                        suggestedMax: cfg.suggestedMax,
                        suggestedMin: cfg.suggestedMin,
                        border: { display: false, dash: [4, 4] },
                        grid: { color: p.grid },
                        ticks: { color: p.ticks, callback: compactINR }
                    }
                }
            }
        });
    }

    window.updateBranchCashflow = (range) => {
        currentRange = range;
        if (!cashflowChart) return;
        const p = getPalette(isDarkMode());
        const cfg = buildSeries(range, p);
        cashflowChart.data.labels = cfg.labels;
        cashflowChart.data.datasets = cfg.datasets;
        cashflowChart.options.scales.y.suggestedMax = cfg.suggestedMax;
        cashflowChart.options.scales.y.suggestedMin = cfg.suggestedMin;
        cashflowChart.update();
    };

    const donutCtx = document.getElementById('branchPortfolioDonut');
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
                    borderWidth: 3
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
            const cfg = buildSeries(currentRange, p);
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
