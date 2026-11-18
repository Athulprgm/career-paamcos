@extends('superadmin.layouts.app')

@section('page-title', 'My Promotion History')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">My Promotion History</h1>
        <div class="page-breadcrumb text-muted text-break">
            <span>Agent</span> / <span class="text-dark fw-semibold">Promotion & Rank Elevation History</span>
        </div>
    </div>
    <div class="w-100 w-sm-auto">
        <a href="{{ route('agent.performance') }}" class="btn btn-outline-dark btn-sm w-100 w-sm-auto text-nowrap">
            <i class="bi bi-award me-1"></i> Career Progress
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Current Rank Status Card -->
<div class="card border mb-3 mb-md-4">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="bg-dark text-white p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                    <i class="bi bi-person-badge fs-3"></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1 text-truncate">{{ $agent->name }}</h5>
                    <div class="text-muted small text-break">
                        Agent Code: <span class="fw-semibold text-dark">{{ $agent->member_code ?? $agent->agent_id }}</span>
                    </div>
                </div>
            </div>
            <div class="text-start text-sm-end">
                <span class="text-muted small d-block">Current Official Designation</span>
                <span class="badge bg-success px-3 py-2 text-nowrap" style="font-size: 0.825rem;">
                    <i class="bi bi-award me-1"></i>{{ $agent->rank?->name ?? 'Facilitator' }} (Rank {{ $agent->rank?->level ?? $agent->rank?->sort_order ?? 1 }})
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 g-md-4">
    <!-- Rank Elevation History Table -->
    <div class="col-lg-7">
        <div class="card border h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold text-truncate">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Official Rank Elevations
                </h6>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table View (>= 768px) -->
                <div class="d-none d-md-block table-responsive">
                    <table class="table mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Previous Rank</th>
                                <th>New Promoted Rank</th>
                                <th>Approved By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $history->promotion_date ? $history->promotion_date->format('d M Y') : $history->created_at->format('d M Y') }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $history->previousLevel?->name ?? 'Initial Tier' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success text-white">
                                        <i class="bi bi-award me-1"></i>{{ $history->newLevel?->name ?? 'Promoted' }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $history->approver?->name ?? 'Central Admin' }}
                                </td>
                                <td>
                                    <span class="badge-active">PROMOTED</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small p-3">
                                    No promotions recorded yet. Initial appointment: <strong>{{ $agent->rank?->name ?? 'Facilitator' }}</strong>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Milestone Cards (< 768px) -->
                <div class="d-block d-md-none divide-y">
                    @forelse($histories as $history)
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <span class="fw-bold text-dark" style="font-size: 0.85rem;">
                                <i class="bi bi-calendar-check me-1 text-primary"></i>
                                {{ $history->promotion_date ? $history->promotion_date->format('d M Y') : $history->created_at->format('d M Y') }}
                            </span>
                            <span class="badge bg-success text-white">PROMOTED</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-dark border">{{ $history->previousLevel?->name ?? 'Initial Tier' }}</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span class="badge bg-success text-white">
                                <i class="bi bi-award me-1"></i>{{ $history->newLevel?->name ?? 'Promoted' }}
                            </span>
                        </div>
                        <div class="small text-muted" style="font-size: 0.75rem;">
                            Approved By: <span class="fw-medium text-dark">{{ $history->approver?->name ?? 'Central Admin' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted small">
                        No promotions recorded yet. Initial appointment: <strong>{{ $agent->rank?->name ?? 'Facilitator' }}</strong>.
                    </div>
                    @endforelse
                </div>
            </div>
            @if($histories->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $histories->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Submitted Promotion Requests Log -->
    <div class="col-lg-5">
        <div class="card border h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold text-truncate">
                    <i class="bi bi-arrow-up-circle me-2 text-info"></i>Promotion Requests Log
                </h6>
            </div>
            <div class="card-body p-0">
                @if($promotionRequests->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($promotionRequests as $req)
                    <li class="list-group-item p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-1">
                            <div class="min-w-0">
                                <span class="fw-bold text-dark">{{ $req->request_number }}</span>
                                <span class="text-muted small ms-1">&rarr; {{ $req->proposedLevel?->name }}</span>
                            </div>
                            <span class="badge {{ $req->getStatusBadgeClass() }} text-nowrap">{{ $req->status }}</span>
                        </div>
                        <div class="small text-muted mb-1 text-break">
                            Requested: {{ $req->created_at->format('d M Y, h:i A') }}
                            @if($req->reviewed_at)
                                &bull; Decided: {{ $req->reviewed_at->format('d M Y') }}
                            @endif
                        </div>
                        @if($req->review_reason)
                            <div class="small text-success mt-1 text-break">
                                <i class="bi bi-chat-quote me-1"></i>"{{ $req->review_reason }}"
                            </div>
                        @endif
                        @if($req->rejection_reason)
                            <div class="small text-danger mt-1 text-break">
                                <i class="bi bi-x-circle me-1"></i><strong>Declined:</strong> {{ $req->rejection_reason }}
                            </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4 text-muted small p-3">
                    No promotion requests submitted.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
