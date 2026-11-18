<!-- Dashboard -->
<div class="tree-diagram-wrap">
    <a href="{{ route('branch-admin.dashboard') }}"
       class="tree-root-item {{ request()->routeIs('branch-admin.dashboard') ? 'active' : '' }}"
       data-tooltip="Dashboard">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </span>
    </a>
</div>

<div class="nav-section-title">Operations</div>

<!-- Staff -->
@php
    $isBranchStaffActive = request()->routeIs('admin.staff.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isBranchStaffActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Staff">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-person-workspace"></i>
            <span>Staff</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isBranchStaffActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.staff.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.staff.index', 'admin.staff.show', 'admin.staff.edit') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>All Staff</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.staff.create') }}"
               class="tree-link-node {{ request()->routeIs('admin.staff.create') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i>
                <span>Add Staff</span>
            </a>
        </div>
    </div>
</div>

<!-- Agents & Leads -->
@php
    $isBranchAgentsLeadsActive = request()->routeIs('admin.agents.*', 'admin.leads.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isBranchAgentsLeadsActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Agents & Leads">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-person-vcard"></i>
            <span>Agents & Leads</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isBranchAgentsLeadsActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.agents.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>All Agents</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.leads.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                <i class="bi bi-briefcase"></i>
                <span>Agent Leads</span>
            </a>
        </div>
    </div>
</div>

<div class="nav-section-title">Finance</div>

<!-- Deposits -->
@php
    $isBranchDepositsActive = request()->routeIs('admin.deposits.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isBranchDepositsActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Deposits">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-piggy-bank"></i>
            <span>Deposits</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isBranchDepositsActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.deposits.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.deposits.index', 'admin.deposits.create', 'admin.deposits.show', 'admin.deposits.edit', 'admin.deposits.history') ? 'active' : '' }}">
                <i class="bi bi-folder2"></i>
                <span>All Deposits</span>
            </a>
        </div>
    </div>
</div>

<!-- Commission -->
@php
    $isBranchCommissionsActive = request()->routeIs('admin.commissions.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isBranchCommissionsActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Commissions">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-percent"></i>
            <span>Commission</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isBranchCommissionsActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.overview') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.overview') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Overview</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.index', 'admin.commissions.show', 'admin.commissions.pay', 'admin.commissions.pending', 'admin.commissions.approved', 'admin.commissions.payable', 'admin.commissions.paid', 'admin.commissions.history') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>All Commissions</span>
            </a>
        </div>
    </div>
</div>

<div class="nav-section-title">Growth & Performance</div>

<!-- Career -->
@php
    $isBranchCareerActive = request()->routeIs('admin.career.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isBranchCareerActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Career & Progression">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-award"></i>
            <span>Career & Progression</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isBranchCareerActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.career.overview') }}"
               class="tree-link-node {{ request()->routeIs('admin.career.overview') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Overview</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.career.performance') }}"
               class="tree-link-node {{ request()->routeIs('admin.career.performance*', 'admin.career.agent-performance') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Performance Matrix</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.career.requests.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.career.requests.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-up-circle"></i>
                <span>Promotion Requests</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.career.history') }}"
               class="tree-link-node {{ request()->routeIs('admin.career.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Promotion History</span>
            </a>
        </div>
    </div>
</div>

<!-- Reports -->
<div class="tree-diagram-wrap">
    <a href="{{ route('admin.reports.overview') }}"
       class="tree-root-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
       data-tooltip="Reports">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Reports & Analytics</span>
        </span>
    </a>
</div>

<div class="nav-section-title">System</div>

<!-- Notifications -->
<div class="tree-diagram-wrap">
    <a href="{{ route('branch-admin.notifications') }}"
       class="tree-root-item {{ request()->routeIs('branch-admin.notifications*') ? 'active' : '' }}"
       data-tooltip="Notifications">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
        </span>
    </a>
</div>

<!-- Profile -->
<div class="tree-diagram-wrap">
    <a href="{{ route('superadmin.profile') }}"
       class="tree-root-item {{ request()->routeIs('superadmin.profile*') ? 'active' : '' }}"
       data-tooltip="Profile Settings">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span>Profile Settings</span>
        </span>
    </a>
</div>
