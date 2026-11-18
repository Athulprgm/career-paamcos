@extends('superadmin.layouts.app')

@section('page-title', 'Downline Agent: ' . $agent->name)

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">{{ $agent->name }}</h1>
        <div class="page-breadcrumb text-muted text-break">
            <a href="{{ route('agent.agents.index') }}" class="text-decoration-none">My Team</a> /
            <span class="text-dark fw-semibold">{{ $agent->member_code ?? $agent->agent_id }}</span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
        <a href="{{ route('agent.agents.index') }}" class="btn btn-outline-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        @if($agent->phone)
        <a href="tel:{{ $agent->phone }}" class="btn btn-dark btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-telephone-fill me-1 text-success"></i> Call
        </a>
        @endif
        @php
            $cleanAgentPhone = preg_replace('/[^0-9]/', '', (string) $agent->phone);
        @endphp
        @if($cleanAgentPhone)
        <a href="https://wa.me/{{ $cleanAgentPhone }}?text={{ urlencode('Hello ' . $agent->name . ', greetings from MultiCred.') }}" target="_blank" class="btn btn-outline-success btn-sm flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
        </a>
        @endif
    </div>
</div>

<div class="row g-3 g-md-4">
    <!-- Left Column: Agent Profile Card -->
    <div class="col-lg-4">
        <div class="card border mb-3 mb-md-4">
            <div class="card-body text-center p-3 p-md-4">
                @if($agent->profile_image_url)
                    <img src="{{ $agent->profile_image_url }}" alt="{{ $agent->name }}" class="mx-auto mb-3 border border-2 border-white object-fit-cover d-block" style="width: 76px; height: 76px;">
                @else
                    <div class="bg-dark text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                        <i class="bi bi-person-fill fs-1"></i>
                    </div>
                @endif
                <h5 class="fw-bold mb-1 text-truncate">{{ $agent->name }}</h5>
                <div class="badge bg-light text-dark font-monospace border mb-2 text-nowrap num-tabular">
                    {{ $agent->member_code ?? $agent->agent_id }}
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-light text-secondary border text-nowrap">
                        {{ $agent->rank?->name ?? 'Agent' }}
                    </span>
                    @if(strtolower((string) $agent->status) === 'active')
                        <span class="badge-active text-nowrap">Active</span>
                    @elseif(strtolower((string) $agent->status) === 'pending')
                        <span class="badge-pending text-nowrap"><i class="bi bi-hourglass-split me-1"></i>Pending Verification</span>
                    @elseif(strtolower((string) $agent->status) === 'rejected')
                        <span class="badge-rejected text-nowrap">Rejected</span>
                    @else
                        <span class="badge-inactive text-nowrap">Inactive</span>
                    @endif
                </div>
            </div>
            <div class="border-top p-3 bg-light">
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Direct Sponsor</span>
                    <span class="fw-semibold text-dark text-end ms-2">{{ $currentAgent->name }} (You)</span>
                </div>
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Branch</span>
                    <span class="fw-semibold text-dark text-end ms-2">{{ $agent->branch?->name ?? 'Free Field' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small text-break">
                    <span class="text-muted">Joined On</span>
                    <span class="fw-semibold text-dark text-end ms-2 num-tabular">{{ $agent->joining_date ? $agent->joining_date->format('d M Y') : '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card border mb-3 mb-lg-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title m-0 fw-bold">Contact Details</h6>
            </div>
            <div class="card-body p-3">
                <div class="mb-3 text-break">
                    <label class="text-muted small d-block">Phone Number</label>
                    <a href="tel:{{ $agent->phone }}" class="text-decoration-none text-dark fw-medium num-tabular">
                        <i class="bi bi-telephone text-muted me-1"></i> {{ $agent->phone }}
                    </a>
                </div>
                <div class="mb-3 text-break">
                    <label class="text-muted small d-block">Email Address</label>
                    <a href="mailto:{{ $agent->email }}" class="text-decoration-none text-dark fw-medium">
                        <i class="bi bi-envelope text-muted me-1"></i> {{ $agent->email }}
                    </a>
                </div>
                @if($agent->address)
                    <div class="text-break">
                        <label class="text-muted small d-block">Address</label>
                        <span class="text-dark"><i class="bi bi-geo-alt text-muted me-1"></i> {{ $agent->address }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Performance & Customers -->
    <div class="col-lg-8">
        <!-- Performance Summary Cards (2x2 on mobile) -->
        <div class="row g-2 g-sm-3 mb-3 mb-md-4">
            <div class="col-6">
                <div class="stat-card h-100">
                    <div class="stat-icon dark"><i class="bi bi-briefcase-fill"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="stat-value num-tabular">{{ number_format($agent->leads->count()) }}</div>
                        <div class="stat-label text-truncate">Leads Registered</div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="stat-card h-100">
                    <div class="stat-icon black"><i class="bi bi-diagram-2-fill"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="stat-value num-tabular">{{ number_format($agent->downlineAgents->count()) }}</div>
                        <div class="stat-label text-truncate">Downline Sub-Agents</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads Card -->
        <div class="card border">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="card-title m-0 fw-bold text-truncate">Recent Leads Added by {{ $agent->name }}</h6>
                <span class="badge bg-light text-dark border text-nowrap num-tabular">{{ $agent->leads->count() }} Total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-3">Lead Name</th>
                            <th>Mobile</th>
                            <th>Scheme / Target</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agent->leads->take(10) as $lead)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 160px;">{{ $lead->name }}</div>
                                </td>
                                <td class="num-tabular">{{ $lead->mobile }}</td>
                                <td>
                                    <span class="small text-muted">{{ $lead->interestedScheme?->name ?? 'General Inquiry' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                                </td>
                                <td class="text-end pe-3 text-muted small num-tabular">
                                    {{ $lead->created_at ? $lead->created_at->format('d M Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted p-3">
                                    No leads have been registered by this agent yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
