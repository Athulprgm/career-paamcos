<!-- Desktop Table View (>= 768px) -->
<div class="d-none d-md-block table-responsive">
    <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light text-uppercase small text-muted">
            <tr>
                <th class="ps-3">Agent</th>
                <th>Rank</th>
                <th>Contact</th>
                <th>Branch</th>
                <th class="text-center">Leads</th>
                <th class="text-center">Sub-Agents</th>
                <th>Joined</th>
                <th>Status</th>
                <th class="text-end pe-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($downlines as $downline)
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-2">
                            @if($downline->profile_image_url)
                                <img src="{{ $downline->profile_image_url }}" alt="{{ $downline->name }}" class="border object-fit-cover flex-shrink-0" style="width: 38px; height: 38px;">
                            @else
                                <div class="bg-dark text-white d-flex align-items-center justify-content-center fw-semibold flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                    {{ strtoupper(substr($downline->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="fw-bold text-dark">{{ $downline->name }}</div>
                                <span class="badge bg-light text-dark font-monospace border">
                                    {{ $downline->member_code ?? $downline->agent_id }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-secondary border">
                            {{ $downline->rank?->name ?? 'Agent' }}
                        </span>
                    </td>
                    <td>
                        <div><i class="bi bi-telephone text-muted me-1"></i>{{ $downline->phone }}</div>
                        <div class="small text-muted"><i class="bi bi-envelope text-muted me-1"></i>{{ $downline->email }}</div>
                    </td>
                    <td>
                        <span class="small fw-medium text-dark">{{ $downline->branch?->name ?? 'Free Field' }}</span>
                        @if($downline->place)
                            <div class="small text-muted" style="font-size:0.75rem;"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $downline->place }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border num-tabular">{{ $downline->leads_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border num-tabular">{{ $downline->downline_agents_count }}</span>
                    </td>
                    <td>
                        <span class="small text-muted num-tabular">
                            {{ $downline->joining_date ? $downline->joining_date->format('d M Y') : '—' }}
                        </span>
                    </td>
                    <td>
                        @if(strtolower((string) $downline->status) === 'active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 d-inline-flex align-items-center gap-1.5"><i class="bi bi-check-circle"></i><span>Active</span></span>
                        @elseif(strtolower((string) $downline->status) === 'pending')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 d-inline-flex align-items-center gap-1.5"><i class="bi bi-hourglass-split"></i><span>Pending Verification</span></span>
                        @elseif(strtolower((string) $downline->status) === 'rejected')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 d-inline-flex align-items-center gap-1.5"><i class="bi bi-x-circle"></i><span>Rejected</span></span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 d-inline-flex align-items-center">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('agent.agents.show', $downline) }}" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-people display-6 d-block mb-3 text-secondary opacity-50"></i>
                            @if(request()->hasAny(['search', 'status']))
                                <h6 class="fw-bold text-dark">No matching agents found</h6>
                                <p class="small mb-3">Try adjusting your search criteria or clearing filters.</p>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="if(window.resetAgentFilters) window.resetAgentFilters();">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                </button>
                            @else
                                <h6 class="fw-bold text-dark">No downline agents yet</h6>
                                <p class="small mb-3">You haven't recruited any downline team members yet. Expand your network by adding new agents under your sponsorship.</p>
                                <a href="{{ route('agent.agents.create') }}" class="btn btn-sm btn-dark">
                                    <i class="bi bi-person-plus me-1"></i> Add First Agent
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile App Feed Cards (< 768px) -->
<div class="d-block d-md-none p-2 p-sm-3">
    @forelse($downlines as $downline)
    <div class="mobile-feed-card">
        <!-- Top Row: Avatar + Details (Name, Code, Status) -->
        <div class="d-flex align-items-start gap-2 mb-2">
            @if($downline->profile_image_url)
                <img src="{{ $downline->profile_image_url }}" alt="{{ $downline->name }}" class="border object-fit-cover flex-shrink-0" style="width: 40px; height: 40px;">
            @else
                <div class="bg-dark text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px; font-size: 0.95rem;">
                    {{ strtoupper(substr($downline->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <a href="{{ route('agent.agents.show', $downline) }}" class="fw-bold text-dark text-decoration-none text-truncate" style="font-size: 0.92rem;">
                        {{ $downline->name }}
                    </a>
                    <div class="flex-shrink-0">
                        @if(strtolower((string) $downline->status) === 'active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 d-inline-flex align-items-center gap-1.5"><i class="bi bi-check-circle"></i><span>Active</span></span>
                        @elseif(strtolower((string) $downline->status) === 'pending')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 d-inline-flex align-items-center gap-1.5"><i class="bi bi-hourglass-split"></i><span>Pending</span></span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 d-inline-flex align-items-center">Inactive</span>
                        @endif
                    </div>
                </div>
                <span class="badge bg-light text-dark font-monospace border" style="font-size: 0.65rem;">
                    {{ $downline->member_code ?? $downline->agent_id }}
                </span>
            </div>
        </div>

        <!-- Meta Pills Row -->
        <div class="d-flex flex-wrap gap-1 mb-2 pt-1">
            <span class="badge bg-light text-secondary border">
                <i class="bi bi-award me-1"></i>{{ $downline->rank?->name ?? 'Agent' }}
            </span>
            <span class="badge bg-light text-dark border">
                <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $downline->branch?->name ?? 'Free Field' }}
            </span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                <i class="bi bi-briefcase me-1"></i>{{ $downline->leads_count }} Leads
            </span>
            <span class="badge bg-dark bg-opacity-10 text-dark border">
                <i class="bi bi-people me-1"></i>{{ $downline->downline_agents_count }} Downlines
            </span>
        </div>

        <!-- Quick 1-Tap Mobile Actions Strip -->
        <div class="d-flex align-items-center justify-content-between gap-1 pt-2 border-top">
            <div class="d-flex align-items-center gap-1">
                @if($downline->phone)
                <a href="tel:{{ $downline->phone }}" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.75rem;">
                    <i class="bi bi-telephone-fill text-success"></i>
                    <span>Call</span>
                </a>
                @endif

                @if($downline->email)
                <a href="mailto:{{ $downline->email }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.75rem;">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                </a>
                @endif
            </div>

            <div>
                <a href="{{ route('agent.agents.show', $downline) }}" class="btn btn-sm btn-dark px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <span>Profile</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted bg-white border p-3">
        <i class="bi bi-people display-6 d-block mb-3 text-secondary opacity-50"></i>
        @if(request()->hasAny(['search', 'status']))
            <h6 class="fw-bold text-dark">No matching agents found</h6>
            <p class="small mb-3">Try adjusting your search criteria or clearing filters.</p>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="if(window.resetAgentFilters) window.resetAgentFilters();">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
            </button>
        @else
            <h6 class="fw-bold text-dark">No downline agents yet</h6>
            <p class="small mb-3">You haven't recruited any downline team members yet. Expand your network by adding new agents under your sponsorship.</p>
            <a href="{{ route('agent.agents.create') }}" class="btn btn-sm btn-dark">
                <i class="bi bi-person-plus me-1"></i> Add First Agent
            </a>
        @endif
    </div>
    @endforelse
</div>

@if($downlines->hasPages())
    <div class="card-footer bg-white border-top p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted small">
            Showing {{ $downlines->firstItem() ?? 0 }} to {{ $downlines->lastItem() ?? 0 }} of {{ number_format($downlines->total()) }} agents
        </span>
        <div>
            {{ $downlines->links() }}
        </div>
    </div>
@endif
