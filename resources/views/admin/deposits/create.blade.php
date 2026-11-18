@extends('superadmin.layouts.app')

@section('page-title', 'New Deposit Account')

@push('styles')
<style>
    .bank-mono-input {
        font-variant-numeric: tabular-nums;
    }
    .summary-list {
        display: flex;
        flex-direction: column;
    }
    .summary-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 16px;
        border-bottom: 1px solid var(--border-color, #e2e8e4);
        font-size: 0.84rem;
    }
    .summary-row:last-child {
        border-bottom: none;
    }
    .summary-label {
        color: var(--text-muted, #64748b);
    }
    .summary-value {
        font-weight: 600;
        color: var(--text-heading, #0f172a);
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .summary-sub {
        display: block;
        font-size: 0.72rem;
        font-weight: 400;
        color: var(--text-muted, #64748b);
    }
    .summary-section-title {
        padding: 6px 16px;
        background: var(--surface-subtle, #f8faf9);
        border-top: 1px solid var(--border-color, #e2e8e4);
        border-bottom: 1px solid var(--border-color, #e2e8e4);
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">New Deposit Account</h1>
        <div class="page-breadcrumb">
            <a href="{{ route('admin.deposits.index') }}" class="text-decoration-none text-muted">Deposits</a> / New Deposit
        </div>
    </div>
    <div>
        <a href="{{ route('admin.deposits.index') }}" class="btn btn-outline-dark btn-sm">
            Back to Deposits
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <div class="fw-semibold mb-1">Please fix the following errors:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.deposits.store') }}" method="POST" id="depositForm">
    @csrf

    <input type="hidden" name="payment_mode" value="CASH">
    <input type="hidden" name="branch_id" id="branch_id" value="{{ old('branch_id', $selectedLead?->branch_id ?? $selectedCustomer?->branch_id) }}">
    <input type="hidden" name="payout_frequency" id="payout_frequency" value="{{ old('payout_frequency', 'AT_MATURITY') }}">
    <input type="hidden" name="is_double" id="is_double_hidden" value="{{ old('is_double', 0) }}">
    <input type="hidden" name="tenure" id="tenure" value="{{ old('tenure') }}">
    <input type="hidden" name="tenure_unit" id="tenure_unit" value="{{ old('tenure_unit') }}">

    <div class="row g-3 align-items-start">
        <!-- Left Column: Form Inputs -->
        <div class="col-lg-8">
            <!-- Customer & Agent -->
            <div class="card border mb-3">
                <div class="card-header bg-white py-2 px-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">Customer &amp; Agent</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="lead_id" class="form-label small fw-medium mb-1">Lead (Optional)</label>
                            <select name="lead_id" id="lead_id" class="form-select @error('lead_id') is-invalid @enderror" onchange="onLeadSelect(this)">
                                <option value="">— Select Lead —</option>
                                @foreach($agentLeads as $lead)
                                    @php
                                        $leadCust = $lead->convertedCustomer;
                                        $leadIsSenior = $leadCust && ! empty($leadCust->date_of_birth)
                                            && \Carbon\Carbon::parse($leadCust->date_of_birth)->age >= 60;
                                    @endphp
                                    <option value="{{ $lead->id }}"
                                            data-lead-id="{{ $lead->id }}"
                                            data-name="{{ $lead->name }}"
                                            data-mobile="{{ $lead->mobile }}"
                                            data-agent-id="{{ $lead->agent_id }}"
                                            data-agent-name="{{ $lead->agent?->name }}"
                                            data-branch-id="{{ $lead->branch_id }}"
                                            data-scheme-id="{{ $lead->interested_scheme_id }}"
                                            data-amount="{{ $lead->expected_amount }}"
                                            data-is-senior="{{ $leadIsSenior ? '1' : '0' }}"
                                            {{ (old('lead_id', $selectedLeadId ?? ($selectedLead?->id ?? '')) == $lead->id) ? 'selected' : '' }}>
                                        {{ $lead->name }} ({{ $lead->mobile }}) — {{ $lead->agent?->name ?? 'Unassigned' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="agent_id" class="form-label small fw-medium mb-1">Agent <span class="text-danger">*</span></label>
                            <select name="agent_id" id="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required onchange="onAgentSelect(this)">
                                <option value="">— Select Agent —</option>
                                @foreach($agents as $ag)
                                    @php
                                        $agCode = $ag->member_code ?? $ag->agent_id;
                                        $agBranch = $ag->branch?->name ?? 'All Branches / Head Office';
                                        $agRank = $ag->rank?->name ?? $ag->careerLevel?->name;
                                        $defaultSelectedAgentId = old('agent_id', $selectedLead?->agent_id ?? $selectedCustomer?->agent_id ?? (auth()->check() && auth()->user()->isSuperAdmin() ? auth()->user()->agentProfile?->id : null));
                                    @endphp
                                    <option value="{{ $ag->id }}"
                                            data-id="{{ $ag->id }}"
                                            data-name="{{ $ag->name }}"
                                            data-code="{{ $agCode }}"
                                            data-branch-id="{{ $ag->branch_id ?? '' }}"
                                            data-branch-name="{{ $agBranch }}"
                                            data-rank="{{ $agRank ?? '' }}"
                                            {{ ($defaultSelectedAgentId == $ag->id) ? 'selected' : '' }}>
                                        {{ $ag->name }} ({{ $agCode }})@if($agRank) — [{{ $agRank }}]@endif — {{ $agBranch }}
                                    </option>
                                @endforeach
                            </select>
                            @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-7">
                            <label for="manual_customer_name" class="form-label small fw-medium mb-1">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="manual_customer_name" id="manual_customer_name"
                                   class="form-control @error('manual_customer_name') is-invalid @enderror"
                                   placeholder="Full name"
                                   value="{{ old('manual_customer_name', $selectedLead?->name ?? $selectedCustomer?->name) }}" required>
                            @error('manual_customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-5">
                            <label for="manual_customer_mobile" class="form-label small fw-medium mb-1">Mobile Number</label>
                            <input type="text" name="manual_customer_mobile" id="manual_customer_mobile"
                                   class="form-control bank-mono-input @error('manual_customer_mobile') is-invalid @enderror"
                                   placeholder="10-digit mobile" maxlength="15"
                                   value="{{ old('manual_customer_mobile', $selectedLead?->mobile ?? $selectedCustomer?->mobile) }}">
                            @error('manual_customer_mobile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deposit Details -->
            <div class="card border">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Deposit Details</h6>
                    <span class="badge bg-light text-secondary border fw-normal" id="interest_type_badge">Standard</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="deposit_scheme_id" class="form-label small fw-medium mb-1">Deposit Scheme <span class="text-danger">*</span></label>
                            <select name="deposit_scheme_id" id="deposit_scheme_id" class="form-select @error('deposit_scheme_id') is-invalid @enderror" required onchange="onSchemeChange(this)">
                                <option value="">— Select Scheme —</option>
                                @foreach($schemes as $sch)
                                    @php
                                        $schIsMrp = $sch->isMrp();
                                        $schIsKsp = $sch->isKsp();
                                        $schKspConfig = $schIsKsp ? $sch->getKspConfig() : null;
                                        $schCollFreq = $sch->getEffectiveCollectionFrequency();
                                        $schPayoutFreq = $sch->getEffectivePayoutFrequency();
                                        $schMrpRates = ($sch->mrpConfiguration && $sch->mrpConfiguration->is_enabled)
                                            ? $sch->mrpRates->map(fn ($mr) => [
                                                'year' => (int) $mr->year,
                                                'general_rate' => (float) $mr->general_rate,
                                                'general_monthly_return' => (float) $mr->general_monthly_return,
                                                'senior_rate' => (float) $mr->senior_rate,
                                                'senior_monthly_return' => (float) $mr->senior_monthly_return,
                                            ])->values()
                                            : [];
                                    @endphp
                                    <option value="{{ $sch->id }}"
                                            data-scheme-name="{{ $sch->name }}"
                                            data-scheme-code="{{ $sch->code }}"
                                            data-min="{{ $sch->min_amount }}"
                                            data-max="{{ $sch->max_amount ?? '' }}"
                                            data-tenure="{{ $sch->tenure }}"
                                            data-tenure-unit="{{ $sch->tenure_unit }}"
                                            data-rate="{{ $sch->interest_rate }}"
                                            data-senior-extra="{{ (float)($sch->senior_citizen_extra_rate ?? 0) }}"
                                            data-type="{{ $sch->scheme_type }}"
                                            data-collection-frequency="{{ $schCollFreq }}"
                                            data-collection-label="{{ $sch->getCollectionFrequencyLabel() }}"
                                            data-payout-frequency="{{ $schPayoutFreq }}"
                                            data-payout-label="{{ $sch->getInterestPayoutFrequencyLabel() }}"
                                            data-is-double="{{ $sch->isDouble() ? '1' : '0' }}"
                                            data-is-mrp="{{ $schIsMrp ? '1' : '0' }}"
                                            data-is-ksp="{{ $schIsKsp ? '1' : '0' }}"
                                            data-ksp-config="{{ json_encode($schKspConfig) }}"
                                            data-mrp-method="{{ $sch->mrpConfiguration?->calculation_method ?? 'STANDARD' }}"
                                            data-mrp-rates="{{ json_encode($schMrpRates) }}"
                                            data-tenure-rates="{{ json_encode($sch->activeInterestRates ? $sch->activeInterestRates->map(function($r) {
                                                return [
                                                    'id' => $r->id,
                                                    'tenure' => $r->tenure,
                                                    'tenure_unit' => $r->tenure_unit,
                                                    'interest_rate' => (float)$r->interest_rate,
                                                    'senior_citizen_extra_rate' => (float)($r->senior_citizen_extra_rate ?? 0),
                                                    'formatted_tenure' => $r->getFormattedTenure(),
                                                ];
                                            })->values() : []) }}"
                                            {{ (old('deposit_scheme_id', $selectedLead?->interested_scheme_id) == $sch->id) ? 'selected' : '' }}>
                                        @if($schIsKsp && $schKspConfig)
                                            @php
                                                $kspPeriod = (int) $schKspConfig['deposit_period'];
                                                $kspInsFormatted = \App\Services\CurrencyFormatter::formatIndianNumber((float) $schKspConfig['insurance_coverage'], 0);
                                                $kspRetFormatted = \App\Services\CurrencyFormatter::formatIndianNumber((float) $schKspConfig['annual_return_amount'], 0);
                                                $kspStart = (int) $schKspConfig['return_start_year'];
                                                $kspEnd = (int) $schKspConfig['return_end_year'];
                                                $kspWin = $kspStart === $kspEnd
                                                    ? \App\Models\DepositScheme::formatOrdinalYear($kspStart)
                                                    : ($kspEnd === $kspStart + 1
                                                        ? str_replace(' Year', '', \App\Models\DepositScheme::formatOrdinalYear($kspStart)).' & '.str_replace(' Year', ' Yr', \App\Models\DepositScheme::formatOrdinalYear($kspEnd))
                                                        : 'Yr '.$kspStart.'–'.$kspEnd);
                                            @endphp
                                            {{ $sch->name }} ({{ $sch->code }}) — {{ $kspPeriod }} {{ $kspPeriod === 1 ? 'Year' : 'Years' }} | Return: ₹{{ $kspRetFormatted }}/yr ({{ $kspWin }}) | Insurance: ₹{{ $kspInsFormatted }}
                                        @else
                                            {{ $sch->name }} ({{ $sch->code }}) — {{ $sch->tenure }} {{ strtolower($sch->tenure_unit) }} ({{ $sch->isDouble() ? '2x Plan' : $sch->interest_rate.'% p.a.' }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('deposit_scheme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-5">
                            <label for="tenure_tier_select" class="form-label small fw-medium mb-1">Tenure <span class="text-danger">*</span></label>
                            <select id="tenure_tier_select" class="form-select" onchange="onTenureTierChange(this)">
                                <option value="">Select scheme first</option>
                            </select>
                            <small id="tenure_tier_hint" class="text-muted d-block mt-1" style="font-size: 0.74rem;"></small>
                            @error('tenure')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label for="amount" class="form-label small fw-medium mb-1">
                                <span id="amount_label_text">Deposit Amount (₹)</span> <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                   class="form-control bank-mono-input @error('amount') is-invalid @enderror"
                                   placeholder="0.00"
                                   value="{{ old('amount', $selectedLead?->expected_amount) }}" required
                                   oninput="calculateMaturityPreview()">
                            <small id="scheme_amount_hint" class="text-muted d-block mt-1" style="font-size: 0.74rem;"></small>
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label for="start_date" class="form-label small fw-medium mb-1">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                   class="form-control bank-mono-input @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', date('Y-m-d')) }}" required onchange="calculateMaturityPreview()">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-6 col-md-3" id="interest_rate_col">
                            <label for="interest_rate_display" class="form-label small fw-medium mb-1" id="interest_rate_label_text">Interest Rate (% p.a.)</label>
                            <input type="text" id="interest_rate_display" class="form-control bg-light bank-mono-input" readonly tabindex="-1" value="—">
                        </div>

                        <div class="col-sm-6 col-md-3" id="insurance_coverage_col" style="display: none;">
                            <label for="insurance_coverage_display" class="form-label small fw-medium mb-1">Insurance Coverage (₹)</label>
                            <input type="text" id="insurance_coverage_display" class="form-control bg-light bank-mono-input fw-semibold text-primary" readonly tabindex="-1" value="₹0.00">
                            <small id="insurance_coverage_hint" class="text-muted d-block mt-1" style="font-size: 0.74rem;">KSP Insurance Benefit</small>
                        </div>

                        <div class="col-sm-6 col-md-3" id="maturity_amount_col">
                            <label for="maturity_amount_display" class="form-label small fw-medium mb-1" id="maturity_amount_label_text">Maturity Amount (₹)</label>
                            <input type="text" id="maturity_amount_display" class="form-control bg-light bank-mono-input fw-semibold" readonly tabindex="-1" value="₹0.00">
                            <small id="maturity_amount_hint" class="text-muted d-block mt-1" style="font-size: 0.74rem;"></small>
                        </div>

                        @php
                            $defaultSeniorChecked = $selectedCustomer
                                && ! empty($selectedCustomer->date_of_birth)
                                && \Carbon\Carbon::parse($selectedCustomer->date_of_birth)->age >= 60;
                        @endphp
                        <div class="col-md-5 d-flex align-items-center pt-md-2" id="senior_citizen_col">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_senior_citizen" value="1" id="is_senior_citizen"
                                       {{ old('is_senior_citizen', $defaultSeniorChecked) ? 'checked' : '' }} onchange="calculateMaturityPreview()">
                                <label class="form-check-label fw-medium" for="is_senior_citizen">Senior Citizen</label>
                                <small id="senior_citizen_hint" class="text-muted d-block" style="font-size: 0.74rem;"></small>
                            </div>
                            @error('is_double')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-7">
                            <label for="notes" class="form-label small fw-medium mb-1">Notes (Optional)</label>
                            <input type="text" name="notes" id="notes"
                                   class="form-control @error('notes') is-invalid @enderror"
                                   placeholder="Remarks or notes"
                                   value="{{ old('notes') }}">
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- KSP Plan & Insurance Coverage Details Panel -->
                        <div class="col-12" id="ksp_details_panel" style="display: none;">
                            <div class="p-3 bg-light border">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-shield-check text-success"></i>
                                        <span class="fw-semibold small text-dark text-uppercase">KSP Plan Details &amp; Insurance Coverage</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle bank-mono-input" id="ksp_panel_insurance_badge">Insurance: ₹0</span>
                                        <span class="badge bg-white text-secondary border" id="ksp_panel_premature_badge">Premature Withdrawal: Not Allowed</span>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6 col-md-3">
                                        <div class="bg-white border p-2 h-100">
                                            <div class="text-muted" style="font-size: 0.72rem;">Annual Deposit &amp; Period</div>
                                            <div class="fw-semibold small text-dark bank-mono-input" id="ksp_panel_deposit">—</div>
                                            <div class="text-muted bank-mono-input" style="font-size: 0.72rem;" id="ksp_panel_total_deposit">Total: ₹0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="bg-white border p-2 h-100">
                                            <div class="text-muted" style="font-size: 0.72rem;">Insurance Coverage</div>
                                            <div class="fw-bold small text-primary bank-mono-input" id="ksp_panel_insurance">—</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">KSP Coverage Benefit</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="bg-white border p-2 h-100">
                                            <div class="text-muted" style="font-size: 0.72rem;">Return Window</div>
                                            <div class="fw-semibold small text-dark" id="ksp_panel_return_window">—</div>
                                            <div class="text-muted" style="font-size: 0.72rem;" id="ksp_panel_return_freq">—</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="bg-white border p-2 h-100">
                                            <div class="text-muted" style="font-size: 0.72rem;">Guaranteed Return</div>
                                            <div class="fw-bold small text-success bank-mono-input" id="ksp_panel_annual_return">—</div>
                                            <div class="text-muted bank-mono-input" style="font-size: 0.72rem;" id="ksp_panel_total_return">Total: ₹0.00</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="small text-muted mb-2" id="ksp_panel_summary_text"></div>
                                <div class="d-flex flex-wrap gap-1" id="ksp_panel_payout_chips"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary -->
        <div class="col-lg-4">
            <div class="card border" id="scheme_info_box">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold" id="preview_section_title">Summary</h6>
                    <span id="preview_type_badge" class="badge bg-light text-secondary border fw-normal">DEPOSIT</span>
                </div>

                <div class="p-3 bg-light border-bottom">
                    <div class="text-muted small" id="preview_maturity_label">Maturity Amount</div>
                    <div class="fs-4 fw-bold text-dark bank-mono-input" id="preview_maturity_amount">₹0.00</div>
                    <div class="text-muted" style="font-size: 0.75rem;" id="preview_rounded_maturity">Select scheme &amp; enter amount</div>
                </div>

                <div class="summary-list" id="preview_fd_rows">
                    <div class="summary-row" id="preview_principal_row">
                        <span class="summary-label" id="preview_principal_label">Principal Deposit</span>
                        <span class="summary-value" id="preview_principal">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" id="preview_rate_label">Rate &amp; Tenure</span>
                        <span class="summary-value">
                            <span id="preview_rate">—</span>
                            <span class="summary-sub" id="preview_tenure">—</span>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Interest / Yield</span>
                        <span class="summary-value text-success" id="preview_interest_gain">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Maturity Date</span>
                        <span class="summary-value">
                            <span id="preview_maturity">—</span>
                            <span class="summary-sub" id="preview_payout_mode_note">At Maturity</span>
                        </span>
                    </div>
                </div>

                <div class="summary-list" id="preview_mrp_rows" style="display: none;">
                    <div class="summary-section-title">Periodic Payout</div>
                    <div class="summary-row">
                        <span class="summary-label" id="preview_mrp_payout_label">Monthly Return</span>
                        <span class="summary-value text-success" id="preview_mrp_monthly_return">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Payout Schedule</span>
                        <span class="summary-value" id="preview_mrp_payout_count">—</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Interest</span>
                        <span class="summary-value" id="preview_mrp_total_returns">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Principal Refund</span>
                        <span class="summary-value" id="preview_mrp_principal_refund">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Benefit</span>
                        <span class="summary-value" id="preview_mrp_total_benefit">₹0.00</span>
                    </div>
                </div>

                <div class="summary-list" id="preview_rd_rows" style="display: none;">
                    <div class="summary-section-title">Installment Schedule</div>
                    <div class="summary-row">
                        <span class="summary-label" id="preview_installment_label">Monthly Installment</span>
                        <span class="summary-value" id="preview_installment">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" id="preview_num_installments_label">Installments</span>
                        <span class="summary-value" id="preview_num_installments">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" id="preview_total_principal_label">Total Principal</span>
                        <span class="summary-value" id="preview_total_principal">₹0.00</span>
                    </div>
                </div>

                <div class="summary-list" id="preview_ksp_rows" style="display: none;">
                    <div class="summary-section-title">KSP Plan &amp; Insurance</div>
                    <div class="summary-row">
                        <span class="summary-label">Insurance Coverage</span>
                        <span class="summary-value text-primary" id="preview_ksp_insurance">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Annual Deposit</span>
                        <span class="summary-value" id="preview_ksp_annual_deposit">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Deposit Period</span>
                        <span class="summary-value">
                            <span id="preview_ksp_deposit_period">—</span>
                            <span class="summary-sub" id="preview_ksp_deposit_end">—</span>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Deposit</span>
                        <span class="summary-value" id="preview_ksp_total_deposit">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Return Window</span>
                        <span class="summary-value">
                            <span id="preview_ksp_return_window">—</span>
                            <span class="summary-sub" id="preview_ksp_return_schedule">—</span>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Annual Return</span>
                        <span class="summary-value text-success" id="preview_ksp_annual_return">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Return Payout</span>
                        <span class="summary-value text-success" id="preview_ksp_total_return">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Net Benefit / Gain</span>
                        <span class="summary-value text-success" id="preview_ksp_net_gain">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Premature Withdrawal</span>
                        <span class="summary-value" id="preview_ksp_premature">Not Allowed</span>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-grid gap-2">
                    <button type="submit" name="status_action" value="active" class="btn btn-dark">
                        Save &amp; Activate
                    </button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="status_action" value="pending" class="btn btn-outline-dark w-100">
                            Submit for Approval
                        </button>
                        <a href="{{ route('admin.deposits.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
const schemeRatesUrlTemplate = @json(route('admin.deposit-schemes.rates', ['depositScheme' => '__SCHEME_ID__']));
const hasValidationOldInput = @json(old('_token') !== null);
const initialOldTenure = @json(old('tenure'));
const initialOldTenureUnit = @json(old('tenure_unit'));

let currentSchemeTenureRates = [];
let activeSchemeFetchId = 0;

function formatInr(val) {
    return '₹' + (Number(val) || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const leadSelect = document.getElementById('lead_id');
    const agentSelect = document.getElementById('agent_id');
    const schemeSelect = document.getElementById('deposit_scheme_id');
    const amountInput = document.getElementById('amount');

    if (amountInput) {
        if (amountInput.value) {
            amountInput.dataset.userCustomized = '1';
        }
        amountInput.addEventListener('input', function () {
            amountInput.dataset.userCustomized = amountInput.value ? '1' : '0';
        });
    }

    if (leadSelect && leadSelect.value) {
        onLeadSelect(leadSelect, true);
    } else if (agentSelect && agentSelect.value) {
        onAgentSelect(agentSelect);
    }

    if (schemeSelect && schemeSelect.value) {
        onSchemeChange(schemeSelect, true);
    } else {
        resetSchemeFields();
    }
});

function syncDoubleControls(isDouble) {
    const doubleHidden = document.getElementById('is_double_hidden');
    if (doubleHidden) {
        doubleHidden.value = isDouble ? '1' : '0';
    }
}

function getSelectedSchemeDoubleState(schOpt) {
    if (!schOpt || !schOpt.value) return false;
    return schOpt.getAttribute('data-is-double') === '1'
        || (schOpt.getAttribute('data-type') || '').toUpperCase() === 'DOUBLE';
}

function resolveSchemeProfile(schemeData, opt) {
    const schemeType = String(schemeData?.scheme_type || opt?.getAttribute('data-type') || 'FD').toUpperCase();
    const schemeName = String(schemeData?.scheme_name || opt?.getAttribute('data-scheme-name') || opt?.text || '').trim();
    const schemeCode = String(schemeData?.scheme_code || opt?.getAttribute('data-scheme-code') || '').trim().toUpperCase();
    const upperName = schemeName.toUpperCase();

    const isKsp = Boolean(schemeData?.is_ksp)
        || opt?.getAttribute('data-is-ksp') === '1'
        || schemeCode === 'KSP';

    let kspConfig = schemeData?.ksp_configuration || null;
    if (!kspConfig && opt?.getAttribute('data-ksp-config')) {
        try {
            kspConfig = JSON.parse(opt.getAttribute('data-ksp-config'));
        } catch (e) {
            kspConfig = null;
        }
    }

    const isDouble = !isKsp && (
        Boolean(schemeData?.is_double)
        || getSelectedSchemeDoubleState(opt)
        || schemeType === 'DOUBLE'
    );

    const isMrp = !isKsp && !isDouble && (
        Boolean(schemeData?.is_mrp)
        || opt?.getAttribute('data-is-mrp') === '1'
        || schemeType === 'MIS'
        || upperName.includes('MRP')
        || upperName.includes('MONTHLY RETURN')
    );

    let collectionFreq = String(schemeData?.collection_frequency || opt?.getAttribute('data-collection-frequency') || '').toUpperCase();
    if (!collectionFreq) {
        if (schemeType === 'RD' || schemeType === 'RECURRING') {
            collectionFreq = 'MONTHLY';
        } else if (schemeType === 'DD' || schemeType === 'DAILY') {
            collectionFreq = 'DAILY';
        } else {
            collectionFreq = 'ONE_TIME';
        }
    }

    let payoutFreq = String(schemeData?.interest_payout_frequency || opt?.getAttribute('data-payout-frequency') || '').toUpperCase();
    if (!payoutFreq) {
        payoutFreq = isMrp ? 'MONTHLY' : 'AT_MATURITY';
    }

    let mode = 'FD';
    if (isKsp) {
        mode = 'KSP';
    } else if (isDouble) {
        mode = 'DOUBLE';
    } else if (isMrp) {
        mode = 'MRP';
    } else if (schemeType === 'RD' || schemeType === 'RECURRING' || ((collectionFreq === 'MONTHLY' || collectionFreq === 'WEEKLY') && schemeType !== 'MIS')) {
        mode = 'RD';
    } else if (schemeType === 'DD' || schemeType === 'DAILY' || collectionFreq === 'DAILY') {
        mode = 'DD';
    } else if (['MONTHLY', 'QUARTERLY', 'ANNUALLY'].includes(payoutFreq)) {
        mode = 'PERIODIC_FD';
    }

    const defaultCollLabels = {
        ONE_TIME: 'One-Time / Lump Sum',
        MONTHLY: 'Monthly Installment',
        WEEKLY: 'Weekly Installment',
        DAILY: 'Daily Collection',
        QUARTERLY: 'Quarterly',
    };
    const defaultPayoutLabels = {
        AT_MATURITY: 'At Maturity',
        MONTHLY: 'Monthly Return',
        QUARTERLY: 'Quarterly Payout',
        ANNUALLY: 'Annual Payout',
    };

    const collectionLabel = schemeData?.collection_frequency_label
        || opt?.getAttribute('data-collection-label')
        || defaultCollLabels[collectionFreq]
        || collectionFreq.replace(/_/g, ' ');

    const payoutLabel = isDouble
        ? '2x Principal at Maturity'
        : (schemeData?.interest_payout_frequency_label
            || opt?.getAttribute('data-payout-label')
            || defaultPayoutLabels[payoutFreq]
            || payoutFreq.replace(/_/g, ' '));

    const mrpMethod = String(
        schemeData?.mrp_calculation_method
        || schemeData?.mrp_configuration?.calculation_method
        || opt?.getAttribute('data-mrp-method')
        || 'STANDARD'
    ).toUpperCase();

    return {
        schemeType,
        schemeName,
        schemeCode,
        collectionFreq,
        collectionLabel,
        payoutFreq,
        payoutLabel,
        isDouble,
        isMrp,
        isKsp,
        kspConfig,
        isPeriodicPayout: !isKsp && (isMrp || ['MONTHLY', 'QUARTERLY', 'ANNUALLY'].includes(payoutFreq)),
        mrpMethod,
        mode,
    };
}

function formatOrdinalYear(yr) {
    const n = parseInt(yr, 10);
    if (isNaN(n)) return '';
    const mod100 = n % 100;
    if (mod100 >= 11 && mod100 <= 13) return n + 'th Year';
    switch (n % 10) {
        case 1: return n + 'st Year';
        case 2: return n + 'nd Year';
        case 3: return n + 'rd Year';
        default: return n + 'th Year';
    }
}

function formatKspWindow(startYr, endYr) {
    if (endYr <= startYr) return formatOrdinalYear(startYr);
    if (endYr === startYr + 1) return `${formatOrdinalYear(startYr)} & ${formatOrdinalYear(endYr)}`;
    return `${formatOrdinalYear(startYr)} to ${formatOrdinalYear(endYr)}`;
}

function formatKspShortWindow(startYr, endYr) {
    if (endYr <= startYr) return formatOrdinalYear(startYr).replace(' Year', ' Yr');
    if (endYr === startYr + 1) return `${startYr} & ${endYr} Yr`;
    return `Yr ${startYr}–${endYr}`;
}

function resetVoucherPreview(keepRate = false) {
    const sectionTitle = document.getElementById('preview_section_title');
    const typeBadge = document.getElementById('preview_type_badge');
    const pMaturityLabel = document.getElementById('preview_maturity_label');
    const pMaturityAmt = document.getElementById('preview_maturity_amount');
    const pRoundedMaturity = document.getElementById('preview_rounded_maturity');
    const fdRows = document.getElementById('preview_fd_rows');
    const pPrincipalRow = document.getElementById('preview_principal_row');
    const pPrincipalLabel = document.getElementById('preview_principal_label');
    const pPrincipal = document.getElementById('preview_principal');
    const pRate = document.getElementById('preview_rate');
    const pTenure = document.getElementById('preview_tenure');
    const pInterestGain = document.getElementById('preview_interest_gain');
    const pMaturity = document.getElementById('preview_maturity');
    const pPayoutNote = document.getElementById('preview_payout_mode_note');
    const mrpRows = document.getElementById('preview_mrp_rows');
    const rdRows = document.getElementById('preview_rd_rows');
    const kspRows = document.getElementById('preview_ksp_rows');

    if (!keepRate) {
        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (typeBadge) {
            typeBadge.innerText = 'DEPOSIT';
            typeBadge.className = 'badge bg-light text-secondary border fw-normal';
        }
        if (pPrincipalLabel) pPrincipalLabel.innerText = 'Principal Deposit';
        if (pRate) pRate.innerText = '—';
        if (pTenure) pTenure.innerText = '—';
    }
    if (fdRows) fdRows.style.display = 'flex';
    if (pPrincipalRow) pPrincipalRow.style.display = 'flex';
    if (pMaturityLabel) pMaturityLabel.innerText = 'Maturity Amount';
    if (pMaturityAmt) pMaturityAmt.innerText = '₹0.00';
    if (pRoundedMaturity) pRoundedMaturity.innerText = keepRate ? 'Enter deposit amount to calculate' : 'Select scheme & enter amount';
    if (pPrincipal) pPrincipal.innerText = '₹0.00';
    if (pInterestGain) pInterestGain.innerText = '₹0.00';
    if (pMaturity) pMaturity.innerText = '—';
    if (pPayoutNote) pPayoutNote.innerText = 'At Maturity';
    if (mrpRows) mrpRows.style.display = 'none';
    if (rdRows) rdRows.style.display = 'none';
    if (kspRows) kspRows.style.display = 'none';
}

function resetSchemeFields() {
    activeSchemeFetchId++;
    currentSchemeTenureRates = [];

    const tierSelect = document.getElementById('tenure_tier_select');
    const tierHint = document.getElementById('tenure_tier_hint');
    const tenureInput = document.getElementById('tenure');
    const unitInput = document.getElementById('tenure_unit');
    const payoutFreqInput = document.getElementById('payout_frequency');
    const hint = document.getElementById('scheme_amount_hint');
    const amountLabelText = document.getElementById('amount_label_text');
    const rateLabelText = document.getElementById('interest_rate_label_text');
    const maturityLabelText = document.getElementById('maturity_amount_label_text');
    const interestDisplay = document.getElementById('interest_rate_display');
    const insuranceDisplay = document.getElementById('insurance_coverage_display');
    const interestBadge = document.getElementById('interest_type_badge');
    const seniorSwitch = document.getElementById('is_senior_citizen');
    const seniorHint = document.getElementById('senior_citizen_hint');
    const maturityDisplay = document.getElementById('maturity_amount_display');
    const maturityHint = document.getElementById('maturity_amount_hint');

    const interestCol = document.getElementById('interest_rate_col');
    const insuranceCol = document.getElementById('insurance_coverage_col');
    const seniorCol = document.getElementById('senior_citizen_col');
    const kspPanel = document.getElementById('ksp_details_panel');

    if (tierSelect) {
        tierSelect.innerHTML = '<option value="">Select scheme first</option>';
        tierSelect.classList.remove('bg-light');
        tierSelect.style.pointerEvents = '';
        tierSelect.style.opacity = '';
    }
    if (tierHint) tierHint.innerText = '';
    if (interestCol) interestCol.style.display = '';
    if (insuranceCol) insuranceCol.style.display = 'none';
    if (kspPanel) kspPanel.style.display = 'none';
    if (seniorCol) seniorCol.style.opacity = '';
    if (tenureInput) tenureInput.value = '';
    if (unitInput) unitInput.value = '';
    if (payoutFreqInput) payoutFreqInput.value = 'AT_MATURITY';
    if (amountLabelText) amountLabelText.innerText = 'Deposit Amount (₹)';
    if (rateLabelText) rateLabelText.innerText = 'Interest Rate (% p.a.)';
    if (maturityLabelText) maturityLabelText.innerText = 'Maturity Amount (₹)';
    if (hint) hint.innerText = '';
    if (insuranceDisplay) insuranceDisplay.value = '₹0.00';
    if (maturityDisplay) maturityDisplay.value = '₹0.00';
    if (maturityHint) maturityHint.innerText = '';

    resetVoucherPreview(false);
    syncDoubleControls(false);

    if (interestDisplay) interestDisplay.value = '—';
    if (interestBadge) {
        interestBadge.textContent = 'Standard';
        interestBadge.className = 'badge bg-light text-secondary border fw-normal';
    }
    if (seniorSwitch) {
        seniorSwitch.disabled = false;
    }
    if (seniorHint) {
        seniorHint.innerText = '';
    }
}

function onLeadSelect(selectEl, isInit = false) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;

    const name = opt.getAttribute('data-name') || '';
    const mobile = opt.getAttribute('data-mobile') || '';
    const agentId = opt.getAttribute('data-agent-id') || '';
    const agentName = opt.getAttribute('data-agent-name') || '';
    const branchId = opt.getAttribute('data-branch-id') || '';
    const schemeId = opt.getAttribute('data-scheme-id') || '';
    const amount = opt.getAttribute('data-amount') || '';
    const isSenior = opt.getAttribute('data-is-senior') === '1';

    const nameInput = document.getElementById('manual_customer_name');
    const mobileInput = document.getElementById('manual_customer_mobile');
    if (nameInput && name && (!isInit || !hasValidationOldInput || !nameInput.value)) {
        nameInput.value = name;
    }
    if (mobileInput && mobile && (!isInit || !hasValidationOldInput || !mobileInput.value)) {
        mobileInput.value = mobile;
    }

    const seniorSwitch = document.getElementById('is_senior_citizen');
    if (seniorSwitch && !seniorSwitch.disabled && (!isInit || !hasValidationOldInput)) {
        seniorSwitch.checked = isSenior;
    }

    const branchInput = document.getElementById('branch_id');
    if (branchInput && branchId && (!isInit || !hasValidationOldInput || !branchInput.value)) {
        branchInput.value = branchId;
    }

    const agentSelect = document.getElementById('agent_id');
    if (agentSelect && agentId && (!isInit || !hasValidationOldInput || !agentSelect.value)) {
        let agentOpt = agentSelect.querySelector(`option[value="${agentId}"]`);
        if (!agentOpt && agentName) {
            agentOpt = document.createElement('option');
            agentOpt.value = agentId;
            agentOpt.textContent = agentName;
            if (branchId) agentOpt.setAttribute('data-branch-id', branchId);
            agentSelect.appendChild(agentOpt);
        }
        agentSelect.value = agentId;
        onAgentSelect(agentSelect);
    }

    const amountInput = document.getElementById('amount');
    if (amountInput && amount && (!isInit || !hasValidationOldInput) && (!amountInput.value || amountInput.dataset.userCustomized !== '1')) {
        amountInput.value = parseFloat(amount).toFixed(2);
        amountInput.dataset.userCustomized = '1';
    }

    const schemeSelect = document.getElementById('deposit_scheme_id');
    if (schemeSelect && schemeId && !schemeSelect.value) {
        schemeSelect.value = schemeId;
        if (!isInit) {
            onSchemeChange(schemeSelect, false);
        }
    } else if (!isInit) {
        calculateMaturityPreview();
    }
}

function onAgentSelect(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;

    const branchId = opt.getAttribute('data-branch-id') || '';
    const branchInput = document.getElementById('branch_id');
    if (branchInput && branchId) {
        branchInput.value = branchId;
    }
}

function applySchemeData(opt, schemeData, options = {}) {
    const isInit = Boolean(options.isInit);
    const fromFetch = Boolean(options.fromFetch);

    const profile = resolveSchemeProfile(schemeData, opt);
    const isSchDouble = profile.isDouble;

    syncDoubleControls(isSchDouble);

    const payoutFreqInput = document.getElementById('payout_frequency');
    if (payoutFreqInput) {
        payoutFreqInput.value = profile.payoutFreq;
    }
    if (opt) {
        opt.setAttribute('data-type', profile.schemeType);
        opt.setAttribute('data-collection-frequency', profile.collectionFreq);
        opt.setAttribute('data-collection-label', profile.collectionLabel);
        opt.setAttribute('data-payout-frequency', profile.payoutFreq);
        opt.setAttribute('data-payout-label', profile.payoutLabel);
        opt.setAttribute('data-is-double', profile.isDouble ? '1' : '0');
        opt.setAttribute('data-is-mrp', profile.isMrp ? '1' : '0');
        opt.setAttribute('data-is-ksp', profile.isKsp ? '1' : '0');
        opt.setAttribute('data-ksp-config', JSON.stringify(profile.kspConfig || null));
        opt.setAttribute('data-mrp-method', profile.mrpMethod);
    }

    const schemeSeniorExtra = parseFloat(schemeData.senior_citizen_extra_rate) || 0;

    let tierRates = Array.isArray(schemeData.rates) ? schemeData.rates : [];
    if (tierRates.length === 0 && profile.isMrp && schemeData.mrp_configuration && Array.isArray(schemeData.mrp_configuration.rates)) {
        tierRates = schemeData.mrp_configuration.rates.map(mr => ({
            id: null,
            tenure: parseInt(mr.year, 10) || 1,
            tenure_unit: 'YEARS',
            interest_rate: parseFloat(mr.general_rate) || 0,
            senior_citizen_extra_rate: Math.max(0, (parseFloat(mr.senior_rate) || 0) - (parseFloat(mr.general_rate) || 0)),
        }));
    }
    currentSchemeTenureRates = tierRates;

    const kspPeriodForTier = parseInt(profile.kspConfig?.deposit_period ?? schemeData.default_tenure ?? 10, 10) || 10;
    const kspStartForTier = parseInt(profile.kspConfig?.return_start_year ?? 11, 10);
    const kspEndForTier = parseInt(profile.kspConfig?.return_end_year ?? (10 + kspPeriodForTier), 10);
    const kspTierWinShort = formatKspShortWindow(kspStartForTier, kspEndForTier);
    const kspTierWinFull = formatKspWindow(kspStartForTier, kspEndForTier);

    const defaultTenure = profile.isKsp ? kspPeriodForTier : (parseInt(schemeData.default_tenure, 10) || 12);
    const defaultUnit = profile.isKsp ? 'YEARS' : ((schemeData.default_tenure_unit || 'MONTHS').toUpperCase());
    const defaultRate = parseFloat(schemeData.default_interest_rate) || 0;

    const tierSelect = document.getElementById('tenure_tier_select');
    const tierHint = document.getElementById('tenure_tier_hint');
    if (tierSelect) {
        const currentSelectedTier = fromFetch ? tierSelect.value : null;
        const targetTenure = isInit && initialOldTenure ? parseInt(initialOldTenure, 10) : null;
        const targetUnit = isInit && initialOldTenureUnit ? String(initialOldTenureUnit).toUpperCase() : null;

        tierSelect.innerHTML = '';
        let matchedSelection = false;

        if (currentSchemeTenureRates.length > 0 && !profile.isKsp) {
            currentSchemeTenureRates.forEach(tier => {
                const tierTenure = parseInt(tier.tenure, 10);
                const tierUnit = String(tier.tenure_unit || 'MONTHS').toUpperCase();
                const tierRate = parseFloat(tier.interest_rate) || 0;
                const tierSeniorExtra = parseFloat(tier.senior_citizen_extra_rate) || schemeSeniorExtra || 0;

                const optEl = document.createElement('option');
                optEl.value = `${tierTenure}_${tierUnit}`;
                optEl.dataset.tenure = tierTenure;
                optEl.dataset.unit = tierUnit;
                optEl.dataset.rate = tierRate;
                optEl.dataset.seniorExtra = tierSeniorExtra;

                let label;
                if (isSchDouble) {
                    label = `${tierTenure} ${tierUnit.toLowerCase()} — Double (2x Payout)`;
                } else {
                    label = `${tierTenure} ${tierUnit.toLowerCase()} — ${tierRate.toFixed(2)}% p.a.`;
                    if (tierSeniorExtra > 0) {
                        label += ` (+${tierSeniorExtra.toFixed(2)}% Senior)`;
                    }
                }
                optEl.textContent = label;

                if (currentSelectedTier && optEl.value === currentSelectedTier) {
                    optEl.selected = true;
                    matchedSelection = true;
                } else if (!matchedSelection && targetTenure && tierTenure === targetTenure && (!targetUnit || tierUnit === targetUnit)) {
                    optEl.selected = true;
                    matchedSelection = true;
                }

                tierSelect.appendChild(optEl);
            });
        }

        const hasBaseInTiers = !profile.isKsp && currentSchemeTenureRates.some(
            t => parseInt(t.tenure, 10) === defaultTenure && String(t.tenure_unit || '').toUpperCase() === defaultUnit
        );
        if (!hasBaseInTiers) {
            const defOpt = document.createElement('option');
            defOpt.value = `${defaultTenure}_${defaultUnit}`;
            defOpt.dataset.tenure = defaultTenure;
            defOpt.dataset.unit = defaultUnit;
            defOpt.dataset.rate = defaultRate;
            defOpt.dataset.seniorExtra = schemeSeniorExtra;

            let label;
            if (profile.isKsp) {
                label = `${defaultTenure} ${defaultTenure === 1 ? 'Year' : 'Years'} Deposit — Return: ${kspTierWinShort}`;
            } else if (isSchDouble) {
                label = `${defaultTenure} ${defaultUnit.toLowerCase()} — Double (2x Payout)`;
            } else {
                label = `${defaultTenure} ${defaultUnit.toLowerCase()} — ${defaultRate.toFixed(2)}% p.a.`;
                if (schemeSeniorExtra > 0) {
                    label += ` (+${schemeSeniorExtra.toFixed(2)}% Senior)`;
                }
            }
            defOpt.textContent = label;

            if (currentSelectedTier && defOpt.value === currentSelectedTier) {
                defOpt.selected = true;
                matchedSelection = true;
            } else if (!matchedSelection && targetTenure && defaultTenure === targetTenure && (!targetUnit || defaultUnit === targetUnit)) {
                defOpt.selected = true;
                matchedSelection = true;
            }

            tierSelect.appendChild(defOpt);
        }

        onTenureTierChange(tierSelect);
    }

    if (tierHint) {
        tierHint.textContent = profile.isKsp
            ? `Deposit Period: ${kspPeriodForTier} ${kspPeriodForTier === 1 ? 'Year' : 'Years'} | Return Window: ${kspTierWinFull}`
            : '';
    }

    const min = parseFloat(schemeData.min_amount) || 0;
    const max = parseFloat(schemeData.max_amount) || 0;
    const hint = document.getElementById('scheme_amount_hint');
    const amountInput = document.getElementById('amount');
    const amountLabelText = document.getElementById('amount_label_text');
    const rateLabelText = document.getElementById('interest_rate_label_text');
    const maturityLabelText = document.getElementById('maturity_amount_label_text');

    const interestCol = document.getElementById('interest_rate_col');
    const insuranceCol = document.getElementById('insurance_coverage_col');
    const seniorCol = document.getElementById('senior_citizen_col');
    const tierSelectEl = document.getElementById('tenure_tier_select');
    const kspPanel = document.getElementById('ksp_details_panel');

    if (profile.isKsp) {
        if (interestCol) interestCol.style.display = 'none';
        if (insuranceCol) insuranceCol.style.display = '';
        if (kspPanel) kspPanel.style.display = '';
        if (seniorCol) seniorCol.style.opacity = '0.5';
        if (tierSelectEl) {
            tierSelectEl.classList.add('bg-light');
            tierSelectEl.style.pointerEvents = 'none';
            tierSelectEl.style.opacity = '0.65';
        }
    } else {
        if (interestCol) interestCol.style.display = '';
        if (insuranceCol) insuranceCol.style.display = 'none';
        if (kspPanel) kspPanel.style.display = 'none';
        if (seniorCol) seniorCol.style.opacity = '';
        if (tierSelectEl) {
            tierSelectEl.classList.remove('bg-light');
            tierSelectEl.style.pointerEvents = '';
            tierSelectEl.style.opacity = '';
        }
    }

    if (amountLabelText) {
        if (profile.isKsp) {
            amountLabelText.textContent = 'Annual Deposit Amount (₹)';
        } else if (profile.isMrp) {
            amountLabelText.textContent = 'Contribution Amount (₹)';
        } else if (profile.mode === 'RD') {
            amountLabelText.textContent = 'Monthly Installment (₹)';
        } else if (profile.mode === 'DD') {
            amountLabelText.textContent = 'Daily Amount (₹)';
        } else {
            amountLabelText.textContent = 'Deposit Amount (₹)';
        }
    }

    if (rateLabelText) {
        rateLabelText.textContent = profile.isMrp
            ? 'Return Rate (% p.a.)'
            : 'Interest Rate (% p.a.)';
    }

    if (maturityLabelText) {
        if (profile.isKsp) {
            maturityLabelText.textContent = `Annual Return (${kspTierWinShort})`;
        } else if (profile.isMrp || profile.payoutFreq === 'MONTHLY') {
            maturityLabelText.textContent = 'Monthly Return (₹)';
        } else if (profile.payoutFreq === 'QUARTERLY') {
            maturityLabelText.textContent = 'Quarterly Return (₹)';
        } else if (profile.payoutFreq === 'ANNUALLY') {
            maturityLabelText.textContent = 'Annual Return (₹)';
        } else if (profile.isDouble) {
            maturityLabelText.textContent = 'Maturity Amount (2x) (₹)';
        } else {
            maturityLabelText.textContent = 'Maturity Amount (₹)';
        }
    }

    if (hint) {
        if (profile.isKsp) {
            const baseAnnual = parseFloat(profile.kspConfig?.annual_deposit_amount ?? min ?? 15000);
            let msg = `Base: ${formatInr(baseAnnual)}/yr × ${kspPeriodForTier} ${kspPeriodForTier === 1 ? 'Yr' : 'Yrs'} (Min: ₹${min.toLocaleString('en-IN')})`;
            if (max > 0) msg += ` | Max: ₹${max.toLocaleString('en-IN')}`;
            hint.innerText = msg;
        } else {
            let msg = `Min: ₹${min.toLocaleString('en-IN')}`;
            if (max > 0) msg += ` | Max: ₹${max.toLocaleString('en-IN')}`;
            hint.innerText = msg;
        }
    }

    if (amountInput) {
        const currentAmt = parseFloat(amountInput.value) || 0;
        const shouldAutoAssign = !amountInput.value
            || currentAmt < min
            || (!fromFetch && amountInput.dataset.userCustomized !== '1');

        if (shouldAutoAssign && min > 0) {
            amountInput.value = min.toFixed(2);
            amountInput.dataset.userCustomized = '0';
        }
    }

    calculateMaturityPreview();
}

function onSchemeChange(selectEl, isInit = false) {
    const opt = selectEl.options[selectEl.selectedIndex];

    if (!opt || !opt.value) {
        resetSchemeFields();
        return;
    }

    let embeddedRates = [];
    try {
        embeddedRates = JSON.parse(opt.getAttribute('data-tenure-rates') || '[]');
    } catch (e) {
        embeddedRates = [];
    }

    let embeddedMrpRates = [];
    try {
        embeddedMrpRates = JSON.parse(opt.getAttribute('data-mrp-rates') || '[]');
    } catch (e) {
        embeddedMrpRates = [];
    }

    let embeddedKspConfig = null;
    try {
        embeddedKspConfig = JSON.parse(opt.getAttribute('data-ksp-config') || 'null');
    } catch (e) {
        embeddedKspConfig = null;
    }

    const initialData = {
        scheme_id: opt.value,
        scheme_name: opt.getAttribute('data-scheme-name') || opt.textContent,
        scheme_code: opt.getAttribute('data-scheme-code') || '',
        scheme_type: opt.getAttribute('data-type') || 'FD',
        collection_frequency: opt.getAttribute('data-collection-frequency') || '',
        collection_frequency_label: opt.getAttribute('data-collection-label') || '',
        interest_payout_frequency: opt.getAttribute('data-payout-frequency') || '',
        interest_payout_frequency_label: opt.getAttribute('data-payout-label') || '',
        is_double: getSelectedSchemeDoubleState(opt),
        is_mrp: opt.getAttribute('data-is-mrp') === '1',
        is_ksp: opt.getAttribute('data-is-ksp') === '1',
        ksp_configuration: embeddedKspConfig,
        mrp_calculation_method: opt.getAttribute('data-mrp-method') || 'STANDARD',
        mrp_configuration: embeddedMrpRates.length > 0 ? { is_enabled: true, rates: embeddedMrpRates } : null,
        min_amount: opt.getAttribute('data-min') || 0,
        max_amount: opt.getAttribute('data-max') || null,
        default_tenure: opt.getAttribute('data-tenure') || 12,
        default_tenure_unit: opt.getAttribute('data-tenure-unit') || 'MONTHS',
        default_interest_rate: opt.getAttribute('data-rate') || 0,
        senior_citizen_extra_rate: opt.getAttribute('data-senior-extra') || 0,
        rates: embeddedRates,
    };

    applySchemeData(opt, initialData, { isInit, fromFetch: false });

    const fetchId = ++activeSchemeFetchId;
    const url = schemeRatesUrlTemplate.replace('__SCHEME_ID__', encodeURIComponent(opt.value));

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(res => (res.ok ? res.json() : null))
        .then(data => {
            if (!data || !data.success) return;
            if (fetchId !== activeSchemeFetchId) return;
            if (String(selectEl.value) !== String(opt.value)) return;

            if (data.scheme_name) opt.setAttribute('data-scheme-name', data.scheme_name);
            if (data.scheme_code) opt.setAttribute('data-scheme-code', data.scheme_code);
            opt.setAttribute('data-type', data.scheme_type || 'FD');
            opt.setAttribute('data-collection-frequency', data.collection_frequency || 'ONE_TIME');
            if (data.collection_frequency_label) opt.setAttribute('data-collection-label', data.collection_frequency_label);
            opt.setAttribute('data-payout-frequency', data.interest_payout_frequency || 'AT_MATURITY');
            if (data.interest_payout_frequency_label) opt.setAttribute('data-payout-label', data.interest_payout_frequency_label);
            opt.setAttribute('data-is-double', data.is_double ? '1' : '0');
            opt.setAttribute('data-is-mrp', data.is_mrp ? '1' : '0');
            opt.setAttribute('data-is-ksp', data.is_ksp ? '1' : '0');
            opt.setAttribute('data-ksp-config', JSON.stringify(data.ksp_configuration || null));
            if (data.mrp_calculation_method) opt.setAttribute('data-mrp-method', data.mrp_calculation_method);
            opt.setAttribute('data-rate', data.default_interest_rate ?? 0);
            opt.setAttribute('data-tenure', data.default_tenure ?? 12);
            opt.setAttribute('data-tenure-unit', data.default_tenure_unit ?? 'MONTHS');
            opt.setAttribute('data-senior-extra', data.senior_citizen_extra_rate ?? 0);
            if (data.min_amount !== undefined && data.min_amount !== null) {
                opt.setAttribute('data-min', data.min_amount);
            }
            opt.setAttribute('data-max', data.max_amount !== undefined && data.max_amount !== null ? data.max_amount : '');
            opt.setAttribute('data-tenure-rates', JSON.stringify(data.rates || []));
            if (data.mrp_configuration && Array.isArray(data.mrp_configuration.rates)) {
                opt.setAttribute('data-mrp-rates', JSON.stringify(data.mrp_configuration.rates));
            }

            applySchemeData(opt, data, { isInit: false, fromFetch: true });
        })
        .catch(() => {});
}

function onTenureTierChange(selectEl) {
    const opt = selectEl && selectEl.selectedIndex >= 0 ? selectEl.options[selectEl.selectedIndex] : null;

    if (opt && opt.value) {
        const tenureInput = document.getElementById('tenure');
        const unitInput = document.getElementById('tenure_unit');
        if (tenureInput) tenureInput.value = opt.dataset.tenure || '';
        if (unitInput) unitInput.value = opt.dataset.unit || '';
    }
    calculateMaturityPreview();
}

function calculateMaturityPreview() {
    const schemeSelect = document.getElementById('deposit_scheme_id');
    const amountInput = document.getElementById('amount');
    const dateInput = document.getElementById('start_date');
    const tierSelect = document.getElementById('tenure_tier_select');

    if (!schemeSelect || !schemeSelect.value) {
        resetVoucherPreview(false);
        return;
    }

    const schOpt = schemeSelect.options[schemeSelect.selectedIndex];
    const profile = resolveSchemeProfile({}, schOpt);
    const tierOpt = tierSelect && tierSelect.selectedIndex >= 0 ? tierSelect.options[tierSelect.selectedIndex] : null;
    const hasValidTier = Boolean(tierOpt && tierOpt.value !== '');

    const baseRate = hasValidTier
        ? (parseFloat(tierOpt.dataset.rate) || 0)
        : (parseFloat(schOpt.getAttribute('data-rate')) || 0);
    const schemeSeniorExtra = parseFloat(schOpt.getAttribute('data-senior-extra')) || 0;
    const tierSeniorExtra = hasValidTier
        ? (parseFloat(tierOpt.dataset.seniorExtra) || schemeSeniorExtra)
        : schemeSeniorExtra;

    const tenure = hasValidTier
        ? (parseInt(tierOpt.dataset.tenure, 10) || 12)
        : (parseInt(schOpt.getAttribute('data-tenure'), 10) || 12);
    const unit = hasValidTier
        ? ((tierOpt.dataset.unit || 'MONTHS').toUpperCase())
        : ((schOpt.getAttribute('data-tenure-unit') || 'MONTHS').toUpperCase());

    const isDouble = profile.isDouble;
    syncDoubleControls(isDouble);

    const seniorSwitch = document.getElementById('is_senior_citizen');
    const seniorHint = document.getElementById('senior_citizen_hint');
    const interestDisplay = document.getElementById('interest_rate_display');
    const insuranceDisplay = document.getElementById('insurance_coverage_display');
    const insuranceHint = document.getElementById('insurance_coverage_hint');
    const interestBadge = document.getElementById('interest_type_badge');

    const fdRows = document.getElementById('preview_fd_rows');
    const kspRows = document.getElementById('preview_ksp_rows');
    const kspPanel = document.getElementById('ksp_details_panel');
    const sectionTitle = document.getElementById('preview_section_title');
    const typeBadge = document.getElementById('preview_type_badge');
    const pPrincipalLabel = document.getElementById('preview_principal_label');
    const pPrincipal = document.getElementById('preview_principal');
    const pRateLabel = document.getElementById('preview_rate_label');
    const pRate = document.getElementById('preview_rate');
    const pTenure = document.getElementById('preview_tenure');
    const pMaturity = document.getElementById('preview_maturity');
    const pPayoutNote = document.getElementById('preview_payout_mode_note');
    const pMaturityLabel = document.getElementById('preview_maturity_label');
    const pMaturityAmt = document.getElementById('preview_maturity_amount');
    const pInterestGain = document.getElementById('preview_interest_gain');
    const pRoundedMaturity = document.getElementById('preview_rounded_maturity');
    const maturityDisplay = document.getElementById('maturity_amount_display');
    const maturityHint = document.getElementById('maturity_amount_hint');

    let effectiveRate = 0;
    let isSeniorCitizen = false;
    const amount = amountInput && amountInput.value ? (parseFloat(amountInput.value) || 0) : 0;
    const startDateVal = dateInput && dateInput.value ? dateInput.value : new Date().toISOString().split('T')[0];
    const dateParts = startDateVal.split('-').map(Number);
    const startDate = dateParts.length === 3
        ? new Date(dateParts[0], dateParts[1] - 1, dateParts[2])
        : new Date(startDateVal);

    if (profile.isKsp) {
        if (seniorSwitch) {
            seniorSwitch.checked = false;
            seniorSwitch.disabled = true;
        }
        if (seniorHint) {
            seniorHint.innerText = 'Not applicable for KSP plan';
        }
        const kspAnnualDeposit = parseFloat(profile.kspConfig?.annual_deposit_amount ?? 15000);
        const kspDepositPeriod = Math.max(1, parseInt(profile.kspConfig?.deposit_period ?? tenure ?? 10, 10));
        const kspInsurance = parseFloat(profile.kspConfig?.insurance_coverage ?? 300000);
        const kspStartYr = parseInt(profile.kspConfig?.return_start_year ?? 11, 10);
        const kspEndYr = Math.max(kspStartYr, parseInt(profile.kspConfig?.return_end_year ?? (10 + kspDepositPeriod), 10));
        const kspAnnualReturn = parseFloat(profile.kspConfig?.annual_return_amount ?? 45000);
        const kspFreq = String(profile.kspConfig?.return_frequency ?? 'Yearly');
        const kspPremature = String(profile.kspConfig?.premature_withdrawal ?? 'Not Allowed');
        const returnYearsCount = Math.max(1, kspEndYr - kspStartYr + 1);

        const kspWinShort = formatKspShortWindow(kspStartYr, kspEndYr);
        const kspWinFull = formatKspWindow(kspStartYr, kspEndYr);
        const hasAmount = Boolean(amountInput && amountInput.value && amount > 0);
        const effectiveAnnualDeposit = hasAmount ? amount : kspAnnualDeposit;
        const scaleFactor = kspAnnualDeposit > 0 ? (effectiveAnnualDeposit / kspAnnualDeposit) : 1;
        const scaledAnnualReturn = Math.round(kspAnnualReturn * scaleFactor * 100) / 100;
        const totalPaidIn = Math.round(effectiveAnnualDeposit * kspDepositPeriod * 100) / 100;
        const totalReturns = Math.round(scaledAnnualReturn * returnYearsCount * 100) / 100;
        const netGain = Math.max(0, Math.round((totalReturns - totalPaidIn) * 100) / 100);

        const depositEndDate = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
        depositEndDate.setFullYear(depositEndDate.getFullYear() + kspDepositPeriod);
        const formattedDepositEndDate = depositEndDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        if (interestDisplay) interestDisplay.value = `${formatInr(scaledAnnualReturn)} / yr`;
        if (insuranceDisplay) insuranceDisplay.value = formatInr(kspInsurance);
        if (insuranceHint) insuranceHint.innerText = 'KSP Life / Risk Coverage';
        if (interestBadge) {
            interestBadge.textContent = 'KSP Plan';
            interestBadge.className = 'badge bg-success-subtle text-success border border-success-subtle fw-semibold';
        }
        if (typeBadge) {
            typeBadge.innerText = 'KSP';
            typeBadge.className = 'badge bg-success-subtle text-success border border-success-subtle fw-semibold';
        }

        if (maturityDisplay) {
            maturityDisplay.value = hasAmount ? `${formatInr(scaledAnnualReturn)} / yr` : `${formatInr(kspAnnualReturn)} / yr`;
        }
        if (maturityHint) {
            maturityHint.innerText = hasAmount
                ? `Total Return: ${formatInr(totalReturns)} (${returnYearsCount} ${returnYearsCount === 1 ? 'Payout' : 'Payouts'})`
                : `Base Return: ${formatInr(totalReturns)} (${kspWinShort})`;
        }

        // Update KSP details panel
        if (kspPanel) kspPanel.style.display = '';
        const kspPanelInsBadge = document.getElementById('ksp_panel_insurance_badge');
        const kspPanelPremBadge = document.getElementById('ksp_panel_premature_badge');
        const kspPanelDeposit = document.getElementById('ksp_panel_deposit');
        const kspPanelTotalDeposit = document.getElementById('ksp_panel_total_deposit');
        const kspPanelInsurance = document.getElementById('ksp_panel_insurance');
        const kspPanelReturnWindow = document.getElementById('ksp_panel_return_window');
        const kspPanelReturnFreq = document.getElementById('ksp_panel_return_freq');
        const kspPanelAnnualReturn = document.getElementById('ksp_panel_annual_return');
        const kspPanelTotalReturn = document.getElementById('ksp_panel_total_return');
        const kspPanelSummaryText = document.getElementById('ksp_panel_summary_text');
        const kspPanelChips = document.getElementById('ksp_panel_payout_chips');

        if (kspPanelInsBadge) kspPanelInsBadge.textContent = `Insurance: ${formatInr(kspInsurance)}`;
        if (kspPanelPremBadge) kspPanelPremBadge.textContent = `Premature Withdrawal: ${kspPremature}`;
        if (kspPanelDeposit) {
            kspPanelDeposit.textContent = hasAmount
                ? `${formatInr(effectiveAnnualDeposit)} / yr × ${kspDepositPeriod} ${kspDepositPeriod === 1 ? 'Yr' : 'Yrs'}`
                : `Base ${formatInr(kspAnnualDeposit)} / yr × ${kspDepositPeriod} ${kspDepositPeriod === 1 ? 'Yr' : 'Yrs'}`;
        }
        if (kspPanelTotalDeposit) {
            kspPanelTotalDeposit.textContent = hasAmount
                ? `Total Deposit: ${formatInr(totalPaidIn)}`
                : 'Enter amount to compute total';
        }
        if (kspPanelInsurance) kspPanelInsurance.textContent = formatInr(kspInsurance);
        if (kspPanelReturnWindow) kspPanelReturnWindow.textContent = kspWinFull;
        if (kspPanelReturnFreq) kspPanelReturnFreq.textContent = `${kspFreq} (${returnYearsCount} ${returnYearsCount === 1 ? 'Payout' : 'Payouts'})`;
        if (kspPanelAnnualReturn) kspPanelAnnualReturn.textContent = `${formatInr(scaledAnnualReturn)} / yr`;
        if (kspPanelTotalReturn) kspPanelTotalReturn.textContent = `Total: ${formatInr(totalReturns)} (Gain: ${formatInr(netGain)})`;

        if (kspPanelSummaryText) {
            const depYearsText = kspDepositPeriod === 1 ? 'Year 1' : `Years 1 to ${kspDepositPeriod}`;
            kspPanelSummaryText.innerHTML = `Deposit <strong>${formatInr(effectiveAnnualDeposit)}/yr</strong> for <strong>${kspDepositPeriod} ${kspDepositPeriod === 1 ? 'Year' : 'Years'}</strong> (${depYearsText}, ends ${formattedDepositEndDate}) &rarr; Receive <strong>${formatInr(scaledAnnualReturn)}</strong> (${kspFreq}) in <strong>${kspWinFull}</strong> with <strong>${formatInr(kspInsurance)}</strong> Insurance Coverage.`;
        }

        if (kspPanelChips) {
            kspPanelChips.innerHTML = '';
            for (let yr = kspStartYr; yr <= Math.min(kspEndYr, kspStartYr + 29); yr++) {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border bank-mono-input px-2 py-1';
                chip.style.fontSize = '0.73rem';
                chip.innerHTML = `${formatOrdinalYear(yr)}: <strong class="text-success">${formatInr(scaledAnnualReturn)}</strong>`;
                kspPanelChips.appendChild(chip);
            }
        }

        // Update right-column KSP Summary card
        if (sectionTitle) sectionTitle.innerText = 'KSP Summary';
        if (pMaturityLabel) pMaturityLabel.innerText = `${kspFreq} Return (${kspWinShort})`;
        if (pMaturityAmt) pMaturityAmt.innerText = hasAmount ? `${formatInr(scaledAnnualReturn)} / yr` : '₹0.00';
        if (pRoundedMaturity) {
            pRoundedMaturity.innerText = hasAmount
                ? `Total Return (${kspWinShort}): ${formatInr(totalReturns)} | Insurance: ${formatInr(kspInsurance)}`
                : `Insurance Cover: ${formatInr(kspInsurance)} — Enter deposit amount`;
        }

        if (fdRows) fdRows.style.display = 'none';
        if (document.getElementById('preview_mrp_rows')) document.getElementById('preview_mrp_rows').style.display = 'none';
        if (document.getElementById('preview_rd_rows')) document.getElementById('preview_rd_rows').style.display = 'none';
        if (kspRows) kspRows.style.display = 'flex';

        const pKspIns = document.getElementById('preview_ksp_insurance');
        const pKspAnnDep = document.getElementById('preview_ksp_annual_deposit');
        const pKspDepPeriod = document.getElementById('preview_ksp_deposit_period');
        const pKspDepEnd = document.getElementById('preview_ksp_deposit_end');
        const pKspTotDep = document.getElementById('preview_ksp_total_deposit');
        const pKspRetWin = document.getElementById('preview_ksp_return_window');
        const pKspRetSched = document.getElementById('preview_ksp_return_schedule');
        const pKspAnnRet = document.getElementById('preview_ksp_annual_return');
        const pKspTotRet = document.getElementById('preview_ksp_total_return');
        const pKspNetGain = document.getElementById('preview_ksp_net_gain');
        const pKspPrem = document.getElementById('preview_ksp_premature');

        if (pKspIns) pKspIns.innerText = formatInr(kspInsurance);
        if (pKspAnnDep) pKspAnnDep.innerText = hasAmount ? `${formatInr(amount)} / yr` : '₹0.00';
        if (pKspDepPeriod) pKspDepPeriod.innerText = `${kspDepositPeriod} ${kspDepositPeriod === 1 ? 'Year' : 'Years'}`;
        if (pKspDepEnd) pKspDepEnd.innerText = `Deposit Ends ${formattedDepositEndDate}`;
        if (pKspTotDep) pKspTotDep.innerText = hasAmount ? formatInr(totalPaidIn) : '₹0.00';
        if (pKspRetWin) pKspRetWin.innerText = kspWinFull;
        if (pKspRetSched) pKspRetSched.innerText = `${kspFreq} • ${returnYearsCount} ${returnYearsCount === 1 ? 'Payout' : 'Payouts'}`;
        if (pKspAnnRet) pKspAnnRet.innerText = hasAmount ? `${formatInr(scaledAnnualReturn)} / yr` : '₹0.00';
        if (pKspTotRet) pKspTotRet.innerText = hasAmount ? formatInr(totalReturns) : '₹0.00';
        if (pKspNetGain) pKspNetGain.innerText = hasAmount ? formatInr(netGain) : '₹0.00';
        if (pKspPrem) pKspPrem.innerText = kspPremature;

        return;
    }

    if (kspPanel) kspPanel.style.display = 'none';
    if (kspRows) kspRows.style.display = 'none';

    if (isDouble) {
        if (seniorSwitch) {
            seniorSwitch.checked = false;
            seniorSwitch.disabled = true;
        }
        if (seniorHint) {
            seniorHint.innerText = 'Not applicable for 2x plan';
        }
        if (interestDisplay) interestDisplay.value = '2x Doubling (0%)';
        if (interestBadge) {
            interestBadge.textContent = '2x Double';
            interestBadge.className = 'badge bg-light text-secondary border fw-normal';
        }
        if (typeBadge) {
            typeBadge.innerText = 'DOUBLE';
            typeBadge.className = 'badge bg-light text-secondary border fw-normal';
        }
        if (pRate) pRate.innerText = '2x Payout';
    } else {
        if (seniorSwitch) {
            seniorSwitch.disabled = false;
            isSeniorCitizen = seniorSwitch.checked;
        }
        const seniorExtra = isSeniorCitizen ? tierSeniorExtra : 0;
        effectiveRate = baseRate + seniorExtra;

        if (seniorHint) {
            seniorHint.innerText = tierSeniorExtra > 0
                ? `+${tierSeniorExtra.toFixed(2)}% extra rate`
                : '';
        }

        let rateTxt = effectiveRate.toFixed(2) + '% p.a.';
        if (isSeniorCitizen && seniorExtra > 0) {
            rateTxt += ` (+${seniorExtra.toFixed(2)}%)`;
        }

        if (interestDisplay) interestDisplay.value = rateTxt;
        if (interestBadge) {
            interestBadge.textContent = profile.isMrp ? 'Monthly Return' : profile.schemeType;
            interestBadge.className = 'badge bg-light text-secondary border fw-normal';
        }
        if (typeBadge) {
            typeBadge.innerText = profile.isMrp ? 'MRP' : profile.schemeType;
            typeBadge.className = 'badge bg-light text-secondary border fw-normal';
        }
        if (pRate) pRate.innerText = rateTxt;
    }

    if (pTenure) pTenure.innerText = `${tenure} ${unit.toLowerCase()}`;
    if (!amountInput || !amountInput.value || amount <= 0) {
        if (maturityDisplay) maturityDisplay.value = '₹0.00';
        if (maturityHint) maturityHint.innerText = '';
        resetVoucherPreview(true);
        return;
    }

    const pPrincipalRow = document.getElementById('preview_principal_row');
    if (fdRows) fdRows.style.display = 'flex';
    if (pPrincipal) pPrincipal.innerText = formatInr(amount);

    const maturityDate = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());

    if (unit === 'DAYS') {
        maturityDate.setDate(maturityDate.getDate() + tenure);
    } else if (unit === 'YEARS') {
        maturityDate.setFullYear(maturityDate.getFullYear() + tenure);
    } else {
        maturityDate.setMonth(maturityDate.getMonth() + tenure);
    }

    const formattedMaturityDate = maturityDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    if (pMaturity) {
        pMaturity.innerText = formattedMaturityDate;
    }

    let totalYears = tenure / 12;
    if (unit === 'DAYS') totalYears = tenure / 365;
    if (unit === 'YEARS') totalYears = tenure;
    if (unit === 'WEEKS') totalYears = (tenure * 7) / 365;

    const mrpRows = document.getElementById('preview_mrp_rows');
    const pMrpPayoutLabel = document.getElementById('preview_mrp_payout_label');
    const pMrpMonthlyReturn = document.getElementById('preview_mrp_monthly_return');
    const pMrpPayoutCount = document.getElementById('preview_mrp_payout_count');
    const pMrpTotalReturns = document.getElementById('preview_mrp_total_returns');
    const pMrpPrincipalRefund = document.getElementById('preview_mrp_principal_refund');
    const pMrpTotalBenefit = document.getElementById('preview_mrp_total_benefit');

    const rdRows = document.getElementById('preview_rd_rows');
    const pInstallmentLabel = document.getElementById('preview_installment_label');
    const pInstallment = document.getElementById('preview_installment');
    const pNumInstallmentsLabel = document.getElementById('preview_num_installments_label');
    const pNumInstallments = document.getElementById('preview_num_installments');
    const pTotalPrincipalLabel = document.getElementById('preview_total_principal_label');
    const pTotalPrincipal = document.getElementById('preview_total_principal');

    let computedMaturity = amount;
    let totalInterest = 0;
    let heroMrpReturnText = null;
    let heroMrpSubText = null;

    if (isDouble) {
        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (pPrincipalRow) pPrincipalRow.style.display = 'flex';
        if (pPrincipalLabel) pPrincipalLabel.innerText = 'Principal Deposit';
        if (pRateLabel) pRateLabel.innerText = 'Term & Plan';
        if (pMaturityLabel) pMaturityLabel.innerText = 'Maturity Amount (2x)';
        if (pPayoutNote) pPayoutNote.innerText = '2x principal at maturity';
        if (mrpRows) mrpRows.style.display = 'none';
        if (rdRows) rdRows.style.display = 'none';

        computedMaturity = Math.round((amount * 2) * 100) / 100;
        totalInterest = Math.round((computedMaturity - amount) * 100) / 100;

        if (maturityDisplay) maturityDisplay.value = formatInr(computedMaturity);
        if (maturityHint) maturityHint.innerText = `Matures ${formattedMaturityDate}`;
    } else if (profile.isMrp || profile.isPeriodicPayout) {
        const isQuarterly = profile.payoutFreq === 'QUARTERLY';
        const isAnnual = profile.payoutFreq === 'ANNUALLY';
        const numMonths = Math.max(1, Math.round(unit === 'YEARS' ? tenure * 12 : (unit === 'DAYS' ? tenure / 30.4167 : tenure)));

        let periodicReturn = Math.round(((amount * effectiveRate) / 1200) * 100) / 100;
        if (profile.mrpMethod === 'ROUND_INTEGER') {
            periodicReturn = Math.round((amount * effectiveRate) / 1200);
        }
        let payoutUnitSuffix = '/ mo';
        let payoutTitle = 'Monthly Return';
        let payoutScheduleText = `${numMonths} Months`;

        if (isQuarterly) {
            periodicReturn = Math.round(((amount * effectiveRate) / 400) * 100) / 100;
            payoutUnitSuffix = '/ qtr';
            payoutTitle = 'Quarterly Return';
            payoutScheduleText = `${Math.max(1, Math.round(numMonths / 3))} Quarters`;
        } else if (isAnnual) {
            periodicReturn = Math.round(((amount * effectiveRate) / 100) * 100) / 100;
            payoutUnitSuffix = '/ yr';
            payoutTitle = 'Annual Return';
            payoutScheduleText = `${Math.max(1, Math.round(totalYears))} Years`;
        }

        totalInterest = effectiveRate > 0 && totalYears > 0
            ? Math.round((amount * (effectiveRate / 100) * totalYears) * 100) / 100
            : 0;
        computedMaturity = Math.round((amount + totalInterest) * 100) / 100;

        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (pPrincipalRow) pPrincipalRow.style.display = 'flex';
        if (pPrincipalLabel) pPrincipalLabel.innerText = 'Contribution';
        if (pRateLabel) pRateLabel.innerText = 'Rate & Tenure';
        if (pMaturityLabel) pMaturityLabel.innerText = payoutTitle;
        if (pPayoutNote) pPayoutNote.innerText = profile.payoutLabel;

        const periodicReturnFormatted = `${formatInr(periodicReturn)} ${payoutUnitSuffix}`;
        heroMrpReturnText = periodicReturnFormatted;
        heroMrpSubText = `Total Benefit: ${formatInr(computedMaturity)}`;

        if (pMrpPayoutLabel) pMrpPayoutLabel.innerText = payoutTitle;
        if (pMrpMonthlyReturn) pMrpMonthlyReturn.innerText = periodicReturnFormatted;
        if (pMrpPayoutCount) pMrpPayoutCount.innerText = payoutScheduleText;
        if (pMrpTotalReturns) pMrpTotalReturns.innerText = formatInr(totalInterest);
        if (pMrpPrincipalRefund) pMrpPrincipalRefund.innerText = formatInr(amount);
        if (pMrpTotalBenefit) pMrpTotalBenefit.innerText = formatInr(computedMaturity);

        if (mrpRows) mrpRows.style.display = 'flex';
        if (rdRows) rdRows.style.display = 'none';

        if (maturityDisplay) maturityDisplay.value = periodicReturnFormatted;
        if (maturityHint) maturityHint.innerText = `Principal Refund: ${formatInr(amount)}`;
    } else if (profile.mode === 'RD') {
        const numMonths = unit === 'YEARS' ? (tenure * 12) : (unit === 'DAYS' ? Math.ceil(tenure / 30) : tenure);
        const n = Math.max(1, numMonths);
        const rdBreakdown = calculateRdSimpleInterest(amount, n, effectiveRate, true);
        const totalPrincipal = rdBreakdown.total_principal;
        const rdMaturity = rdBreakdown.maturity_value;

        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (pPrincipalRow) pPrincipalRow.style.display = 'none';
        if (pRateLabel) pRateLabel.innerText = 'Rate & Tenure';
        if (pMaturityLabel) pMaturityLabel.innerText = 'Maturity Amount';
        if (pPayoutNote) pPayoutNote.innerText = 'At Maturity';

        if (pInstallmentLabel) pInstallmentLabel.innerText = 'Monthly Installment';
        if (pInstallment) pInstallment.innerText = formatInr(amount);
        if (pNumInstallmentsLabel) pNumInstallmentsLabel.innerText = 'Installments';
        if (pNumInstallments) pNumInstallments.innerText = n.toString();
        if (pTotalPrincipalLabel) pTotalPrincipalLabel.innerText = 'Total Principal';
        if (pTotalPrincipal) pTotalPrincipal.innerText = formatInr(totalPrincipal);
        if (mrpRows) mrpRows.style.display = 'none';
        if (rdRows) rdRows.style.display = 'flex';

        computedMaturity = rdMaturity;
        totalInterest = rdBreakdown.total_interest;

        if (maturityDisplay) maturityDisplay.value = formatInr(computedMaturity);
        if (maturityHint) maturityHint.innerText = `${n} Installments`;
    } else if (profile.mode === 'DD') {
        const days = Math.max(1, unit === 'YEARS' ? (tenure * 365) : (unit === 'MONTHS' ? (tenure * 30) : tenure));
        const totalPrincipal = amount * days;
        const interest = effectiveRate > 0 ? totalPrincipal * (effectiveRate / 100) * (days / 365) : 0;
        const ddMaturity = Math.round((totalPrincipal + interest) * 100) / 100;

        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (pPrincipalRow) pPrincipalRow.style.display = 'none';
        if (pRateLabel) pRateLabel.innerText = 'Rate & Tenure';
        if (pMaturityLabel) pMaturityLabel.innerText = 'Maturity Amount';
        if (pPayoutNote) pPayoutNote.innerText = 'At Maturity';

        if (pInstallmentLabel) pInstallmentLabel.innerText = 'Daily Amount';
        if (pInstallment) pInstallment.innerText = formatInr(amount);
        if (pNumInstallmentsLabel) pNumInstallmentsLabel.innerText = 'Days';
        if (pNumInstallments) pNumInstallments.innerText = `${days} Days`;
        if (pTotalPrincipalLabel) pTotalPrincipalLabel.innerText = 'Total Principal';
        if (pTotalPrincipal) pTotalPrincipal.innerText = formatInr(totalPrincipal);
        if (mrpRows) mrpRows.style.display = 'none';
        if (rdRows) rdRows.style.display = 'flex';

        computedMaturity = ddMaturity;
        totalInterest = Math.round((ddMaturity - totalPrincipal) * 100) / 100;

        if (maturityDisplay) maturityDisplay.value = formatInr(computedMaturity);
        if (maturityHint) maturityHint.innerText = `${days} Days`;
    } else {
        if (sectionTitle) sectionTitle.innerText = 'Summary';
        if (pPrincipalRow) pPrincipalRow.style.display = 'flex';
        if (pPrincipalLabel) pPrincipalLabel.innerText = 'Principal Deposit';
        if (pRateLabel) pRateLabel.innerText = 'Rate & Tenure';
        if (pMaturityLabel) pMaturityLabel.innerText = 'Maturity Amount';
        if (pPayoutNote) pPayoutNote.innerText = 'At Maturity';
        if (mrpRows) mrpRows.style.display = 'none';
        if (rdRows) rdRows.style.display = 'none';

        computedMaturity = effectiveRate > 0 && totalYears > 0
            ? Math.round((amount * Math.pow(1 + (effectiveRate / 100), totalYears)) * 100) / 100
            : amount;
        totalInterest = Math.round((computedMaturity - amount) * 100) / 100;

        if (maturityDisplay) maturityDisplay.value = formatInr(computedMaturity);
        if (maturityHint) maturityHint.innerText = `Matures ${formattedMaturityDate}`;
    }

    const formattedMaturity = formatInr(computedMaturity);
    const formattedInterest = formatInr(totalInterest);
    const roundedMaturity = Math.round(computedMaturity);

    if (pMaturityAmt) {
        pMaturityAmt.innerText = heroMrpReturnText || formattedMaturity;
    }
    if (pRoundedMaturity) {
        if (heroMrpSubText) {
            pRoundedMaturity.innerText = heroMrpSubText;
        } else {
            pRoundedMaturity.innerText = 'Rounded: ₹' + roundedMaturity.toLocaleString('en-IN');
        }
    }
    if (pInterestGain) {
        pInterestGain.innerText = formattedInterest;
    }
}

function calculateRdSimpleInterest(P, n, r, truncateMonthlyInterest = true) {
    if (P <= 0 || n <= 0) {
        return {
            monthly_interest: 0,
            total_principal: 0,
            total_interest: 0,
            maturity_value: 0,
            schedule: [],
        };
    }

    const rawMonthlyInterest = r > 0 ? (P * (r / 100)) / 12 : 0;
    const monthlyInterest = truncateMonthlyInterest
        ? Math.floor((rawMonthlyInterest * 100) + 1e-9) / 100
        : rawMonthlyInterest;

    const sumOfMonths = (n * (n + 1)) / 2;
    const totalPrincipal = Math.round(P * n * 100) / 100;
    const totalInterest = Math.round(monthlyInterest * sumOfMonths * 100) / 100;
    const maturityValue = Math.round((totalPrincipal + totalInterest) * 100) / 100;

    return {
        monthly_interest: monthlyInterest,
        total_principal: totalPrincipal,
        total_interest: totalInterest,
        maturity_value: maturityValue,
    };
}
</script>
@endpush
