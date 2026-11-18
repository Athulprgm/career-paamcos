@extends('superadmin.layouts.app')

@section('page-title', 'Modify Deposit Account: ' . $deposit->deposit_number)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Modify Deposit Account: {{ $deposit->deposit_number }}</h1>
        <div class="page-breadcrumb text-muted">
            <a href="{{ route('admin.deposits.index') }}" class="text-decoration-none">Deposit Operations</a> /
            <a href="{{ route('admin.deposits.show', $deposit) }}" class="text-decoration-none">{{ $deposit->deposit_number }}</a> /
            <span class="text-dark fw-semibold">Modify Account Terms</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.deposits.show', $deposit) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Deposit Account
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0 fs-6 fw-bold">Deposit Contract Endorsement & Modification</h5>
                    <small class="text-muted">Modify deposit product, tenor, or principal amount. Linked mobilization commission schedules will be automatically recalculated upon endorsement.</small>
                </div>
                <span class="{{ $deposit->getStatusBadgeClass() }} fs-7">
                    {{ str_replace('_', ' ', $deposit->status) }}
                </span>
            </div>

            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.deposits.update', $deposit) }}" method="POST" id="editDepositForm">
                    @csrf
                    @method('PUT')

                    <!-- Customer Info (Read-only reference) -->
                    <h6 class="fw-bold mb-3 pb-2 border-bottom">Account Holder Particulars</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Primary Account Holder</label>
                            <div class="fw-semibold fs-6 text-dark">
                                {{ $deposit->customer?->name ?? 'N/A' }}
                                @if($deposit->customer?->customer_number)
                                    <span class="badge bg-light text-secondary border font-monospace ms-1">{{ $deposit->customer->customer_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Operating Branch</label>
                            <div class="fw-semibold text-dark">{{ $deposit->branch?->name ?? 'N/A' }} ({{ $deposit->branch?->code }})</div>
                        </div>
                    </div>

                    <!-- ─── Sourcing Channel & Advisor Attribution ─── -->
                    <h6 class="fw-bold mb-3 pb-2 border-bottom">Sourcing Channel & Advisor Attribution</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="agent_id" class="form-label fw-semibold">Sourcing Business Facilitator / Field Agent <span class="text-danger">*</span></label>
                            <select name="agent_id" id="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required onchange="onAgentSelect(this)">
                                <option value="">-- Select Sourcing Business Facilitator / Agent --</option>
                                @foreach($agents as $ag)
                                    @php
                                        $agCode = $ag->member_code ?? $ag->agent_id;
                                        $agRank = $ag->rank?->name ?? 'Advisor';
                                        $agBranch = $ag->branch?->name ?? 'Branch';
                                    @endphp
                                    <option value="{{ $ag->id }}"
                                            data-id="{{ $ag->id }}"
                                            data-name="{{ $ag->name }}"
                                            data-code="{{ $agCode }}"
                                            data-branch-id="{{ $ag->branch_id }}"
                                            data-branch-name="{{ $agBranch }}"
                                            {{ (old('agent_id', $deposit->agent_id) == $ag->id) ? 'selected' : '' }}>
                                        {{ $ag->name }} [{{ $agCode }}] &bull; {{ $agBranch }} ({{ $agRank }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Field agent credited for deposit mobilization and commission entitlement.</small>
                            @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="lead_id" class="form-label fw-semibold">Prospective Customer / Lead</label>
                            <select name="lead_id" id="lead_id" class="form-select @error('lead_id') is-invalid @enderror">
                                <option value="">-- No Linked Customer Lead --</option>
                                @foreach($agentLeads as $lead)
                                    <option value="{{ $lead->id }}" {{ (old('lead_id', $deposit->lead_id) == $lead->id) ? 'selected' : '' }}>
                                        {{ $lead->name }} [{{ $lead->lead_number ?? ('LEAD#'.$lead->id) }}] &bull; ₹{{ number_format((float)($lead->expected_amount ?? 0), 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Associated prospective lead record if applicable.</small>
                            @error('lead_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- ─── Deposit Product, Tenor & Financial Terms ─── -->
                    <h6 class="fw-bold mb-3 pb-2 border-bottom">Deposit Product, Tenor & Financial Terms</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label for="deposit_scheme_id" class="form-label fw-semibold">Deposit Scheme / Product Plan <span class="text-danger">*</span></label>
                            <select name="deposit_scheme_id" id="deposit_scheme_id" class="form-select @error('deposit_scheme_id') is-invalid @enderror" required onchange="onSchemeChange(this)">
                                <option value="">-- Select Deposit Scheme / Product --</option>
                                @foreach($schemes as $sch)
                                    @php
                                        $schIsKsp = $sch->isKsp();
                                        $schKspConfig = $schIsKsp ? $sch->getKspConfig() : null;
                                    @endphp
                                    <option value="{{ $sch->id }}"
                                            data-min="{{ $sch->min_amount }}"
                                            data-max="{{ $sch->max_amount ?? '' }}"
                                            data-tenure="{{ $sch->tenure }}"
                                            data-tenure-unit="{{ $sch->tenure_unit }}"
                                            data-rate="{{ $sch->interest_rate }}"
                                            data-senior-extra="{{ (float)($sch->senior_citizen_extra_rate ?? 0) }}"
                                            data-type="{{ $sch->scheme_type }}"
                                            data-is-double="{{ $sch->isDouble() ? '1' : '0' }}"
                                            data-is-ksp="{{ $schIsKsp ? '1' : '0' }}"
                                            data-ksp-config="{{ json_encode($schKspConfig) }}"
                                            data-tenure-rates="{{ json_encode($sch->activeInterestRates->map(function($r) {
                                                return [
                                                    'tenure' => $r->tenure,
                                                    'tenure_unit' => $r->tenure_unit,
                                                    'interest_rate' => (float)$r->interest_rate,
                                                    'senior_citizen_extra_rate' => (float)($r->senior_citizen_extra_rate ?? 0),
                                                    'formatted_tenure' => $r->getFormattedTenure(),
                                                ];
                                            })) }}"
                                            {{ (old('deposit_scheme_id', $deposit->deposit_scheme_id) == $sch->id) ? 'selected' : '' }}>
                                        @if($schIsKsp && $schKspConfig)
                                            @php
                                                $kspPeriod = (int) $schKspConfig['deposit_period'];
                                                $kspInsFormatted = \App\Services\CurrencyFormatter::formatIndianNumber((float) $schKspConfig['insurance_coverage'], 0);
                                                $kspRetFormatted = \App\Services\CurrencyFormatter::formatIndianNumber((float) $schKspConfig['annual_return_amount'], 0);
                                            @endphp
                                            {{ $sch->name }} ({{ $sch->code }}) — {{ $kspPeriod }} {{ $kspPeriod === 1 ? 'Year' : 'Years' }} | Return: ₹{{ $kspRetFormatted }}/yr | Insurance: ₹{{ $kspInsFormatted }}
                                        @else
                                            {{ $sch->name }} ({{ $sch->code }}) — {{ $sch->tenure }} {{ strtolower($sch->tenure_unit) }} @ {{ $sch->isDouble() ? 'Maturity Doubling Plan' : $sch->interest_rate.'% p.a.' }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('deposit_scheme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="tenure_tier_select" class="form-label fw-semibold">Deposit Term / Tenor Duration <span class="text-danger">*</span></label>
                            <select id="tenure_tier_select" class="form-select" onchange="onTenureTierChange(this)">
                                <option value="">Select Deposit Scheme First</option>
                            </select>
                            <input type="hidden" name="tenure" id="tenure" value="{{ old('tenure', $deposit->tenure) }}">
                            <input type="hidden" name="tenure_unit" id="tenure_unit" value="{{ old('tenure_unit', $deposit->tenure_unit) }}">
                            <small class="text-muted d-block mt-1" id="tenure_rate_display_hint">Approved tenor tier with applicable interest rate.</small>
                            @error('tenure')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3 d-flex flex-column justify-content-center">
                            <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" name="is_senior_citizen" value="1" id="is_senior_citizen"
                                       {{ old('is_senior_citizen', $deposit->is_senior_citizen) ? 'checked' : '' }} onchange="calculateMaturityPreview()">
                                <label class="form-check-label fw-semibold text-dark" for="is_senior_citizen">
                                    <i class="bi bi-person-heart text-danger me-1"></i> Senior Citizen
                                </label>
                                <small class="text-muted d-block" id="senior_citizen_hint">+Preferential interest rate bonus</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="amount" class="form-label fw-semibold">Principal Deposit Amount (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       placeholder="e.g. 50000.00"
                                       value="{{ old('amount', $deposit->amount) }}" required
                                       oninput="calculateMaturityPreview()">
                            </div>
                            <small id="scheme_amount_hint" class="text-muted d-block mt-1">Contracted principal deposit value.</small>
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <!-- Interest Field & Double Feature -->
                        @php
                            $editIsKsp = $deposit->isKsp();
                            $editKspDetails = $editIsKsp ? $deposit->getKspDetails() : null;
                        @endphp
                        <div class="col-md-4">
                            <label for="interest_rate_display" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span>{{ $editIsKsp ? 'Plan & Insurance Coverage' : 'Contracted Interest Rate (% p.a.)' }}</span>
                                <span class="badge {{ $editIsKsp ? 'bg-success-subtle text-success border border-success-subtle' : ($deposit->is_double ? 'bg-warning text-dark border border-warning' : 'bg-primary-subtle text-primary border border-primary-subtle') }}" id="interest_type_badge">
                                    {{ $editIsKsp ? 'KSP PLAN' : ($deposit->is_double ? '2X DOUBLE' : 'Standard') }}
                                </span>
                            </label>
                            <div class="input-group">
                                <input type="text" id="interest_rate_display" class="form-control bg-light" readonly value="{{ $editIsKsp && $editKspDetails ? 'KSP • Insurance ₹'.\App\Services\CurrencyFormatter::formatIndianNumber($editKspDetails['insurance_coverage'], 2) : ($deposit->is_double ? 'No Interest (0.00%) — Doubling (2x)' : number_format($deposit->interest_rate, 2).'% p.a.') }}">
                                <span class="input-group-text bg-light"><i class="bi bi-percent"></i></span>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_double" value="1" id="is_double"
                                       {{ old('is_double', $deposit->is_double) ? 'checked' : '' }} onchange="onDoubleToggle(this)">
                                <label class="form-check-label fw-bold text-dark" for="is_double">
                                    <i class="bi bi-stars text-warning me-1"></i> Special Plan: Doubling Scheme (2x Payout)
                                </label>
                                <small class="text-muted d-block" id="double_feature_hint" style="font-size:0.75rem;">Maturity is 2x deposit principal upon completion of term.</small>
                            </div>
                            @error('is_double')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="start_date" class="form-label fw-semibold">Account Value Date / Inception Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', $deposit->start_date?->format('Y-m-d')) }}" required onchange="calculateMaturityPreview()">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Live Calculation Summary Card -->
                        <div class="col-12" id="scheme_info_box" style="display: none;">
                            <div class="p-3 bg-light border">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-calculator text-primary me-1"></i> Updated Maturity Projection & Yield Schedule</span>
                                    <span id="preview_type_badge" class="badge bg-dark text-white">{{ $deposit->deposit_type }}</span>
                                </div>

                                <div class="row g-2 text-center" id="preview_fd_rows">
                                    <div class="col-6 col-md-3 border-end">
                                        <small class="text-muted d-block">Principal Deposit (₹)</small>
                                        <span id="preview_principal" class="fw-bold text-dark fs-6 num-tabular">₹0.00</span>
                                    </div>
                                    <div class="col-6 col-md-3 border-end">
                                        <small class="text-muted d-block">Contracted Rate (% p.a.) & Tenor</small>
                                        <span id="preview_rate" class="fw-bold text-success fs-6 num-tabular">-</span>
                                        <small class="text-muted d-block" id="preview_tenure">-</small>
                                        <small class="text-success d-block fw-semibold num-tabular" id="preview_interest_gain" style="font-size:0.75rem;"></small>
                                    </div>
                                    <div class="col-6 col-md-3 border-end">
                                        <small class="text-muted d-block">Scheduled Maturity Date</small>
                                        <span id="preview_maturity" class="fw-bold text-dark fs-6 num-tabular">-</span>
                                    </div>
                                    <div class="col-6 col-md-3 bg-white border p-2">
                                        <small class="text-muted d-block">Estimated Maturity Payout (₹)</small>
                                        <span id="preview_maturity_amount" class="fw-bold text-primary fs-6 num-tabular">₹0.00</span>
                                        <small class="text-muted d-block num-tabular" id="preview_rounded_maturity" style="font-size:0.75rem;"></small>
                                    </div>
                                </div>

                                <div class="row g-2 text-center mt-2 pt-2 border-top" id="preview_rd_rows" style="display: none;">
                                    <div class="col-4 border-end">
                                        <small class="text-muted d-block">Monthly Recurring Installment (₹)</small>
                                        <span id="preview_installment" class="fw-bold text-dark fs-6">₹0.00</span>
                                    </div>
                                    <div class="col-4 border-end">
                                        <small class="text-muted d-block">Total Installment Count</small>
                                        <span id="preview_num_installments" class="fw-bold text-dark fs-6">0</span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Total Cumulative Principal (₹)</small>
                                        <span id="preview_total_principal" class="fw-bold text-dark fs-6">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment & Nominee Details -->
                        <div class="col-md-6">
                            <label for="payment_mode" class="form-label fw-semibold">Remittance / Payment Method</label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="CASH" {{ old('payment_mode', $deposit->payment_mode) === 'CASH' ? 'selected' : '' }}>CASH</option>
                                <option value="BANK_TRANSFER" {{ old('payment_mode', $deposit->payment_mode) === 'BANK_TRANSFER' ? 'selected' : '' }}>BANK TRANSFER / NEFT / RTGS</option>
                                <option value="CHEQUE" {{ old('payment_mode', $deposit->payment_mode) === 'CHEQUE' ? 'selected' : '' }}>CHEQUE</option>
                                <option value="ONLINE_UPI" {{ old('payment_mode', $deposit->payment_mode) === 'ONLINE_UPI' ? 'selected' : '' }}>ONLINE / UPI</option>
                                <option value="DEMAND_DRAFT" {{ old('payment_mode', $deposit->payment_mode) === 'DEMAND_DRAFT' ? 'selected' : '' }}>DEMAND DRAFT</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_reference" class="form-label fw-semibold">Transaction Reference / UTR Number</label>
                            <input type="text" name="payment_reference" id="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror"
                                   placeholder="UTR / Cheque / Bank transaction number"
                                   value="{{ old('payment_reference', $deposit->payment_reference) }}">
                        </div>

                        <div class="col-md-6">
                            <label for="nominee_name" class="form-label fw-semibold">Nominee Full Name</label>
                            <input type="text" name="nominee_name" id="nominee_name" class="form-control"
                                   placeholder="Full name of registered nominee"
                                   value="{{ old('nominee_name', $deposit->nominee_name) }}">
                        </div>

                        <div class="col-md-3">
                            <label for="nominee_relation" class="form-label fw-semibold">Relationship with Depositor</label>
                            <input type="text" name="nominee_relation" id="nominee_relation" class="form-control"
                                   placeholder="e.g. Spouse, Son, Mother"
                                   value="{{ old('nominee_relation', $deposit->nominee_relation) }}">
                        </div>

                        <div class="col-md-3">
                            <label for="nominee_phone" class="form-label fw-semibold">Nominee Contact Mobile</label>
                            <input type="text" name="nominee_phone" id="nominee_phone" class="form-control"
                                   placeholder="10-digit mobile number"
                                   value="{{ old('nominee_phone', $deposit->nominee_phone) }}">
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold">Endorsement Reason / Officer Remarks</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Reason for updating deposit terms...">{{ old('notes', $deposit->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- ─── Actions ─── -->
                    <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a href="{{ route('admin.deposits.show', $deposit) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-dark d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check2-circle"></i> Endorse Changes & Update Contract
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentSchemeTenureRates = [];
const initialTenure = {{ (int) old('tenure', $deposit->tenure) }};
const initialTenureUnit = '{{ strtoupper((string) old('tenure_unit', $deposit->tenure_unit)) }}';

document.addEventListener('DOMContentLoaded', function() {
    const schemeSelect = document.getElementById('deposit_scheme_id');
    if (schemeSelect && schemeSelect.value) {
        onSchemeChange(schemeSelect, true);
    }
});

function onDoubleToggle(checkbox) {
    calculateMaturityPreview();
}

function getEditKspConfig(opt) {
    if (!opt || opt.getAttribute('data-is-ksp') !== '1') return null;
    try {
        return JSON.parse(opt.getAttribute('data-ksp-config') || 'null');
    } catch (e) {
        return null;
    }
}

function onSchemeChange(selectEl, isInit = false) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;

    const kspConfig = getEditKspConfig(opt);
    const isKsp = Boolean(kspConfig);

    if (!isInit) {
        const isSchDouble = !isKsp && (opt.getAttribute('data-is-double') === '1' || (opt.getAttribute('data-type') || '').toUpperCase() === 'DOUBLE');
        const doubleInput = document.getElementById('is_double');
        if (doubleInput) {
            doubleInput.checked = isSchDouble;
        }
    }

    const schemeSeniorExtra = parseFloat(opt.getAttribute('data-senior-extra')) || 0;

    try {
        currentSchemeTenureRates = JSON.parse(opt.getAttribute('data-tenure-rates') || '[]');
    } catch(e) {
        currentSchemeTenureRates = [];
    }

    const defaultTenure = isKsp ? (parseInt(kspConfig.deposit_period, 10) || parseInt(opt.getAttribute('data-tenure'), 10) || 10) : (parseInt(opt.getAttribute('data-tenure')) || 12);
    const defaultUnit = isKsp ? 'YEARS' : ((opt.getAttribute('data-tenure-unit') || 'MONTHS').toUpperCase());
    const defaultRate = parseFloat(opt.getAttribute('data-rate')) || 0;

    const tierSelect = document.getElementById('tenure_tier_select');
    if (tierSelect) {
        tierSelect.innerHTML = '';
        let matchedOld = false;

        const targetTenure = isInit ? initialTenure : null;
        const targetUnit = isInit ? initialTenureUnit : null;

        if (currentSchemeTenureRates.length > 0 && !isKsp) {
            currentSchemeTenureRates.forEach(tier => {
                const optEl = document.createElement('option');
                optEl.value = `${tier.tenure}_${tier.tenure_unit}`;
                optEl.dataset.tenure = tier.tenure;
                optEl.dataset.unit = tier.tenure_unit;
                optEl.dataset.rate = tier.interest_rate;
                const tierSeniorExtra = parseFloat(tier.senior_citizen_extra_rate) || schemeSeniorExtra || 0;
                optEl.dataset.seniorExtra = tierSeniorExtra;

                let label = `${tier.tenure} ${tier.tenure_unit.toLowerCase()} — ${parseFloat(tier.interest_rate).toFixed(2)}% p.a.`;
                if (tierSeniorExtra > 0) {
                    label += ` (+${tierSeniorExtra.toFixed(2)}% Senior)`;
                }
                optEl.textContent = label;

                if (targetTenure && parseInt(targetTenure) === parseInt(tier.tenure) && (!targetUnit || targetUnit.toUpperCase() === tier.tenure_unit.toUpperCase())) {
                    optEl.selected = true;
                    matchedOld = true;
                }
                tierSelect.appendChild(optEl);
            });
        }

        const hasBaseInTiers = !isKsp && currentSchemeTenureRates.some(t => parseInt(t.tenure) === defaultTenure && t.tenure_unit.toUpperCase() === defaultUnit);
        if (!hasBaseInTiers) {
            const defOpt = document.createElement('option');
            defOpt.value = `${defaultTenure}_${defaultUnit}`;
            defOpt.dataset.tenure = defaultTenure;
            defOpt.dataset.unit = defaultUnit;
            defOpt.dataset.rate = defaultRate;
            defOpt.dataset.seniorExtra = schemeSeniorExtra;
            let label;
            if (isKsp) {
                const sYr = parseInt(kspConfig.return_start_year, 10) || 11;
                const eYr = parseInt(kspConfig.return_end_year, 10) || (10 + defaultTenure);
                const win = sYr === eYr ? `${sYr}th Yr` : `Yr ${sYr}–${eYr}`;
                label = `${defaultTenure} ${defaultTenure === 1 ? 'Year' : 'Years'} Deposit — Return: ${win}`;
            } else {
                label = `Default: ${defaultTenure} ${defaultUnit.toLowerCase()} — ${defaultRate.toFixed(2)}% p.a.`;
                if (schemeSeniorExtra > 0) {
                    label += ` (+${schemeSeniorExtra.toFixed(2)}% Senior)`;
                }
            }
            defOpt.textContent = label;
            if (!matchedOld) {
                defOpt.selected = true;
            }
            tierSelect.appendChild(defOpt);
        }

        onTenureTierChange(tierSelect);
    } else {
        calculateMaturityPreview();
    }
}

function onTenureTierChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const schemeSelect = document.getElementById('deposit_scheme_id');
    const schOpt = schemeSelect && schemeSelect.selectedIndex >= 0 ? schemeSelect.options[schemeSelect.selectedIndex] : null;
    const kspConfig = getEditKspConfig(schOpt);

    if (opt) {
        const tenureInput = document.getElementById('tenure');
        const unitInput = document.getElementById('tenure_unit');
        if (tenureInput) tenureInput.value = opt.dataset.tenure || '';
        if (unitInput) unitInput.value = opt.dataset.unit || '';

        const hint = document.getElementById('tenure_rate_display_hint');
        if (hint) {
            if (kspConfig) {
                const ins = Number(kspConfig.insurance_coverage || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
                hint.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle">KSP Plan • Insurance: ₹${ins}</span>`;
            } else {
                hint.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle">Applied Rate: ${parseFloat(opt.dataset.rate).toFixed(2)}% p.a.</span>`;
            }
        }
    }
    calculateMaturityPreview();
}

function calculateMaturityPreview() {
    const schemeSelect = document.getElementById('deposit_scheme_id');
    const amountInput = document.getElementById('amount');
    const dateInput = document.getElementById('start_date');
    const infoBox = document.getElementById('scheme_info_box');
    const tierSelect = document.getElementById('tenure_tier_select');

    if (!schemeSelect || !schemeSelect.value || !amountInput || !amountInput.value) {
        if (infoBox) infoBox.style.display = 'none';
        return;
    }

    const schOpt = schemeSelect.options[schemeSelect.selectedIndex];
    const kspConfig = getEditKspConfig(schOpt);
    const isKsp = Boolean(kspConfig);
    const tierOpt = tierSelect ? tierSelect.options[tierSelect.selectedIndex] : null;

    const amount = parseFloat(amountInput.value) || 0;
    const baseRate = tierOpt ? (parseFloat(tierOpt.dataset.rate) || 0) : (parseFloat(schOpt.getAttribute('data-rate')) || 0);
    const schemeSeniorExtra = parseFloat(schOpt.getAttribute('data-senior-extra')) || 0;
    const tierSeniorExtra = tierOpt ? (parseFloat(tierOpt.dataset.seniorExtra) || schemeSeniorExtra) : schemeSeniorExtra;

    const tenure = tierOpt ? (parseInt(tierOpt.dataset.tenure) || 12) : (parseInt(schOpt.getAttribute('data-tenure')) || 12);
    const unit = tierOpt ? ((tierOpt.dataset.unit || 'MONTHS').toUpperCase()) : ((schOpt.getAttribute('data-tenure-unit') || 'MONTHS').toUpperCase());
    const type = (schOpt.getAttribute('data-type') || 'FD').toUpperCase();

    const isDoubleCheckbox = document.getElementById('is_double');
    const isDouble = !isKsp && (isDoubleCheckbox ? isDoubleCheckbox.checked : false);

    const seniorSwitch = document.getElementById('is_senior_citizen');
    const seniorHint = document.getElementById('senior_citizen_hint');

    const typeBadge = document.getElementById('preview_type_badge');
    const pPrincipal = document.getElementById('preview_principal');
    const pRate = document.getElementById('preview_rate');
    const pTenure = document.getElementById('preview_tenure');
    const pMaturity = document.getElementById('preview_maturity');
    const pMaturityAmt = document.getElementById('preview_maturity_amount');

    const interestDisplay = document.getElementById('interest_rate_display');
    const interestBadge = document.getElementById('interest_type_badge');

    let effectiveRate = 0;
    let isSeniorCitizen = false;

    if (isKsp) {
        if (isDoubleCheckbox) {
            isDoubleCheckbox.checked = false;
            isDoubleCheckbox.disabled = true;
        }
        if (seniorSwitch) {
            seniorSwitch.checked = false;
            seniorSwitch.disabled = true;
        }
        if (seniorHint) {
            seniorHint.innerText = 'Not applicable for KSP plan';
        }
        const insFormatted = Number(kspConfig.insurance_coverage || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (interestDisplay) interestDisplay.value = `KSP • Insurance ₹${insFormatted}`;
        if (interestBadge) {
            interestBadge.textContent = 'KSP PLAN';
            interestBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
        }
    } else if (isDouble) {
        if (isDoubleCheckbox) isDoubleCheckbox.disabled = false;
        if (seniorSwitch) {
            seniorSwitch.disabled = true;
        }
        if (seniorHint) {
            seniorHint.innerHTML = '<span class="text-warning fw-semibold"><i class="bi bi-info-circle me-1"></i>Not applicable for 2X Doubling (Fixed 2x payout)</span>';
        }
        if (interestDisplay) interestDisplay.value = 'No Interest (0.00%) — Doubling (2x)';
        if (interestBadge) {
            interestBadge.textContent = '2X DOUBLE';
            interestBadge.className = 'badge bg-warning text-dark border border-warning';
        }
    } else {
        if (isDoubleCheckbox) isDoubleCheckbox.disabled = false;
        if (seniorSwitch) {
            seniorSwitch.disabled = false;
            isSeniorCitizen = seniorSwitch.checked;
        }
        const seniorExtra = isSeniorCitizen ? tierSeniorExtra : 0;
        effectiveRate = baseRate + seniorExtra;

        if (seniorHint) {
            seniorHint.innerText = tierSeniorExtra > 0
                ? `+${tierSeniorExtra.toFixed(2)}% Extra rate for Senior Citizens`
                : '+Extra rate bonus if applicable';
        }

        let disp = effectiveRate.toFixed(2) + '% p.a.';
        if (isSeniorCitizen && seniorExtra > 0) disp += ` (+${seniorExtra.toFixed(2)}% Senior)`;
        if (interestDisplay) interestDisplay.value = disp;
        if (interestBadge) {
            interestBadge.textContent = 'Standard';
            interestBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle';
        }
    }

    if (typeBadge) typeBadge.innerText = isKsp ? 'KSP' : (isDouble ? 'DOUBLE' : type);
    if (pPrincipal) pPrincipal.innerText = '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + (isKsp ? ' / yr' : '');
    if (pRate) {
        if (isKsp) {
            const insFormatted = Number(kspConfig.insurance_coverage || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            pRate.innerHTML = `<span class="text-primary fw-bold">Insurance: ₹${insFormatted}</span>`;
        } else if (isDouble) {
            pRate.innerHTML = '<span class="text-warning-emphasis fw-bold"><i class="bi bi-stars text-warning me-1"></i>No Interest (0.00%)</span><small class="text-muted d-block">Doubling Feature (2x)</small>';
        } else {
            let rateTxt = effectiveRate.toFixed(2) + '% p.a.';
            if (isSeniorCitizen && (isSeniorCitizen ? tierSeniorExtra : 0) > 0) {
                rateTxt += ` (incl. +${tierSeniorExtra.toFixed(2)}% Senior)`;
            }
            pRate.innerText = rateTxt;
        }
    }
    if (pTenure) {
        if (isKsp) {
            const sYr = parseInt(kspConfig.return_start_year, 10) || 11;
            const eYr = parseInt(kspConfig.return_end_year, 10) || (10 + tenure);
            pTenure.innerText = `${tenure} ${tenure === 1 ? 'Year' : 'Years'} Deposit | Return Yr ${sYr === eYr ? sYr : sYr + '–' + eYr}`;
        } else {
            pTenure.innerText = `${tenure} ${unit.toLowerCase()}`;
        }
    }

    // Maturity Date
    const startDateVal = dateInput && dateInput.value ? dateInput.value : new Date().toISOString().split('T')[0];
    const startDate = new Date(startDateVal);
    const maturityDate = new Date(startDate);

    if (unit === 'DAYS') {
        maturityDate.setDate(maturityDate.getDate() + tenure);
    } else if (unit === 'YEARS') {
        maturityDate.setFullYear(maturityDate.getFullYear() + tenure);
    } else {
        maturityDate.setMonth(maturityDate.getMonth() + tenure);
    }

    if (pMaturity) {
        pMaturity.innerText = maturityDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // Maturity Amount
    let totalYears = tenure / 12;
    if (unit === 'DAYS') totalYears = tenure / 365;
    if (unit === 'YEARS') totalYears = tenure;
    if (unit === 'WEEKS') totalYears = (tenure * 7) / 365;

    const rdRows = document.getElementById('preview_rd_rows');
    const pInstallment = document.getElementById('preview_installment');
    const pNumInstallments = document.getElementById('preview_num_installments');
    const pTotalPrincipal = document.getElementById('preview_total_principal');
    const pInterestGain = document.getElementById('preview_interest_gain');
    const pRoundedMaturity = document.getElementById('preview_rounded_maturity');

    if (isKsp) {
        const baseAnnual = parseFloat(kspConfig.annual_deposit_amount ?? 15000);
        const depPeriod = Math.max(1, parseInt(kspConfig.deposit_period ?? tenure ?? 10, 10));
        const sYr = parseInt(kspConfig.return_start_year ?? 11, 10);
        const eYr = Math.max(sYr, parseInt(kspConfig.return_end_year ?? (10 + depPeriod), 10));
        const retCount = Math.max(1, eYr - sYr + 1);
        const baseReturn = parseFloat(kspConfig.annual_return_amount ?? 45000);
        const scaleFactor = baseAnnual > 0 ? (amount / baseAnnual) : 1;
        const scaledReturn = Math.round(baseReturn * scaleFactor * 100) / 100;
        const totalDeposited = Math.round(amount * depPeriod * 100) / 100;
        const totalReturn = Math.round(scaledReturn * retCount * 100) / 100;
        const netGain = Math.max(0, Math.round((totalReturn - totalDeposited) * 100) / 100);

        if (rdRows) rdRows.style.display = 'none';
        if (pMaturityAmt) pMaturityAmt.innerText = '₹' + totalReturn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (pRoundedMaturity) pRoundedMaturity.innerText = `Annual Return: ₹${scaledReturn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}/yr | Total Deposit: ₹${totalDeposited.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (pInterestGain) pInterestGain.innerText = `Net Gain: ₹${netGain.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    } else if (type === 'RD') {
        const numMonths = unit === 'YEARS' ? (tenure * 12) : (unit === 'DAYS' ? Math.ceil(tenure / 30) : tenure);
        const n = Math.max(1, numMonths);
        const rdBreakdown = calculateRdSimpleInterest(amount, n, effectiveRate, true);
        const totalPrincipal = rdBreakdown.total_principal;
        const rdMaturity = rdBreakdown.maturity_value;
        const rdInterest = rdBreakdown.total_interest;

        if (pInstallment) pInstallment.innerText = '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (pNumInstallments) pNumInstallments.innerText = n.toString();
        if (pTotalPrincipal) pTotalPrincipal.innerText = '₹' + totalPrincipal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (rdRows) rdRows.style.display = 'flex';

        if (pMaturityAmt) pMaturityAmt.innerText = '₹' + rdMaturity.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (pRoundedMaturity) pRoundedMaturity.innerText = 'Rounded: ₹' + Math.round(rdMaturity).toLocaleString('en-IN');
        if (pInterestGain) pInterestGain.innerText = 'Est. Interest: ₹' + rdInterest.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        if (pInstallment) pInstallment.innerText = '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (pNumInstallments) pNumInstallments.innerText = '1';
        if (pTotalPrincipal) pTotalPrincipal.innerText = '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (rdRows) rdRows.style.display = 'none';

        let exactMaturity, maturityAmt, roundedMaturity, totalInterest;
        if (isDouble) {
            exactMaturity = amount * 2;
            maturityAmt = Math.round(exactMaturity * 100) / 100;
            roundedMaturity = Math.round(exactMaturity);
            totalInterest = amount;
        } else {
            exactMaturity = amount * Math.pow(1 + (effectiveRate / 100), totalYears);
            maturityAmt = Math.round(exactMaturity * 100) / 100;
            roundedMaturity = Math.round(exactMaturity);
            totalInterest = Math.round((exactMaturity - amount) * 100) / 100;
        }

        if (pMaturityAmt) pMaturityAmt.innerText = '₹' + maturityAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (pRoundedMaturity) pRoundedMaturity.innerText = isDouble ? ('Doubled Payout: ₹' + roundedMaturity.toLocaleString('en-IN')) : ('Rounded: ₹' + roundedMaturity.toLocaleString('en-IN'));
        if (pInterestGain) pInterestGain.innerText = isDouble ? ('Maturity Gain: ₹' + totalInterest.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (+100%)') : ('Est. Interest: ₹' + totalInterest.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    if (infoBox) infoBox.style.display = 'block';
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
