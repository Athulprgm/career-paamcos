@extends('superadmin.layouts.app')

@section('page-title', 'Business Leads')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">Business & Leads</h1>
        <div class="page-breadcrumb text-muted text-break">
            <span>Agent</span> / <span class="text-dark fw-semibold">Leads Management</span>
        </div>
    </div>
    <div>
        <a href="{{ route('agent.leads.create') }}" class="btn btn-primary btn-sm text-nowrap d-inline-flex align-items-center gap-1 px-3 py-1">
            <i class="bi bi-plus-lg"></i>
            <span>Add New Lead</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 mb-md-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Overview Stat Cards (2x2 on mobile for app feel) -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index') }}" class="text-decoration-none">
            <div class="stat-card h-100 {{ (!request('status') && !request('follow_up_filter')) ? 'border-primary' : '' }}">
                <div class="stat-icon dark"><i class="bi bi-briefcase-fill"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="stat-value">{{ number_format($statusCounts['all']) }}</div>
                    <div class="stat-label text-truncate">Total Leads</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index', ['status' => 'NEW']) }}" class="text-decoration-none">
            <div class="stat-card h-100 {{ request('status') === 'NEW' ? 'border-primary' : '' }}">
                <div class="stat-icon black"><i class="bi bi-person-plus-fill"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="stat-value text-primary">{{ number_format($statusCounts['new']) }}</div>
                    <div class="stat-label text-truncate">New Inquiries</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index', ['status' => 'INTERESTED']) }}" class="text-decoration-none">
            <div class="stat-card h-100 {{ request('status') === 'INTERESTED' ? 'border-warning' : '' }}">
                <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="stat-value text-warning">{{ number_format($statusCounts['contacted'] + $statusCounts['interested']) }}</div>
                    <div class="stat-label text-truncate">In Follow-Up</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('agent.leads.index', ['status' => 'CONVERTED']) }}" class="text-decoration-none">
            <div class="stat-card h-100 {{ request('status') === 'CONVERTED' ? 'border-success' : '' }}">
                <div class="stat-icon emerald"><i class="bi bi-check-circle-fill"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="stat-value text-success">{{ number_format($statusCounts['converted']) }}</div>
                    <div class="stat-label text-truncate">Converted</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Mobile Quick Status Filter Chips (Visible on Mobile < 768px) -->
<div class="d-md-none mobile-chip-bar">
    <a href="{{ route('agent.leads.index') }}" class="mobile-chip {{ (!request('status') && !request('follow_up_filter')) ? 'active' : '' }}">
        <span>All</span>
        <span class="badge bg-light text-dark border num-tabular">{{ $statusCounts['all'] }}</span>
    </a>
    <a href="{{ route('agent.leads.index', ['status' => 'NEW']) }}" class="mobile-chip {{ request('status') === 'NEW' ? 'active' : '' }}">
        <span>New</span>
        <span class="badge bg-light text-dark border num-tabular">{{ $statusCounts['new'] }}</span>
    </a>
    <a href="{{ route('agent.leads.index', ['status' => 'INTERESTED']) }}" class="mobile-chip {{ request('status') === 'INTERESTED' ? 'active' : '' }}">
        <span>Follow-up</span>
        <span class="badge bg-light text-dark border num-tabular">{{ $statusCounts['contacted'] + $statusCounts['interested'] }}</span>
    </a>
    <a href="{{ route('agent.leads.index', ['status' => 'CONVERTED']) }}" class="mobile-chip {{ request('status') === 'CONVERTED' ? 'active' : '' }}">
        <span>Converted</span>
        <span class="badge bg-light text-dark border num-tabular">{{ $statusCounts['converted'] }}</span>
    </a>
    <a href="{{ route('agent.leads.index', ['follow_up_filter' => 'today']) }}" class="mobile-chip {{ request('follow_up_filter') === 'today' ? 'active' : '' }}">
        <i class="bi bi-calendar2-check text-warning"></i>
        <span>Due Today</span>
    </a>
    <a href="{{ route('agent.leads.index', ['follow_up_filter' => 'overdue']) }}" class="mobile-chip {{ request('follow_up_filter') === 'overdue' ? 'active' : '' }}">
        <i class="bi bi-exclamation-octagon text-danger"></i>
        <span>Overdue</span>
    </a>
</div>

<!-- Search & Filter Card -->
<div class="card border mb-3 mb-md-4">
    <div class="card-body p-3">
        <form id="leadFiltersForm" method="GET" action="{{ route('agent.leads.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm position-relative">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="leadSearchInput" name="search" class="form-control border-start-0 pe-5" placeholder="Search name, phone, email, scheme..." value="{{ request('search') }}" autocomplete="off">
                    
                    <!-- Inline Search Spinner & Clear Button -->
                    <div class="position-absolute end-0 top-50 translate-middle-y d-flex align-items-center pe-2" style="z-index: 5;">
                        <span id="leadSearchSpinner" class="spinner-border spinner-border-sm text-primary me-1 d-none" role="status" style="width: 0.85rem; height: 0.85rem;" aria-hidden="true"></span>
                        <button type="button" id="clearLeadSearchBtn" class="btn btn-link btn-sm text-muted p-0 border-0 {{ request('search') ? '' : 'd-none' }}" title="Clear search" style="line-height: 1; text-decoration: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <select id="branchFilter" name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select id="statusFilter" name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Lead::getAvailableStatuses() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select id="followUpFilter" name="follow_up_filter" class="form-select form-select-sm">
                    <option value="">All Follow-up Dates</option>
                    <option value="today" {{ request('follow_up_filter') === 'today' ? 'selected' : '' }}>Due Today</option>
                    <option value="upcoming" {{ request('follow_up_filter') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="overdue" {{ request('follow_up_filter') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill text-nowrap">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('agent.leads.index') }}" id="resetLeadFiltersBtn" class="btn btn-sm btn-outline-secondary flex-shrink-0 {{ request()->hasAny(['search', 'branch_id', 'status', 'follow_up_filter']) ? '' : 'd-none' }}" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Leads Table Container -->
<div id="leadsTableContainer" class="card border shadow-sm position-relative">
    @include('agent.leads.partials.table', ['leads' => $leads])
</div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('leadFiltersForm');
    if (!form) return;

    const searchInput = document.getElementById('leadSearchInput');
    const clearSearchBtn = document.getElementById('clearLeadSearchBtn');
    const searchSpinner = document.getElementById('leadSearchSpinner');
    const resetFiltersBtn = document.getElementById('resetLeadFiltersBtn');
    const tableContainer = document.getElementById('leadsTableContainer');
    const branchFilter = document.getElementById('branchFilter');
    const statusFilter = document.getElementById('statusFilter');
    const followUpFilter = document.getElementById('followUpFilter');

    let debounceTimer = null;
    let abortController = null;

    function hasActiveFilters() {
        return (searchInput && searchInput.value.trim() !== '') ||
               (branchFilter && branchFilter.value !== '') ||
               (statusFilter && statusFilter.value !== '') ||
               (followUpFilter && followUpFilter.value !== '');
    }

    function syncResetButton() {
        if (!resetFiltersBtn) return;
        if (hasActiveFilters()) {
            resetFiltersBtn.classList.remove('d-none');
        } else {
            resetFiltersBtn.classList.add('d-none');
        }
    }

    function syncClearButton() {
        if (!clearSearchBtn || !searchInput) return;
        if (searchInput.value.length > 0) {
            clearSearchBtn.classList.remove('d-none');
        } else {
            clearSearchBtn.classList.add('d-none');
        }
    }

    function performSearch(targetUrl = null) {
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        let fetchUrl;
        if (targetUrl) {
            fetchUrl = new URL(targetUrl, window.location.origin);
        } else {
            fetchUrl = new URL(form.getAttribute('action') || window.location.pathname, window.location.origin);
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                if (value !== '') {
                    fetchUrl.searchParams.set(key, value);
                }
            }
        }

        // Show loading visual states
        if (searchSpinner) searchSpinner.classList.remove('d-none');
        if (tableContainer) {
            tableContainer.style.transition = 'opacity 0.15s ease';
            tableContainer.style.opacity = '0.55';
        }

        fetch(fetchUrl.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            signal: abortController.signal
        })
        .then(response => {
            if (!response.ok) throw new Error('Live search request failed');
            return response.json();
        })
        .then(data => {
            if (tableContainer && data.html !== undefined) {
                tableContainer.innerHTML = data.html;
            }
            window.history.replaceState({}, '', fetchUrl.toString());
            syncResetButton();
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            console.error('Realtime search error:', err);
        })
        .finally(() => {
            if (searchSpinner) searchSpinner.classList.add('d-none');
            if (tableContainer) tableContainer.style.opacity = '1';
        });
    }

    // Realtime typing search with responsive 250ms debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            syncClearButton();
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performSearch();
            }, 250);
        });

        // Enter key triggers immediate search without full page reload
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (debounceTimer) clearTimeout(debounceTimer);
                performSearch();
            }
        });
    }

    // Clear search button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                syncClearButton();
                if (debounceTimer) clearTimeout(debounceTimer);
                performSearch();
            }
        });
    }

    // Filter dropdowns trigger search immediately on change
    [branchFilter, statusFilter, followUpFilter].forEach(select => {
        if (select) {
            select.addEventListener('change', function() {
                if (debounceTimer) clearTimeout(debounceTimer);
                performSearch();
            });
        }
    });

    // Form submit prevention
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (debounceTimer) clearTimeout(debounceTimer);
        performSearch();
    });

    // Global reset function exposed for empty state button
    window.resetLeadFilters = function() {
        if (searchInput) searchInput.value = '';
        if (branchFilter) branchFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (followUpFilter) followUpFilter.value = '';
        syncClearButton();
        if (debounceTimer) clearTimeout(debounceTimer);
        performSearch();
    };

    // Reset button click
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.resetLeadFilters();
        });
    }

    // Realtime pagination click interception
    if (tableContainer) {
        tableContainer.addEventListener('click', function(e) {
            const pageLink = e.target.closest('.pagination a');
            if (pageLink && pageLink.href) {
                e.preventDefault();
                performSearch(pageLink.href);
                tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
})();
</script>
@endpush
