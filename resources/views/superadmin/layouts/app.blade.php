<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#14532D">
    <title>@yield('page-title', 'Dashboard') — MultiCred</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <script>
        (function() {
            try {
                var theme = localStorage.getItem('multicred_theme');
                if (!theme) {
                    theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                var metaTheme = document.querySelector('meta[name="theme-color"]');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                    if (metaTheme) metaTheme.setAttribute('content', '#090F0C');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    if (metaTheme) metaTheme.setAttribute('content', '#14532D');
                }
                if (window.innerWidth >= 768 && localStorage.getItem('multicred_sidebar_collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.enterprise-theme')
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}">
    @stack('styles')
</head>
<body class="{{ ((auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin()) || request()->is('agent*')) ? 'is-agent-context' : '' }}">

<!-- Mobile Sidebar Backdrop -->
<div id="sidebar-backdrop" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<nav id="sidebar">
    @php
        $navDashboardUrl = (auth()->check() && auth()->user()->isBranchAdmin())
            ? route('branch-admin.dashboard')
            : ((auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin())
                ? route('agent.dashboard')
                : ((auth()->check() && auth()->user()->isStaff() && !auth()->user()->isAdmin())
                    ? route('staff.dashboard')
                    : route('superadmin.dashboard')));
    @endphp
    <div class="sidebar-brand">
        <a href="{{ $navDashboardUrl }}" class="brand-wrapper" title="MultiCred Co-operative Banking">
            <div class="brand-logo">
                @if(file_exists(public_path('logo.png')))
                    <img src="{{ asset('logo.png') }}" alt="MultiCred Logo">
                @else
                    <i class="bi bi-wallet2 text-white"></i>
                @endif
            </div>
            <div class="brand-text">
                <div class="brand-title-row">
                    <span class="brand-title">MultiCred</span>
                    <span class="brand-badge">{{ auth()->check() && auth()->user()->isBranchAdmin() ? 'BRANCH' : (auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin() ? 'AGENT' : (auth()->check() && auth()->user()->isStaff() && !auth()->user()->isAdmin() ? 'STAFF' : (auth()->check() && auth()->user()->isSuperAdmin() ? 'SUPER ADMIN' : 'ADMIN'))) }}</span>
                </div>
                <span class="brand-sub">Co-operative Banking</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn d-md-none" onclick="closeSidebar()" title="Close Sidebar" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    @if(auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin())
        @php
            $agentHeaderProfile = auth()->user()->agentProfile;
        @endphp
        @if($agentHeaderProfile)
            @php
                $agentName = $agentHeaderProfile->name ?? 'Agent';
                $words = preg_split('/\s+/', trim($agentName));
                $initials = '';
                foreach(array_slice($words, 0, 2) as $w) {
                    if (!empty($w)) {
                        $initials .= strtoupper(substr($w, 0, 1));
                    }
                }
                $initials = $initials ?: 'AG';
                $agentCode = $agentHeaderProfile->member_code ?? $agentHeaderProfile->agent_id ?? '';
                $branchName = $agentHeaderProfile->branch?->name;
            @endphp
            <a href="{{ route('superadmin.profile') }}"
               class="sidebar-context-widget"
               title="{{ $agentName }}@if($agentCode) ({{ $agentCode }})@endif @if($branchName) &bull; {{ $branchName }}@endif">
                <div class="sidebar-context-avatar overflow-hidden">
                    @if($agentHeaderProfile->profile_image_url)
                        <img src="{{ $agentHeaderProfile->profile_image_url }}" alt="{{ $agentName }}" class="w-100 h-100 object-fit-cover">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="sidebar-context-body">
                    <span class="sidebar-context-name">{{ $agentName }}</span>
                    <span class="sidebar-context-subtitle">
                        @if($agentCode && $branchName)
                            {{ $agentCode }} <span class="sidebar-context-sep">&middot;</span> {{ $branchName }}
                        @elseif($agentCode)
                            {{ $agentCode }}
                        @elseif($branchName)
                            {{ $branchName }}
                        @endif
                    </span>
                </div>
            </a>
        @endif
    @elseif(auth()->check() && (auth()->user()->isBranchAdmin() || auth()->user()->isStaff()) && (auth()->user()->branch ?? auth()->user()->staffProfile?->branch))
        @php
            $userBranch = auth()->user()->branch ?? auth()->user()->staffProfile?->branch;
            $userBranchName = $userBranch->name ?? 'Branch';
            $userBranchCode = $userBranch->code ?? '';
        @endphp
        <div class="sidebar-context-widget" title="{{ $userBranchName }}@if($userBranchCode) ({{ $userBranchCode }})@endif">
            <div class="sidebar-context-avatar">
                <i class="bi bi-building"></i>
            </div>
            <div class="sidebar-context-body">
                <span class="sidebar-context-name">{{ $userBranchName }}</span>
                <span class="sidebar-context-subtitle">
                    @if($userBranchCode)
                        {{ $userBranchCode }} <span class="sidebar-context-sep">&middot;</span> Branch Office
                    @else
                        Branch Office
                    @endif
                </span>
            </div>
        </div>
    @endif

    <div class="sidebar-nav-container">
        @if(auth()->check() && auth()->user()->isBranchAdmin())
            @include('branch-admin.layouts.nav')
        @elseif(auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin())
            @include('agent.layouts.nav')
        @elseif(auth()->check() && auth()->user()->isStaff() && !auth()->user()->isAdmin())
            @include('staff.layouts.nav')
        @else
        <!-- Dashboard -->
        <div class="tree-diagram-wrap">
            <a href="{{ $navDashboardUrl }}"
               class="tree-root-item {{ request()->routeIs('superadmin.dashboard') || request()->routeIs('branch-admin.dashboard') ? 'active' : '' }}"
               data-tooltip="Dashboard">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </span>
            </a>
        </div>

        @if(auth()->check() && auth()->user()->isSuperAdmin())
        <div class="nav-section-title">Structure</div>

        <!-- Branch & Structure -->
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ request()->routeIs('superadmin.branches*') || request()->routeIs('superadmin.branch-admins*') || request()->routeIs('superadmin.staff-positions*') || request()->routeIs('superadmin.agent-ranks*') || request()->routeIs('superadmin.permissions*') ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="Branch & Structure">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3"></i>
                    <span>Branch & Structure</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>
            <div class="tree-main-trunk {{ request()->routeIs('superadmin.branches*') || request()->routeIs('superadmin.branch-admins*') || request()->routeIs('superadmin.staff-positions*') || request()->routeIs('superadmin.agent-ranks*') || request()->routeIs('superadmin.permissions*') ? 'show' : '' }}">
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.branches.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.branches*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>
                        <span>Branches</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.branch-admins.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.branch-admins*') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i>
                        <span>Branch Admins</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.staff-positions.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.staff-positions*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>
                        <span>Staff Positions</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.agent-ranks.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.agent-ranks*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>
                        <span>Agent Ranks</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.permissions.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.permissions*') ? 'active' : '' }}">
                        <i class="bi bi-shield-lock"></i>
                        <span>Permissions & Roles</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if(auth()->check() && auth()->user()->isAdmin())
        <div class="nav-section-title">People & Team</div>

        <!-- People -->
        @php
            $isPeopleActive = request()->routeIs('admin.staff.*', 'admin.agents.*', 'admin.leads.*');
        @endphp
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ $isPeopleActive ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="People & Team">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-people"></i>
                    <span>People & Team</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>
            <div class="tree-main-trunk {{ $isPeopleActive ? 'show' : '' }}">
                <div class="tree-branch-item">
                    <a href="{{ route('admin.staff.index') }}"
                       class="tree-link-node {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                        <i class="bi bi-person-workspace"></i>
                        <span>Staff Members</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.agents.index') }}"
                       class="tree-link-node {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">
                        <i class="bi bi-person-vcard"></i>
                        <span>Agents</span>
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
        @endif

        <div class="nav-section-title">Finance & Deposits</div>

        <!-- Deposits -->
        @php
            $isDepositsActive = request()->routeIs('admin.deposits.*', 'admin.deposit-schemes.*');
        @endphp
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ $isDepositsActive ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="Deposit Accounts">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-piggy-bank"></i>
                    <span>Deposit Accounts</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>

            <!-- Main Trunk -->
            <div class="tree-main-trunk {{ $isDepositsActive ? 'show' : '' }}">
                <!-- Branch 1: All Deposits -->
                <div class="tree-branch-item">
                    <a href="{{ route('admin.deposits.index') }}"
                       class="tree-link-node {{ request()->routeIs('admin.deposits.index', 'admin.deposits.create', 'admin.deposits.show', 'admin.deposits.edit', 'admin.deposits.history') ? 'active' : '' }}">
                        <i class="bi bi-folder2"></i>
                        <span>Deposit Accounts Registry</span>
                    </a>
                </div>

                <!-- Branch 2: Deposit Schemes (Super Admin Only) -->
                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <div class="tree-branch-item">
                    <a href="{{ route('admin.deposit-schemes.index') }}"
                       class="tree-link-node {{ request()->routeIs('admin.deposit-schemes.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>Deposit Schemes</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="nav-section-title">Commissions & Rules</div>

        <!-- Commission -->
        @php
            $isCommissionsActive = request()->routeIs('admin.commissions.*', 'superadmin.fd-commissions*');
        @endphp
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ $isCommissionsActive ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="Commissions">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-percent"></i>
                    <span>Commission</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>

            <!-- Main Trunk -->
            <div class="tree-main-trunk {{ $isCommissionsActive ? 'show' : '' }}">
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

                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.fd-commissions.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.fd-commissions*') ? 'active' : '' }}">
                        <i class="bi bi-percent"></i>
                        <span>Deposit Commissions</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="nav-section-title">Career & Progression</div>

        <!-- Career & Progression -->
        @php
            $isCareerActive = request()->routeIs('admin.career.*', 'superadmin.promotion-rules*');
        @endphp
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ $isCareerActive ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="Career & Progression">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-award"></i>
                    <span>Career & Progression</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>

            <div class="tree-main-trunk {{ $isCareerActive ? 'show' : '' }}">
                <div class="tree-branch-item">
                    <a href="{{ route('admin.career.overview') }}"
                       class="tree-link-node {{ request()->routeIs('admin.career.overview') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Overview</span>
                    </a>
                </div>

                <div class="tree-branch-item">
                    <a href="{{ route('admin.career.performance') }}"
                       class="tree-link-node {{ request()->routeIs('admin.career.performance', 'admin.career.agent-performance') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Performance</span>
                    </a>
                </div>

                <div class="tree-branch-item">
                    <a href="{{ route('admin.career.requests.index') }}"
                       class="tree-link-node {{ request()->routeIs('admin.career.requests.index', 'admin.career.requests.create', 'admin.career.requests.show') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Promotion Requests</span>
                    </a>
                </div>

                <div class="tree-branch-item">
                    <a href="{{ route('admin.career.requests.approved') }}"
                       class="tree-link-node {{ request()->routeIs('admin.career.requests.approved') ? 'active' : '' }}">
                        <i class="bi bi-check2-circle"></i>
                        <span>Approved Promotions</span>
                    </a>
                </div>

                <div class="tree-branch-item">
                    <a href="{{ route('admin.career.history') }}"
                       class="tree-link-node {{ request()->routeIs('admin.career.history') ? 'active' : '' }}">
                        <i class="bi bi-archive"></i>
                        <span>Career History</span>
                    </a>
                </div>

                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <div class="tree-branch-item">
                    <a href="{{ route('superadmin.promotion-rules.index') }}"
                       class="tree-link-node {{ request()->routeIs('superadmin.promotion-rules*') ? 'active' : '' }}">
                        <i class="bi bi-stars"></i>
                        <span>Promotion Rules</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="nav-section-title">Reports & System</div>

        <!-- Reports -->
        <div class="tree-diagram-wrap">
            <div class="tree-root-item {{ request()->routeIs('admin.reports.*') ? 'open active' : '' }}"
                 onclick="toggleNavGroup(this)"
                 data-tooltip="Reports & Analytics">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart"></i>
                    <span>Reports</span>
                </span>
                <i class="bi bi-chevron-down nav-group-caret"></i>
            </div>

            <div class="tree-main-trunk {{ request()->routeIs('admin.reports.*') ? 'show' : '' }}">
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.overview') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.overview') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Overview</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.customers') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.customers') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Customers Report</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.deposits') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.deposits') ? 'active' : '' }}">
                        <i class="bi bi-piggy-bank"></i>
                        <span>Deposits Report</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.commissions') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.commissions') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i>
                        <span>Commissions Report</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.agents') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.agents') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>
                        <span>Agents Report</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.career') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.career') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>Career Report</span>
                    </a>
                </div>
                <div class="tree-branch-item">
                    <a href="{{ route('admin.reports.audit') }}"
                       class="tree-link-node {{ request()->routeIs('admin.reports.audit') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Audit Report</span>
                    </a>
                </div>
            </div>
        </div>

        @if(auth()->check() && auth()->user()->isAdmin())
        <!-- Profile Settings (All Admins) -->
        <div class="tree-diagram-wrap">
            <a href="{{ route('superadmin.profile') }}"
               class="tree-root-item {{ request()->routeIs('superadmin.profile*') ? 'active' : '' }}"
               data-tooltip="Profile Settings">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-gear"></i>
                    <span>Profile Settings</span>
                </span>
            </a>
        </div>
        @endif
        @endif
    </div>

    <!-- Sidebar footer -->
    @php
        $sidebarRoleLabel = auth()->check() && auth()->user()->isSuperAdmin()
            ? 'Super Admin'
            : (auth()->check() && auth()->user()->isBranchAdmin()
                ? 'Branch Admin'
                : (auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin()
                    ? 'Agent'
                    : (auth()->check() && auth()->user()->isStaff() && !auth()->user()->isAdmin()
                        ? 'Staff'
                        : 'Logged in as')));
        $sidebarUserName = auth()->user()->name
            ?? (auth()->check() && auth()->user()->isBranchAdmin() ? 'Branch Administrator' : 'System Administrator');
        $sidebarInitials = collect(preg_split('/\s+/', trim($sidebarUserName)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('') ?: 'AD';
    @endphp
    <div class="sidebar-footer">
        <div class="sidebar-user-info" data-tooltip="{{ $sidebarUserName }} ({{ $sidebarRoleLabel }})" title="{{ $sidebarUserName }}">
            <div class="sidebar-user-row">
                <div class="sidebar-footer-avatar">
                    @if(auth()->check() && auth()->user()->profile_image_url)
                        <img src="{{ auth()->user()->profile_image_url }}" alt="{{ $sidebarUserName }}">
                    @else
                        {{ $sidebarInitials }}
                    @endif
                </div>
                <div class="sidebar-footer-text">
                    <div class="sidebar-footer-label">{{ $sidebarRoleLabel }}</div>
                    <div class="sidebar-footer-name text-truncate">{{ $sidebarUserName }}</div>
                </div>
            </div>
        </div>
        <form action="{{ route('superadmin.logout') }}" method="POST" class="m-0 p-0">
            @csrf
            <button type="submit" class="sidebar-signout-btn" data-tooltip="Sign Out" title="Sign Out">
                <i class="bi bi-box-arrow-left"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</nav>
<div id="sidebar-floating-tooltip" role="tooltip" aria-hidden="true"></div>

<!-- Main -->
<div id="main-content">
    <!-- Top Bar -->
    <header id="topbar">
        @php
            $isAgentUserContext = (auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin()) || request()->is('agent*');
        @endphp
        <div class="d-flex align-items-center gap-2 min-w-0" style="min-width: 0; flex: 1;">
            @if($isAgentUserContext)
                <!-- Agent Mobile App Brand Logo -->
                <a href="{{ route('agent.dashboard') }}" class="d-flex d-md-none align-items-center gap-2 text-decoration-none flex-shrink-0" title="MultiCred">
                    <div class="bg-primary bg-opacity-10 d-flex align-items-center justify-content-center p-1 border border-primary border-opacity-25 rounded-2" style="width: 32px; height: 32px;">
                        @if(file_exists(public_path('logo.png')))
                            <img src="{{ asset('logo.png') }}" alt="Logo" style="width: 22px; height: 22px; object-fit: contain;">
                        @else
                            <i class="bi bi-wallet2 text-primary fs-6"></i>
                        @endif
                    </div>
                </a>
                <button class="sidebar-toggle-btn d-none d-md-flex" id="sidebarToggleBtn" type="button" onclick="toggleSidebar()" title="Toggle Sidebar" aria-label="Toggle Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
            @else
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" onclick="toggleSidebar()" title="Toggle Sidebar" aria-label="Toggle Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
            @endif
            <span class="topbar-title text-truncate fw-bold" style="color: var(--text-heading);" title="@yield('page-title', 'Dashboard')">@yield('page-title', 'Dashboard')</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Global Light / Dark Theme Switcher -->
            <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Switch Light/Dark Theme" aria-label="Toggle Theme">
                <i class="bi bi-sun-fill theme-icon-light"></i>
                <i class="bi bi-moon-fill theme-icon-dark"></i>
            </button>

            @if($isAgentUserContext)
                <a href="{{ route('agent.notifications') }}" class="topbar-icon-btn position-relative" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>

                <!-- Agent Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown" aria-expanded="false" title="Account Menu">
                        @if(auth()->user()?->profile_image_url)
                            <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="user-avatar object-fit-cover border">
                        @else
                            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'AG', 0, 2)) }}</div>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border mt-1 p-2" style="min-width: 220px; z-index: 1060;">
                        <li class="px-2 py-2 mb-1 border-bottom">
                            <div class="fw-bold text-truncate" style="color: var(--text-heading); font-size: 0.88rem;">{{ auth()->user()->name ?? 'Agent' }}</div>
                            <div class="small text-muted" style="font-size: 0.72rem;">{{ auth()->user()->email ?? '' }}</div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace mt-1" style="font-size: 0.65rem;">
                                {{ auth()->user()->agentProfile?->member_code ?? 'AGENT' }}
                            </span>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('superadmin.profile') }}">
                                <i class="bi bi-person text-muted"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('agent.performance') }}">
                                <i class="bi bi-award text-muted"></i>
                                <span>Career & Promotion</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('admin.commissions.overview') }}">
                                <i class="bi bi-cash-coin text-muted"></i>
                                <span>Commissions</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('superadmin.logout') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 small text-danger fw-semibold">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <!-- Admin / Staff / Branch Admin Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle dropdown-toggle-nocaret gap-2" data-bs-toggle="dropdown" aria-expanded="false" title="Account Menu">
                        @if(auth()->user()?->profile_image_url)
                            <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="user-avatar object-fit-cover border bg-white p-0.5 rounded-circle">
                        @else
                            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}</div>
                        @endif
                        <div class="d-none d-sm-block text-truncate text-start" style="max-width: 140px;">
                            <div style="font-weight:600;font-size:0.8rem;color:var(--text-heading);" class="text-truncate">{{ auth()->user()->name ?? 'User' }}</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);" class="text-truncate">
                                {{ auth()->check() && auth()->user()->isSuperAdmin() ? 'Super Admin' : (auth()->check() && auth()->user()->isBranchAdmin() ? 'Branch Admin' : (auth()->check() && auth()->user()->isStaff() ? 'Staff' : 'Administrator')) }}
                            </div>
                        </div>
                        <i class="bi bi-chevron-down text-muted small d-none d-sm-inline" style="font-size: 0.65rem;"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border mt-1 p-2" style="min-width: 220px; z-index: 1060;">
                        <li class="px-2 py-2 mb-1 border-bottom">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if(auth()->user()?->profile_image_url)
                                    <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="border border-1 border-secondary border-opacity-25 bg-white p-0.5 rounded-circle flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                @endif
                                <div class="min-w-0">
                                    <div class="fw-bold text-truncate" style="color:var(--text-heading); font-size: 0.88rem;">{{ auth()->user()->name ?? 'User' }}</div>
                                    <div class="small text-muted text-truncate" style="font-size: 0.72rem;">{{ auth()->user()->email ?? '' }}</div>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.65rem;">
                                {{ auth()->check() && auth()->user()->isSuperAdmin() ? 'SUPER ADMIN' : (auth()->check() && auth()->user()->isBranchAdmin() ? 'BRANCH ADMIN' : (auth()->check() && auth()->user()->isStaff() ? 'STAFF' : 'ADMIN')) }}
                            </span>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('superadmin.profile') }}">
                                <i class="bi bi-person text-muted"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                        @if(auth()->check() && auth()->user()->isSuperAdmin())
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('superadmin.branches.index') }}">
                                <i class="bi bi-building text-muted"></i>
                                <span>Branches</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->isBranchAdmin())
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 small" href="{{ route('branch-admin.notifications') }}">
                                <i class="bi bi-bell text-muted"></i>
                                <span>Notifications</span>
                            </a>
                        </li>
                        @endif
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('superadmin.logout') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 small text-danger fw-semibold">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </header>

    <!-- Content -->
    <main class="content-wrapper">
        @include('partials.toasts')

        @yield('content')
    </main>

    @if((auth()->check() && auth()->user()->isAgent() && !auth()->user()->isAdmin()) || request()->is('agent*'))
    <!-- Agent Mobile Bottom App Navigation Bar -->
    <nav class="agent-mobile-bottom-nav d-flex d-md-none" aria-label="Mobile Navigation">
        <a href="{{ route('agent.dashboard') }}" class="mobile-nav-item {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
            <i class="bi {{ request()->routeIs('agent.dashboard') ? 'bi-grid-fill' : 'bi-grid' }}"></i>
            <span>Home</span>
        </a>

        <a href="{{ route('agent.leads.index') }}" class="mobile-nav-item {{ (request()->is('agent/leads*') && !request()->routeIs('agent.leads.create')) ? 'active' : '' }}">
            <i class="bi {{ (request()->is('agent/leads*') && !request()->routeIs('agent.leads.create')) ? 'bi-briefcase-fill' : 'bi-briefcase' }}"></i>
            <span>Leads</span>
        </a>

        <!-- Center Floating Action Button (FAB) -->
        <a href="{{ route('agent.leads.create') }}" class="mobile-nav-fab {{ request()->routeIs('agent.leads.create') ? 'active' : '' }}" title="Add New Lead" aria-label="Add New Lead">
            <div class="mobile-nav-fab-btn">
                <i class="bi bi-plus-lg"></i>
            </div>
            <span>Add</span>
        </a>

        <a href="{{ route('agent.agents.index') }}" class="mobile-nav-item {{ request()->is('agent/agents*') ? 'active' : '' }}">
            <i class="bi {{ request()->is('agent/agents*') ? 'bi-people-fill' : 'bi-people' }}"></i>
            <span>Team</span>
        </a>

        <a href="{{ route('agent.performance') }}" class="mobile-nav-item {{ (request()->routeIs('agent.performance*') || request()->routeIs('agent.progress*') || request()->routeIs('agent.promotion-history*')) ? 'active' : '' }}">
            <i class="bi {{ (request()->routeIs('agent.performance*') || request()->routeIs('agent.progress*') || request()->routeIs('agent.promotion-history*')) ? 'bi-award-fill' : 'bi-award' }}"></i>
            <span>Career</span>
        </a>
    </nav>
    @endif
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.confirm-modal')
@include('partials.loading-overlay')
<script>
function isMobile() {
    return window.innerWidth < 768;
}

function hideSidebarTooltip() {
    const tooltip = document.getElementById('sidebar-floating-tooltip');
    if (tooltip) {
        tooltip.classList.remove('visible');
        tooltip.setAttribute('aria-hidden', 'true');
    }
}

function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const bd = document.getElementById('sidebar-backdrop');
    hideSidebarTooltip();

    if (isMobile()) {
        const isOpen = sb && (sb.classList.contains('open') || document.body.classList.contains('sidebar-mobile-open'));
        if (isOpen) {
            closeSidebar();
        } else {
            if (sb) sb.classList.add('open');
            document.body.classList.add('sidebar-mobile-open');
            if (bd) bd.classList.add('show');
        }
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        document.documentElement.classList.toggle('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        try {
            localStorage.setItem('multicred_sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    }
}

function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const bd = document.getElementById('sidebar-backdrop');
    if (sb) sb.classList.remove('open');
    document.body.classList.remove('sidebar-mobile-open');
    if (bd) bd.classList.remove('show');
    hideSidebarTooltip();
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.documentElement.classList.contains('sidebar-collapsed')) {
        document.body.classList.add('sidebar-collapsed');
    }

    const tooltipEl = document.getElementById('sidebar-floating-tooltip');
    const navContainer = document.querySelector('.sidebar-nav-container');

    // Populate data-tooltip and title attributes, plus floating hover tooltip when collapsed
    document.querySelectorAll('#sidebar .tree-root-item, #sidebar .nav-link-item, #sidebar .sidebar-context-widget, #sidebar .sidebar-user-info, #sidebar .sidebar-signout-btn').forEach(function(item) {
        if (!item.getAttribute('data-tooltip')) {
            const textEl = item.querySelector('span span, span:last-child') || item.querySelector('span');
            const label = textEl ? textEl.textContent.trim() : item.textContent.trim();
            if (label) {
                item.setAttribute('data-tooltip', label);
            }
        }
        const tipLabel = item.getAttribute('data-tooltip');
        if (tipLabel && !item.getAttribute('title')) {
            item.setAttribute('title', tipLabel);
        }

        if (tooltipEl && tipLabel) {
            item.addEventListener('mouseenter', function() {
                const collapsed = document.body.classList.contains('sidebar-collapsed') || document.documentElement.classList.contains('sidebar-collapsed');
                if (!collapsed || isMobile()) {
                    return;
                }
                const rect = item.getBoundingClientRect();
                tooltipEl.textContent = tipLabel;
                tooltipEl.style.left = (rect.right + 10) + 'px';
                tooltipEl.style.top = (rect.top + rect.height / 2) + 'px';
                tooltipEl.setAttribute('aria-hidden', 'false');
                tooltipEl.classList.add('visible');
            });
            item.addEventListener('mouseleave', hideSidebarTooltip);
        }
    });

    if (navContainer) {
        navContainer.addEventListener('scroll', hideSidebarTooltip, { passive: true });
        const activeLink = navContainer.querySelector('.tree-link-node.active, a.tree-root-item.active');
        if (activeLink) {
            activeLink.scrollIntoView({ block: 'nearest' });
        }
    }

    // Close mobile sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMobile()) {
            closeSidebar();
        }
    });

    // Safe Lucide and Flowbite initialization
    if (typeof window.refreshIcons === 'function') {
        try { window.refreshIcons(); } catch (e) {}
    } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons({ icons: window.lucide.icons || {} }); } catch (e) {}
    }
    if (typeof window.initFlowbite === 'function') {
        try { window.initFlowbite(); } catch (e) {}
    }
});

window.addEventListener('resize', function() {
    hideSidebarTooltip();
    if (!isMobile()) {
        closeSidebar();
        if (localStorage.getItem('multicred_sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
            document.documentElement.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
            document.documentElement.classList.remove('sidebar-collapsed');
        }
    } else {
        document.body.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('sidebar-collapsed');
    }
});

function toggleNavGroup(el) {
    const isCollapsed = document.body.classList.contains('sidebar-collapsed') || document.documentElement.classList.contains('sidebar-collapsed');
    const inner = el.nextElementSibling;
    if (isCollapsed && !isMobile()) {
        toggleSidebar();
        el.classList.add('open');
        if (inner) {
            inner.classList.add('show');
        }
        return;
    }
    el.classList.toggle('open');
    if (inner) {
        inner.classList.toggle('show');
    }
}
function toggleNavTree(el, event) {
    if (event) {
        event.stopPropagation();
    }
    el.classList.toggle('open');
    const inner = el.nextElementSibling;
    if (inner) {
        inner.classList.toggle('show');
    }
}
function toggleNavSubgroup(el, event) {
    toggleNavTree(el, event);
}

function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
    const next = isDark ? 'light' : 'dark';
    const metaTheme = document.querySelector('meta[name="theme-color"]');
    try {
        localStorage.setItem('multicred_theme', next);
    } catch (e) {}
    if (next === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        if (document.body) document.body.classList.add('dark');
        if (metaTheme) metaTheme.setAttribute('content', '#090F0C');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-bs-theme', 'light');
        if (document.body) document.body.classList.remove('dark');
        if (metaTheme) metaTheme.setAttribute('content', '#14532D');
    }
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: next } }));
}

// Seamless Dark Mode Print Switcher: ensures clean white ink-friendly paper output
(function() {
    let wasDarkBeforePrint = false;
    window.addEventListener('beforeprint', function() {
        const root = document.documentElement;
        if (root.classList.contains('dark') || root.getAttribute('data-theme') === 'dark') {
            wasDarkBeforePrint = true;
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
            root.setAttribute('data-bs-theme', 'light');
            if (document.body) document.body.classList.remove('dark');
        } else {
            wasDarkBeforePrint = false;
        }
    });
    window.addEventListener('afterprint', function() {
        if (wasDarkBeforePrint) {
            const root = document.documentElement;
            root.classList.add('dark');
            root.setAttribute('data-theme', 'dark');
            root.setAttribute('data-bs-theme', 'dark');
            if (document.body) document.body.classList.add('dark');
            wasDarkBeforePrint = false;
        }
    });
})();
</script>

<!-- Cropper.js Library -->
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
@stack('scripts')
</body>
</html>
