@extends('superadmin.layouts.app')

@section('page-title', $scheme->name . ' — Deposit Commission Details')

@section('content')
<style>
    .fd-show-card .table-responsive {
        border: none;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
</style>
{{-- ── 1. Page Header & Actions ───────────────────────────────────────────── --}}
<div class="page-header mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="page-title mb-0">{{ $scheme->name }}</h1>
            <span class="badge bg-light text-secondary border font-monospace px-2 py-1">
                {{ $scheme->code }}
            </span>
            <span class="badge bg-light text-dark border px-2 py-1">
                {{ $scheme->scheme_type }} &bull; {{ $scheme->tenure }} {{ (int) $scheme->tenure === 1 ? Str::singular(ucfirst(strtolower($scheme->tenure_unit))) : ucfirst(strtolower($scheme->tenure_unit)) }}
            </span>
            @if($scheme->isDouble())
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Double (2x)</span>
            @endif
            @if($scheme->isActive())
                <span class="badge-active">Active</span>
            @else
                <span class="badge-inactive">Inactive</span>
            @endif
        </div>
        <div class="page-breadcrumb">
            Commission / <a href="{{ route('superadmin.fd-commissions.index') }}" style="color:inherit; text-decoration:none;">Deposit Schemes</a> / {{ $scheme->name }}
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>

        <a href="{{ route('superadmin.fd-commissions.create', ['clone_from' => $scheme->id]) }}"
           class="btn btn-outline-secondary btn-sm"
           title="Create a new scheme variant based on this structure">
            <i class="bi bi-copy me-1"></i> Clone
        </a>

        <a href="{{ route('superadmin.fd-commissions.edit', $scheme) }}" class="btn btn-dark btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Scheme
        </a>

        <form action="{{ route('superadmin.fd-commissions.toggle-status', $scheme) }}" method="POST" class="d-inline m-0">
            @csrf
            <button type="submit"
                    class="btn btn-sm {{ $scheme->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                    onclick="return confirm('{{ $scheme->isActive() ? 'Deactivate' : 'Activate' }} {{ addslashes($scheme->name) }}?')">
                <i class="bi bi-{{ $scheme->isActive() ? 'pause-circle' : 'play-circle' }} me-1"></i>
                {{ $scheme->isActive() ? 'Deactivate' : 'Activate' }}
            </button>
        </form>

        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash me-1"></i> Delete Scheme
        </button>
    </div>
</div>

{{-- ── 2. Feedback Notifications ────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0"></i>
    <div>{{ session('error') }}</div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- ── 3. Key Financial Highlights (Humanized Stat Cards) ───────────────── --}}
@php
    $baseInterestRate = (float) $scheme->interest_rate;
    $seniorExtraRate = (float) ($scheme->senior_citizen_extra_rate ?? 0.0);
    $seniorTotalRate = $baseInterestRate + $seniorExtraRate;
@endphp
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon emerald">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                @if($scheme->isDouble() && $baseInterestRate <= 0)
                    <div class="stat-value">Double (2x)</div>
                    <div class="stat-label">Maturity Benefit</div>
                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                        Doubles principal on maturity
                    </div>
                @else
                    <div class="stat-value">{{ number_format($baseInterestRate, 2) }}%</div>
                    <div class="stat-label">Annual Interest Rate</div>
                    @if($seniorExtraRate > 0)
                        <div class="text-primary fw-medium small mt-1" style="font-size: 0.72rem;">
                            Senior: {{ number_format($seniorTotalRate, 2) }}% (+{{ number_format($seniorExtraRate, 2) }}%)
                        </div>
                    @else
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                            {{ str_replace('_', ' ', $scheme->interest_payout_frequency ?? 'AT MATURITY') }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon black">
                <i class="bi bi-calendar3"></i>
            </div>
            <div>
                <div class="stat-value">{{ $scheme->tenure }} <span style="font-size: 0.95rem; font-weight: 500;">{{ ucfirst(strtolower($scheme->tenure_unit)) }}</span></div>
                <div class="stat-label">Deposit Tenure</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    Collection: {{ ucfirst(strtolower(str_replace('_', ' ', $scheme->collection_frequency ?? 'One Time'))) }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format((float) $totalCommission, 2) }}%</div>
                <div class="stat-label">Total Commission Pool</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    {{ $eligibleCount }} of {{ $allRanks->count() }} ranks eligible
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-trophy-fill text-primary"></i>
            </div>
            <div>
                <div class="stat-value text-primary" style="font-size: 1.25rem; font-weight: 700;">
                    {{ number_format((float) $scheme->getPromotionTargetPercentage(), 2) }}%
                </div>
                <div class="stat-label">Promotion Target Credit</div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    Volume counted toward rank promotion targets
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 4. Main Details & Commission Distribution ───────────────────────── --}}
<div class="row g-4">
    {{-- Left Main Column: Commission Rates --}}
    <div class="col-lg-8">
        <div class="card fd-show-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                <div>
                    <h5 class="mb-0 fw-semibold text-dark" style="font-size: 1rem;">
                        <i class="bi bi-diagram-3-fill me-2 text-primary"></i>Agent Commission Distribution
                    </h5>
                    <div class="text-muted small mt-1">
                        Commission rates for {{ $scheme->name }} ({{ $scheme->tenure }} {{ (int) $scheme->tenure === 1 ? Str::singular(ucfirst(strtolower($scheme->tenure_unit))) : ucfirst(strtolower($scheme->tenure_unit)) }})
                    </div>
                </div>

                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 font-monospace" style="font-size: 0.78rem;">
                    Total Pool: {{ number_format((float) $totalCommission, 2) }}%
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="ps-3 py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.7rem; letter-spacing:0.04em;">Rank / Designation</th>
                                <th class="text-center py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.7rem; width:70px;">Level</th>
                                <th class="text-center py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.7rem; width:95px;">Status</th>
                                <th class="text-end pe-3 py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.7rem; width:140px;">Commission Rate</th>
                                <th class="text-end pe-3 py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.7rem; width:120px;">Share of Pool</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allRanks as $rank)
                                @php
                                    $rc = $rankCommissionsMap->get($rank->id);
                                    $isEligible = $rc ? (bool) $rc->is_eligible : false;
                                    $rate = ($rc && $isEligible) ? (float) $rc->commission_percentage : 0.0;
                                    $share = ($totalCommission > 0 && $rate > 0) ? round(($rate / $totalCommission) * 100, 1) : 0;
                                @endphp
                                <tr class="{{ $isEligible ? '' : 'text-muted bg-light bg-opacity-25' }}">
                                    <td class="ps-3 py-2">
                                        <div class="fw-semibold text-dark">{{ $rank->name }}</div>
                                        <div class="text-muted font-monospace small" style="font-size: 0.72rem;">{{ $rank->code }}</div>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <span class="badge bg-light text-secondary border">
                                            L{{ $rank->level ?? ($rank->sort_order ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($isEligible)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.72rem;">Eligible</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.72rem;">Ineligible</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end font-monospace">
                                        @if($isEligible && $rate > 0)
                                            <span class="fw-bold text-dark">{{ number_format($rate, 2) }}%</span>
                                        @else
                                            <span class="text-muted small">0.00%</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end font-monospace text-muted small">
                                        @if($isEligible && $rate > 0)
                                            {{ $share }}%
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No rank commission configuration available for this scheme.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light border-top fw-bold">
                            <tr>
                                <td colspan="3" class="ps-3 py-2 text-dark text-uppercase" style="font-size:0.75rem; letter-spacing:0.04em;">
                                    Total Commission Pool
                                </td>
                                <td class="pe-3 text-end font-monospace text-dark py-2" style="font-size:0.9rem;">
                                    {{ number_format((float) $totalCommission, 2) }}%
                                </td>
                                <td class="pe-3 text-end text-muted font-monospace small py-2">
                                    {{ $totalCommission > 0 ? '100.0%' : '0.0%' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Audit & History Log --}}
        <div class="card fd-show-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-clock-history me-1 text-muted"></i>Configuration History
                </span>
                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                    {{ $scheme->commissionHistories->count() }} {{ Str::plural('change', $scheme->commissionHistories->count()) }}
                </span>
            </div>
            <div class="card-body p-0">
                @if($scheme->commissionHistories->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.675rem; width:150px;">Date & Time</th>
                                    <th class="py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.675rem; width:130px;">User</th>
                                    <th class="py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.675rem; width:100px;">Action</th>
                                    <th class="py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.675rem; width:90px;">Pool Rate</th>
                                    <th class="pe-3 py-2 text-secondary text-uppercase fw-semibold" style="font-size:0.675rem;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheme->commissionHistories as $history)
                                    <tr>
                                        <td class="ps-3 py-2 font-monospace text-muted small">
                                            {{ $history->created_at->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="py-2">
                                            <span class="fw-medium text-dark">{{ $history->user?->name ?? 'System' }}</span>
                                        </td>
                                        <td class="py-2">
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.675rem;">
                                                {{ ucfirst($history->action) }}
                                            </span>
                                        </td>
                                        <td class="py-2 font-monospace fw-semibold text-dark">
                                            {{ number_format((float) $history->total_commission_percentage, 2) }}%
                                        </td>
                                        <td class="pe-3 py-2 text-muted small">
                                            {{ $history->notes ?? 'Configuration modified.' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-clock-history d-block mb-1 fs-4 text-secondary opacity-50"></i>
                        No configuration changes recorded yet. This scheme is operating on its original configuration.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right Sidebar Column: Scheme Rules & Integrity --}}
    <div class="col-lg-4">
        {{-- Scheme Limits & Info Card --}}
        <div class="card mb-4">
            <div class="card-header py-2 px-3">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-cash-stack me-1 text-muted"></i>Deposit Limits & Info
                </span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Minimum Deposit</span>
                    <span class="fw-semibold text-dark font-monospace small">₹{{ number_format((float) $scheme->min_amount, 2) }}</span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Maximum Deposit</span>
                    <span class="fw-semibold text-dark font-monospace small">
                        {{ $scheme->max_amount ? '₹' . number_format((float) $scheme->max_amount, 2) : 'No Limit' }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Tenure Duration</span>
                    <span class="fw-semibold text-dark font-monospace small">
                        {{ $scheme->tenure }} {{ ucfirst(strtolower($scheme->tenure_unit)) }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Promotion Target Credit</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace small">
                        <i class="bi bi-trophy me-1"></i>{{ number_format((float) $scheme->getPromotionTargetPercentage(), 2) }}%
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Collection Method</span>
                    <span class="fw-semibold text-dark small">
                        {{ ucfirst(strtolower(str_replace('_', ' ', $scheme->collection_frequency ?? 'One Time'))) }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Scheme Type</span>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $scheme->scheme_type }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Interest & Payout Terms --}}
        <div class="card mb-4">
            <div class="card-header py-2 px-3">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-wallet2 me-1 text-muted"></i>Interest & Payout Terms
                </span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Base Annual Interest</span>
                    @if($scheme->isDouble() && $baseInterestRate <= 0)
                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace small">
                            Double (2x)
                        </span>
                    @else
                        <span class="fw-semibold text-success font-monospace small">
                            {{ number_format($baseInterestRate, 2) }}% p.a.
                        </span>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Senior Citizen Extra</span>
                    <span class="fw-semibold text-dark font-monospace small">
                        +{{ number_format($seniorExtraRate, 2) }}%
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Senior Citizen Effective</span>
                    <span class="fw-semibold text-primary font-monospace small">
                        @if($scheme->isDouble() && $baseInterestRate <= 0)
                            2x at Maturity
                        @else
                            {{ number_format($seniorTotalRate, 2) }}% p.a.
                        @endif
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Interest Payout</span>
                    <span class="fw-semibold text-dark small">
                        {{ str_replace('_', ' ', $scheme->interest_payout_frequency ?? 'AT MATURITY') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Withdrawal & Loan Terms --}}
        <div class="card mb-4">
            <div class="card-header py-2 px-3">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-shield-lock me-1 text-muted"></i>Premature & Loan Terms
                </span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Lock-in Period</span>
                    <span class="fw-semibold text-dark small">
                        {{ $scheme->lock_in_months ? $scheme->lock_in_months . ' Months' : 'None' }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Premature Withdrawal</span>
                    <span>
                        @if($scheme->allow_premature_withdrawal)
                            <span class="text-success small fw-medium">Allowed</span>
                            <span class="text-muted small">({{ number_format((float)$scheme->premature_penalty_percent, 2) }}% penalty)</span>
                        @else
                            <span class="text-danger small fw-medium">Not Allowed</span>
                        @endif
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Loan Against Deposit</span>
                    <span>
                        @if($scheme->allow_loan_against_deposit)
                            <span class="text-success small fw-medium">Allowed</span>
                            <span class="text-muted small">(Up to {{ number_format((float)$scheme->max_loan_percentage, 0) }}%)</span>
                        @else
                            <span class="text-danger small fw-medium">Not Allowed</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Audit Meta Information --}}
        <div class="card mb-4 bg-light bg-opacity-50">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">
                    <i class="bi bi-person me-1"></i>Created by:
                    <strong class="text-dark">{{ $scheme->creator?->name ?? 'System Admin' }}</strong>
                </div>
                <div class="small text-muted mb-1">
                    <i class="bi bi-clock me-1"></i>Created:
                    <span class="font-monospace">{{ $scheme->created_at->format('d M Y, h:i A') }}</span>
                </div>
                @if($scheme->updated_at->ne($scheme->created_at))
                    <div class="small text-muted">
                        <i class="bi bi-pencil me-1"></i>Last updated:
                        <span class="font-monospace">{{ $scheme->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Delete Scheme Modal ─────────────────────────────────────────────── --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Deposit Scheme
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('superadmin.fd-commissions.destroy', $scheme) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete scheme <strong>{{ $scheme->name }} ({{ $scheme->code }})</strong>?</p>
                    
                    @if($deleteSafety['can_delete'] ?? false)
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            This scheme has no active deposits or transactions and can be deleted safely.
                        </div>
                    @else
                        <div class="alert alert-danger small mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <strong>Warning:</strong> {{ $deleteSafety['reason'] ?? 'This scheme has linked records.' }}
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="force" value="1" id="confirmForceDelete" required>
                            <label class="form-check-label small text-danger fw-semibold" for="confirmForceDelete">
                                Confirm force delete: delete this scheme and clean up associated rules/commissions.
                            </label>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
