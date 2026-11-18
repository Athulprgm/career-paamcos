@extends('superadmin.layouts.app')

@section('page-title', 'Promotion Request ' . $promotionRequest->request_number)

@section('content')
<div class="page-header">
    <div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0">{{ $promotionRequest->request_number }}</h1>
            @if($promotionRequest->status === 'PENDING_REVIEW')
                <span class="badge-pending fs-6">PENDING REVIEW</span>
            @elseif($promotionRequest->status === 'APPROVED')
                <span class="badge-approved fs-6">APPROVED</span>
            @elseif($promotionRequest->status === 'PROMOTED')
                <span class="badge-payable fs-6">PROMOTED</span>
            @elseif($promotionRequest->status === 'REJECTED')
                <span class="badge-rejected fs-6">REJECTED</span>
            @else
                <span class="badge bg-light text-dark border fs-6">{{ $promotionRequest->status }}</span>
            @endif
        </div>
        <div class="page-breadcrumb">Career / Promotion Request Details / {{ $promotionRequest->request_number }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Requests
        </a>

        @if(!auth()->user()->isAgent() && $promotionRequest->canBeReviewed())
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="bi bi-check2-circle me-1"></i> Approve & Promote
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
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

<!-- Promotion Workflow Stepper -->
<div class="card mb-4">
    <div class="card-body py-3">
        @if($promotionRequest->isRejected())
            <div class="alert alert-danger mb-0 d-flex align-items-center justify-content-between">
                <div>
                    <strong><i class="bi bi-x-circle-fill me-2"></i>Promotion Request Rejected</strong>
                    <div class="small mt-1">
                        <strong>Reason:</strong> {{ $promotionRequest->rejection_reason ?? 'Not specified' }}
                    </div>
                </div>
                <div class="text-end small">
                    <div>Reviewed By: {{ $promotionRequest->reviewer->name ?? 'Admin' }}</div>
                    <div>Date: {{ $promotionRequest->reviewed_at ? $promotionRequest->reviewed_at->format('d M Y, h:i A') : '—' }}</div>
                </div>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center position-relative px-4">
                <!-- Step 1: PENDING_REVIEW -->
                <!-- Step 1 -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 bg-warning-subtle text-warning"
                         style="width:38px; height:38px; border:2px solid var(--status-pending, #D97706); font-weight:bold;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="small fw-bold">PENDING REVIEW</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">{{ $promotionRequest->request_date ? $promotionRequest->request_date->format('d M Y') : '' }}</div>
                </div>

                <div class="flex-grow-1 border-top" style="border-width: 2px !important; border-color: {{ in_array($promotionRequest->status, ['APPROVED', 'PROMOTED']) ? 'var(--bs-info, #0284C7)' : 'var(--border-color)' }} !important; margin: 0 -10px 20px;"></div>

                <!-- Step 2: APPROVED -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 {{ in_array($promotionRequest->status, ['APPROVED', 'PROMOTED']) ? 'bg-info-subtle text-info' : '' }}"
                         style="width:38px; height:38px; {{ in_array($promotionRequest->status, ['APPROVED', 'PROMOTED']) ? 'border:2px solid var(--bs-info, #0284C7);' : 'background:var(--surface-subtle); color:var(--text-muted); border:2px solid var(--border-strong);' }} font-weight:bold;">
                        <i class="bi {{ in_array($promotionRequest->status, ['APPROVED', 'PROMOTED']) ? 'bi-check-lg' : 'bi-dash' }}"></i>
                    </div>
                    <div class="small fw-bold {{ in_array($promotionRequest->status, ['APPROVED', 'PROMOTED']) ? 'text-primary' : 'text-muted' }}">APPROVED</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">
                        {{ $promotionRequest->reviewed_at ? $promotionRequest->reviewed_at->format('d M Y') : 'Pending' }}
                    </div>
                </div>

                <div class="flex-grow-1 border-top" style="border-width: 2px !important; border-color: {{ $promotionRequest->status === 'PROMOTED' ? '#10B981' : 'var(--border-color)' }} !important; margin: 0 -10px 20px;"></div>

                <!-- Step 3: PROMOTED -->
                <div class="text-center position-relative" style="z-index:2;">
                    <div class="d-flex align-items-center justify-content-center mx-auto mb-1 {{ $promotionRequest->status === 'PROMOTED' ? 'bg-success-subtle text-success' : '' }}"
                         style="width:38px; height:38px; {{ $promotionRequest->status === 'PROMOTED' ? 'border:2px solid #10B981;' : 'background:var(--surface-subtle); color:var(--text-muted); border:2px solid var(--border-strong);' }} font-weight:bold;">
                        <i class="bi {{ $promotionRequest->status === 'PROMOTED' ? 'bi-award' : 'bi-dash' }}"></i>
                    </div>
                    <div class="small fw-bold {{ $promotionRequest->status === 'PROMOTED' ? 'text-success' : 'text-muted' }}">PROMOTED</div>
                    <div class="text-muted num-tabular" style="font-size:0.75rem;">
                        {{ $promotionRequest->promoted_at ? $promotionRequest->promoted_at->format('d M Y') : 'Pending' }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Qualification Evaluation Snapshot -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-data me-2" style="color:var(--color-primary);"></i>Eligibility Evaluation Snapshot
                </h5>
                @if(!empty($promotionRequest->rule_snapshot['name']))
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-trophy me-1 text-warning"></i> Rule: {{ $promotionRequest->rule_snapshot['name'] }}
                    </span>
                @elseif($promotionRequest->rule)
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-trophy me-1 text-warning"></i> Rule: {{ $promotionRequest->rule->name }}
                    </span>
                @endif
            </div>
            <div class="card-body p-0">
                @php
                    $criteriaList = $promotionRequest->eligibility_result['criteria'] ?? [];
                @endphp
                @if(!empty($criteriaList))
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Criteria Rule</th>
                                <th class="text-center">Required Target</th>
                                <th class="text-center">Actual Achieved</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criteriaList as $item)
                            @php
                                $isAchieved = !empty($item['achieved']) || !empty($item['met']);
                                $unit = !empty($item['unit']) ? ' ' . $item['unit'] : '';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item['label'] ?? 'Criteria' }}</div>
                                </td>
                                <td class="text-center">
                                    @if(is_numeric($item['required'] ?? null))
                                        @if($item['required'] > 0)
                                            {{ ($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['required'], 2) : $item['required'] . $unit }}
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    @else
                                        {{ $item['required'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">
                                    @if(is_numeric($item['actual'] ?? null))
                                        {{ ($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['actual'], 2) : $item['actual'] . $unit }}
                                    @else
                                        {{ $item['actual'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(is_numeric($item['required'] ?? null) && $item['required'] == 0)
                                        <span class="badge bg-light text-muted border">—</span>
                                    @elseif($isAchieved)
                                        <span class="badge bg-success text-white"><i class="bi bi-check-lg me-1"></i>Met</span>
                                    @else
                                        <span class="badge bg-danger text-white"><i class="bi bi-x-lg me-1"></i>Unmet</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    No qualification snapshot recorded with this request.
                </div>
                @endif
            </div>
        </div>

        @if($promotionRequest->remarks)
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="card-title mb-0">Requester Recommendation Notes</h6>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted fst-italic">"{{ $promotionRequest->remarks }}"</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column: Agent Profile & Review Details -->
    <div class="col-lg-5">
        <!-- Candidate Agent Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-vcard me-2" style="color:var(--color-primary);"></i>Candidate Agent
                </h5>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $promotionRequest->agent->name ?? 'N/A' }}</h5>
                <div class="small text-muted mb-3">{{ $promotionRequest->agent->member_code ?? $promotionRequest->agent->agent_id }}</div>

                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Branch:</span>
                    <span>{{ $promotionRequest->branch->name ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Current Career Rank:</span>
                    <span class="fw-bold">{{ $promotionRequest->currentLevel->name ?? 'Level 1' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Proposed Rank:</span>
                    <span class="fw-bold text-success">{{ $promotionRequest->proposedLevel->name ?? 'Next Level' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Performance Period:</span>
                    <span>{{ $promotionRequest->performance_period ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Submission Date:</span>
                    <span>{{ $promotionRequest->request_date ? $promotionRequest->request_date->format('d M Y') : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Submitted By:</span>
                    <span>{{ $promotionRequest->requester->name ?? 'System' }}</span>
                </div>
            </div>
        </div>

        <!-- Review / Promoted Audit Record -->
        @if($promotionRequest->reviewer)
        <div class="card mb-4 {{ $promotionRequest->isPromoted() ? 'border-success' : ($promotionRequest->isRejected() ? 'border-danger' : '') }}">
            <div class="card-header {{ $promotionRequest->isPromoted() ? 'bg-success text-white' : ($promotionRequest->isRejected() ? 'bg-danger text-white' : 'bg-transparent') }} py-2">
                <h6 class="card-title mb-0 {{ $promotionRequest->isPromoted() || $promotionRequest->isRejected() ? 'text-white' : '' }}">
                    <i class="bi bi-shield-check me-1"></i> Review Decision Log
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Reviewed By:</span>
                    <strong>{{ $promotionRequest->reviewer->name }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-muted">Decision Date:</span>
                    <span>{{ $promotionRequest->reviewed_at ? $promotionRequest->reviewed_at->format('d M Y, h:i A') : '—' }}</span>
                </div>
                @if($promotionRequest->review_reason)
                <div class="py-2 small">
                    <span class="text-muted d-block">Approval Remarks:</span>
                    <em>{{ $promotionRequest->review_reason }}</em>
                </div>
                @endif
                @if($promotionRequest->rejection_reason)
                <div class="py-2 small text-danger">
                    <span class="d-block fw-bold">Rejection Reason:</span>
                    <em>{{ $promotionRequest->rejection_reason }}</em>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal: Approve & Promote -->
@if(!auth()->user()->isAgent() && $promotionRequest->canBeReviewed())
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.career.requests.approve', $promotionRequest) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Promotion [{{ $promotionRequest->request_number }}]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Approving this request will immediately promote <strong>{{ $promotionRequest->agent->name }}</strong> to <strong>{{ $promotionRequest->proposedLevel->name }}</strong> and create an immutable career history record.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Approval Remarks / Commendation</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Optional approval remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm & Promote Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.career.requests.reject', $promotionRequest) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Promotion [{{ $promotionRequest->request_number }}]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Please state the reason for rejecting this promotion request. The candidate will remain at their current career rank.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why the promotion is being declined..."></textarea>
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
