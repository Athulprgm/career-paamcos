@extends('superadmin.layouts.app')

@section('page-title', 'Career History & Audit')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Career History</h1>
        <div class="page-breadcrumb">Career / Permanent Progression Audit Trail</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.career.overview') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Career Overview
        </a>
        <a href="{{ route('admin.career.performance') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-graph-up me-1"></i> Performance Matrix
        </a>
        <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i> Promotion Requests
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.career.history') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <!-- Search -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Agent name, remarks…" value="{{ request('search') }}">
            </div>

            <!-- Branch filter -->
            @if(is_null($restrictedBranchId))
            <div style="min-width:160px;">
                <label class="form-label text-muted small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.career.history') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- History Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Promotion Date</th>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Previous Level</th>
                        <th>Promoted Level</th>
                        <th>Action</th>
                        <th>Approved By</th>
                        <th>Remarks / Context</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $hist)
                    <tr>
                        <td class="fw-semibold">
                            {{ $hist->promotion_date ? $hist->promotion_date->format('d M Y') : '—' }}
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $hist->agent->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $hist->agent->member_code ?? $hist->agent->agent_id }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $hist->agent->branch->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            @if($hist->previousLevel)
                                <span class="badge bg-light text-dark border">{{ $hist->previousLevel->name }}</span>
                            @else
                                <span class="text-muted small">Initial Tier</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-success text-white">
                                <i class="bi bi-award me-1"></i>{{ $hist->newLevel->name ?? 'New Level' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $hist->action }}</span>
                        </td>
                        <td>
                            <div class="small">{{ $hist->approver->name ?? 'System Admin' }}</div>
                            @if($hist->performance_period)
                                <div class="text-muted" style="font-size:0.75rem;">Period: {{ $hist->performance_period }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="small text-muted">{{ Str::limit($hist->remarks, 50) ?? '—' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No career history records logged yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($histories->hasPages())
    <div class="card-footer py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }} of {{ $histories->total() }} records
        </div>
        <div>
            {{ $histories->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
