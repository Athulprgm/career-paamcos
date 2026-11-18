@extends('superadmin.layouts.app')

@section('page-title', 'Edit Deposit Commission: ' . $scheme->name)

@section('content')
<style>
    .fd-form-card .table-responsive {
        border: none;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
    .senior-rate-preview-bar {
        background: var(--surface-subtle);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.78rem;
    }
    .promo-target-box {
        background: var(--surface-subtle);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 12px;
    }
</style>
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Scheme: {{ $scheme->name }}</h1>
        <div class="page-breadcrumb">Commission / <a href="{{ route('superadmin.fd-commissions.index') }}" style="color:inherit;">Deposit Commissions</a> / {{ $scheme->code }} / Edit</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('superadmin.fd-commissions.show', $scheme) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-eye me-1"></i> View Details
        </a>
        <a href="{{ route('superadmin.fd-commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Please fix the errors below:</strong>
    <ul class="mb-0 mt-1 ps-3" style="font-size:0.8rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form method="POST" action="{{ route('superadmin.fd-commissions.update', $scheme) }}" id="fdSchemeEditForm">
    @csrf
    @method('PUT')

    <input type="hidden" name="payout_year" id="payoutYearInput" value="{{ old('payout_year', max(1, $scheme->getTenureInYears())) }}">
    <input type="hidden" name="years_data" id="yearsDataInput" value="{{ old('years_data') }}">

    <div class="row g-3">
        <!-- Scheme Details -->
        <div class="col-lg-5">
            <div class="card fd-form-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Scheme Specifications</span>
                    <div class="d-flex align-items-center gap-1">
                        @if($scheme->isDouble())
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Double (2x)</span>
                        @endif
                        <code style="font-size:0.75rem;">{{ $scheme->code }}</code>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-dark mb-1">
                            Scheme Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name', $scheme->name) }}"
                               placeholder="e.g. 1 Year Regular Fixed Deposit" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="code" class="form-label small fw-semibold text-dark mb-0">
                                    Scheme Code <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnAutoGenCode" style="font-size:0.75rem;" title="Auto-generate clean unique code">
                                    <i class="bi bi-magic me-1"></i>Auto Gen
                                </button>
                            </div>
                            <input type="text" id="code" name="code"
                                   class="form-control form-control-sm font-monospace text-uppercase fw-semibold @error('code') is-invalid @enderror"
                                   value="{{ old('code', $scheme->code) }}"
                                   maxlength="30"
                                   placeholder="e.g. FDKDSP5Y" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="scheme_type" class="form-label small fw-semibold text-dark mb-1">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select id="scheme_type" name="scheme_type" class="form-select form-select-sm @error('scheme_type') is-invalid @enderror" required>
                                @foreach($schemeTypes as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" {{ old('scheme_type', $scheme->scheme_type) === $typeKey ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('scheme_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label for="interest_rate" class="form-label small fw-semibold text-dark mb-1">
                                Interest Rate (%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" id="interest_rate" name="interest_rate"
                                       class="form-control form-control-sm text-end @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate', number_format((float) $scheme->interest_rate, 2, '.', '')) }}"
                                       placeholder="7.50" required>
                                <span class="input-group-text bg-light text-muted">% p.a.</span>
                            </div>
                            @error('interest_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="senior_citizen_extra_rate" class="form-label small fw-semibold text-dark mb-1">
                                <i class="bi bi-person-plus text-primary me-1"></i>Senior Citizen Extra (%)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="10" id="senior_citizen_extra_rate" name="senior_citizen_extra_rate"
                                       class="form-control form-control-sm text-end @error('senior_citizen_extra_rate') is-invalid @enderror"
                                       value="{{ old('senior_citizen_extra_rate', number_format((float) ($scheme->senior_citizen_extra_rate ?? 0), 2, '.', '')) }}"
                                       placeholder="0.50">
                                <span class="input-group-text bg-light text-muted">+% p.a.</span>
                            </div>
                            @error('senior_citizen_extra_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    @php
                        $initBaseRate = (float) old('interest_rate', $scheme->interest_rate ?? 0);
                        $initSeniorExtra = (float) old('senior_citizen_extra_rate', $scheme->senior_citizen_extra_rate ?? 0);
                        $initSeniorTotal = $initBaseRate + $initSeniorExtra;
                    @endphp
                    <div class="senior-rate-preview-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3" id="seniorRateSummaryBar">
                        <span class="text-muted">
                            <i class="bi bi-person-heart text-primary me-1"></i>Senior Citizen Effective Rate:
                        </span>
                        <span class="fw-bold font-monospace text-primary" id="seniorTotalRatePreview">
                            @if($scheme->isDouble() && $initBaseRate <= 0)
                                Double (2x) at Maturity
                            @else
                                {{ number_format($initSeniorTotal, 2) }}% p.a.
                                <span class="text-muted fw-normal small" id="seniorRateBreakdownText">
                                    ({{ number_format($initBaseRate, 2) }}% + {{ number_format($initSeniorExtra, 2) }}%)
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="tenure" class="form-label small fw-semibold text-dark mb-1">
                                Tenure <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" max="1200" id="tenure" name="tenure"
                                       class="form-control form-control-sm text-center @error('tenure') is-invalid @enderror"
                                       value="{{ old('tenure', $scheme->tenure) }}" required>
                                <select name="tenure_unit" id="tenure_unit" class="form-select form-select-sm" style="max-width: 95px;">
                                    @foreach($tenureUnits as $unitKey => $unitLabel)
                                        <option value="{{ $unitKey }}" {{ old('tenure_unit', $scheme->tenure_unit) === $unitKey ? 'selected' : '' }}>
                                            {{ $unitLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('tenure')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="collection_frequency" class="form-label small fw-semibold text-dark mb-1">Collection</label>
                            <select id="collection_frequency" name="collection_frequency" class="form-select form-select-sm @error('collection_frequency') is-invalid @enderror">
                                @foreach($collectionFrequencies as $freqKey => $freqLabel)
                                    <option value="{{ $freqKey }}" {{ old('collection_frequency', $scheme->collection_frequency) === $freqKey ? 'selected' : '' }}>
                                        {{ $freqLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('collection_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="min_amount" class="form-label small fw-semibold text-dark mb-1">Min Deposit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">₹</span>
                                <input type="number" step="100" min="0" id="min_amount" name="min_amount"
                                       class="form-control form-control-sm @error('min_amount') is-invalid @enderror"
                                       value="{{ old('min_amount', $scheme->min_amount) }}">
                            </div>
                            @error('min_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="max_amount" class="form-label small fw-semibold text-dark mb-1">Max Deposit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">₹</span>
                                <input type="number" step="100" min="0" id="max_amount" name="max_amount"
                                       class="form-control form-control-sm @error('max_amount') is-invalid @enderror"
                                       value="{{ old('max_amount', $scheme->max_amount) }}" placeholder="Optional">
                            </div>
                            @error('max_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="interest_payout_frequency" class="form-label small fw-semibold text-dark mb-1">Interest Payout</label>
                            <select name="interest_payout_frequency" id="interest_payout_frequency" class="form-select form-select-sm @error('interest_payout_frequency') is-invalid @enderror">
                                @foreach($payoutFrequencies as $payoutKey => $payoutLabel)
                                    <option value="{{ $payoutKey }}" {{ old('interest_payout_frequency', $scheme->interest_payout_frequency ?? 'AT_MATURITY') === $payoutKey ? 'selected' : '' }}>
                                        {{ $payoutLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('interest_payout_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="status" class="form-label small fw-semibold text-dark mb-1">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                                <option value="ACTIVE" {{ old('status', $scheme->status) === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="INACTIVE" {{ old('status', $scheme->status) === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3 promo-target-box">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="promotion_target_percentage" class="form-label small fw-semibold text-dark mb-0">
                                <i class="bi bi-trophy text-warning me-1"></i>Promotion Target Credit %
                            </label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnAutoSuggestPromoTarget" style="font-size:0.75rem;" title="Auto-suggest standard rate based on tenure">
                                <i class="bi bi-magic me-1"></i>Auto Suggest
                            </button>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" max="100" id="promotion_target_percentage" name="promotion_target_percentage"
                                   class="form-control form-control-sm text-end fw-semibold @error('promotion_target_percentage') is-invalid @enderror"
                                   value="{{ old('promotion_target_percentage', $scheme->promotion_target_percentage !== null ? number_format((float) $scheme->promotion_target_percentage, 2, '.', '') : '') }}"
                                   placeholder="e.g. 20.00">
                            <span class="input-group-text bg-light text-muted">% credited</span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                            Weighted % of deposit volume counted toward agent rank promotion targets (e.g. 1Y: 20%, 2Y: 30%, 3Y: 40%, 5Y: 80%, 7Y RD: 90%, DD 1Y: 20%, 2Y: 40%, 3Y: 50%).
                        </small>
                        @error('promotion_target_percentage')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold text-dark mb-1">Description</label>
                        <textarea id="description" name="description" rows="2"
                                  class="form-control form-control-sm @error('description') is-invalid @enderror"
                                  placeholder="Optional scheme description or terms">{{ old('description', $scheme->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="notes" class="form-label small fw-semibold text-dark mb-1">Audit Note</label>
                        <input type="text" id="notes" name="notes"
                               class="form-control form-control-sm"
                               value="{{ old('notes', "Updated {$scheme->name} ({$scheme->code}) commission configuration.") }}"
                               placeholder="Reason for change">
                    </div>
                </div>
            </div>
        </div>

        <!-- Rank-wise Commission Configuration -->
        <div class="col-lg-7">
            @if(count($activeYears) > 1)
            <div class="card fd-form-card mb-3" id="yearSelectionCard">
                <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                    <div>
                        <span class="fw-semibold small">Payout Year Multi-Tier Commission</span>
                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">
                            Configure commission percentages per payout year
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-dark btn-sm py-1 px-2" id="btnAddYear" style="font-size:0.75rem;">
                        <i class="bi bi-plus me-1"></i>Add Year
                    </button>
                </div>
                <div class="card-body py-2 px-3">
                    <ul class="nav nav-pills gap-1" id="yearTabList">
                        @foreach($activeYears as $yr)
                            <li class="nav-item">
                                <button type="button" class="nav-link btn btn-sm py-1 px-3 {{ $loop->first ? 'active' : '' }}" data-year="{{ $yr }}">
                                    Year {{ $yr }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <div class="card fd-form-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span>Rank-wise Commission Configuration</span>
                            <span class="badge bg-light text-secondary border" id="badgeEditingYear">
                                Year {{ count($activeYears) > 1 ? $activeYears[0] : max(1, $scheme->getTenureInYears()) }}
                            </span>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">
                            Configure percentages for {{ $scheme->name }}
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btnSelectAll">All</button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btnDeselectAll">None</button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 48px;" class="text-center">Active</th>
                                    <th>Agent Rank</th>
                                    <th style="width: 70px;" class="text-center">Level</th>
                                    <th style="width: 150px;" class="text-end pe-3">Commission %</th>
                                </tr>
                            </thead>
                            <tbody id="rankCommissionTableBody">
                                @forelse($ranks as $index => $rank)
                                    @php
                                        $savedRule = $existingRankCommissions->get($rank->id);
                                        $defaultEligible = $savedRule ? (bool)$savedRule->is_eligible : false;
                                        $defaultPct = $savedRule && $savedRule->commission_percentage !== null
                                            ? number_format((float) $savedRule->commission_percentage, 2, '.', '')
                                            : '';

                                        $isEligible = old("ranks.{$index}.is_eligible", $defaultEligible);
                                        $commissionPct = old("ranks.{$index}.commission_percentage", $defaultPct);
                                    @endphp
                                    <tr class="rank-row {{ $isEligible ? '' : 'text-muted bg-light' }}" id="row-rank-{{ $rank->id }}">
                                        <td class="text-center">
                                            <input type="hidden" name="ranks[{{ $index }}][rank_id]" value="{{ $rank->id }}">
                                            <input type="checkbox"
                                                   name="ranks[{{ $index }}][is_eligible]"
                                                   value="1"
                                                   class="form-check-input rank-eligible-toggle"
                                                   id="toggle-{{ $rank->id }}"
                                                   data-rank-id="{{ $rank->id }}"
                                                   {{ $isEligible ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <label for="toggle-{{ $rank->id }}" class="mb-0 fw-medium text-dark user-select-none d-block" style="cursor: pointer;">
                                                {{ $rank->name }}
                                            </label>
                                            <span class="text-muted small font-monospace">{{ $rank->code ?? '' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border">
                                                L{{ $rank->level ?? ($rank->sort_order ?? $index + 1) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="input-group input-group-sm ms-auto" style="max-width: 130px;">
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       max="100"
                                                       name="ranks[{{ $index }}][commission_percentage]"
                                                       id="pct-{{ $rank->id }}"
                                                       class="form-control form-control-sm text-end rank-commission-input @error("ranks.{$index}.commission_percentage") is-invalid @enderror"
                                                       value="{{ $commissionPct }}"
                                                       placeholder="0.00"
                                                       data-rank-id="{{ $rank->id }}"
                                                       {{ $isEligible ? '' : 'disabled' }}>
                                                <span class="input-group-text bg-light text-muted">%</span>
                                            </div>
                                            @error("ranks.{$index}.commission_percentage")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            No agent ranks found in system.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Commission Summary Bar -->
                <div class="card-footer bg-transparent border-top py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">
                        <span id="lblEligibleCount" class="fw-semibold text-dark">0</span> of {{ count($ranks) }} ranks eligible
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-semibold text-muted text-uppercase">Total Commission:</span>
                        <span class="badge bg-dark text-white fs-6 font-monospace px-3 py-2" id="badgeTotalCommission">
                            {{ number_format((float) $scheme->total_commission_percentage, 2) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.fd-commissions.show', $scheme) }}" class="btn btn-outline-dark">
                    Cancel
                </a>
                <button type="submit" class="btn btn-dark" id="btnSubmitScheme">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('fdSchemeEditForm');
    const eligibleToggles = document.querySelectorAll('.rank-eligible-toggle');
    const commissionInputs = document.querySelectorAll('.rank-commission-input');
    const badgeTotal = document.getElementById('badgeTotalCommission');
    const lblCount = document.getElementById('lblEligibleCount');
    const btnSelectAll = document.getElementById('btnSelectAll');
    const btnDeselectAll = document.getElementById('btnDeselectAll');
    const payoutYearInput = document.getElementById('payoutYearInput');
    const yearsDataInput = document.getElementById('yearsDataInput');
    const yearTabList = document.getElementById('yearTabList');
    const btnAddYear = document.getElementById('btnAddYear');
    const badgeEditingYear = document.getElementById('badgeEditingYear');
    const tenureInput = document.getElementById('tenure');
    const tenureUnitSelect = document.getElementById('tenure_unit');
    const interestRateInput = document.getElementById('interest_rate');
    const seniorExtraInput = document.getElementById('senior_citizen_extra_rate');
    const seniorTotalPreview = document.getElementById('seniorTotalRatePreview');
    const isDoubleScheme = {{ $scheme->isDouble() ? 'true' : 'false' }};
    const btnAutoGenCode = document.getElementById('btnAutoGenCode');
    const nameInput = document.getElementById('name');
    const codeInput = document.getElementById('code');
    const typeSelect = document.getElementById('scheme_type');

    const hasMultiYear = {{ count($activeYears) > 1 ? 'true' : 'false' }};
    const yearsState = @json($rankCommissionsByYear);
    let currentYear = {{ (int) ($activeYears[0] ?? max(1, $scheme->getTenureInYears())) }};

    function updateSeniorRatePreview() {
        if (!seniorTotalPreview) return;
        const base = parseFloat(interestRateInput ? interestRateInput.value : 0) || 0;
        const extra = parseFloat(seniorExtraInput ? seniorExtraInput.value : 0) || 0;
        if (isDoubleScheme && base <= 0) {
            seniorTotalPreview.textContent = 'Double (2x) at Maturity';
            return;
        }
        const total = base + extra;
        seniorTotalPreview.innerHTML = total.toFixed(2) + '% p.a. <span class="text-muted fw-normal small">('
            + base.toFixed(2) + '% + ' + extra.toFixed(2) + '%)</span>';
    }

    function updatePayoutYear() {
        if (hasMultiYear) {
            if (payoutYearInput) payoutYearInput.value = currentYear;
            if (badgeEditingYear) badgeEditingYear.textContent = 'Year ' + currentYear;
            return;
        }
        const ten = Math.max(1, parseInt(tenureInput ? tenureInput.value : 1) || 1);
        const unit = ((tenureUnitSelect ? tenureUnitSelect.value : 'MONTHS') || 'MONTHS').toUpperCase();
        let tenureInYears = 1;
        if (unit === 'YEARS') tenureInYears = ten;
        else if (unit === 'MONTHS') tenureInYears = Math.max(1, Math.floor(ten / 12));
        else if (unit === 'DAYS') tenureInYears = Math.max(1, Math.floor(ten / 365));
        if (payoutYearInput) payoutYearInput.value = tenureInYears;
        if (badgeEditingYear) badgeEditingYear.textContent = 'Year ' + tenureInYears;
    }

    function saveCurrentYearToState() {
        if (!hasMultiYear) return;
        const rows = [];
        eligibleToggles.forEach(function (toggle) {
            const rankId = parseInt(toggle.getAttribute('data-rank-id'), 10);
            const input = document.getElementById('pct-' + rankId);
            const isEligible = toggle.checked;
            const pct = isEligible ? (parseFloat(input ? input.value : 0) || 0) : 0;
            rows.push({
                rank_id: rankId,
                eligible: isEligible,
                pct: pct.toFixed(2)
            });
        });
        yearsState[String(currentYear)] = rows;
        if (yearsDataInput) {
            yearsDataInput.value = JSON.stringify(yearsState);
        }
    }

    function loadYearFromState(yr) {
        currentYear = parseInt(yr, 10) || 1;
        const rows = yearsState[String(currentYear)] || [];
        const map = {};
        rows.forEach(function (r) {
            map[String(r.rank_id)] = r;
        });

        eligibleToggles.forEach(function (toggle) {
            const rid = toggle.getAttribute('data-rank-id');
            const input = document.getElementById('pct-' + rid);
            const entry = map[String(rid)];
            if (entry) {
                toggle.checked = Boolean(entry.eligible ?? entry.is_eligible);
                const val = entry.pct ?? entry.commission_percentage;
                if (input) {
                    input.value = (val !== null && val !== undefined && val !== '') ? parseFloat(val).toFixed(2) : '';
                }
            } else {
                toggle.checked = false;
                if (input) input.value = '';
            }
        });

        if (yearTabList) {
            yearTabList.querySelectorAll('.nav-link').forEach(function (btn) {
                btn.classList.toggle('active', parseInt(btn.getAttribute('data-year'), 10) === currentYear);
            });
        }
        updatePayoutYear();
        calculateTotal();
    }

    /* ═══════════ Commission total ════════════════════════════════════ */
    function calculateTotal() {
        let total = 0.00;
        let count = 0;

        eligibleToggles.forEach(function (toggle) {
            const rankId = toggle.getAttribute('data-rank-id');
            const input = document.getElementById('pct-' + rankId);
            const row = document.getElementById('row-rank-' + rankId);

            if (toggle.checked) {
                count++;
                if (input) {
                    input.removeAttribute('disabled');
                    total += (parseFloat(input.value) || 0);
                }
                if (row) {
                    row.classList.remove('text-muted', 'bg-light');
                }
            } else {
                if (input) {
                    input.setAttribute('disabled', 'disabled');
                }
                if (row) {
                    row.classList.add('text-muted', 'bg-light');
                }
            }
        });

        badgeTotal.textContent = total.toFixed(2) + '%';
        lblCount.textContent = count;

        if (total > 100.00) {
            badgeTotal.className = 'badge bg-danger text-white fs-6 font-monospace px-3 py-2';
        } else {
            badgeTotal.className = 'badge bg-dark text-white fs-6 font-monospace px-3 py-2';
        }

        if (hasMultiYear) {
            saveCurrentYearToState();
        }
    }

    eligibleToggles.forEach(function (toggle) {
        toggle.addEventListener('change', calculateTotal);
    });

    commissionInputs.forEach(function (input) {
        input.addEventListener('input', calculateTotal);
    });

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            eligibleToggles.forEach(function (t) { t.checked = true; });
            calculateTotal();
        });
    }

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            eligibleToggles.forEach(function (t) { t.checked = false; });
            calculateTotal();
        });
    }

    if (yearTabList) {
        yearTabList.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-year]');
            if (!btn) return;
            saveCurrentYearToState();
            loadYearFromState(btn.getAttribute('data-year'));
        });
    }

    if (btnAddYear && yearTabList) {
        btnAddYear.addEventListener('click', function () {
            saveCurrentYearToState();
            const existing = Object.keys(yearsState).map(function (k) { return parseInt(k, 10) || 1; });
            const nextYr = existing.length ? (Math.max.apply(null, existing) + 1) : 2;
            if (!yearsState[String(nextYr)]) {
                yearsState[String(nextYr)] = [];
            }
            const li = document.createElement('li');
            li.className = 'nav-item';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'nav-link btn btn-sm py-1 px-3';
            btn.setAttribute('data-year', String(nextYr));
            btn.textContent = 'Year ' + nextYr;
            li.appendChild(btn);
            yearTabList.appendChild(li);
            loadYearFromState(nextYr);
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function () {
            if (hasMultiYear) {
                saveCurrentYearToState();
            } else if (yearsDataInput) {
                yearsDataInput.value = '';
            }
        });
    }

    if (interestRateInput) {
        interestRateInput.addEventListener('input', updateSeniorRatePreview);
    }
    if (seniorExtraInput) {
        seniorExtraInput.addEventListener('input', updateSeniorRatePreview);
    }

    if (tenureInput) {
        tenureInput.addEventListener('input', updatePayoutYear);
    }
    if (tenureUnitSelect) {
        tenureUnitSelect.addEventListener('change', updatePayoutYear);
    }

    /* ═══════════ Code auto-generation ════════════════════════════════ */
    function extractAbbr(name, depositType) {
        if (!name) return '';
        let clean = name.trim();
        clean = clean.replace(/\(\s*\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\s*\)/gi, '');
        clean = clean.replace(/\b\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\b/gi, '');
        const dt = (depositType || 'FD').toUpperCase().trim();
        if (dt === 'FD') clean = clean.replace(/^(fixed\s*deposit|fd)\b\s*/i, '');
        else if (dt === 'RD') clean = clean.replace(/^(recurring\s*deposit|rd)\b\s*/i, '');
        else if (dt === 'DD') clean = clean.replace(/^(daily\s*deposit|dd|pigmy)\b\s*/i, '');
        else if (dt === 'MIS') clean = clean.replace(/^(monthly\s*income\s*scheme|mis)\b\s*/i, '');
        clean = clean.replace(/[^a-zA-Z0-9\s]/g, ' ').trim();
        if (!clean) return '';
        let words = clean.split(/\s+/).filter(Boolean);
        if (!words.length) return '';
        if (words.length > 2) {
            const stopWords = ['AND', 'OF', 'FOR', 'THE', 'IN', 'ON', 'AT', 'TO', 'A', 'AN', 'WITH'];
            const filtered = words.filter(w => !stopWords.includes(w.toUpperCase()));
            if (filtered.length > 0) words = filtered;
        }
        if (words.length >= 2) return words.map(w => w.charAt(0).toUpperCase()).join('');
        const single = words[0];
        if (single.length <= 2) return single.toUpperCase();
        if (single === single.toUpperCase() && single.length <= 4) return single;
        const first = single.charAt(0).toUpperCase();
        const m = single.substring(1).match(/[bcdfghjklmnpqrstvwxyz]/i);
        return m ? (first + m[0].toUpperCase()) : single.substring(0, 2).toUpperCase();
    }

    function autoGenEditCode() {
        const type = ((typeSelect ? typeSelect.value : 'FD') || 'FD').toUpperCase().trim();
        const ten  = Math.max(1, parseInt(tenureInput ? tenureInput.value : 1) || 1);
        const unit = ((tenureUnitSelect ? tenureUnitSelect.value : 'YEARS') || 'YEARS').toUpperCase();
        let suf = 'M';
        if (unit === 'YEARS' || unit === 'YEAR' || unit === 'Y') suf = 'Y';
        else if (unit === 'DAYS' || unit === 'DAY' || unit === 'D') suf = 'D';

        const abbr = extractAbbr(nameInput ? nameInput.value : '', type);
        const final = (type + abbr + ten + suf).toUpperCase().substring(0, 30);

        if (codeInput) {
            codeInput.value = final;
            codeInput.classList.add('border-primary');
            setTimeout(function () { codeInput.classList.remove('border-primary'); }, 600);
        }
    }

    if (btnAutoGenCode) {
        btnAutoGenCode.addEventListener('click', autoGenEditCode);
    }

    /* ═══════════ Promotion Target Auto-Suggest ══════════════════════ */
    function getSuggestedPromoTarget(schemeType, tenure, tenureUnit) {
        const t = parseInt(tenure, 10) || 0;
        const u = (tenureUnit || 'MONTHS').toUpperCase();
        let years = 0;
        if (u === 'YEARS' || u === 'YEAR' || u === 'Y') {
            years = t;
        } else if (u === 'MONTHS' || u === 'MONTH' || u === 'M') {
            years = Math.floor(t / 12);
        } else if (u === 'DAYS' || u === 'DAY' || u === 'D') {
            years = Math.floor(t / 365);
        }
        const st = (schemeType || 'FD').toUpperCase();
        if (st.includes('DD') || st.includes('DAILY') || st.includes('PIGMY')) {
            if (years === 1) return '20.00';
            if (years === 2) return '40.00';
            if (years === 3) return '50.00';
        } else if (st.includes('RD') || st.includes('RECUR')) {
            if (years === 1) return '20.00';
            if (years === 2) return '30.00';
            if (years === 3) return '40.00';
            if (years === 5 || years === 6) return '80.00';
            if (years === 7) return '90.00';
        } else {
            if (years === 1) return '20.00';
            if (years === 2) return '30.00';
            if (years === 3) return '40.00';
            if (years === 5 || years === 6) return '80.00';
        }
        return '';
    }

    const btnAutoSuggestPromo = document.getElementById('btnAutoSuggestPromoTarget');
    const promoTargetInput = document.getElementById('promotion_target_percentage');

    if (btnAutoSuggestPromo && promoTargetInput) {
        btnAutoSuggestPromo.addEventListener('click', function () {
            const st = typeSelect ? typeSelect.value : '{{ $scheme->scheme_type }}';
            const t = tenureInput ? tenureInput.value : {{ $scheme->tenure }};
            const u = tenureUnitSelect ? tenureUnitSelect.value : '{{ $scheme->tenure_unit }}';
            const suggested = getSuggestedPromoTarget(st, t, u);
            if (suggested) {
                promoTargetInput.value = suggested;
                promoTargetInput.classList.add('border-primary');
                setTimeout(function () { promoTargetInput.classList.remove('border-primary'); }, 600);
            }
        });
    }

    /* ═══════════ Bootstrap ══════════════════════════════════════════ */
    updateSeniorRatePreview();
    if (hasMultiYear) {
        loadYearFromState(currentYear);
    } else {
        updatePayoutYear();
        calculateTotal();
    }
});
</script>
@endsection
