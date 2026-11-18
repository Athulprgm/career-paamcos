@extends('superadmin.layouts.app')

@section('page-title', 'Commission History & Audit')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Commission History</h1>
        <div class="page-breadcrumb">Commission / Settlement & Status History</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.commissions.overview') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Overview
        </a>
        <a href="{{ route('admin.commissions.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-list-check me-1"></i> All Commissions
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.commissions.history') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <!-- Search -->
            <div style="min-width:200px;">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="COM#, Agent, Customer…" value="{{ request('search') }}">
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

            <!-- Agent filter -->
            @if(is_null($restrictedAgentId))
            <div style="min-width:210px;">
                <label class="form-label text-muted small mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $a)
                        <option value="{{ $a->id }}" {{ request('agent_id') == $a->id ? 'selected' : '' }}>
                            {{ $a->name }} ({{ $a->member_code ?? $a->agent_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Status filter -->
            <div style="min-width:155px;">
                <label class="form-label text-muted small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="PAYABLE" {{ request('status') === 'PAYABLE' ? 'selected' : '' }}>Payable</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Period filter -->
            <div style="min-width:150px;">
                <label class="form-label text-muted small mb-1">Period</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="">All Periods</option>
                    @foreach($periods as $p)
                        <option value="{{ $p }}" {{ request('period') === $p ? 'selected' : '' }}>
                            {{ $p }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action buttons -->
            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-dark btn-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.commissions.history') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
                        <th>Commission #</th>
                        <th>Agent / Branch</th>
                        <th>Origin (Coll & Dep)</th>
                        <th class="text-end">Commission (₹)</th>
                        <th class="text-center">Current Status</th>
                        <th>Workflow Actors</th>
                        <th>Settlement Info</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $comm)
                    <tr>
                        <td>
                            <a href="{{ route('admin.commissions.show', $comm) }}" class="fw-semibold text-decoration-none" style="color:var(--color-primary);">
                                {{ $comm->commission_number }}
                            </a>
                            <div class="small text-muted">Period: {{ $comm->period }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $comm->agent->name ?? 'N/A' }}</div>
                            <div class="small text-muted">{{ $comm->branch->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            @if($comm->collection)
                                <div class="small fw-semibold">Coll: {{ $comm->collection->collection_number }}</div>
                            @endif
                            <div class="small text-muted">Dep: {{ $comm->deposit->deposit_number ?? 'N/A' }}</div>
                        </td>
                        <td class="text-end text-success fw-bold">
                            ₹{{ number_format($comm->calculated_amount, 2) }}
                        </td>
                        <td class="text-center">
                            @php $st = strtoupper((string) $comm->status); @endphp
                            @if($st === 'PENDING')
                                <span class="badge-pending">PENDING</span>
                            @elseif($st === 'APPROVED')
                                <span class="badge-approved">APPROVED</span>
                            @elseif($st === 'PAYABLE')
                                <span class="badge-payable">PAYABLE</span>
                            @elseif($st === 'PAID')
                                <span class="badge-paid">PAID</span>
                            @elseif($st === 'REJECTED')
                                <span class="badge-rejected">REJECTED</span>
                            @else
                                <span class="badge bg-light text-dark border">{{ $st }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">
                                @if($comm->approver)
                                    <div><i class="bi bi-check2 text-primary me-1"></i>Appr: {{ $comm->approver->name }}</div>
                                @endif
                                @if($comm->payer)
                                    <div><i class="bi bi-cash-stack text-success me-1"></i>Paid by: {{ $comm->payer->name }}</div>
                                @endif
                                @if(!$comm->approver && !$comm->payer)
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($comm->isPaid())
                                <div class="small fw-semibold text-success">
                                    {{ $comm->payment_date ? $comm->payment_date->format('d M Y') : 'Paid' }}
                                </div>
                                <div class="small text-muted">{{ $comm->payment_method }} &bull; {{ $comm->reference_number ?? 'No Ref' }}</div>
                            @elseif($comm->isRejected())
                                <div class="small text-danger">Rejected: {{ Str::limit($comm->rejection_reason, 35) }}</div>
                            @else
                                <span class="small text-muted">Pending Settlement</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.commissions.show', $comm) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="View Full Details & Audit">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No commission history found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($commissions->hasPages())
    <div class="card-footer py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing {{ $commissions->firstItem() }} to {{ $commissions->lastItem() }} of {{ $commissions->total() }} records
        </div>
        <div>
            {{ $commissions->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
