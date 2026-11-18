@php
    $user = auth()->user();
    $canDeposits = $user->isAdmin() || $user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits');
    $canCommissions = $user->isAdmin() || $user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions');
    $canLeads = $user->isAdmin() || $user->hasPermission('view-leads') || $user->hasPermission('manage-leads');
    $canStaff = $user->isAdmin() || $user->hasPermission('view-employees');
    $canReports = $user->isAdmin() || $user->hasPermission('view-reports');
@endphp

<!-- Dashboard -->
<div class="tree-diagram-wrap">
    <a href="{{ route('staff.dashboard') }}"
       class="tree-root-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}"
       data-tooltip="Staff Dashboard">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2"></i>
            <span>Staff Dashboard</span>
        </span>
    </a>
</div>

<div class="nav-section-title">Operational Tasks</div>

@if($canDeposits)
<!-- Deposits Module -->
@php
    $isStaffDepositsActive = request()->routeIs('admin.deposits.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isStaffDepositsActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Deposit Accounts">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-piggy-bank"></i>
            <span>Deposit Accounts</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isStaffDepositsActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.deposits.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.deposits.index', 'admin.deposits.create', 'admin.deposits.show', 'admin.deposits.edit', 'admin.deposits.history') ? 'active' : '' }}">
                <i class="bi bi-card-checklist"></i>
                <span>Deposit Accounts Registry</span>
            </a>
        </div>
    </div>
</div>
@endif

@if($canCommissions)
<!-- Commissions Module -->
@php
    $isStaffCommissionsActive = request()->routeIs('admin.commissions.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isStaffCommissionsActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Commissions">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-percent"></i>
            <span>Commissions</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isStaffCommissionsActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.overview') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.overview') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Overview</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.pending') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.pending') ? 'active' : '' }}">
                <i class="bi bi-hourglass-split"></i>
                <span>Pending Approval</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.index', 'admin.commissions.show', 'admin.commissions.pay', 'admin.commissions.approved', 'admin.commissions.payable', 'admin.commissions.paid', 'admin.commissions.history') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>All Commissions</span>
            </a>
        </div>
    </div>
</div>
@endif

@if($canLeads)
<!-- Agent Leads Module -->
<div class="tree-diagram-wrap">
    <a href="{{ route('admin.leads.index') }}"
       class="tree-root-item {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}"
       data-tooltip="Agent Leads">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-briefcase"></i>
            <span>Agent Leads</span>
        </span>
    </a>
</div>
@endif

@if($canReports)
<div class="nav-section-title">Reports</div>

<!-- Reports & Analytics -->
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ request()->routeIs('admin.reports.*') ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Reports">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-line"></i>
            <span>Reports</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ request()->routeIs('admin.reports.*') ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.reports.overview') }}"
               class="tree-link-node {{ request()->routeIs('admin.reports.overview') ? 'active' : '' }}">
                <i class="bi bi-pie-chart"></i>
                <span>Overview</span>
            </a>
        </div>
        @if($canDeposits)
        <div class="tree-branch-item">
            <a href="{{ route('admin.reports.deposits') }}"
               class="tree-link-node {{ request()->routeIs('admin.reports.deposits') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Deposits Report</span>
            </a>
        </div>
        @endif
    </div>
</div>
@endif

<div class="nav-section-title">Account</div>

<!-- User Profile Settings -->
<div class="tree-diagram-wrap">
    <a href="{{ route('superadmin.profile') }}"
       class="tree-root-item {{ request()->routeIs('superadmin.profile*') ? 'active' : '' }}"
       data-tooltip="My Profile">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-person-gear"></i>
            <span>My Profile</span>
        </span>
    </a>
</div>
