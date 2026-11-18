@extends('superadmin.layouts.app')

@section('page-title', 'Commission ' . $commission->commission_number)

@section('content')
<div class="page-header">
    <div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0">{{ $commission->commission_number }}</h1>
            @php $st = strtoupper((string) $commission->status); @endphp
            @if($st === 'PENDING')
                <span class="badge-pending fs-6">PENDING</span>
            @elseif($st === 'APPROVED')
                <span class="badge-approved fs-6">APPROVED</span>
            @elseif($st === 'PAYABLE')
                <span class="badge-payable fs-6">PAYABLE</span>
            @elseif($st === 'PAID')
                <span class="badge-paid fs-6">PAID</span>
            @elseif($st === 'REJECTED')
                <span class="badge-rejected fs-6">REJECTED</span>
            @else
                <span class="badge bg-light text-dark border fs-6">{{ $st }}</span>
            @endif
        </div>
        <div class="page-breadcrumb">Commission / Details & Audit Trail / {{ $commission->commission_number }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>

        @if(!auth()->user()->isAgent())
            @if($commission->canBeApproved())
                <form action="{{ route('admin.commissions.approve', $commission) }}" method="POST" class="d-inline"
                      data-confirm="Approve this commission for ₹{{ number_format($commission->calculated_amount, 2) }}?"
                      data-confirm-title="Approve Commission"
                      data-confirm-type="success"
                      data-confirm-btn="Approve">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Approve
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-lg me-1"></i> Reject
                </button>
            @endif

            @if($commission->canBeMarkedPayable())
                <form action="{{ route('admin.commissions.mark-payable', $commission) }}" method="POST" class="d-inline"
                      data-confirm="Mark this commission as PAYABLE?"
                      data-confirm-title="Mark Commission as Payable"
                      data-confirm-type="success"
                      data-confirm-btn="Mark Payable">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-wallet2 me-1"></i> Mark Payable
                    </button>
                </form>
            @endif

            @if($commission->canBePaid())
                <a href="{{ route('admin.commissions.pay', $commission) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-credit-card me-1"></i> Process Payout
                </a>
            @endif
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Workflow Progress Stepper -->
<div class="card mb-4">
    <div class="card-body py-3">
        @if($commission->isRejected())
            <div class="alert alert-danger mb-0 d-flex align-items-center justify-content-between">
                <div>
                    <strong><i class="bi bi-x-circle-fill me-2"></i>Commission Rejected</strong>
                    <div class="small mt-1">
                        <strong>Reason:</strong> {{ $commission->rejection_reason ?? 'Not specified' }}
                    </div>
                </div>
                <div class="text-end small">
                    <div>Rejected By: {{ $commission->rejecter->name ?? 'Admin' }}</div>
                    <div>Date: {{ $commission->rejected_at ? $commission->rejected_at->format('d M Y, h:i A') : '—' }}</div>
                </div>
            </div>
        @else
            <div class="overflow-auto pb-1">
                <div class="d-flex justify-content-between align-items-center position-relative px-4" style="min-width: 480px;">
                <!-- Step 1: PENDING -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 bg-warning-subtle text-warning"
                         style="width:38px; height:38px; border:2px solid var(--status-pending, #D97706); font-weight:bold;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="small fw-bold">PENDING</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">{{ $commission->created_at->format('d M Y') }}</div>
                </div>

                <div class="flex-grow-1 border-top" style="border-width: 2px !important; border-color: {{ in_array($st, ['APPROVED', 'PAYABLE', 'PAID']) ? 'var(--bs-info, #0284C7)' : 'var(--border-color)' }} !important; margin: 0 -10px 20px;"></div>

                <!-- Step 2: APPROVED -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 {{ in_array($st, ['APPROVED', 'PAYABLE', 'PAID']) ? 'bg-info-subtle text-info' : '' }}"
                         style="width:38px; height:38px; {{ in_array($st, ['APPROVED', 'PAYABLE', 'PAID']) ? 'border:2px solid var(--bs-info, #0284C7);' : 'background:var(--surface-subtle); color:var(--text-muted); border:2px solid var(--border-strong);' }} font-weight:bold;">
                        <i class="bi {{ in_array($st, ['APPROVED', 'PAYABLE', 'PAID']) ? 'bi-check-lg' : 'bi-dash' }}"></i>
                    </div>
                    <div class="small fw-bold {{ in_array($st, ['APPROVED', 'PAYABLE', 'PAID']) ? 'text-primary' : 'text-muted' }}">APPROVED</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">
                        {{ $commission->approved_at ? $commission->approved_at->format('d M Y') : 'Waiting' }}
                    </div>
                </div>

                <div class="flex-grow-1 border-top" style="border-width: 2px !important; border-color: {{ in_array($st, ['PAYABLE', 'PAID']) ? '#10B981' : 'var(--border-color)' }} !important; margin: 0 -10px 20px;"></div>

                <!-- Step 3: PAYABLE -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 {{ in_array($st, ['PAYABLE', 'PAID']) ? 'bg-success-subtle text-success' : '' }}"
                         style="width:38px; height:38px; {{ in_array($st, ['PAYABLE', 'PAID']) ? 'border:2px solid #10B981;' : 'background:var(--surface-subtle); color:var(--text-muted); border:2px solid var(--border-strong);' }} font-weight:bold;">
                        <i class="bi {{ in_array($st, ['PAYABLE', 'PAID']) ? 'bi-wallet2' : 'bi-dash' }}"></i>
                    </div>
                    <div class="small fw-bold {{ in_array($st, ['PAYABLE', 'PAID']) ? 'text-success' : 'text-muted' }}">PAYABLE</div>
                    <div class="text-muted" style="font-size:0.75rem;">
                        {{ in_array($st, ['PAYABLE', 'PAID']) ? 'Ready' : 'Waiting' }}
                    </div>
                </div>

                <div class="flex-grow-1 border-top" style="border-width: 2px !important; border-color: {{ $st === 'PAID' ? 'var(--color-success, #10B981)' : 'var(--border-color)' }} !important; margin: 0 -10px 20px;"></div>

                <!-- Step 4: PAID -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 {{ $st === 'PAID' ? 'bg-success-subtle text-success' : '' }}"
                         style="width:38px; height:38px; {{ $st === 'PAID' ? 'border:2px solid var(--color-success, #10B981);' : 'background:var(--surface-subtle); color:var(--text-muted); border:2px solid var(--border-strong);' }} font-weight:bold;">
                        <i class="bi {{ $st === 'PAID' ? 'bi-check-all' : 'bi-dash' }}"></i>
                    </div>
                    <div class="small fw-bold {{ $st === 'PAID' ? 'text-success' : 'text-muted' }}">PAID</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">
                        {{ $commission->payment_date ? $commission->payment_date->format('d M Y') : 'Waiting' }}
                    </div>
                </div>
            </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Commission Calculation & Traceability -->
    <div class="col-lg-8">
        <!-- Financial Calculation Breakdown Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calculator me-2" style="color:var(--color-primary);"></i>Commission Calculation Breakdown
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 p-3 mb-0" style="background:var(--surface-subtle); border:1px solid var(--border-color);">
                    <div class="col-md-3">
                        <div class="text-muted small">Calculation Basis</div>
                        <div class="fw-bold">{{ str_replace('_', ' ', $commission->rule->calculation_basis ?? 'COLLECTION AMOUNT') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Base Amount</div>
                        <div class="fw-bold fs-5">₹{{ number_format($commission->base_amount, 2) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Rule Rate / Flat</div>
                        <div class="fw-bold fs-5">
                            @if($commission->rule && $commission->rule->commission_type === 'FIXED')
                                Flat ₹{{ number_format($commission->rate, 2) }}
                            @else
                                {{ $commission->rate }}%
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Calculated Commission</div>
                        <div class="fw-bold fs-4 text-success">₹{{ number_format($commission->calculated_amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Traceability: Source Collection & Deposit Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-link-45deg me-2" style="color:var(--color-accent);"></i>Traceable Origin (Collection & Deposit)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @if($commission->collection)
                            <tr>
                                <th style="width:220px;" class="text-muted">Source Collection:</th>
                                <td>
                                    <span class="fw-bold text-dark">{{ $commission->collection->collection_number }}</span>
                                    <span class="badge bg-secondary ms-2">{{ $commission->collection->status }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Collection Due Date:</th>
                                <td>{{ $commission->collection->due_date ? $commission->collection->due_date->format('d M Y') : '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Collection Payment Amount:</th>
                                <td class="fw-semibold text-success">₹{{ number_format($commission->collection->paid_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Collection Payment Date:</th>
                                <td>{{ $commission->collection->collection_date ? $commission->collection->collection_date->format('d M Y') : '—' }}</td>
                            </tr>
                            @else
                            <tr>
                                <th style="width:220px;" class="text-muted">Source Collection:</th>
                                <td class="text-muted italic">Legacy / Direct Deposit Record</td>
                            </tr>
                            @endif

                            <tr>
                                <th class="text-muted">Deposit Account:</th>
                                <td>
                                    @if($commission->deposit)
                                        <a href="{{ route('admin.deposits.show', $commission->deposit) }}" class="fw-bold text-decoration-none" style="color:var(--color-primary);">
                                            {{ $commission->deposit->deposit_number }}
                                        </a>
                                        <span class="ms-2 text-muted">({{ $commission->deposit->scheme->name ?? 'Scheme' }})</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if($commission->deposit && $commission->payout_year)
                            <tr>
                                <th class="text-muted">Tenure Payout Year:</th>
                                <td>
                                    <span class="badge bg-primary text-white">Year {{ $commission->payout_year }}</span>
                                    <span class="text-muted ms-2">(of {{ $commission->deposit->getTenureInYears() }} Year Tenure)</span>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-muted">Customer:</th>
                                <td>
                                    <strong>{{ $commission->customer->name ?? 'N/A' }}</strong>
                                    @if($commission->customer && $commission->customer->phone)
                                        <span class="text-muted ms-2">({{ $commission->customer->phone }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Branch:</th>
                                <td>{{ $commission->branch->name ?? 'N/A' }} ({{ $commission->branch->code ?? '' }})</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Audit Trail / Timeline Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2" style="color:var(--color-secondary);"></i>Audit Trail & Event History
                </h5>
            </div>
            <div class="card-body">
                @if($commission->auditLogs->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($commission->auditLogs as $log)
                            <li class="pb-3 mb-3 border-bottom d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold small">{{ $log->description ?? $log->event }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        By <strong>{{ $log->user->name ?? 'System' }}</strong>
                                        @if($log->ip_address) &bull; IP: {{ $log->ip_address }} @endif
                                    </div>
                                </div>
                                <div class="text-end text-muted small" style="white-space:nowrap;">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted small py-2">
                        <i class="bi bi-info-circle me-1"></i> Commission created on {{ $commission->created_at->format('d M Y, h:i A') }}.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Agent & Rule Details & Payment Info -->
    <div class="col-lg-4">
        <!-- Agent Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-badge me-2" style="color:var(--color-primary);"></i>Agent Profile
                </h5>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $commission->agent->name ?? 'N/A' }}</h6>
                <div class="small text-muted mb-3">{{ $commission->agent->member_code ?? $commission->agent->agent_id }}</div>

                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Rank:</span>
                    <span class="fw-semibold">{{ $commission->agent->rank->name ?? 'Standard' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Agent Status:</span>
                    <span class="{{ $commission->agent && $commission->agent->isActive() ? 'text-success fw-bold' : 'text-danger' }}">
                        {{ $commission->agent->status ?? 'ACTIVE' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Phone:</span>
                    <span>{{ $commission->agent->phone ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Branch:</span>
                    <span>{{ $commission->agent->branch->name ?? $commission->branch->name ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Commission Rule Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-sliders me-2" style="color:var(--color-accent);"></i>Applied Rule
                </h5>
            </div>
            <div class="card-body">
                @if($commission->rule)
                    <h6 class="fw-bold mb-1 text-dark">
                        {{ $commission->rule->name }}
                    </h6>
                    <div class="small text-muted mb-3">Code: <code>{{ $commission->rule->code }}</code></div>

                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Type:</span>
                        <span class="fw-semibold">{{ $commission->rule->commission_type }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Configured Rate:</span>
                        <span class="fw-semibold">{{ $commission->rule->rate }}{{ $commission->rule->commission_type === 'PERCENTAGE' ? '%' : ' (Flat)' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Rule Status:</span>
                        <span class="badge-active">{{ $commission->rule->status }}</span>
                    </div>
                @else
                    <div class="text-muted small">Standard Default Commission Calculation</div>
                @endif
            </div>
        </div>

        <!-- Payout Record Card (if PAID) -->
        @if($commission->isPaid())
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white py-2">
                <h6 class="card-title mb-0 text-white">
                    <i class="bi bi-check-circle me-1"></i> Payment Settlement Record
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Payment Date:</span>
                    <span class="fw-bold">{{ $commission->payment_date ? $commission->payment_date->format('d M Y') : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Amount Paid:</span>
                    <span class="fw-bold text-success fs-6">₹{{ number_format($commission->calculated_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Payment Method:</span>
                    <span class="fw-semibold">{{ $commission->payment_method ?? 'BANK_TRANSFER' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Reference #:</span>
                    <span><code>{{ $commission->reference_number ?? '—' }}</code></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Disbursed By:</span>
                    <span>{{ $commission->payer->name ?? 'Admin' }}</span>
                </div>
                @if($commission->notes)
                <div class="py-2 small">
                    <span class="text-muted d-block">Notes:</span>
                    <em>{{ $commission->notes }}</em>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal for Rejection -->
@if($commission->canBeApproved())
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.commissions.reject', $commission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Commission [{{ $commission->commission_number }}]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Rejecting this commission will mark it as permanently rejected and record your identity and reason in the audit trail.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Explain why this commission is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
