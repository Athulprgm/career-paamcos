@extends('superadmin.layouts.app')

@section('page-title', 'Agent Performance Matrix')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Agent Performance Matrix</h1>
        <div class="page-breadcrumb">Career / Real Performance Analytics & Milestones</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.career.overview') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Overview
        </a>
        <a href="{{ route('admin.targets.index') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-crosshair me-1"></i> Targets Dashboard
        </a>
        <a href="{{ route('admin.career.requests.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i> Promotion Requests
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.career.performance') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <!-- Search -->
            <div style="min-width:180px;">
                <label class="form-label text-muted small mb-1">Search Agent</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Name or Agent ID…" value="{{ request('search') }}">
            </div>

            <!-- Branch filter -->
            @if(is_null($restrictedBranchId))
            <div style="min-width:180px;">
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

            <!-- Career Level filter -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Career Level</label>
                <select name="level_id" class="form-select form-select-sm">
                    <option value="">All Levels</option>
                    @foreach($careerLevels as $lvl)
                        <option value="{{ $lvl->id }}" {{ request('level_id') == $lvl->id ? 'selected' : '' }}>
                            Level {{ $lvl->sort_order }} - {{ $lvl->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Performance Period -->
            <div style="min-width:130px;">
                <label class="form-label text-muted small mb-1">Period (YYYY-MM)</label>
                <input type="month" name="period" class="form-control form-control-sm"
                       value="{{ $period }}">
            </div>

            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.career.performance') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Performance Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Current Rank</th>
                        <th class="text-center">Active Customers</th>
                        <th class="text-end">Deposits (₹)</th>
                        <th class="text-end">Target / Eligible</th>
                        <th class="text-center">Progress</th>
                        <th class="text-end">Collections (₹)</th>
                        <th class="text-end">Commission (₹)</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Promotion Eligibility</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                    @php
                        $p = $performances[$agent->id]['metrics'] ?? [];
                        $e = $performances[$agent->id]['eligibility'] ?? [];
                        $ta = $performances[$agent->id]['target_achievement'] ?? [];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.career.agent-performance', $agent) }}" class="fw-semibold text-decoration-none" style="color:var(--color-primary);">
                                {{ $agent->name }}
                            </a>
                            <div class="small text-muted">{{ $agent->member_code ?? $agent->agent_id }}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $agent->branch->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $agent->careerLevel->name ?? 'Initial Rank' }}
                            </span>
                        </td>
                        <td class="text-center fw-bold">
                            {{ $p['active_customers'] ?? 0 }}
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold">₹{{ number_format($p['deposit_amount'] ?? 0, 2) }}</div>
                            <div class="small text-muted">{{ $p['deposit_count'] ?? 0 }} accounts</div>
                        </td>
                        <td class="text-end">
                            @if(!empty($ta['target_amount']))
                                <div class="fw-semibold text-dark">₹{{ number_format($ta['target_amount'], 2) }}</div>
                                <div class="small text-success">Elig: ₹{{ number_format($ta['eligible_deposits'], 2) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(isset($ta['progress_percentage']))
                                <span class="badge {{ $ta['progress_percentage'] >= 100 ? 'bg-success text-white' : 'bg-light text-dark border' }} fw-bold">
                                    {{ $ta['progress_percentage'] }}%
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold text-success">₹{{ number_format($p['collection_amount'] ?? 0, 2) }}</div>
                            <div class="small text-muted">{{ $p['collection_count'] ?? 0 }} coll.</div>
                        </td>
                        <td class="text-end fw-semibold">
                            ₹{{ number_format($p['commission_amount'] ?? 0, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-bold fs-6">
                                {{ number_format($p['performance_score'] ?? 0, 1) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if(!empty($e['is_eligible']))
                                <span class="badge-payable">
                                    <i class="bi bi-check-circle me-1"></i>ELIGIBLE
                                </span>
                            @elseif(!empty($e['has_pending_request']))
                                <span class="badge-pending">
                                    <i class="bi bi-hourglass-split me-1"></i>REQUEST PENDING
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">
                                    NOT ELIGIBLE
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('admin.career.agent-performance', $agent) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Full Performance & Eligibility">
                                    <i class="bi bi-eye"></i> Scorecard
                                </a>
                                <a href="{{ route('admin.targets.agents.show', $agent) }}" class="btn btn-outline-success btn-sm py-1 px-2" title="View Financial Year Targets">
                                    <i class="bi bi-crosshair"></i> Target
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No agent performance records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($agents->hasPages())
    <div class="card-footer py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing {{ $agents->firstItem() }} to {{ $agents->lastItem() }} of {{ $agents->total() }} agents
        </div>
        <div>
            {{ $agents->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
