@extends('superadmin.layouts.app')

@section('page-title', 'Deposit Account: ' . $deposit->deposit_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Deposit Account: {{ $deposit->deposit_number }}</h1>
        <div class="page-breadcrumb">Deposit Operations / All Deposits / {{ $deposit->deposit_number }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{-- Staff / Officer Verification Action --}}
        @if($deposit->status === 'PENDING_APPROVAL' && !$deposit->isVerified())
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('manage-deposits') || auth()->user()->hasPermission('verify-deposits'))
                <button type="button" class="btn btn-primary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#verifyDepositModal">
                    <i class="bi bi-shield-check me-1"></i> Verify Application
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm text-dark"
                        data-bs-toggle="modal" data-bs-target="#returnCorrectionModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Return to Maker for Rectification
                </button>
            @endif
        @endif

        {{-- Final Approval Action (Admins only) --}}
        @if(auth()->user()->isAdmin())
            @if($deposit->canBeApproved())
                <form action="{{ route('admin.deposits.approve', $deposit) }}" method="POST" class="d-inline"
                      data-confirm="Confirm approval for deposit account {{ $deposit->deposit_number }}?"
                      data-confirm-title="Authorize Deposit Account"
                      data-confirm-type="success"
                      data-confirm-btn="Authorize Deposit">
                    @csrf
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Authorize & Approve Deposit
                    </button>
                </form>
            @endif
        @endif

        {{-- Rejection Action --}}
        @if($deposit->status === 'PENDING_APPROVAL')
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('manage-deposits'))
                <button type="button" class="btn btn-outline-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-1"></i> Reject Application
                </button>
            @endif
        @endif

            @if(auth()->user()->isAdmin())
                @if($deposit->canBeActivated())
                    <form action="{{ route('admin.deposits.activate', $deposit) }}" method="POST" class="d-inline"
                          data-confirm="Activate deposit account {{ $deposit->deposit_number }}?"
                          data-confirm-title="Activate Deposit Account"
                          data-confirm-type="success"
                          data-confirm-btn="Activate Deposit">
                        @csrf
                        <button type="submit" class="btn btn-dark btn-sm">
                            <i class="bi bi-lightning-charge me-1"></i> Direct Account Activation
                        </button>
                    </form>
                @endif
            @endif

        {{-- Edit Deposit Action --}}
        @if(!auth()->user()->isAgent() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('create-deposits') || auth()->user()->hasPermission('manage-deposits')))
            @if(!in_array($deposit->status, ['CLOSED', 'MATURED', 'REJECTED'], true) || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.deposits.edit', $deposit) }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Modify Account Terms
                </a>
            @endif
        @endif

        {{-- Delete Deposit Action --}}
        @if(!auth()->user()->isAgent() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->hasPermission('delete-deposits')))
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDepositModal">
                <i class="bi bi-trash me-1"></i> Delete Account Record
            </button>
        @endif

        @if(auth()->user()->isAgent())
            <a href="{{ route('admin.commissions.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Commissions
            </a>
        @else
            <a href="{{ route('admin.deposits.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Deposit Accounts
            </a>
        @endif
    </div>
</div>

@php
    $isKspDeposit = $deposit->isKsp();
    $kspDetails = $isKspDeposit ? $deposit->getKspDetails() : null;
@endphp

<div class="row g-3">
    <!-- Main Left Column -->
    <div class="col-lg-8">
        <!-- Account Overview Card -->
        <div class="card mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="card-title mb-0 fs-6 fw-bold">Account Master Record & Contract Summary</h5>
                    <span class="badge bg-secondary text-white">{{ $deposit->deposit_type ?? $deposit->scheme?->scheme_type ?? 'FD' }}</span>
                    @if($isKspDeposit)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">KSP</span>
                    @endif
                </div>
                <span class="{{ $deposit->getStatusBadgeClass() }} fs-7">
                    {{ str_replace('_', ' ', $deposit->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small">Deposit Account Number</div>
                        <div class="fw-bold fs-6 text-dark">{{ $deposit->deposit_number }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">{{ $isKspDeposit ? 'Annual Deposit Amount' : 'Principal Deposit Amount' }}</div>
                        <div class="fw-bold fs-5 text-dark">
                            ₹{{ number_format($deposit->amount, 2) }}{{ $isKspDeposit ? ' / yr' : '' }}
                        </div>
                        @if($isKspDeposit && $kspDetails)
                            <small class="text-muted" style="font-size:0.75rem;">Total Deposit ({{ $kspDetails['deposit_period'] }} {{ $kspDetails['deposit_period'] === 1 ? 'Yr' : 'Yrs' }}): ₹{{ number_format($kspDetails['total_deposited'], 2) }}</small>
                        @endif
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Deposit Product / Scheme</div>
                        <div class="fw-semibold">{{ $deposit->scheme?->name ?? 'N/A' }} ({{ $deposit->scheme?->code }})</div>
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-sm-4">
                        <div class="text-muted small">Account Value Date</div>
                        <div class="fw-semibold">{{ $deposit->start_date?->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">{{ $isKspDeposit ? 'Deposit Period & Return Window' : 'Deposit Tenor & Collection Frequency' }}</div>
                        @if($isKspDeposit && $kspDetails)
                            <div class="fw-semibold">{{ $kspDetails['deposit_period'] }} {{ $kspDetails['deposit_period'] === 1 ? 'Year' : 'Years' }} ({{ $kspDetails['window_label'] }})</div>
                        @else
                            <div class="fw-semibold">{{ $deposit->tenure }} {{ strtolower($deposit->tenure_unit) }} ({{ str_replace('_', ' ', $deposit->collection_frequency) }})</div>
                        @endif
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">{{ $isKspDeposit ? 'Deposit Period End Date' : 'Scheduled Maturity Date' }}</div>
                        <div class="fw-bold text-dark fs-6">{{ $deposit->maturity_date?->format('d M Y') ?? 'N/A' }}</div>
                    </div>

                    <div class="col-sm-4">
                        @if($isKspDeposit && $kspDetails)
                            <div class="text-muted small">Insurance Coverage</div>
                            <div class="fw-bold text-primary fs-6">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['insurance_coverage'], 2) }}</div>
                            <small class="text-muted" style="font-size:0.75rem;">KSP Plan ({{ $kspDetails['return_frequency'] }} Return)</small>
                        @else
                            <div class="text-muted small">Contracted Interest Rate (% p.a.)</div>
                            @if($deposit->isDouble())
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1">
                                    <i class="bi bi-stars text-warning me-1"></i>Maturity Doubling Plan (2x Principal)
                                </span>
                            @else
                                <div class="fw-semibold text-success">
                                    {{ number_format($deposit->interest_rate ?? $deposit->scheme?->interest_rate ?? 0, 2) }}% p.a.
                                    @if($deposit->isSeniorCitizen())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-person-heart me-1"></i>Senior Citizen
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="col-sm-4">
                        @if($isKspDeposit && $kspDetails)
                            <div class="text-muted small">Projected Net Gain / Benefit</div>
                            <div class="fw-bold text-success fs-6">₹{{ number_format($kspDetails['net_gain'], 2) }}</div>
                            <small class="text-muted" style="font-size:0.75rem;">Annual Return: ₹{{ number_format($kspDetails['annual_return_amount'], 2) }} / yr</small>
                        @else
                            <div class="text-muted small">{{ $deposit->isDouble() ? 'Contracted Doubling Yield (+100%)' : 'Projected Accrued Interest' }}</div>
                            <div class="fw-bold text-success fs-6">₹{{ number_format($deposit->estimated_interest, 2) }}</div>
                            <small class="text-muted" style="font-size:0.75rem;">(Rounded: ₹{{ number_format(round($deposit->estimated_interest)) }})</small>
                        @endif
                    </div>
                    <div class="col-sm-4">
                        @if($isKspDeposit && $kspDetails)
                            <div class="text-muted small">Estimated Total KSP Return</div>
                            <div class="fw-bold text-primary fs-5">₹{{ number_format($deposit->maturity_amount ?? $kspDetails['total_return'], 2) }}</div>
                            <small class="text-muted" style="font-size:0.75rem;">{{ $kspDetails['return_years_count'] }} {{ $kspDetails['return_years_count'] === 1 ? 'Payout' : 'Payouts' }} ({{ $kspDetails['window_label'] }})</small>
                        @else
                            <div class="text-muted small">Estimated Gross Maturity Payout</div>
                            <div class="fw-bold text-primary fs-5">₹{{ number_format($deposit->maturity_amount ?? ($deposit->amount + $deposit->estimated_interest), 2) }}</div>
                            <small class="text-muted" style="font-size:0.75rem;">{{ $deposit->isDouble() ? '(Doubled Payout: 2x Principal)' : '(Rounded: ₹'.number_format(round($deposit->maturity_amount ?? ($deposit->amount + $deposit->estimated_interest))).')' }}</small>
                        @endif
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-sm-4">
                        <div class="text-muted small">Remittance / Payment Method</div>
                        <div class="fw-semibold"><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $deposit->payment_mode ?? 'CASH') }}</span></div>
                    </div>
                    @if($deposit->payment_reference)
                    <div class="col-sm-4">
                        <div class="text-muted small">Transaction Reference / UTR Number</div>
                        <div class="fw-semibold font-monospace small">{{ $deposit->payment_reference }}</div>
                    </div>
                    @endif
                    <div class="col-sm-4">
                        <div class="text-muted small">Funds Realization Date</div>
                        <div class="fw-semibold">{{ $deposit->deposit_date ? \Carbon\Carbon::parse($deposit->deposit_date)->format('d M Y') : '—' }}</div>
                    </div>

                    @if($deposit->auto_renewal && $deposit->auto_renewal !== 'NONE')
                    <div class="col-sm-4">
                        <div class="text-muted small">Standing Instruction (Auto-Rollover)</div>
                        <div class="fw-semibold text-secondary small">{{ str_replace('_', ' ', $deposit->auto_renewal) }}</div>
                    </div>
                    @endif
                    @if($deposit->payout_frequency && $deposit->payout_frequency !== 'AT_MATURITY')
                    <div class="col-sm-4">
                        <div class="text-muted small">Periodic Interest Payout Mandate</div>
                        <div class="fw-semibold text-secondary small">{{ str_replace('_', ' ', $deposit->payout_frequency) }}</div>
                    </div>
                    @endif
                    <div class="col-sm-4">
                        <div class="text-muted small">Account Lifecycle Status</div>
                        <div><span class="{{ $deposit->getStatusBadgeClass() }}">{{ str_replace('_', ' ', $deposit->status) }}</span></div>
                    </div>

                    @if($deposit->rejection_reason)
                    <div class="col-12">
                        <div class="p-3 bg-light border border-danger-subtle text-danger">
                            <strong class="d-block mb-1"><i class="bi bi-x-octagon me-1"></i> Application Rejection Remarks:</strong>
                            {{ $deposit->rejection_reason }}
                            @if($deposit->rejecter)
                                <div class="mt-1 small text-muted">Rejected by {{ $deposit->rejecter->name }} on {{ $deposit->rejected_at?->format('d M Y, h:i A') }}</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($deposit->notes)
                    <div class="col-12">
                        <div class="text-muted small">Operational Notes / Remarks</div>
                        <div class="text-secondary small bg-light p-2">{{ $deposit->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($isKspDeposit && $kspDetails)
        <!-- KSP Plan, Insurance Coverage & Return Schedule Card -->
        <div class="card mb-3 border border-success-subtle" id="ksp_deposit_details_card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <h6 class="card-title mb-0 fw-bold">KSP Plan, Insurance Coverage &amp; Return Schedule</h6>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Kissan Samrudhi Plan (KSP)</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Annual Deposit</div>
                        <div class="fw-bold num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['annual_deposit_amount'], 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Deposit Period</div>
                        <div class="fw-semibold num-tabular">{{ $kspDetails['deposit_period'] }} {{ $kspDetails['deposit_period'] === 1 ? 'Year' : 'Years' }}</div>
                        <small class="text-muted">Total: ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['total_deposited'], 2) }}</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Insurance Coverage</div>
                        <div class="fw-bold text-primary num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['insurance_coverage'], 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Annual Return</div>
                        <div class="fw-bold text-success num-tabular">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['annual_return_amount'], 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Return Start Year</div>
                        <div class="fw-semibold">{{ \App\Models\DepositScheme::formatOrdinalYear($kspDetails['return_start_year']) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Return End Year</div>
                        <div class="fw-semibold">{{ \App\Models\DepositScheme::formatOrdinalYear($kspDetails['return_end_year']) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Return Frequency</div>
                        <div class="fw-semibold">{{ $kspDetails['return_frequency'] }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Premature Withdrawal</div>
                        <div class="fw-semibold">{{ $kspDetails['premature_withdrawal'] }}</div>
                    </div>
                </div>

                <div class="p-3 bg-light border">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="fw-semibold small text-dark">
                            <i class="bi bi-calendar-range text-success me-1"></i>Projected Payout Schedule ({{ $kspDetails['window_label'] }})
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle num-tabular">
                            Total Return: ₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['total_return'], 2) }} ({{ $kspDetails['return_years_count'] }} {{ $kspDetails['return_years_count'] === 1 ? 'Payout' : 'Payouts' }})
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @for($yr = $kspDetails['return_start_year']; $yr <= min($kspDetails['return_end_year'], $kspDetails['return_start_year'] + 29); $yr++)
                            <span class="badge bg-white text-dark border font-monospace num-tabular px-2 py-1" style="font-size:0.74rem;">
                                {{ \App\Models\DepositScheme::formatOrdinalYear($yr) }}: <strong class="text-success">₹{{ \App\Services\CurrencyFormatter::formatIndianNumber($kspDetails['annual_return_amount'], 2) }}</strong>
                            </span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Sourced from Lead Card --}}
        @if($deposit->lead)
        <div class="card mb-3 border border-primary-subtle">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-primary fs-5"></i>
                    <h6 class="card-title mb-0 fw-bold">Lead Attribution & Customer Acquisition</h6>
                </div>
                <a href="{{ route('admin.leads.show', $deposit->lead) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i> View Lead Profile
                </a>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.7rem;">PROSPECTIVE CUSTOMER</small>
                        <div class="fw-bold text-dark fs-6">{{ $deposit->lead->name }}</div>
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $deposit->lead->mobile }}</div>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.7rem;">SOURCING ADVISOR / AGENT</small>
                        <div class="fw-bold text-dark fs-6">{{ $deposit->lead->agent?->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $deposit->lead->agent?->rank?->name ?? 'Advisor' }} &bull; Branch: {{ $deposit->lead->branch?->name }}</div>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.7rem;">ACQUISITION STATUS</small>
                        <div>
                            <span class="{{ $deposit->lead->getStatusBadgeClass() }}">{{ $deposit->lead->status }}</span>
                        </div>
                        <small class="text-muted">Acquired On {{ $deposit->lead->created_at?->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Agent Commission & Admin Approval Card --}}
        <div class="card mb-3 border">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-coin text-warning fs-5"></i>
                    <h6 class="card-title mb-0 fw-bold">Deposit Mobilization Commission Schedule</h6>
                </div>
                <a href="{{ route('admin.commissions.index') }}" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-list-check me-1"></i> Commission Ledger
                </a>
            </div>
            <div class="card-body p-3">
                @if($deposit->commissions->isNotEmpty())
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-white border">
                        <div>
                            <span class="small text-muted">Total Mobilization Commissions:</span>
                            <span class="fw-bold text-primary fs-6 ms-1 num-tabular">₹{{ number_format($deposit->commissions->sum('commission_amount'), 2) }}</span>
                            <span class="badge bg-secondary ms-2">{{ $deposit->commissions->count() }} Year(s) Generated</span>
                        </div>
                        <div class="small text-muted">
                            Scheme Tenor: <strong class="text-dark">{{ $deposit->getTenureInYears() }} Year(s)</strong>
                        </div>
                    </div>

                    @foreach($deposit->commissions->sortBy('payout_year') as $comm)
                        <div class="p-3 bg-light border mb-2">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                                <div>
                                    <span class="fw-bold text-dark fs-6 font-monospace">{{ $comm->commission_number }}</span>
                                    @if($comm->payout_year)
                                        <span class="badge bg-primary text-white ms-1">Year {{ $comm->payout_year }}</span>
                                    @endif
                                    <span class="text-muted small ms-2">Rule: {{ $comm->rule?->name ?? 'Configured Rate' }}</span>
                                </div>
                                <div>
                                    <span class="{{ $comm->getStatusBadgeClass() }} fs-7">
                                        {{ $comm->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="row g-2 text-center align-items-center">
                                <div class="col-6 col-md-3 border-end">
                                    <small class="text-muted d-block">Sourcing Advisor</small>
                                    <span class="fw-bold text-dark fs-6">{{ $comm->agent?->name ?? $deposit->agent?->name }}</span>
                                    <small class="text-muted d-block">{{ $comm->agent?->rank?->name ?? 'Advisor' }}</small>
                                </div>
                                <div class="col-6 col-md-3 border-end">
                                    <small class="text-muted d-block">Commissionable Base Amount</small>
                                    <span class="fw-bold text-dark fs-6 num-tabular">₹{{ number_format($comm->base_amount, 2) }}</span>
                                </div>
                                <div class="col-6 col-md-3 border-end">
                                    <small class="text-muted d-block">Entitlement Rate</small>
                                    <span class="fw-bold text-success fs-6 num-tabular">{{ number_format($comm->rate, 2) }}%</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block">Net Commission Payable</small>
                                    <span class="fw-bold text-primary fs-5 num-tabular">₹{{ number_format($comm->commission_amount, 2) }}</span>
                                </div>
                            </div>

                            {{-- Actions for Pending Commission --}}
                            @if($comm->isPending())
                                <div class="mt-3 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="small text-muted">
                                        <i class="bi bi-clock-history me-1"></i> Awaiting Administrator review and approval.
                                    </div>
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('approve-commissions'))
                                        <div class="d-flex gap-2">
                                            <form action="{{ route('admin.commissions.approve', $comm) }}" method="POST" class="d-inline"
                                                  data-confirm="Approve commission {{ $comm->commission_number }} of ₹{{ number_format($comm->commission_amount, 2) }} for {{ $comm->agent?->name }}?"
                                                  data-confirm-title="Approve Mobilization Commission"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="Approve Commission">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Authorize Commission
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye me-1"></i> Details
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @elseif($comm->isApproved())
                                <div class="mt-2 pt-2 border-top small text-success d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-patch-check-fill me-1"></i> Authorized by {{ $comm->approver?->name ?? 'Admin' }} on {{ $comm->approved_at?->format('d M Y, h:i A') }}
                                    </div>
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-sm btn-outline-dark">View Ledger</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="p-3 text-center text-muted small bg-light border">
                        <i class="bi bi-info-circle me-1"></i> No mobilization commission generated yet. Commissions are automatically calculated based on product scheme and advisor rank rules.
                    </div>
                @endif
            </div>
        </div>

        <!-- Audit History Card -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Account Activity & Audit History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Processing Officer</th>
                                <th>Action Event</th>
                                <th>Activity Description & Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposit->auditLogs as $log)
                                <tr>
                                    <td class="small text-muted">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="fw-medium small">{{ $log->user_name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $log->event }}</span></td>
                                    <td class="small">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">
                                        No audit entries recorded for this deposit account.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Information Column -->
    <div class="col-lg-4">
        <!-- Customer Information -->
        <div class="card mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Primary Account Holder Particulars</h6>
            </div>
            <div class="card-body">
                @if($deposit->lead)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold fs-6 text-dark">{{ $deposit->lead->name }}</span>
                        <span class="badge bg-primary-subtle text-primary border">Acquired Lead</span>
                    </div>
                    <div class="py-1 small border-top d-flex justify-content-between">
                        <span class="text-muted">Registered Mobile</span>
                        <span class="fw-medium font-monospace">{{ $deposit->lead->mobile }}</span>
                    </div>
                    @if($deposit->lead->email)
                    <div class="py-1 small border-top d-flex justify-content-between">
                        <span class="text-muted">Email Address</span>
                        <span class="fw-medium">{{ $deposit->lead->email }}</span>
                    </div>
                    @endif
                @elseif($deposit->customer)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold fs-6 text-dark">{{ $deposit->customer->name }}</span>
                        <span class="{{ $deposit->customer->getStatusBadgeClass() }}">{{ $deposit->customer->status }}</span>
                    </div>
                    @if($deposit->customer->mobile)
                    <div class="py-1 small border-top d-flex justify-content-between">
                        <span class="text-muted">Registered Mobile</span>
                        <span class="fw-medium font-monospace">{{ $deposit->customer->mobile }}</span>
                    </div>
                    @endif
                @else
                    <span class="text-muted small">No associated customer profile.</span>
                @endif
                <div class="py-1 small border-top d-flex justify-content-between">
                    <span class="text-muted">Home Branch</span>
                    <span class="fw-medium">{{ $deposit->branch?->name }}</span>
                </div>
            </div>
        </div>

        @if($deposit->nominee_name)
        <!-- Nominee Details Card -->
        <div class="card mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Nomination Particulars (Section 45ZA)</h6>
                <span class="badge bg-light text-dark border"><i class="bi bi-person-heart me-1"></i> Registered Nominee</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold fs-6 text-dark">{{ $deposit->nominee_name }}</span>
                    @if($deposit->nominee_relation)
                        <span class="badge bg-info-subtle text-info-emphasis border">{{ $deposit->nominee_relation }}</span>
                    @endif
                </div>
                @if($deposit->nominee_phone)
                <div class="py-1 small border-top d-flex justify-content-between">
                    <span class="text-muted">Nominee Contact Phone</span>
                    <span class="fw-medium font-monospace">{{ $deposit->nominee_phone }}</span>
                </div>
                @endif
                @if($deposit->nominee_age)
                <div class="py-1 small border-top d-flex justify-content-between">
                    <span class="text-muted">Nominee Age</span>
                    <span class="fw-medium">{{ $deposit->nominee_age }} years</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Servicing Agent & Branch -->
        <div class="card mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Operating Branch & Sourcing Advisor</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Operating Branch</div>
                    <div class="fw-semibold">{{ $deposit->branch?->name ?? 'N/A' }} ({{ $deposit->branch?->code }})</div>
                </div>
                <div class="border-top pt-2">
                    <div class="text-muted small">Sourcing Business Facilitator / Agent</div>
                    <div class="fw-semibold">{{ $deposit->agent?->name ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $deposit->agent?->agent_id }} | {{ $deposit->agent?->rank?->name ?? 'Advisor' }}</small>
                    @if($deposit->agent)
                        <div class="mt-2 pt-2 border-top d-flex flex-wrap gap-1">
                            <a href="{{ route('admin.agents.show', $deposit->agent) }}" class="btn btn-sm btn-outline-dark py-1 px-2" style="font-size: 0.75rem;" title="View Advisor Profile">
                                <i class="bi bi-person me-1"></i>Profile
                            </a>
                            <a href="{{ route('admin.targets.agents.show', $deposit->agent) }}" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size: 0.75rem;" title="View FY Target Progress">
                                <i class="bi bi-crosshair me-1"></i>FY Target
                            </a>
                            <a href="{{ route('admin.career.agent-performance', $deposit->agent) }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" title="View Career Scorecard">
                                <i class="bi bi-award me-1"></i>Career
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Lifecycle Timeline -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Maker-Checker Processing Trail</h6>
            </div>
            <div class="card-body small">
                <div class="py-2 border-bottom">
                    <div class="text-muted">Originated By (Maker):</div>
                    <div class="fw-semibold">{{ $deposit->creator?->name ?? 'System' }}</div>
                    <small class="text-muted">{{ $deposit->created_at->format('d M Y, h:i A') }}</small>
                </div>

                @if($deposit->verified_at)
                <div class="py-2 border-bottom">
                    <div class="text-muted">Audited & Verified (Checker):</div>
                    <div class="fw-semibold text-primary"><i class="bi bi-patch-check-fill me-1"></i>{{ $deposit->verifier?->name ?? 'Staff' }}</div>
                    <small class="text-muted">{{ $deposit->verified_at->format('d M Y, h:i A') }}</small>
                    @if($deposit->remarks)
                        <div class="small text-dark mt-1 bg-light p-1 border font-monospace">"{{ $deposit->remarks }}"</div>
                    @endif
                </div>
                @endif

                @if($deposit->approved_at)
                <div class="py-2 border-bottom">
                    <div class="text-muted">Authorized & Approved (Manager):</div>
                    <div class="fw-semibold text-success">{{ $deposit->approver?->name ?? 'Manager' }}</div>
                    <small class="text-muted">{{ $deposit->approved_at->format('d M Y, h:i A') }}</small>
                </div>
                @endif

                @if($deposit->rejected_at)
                <div class="py-2 border-bottom">
                    <div class="text-muted">Rejected By:</div>
                    <div class="fw-semibold text-danger">{{ $deposit->rejecter?->name ?? 'Manager' }}</div>
                    <small class="text-muted">{{ $deposit->rejected_at->format('d M Y, h:i A') }}</small>
                </div>
                @endif

                @if($deposit->activated_at)
                <div class="py-2 border-bottom">
                    <div class="text-muted">Activated On:</div>
                    <div class="fw-semibold text-primary">{{ $deposit->activated_at->format('d M Y, h:i A') }}</div>
                </div>
                @endif

                @if($deposit->matured_at)
                <div class="py-2 border-bottom">
                    <div class="text-muted">Matured On:</div>
                    <div class="fw-semibold text-info">{{ $deposit->matured_at->format('d M Y, h:i A') }}</div>
                </div>
                @endif

                @if($deposit->closed_at)
                <div class="py-2">
                    <div class="text-muted">Closed / Settled By:</div>
                    <div class="fw-semibold">{{ $deposit->closer?->name ?? 'Manager' }}</div>
                    <small class="text-muted">{{ $deposit->closed_at->format('d M Y, h:i A') }}</small>
                </div>
                @endif
            </div>
        </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.deposits.reject', $deposit) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Reject Deposit Account Application</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">You are rejecting deposit application <strong>{{ $deposit->deposit_number }}</strong> with principal <strong>₹{{ number_format($deposit->amount, 2) }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label required">Application Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="State specific reason for rejection (e.g. document mismatch, remittance discrepancy)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify Deposit Modal -->
<div class="modal fade" id="verifyDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.deposits.verify', $deposit) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-shield-check text-primary me-1"></i> Checker Audit & Verification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">You are verifying deposit account application <strong>{{ $deposit->deposit_number }}</strong> for principal <strong>₹{{ number_format($deposit->amount, 2) }}</strong> belonging to account holder <strong>{{ $deposit->customer?->name }}</strong>.</p>
                    <div class="alert alert-info py-2 small">
                        Confirm that you have reviewed customer details, scheme terms, and bank receipt / remittance voucher. Once verified, this application moves to the Authorization Queue for final approval.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Checker Audit Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Application documents and bank realization receipt verified..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirm Verification & Forward for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return for Correction Modal -->
<div class="modal fade" id="returnCorrectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.deposits.return-correction', $deposit) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise text-warning me-1"></i> Return Application for Rectification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Specify required rectifications or missing documentation for deposit account <strong>{{ $deposit->deposit_number }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label required">Discrepancy Details & Required Rectifications <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Specify what documents or details need maker rectification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm">Return Application to Maker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Deposit Modal -->
<div class="modal fade" id="deleteDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border">
            <form action="{{ route('admin.deposits.destroy', $deposit) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Purge Deposit Account Record</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-2">Are you sure you want to permanently delete deposit account <strong>{{ $deposit->deposit_number }}</strong> (₹{{ number_format($deposit->amount, 2) }})?</p>
                    <div class="alert alert-danger py-2 px-3 small border-0 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        This will permanently delete the deposit contract, transaction schedules, and associated mobilization commission entries. This operation cannot be reversed.
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Record Deletion</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
