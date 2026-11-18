<div class="card mb-4 border-0 shadow-sm reports-nav d-print-none">
    <div class="card-body p-2 bg-white rounded-3">
        <div class="d-flex gap-1 overflow-x-auto align-items-center" style="scrollbar-width: thin;">
            <a href="{{ route('admin.reports.overview') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.overview') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-grid-1x2 me-1"></i> Executive Overview
            </a>
            <a href="{{ route('admin.reports.customers') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.customers*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-people me-1"></i> Customers
            </a>
            <a href="{{ route('admin.reports.deposits') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.deposits*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-piggy-bank me-1"></i> Deposits
            </a>

            <a href="{{ route('admin.reports.commissions') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.commissions*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-percent me-1"></i> Commissions
            </a>
            <a href="{{ route('admin.reports.agents') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.agents*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-graph-up me-1"></i> Agent Performance
            </a>
            <a href="{{ route('admin.reports.career') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.career*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-award me-1"></i> Career Progression
            </a>
            <a href="{{ route('admin.reports.audit') }}"
               class="btn btn-sm text-nowrap {{ request()->routeIs('admin.reports.audit*') ? 'btn-dark fw-semibold shadow-sm' : 'btn-light border-0 text-secondary bg-transparent' }}">
                <i class="bi bi-shield-check me-1"></i> Audit Trail
            </a>
        </div>
    </div>
</div>
