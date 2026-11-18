@extends('superadmin.layouts.app')

@section('page-title', 'My Team / Downline Agents')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-sm-center align-items-start gap-2 mb-3 mb-md-4">
    <div class="min-w-0">
        <h1 class="page-title mb-1 text-truncate">My Team / Downline Agents</h1>
        <div class="page-breadcrumb text-muted text-break">
            <span>Agent</span> / <span class="text-dark fw-semibold">Downline Network</span>
        </div>
    </div>
    <div class="w-100 w-sm-auto">
        <a href="{{ route('agent.agents.create') }}" class="btn btn-dark btn-sm w-100 w-sm-auto text-nowrap">
            <i class="bi bi-person-plus me-1"></i> Add New Agent
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

<!-- Overview Stat Cards (2x2 on mobile) -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon dark"><i class="bi bi-people-fill"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="stat-value">{{ number_format($totalDownlines) }}</div>
                <div class="stat-label text-truncate">Direct Downlines</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon emerald"><i class="bi bi-person-check-fill"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="stat-value">{{ number_format($activeDownlines) }}</div>
                <div class="stat-label text-truncate">Active Agents</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="stat-value">{{ number_format($pendingDownlines) }}</div>
                <div class="stat-label text-truncate">Pending Approval</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon black"><i class="bi bi-award-fill"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="stat-value font-monospace text-truncate" style="font-size: 1.15rem;">{{ $currentAgent->member_code ?? $currentAgent->agent_id }}</div>
                <div class="stat-label text-truncate">{{ $currentAgent->rank?->name ?? 'Agent' }} (Lvl {{ $currentAgent->rank?->level ?? 1 }}) &bull; {{ $currentAgent->branch?->name ? ucwords(strtolower($currentAgent->branch->name)) : 'Free Field' }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Quick Status Filter Chips (Visible on Mobile < 768px) -->
<div class="d-md-none mobile-chip-bar">
    <a href="{{ route('agent.agents.index') }}" class="mobile-chip {{ !request('status') ? 'active' : '' }}">
        <span>All Team</span>
        <span class="badge bg-light text-dark border num-tabular">{{ $totalDownlines }}</span>
    </a>
    <a href="{{ route('agent.agents.index', ['status' => 'active']) }}" class="mobile-chip {{ request('status') === 'active' ? 'active' : '' }}">
        <span>Active</span>
        <span class="badge bg-success text-white num-tabular">{{ $activeDownlines }}</span>
    </a>
    <a href="{{ route('agent.agents.index', ['status' => 'pending']) }}" class="mobile-chip {{ request('status') === 'pending' ? 'active' : '' }}">
        <span>Pending</span>
        <span class="badge bg-warning text-dark num-tabular">{{ $pendingDownlines }}</span>
    </a>
    <a href="{{ route('agent.agents.index', ['status' => 'inactive']) }}" class="mobile-chip {{ request('status') === 'inactive' ? 'active' : '' }}">
        <span>Inactive</span>
    </a>
</div>

<!-- Search & Filter Card -->
<div class="card border mb-3 mb-md-4">
    <div class="card-body p-3">
        <form id="agentFiltersForm" method="GET" action="{{ route('agent.agents.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group input-group-sm position-relative">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="agentSearchInput" name="search" class="form-control border-start-0 pe-5" placeholder="Search by name, agent ID, mobile, or email..." value="{{ request('search') }}" autocomplete="off">
                    
                    <!-- Inline Search Spinner & Clear Button -->
                    <div class="position-absolute end-0 top-50 translate-middle-y d-flex align-items-center pe-2" style="z-index: 5;">
                        <span id="agentSearchSpinner" class="spinner-border spinner-border-sm text-primary me-1 d-none" role="status" style="width: 0.85rem; height: 0.85rem;" aria-hidden="true"></span>
                        <button type="button" id="clearAgentSearchBtn" class="btn btn-link btn-sm text-muted p-0 border-0 {{ request('search') ? '' : 'd-none' }}" title="Clear search" style="line-height: 1; text-decoration: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <select id="agentStatusFilter" name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill flex-sm-grow-0 text-nowrap">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('agent.agents.index') }}" id="resetAgentFiltersBtn" class="btn btn-sm btn-outline-secondary flex-fill flex-sm-grow-0 text-nowrap {{ request()->hasAny(['search', 'status']) ? '' : 'd-none' }}">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Downline Agents Table Container -->
<div id="agentsTableContainer" class="card border position-relative">
    @include('agent.agents.partials.table', ['downlines' => $downlines])
</div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('agentFiltersForm');
    if (!form) return;

    const searchInput = document.getElementById('agentSearchInput');
    const clearSearchBtn = document.getElementById('clearAgentSearchBtn');
    const searchSpinner = document.getElementById('agentSearchSpinner');
    const resetFiltersBtn = document.getElementById('resetAgentFiltersBtn');
    const tableContainer = document.getElementById('agentsTableContainer');
    const statusFilter = document.getElementById('agentStatusFilter');

    let debounceTimer = null;
    let abortController = null;

    function hasActiveFilters() {
        return (searchInput && searchInput.value.trim() !== '') ||
               (statusFilter && statusFilter.value !== '');
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

    // Status dropdown change triggers immediately
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            if (debounceTimer) clearTimeout(debounceTimer);
            performSearch();
        });
    }

    // Form submit prevention
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (debounceTimer) clearTimeout(debounceTimer);
        performSearch();
    });

    // Global reset function
    window.resetAgentFilters = function() {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        syncClearButton();
        if (debounceTimer) clearTimeout(debounceTimer);
        performSearch();
    };

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.resetAgentFilters();
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
