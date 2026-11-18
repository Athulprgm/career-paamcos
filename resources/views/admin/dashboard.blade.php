<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#14532D">
    <title>Finora — Admin Financial Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', Inter, system-ui, sans-serif; }
    </style>
</head>
<body
    class="min-h-screen bg-[#F8FAF9] text-[#0F172A] antialiased selection:bg-[#14532D] selection:text-white"
    x-data="{
        activeNav: 'dashboard',
        profileOpen: false,
        notificationsOpen: false,
        filterModalOpen: false,
        addCardModalOpen: false,
        transferModal: { open: false, type: 'send' },
        cashFlowOpen: false,
        cashFlowPeriod: 'monthly',
        expensesOpen: false,
        expensesPeriod: 'monthly',
        selectedFilterRange: 'Last 30 days',
        selectedFilterCategory: 'All Categories',
        toastMessage: '',
        showToast(msg) {
            this.toastMessage = msg;
            setTimeout(() => {
                if (this.toastMessage === msg) {
                    this.toastMessage = '';
                }
            }, 3000);
        }
    }"
>
    <div class="min-h-screen bg-[#F8FAF9] p-4 lg:p-6 max-w-[1600px] mx-auto flex flex-col gap-5">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- A. TOP HEADER BAR                                                   --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <header class="flex items-center justify-between gap-4">
            {{-- Left: Brand Logo + Name --}}
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group focus:outline-none">
                <div class="w-10 h-10 bg-[#14532D] rounded-xl flex items-center justify-center shadow-sm transition-transform duration-200 group-hover:scale-105">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 6.5C5 5.67157 5.67157 5 6.5 5H17.5C18.3284 5 19 5.67157 19 6.5C19 7.32843 18.3284 8 17.5 8H8V11H15.5C16.3284 11 17 11.6716 17 12.5C17 13.3284 16.3284 14 15.5 14H8V17.5C8 18.3284 7.32843 19 6.5 19C5.67157 19 5 18.3284 5 17.5V6.5Z" fill="currentColor"/>
                        <circle cx="16.5" cy="17" r="2" fill="#A7F3D0"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-[#0F172A]">Finora</span>
            </a>

            {{-- Right: Action Buttons & Admin Profile Pill --}}
            <div class="flex items-center gap-2.5 sm:gap-3">
                {{-- Settings Button --}}
                <button
                    type="button"
                    @click="showToast('System preferences are up to date')"
                    aria-label="Settings"
                    class="w-10 h-10 rounded-full bg-white border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] flex items-center justify-center text-[#64748B] hover:text-[#14532D] hover:border-[#A7F3D0] hover:bg-[#ECFDF5] transition"
                >
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                {{-- Notifications Bell Button --}}
                <div class="relative" @click.outside="notificationsOpen = false">
                    <button
                        type="button"
                        @click="notificationsOpen = !notificationsOpen"
                        aria-label="Notifications"
                        class="relative w-10 h-10 rounded-full bg-white border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] flex items-center justify-center text-[#64748B] hover:text-[#14532D] hover:border-[#A7F3D0] hover:bg-[#ECFDF5] transition"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C2410C] ring-2 ring-white"></span>
                    </button>

                    <div
                        x-show="notificationsOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-[#E2E8E4] shadow-lg p-3.5 z-40"
                    >
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-semibold text-[#0F172A]">Notifications</span>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-[#ECFDF5] text-[#065F46] border border-[#A7F3D0]">2 New</span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="p-2.5 rounded-xl bg-[#F8FAF9] border border-[#E2E8E4]">
                                <p class="font-medium text-[#0F172A]">Monthly payout settled</p>
                                <p class="text-[#64748B] mt-0.5">$12,645.00 cleared into treasury.</p>
                            </div>
                            <div class="p-2.5 rounded-xl bg-[#F8FAF9] border border-[#E2E8E4]">
                                <p class="font-medium text-[#0F172A]">4 bills due on Feb 24</p>
                                <p class="text-[#64748B] mt-0.5">Auto-debit reminders scheduled.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Admin Profile Pill --}}
                <div class="relative" @click.outside="profileOpen = false">
                    <button
                        type="button"
                        @click="profileOpen = !profileOpen"
                        class="flex items-center gap-2.5 bg-white border border-[#E2E8E4] rounded-full py-1.5 pl-1.5 pr-3.5 shadow-[0_1px_3px_rgba(15,23,20,0.05)] hover:border-[#A7F3D0] hover:bg-[#F8FAF9] transition"
                    >
                        <div class="w-8 h-8 rounded-full bg-[#14532D] text-white flex items-center justify-center text-xs font-bold overflow-hidden shrink-0">
                            @if (auth()->user()?->profile_image_url)
                                <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                            @else
                                <span>{{ strtoupper(substr(auth()->user()->name ?? 'John Smith', 0, 2)) }}</span>
                            @endif
                        </div>
                        <div class="hidden sm:flex flex-col items-start text-left leading-tight">
                            <span class="text-xs font-semibold text-[#0F172A]">{{ auth()->user()->name ?? 'John Smith' }}</span>
                            <span class="text-[11px] text-[#64748B]">Admin</span>
                        </div>
                        <svg class="w-4 h-4 text-[#64748B] transition-transform duration-150" :class="profileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="profileOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 mt-2 w-48 bg-white rounded-2xl border border-[#E2E8E4] shadow-lg py-1.5 z-40"
                    >
                        <div class="px-3.5 py-2 border-b border-[#E2E8E4] sm:hidden">
                            <p class="text-xs font-semibold text-[#0F172A]">{{ auth()->user()->name ?? 'John Smith' }}</p>
                            <p class="text-[11px] text-[#64748B]">Admin</p>
                        </div>
                        <button type="button" @click="profileOpen = false; showToast('Profile overview opened')" class="w-full text-left px-3.5 py-2 text-xs font-medium text-[#1E293B] hover:bg-[#ECFDF5] hover:text-[#14532D] transition">
                            Account Profile
                        </button>
                        <button type="button" @click="profileOpen = false; showToast('Security audit logs verified')" class="w-full text-left px-3.5 py-2 text-xs font-medium text-[#1E293B] hover:bg-[#ECFDF5] hover:text-[#14532D] transition">
                            Security &amp; Roles
                        </button>
                        <div class="my-1 border-t border-[#E2E8E4]"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3.5 py-2 text-xs font-medium text-[#B91C1C] hover:bg-[#FEF2F2] transition">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- MAIN 3-PART ASYMMETRIC LAYOUT                                       --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col lg:flex-row gap-5 items-stretch">

            {{-- ─────────────────────────────────────────────────────────────── --}}
            {{-- B. LEFT FLOATING SIDEBAR (w-16 / 64px on desktop)               --}}
            {{-- ─────────────────────────────────────────────────────────────── --}}
            <aside class="lg:w-16 shrink-0 flex flex-row lg:flex-col justify-between items-center gap-4">
                {{-- Top Pill: 6 Navigation Icons --}}
                <nav aria-label="Primary Sidebar" class="bg-white rounded-full py-2 px-3 lg:py-4 lg:px-2 shadow-[0_1px_3px_rgba(15,23,20,0.05)] border border-[#E2E8E4] flex flex-row lg:flex-col items-center gap-2 sm:gap-3 overflow-x-auto">
                    {{-- 1. Dashboard / Grid --}}
                    <button
                        type="button"
                        @click="activeNav = 'dashboard'"
                        :class="activeNav === 'dashboard' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Dashboard"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                        </svg>
                    </button>

                    {{-- 2. Analytics --}}
                    <button
                        type="button"
                        @click="activeNav = 'analytics'; showToast('Switched focus: Analytics Overview')"
                        :class="activeNav === 'analytics' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Analytics"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </button>

                    {{-- 3. Folder / Projects --}}
                    <button
                        type="button"
                        @click="activeNav = 'projects'; showToast('Switched focus: Portfolios & Projects')"
                        :class="activeNav === 'projects' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Projects"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                    </button>

                    {{-- 4. Users --}}
                    <button
                        type="button"
                        @click="activeNav = 'users'; showToast('Switched focus: Team & Member Accounts')"
                        :class="activeNav === 'users' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Users"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    {{-- 5. Credit Card / Billing --}}
                    <button
                        type="button"
                        @click="activeNav = 'billing'; showToast('Switched focus: Cards & Billing')"
                        :class="activeNav === 'billing' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Billing"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </button>

                    {{-- 6. Activity Logs --}}
                    <button
                        type="button"
                        @click="activeNav = 'logs'; showToast('Switched focus: Activity Logs')"
                        :class="activeNav === 'logs' ? 'bg-[#14532D] text-white shadow-sm' : 'text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D]'"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition shrink-0"
                        title="Activity Logs"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>
                </nav>

                {{-- Bottom Pill: Help & Logout --}}
                <div class="bg-white rounded-full py-2 px-3 lg:py-4 lg:px-2 shadow-[0_1px_3px_rgba(15,23,20,0.05)] border border-[#E2E8E4] flex flex-row lg:flex-col items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        @click="showToast('Finora Priority Support is online 24/7')"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-[#64748B] hover:bg-[#ECFDF5] hover:text-[#14532D] transition"
                        title="Help & Support"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 13v-1a8 8 0 0116 0v1m-16 0a2 2 0 002 2h1a1 1 0 001-1v-3a1 1 0 00-1-1H6a2 2 0 00-2 2zm16 0a2 2 0 01-2 2h-1a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 012 2zm-4 5a2 2 0 01-2 2h-2" />
                        </svg>
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-10 h-10 rounded-full flex items-center justify-center text-[#64748B] hover:bg-[#FEF2F2] hover:text-[#B91C1C] transition"
                            title="Log out"
                            aria-label="Log out"
                        >
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </aside>

            {{-- ─────────────────────────────────────────────────────────────── --}}
            {{-- C. CENTER MAIN ANALYTICS COLUMN (flex-1)                        --}}
            {{-- ─────────────────────────────────────────────────────────────── --}}
            <main class="flex-1 min-w-0 flex flex-col gap-5">

                {{-- 1. Greeting & Filter Row --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-[#0F172A]">
                            Good Morning, {{ $adminName ?? 'David' }}
                        </h1>
                        <p class="text-sm text-[#64748B] mt-1">
                            Stay on top of your tasks, monitor progress, and track status efficiently
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="filterModalOpen = true"
                            class="bg-[#14532D] hover:bg-[#0F3F22] text-white rounded-full px-5 py-2.5 text-sm font-medium flex items-center gap-2 shadow-sm transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Filter</span>
                        </button>
                    </div>
                </div>

                {{-- 2. Top KPI Row (4 Equal-Width Cards) --}}
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Key Financial Indicators">
                    @foreach ($kpis as $kpi)
                        <div class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] p-5 flex flex-col justify-between gap-4 hover:border-[#14532D] hover:shadow-[0_6px_16px_-2px_rgba(20,83,45,0.09)] transition">
                            {{-- Top Row: Pastel Icon Badge + Metric Label --}}
                            <div class="flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl {{ $kpi['badge_bg'] }} {{ $kpi['badge_text'] }} flex items-center justify-center shrink-0">
                                    @if ($kpi['icon'] === 'wallet')
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h12a2 2 0 012 2v2h1a1 1 0 011 1v4a1 1 0 01-1 1h-1v2a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm14 4a1 1 0 100 2 1 1 0 000-2z" />
                                        </svg>
                                    @elseif ($kpi['icon'] === 'piggy')
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif ($kpi['icon'] === 'bag')
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    @else
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                        </svg>
                                    @endif
                                </span>
                                <span class="text-sm font-medium text-[#64748B] truncate">{{ $kpi['label'] }}</span>
                            </div>

                            {{-- Middle Row: Bold KPI Value --}}
                            <div class="text-2xl font-bold tracking-tight text-[#0F172A]">
                                {{ $kpi['value'] }}
                            </div>

                            {{-- Bottom Row: Status Badge + Circular Chevron Button --}}
                            <div class="flex items-center justify-between">
                                @if ($kpi['trend'] === 'up')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#065F46] border border-[#A7F3D0]">
                                        {{ $kpi['change'] }} &uarr;
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FEF2F2] text-[#B91C1C] border border-[#FECACA]">
                                        {{ $kpi['change'] }} &darr;
                                    </span>
                                @endif

                                <button
                                    type="button"
                                    @click="showToast('Viewing breakdown for {{ $kpi['label'] }}')"
                                    aria-label="View {{ $kpi['label'] }} details"
                                    class="w-7 h-7 rounded-full border border-[#E2E8E4] bg-[#F8FAF9] flex items-center justify-center text-[#64748B] hover:bg-[#14532D] hover:text-white hover:border-[#14532D] transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </section>

                {{-- 3. Middle Wide Card: Cash Flow Overview --}}
                <section class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] p-5 sm:p-6">
                    {{-- Header --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-[#0F172A]">Cash Flow Overview</h2>

                        {{-- Alpine.js Monthly/Yearly Dropdown --}}
                        <div class="relative" @click.outside="cashFlowOpen = false">
                            <button
                                type="button"
                                @click="cashFlowOpen = !cashFlowOpen"
                                class="border border-[#E2E8E4] rounded-full px-3.5 py-1.5 text-xs font-medium text-[#0F172A] bg-[#F8FAF9] hover:bg-[#ECFDF5] hover:border-[#A7F3D0] hover:text-[#14532D] flex items-center gap-1.5 transition"
                            >
                                <span x-text="cashFlowPeriod === 'monthly' ? 'Monthly' : 'Yearly'">Monthly</span>
                                <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                x-show="cashFlowOpen"
                                x-cloak
                                x-transition
                                class="absolute right-0 mt-1.5 w-28 bg-white border border-[#E2E8E4] rounded-xl shadow-lg py-1 z-30"
                            >
                                <button
                                    type="button"
                                    @click="cashFlowPeriod = 'monthly'; cashFlowOpen = false"
                                    :class="cashFlowPeriod === 'monthly' ? 'font-semibold text-[#14532D] bg-[#ECFDF5]' : 'text-[#64748B]'"
                                    class="w-full text-left px-3 py-1.5 text-xs hover:bg-[#F8FAF9]"
                                >
                                    Monthly
                                </button>
                                <button
                                    type="button"
                                    @click="cashFlowPeriod = 'yearly'; cashFlowOpen = false"
                                    :class="cashFlowPeriod === 'yearly' ? 'font-semibold text-[#14532D] bg-[#ECFDF5]' : 'text-[#64748B]'"
                                    class="w-full text-left px-3 py-1.5 text-xs hover:bg-[#F8FAF9]"
                                >
                                    Yearly
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Sub-header Legend --}}
                    <div class="flex flex-wrap items-center gap-5 mt-3 text-xs font-medium text-[#64748B]">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#14532D]"></span>
                            Income
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#C2410C]"></span>
                            Expense
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#059669]"></span>
                            Savings
                        </span>
                    </div>

                    {{-- Custom Responsive SVG: 3-Segment Smooth Cubic-Bezier Line Graph + Barcode Axis --}}
                    <div class="mt-4 w-full overflow-hidden">
                        <svg viewBox="0 0 720 220" class="w-full h-auto block" role="img" aria-label="Cash Flow Overview Chart">
                            <defs>
                                <linearGradient id="incomeGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#14532D" stop-opacity="0.18"/>
                                    <stop offset="100%" stop-color="#14532D" stop-opacity="0.0"/>
                                </linearGradient>
                                <linearGradient id="expenseGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#C2410C" stop-opacity="0.15"/>
                                    <stop offset="100%" stop-color="#C2410C" stop-opacity="0.0"/>
                                </linearGradient>
                                <linearGradient id="savingsGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#059669" stop-opacity="0.14"/>
                                    <stop offset="100%" stop-color="#059669" stop-opacity="0.0"/>
                                </linearGradient>
                                <filter id="pillShadow" x="-15%" y="-20%" width="130%" height="150%">
                                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#0F172A" flood-opacity="0.08"/>
                                </filter>
                            </defs>

                            {{-- Subtle Horizontal Reference Gridlines --}}
                            <line x1="16" y1="42" x2="704" y2="42" stroke="#E2E8E4" stroke-width="1" stroke-dasharray="4 4"/>
                            <line x1="16" y1="84" x2="704" y2="84" stroke="#E2E8E4" stroke-width="1" stroke-dasharray="4 4"/>
                            <line x1="16" y1="126" x2="704" y2="126" stroke="#E2E8E4" stroke-width="1" stroke-dasharray="4 4"/>
                            <line x1="16" y1="166" x2="704" y2="166" stroke="#CBD5D0" stroke-width="1"/>

                            {{-- MONTHLY CURVES --}}
                            <g x-show="cashFlowPeriod === 'monthly'">
                                {{-- Income Curve (Deep Agro Green) --}}
                                <path d="M 20 166 C 72 166, 92 44, 150 44 L 240 44 C 288 44, 304 166, 356 166 Z" fill="url(#incomeGrad)"/>
                                <path d="M 20 166 C 72 166, 92 44, 150 44 L 240 44 C 288 44, 304 166, 356 166" fill="none" stroke="#14532D" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Expense Curve (Warm Copper) --}}
                                <path d="M 206 166 C 254 166, 276 68, 336 68 L 426 68 C 474 68, 492 166, 540 166 Z" fill="url(#expenseGrad)"/>
                                <path d="M 206 166 C 254 166, 276 68, 336 68 L 426 68 C 474 68, 492 166, 540 166" fill="none" stroke="#C2410C" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Savings Curve (Emerald) --}}
                                <path d="M 418 166 C 466 166, 488 112, 546 112 L 634 112 C 674 112, 688 166, 704 166 Z" fill="url(#savingsGrad)"/>
                                <path d="M 418 166 C 466 166, 488 112, 546 112 L 634 112 C 674 112, 688 166, 704 166" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Callout 1: Income --}}
                                <circle cx="195" cy="44" r="6" fill="#14532D" fill-opacity="0.18"/>
                                <circle cx="195" cy="44" r="3.5" fill="#14532D" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="133" y="10" width="124" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="146" cy="22" r="3" fill="#14532D"/>
                                    <text x="155" y="25.5" font-size="10.5" font-weight="600" fill="#0F172A">Income {{ $cashFlow['monthly']['income'] }}</text>
                                </g>

                                {{-- Callout 2: Expense --}}
                                <circle cx="381" cy="68" r="6" fill="#C2410C" fill-opacity="0.18"/>
                                <circle cx="381" cy="68" r="3.5" fill="#C2410C" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="316" y="34" width="130" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="329" cy="46" r="3" fill="#C2410C"/>
                                    <text x="338" y="49.5" font-size="10.5" font-weight="600" fill="#0F172A">Expense {{ $cashFlow['monthly']['expense'] }}</text>
                                </g>

                                {{-- Callout 3: Savings --}}
                                <circle cx="590" cy="112" r="6" fill="#059669" fill-opacity="0.18"/>
                                <circle cx="590" cy="112" r="3.5" fill="#059669" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="531" y="78" width="118" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="544" cy="90" r="3" fill="#059669"/>
                                    <text x="553" y="93.5" font-size="10.5" font-weight="600" fill="#0F172A">Savings {{ $cashFlow['monthly']['savings'] }}</text>
                                </g>
                            </g>

                            {{-- YEARLY CURVES --}}
                            <g x-show="cashFlowPeriod === 'yearly'" x-cloak>
                                {{-- Income Curve (Deep Agro Green) --}}
                                <path d="M 20 166 C 72 166, 92 38, 150 38 L 240 38 C 288 38, 304 166, 356 166 Z" fill="url(#incomeGrad)"/>
                                <path d="M 20 166 C 72 166, 92 38, 150 38 L 240 38 C 288 38, 304 166, 356 166" fill="none" stroke="#14532D" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Expense Curve (Warm Copper) --}}
                                <path d="M 206 166 C 254 166, 276 76, 336 76 L 426 76 C 474 76, 492 166, 540 166 Z" fill="url(#expenseGrad)"/>
                                <path d="M 206 166 C 254 166, 276 76, 336 76 L 426 76 C 474 76, 492 166, 540 166" fill="none" stroke="#C2410C" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Savings Curve (Emerald) --}}
                                <path d="M 418 166 C 466 166, 488 94, 546 94 L 634 94 C 674 94, 688 166, 704 166 Z" fill="url(#savingsGrad)"/>
                                <path d="M 418 166 C 466 166, 488 94, 546 94 L 634 94 C 674 94, 688 166, 704 166" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round"/>

                                {{-- Callout 1: Yearly Income --}}
                                <circle cx="195" cy="38" r="6" fill="#14532D" fill-opacity="0.18"/>
                                <circle cx="195" cy="38" r="3.5" fill="#14532D" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="129" y="6" width="132" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="142" cy="18" r="3" fill="#14532D"/>
                                    <text x="151" y="21.5" font-size="10.5" font-weight="600" fill="#0F172A">Income {{ $cashFlow['yearly']['income'] }}</text>
                                </g>

                                {{-- Callout 2: Yearly Expense --}}
                                <circle cx="381" cy="76" r="6" fill="#C2410C" fill-opacity="0.18"/>
                                <circle cx="381" cy="76" r="3.5" fill="#C2410C" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="314" y="42" width="134" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="327" cy="54" r="3" fill="#C2410C"/>
                                    <text x="336" y="57.5" font-size="10.5" font-weight="600" fill="#0F172A">Expense {{ $cashFlow['yearly']['expense'] }}</text>
                                </g>

                                {{-- Callout 3: Yearly Savings --}}
                                <circle cx="590" cy="94" r="6" fill="#059669" fill-opacity="0.18"/>
                                <circle cx="590" cy="94" r="3.5" fill="#059669" stroke="#FFFFFF" stroke-width="1.5"/>
                                <g filter="url(#pillShadow)">
                                    <rect x="526" y="60" width="128" height="24" rx="12" fill="#FFFFFF" stroke="#E2E8E4" stroke-width="1"/>
                                    <circle cx="539" cy="72" r="3" fill="#059669"/>
                                    <text x="548" y="75.5" font-size="10.5" font-weight="600" fill="#0F172A">Savings {{ $cashFlow['yearly']['savings'] }}</text>
                                </g>
                            </g>

                            {{-- Barcode-style Vertical Tick Timeline --}}
                            @for ($i = 0; $i <= 100; $i++)
                                @php
                                    $tickX = round(18 + ($i * 6.84), 2);
                                    $isMajor = ($i % 10 === 0);
                                @endphp
                                <line
                                    x1="{{ $tickX }}"
                                    y1="{{ $isMajor ? 172 : 176 }}"
                                    x2="{{ $tickX }}"
                                    y2="{{ $isMajor ? 191 : 187 }}"
                                    stroke="{{ $isMajor ? '#64748B' : '#CBD5D0' }}"
                                    stroke-width="{{ $isMajor ? '1.5' : '1' }}"
                                    stroke-linecap="round"
                                />
                            @endfor

                            {{-- Timeline Axis Labels (0K to 500K) --}}
                            @foreach (['0K', '50K', '100K', '150K', '200K', '250K', '300K', '350K', '400K', '450K', '500K'] as $idx => $axisLabel)
                                @php
                                    $labelX = round(18 + ($idx * 10 * 6.84), 2);
                                    $anchor = $idx === 0 ? 'start' : ($idx === 10 ? 'end' : 'middle');
                                @endphp
                                <text
                                    x="{{ $labelX }}"
                                    y="209"
                                    text-anchor="{{ $anchor }}"
                                    font-size="10.5"
                                    font-weight="500"
                                    fill="#64748B"
                                >{{ $axisLabel }}</text>
                            @endforeach
                        </svg>
                    </div>
                </section>

                {{-- 4. Bottom Split Row (2 Equal-Width Cards Side-by-Side) --}}
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    {{-- Left Card: Budget Overview --}}
                    <div class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] p-5 sm:p-6 flex flex-col justify-between gap-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-[#0F172A]">Budget Overview</h2>
                            <span class="bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46] text-xs font-semibold px-3 py-1 rounded-full">
                                {{ $budget['used_percent'] }}% used
                            </span>
                        </div>

                        {{-- Middle Progress Section --}}
                        <div>
                            <div class="flex items-center justify-between text-sm mb-3">
                                <span class="text-[#64748B] font-medium">Spent</span>
                                <span class="font-bold text-[#0F172A]">{{ $budget['spent'] }}</span>
                            </div>

                            <div class="relative w-full h-2.5 bg-[#F1F5F3] rounded-full">
                                <div
                                    class="h-full bg-[#14532D] rounded-full transition-all duration-500"
                                    style="width: {{ $budget['used_percent'] }}%;"
                                ></div>
                                <div
                                    class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-white border-4 border-[#14532D] shadow-sm"
                                    style="left: {{ $budget['used_percent'] }}%;"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-xs font-medium text-[#64748B] mt-2.5">
                                <span>{{ $budget['min_label'] }}</span>
                                <span>{{ $budget['max_label'] }}</span>
                            </div>
                        </div>

                        {{-- Bottom Inner Box --}}
                        <div class="bg-[#F8FAF9] border border-[#E2E8E4] rounded-2xl p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-[#0F172A]">Remaining &#x1F4B0;</span>
                                <span class="text-base font-bold text-[#14532D]">{{ $budget['remaining'] }}</span>
                            </div>
                            <p class="text-xs text-[#64748B] leading-relaxed mt-2">
                                {{ $budget['helper_text'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Right Card: All Expenses (Semi-Circular Polar Rose Chart) --}}
                    <div class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] p-5 sm:p-6 flex flex-col justify-between gap-4">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-[#0F172A]">All Expenses</h2>

                            {{-- Alpine.js Monthly/Yearly Dropdown --}}
                            <div class="relative" @click.outside="expensesOpen = false">
                                <button
                                    type="button"
                                    @click="expensesOpen = !expensesOpen"
                                    class="border border-[#E2E8E4] rounded-full px-3.5 py-1.5 text-xs font-medium text-[#0F172A] bg-[#F8FAF9] hover:bg-[#ECFDF5] hover:border-[#A7F3D0] hover:text-[#14532D] flex items-center gap-1.5 transition"
                                >
                                    <span x-text="expensesPeriod === 'monthly' ? 'Monthly' : 'Yearly'">Monthly</span>
                                    <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div
                                    x-show="expensesOpen"
                                    x-cloak
                                    x-transition
                                    class="absolute right-0 mt-1.5 w-28 bg-white border border-[#E2E8E4] rounded-xl shadow-lg py-1 z-30"
                                >
                                    <button
                                        type="button"
                                        @click="expensesPeriod = 'monthly'; expensesOpen = false"
                                        :class="expensesPeriod === 'monthly' ? 'font-semibold text-[#14532D] bg-[#ECFDF5]' : 'text-[#64748B]'"
                                        class="w-full text-left px-3 py-1.5 text-xs hover:bg-[#F8FAF9]"
                                    >
                                        Monthly
                                    </button>
                                    <button
                                        type="button"
                                        @click="expensesPeriod = 'yearly'; expensesOpen = false"
                                        :class="expensesPeriod === 'yearly' ? 'font-semibold text-[#14532D] bg-[#ECFDF5]' : 'text-[#64748B]'"
                                        class="w-full text-left px-3 py-1.5 text-xs hover:bg-[#F8FAF9]"
                                    >
                                        Yearly
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom-Anchored Semi-Circular Multi-Layered Polar Rose Chart --}}
                        <div class="flex items-center justify-center pt-1">
                            <svg viewBox="0 0 360 205" class="w-full max-w-[350px] h-auto block" role="img" aria-label="All Expenses Polar Rose Chart">
                                {{-- Monthly Rose Wedges (48% / 36% / 16%) --}}
                                <g x-show="expensesPeriod === 'monthly'">
                                    {{-- Wedge 1: 48% Muted Sage (#D1DDD6), R=138, 180deg -> 93.6deg --}}
                                    <path d="M 180 186 L 42 186 A 138 138 0 0 1 171.33 48.27 Z" fill="#D1DDD6" stroke="#FFFFFF" stroke-width="2.5"/>
                                    {{-- Wedge 2: 36% Emerald (#059669), R=112, 93.6deg -> 28.8deg --}}
                                    <path d="M 180 186 L 172.97 74.22 A 112 112 0 0 1 278.15 132.04 Z" fill="#059669" stroke="#FFFFFF" stroke-width="2.5"/>
                                    {{-- Wedge 3: 16% Deep Agro Green (#14532D), R=86, 28.8deg -> 0deg --}}
                                    <path d="M 180 186 L 255.36 144.57 A 86 86 0 0 1 266 186 Z" fill="#14532D" stroke="#FFFFFF" stroke-width="2.5"/>

                                    {{-- Center Semi-Circle Cutout --}}
                                    <path d="M 156 186 A 24 24 0 0 1 204 186 Z" fill="#FFFFFF"/>

                                    {{-- Leader Line & Label: 48% --}}
                                    <polyline points="79,92 62,74 42,74" fill="none" stroke="#64748B" stroke-width="1.2"/>
                                    <circle cx="79" cy="92" r="2.5" fill="#64748B"/>
                                    <text x="36" y="78" text-anchor="end" font-size="13" font-weight="700" fill="#0F172A">48%</text>

                                    {{-- Leader Line & Label: 36% --}}
                                    <polyline points="234,88 250,62 274,62" fill="none" stroke="#059669" stroke-width="1.2"/>
                                    <circle cx="234" cy="88" r="2.5" fill="#059669"/>
                                    <text x="280" y="66" text-anchor="start" font-size="13" font-weight="700" fill="#0F172A">36%</text>

                                    {{-- Leader Line & Label: 16% --}}
                                    <polyline points="263,164 285,146 308,146" fill="none" stroke="#14532D" stroke-width="1.2"/>
                                    <circle cx="263" cy="164" r="2.5" fill="#14532D"/>
                                    <text x="314" y="150" text-anchor="start" font-size="13" font-weight="700" fill="#0F172A">16%</text>
                                </g>

                                {{-- Yearly Rose Wedges (52% / 31% / 17%) --}}
                                <g x-show="expensesPeriod === 'yearly'" x-cloak>
                                    {{-- Wedge 1: 52% Muted Sage (#D1DDD6), R=138, 180deg -> 86.4deg --}}
                                    <path d="M 180 186 L 42 186 A 138 138 0 0 1 188.66 48.27 Z" fill="#D1DDD6" stroke="#FFFFFF" stroke-width="2.5"/>
                                    {{-- Wedge 2: 31% Emerald (#059669), R=112, 86.4deg -> 30.6deg --}}
                                    <path d="M 180 186 L 187.03 74.22 A 112 112 0 0 1 276.40 128.99 Z" fill="#059669" stroke="#FFFFFF" stroke-width="2.5"/>
                                    {{-- Wedge 3: 17% Deep Agro Green (#14532D), R=86, 30.6deg -> 0deg --}}
                                    <path d="M 180 186 L 254.02 142.22 A 86 86 0 0 1 266 186 Z" fill="#14532D" stroke="#FFFFFF" stroke-width="2.5"/>

                                    {{-- Center Semi-Circle Cutout --}}
                                    <path d="M 156 186 A 24 24 0 0 1 204 186 Z" fill="#FFFFFF"/>

                                    {{-- Leader Line & Label: 52% --}}
                                    <polyline points="85,84 65,68 42,68" fill="none" stroke="#64748B" stroke-width="1.2"/>
                                    <circle cx="85" cy="84" r="2.5" fill="#64748B"/>
                                    <text x="36" y="72" text-anchor="end" font-size="13" font-weight="700" fill="#0F172A">52%</text>

                                    {{-- Leader Line & Label: 31% --}}
                                    <polyline points="239,92 256,66 278,66" fill="none" stroke="#059669" stroke-width="1.2"/>
                                    <circle cx="239" cy="92" r="2.5" fill="#059669"/>
                                    <text x="284" y="70" text-anchor="start" font-size="13" font-weight="700" fill="#0F172A">31%</text>

                                    {{-- Leader Line & Label: 17% --}}
                                    <polyline points="262,163 285,146 308,146" fill="none" stroke="#14532D" stroke-width="1.2"/>
                                    <circle cx="262" cy="163" r="2.5" fill="#14532D"/>
                                    <text x="314" y="150" text-anchor="start" font-size="13" font-weight="700" fill="#0F172A">17%</text>
                                </g>
                            </svg>
                        </div>

                        {{-- Compact Legend Footer --}}
                        <div class="flex items-center justify-center gap-4 text-[11px] font-medium text-[#64748B] pt-1">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#D1DDD6]"></span>
                                Operations
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#059669]"></span>
                                Payroll
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#14532D]"></span>
                                Infra
                            </span>
                        </div>
                    </div>

                </section>
            </main>

            {{-- ─────────────────────────────────────────────────────────────── --}}
            {{-- D. RIGHT ADMIN OPERATIONS COLUMN (w-full xl:w-[360px])          --}}
            {{-- ─────────────────────────────────────────────────────────────── --}}
            <aside class="w-full xl:w-[360px] shrink-0 bg-white rounded-[20px] border border-[#E2E8E4] shadow-[0_1px_3px_rgba(15,23,20,0.05)] p-5 flex flex-col gap-6">

                {{-- 1. Top Section: My Card --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-[#0F172A]">My Card</h2>
                        <button
                            type="button"
                            @click="addCardModalOpen = true"
                            class="border border-[#E2E8E4] rounded-full px-3 py-1 text-xs font-medium text-[#14532D] bg-[#F8FAF9] hover:bg-[#ECFDF5] hover:border-[#A7F3D0] transition cursor-pointer"
                        >
                            + Add Card
                        </button>
                    </div>

                    {{-- Inner Soft Slate Card --}}
                    <div class="bg-[#F8FAF9] border border-[#E2E8E4] rounded-2xl p-5">
                        {{-- Card Top Bar --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-[#14532D] text-white text-[11px] font-bold flex items-center justify-center">
                                    {{ strtoupper(substr($cardDetails['holder_name'], 0, 1)) }}
                                </div>
                                <span class="text-sm font-medium text-[#0F172A]">{{ $cardDetails['holder_name'] }}</span>
                            </div>

                            <span class="w-7 h-7 rounded-full bg-[#14532D] text-white flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                        </div>

                        {{-- Center Balance --}}
                        <div class="text-center mt-4">
                            <p class="text-xs font-medium text-[#64748B]">Your Balance</p>
                            <p class="text-3xl font-bold tracking-tight text-[#0F172A] my-2">{{ $cardDetails['balance'] }}</p>

                            {{-- Avatar Stack (4 Circular Avatars) --}}
                            <div class="flex items-center justify-center -space-x-2 mt-2">
                                @foreach ($cardDetails['team_avatars'] as $avatar)
                                    <span class="w-7 h-7 rounded-full {{ $avatar['bg'] }} border-2 border-white flex items-center justify-center text-[10px] font-bold shadow-xs">
                                        {{ $avatar['initials'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Inner White Sub-Card: Last Transaction --}}
                        <div class="bg-white rounded-xl p-3 mt-4 shadow-sm border border-[#E2E8E4]">
                            <div class="flex items-center justify-between mb-2.5">
                                <span class="text-[11px] font-medium text-[#64748B]">Last transaction</span>
                                <button
                                    type="button"
                                    @click="showToast('Viewing all card transactions')"
                                    class="text-[11px] font-semibold text-[#14532D] hover:underline"
                                >
                                    View all
                                </button>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-[#059669] text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.2 7.2c.66 0 1.2.54 1.2 1.2s-.54 1.2-1.2 1.2-1.2-.54-1.2-1.2.54-1.2 1.2-1.2zm-6.4 0c.66 0 1.2.54 1.2 1.2s-.54 1.2-1.2 1.2-1.2-.54-1.2-1.2.54-1.2 1.2-1.2zm3.2 7.8c-2.33 0-4.32-1.45-5.12-3.5h10.24c-.8 2.05-2.79 3.5-5.12 3.5z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-[#0F172A] truncate">{{ $cardDetails['last_transaction']['merchant'] }}</p>
                                        <p class="text-[11px] text-[#64748B]">{{ $cardDetails['last_transaction']['date'] }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-[#0F172A] shrink-0">{{ $cardDetails['last_transaction']['amount'] }}</span>
                            </div>
                        </div>

                        {{-- Action Buttons Row --}}
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="transferModal = { open: true, type: 'receive' }"
                                class="bg-white border border-[#E2E8E4] hover:bg-[#ECFDF5] hover:border-[#A7F3D0] text-[#14532D] rounded-full py-2.5 px-4 text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <span>Receive &darr;</span>
                            </button>
                            <button
                                type="button"
                                @click="transferModal = { open: true, type: 'send' }"
                                class="bg-[#14532D] hover:bg-[#0F3F22] text-white rounded-full py-2.5 px-4 text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer"
                            >
                                <span>Send &uarr;</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 2. Bottom Section: Upcoming Bills --}}
                <div class="flex-1 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-semibold text-[#0F172A]">Upcoming Bills</h2>
                            <button
                                type="button"
                                @click="showToast('Bill due-date reminders are enabled')"
                                class="bg-[#FFF7ED] hover:bg-[#FFEDD5] border border-[#FED7AA] rounded-full px-3 py-1 text-xs font-medium text-[#C2410C] transition cursor-pointer"
                            >
                                Reminders
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($upcomingBills as $bill)
                                <div class="bg-[#F8FAF9] border border-[#E2E8E4] rounded-xl p-3 flex items-center justify-between gap-3 hover:bg-[#F1F5F3] transition">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if ($bill['brand'] === 'spotify')
                                            <div class="w-9 h-9 rounded-full bg-[#1DB954] text-white flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm4.59 14.42a.62.62 0 01-.86.21c-2.35-1.44-5.31-1.76-8.8-.96a.62.62 0 11-.28-1.21c3.82-.87 7.1-.5 9.73 1.11.29.18.38.56.21.85zm1.22-2.72a.78.78 0 01-1.07.26c-2.69-1.65-6.79-2.13-9.97-1.17a.78.78 0 01-.45-1.49c3.63-1.1 8.15-.57 11.23 1.33.36.22.48.7.26 1.07zm.11-2.83c-3.23-1.92-8.56-2.1-11.64-1.16a.94.94 0 11-.55-1.79c3.54-1.07 9.42-.86 13.14 1.35a.94.94 0 01-.95 1.6z"/>
                                                </svg>
                                            </div>
                                        @elseif ($bill['brand'] === 'netflix')
                                            <div class="w-9 h-9 rounded-full bg-[#0F172A] text-[#E50914] flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M7 3h3.2l3.8 11.4V3H17v18h-3.1L10 9.4V21H7V3z"/>
                                                </svg>
                                            </div>
                                        @elseif ($bill['brand'] === 'apple')
                                            <div class="w-9 h-9 rounded-full bg-[#14532D] text-white flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M16.365 1.43c0 1.14-.493 2.27-1.177 3.08-.744.9-1.99 1.57-2.987 1.57-.12 0-.23-.02-.3-.03-.01-.06-.04-.22-.04-.39 0-1.15.572-2.27 1.206-2.98.804-.94 2.142-1.64 3.248-1.68.03.13.05.28.05.43zm4.565 15.71c-.03.07-.463 1.58-1.518 3.12-.945 1.34-1.94 2.71-3.43 2.71-1.517 0-1.9-.88-3.63-.88-1.698 0-2.302.91-3.67.91-1.377 0-2.332-1.26-3.428-2.8-1.287-1.82-2.323-4.63-2.323-7.28 0-4.28 2.797-6.55 5.552-6.55 1.448 0 2.675.95 3.6.95.865 0 2.222-1.01 3.902-1.01.613 0 2.886.06 4.374 2.19-.13.09-2.383 1.37-2.383 4.19 0 3.26 2.854 4.42 2.955 4.45z"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#C2410C] via-[#059669] to-[#14532D] text-white flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4L4 20h4.2l1.6-3.5h4.4L15.8 20H20L12 4zm0 6.5l1.5 3.5h-3l1.5-3.5z"/>
                                                </svg>
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-[#0F172A] truncate">{{ $bill['title'] }}</p>
                                            <p class="text-[11px] text-[#64748B]">{{ $bill['due_date'] }}</p>
                                        </div>
                                    </div>

                                    <span class="text-xs font-bold text-[#0F172A] shrink-0">{{ $bill['amount'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-center text-xs text-[#64748B] pt-1">
                        We'll remind you before the due date
                    </p>
                </div>
            </aside>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- FILTER MODAL (Alpine.js)                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="filterModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0F172A]/35 backdrop-blur-xs p-4"
        @keydown.escape.window="filterModalOpen = false"
    >
        <div
            @click.outside="filterModalOpen = false"
            x-transition
            class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-xl w-full max-w-md p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-[#0F172A]">Filter Dashboard Data</h3>
                <button type="button" @click="filterModalOpen = false" class="w-8 h-8 rounded-full hover:bg-[#F1F5F3] flex items-center justify-center text-[#64748B]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-[#64748B] mb-1.5">Date Range</label>
                    <select x-model="selectedFilterRange" class="w-full rounded-xl border border-[#E2E8E4] px-3.5 py-2.5 text-sm text-[#0F172A] focus:outline-none focus:border-[#059669]">
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Quarter to Date</option>
                        <option>Year to Date</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-[#64748B] mb-1.5">Category</label>
                    <select x-model="selectedFilterCategory" class="w-full rounded-xl border border-[#E2E8E4] px-3.5 py-2.5 text-sm text-[#0F172A] focus:outline-none focus:border-[#059669]">
                        <option>All Categories</option>
                        <option>Income &amp; Revenue</option>
                        <option>Operating Expenses</option>
                        <option>Treasury Savings</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-6">
                <button
                    type="button"
                    @click="selectedFilterRange = 'Last 30 days'; selectedFilterCategory = 'All Categories'; filterModalOpen = false; showToast('Filters reset to default')"
                    class="rounded-full px-4 py-2 text-xs font-semibold text-[#64748B] hover:bg-[#F1F5F3] transition"
                >
                    Reset
                </button>
                <button
                    type="button"
                    @click="filterModalOpen = false; showToast('Applied: ' + selectedFilterRange + ' • ' + selectedFilterCategory)"
                    class="bg-[#14532D] text-white rounded-full px-5 py-2 text-xs font-semibold hover:bg-[#0F3F22] transition"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ADD CARD MODAL (Alpine.js)                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="addCardModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0F172A]/35 backdrop-blur-xs p-4"
        @keydown.escape.window="addCardModalOpen = false"
    >
        <div
            @click.outside="addCardModalOpen = false"
            x-transition
            class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-xl w-full max-w-md p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-[#0F172A]">Link New Corporate Card</h3>
                <button type="button" @click="addCardModalOpen = false" class="w-8 h-8 rounded-full hover:bg-[#F1F5F3] flex items-center justify-center text-[#64748B]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-[#64748B] mb-1">Cardholder Name</label>
                    <input type="text" value="John Smith" class="w-full rounded-xl border border-[#E2E8E4] px-3.5 py-2 text-sm text-[#0F172A] focus:outline-none focus:border-[#059669]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-[#64748B] mb-1">Card Label</label>
                    <input type="text" placeholder="e.g. Cloud Operations Visa" class="w-full rounded-xl border border-[#E2E8E4] px-3.5 py-2 text-sm text-[#0F172A] focus:outline-none focus:border-[#059669]">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-6">
                <button type="button" @click="addCardModalOpen = false" class="rounded-full px-4 py-2 text-xs font-semibold text-[#64748B] hover:bg-[#F1F5F3]">Cancel</button>
                <button type="button" @click="addCardModalOpen = false; showToast('Virtual card request queued')" class="bg-[#14532D] text-white rounded-full px-5 py-2 text-xs font-semibold hover:bg-[#0F3F22]">Save Card</button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SEND / RECEIVE MODAL (Alpine.js)                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="transferModal.open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0F172A]/35 backdrop-blur-xs p-4"
        @keydown.escape.window="transferModal.open = false"
    >
        <div
            @click.outside="transferModal.open = false"
            x-transition
            class="bg-white rounded-[20px] border border-[#E2E8E4] shadow-xl w-full max-w-sm p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-[#0F172A]" x-text="transferModal.type === 'send' ? 'Send Funds' : 'Receive Funds'"></h3>
                <button type="button" @click="transferModal.open = false" class="w-8 h-8 rounded-full hover:bg-[#F1F5F3] flex items-center justify-center text-[#64748B]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="text-xs text-[#64748B] mb-4" x-text="transferModal.type === 'send' ? 'Initiate an instant treasury transfer from your primary card.' : 'Generate a payment link or account routing details.'"></p>

            <input type="text" value="$250.00" class="w-full rounded-xl border border-[#E2E8E4] px-3.5 py-2.5 text-sm font-semibold text-[#0F172A] mb-4 focus:outline-none focus:border-[#059669]">

            <button
                type="button"
                @click="transferModal.open = false; showToast(transferModal.type === 'send' ? 'Transfer scheduled successfully' : 'Payment request link copied')"
                class="w-full bg-[#14532D] text-white rounded-full py-2.5 text-xs font-semibold hover:bg-[#0F3F22] transition"
                x-text="transferModal.type === 'send' ? 'Confirm Send' : 'Copy Request Link'"
            ></button>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div
        x-show="toastMessage !== ''"
        x-cloak
        x-transition
        class="fixed bottom-5 right-5 z-50 bg-[#14532D] text-white text-xs font-medium px-4 py-2.5 rounded-full shadow-lg"
        x-text="toastMessage"
    ></div>
</body>
</html>
