@extends('superadmin.layouts.app')

@section('page-title', 'Commissions Management')

@section('content')
<style>
    /* ─── Enterprise Swiss Design System: Commissions ───────── */
    .comm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .comm-kpi-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 108px;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.15s ease, box-shadow 0.15s ease;
        text-decoration: none !important;
        color: inherit;
        position: relative;
        box-shadow: var(--shadow-card);
    }
    .comm-kpi-card:hover {
        border-color: var(--color-primary, #14532D);
        background: var(--card-bg, #ffffff);
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover, 0 6px 16px -2px rgba(20, 83, 45, 0.1));
        color: inherit;
    }
    .comm-kpi-card.is-active {
        border-color: var(--color-primary, #14532D);
        border-width: 2px;
        background: var(--bs-primary-bg-subtle, #ECFDF5);
    }

    .comm-kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .comm-kpi-label {
        font-size: 0.76rem;
        font-weight: 500;
        color: var(--text-muted, #64748b);
        letter-spacing: -0.01em;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .comm-kpi-dot {
        width: 7px;
        height: 7px;
        border-radius: 50% !important;
        display: inline-block;
        flex-shrink: 0;
    }
    .comm-kpi-dot.dot-total { background: #64748b; }
    .comm-kpi-dot.dot-pending { background: #d97706; }
    .comm-kpi-dot.dot-approved { background: #2563eb; }
    .comm-kpi-dot.dot-payable { background: #059669; }
    .comm-kpi-dot.dot-paid { background: #047857; }

    .comm-kpi-icon-pill {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: var(--text-muted, #64748b);
        background: var(--surface-subtle, #f8fafc);
        border: 1px solid var(--border-color, #e2e8f0);
    }
    .comm-kpi-card:hover .comm-kpi-icon-pill {
        color: var(--color-primary, #14532D);
        background: var(--card-bg, #ffffff);
        border-color: var(--color-primary, #14532D);
    }
    .comm-kpi-card.is-active .comm-kpi-icon-pill {
        color: #ffffff;
        background: var(--color-primary, #14532D);
        border-color: var(--color-primary, #14532D);
    }

    .comm-kpi-amount {
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--text-heading, #0f172a);
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
        font-feature-settings: 'tnum' 1;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        margin: 4px 0 0 0;
    }
    .comm-kpi-amount .currency-symbol {
        font-size: 1.15rem;
        font-weight: 500;
        color: #64748b;
        margin-right: 1px;
    }

    .comm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.72rem;
        color: #94a3b8;
        padding-top: 4px;
        margin-top: auto;
    }
    .comm-kpi-footer .kpi-sub-text {
        color: #64748b;
        font-weight: 400;
    }
    .comm-kpi-arrow {
        font-size: 0.7rem;
        color: #94a3b8;
        transition: transform 0.15s ease, color 0.15s ease;
    }
    .comm-kpi-card:hover .comm-kpi-arrow {
        transform: translateX(2px);
        color: var(--color-primary, #14532D);
    }

    /* ─── Status Segmented Navigation ────────────────────────────── */
    .status-nav-container {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 2px;
        scrollbar-width: thin;
    }
    .status-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px !important;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--text-muted, #475569);
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        text-decoration: none;
        transition: all 0.15s ease;
        white-space: nowrap;
        box-shadow: var(--shadow-subtle, 0 1px 2px rgba(0, 0, 0, 0.03));
    }
    .status-pill-btn:hover {
        background: var(--surface-subtle, #f8fafc);
        color: var(--text-heading, #0f172a);
        border-color: var(--border-strong, #cbd5e1);
    }
    .status-pill-btn.active {
        background: var(--color-primary, #14532D);
        color: #ffffff;
        border-color: var(--color-primary, #14532D);
    }
    .status-pill-btn .pill-count {
        font-size: 0.68rem;
        padding: 2px 7px;
        border-radius: 9999px !important;
        background: var(--surface-subtle, rgba(0, 0, 0, 0.05));
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .status-pill-btn.active .pill-count {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
    }

    /* ─── Minimal Filter Bar ─────────────────────────────────────── */
    .filter-card {
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px !important;
        background: var(--card-bg, #ffffff);
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.03)) !important;
    }
    .search-input-group {
        position: relative;
        min-width: 200px;
    }
    .search-input-group .search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-subtle, #94a3b8);
        font-size: 0.8rem;
        pointer-events: none;
    }
    .search-input-group .form-control {
        padding-left: 32px;
        border-color: var(--border-color, #e2e8f0);
        border-radius: 8px !important;
        font-size: 0.8rem;
        background: var(--card-bg, #ffffff);
        color: var(--text-body, #1e293b);
    }
    .search-input-group .form-control:focus {
        border-color: var(--color-primary, #14532D);
        background: var(--card-bg, #ffffff);
        box-shadow: 0 0 0 3px var(--color-primary-ring, rgba(20, 83, 45, 0.16)) !important;
    }
    .filter-select {
        border-color: var(--border-color, #e2e8f0);
        border-radius: 8px !important;
        font-size: 0.8rem;
        color: var(--text-body, #334155);
        background-color: var(--card-bg, #ffffff);
        padding-left: 11px;
        padding-right: 26px;
    }
    .filter-select:focus {
        border-color: var(--color-primary, #14532D);
        box-shadow: 0 0 0 3px var(--color-primary-ring, rgba(20, 83, 45, 0.16)) !important;
    }

    /* ─── Minimal Modern Table Card ──────────────────────────────── */
    .comm-table-card {
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px !important;
        background: var(--card-bg, #ffffff);
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.04)) !important;
        overflow: hidden;
    }
    .comm-table-card .table {
        margin-bottom: 0;
    }
    .comm-table-card .table th {
        background: var(--surface-subtle, #f8fafc);
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted, #475569);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding: 10px 14px;
        white-space: nowrap;
    }
    .comm-table-card .table td {
        padding: 10px 14px;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
        font-size: 0.835rem;
    }

    /* Master Deposit Row (Minimal Clean Accent) */
    .deposit-parent-row {
        background-color: var(--surface-subtle, #f8fafc);
        border-top: 1px solid var(--border-color, #e2e8f0);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        border-left: 3px solid var(--color-primary, #14532D);
        transition: background-color 0.12s ease;
    }
    .deposit-parent-row:hover {
        background-color: var(--surface-elevated, #f1f5f9);
    }

    /* Child Annual Payout Schedule Row */
    .child-commission-row {
        background-color: var(--card-bg, #ffffff);
        border-left: 3px solid var(--border-color, #e2e8f0);
        transition: background-color 0.12s ease;
    }
    .child-commission-row:hover {
        background-color: var(--surface-subtle, #fafbfc);
    }
    .child-commission-row.is-collapsed {
        display: none !important;
    }

    /* Minimal CSS Tree Angle Connector */
    .schedule-indent-marker {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-left: 1.5px solid var(--border-strong, #cbd5e1);
        border-bottom: 1.5px solid var(--border-strong, #cbd5e1);
        margin-right: 6px;
        margin-top: -3px;
        vertical-align: middle;
    }

    /* Toggle schedule chevron button */
    .toggle-schedule-btn {
        width: 22px;
        height: 22px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.7rem;
        color: var(--text-muted, #64748b);
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        box-shadow: var(--shadow-subtle, 0 1px 2px rgba(0, 0, 0, 0.02));
    }
    .toggle-schedule-btn:hover {
        background: var(--surface-subtle, #f1f5f9);
        color: var(--text-heading, #0f172a);
        border-color: var(--border-strong, #cbd5e1);
    }
    .toggle-schedule-btn.is-collapsed .toggle-icon {
        transform: rotate(180deg);
    }
    .toggle-icon {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-block;
    }

    /* Badges & Micro Elements */
    .year-badge {
        font-size: 0.68rem;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
        letter-spacing: 0.01em;
        display: inline-block;
        font-variant-numeric: tabular-nums;
    }
    html.dark .year-badge {
        background: rgba(59, 130, 246, 0.16);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.32);
    }
    .btn-action-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        color: var(--text-muted, #64748b);
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        text-decoration: none !important;
        box-shadow: var(--shadow-subtle, 0 1px 2px rgba(0, 0, 0, 0.02));
        transition: all 0.15s ease;
    }
    .btn-action-icon:hover {
        background: var(--surface-subtle, #f8fafc);
        color: var(--text-heading, #0f172a);
        border-color: var(--border-strong, #cbd5e1);
    }
    .btn-action-pay {
        color: #ffffff !important;
        background: var(--color-primary, #14532D) !important;
        border-color: var(--color-primary, #14532D) !important;
    }
    .btn-action-pay:hover {
        background: var(--color-primary-hover, #0F3F22) !important;
        border-color: var(--color-primary-hover, #0F3F22) !important;
        color: #ffffff !important;
    }

    .comm-code-link {
        color: var(--text-heading, #0f172a);
        font-weight: 500;
        text-decoration: none;
    }
    .comm-code-link:hover {
        color: var(--color-primary, #14532D) !important;
    }
</style>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="page-title mb-0">Commissions</h1>
            <span class="badge bg-white text-secondary border px-2.5 py-1 num-tabular" style="font-size: 0.74rem; font-weight: 600;">
                <i class="bi bi-collection me-1 text-primary"></i>{{ number_format($commissions->total()) }} Records
            </span>
        </div>
        <div class="page-breadcrumb text-muted mt-1">Commission / Ledger & Multi-Year Payout Schedules</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('admin.commissions.overview') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-speedometer2"></i>
            <span>Overview</span>
        </a>
        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-percent"></i>
            <span>Deposit Commissions</span>
        </a>
        @endif
        <a href="{{ route('admin.commissions.history') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-clock-history"></i>
            <span>Audit History</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 py-2 px-3 small border" role="alert" style="background-color: #f0fdf4; color: #166534; border-left: 3px solid #16a34a !important;">
    <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-2 fs-6 text-success"></i>
        <span>{{ session('success') }}</span>
    </div>
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 py-2 px-3 small border" role="alert" style="border-left: 3px solid #dc2626 !important;">
    <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-6 text-danger"></i>
        <span>{{ session('error') }}</span>
    </div>
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $currentStatus = strtoupper((string) request('status', ''));
@endphp

<!-- Minimal Modern KPI Metric Grid -->
<div class="comm-kpi-grid">
    <!-- Total Volume -->
    <a href="{{ route('admin.commissions.index', request()->except(['page', 'status'])) }}"
       class="comm-kpi-card {{ $currentStatus === '' ? 'is-active' : '' }}" title="View all commission records">
        <div class="comm-kpi-header">
            <span class="comm-kpi-label">
                <span class="comm-kpi-dot dot-total"></span>
                <span>Total Commission Pool</span>
            </span>
            <span class="comm-kpi-icon-pill">
                <i class="bi bi-layers"></i>
            </span>
        </div>
        <div class="comm-kpi-amount">
            <span class="currency-symbol">₹</span>{{ number_format($kpis['total_amount'] ?? 0, 2) }}
        </div>
        <div class="comm-kpi-footer">
            <span class="kpi-sub-text">{{ number_format($kpis['total_count'] ?? 0) }} total lifetime records</span>
            <i class="bi bi-arrow-right comm-kpi-arrow"></i>
        </div>
    </a>

    <!-- Pending Review -->
    <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PENDING'])) }}"
       class="comm-kpi-card {{ $currentStatus === 'PENDING' ? 'is-active' : '' }}" title="View pending review commissions">
        <div class="comm-kpi-header">
            <span class="comm-kpi-label">
                <span class="comm-kpi-dot dot-pending"></span>
                <span>Pending Review</span>
            </span>
            <span class="comm-kpi-icon-pill bg-warning-subtle text-warning border-warning-subtle">
                <i class="bi bi-hourglass-split"></i>
            </span>
        </div>
        <div class="comm-kpi-amount">
            <span class="currency-symbol">₹</span>{{ number_format($kpis['pending_amount'] ?? 0, 2) }}
        </div>
        <div class="comm-kpi-footer">
            <span class="kpi-sub-text">{{ number_format($kpis['pending_count'] ?? 0) }} payouts awaiting sign-off</span>
            <i class="bi bi-arrow-right comm-kpi-arrow"></i>
        </div>
    </a>

    <!-- Ready for Payout (Payable) -->
    <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PAYABLE'])) }}"
       class="comm-kpi-card {{ $currentStatus === 'PAYABLE' ? 'is-active' : '' }}" title="View payable commissions">
        <div class="comm-kpi-header">
            <span class="comm-kpi-label">
                <span class="comm-kpi-dot dot-payable"></span>
                <span>Ready For Payout</span>
            </span>
            <span class="comm-kpi-icon-pill bg-success-subtle text-success border-success-subtle">
                <i class="bi bi-wallet2"></i>
            </span>
        </div>
        <div class="comm-kpi-amount">
            <span class="currency-symbol">₹</span>{{ number_format($kpis['payable_amount'] ?? 0, 2) }}
        </div>
        <div class="comm-kpi-footer">
            <span class="kpi-sub-text">{{ number_format($kpis['payable_count'] ?? 0) }} approved & payable</span>
            <i class="bi bi-arrow-right comm-kpi-arrow"></i>
        </div>
    </a>

    <!-- Disbursed (Paid) -->
    <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PAID'])) }}"
       class="comm-kpi-card {{ $currentStatus === 'PAID' ? 'is-active' : '' }}" title="View disbursed commissions">
        <div class="comm-kpi-header">
            <span class="comm-kpi-label">
                <span class="comm-kpi-dot dot-paid"></span>
                <span>Settled & Paid</span>
            </span>
            <span class="comm-kpi-icon-pill" style="color: #0284c7; background: #f0f9ff; border-color: #bae6fd;">
                <i class="bi bi-check2-circle"></i>
            </span>
        </div>
        <div class="comm-kpi-amount">
            <span class="currency-symbol">₹</span>{{ number_format($kpis['paid_amount'] ?? 0, 2) }}
        </div>
        <div class="comm-kpi-footer">
            <span class="kpi-sub-text">{{ number_format($kpis['paid_count'] ?? 0) }} successfully disbursed</span>
            <i class="bi bi-arrow-right comm-kpi-arrow"></i>
        </div>
    </a>
</div>

<!-- Status Segmented Navigation Tabs & Quick Collapse -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="status-nav-container">
        <a href="{{ route('admin.commissions.index', request()->except(['page', 'status'])) }}"
           class="status-pill-btn {{ $currentStatus === '' ? 'active' : '' }}">
            <span>All Records</span>
            <span class="pill-count">{{ $kpis['total_count'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PENDING'])) }}"
           class="status-pill-btn {{ $currentStatus === 'PENDING' ? 'active' : '' }}">
            <span class="comm-kpi-dot dot-pending"></span>
            <span>Pending</span>
            <span class="pill-count">{{ $kpis['pending_count'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'APPROVED'])) }}"
           class="status-pill-btn {{ $currentStatus === 'APPROVED' ? 'active' : '' }}">
            <span class="comm-kpi-dot dot-approved"></span>
            <span>Approved</span>
            <span class="pill-count">{{ $kpis['approved_count'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PAYABLE'])) }}"
           class="status-pill-btn {{ $currentStatus === 'PAYABLE' ? 'active' : '' }}">
            <span class="comm-kpi-dot dot-payable"></span>
            <span>Payable</span>
            <span class="pill-count">{{ $kpis['payable_count'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'PAID'])) }}"
           class="status-pill-btn {{ $currentStatus === 'PAID' ? 'active' : '' }}">
            <span class="comm-kpi-dot dot-paid"></span>
            <span>Paid</span>
            <span class="pill-count">{{ $kpis['paid_count'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->except(['page', 'status']), ['status' => 'REJECTED'])) }}"
           class="status-pill-btn {{ $currentStatus === 'REJECTED' ? 'active' : '' }}">
            <span class="comm-kpi-dot" style="background: #ef4444;"></span>
            <span>Rejected</span>
            <span class="pill-count">{{ $kpis['rejected_count'] ?? 0 }}</span>
        </a>
    </div>

    <!-- Toggle All Details -->
    <div>
        <button type="button" id="toggleAllSchedulesBtn" class="btn btn-outline-secondary btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1.5" style="font-size: 0.76rem; border-radius: 20px;">
            <i class="bi bi-arrows-collapse" id="toggleAllIcon"></i> <span id="toggleAllText">Collapse All</span>
        </button>
    </div>
</div>

<!-- Compact Filters Toolbar -->
<div class="filter-card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.commissions.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <!-- Search input with icon -->
            <div class="search-input-group flex-grow-1" style="min-width: 200px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search COM#, Deposit#, Agent, Customer…" value="{{ request('search') }}">
            </div>

            @if(is_null($restrictedBranchId))
            <div style="min-width: 135px;">
                <select name="branch_id" class="form-select form-select-sm filter-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(is_null($restrictedAgentId))
            <div style="min-width: 135px;">
                <select name="agent_id" class="form-select form-select-sm filter-select">
                    <option value="">All Agents</option>
                    @foreach($agents as $a)
                        <option value="{{ $a->id }}" {{ request('agent_id') == $a->id ? 'selected' : '' }}>
                            {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width: 135px;">
                <select name="scheme_id" class="form-select form-select-sm filter-select">
                    <option value="">All Schemes</option>
                    @foreach($schemes as $sch)
                        <option value="{{ $sch->id }}" {{ request('scheme_id') == $sch->id ? 'selected' : '' }}>
                            {{ $sch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 115px;">
                <select name="period" class="form-select form-select-sm filter-select">
                    <option value="">All Periods</option>
                    @foreach($periods as $p)
                        <option value="{{ $p }}" {{ request('period') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex gap-1.5 align-items-center">
                <button type="submit" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-funnel"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['search', 'branch_id', 'agent_id', 'scheme_id', 'status', 'period']))
                    <a href="{{ route('admin.commissions.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear All Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Minimal Modern Commissions Table -->
<div class="comm-table-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-3" style="min-width: 160px;">Reference / Year</th>
                    <th style="min-width: 110px;">Date & Time</th>
                    <th style="min-width: 130px;">Customer</th>
                    <th style="min-width: 130px;">Agent</th>
                    <th style="min-width: 145px;">Scheme & Tenure</th>
                    <th class="text-end" style="min-width: 95px;">Base (₹)</th>
                    <th class="text-center" style="min-width: 65px;">Rate</th>
                    <th class="text-end" style="min-width: 110px;">Commission (₹)</th>
                    <th class="text-center" style="min-width: 90px;">Status</th>
                    <th class="text-end pe-3" style="min-width: 75px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedCommissions = $commissions->groupBy(function($comm) {
                        return $comm->deposit_id ? 'dep_' . $comm->deposit_id : 'standalone_' . $comm->id;
                    });
                @endphp

                @forelse($groupedCommissions as $groupKey => $groupItems)
                    @php
                        $firstComm = $groupItems->first();
                        $deposit = $firstComm->deposit;
                        $customer = $firstComm->customer ?? $deposit?->customer;
                        $agent = $firstComm->agent ?? $deposit?->agent;
                        $branch = $firstComm->branch ?? $deposit?->branch;
                        $scheme = $deposit?->scheme ?? $firstComm->scheme;
                        $tenureYears = $deposit ? $deposit->getTenureInYears() : ($groupItems->max('payout_year') ?: 1);
                        $depositAmount = $deposit ? $deposit->amount : $firstComm->base_amount;
                        $activeCommissions = $groupItems->filter(fn($c) => strtoupper((string) $c->status) !== 'REJECTED');
                        $totalCommission = $activeCommissions->isNotEmpty() ? $activeCommissions->sum('calculated_amount') : $groupItems->sum('calculated_amount');
                    @endphp

                    @if($deposit && $groupItems->count() > 1)
                        {{-- Multi-Year Deposit Group: Master Summary Row + Annual Payout Breakdown Rows --}}
                        <tr class="deposit-parent-row" id="parent-row-dep-{{ $deposit->id }}">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-1.5">
                                    <button type="button" class="toggle-schedule-btn me-1"
                                            data-target="dep-schedule-{{ $deposit->id }}" title="Toggle Annual Breakdown">
                                        <i class="bi bi-chevron-up toggle-icon"></i>
                                    </button>
                                    <a href="{{ route('admin.deposits.show', $deposit) }}" class="fw-bold font-monospace text-dark text-decoration-none" style="font-size: 0.85rem;" title="View Deposit Details">
                                        <i class="bi bi-shield-check text-success me-1"></i>{{ $deposit->deposit_number }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-dark fw-medium">{{ ($deposit->created_at ?? $firstComm->created_at)->format('d M Y') }}</span>
                                    <span class="text-muted ms-1" style="font-size: 0.72rem;">{{ ($deposit->created_at ?? $firstComm->created_at)->format('h:i A') }}</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark d-block text-truncate" style="max-width: 140px;">{{ $customer->name ?? 'N/A' }}</span>
                                    @if($customer && $customer->customer_number)
                                        <span class="text-muted font-monospace d-block" style="font-size: 0.7rem;">{{ $customer->customer_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark d-block text-truncate" style="max-width: 140px;">{{ $agent->name ?? 'N/A' }}</span>
                                    @if($agent && $agent->member_code)
                                        <span class="text-muted font-monospace d-block" style="font-size: 0.7rem;">{{ $agent->member_code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark">{{ $scheme->name ?? 'Deposit' }}</span>
                                    <span class="text-muted small d-block" style="font-size: 0.72rem;">{{ $tenureYears }} Yr(s) Tenure</span>
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-dark font-monospace">
                                ₹{{ number_format($depositAmount, 2) }}
                            </td>
                            <td class="text-center text-muted small">
                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.7rem;">{{ $groupItems->count() }} Year(s)</span>
                            </td>
                            <td class="text-end fw-bold font-monospace" style="font-size: 0.9rem; color: var(--color-primary-green);">
                                ₹{{ number_format($totalCommission, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-white text-secondary border" style="font-size: 0.7rem; font-weight: 500;">Tenure Total</span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.deposits.show', $deposit) }}" class="btn-action-icon" title="View Deposit Details">
                                    <i class="bi bi-arrow-right-short" style="font-size: 1rem;"></i>
                                </a>
                            </td>
                        </tr>

                        {{-- Separate Commission per Year Rows Underneath the Main Deposit Row --}}
                        @foreach($groupItems->sortBy('payout_year') as $comm)
                        <tr class="child-commission-row dep-schedule-{{ $deposit->id }}">
                            <td class="ps-3 font-monospace">
                                <div class="d-flex align-items-center">
                                    <span class="schedule-indent-marker"></span>
                                    <span class="year-badge me-1.5">
                                        Year {{ $comm->payout_year ?? 1 }}
                                    </span>
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="comm-code-link small">
                                        {{ $comm->commission_number }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted font-monospace small"><i class="bi bi-calendar3 me-1 text-secondary opacity-75" style="font-size: 0.72rem;"></i>{{ $comm->period }}</span>
                            </td>
                            <td>
                                <span class="text-muted small text-truncate d-block" style="max-width: 130px;">&bull; {{ $customer->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small text-truncate d-block" style="max-width: 130px;">&bull; {{ $agent->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small text-truncate d-block" style="max-width: 140px;">{{ $comm->rule->name ?? 'Scheme Rate' }}</span>
                            </td>
                            <td class="text-end text-muted small font-monospace">
                                ₹{{ number_format($comm->base_amount, 2) }}
                            </td>
                            <td class="text-center font-monospace small text-muted">
                                {{ number_format((float)$comm->rate, 2) }}%
                            </td>
                            <td class="text-end fw-semibold font-monospace {{ strtoupper((string) $comm->status) === 'REJECTED' ? 'text-decoration-line-through text-muted' : '' }}" style="{{ strtoupper((string) $comm->status) !== 'REJECTED' ? 'color: var(--color-primary-green);' : '' }}">
                                ₹{{ number_format($comm->calculated_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @php $st = strtoupper((string) $comm->status); @endphp
                                @if($st === 'PENDING')
                                    <span class="badge-pending">PENDING</span>
                                @elseif($st === 'APPROVED')
                                    <span class="badge-approved">APPROVED</span>
                                @elseif($st === 'PAYABLE')
                                    <span class="badge-payable">PAYABLE</span>
                                @elseif($st === 'PAID')
                                    <span class="badge-paid">PAID</span>
                                @elseif($st === 'REJECTED')
                                    <span class="badge-rejected">REJECTED</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $st }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="btn-action-icon" title="View Commission Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($comm->isPayable() && !auth()->user()->isAgent())
                                        <a href="{{ route('admin.commissions.pay', $comm) }}" class="btn-action-icon btn-action-pay" title="Disburse Commission">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @elseif($deposit)
                        {{-- Single Payout Deposit Commission: Single Clean Row (No Duplicate Parent Row!) --}}
                        <tr class="child-commission-row" style="background-color: var(--card-bg);">
                            <td class="ps-3 font-monospace">
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="year-badge">Year {{ $firstComm->payout_year ?? 1 }}</span>
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="comm-code-link fw-semibold small">
                                        {{ $firstComm->commission_number }}
                                    </a>
                                    <a href="{{ route('admin.deposits.show', $deposit) }}" class="text-muted text-decoration-none font-monospace small ms-0.5" title="Deposit: {{ $deposit->deposit_number }}">
                                        <i class="bi bi-shield-check text-success me-0.5"></i>{{ $deposit->deposit_number }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-dark fw-medium">{{ ($deposit->created_at ?? $firstComm->created_at)->format('d M Y') }}</span>
                                    <span class="text-muted ms-1" style="font-size: 0.72rem;">{{ ($deposit->created_at ?? $firstComm->created_at)->format('h:i A') }}</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark d-block text-truncate" style="max-width: 140px;">{{ $customer->name ?? 'N/A' }}</span>
                                    @if($customer && $customer->customer_number)
                                        <span class="text-muted font-monospace d-block" style="font-size: 0.7rem;">{{ $customer->customer_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark d-block text-truncate" style="max-width: 140px;">{{ $agent->name ?? 'N/A' }}</span>
                                    @if($agent && $agent->member_code)
                                        <span class="text-muted font-monospace d-block" style="font-size: 0.7rem;">{{ $agent->member_code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-medium text-dark">{{ $scheme->name ?? 'Deposit' }}</span>
                                    <span class="text-muted small d-block" style="font-size: 0.72rem;">{{ $tenureYears }} Yr(s) Tenure</span>
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-dark font-monospace">
                                ₹{{ number_format($depositAmount, 2) }}
                            </td>
                            <td class="text-center font-monospace small text-muted">
                                {{ number_format((float)$firstComm->rate, 2) }}%
                            </td>
                            <td class="text-end fw-bold font-monospace" style="color: var(--color-primary-green);">
                                ₹{{ number_format($firstComm->calculated_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @php $st = strtoupper((string) $firstComm->status); @endphp
                                @if($st === 'PENDING')
                                    <span class="badge-pending">PENDING</span>
                                @elseif($st === 'APPROVED')
                                    <span class="badge-approved">APPROVED</span>
                                @elseif($st === 'PAYABLE')
                                    <span class="badge-payable">PAYABLE</span>
                                @elseif($st === 'PAID')
                                    <span class="badge-paid">PAID</span>
                                @elseif($st === 'REJECTED')
                                    <span class="badge-rejected">REJECTED</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $st }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="btn-action-icon" title="View Commission Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($firstComm->isPayable() && !auth()->user()->isAgent())
                                        <a href="{{ route('admin.commissions.pay', $firstComm) }}" class="btn-action-icon btn-action-pay" title="Disburse Commission">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @else
                        {{-- Standalone Commission (No Deposit) --}}
                        <tr class="child-commission-row">
                            <td class="ps-3 font-monospace">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="badge bg-light text-secondary border py-0.5 px-1.5" style="font-size: 0.68rem; border-radius: 4px;">DIRECT</span>
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="comm-code-link">
                                        {{ $firstComm->commission_number }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $firstComm->created_at->format('d M Y, h:i A') }}</span>
                            </td>
                            <td>
                                <span class="fw-medium text-dark small text-truncate d-block" style="max-width: 130px;">{{ $customer->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="fw-medium text-dark small text-truncate d-block" style="max-width: 130px;">{{ $agent->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    @if($firstComm->collection)
                                        Coll #{{ $firstComm->collection->collection_number }}
                                    @else
                                        Standard Collection
                                    @endif
                                </span>
                            </td>
                            <td class="text-end text-dark font-monospace small">
                                ₹{{ number_format($firstComm->base_amount, 2) }}
                            </td>
                            <td class="text-center font-monospace small text-muted">
                                {{ number_format((float)$firstComm->rate, 2) }}%
                            </td>
                            <td class="text-end fw-bold font-monospace" style="color: var(--color-primary-green);">
                                ₹{{ number_format($firstComm->calculated_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @php $st = strtoupper((string) $firstComm->status); @endphp
                                @if($st === 'PENDING')
                                    <span class="badge-pending">PENDING</span>
                                @elseif($st === 'APPROVED')
                                    <span class="badge-approved">APPROVED</span>
                                @elseif($st === 'PAYABLE')
                                    <span class="badge-payable">PAYABLE</span>
                                @elseif($st === 'PAID')
                                    <span class="badge-paid">PAID</span>
                                @elseif($st === 'REJECTED')
                                    <span class="badge-rejected">REJECTED</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $st }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.commissions.show', $firstComm) }}" class="btn-action-icon" title="View Commission Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($firstComm->isPayable() && !auth()->user()->isAgent())
                                        <a href="{{ route('admin.commissions.pay', $firstComm) }}" class="btn-action-icon btn-action-pay" title="Disburse Commission">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <div class="bg-light border text-secondary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.35rem;">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h6 class="text-dark fw-semibold mb-1" style="font-size: 0.9rem;">No commission records found</h6>
                                <p class="text-muted small mb-3">No commission transactions match your current filter criteria.</p>
                                @if(request()->hasAny(['search', 'branch_id', 'agent_id', 'scheme_id', 'status', 'period']))
                                    <a href="{{ route('admin.commissions.index') }}" class="btn btn-sm btn-dark">
                                        <i class="bi bi-x-circle me-1"></i> Clear All Filters
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($commissions->hasPages())
    <div class="card-footer py-2.5 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white border-top">
        <span class="small text-muted" style="font-size: 0.78rem;">
            Showing <strong class="text-dark">{{ $commissions->firstItem() }}</strong> to <strong class="text-dark">{{ $commissions->lastItem() }}</strong> of <strong class="text-dark">{{ $commissions->total() }}</strong> records
        </span>
        <div>
            {{ $commissions->links() }}
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Individual group collapse / expand toggle
    const toggleButtons = document.querySelectorAll('.toggle-schedule-btn');
    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetClass = this.getAttribute('data-target');
            const rows = document.querySelectorAll('.' + targetClass);
            const isCurrentlyCollapsed = this.classList.contains('is-collapsed');

            rows.forEach(function (row) {
                if (isCurrentlyCollapsed) {
                    row.classList.remove('is-collapsed');
                } else {
                    row.classList.add('is-collapsed');
                }
            });

            this.classList.toggle('is-collapsed');
        });
    });

    // Toggle All Schedules Button
    const toggleAllBtn = document.getElementById('toggleAllSchedulesBtn');
    const toggleAllText = document.getElementById('toggleAllText');
    const toggleAllIcon = document.getElementById('toggleAllIcon');
    let allCollapsed = false;

    if (toggleAllBtn) {
        toggleAllBtn.addEventListener('click', function () {
            allCollapsed = !allCollapsed;
            const childRows = document.querySelectorAll('.child-commission-row');
            const individualBtns = document.querySelectorAll('.toggle-schedule-btn');

            childRows.forEach(function (row) {
                if (allCollapsed) {
                    row.classList.add('is-collapsed');
                } else {
                    row.classList.remove('is-collapsed');
                }
            });

            individualBtns.forEach(function (btn) {
                if (allCollapsed) {
                    btn.classList.add('is-collapsed');
                } else {
                    btn.classList.remove('is-collapsed');
                }
            });

            if (toggleAllText) {
                toggleAllText.textContent = allCollapsed ? 'Expand All' : 'Collapse All';
            }
            if (toggleAllIcon) {
                toggleAllIcon.className = allCollapsed ? 'bi bi-arrows-expand' : 'bi bi-arrows-collapse';
            }
        });
    }
});
</script>
@endsection
