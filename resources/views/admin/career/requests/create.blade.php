@extends('superadmin.layouts.app')

@section('page-title', 'Submit Promotion Request — ' . $agent->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Submit Promotion Request</h1>
        <div class="page-breadcrumb">Career / Promotion / New Application / {{ $agent->name }}</div>
    </div>
    <a href="{{ route('admin.career.agent-performance', $agent) }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Scorecard
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Agent Overview Banner -->
        <div class="card mb-4" style="border-top: 4px solid var(--color-primary);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-muted small">Candidate Agent:</span>
                        <h4 class="mb-0 fw-bold" style="color:var(--color-primary);">{{ $agent->name }}</h4>
                        <div class="small text-muted">{{ $agent->member_code ?? $agent->agent_id }} &bull; {{ $agent->branch->name ?? 'N/A' }}</div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Current Rank:</span>
                        <span class="badge bg-light text-dark border fs-6">{{ $agent->careerLevel->name ?? 'Level 1' }}</span>
                    </div>
                </div>

                <!-- Eligibility Evaluation Snapshot -->
                @if(!empty($eligibility['criteria']))
                <div class="p-3 mb-4" style="background:var(--surface-subtle); border:1px solid var(--border-color);">
                    <h6 class="fw-bold mb-2" style="color:var(--color-primary);">
                        <i class="bi bi-clipboard-check me-1"></i> Pre-submission Qualification Audit
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th class="text-center">Required Target</th>
                                    <th class="text-center">Actual Achieved</th>
                                    <th class="text-center">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($eligibility['criteria'] as $item)
                                @php
                                    $isAchieved = !empty($item['achieved']) || !empty($item['met']);
                                    $unit = !empty($item['unit']) ? ' ' . $item['unit'] : '';
                                @endphp
                                <tr>
                                    <td class="small">{{ $item['label'] ?? 'Criteria' }}</td>
                                    <td class="text-center small">
                                        @if(is_numeric($item['required']))
                                            {{ $item['required'] > 0 ? (($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['required'], 2) : $item['required'] . $unit) : 'None' }}
                                        @else
                                            {{ $item['required'] }}
                                        @endif
                                    </td>
                                    <td class="text-center small fw-semibold">
                                        @if(is_numeric($item['actual']))
                                            {{ ($item['unit'] ?? '') === '₹' ? '₹' . number_format((float) $item['actual'], 2) : $item['actual'] . $unit }}
                                        @else
                                            {{ $item['actual'] }}
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if(is_numeric($item['required']) && $item['required'] == 0)
                                            <span class="text-muted">—</span>
                                        @elseif($isAchieved)
                                            <span class="text-success fw-bold"><i class="bi bi-check-lg"></i> Met</span>
                                        @else
                                            <span class="text-danger fw-bold"><i class="bi bi-x-lg"></i> Unmet</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Application Form -->
                <form action="{{ route('admin.career.requests.store', $agent) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proposed Promotion Level <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" readonly value="{{ $nextLevel->name ?? 'Higher Rank' }} (Level {{ $nextLevel->sort_order ?? 2 }})">
                            <input type="hidden" name="proposed_level_id" value="{{ $nextLevel->id ?? '' }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Performance Period <span class="text-danger">*</span></label>
                            <input type="month" name="period" class="form-control" value="{{ $period }}" required>
                            <div class="form-text">Month of qualifying performance data.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Requester Remarks / Recommendation Notes</label>
                            <textarea name="remarks" class="form-control" rows="3"
                                      placeholder="State performance highlights, branch recommendation notes, or reasons for promotion..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="{{ route('admin.career.agent-performance', $agent) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success px-4"
                                data-confirm="Confirm submission of promotion request for {{ $agent->name }}?"
                                data-confirm-title="Submit Promotion Request"
                                data-confirm-type="success"
                                data-confirm-btn="Submit Request">
                            <i class="bi bi-arrow-up-circle me-1"></i> Submit Request for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
