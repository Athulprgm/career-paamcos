@extends('superadmin.layouts.app')

@section('page-title', 'Agent Performance Reports')

@section('content')
@include('admin.reports.partials.print-styles', ['orientation' => 'landscape'])

@php
    $branchName = !empty($filters['branch_id']) ? ($branches->firstWhere('id', $filters['branch_id'])->name ?? 'Branch #'.$filters['branch_id']) : 'All Branches';
    $agentName = !empty($filters['agent_id']) ? ($agents->firstWhere('id', $filters['agent_id'])->name ?? 'Agent #'.$filters['agent_id']) : null;
    $levelName = !empty($filters['career_level_id']) ? ($careerLevels->firstWhere('id', $filters['career_level_id'])->name ?? 'Level #'.$filters['career_level_id']) : null;
    $periodLabel = !empty($filters['period']) ? \Carbon\Carbon::parse($filters['period'].'-01')->format('F Y') : 'All Time';

    $printMeta = [
        'Period' => $periodLabel,
        'Branch' => $branchName,
    ];
    if ($agentName) $printMeta['Agent'] = $agentName;
    if ($levelName) $printMeta['Career Level'] = $levelName;
    $printMeta['Evaluated Agents'] = $summary['total_agents'];
@endphp

<!-- Co-operative Official Print Header (Visible only when printed) -->
@include('admin.reports.partials.print-header', [
    'title' => 'Agent Performance & Productivity Report',
    'subtitle' => 'Field Productivity, Portfolio Metrics & Scorecards',
    'meta' => $printMeta
])

<!-- Page Header (Screen only) -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Agent Performance Reports</h1>
        <div class="page-breadcrumb">Reports / Field Productivity, Portfolio Metrics & Scorecards</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center report-actions">
        <a href="{{ route('admin.reports.agents.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<!-- Unified Reports Navigation Tab Bar -->
@include('admin.reports._nav')

<!-- Summary Metrics Cards -->
<div class="row g-3 mb-4 report-summary-cards">
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon dark"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value">{{ $summary['total_agents'] }}</div>
                <div class="stat-label">Evaluated Agents</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon black"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="stat-value">{{ number_format($summary['total_active_customers']) }}</div>
                <div class="stat-label">Client Portfolio</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon emerald"><i class="bi bi-piggy-bank-fill"></i></div>
            <div>
                <div class="stat-value text-success">₹{{ number_format($summary['total_deposit_value'], 2) }}</div>
                <div class="stat-label">Deposits Mobilized</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value">₹{{ number_format($summary['total_collection_value'], 2) }}</div>
                <div class="stat-label">Collections Handled</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-percent"></i></div>
            <div>
                <div class="stat-value text-warning">₹{{ number_format($summary['total_commission'], 2) }}</div>
                <div class="stat-label">Commissions Paid</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon violet"><i class="bi bi-speedometer2"></i></div>
            <div>
                <div class="stat-value text-primary">{{ $summary['avg_performance_score'] }}</div>
                <div class="stat-label">Avg Score / 100</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 filter-card">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reports.agents') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div style="min-width:130px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Period (YYYY-MM)</label>
                <input type="month" name="period" value="{{ $filters['period'] ?? '' }}" class="form-control form-control-sm">
            </div>
            @if($restrictedBranchId === null)
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($restrictedAgentId === null)
            <div style="min-width:175px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $ag)
                        <option value="{{ $ag->id }}" {{ ($filters['agent_id'] ?? '') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="min-width:185px;" class="flex-grow-1 flex-md-grow-0">
                <label class="form-label text-muted small mb-1">Career Level</label>
                <select name="career_level_id" class="form-select form-select-sm">
                    <option value="">All Levels</option>
                    @foreach($careerLevels as $lvl)
                        <option value="{{ $lvl->id }}" {{ ($filters['career_level_id'] ?? '') == $lvl->id ? 'selected' : '' }}>{{ $lvl->name }} ({{ $lvl->code }})</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:180px;" class="flex-grow-1">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, Code or Mobile..." class="form-control form-control-sm">
            </div>
            <div class="d-flex gap-1 align-self-end mt-2 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
                <a href="{{ route('admin.reports.agents') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-table text-muted"></i>
            <span>Agent Productivity Matrix & Scorecards</span>
        </div>
        <span class="badge bg-light text-dark border">{{ $records->total() }} Agents</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Agent</th>
                        <th>Branch</th>
                        <th>Career Level</th>
                        <th class="text-center">Active Cust.</th>
                        <th class="text-center">Deposits</th>
                        <th class="text-end">Deposit Value</th>
                        <th class="text-center">Collections</th>
                        <th class="text-end">Collection Value</th>
                        <th class="text-end">Commission</th>
                        <th class="text-center">Score</th>
                        <th class="text-center d-print-none">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $row)
                    @php
                        $score = $row['performance_score'];
                        $scoreBadge = $score >= 75 ? 'bg-success-subtle text-success border border-success-subtle' : ($score >= 50 ? 'bg-primary-subtle text-primary border border-primary-subtle' : ($score >= 25 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'badge bg-light text-muted border'));
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $row['agent']->name }}</div>
                            <div class="small text-muted font-monospace">{{ $row['agent']->agent_code }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $row['agent']->branch?->name ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $row['career_level'] }}</span>
                        </td>
                        <td class="text-center fw-semibold text-dark">{{ $row['active_customers'] }}</td>
                        <td class="text-center">{{ $row['deposit_count'] }}</td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format((float) $row['deposit_amount'], 2) }}</td>
                        <td class="text-center">{{ $row['collection_count'] }}</td>
                        <td class="text-end fw-semibold text-dark">₹{{ number_format((float) $row['collection_amount'], 2) }}</td>
                        <td class="text-end fw-semibold text-warning">₹{{ number_format((float) $row['commission_amount'], 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $scoreBadge }}">{{ $score }} / 100</span>
                        </td>
                        <td class="text-center d-print-none">
                            <a href="{{ route('admin.career.agent-performance', $row['agent']) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Full Career Scorecard">
                                <i class="bi bi-award"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No agent performance records found matching the specified criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-uppercase text-end">Total / Summary:</td>
                        <td class="text-center">{{ number_format($summary['total_active_customers']) }}</td>
                        <td class="text-center">{{ number_format($records->sum('deposit_count')) }}</td>
                        <td class="text-end text-success">₹{{ number_format((float) $summary['total_deposit_value'], 2) }}</td>
                        <td class="text-center">{{ number_format($records->sum('collection_count')) }}</td>
                        <td class="text-end">₹{{ number_format((float) $summary['total_collection_value'], 2) }}</td>
                        <td class="text-end text-warning">₹{{ number_format((float) $summary['total_commission'], 2) }}</td>
                        <td class="text-center">{{ $summary['avg_performance_score'] }} / 100</td>
                        <td class="d-print-none"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @if($records->hasPages())
    <div class="card-footer bg-transparent py-3">
        {{ $records->links() }}
    </div>
    @endif
</div>

<!-- Co-operative Official Verification & Sign-off Block (Visible only when printed) -->
@include('admin.reports.partials.print-footer')
@endsection
