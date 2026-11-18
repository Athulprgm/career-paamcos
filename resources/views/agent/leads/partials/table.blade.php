<!-- Desktop Table View (>= 768px) -->
<div class="d-none d-md-block table-responsive">
    <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light text-uppercase small text-muted">
            <tr>
                <th class="ps-3">Lead Details</th>
                <th>Branch</th>
                <th>Interested Scheme</th>
                <th class="text-end">Expected Amount</th>
                <th>Status</th>
                <th>Follow-up</th>
                <th class="text-end pe-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leads as $lead)
            @php
                $isToday = $lead->follow_up_date && $lead->follow_up_date->isToday();
                $isOverdue = $lead->follow_up_date && $lead->follow_up_date->isPast() && !$isToday && !$lead->isConverted();
            @endphp
            <tr>
                <td class="ps-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-dark text-white d-flex align-items-center justify-content-center fw-semibold flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.85rem;">
                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('agent.leads.show', $lead) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $lead->name }}
                            </a>
                            <div class="small text-muted">
                                <a href="tel:{{ $lead->mobile }}" class="text-decoration-none text-muted me-2 num-tabular">
                                    <i class="bi bi-telephone me-1"></i>{{ $lead->mobile }}
                                </a>
                                @if($lead->email)
                                    <span class="d-none d-md-inline">&bull; <i class="bi bi-envelope me-1"></i>{{ $lead->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $lead->branch?->name ?? '—' }}
                    </span>
                </td>
                <td>
                    <span class="small fw-medium text-dark">{{ $lead->interestedScheme?->name ?? '—' }}</span>
                </td>
                <td class="text-end fw-semibold text-dark num-tabular">
                    {{ $lead->expected_amount ? '₹'.number_format($lead->expected_amount, 2) : '—' }}
                </td>
                <td>
                    <span class="{{ $lead->getStatusBadgeClass() }}">{{ $lead->status }}</span>
                </td>
                <td>
                    @if($lead->follow_up_date)
                        <span class="small num-tabular {{ $isOverdue ? 'text-danger fw-bold' : ($isToday ? 'text-primary fw-bold' : 'text-muted') }}">
                            <i class="bi bi-calendar3 me-1"></i>{{ $lead->follow_up_date->format('d M Y') }}
                            @if($isToday) <span class="badge bg-primary text-white ms-1">Today</span> @endif
                            @if($isOverdue) <span class="badge bg-danger text-white ms-1">Overdue</span> @endif
                        </span>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('agent.leads.show', $lead) }}" class="btn btn-outline-dark" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if(!$lead->isConverted())
                            <a href="{{ route('agent.leads.edit', $lead) }}" class="btn btn-outline-secondary" title="Edit Lead">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickStatusModal{{ $lead->id }}" title="Quick Status">
                                <i class="bi bi-check2-circle"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteLeadModal{{ $lead->id }}" title="Delete Lead">
                                <i class="bi bi-trash"></i>
                            </button>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
                                <i class="bi bi-check-circle"></i> Converted
                            </span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    @if(request()->hasAny(['search', 'branch_id', 'status', 'follow_up_filter']))
                        <h6 class="fw-bold text-dark">No matching leads found</h6>
                        <p class="small mb-3">No leads match your current search or filter criteria. Try adjusting or clearing your filters.</p>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="if(window.resetLeadFilters) window.resetLeadFilters();">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                        </button>
                    @else
                        <h6>No leads found</h6>
                        <p class="small mb-3">Record prospective customers to start following up and expanding your business.</p>
                        <a href="{{ route('agent.leads.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 px-3 py-1">
                            <i class="bi bi-plus-lg"></i> Add New Lead
                        </a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile App Cards Feed View (< 768px) -->
<div class="d-block d-md-none p-2 p-sm-3">
    @forelse($leads as $lead)
    @php
        $isToday = $lead->follow_up_date && $lead->follow_up_date->isToday();
        $isOverdue = $lead->follow_up_date && $lead->follow_up_date->isPast() && !$isToday && !$lead->isConverted();
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $lead->mobile);
    @endphp
    <div class="mobile-feed-card">
        <!-- Top Row: Avatar + Details (Name, Phone, Status) -->
        <div class="d-flex align-items-start gap-2 mb-2">
            <div class="bg-dark text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.95rem;">
                {{ strtoupper(substr($lead->name, 0, 1)) }}
            </div>
            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <a href="{{ route('agent.leads.show', $lead) }}" class="fw-bold text-dark text-decoration-none text-truncate" style="font-size: 0.92rem;">
                        {{ $lead->name }}
                    </a>
                    <span class="{{ $lead->getStatusBadgeClass() }} flex-shrink-0" style="font-size: 0.68rem; padding: 3px 8px;">
                        {{ $lead->status }}
                    </span>
                </div>
                <div class="text-muted small text-truncate num-tabular">
                    <i class="bi bi-telephone text-muted me-1"></i>{{ $lead->mobile }}
                </div>
            </div>
        </div>

        <!-- Meta Pills Row -->
        <div class="d-flex flex-wrap gap-1 mb-2 pt-1">
            <span class="badge bg-light text-dark border">
                <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $lead->branch?->name ?? 'Branch' }}
            </span>
            <span class="badge bg-light text-secondary border">
                <i class="bi bi-tag me-1"></i>{{ $lead->interestedScheme?->name ?? 'General Inquiry' }}
            </span>
            @if($lead->expected_amount)
            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold num-tabular">
                ₹{{ number_format($lead->expected_amount, 2) }}
            </span>
            @endif
        </div>

        <!-- Follow-up Alert Row (if set) -->
        @if($lead->follow_up_date)
        <div class="p-2 mb-2 d-flex align-items-center justify-content-between {{ $isOverdue ? 'bg-danger bg-opacity-10 text-danger border border-danger-subtle' : ($isToday ? 'bg-primary bg-opacity-10 text-primary border border-primary-subtle' : 'bg-light text-muted border') }}" style="font-size: 0.75rem;">
            <div>
                <i class="bi {{ $isOverdue ? 'bi-exclamation-octagon-fill' : ($isToday ? 'bi-calendar-check-fill' : 'bi-calendar3') }} me-1"></i>
                <span class="fw-semibold">Follow-up:</span> <span class="num-tabular">{{ $lead->follow_up_date->format('d M Y') }}</span>
            </div>
            @if($isToday)
                <span class="badge bg-primary text-white">Today</span>
            @elseif($isOverdue)
                <span class="badge bg-danger text-white">Overdue</span>
            @endif
        </div>
        @endif

        <!-- Quick 1-Tap Mobile Action Strip -->
        <div class="d-flex align-items-center justify-content-between gap-1 pt-2 border-top">
            <div class="d-flex align-items-center gap-1">
                <!-- Direct Call Button -->
                <a href="tel:{{ $lead->mobile }}" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.75rem;" title="Call">
                    <i class="bi bi-telephone-fill text-success"></i>
                    <span>Call</span>
                </a>

                <!-- WhatsApp Button -->
                @if($cleanPhone)
                <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode('Hello ' . $lead->name . ', greetings from MultiCred.') }}" target="_blank" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.75rem;" title="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                    <span>Chat</span>
                </a>
                @endif

                <!-- Quick Status Modal Trigger -->
                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-2 py-1" data-bs-toggle="modal" data-bs-target="#quickStatusModal{{ $lead->id }}" style="font-size: 0.75rem;" title="Update Status">
                    <i class="bi bi-pencil-square"></i>
                    <span>Status</span>
                </button>
            </div>

            <div class="d-flex align-items-center gap-1">
                <a href="{{ route('agent.leads.show', $lead) }}" class="btn btn-sm btn-dark px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <span>View</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted bg-white border p-3">
        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
        @if(request()->hasAny(['search', 'branch_id', 'status', 'follow_up_filter']))
            <h6 class="fw-bold text-dark">No matching leads found</h6>
            <p class="small mb-3">No leads match your current filter. Try adjusting or clearing your filters.</p>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="if(window.resetLeadFilters) window.resetLeadFilters();">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
            </button>
        @else
            <h6 class="fw-bold text-dark">No leads found</h6>
            <p class="small mb-3">Record prospective customers to start following up and expanding your business.</p>
            <a href="{{ route('agent.leads.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 px-3 py-1">
                <i class="bi bi-plus-lg"></i> Add New Lead
            </a>
        @endif
    </div>
    @endforelse
</div>

<!-- Modals for Both Desktop & Mobile -->
@foreach($leads as $lead)
<!-- Quick Status Modal -->
<div class="modal fade text-start" id="quickStatusModal{{ $lead->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border">
            <form method="POST" action="{{ route('agent.leads.quick-status', $lead) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header py-2 px-3 bg-light">
                    <h6 class="modal-title fw-bold small m-0">Update Status: {{ $lead->name }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm" required>
                            @foreach(\App\Models\Lead::getAvailableStatuses() as $k => $label)
                                <option value="{{ $k }}" {{ $lead->status === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control form-control-sm" value="{{ $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Note (Optional)</label>
                        <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Add update notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-dark">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
@if(!$lead->isConverted())
<div class="modal fade text-start" id="deleteLeadModal{{ $lead->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border">
            <form method="POST" action="{{ route('agent.leads.destroy', $lead) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center p-3">
                    <i class="bi bi-exclamation-triangle text-danger fs-1 mb-2 d-block"></i>
                    <h6 class="fw-bold mb-1">Delete Lead?</h6>
                    <p class="small text-muted mb-3">Remove <strong>{{ $lead->name }}</strong> from your leads?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@if($leads->hasPages())
<div class="p-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="small text-muted">
        Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ number_format($leads->total()) }} leads
    </div>
    <div>
        {{ $leads->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endif
