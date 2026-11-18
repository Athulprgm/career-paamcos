<!-- Dashboard -->
<div class="tree-diagram-wrap">
    <a href="{{ route('agent.dashboard') }}"
       class="tree-root-item {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}"
       data-tooltip="Dashboard">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </span>
    </a>
</div>

<div class="nav-section-title">Operations</div>

<!-- Business / Leads -->
<div class="tree-diagram-wrap">
    <a href="{{ route('agent.leads.index') }}"
       class="tree-root-item {{ request()->routeIs('agent.leads.*') ? 'active' : '' }}"
       data-tooltip="My Leads">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-briefcase"></i>
            <span>My Leads</span>
        </span>
    </a>
</div>

<!-- My Team / Agents -->
<div class="tree-diagram-wrap">
    <a href="{{ route('agent.agents.index') }}"
       class="tree-root-item {{ request()->routeIs('agent.agents.*') ? 'active' : '' }}"
       data-tooltip="Downline Network">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-people"></i>
            <span>Downline Network</span>
        </span>
    </a>
</div>

<div class="nav-section-title">Rewards & Growth</div>

<!-- Commission -->
@php
    $isAgentCommissionActive = request()->routeIs('admin.commissions.*');
@endphp
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ $isAgentCommissionActive ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Commissions">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-percent"></i>
            <span>Commission</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ $isAgentCommissionActive ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.overview') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.overview') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Overview</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('admin.commissions.index') }}"
               class="tree-link-node {{ request()->routeIs('admin.commissions.index', 'admin.commissions.show', 'admin.commissions.pending', 'admin.commissions.approved', 'admin.commissions.payable', 'admin.commissions.paid', 'admin.commissions.history') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>All Commissions</span>
            </a>
        </div>
    </div>
</div>

<!-- Career & Progression -->
<div class="tree-diagram-wrap">
    <div class="tree-root-item {{ request()->routeIs('agent.performance*') || request()->routeIs('agent.progress*') || request()->routeIs('agent.promotion-history*') ? 'open active' : '' }}"
         onclick="toggleNavGroup(this)"
         data-tooltip="Career & Promotion">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-award"></i>
            <span>Career & Promotion</span>
        </span>
        <i class="bi bi-chevron-down nav-group-caret"></i>
    </div>
    <div class="tree-main-trunk {{ request()->routeIs('agent.performance*') || request()->routeIs('agent.progress*') || request()->routeIs('agent.promotion-history*') ? 'show' : '' }}">
        <div class="tree-branch-item">
            <a href="{{ route('agent.performance') }}"
               class="tree-link-node {{ request()->routeIs('agent.performance*') || request()->routeIs('agent.progress*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Performance & Progress</span>
            </a>
        </div>
        <div class="tree-branch-item">
            <a href="{{ route('agent.promotion-history') }}"
               class="tree-link-node {{ request()->routeIs('agent.promotion-history*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Promotion History</span>
            </a>
        </div>
    </div>
</div>

<div class="nav-section-title">Account</div>

<!-- Notifications -->
<div class="tree-diagram-wrap">
    <a href="{{ route('agent.notifications') }}"
       class="tree-root-item {{ request()->routeIs('agent.notifications*') ? 'active' : '' }}"
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
