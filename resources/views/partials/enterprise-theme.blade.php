<style>
/* ==========================================================================
   MULTICRED CO-OPERATIVE BANKING MANAGEMENT SYSTEM
   DEEP AGRO GREEN & WARM COPPER ENTERPRISE DESIGN SYSTEM (LIGHT & DARK)
   Brand Colors:
     Primary Brand:  #14532D (Deep Agro Green) / #10B981 (Dark Mode Emerald)
     Accent Brand:   #059669 (Emerald) / #34D399 (Dark Mode)
     Copper Accent:  #C2410C (Warm Copper) / #FB923C (Dark Mode)
     Canvas Bg:      #F8FAF9 (Light Slate) / #090F0C (Dark Forest Obsidian)
   ========================================================================== */

:root {
    /* ─── Institutional Agro Green & Warm Copper Brand Palette ────────────── */
    --color-primary: #14532D;          /* Deep Agro Green */
    --color-primary-hover: #0F3F22;    /* Rich Dark Agro Green Hover */
    --color-primary-dark: #0A2E18;     /* Deep Shadow Green */
    --color-primary-light: #ECFDF5;    /* Soft Mint Tint */
    --color-primary-border: #A7F3D0;   /* Soft Emerald Border */
    --color-primary-ring: rgba(20, 83, 45, 0.16);
    --color-accent: #059669;           /* Emerald Interactive Accent */
    --color-accent-light: #ECFDF5;
    --color-secondary: #14532D;
    --color-copper: #C2410C;           /* Warm Copper / Terracotta */
    --color-copper-hover: #9A3412;
    --color-copper-light: #FFF7ED;
    --color-copper-border: #FED7AA;
    --color-copper-text: #9A3412;
    --color-gold: var(--color-copper); /* Backwards-compatible accent alias */
    --color-gold-hover: var(--color-copper-hover);
    --color-gold-light: var(--color-copper-light);
    --color-gold-border: var(--color-copper-border);
    --color-gold-text: var(--color-copper-text);

    /* ─── Classic Banking Light Neutrals ──────────────────────────────────── */
    --body-bg: #F8FAF9;                /* Crisp Neutral Slate Page Background */
    --bg-page: #F8FAF9;
    --card-bg: #FFFFFF;                /* Crisp Elevated Card Surface */
    --surface-subtle: #F1F5F3;         /* Soft Subtle Surface */
    --bg-surface-alt: #F8FAF9;         /* Legacy subtle surface alias */
    --color-primary-green: #14532D;    /* Legacy primary brand alias */
    --color-deep-green: #14532D;       /* Legacy deep brand alias */
    --surface-elevated: #FFFFFF;
    --surface-muted: #E2E8E4;
    --text-heading: #0F172A;           /* High readability slate heading */
    --text-body: #1E293B;              /* Crisp dark readable slate */
    --text-secondary: #475569;
    --text-muted: #64748B;
    --text-subtle: #94A3B8;
    --border-color: #E2E8E4;           /* Clean modern border */
    --border-strong: #CBD5D0;
    --border-dark: #94A3B8;
    --border-focus: #059669;
    --focus-ring: 0 0 0 3px rgba(20, 83, 45, 0.18);

    /* ─── Geometry & Subtle Shadow Tokens ─────────────────────────────────── */
    --radius-xs: 4px;
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;
    --radius-xl: 18px;
    --radius-pill: 9999px;

    --shadow-subtle: 0 1px 2px 0 rgba(15, 23, 20, 0.04);
    --shadow-card: 0 1px 3px 0 rgba(15, 23, 20, 0.05), 0 1px 2px -1px rgba(15, 23, 20, 0.04);
    --shadow-hover: 0 6px 16px -2px rgba(20, 83, 45, 0.09), 0 2px 6px -2px rgba(15, 23, 20, 0.04);
    --shadow-dropdown: 0 10px 25px -3px rgba(15, 23, 20, 0.09), 0 4px 10px -4px rgba(15, 23, 20, 0.04);
    --shadow-modal: 0 20px 30px -5px rgba(15, 23, 20, 0.14), 0 8px 12px -6px rgba(15, 23, 20, 0.06);

    /* ─── Layout Dimensions ───────────────────────────────────────────────── */
    --topbar-height: 60px;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 72px;

    /* ─── Adaptive Crisp Light Sidebar (Light Mode) ───────────────────────── */
    --sidebar-bg: #FFFFFF;
    --sidebar-brand-bg: #FFFFFF;
    --sidebar-border: #E2E8E4;
    --sidebar-shadow: 2px 0 12px rgba(15, 23, 20, 0.04);
    --sidebar-heading: #0F172A;
    --sidebar-subtext: #64748B;
    --sidebar-text: #334155;
    --sidebar-text-hover: #14532D;
    --sidebar-sub-text: #475569;
    --sidebar-hover-bg: #F1F5F3;
    --sidebar-active-bg: linear-gradient(135deg, #14532D 0%, #0F3F22 100%);
    --sidebar-active-border: rgba(20, 83, 45, 0.9);
    --sidebar-active-shadow: 0 4px 14px -2px rgba(20, 83, 45, 0.28);
    --sidebar-active-text: #FFFFFF;
    --sidebar-active-icon: #A7F3D0;
    --sidebar-group-active-bg: #ECFDF5;
    --sidebar-group-active-border: #A7F3D0;
    --sidebar-group-active-text: #14532D;
    --sidebar-group-active-icon: #059669;
    --sidebar-sub-active-bg: #ECFDF5;
    --sidebar-sub-active-border: #A7F3D0;
    --sidebar-sub-active-text: #14532D;
    --sidebar-sub-active-icon: #059669;
    --sidebar-icon: #64748B;
    --sidebar-icon-hover: #14532D;
    --sidebar-icon-active: #A7F3D0;
    --sidebar-caret: #94A3B8;
    --sidebar-section-title: #94A3B8;
    --sidebar-divider-line: #E2E8E4;
    --sidebar-footer-bg: #F8FAF9;
    --sidebar-widget-bg: #F8FAF9;
    --sidebar-widget-hover-bg: #F1F5F3;
    --sidebar-widget-border: #E2E8E4;
    --sidebar-widget-hover-border: #A7F3D0;
    --sidebar-avatar-bg: #ECFDF5;
    --sidebar-avatar-border: #A7F3D0;
    --sidebar-avatar-color: #14532D;
    --sidebar-badge-bg: #ECFDF5;
    --sidebar-badge-text: #14532D;
    --sidebar-badge-border: #A7F3D0;
    --sidebar-logo-border: rgba(20, 83, 45, 0.30);
    --sidebar-scrollbar-thumb: rgba(15, 23, 20, 0.14);
    --sidebar-tooltip-bg: #14532D;
    --sidebar-tooltip-border: rgba(52, 211, 153, 0.45);
    --tree-line: #CBD5D0;

    /* ─── Topbar Color Scheme ─────────────────────────────────────────────── */
    --topbar-bg: #FFFFFF;
    --topbar-border: #E2E8E4;

    /* ─── Status Semantic Tokens (Light) ──────────────────────────────────── */
    --status-active-bg: #ECFDF5;
    --status-active-text: #065F46;
    --status-active-border: #A7F3D0;

    --status-pending-bg: #FFFBEB;
    --status-pending-text: #92400E;
    --status-pending-border: #FDE68A;

    --status-info-bg: #F0F9FF;
    --status-info-text: #0369A1;
    --status-info-border: #BAE6FD;

    --status-danger-bg: #FEF2F2;
    --status-danger-text: #B91C1C;
    --status-danger-border: #FECACA;

    --status-neutral-bg: #F1F5F9;
    --status-neutral-text: #475569;
    --status-neutral-border: #E2E8E0;

    --status-purple-bg: #FAF5FF;
    --status-purple-text: #6B21A8;
    --status-purple-border: #E9D5FF;

    /* ─── Bootstrap 5.3 Brand Alignment (Light) ───────────────────────────── */
    --bs-primary: #14532D;
    --bs-primary-rgb: 20, 83, 45;
    --bs-primary-text-emphasis: #0F3F22;
    --bs-primary-bg-subtle: #ECFDF5;
    --bs-primary-border-subtle: #A7F3D0;
    --bs-success: #059669;
    --bs-success-rgb: 5, 150, 105;
    --bs-link-color: #14532D;
    --bs-link-color-rgb: 20, 83, 45;
    --bs-link-hover-color: #0F3F22;
    --bs-link-hover-color-rgb: 15, 63, 34;
    --bs-focus-ring-color: rgba(20, 83, 45, 0.22);
}

/* ==========================================================================
   DARK THEME SYSTEM TOKENS (FOREST OBSIDIAN + AGRO EMERALD & WARM COPPER)
   ========================================================================== */
html.dark,
body.dark,
[data-theme="dark"],
[data-bs-theme="dark"] {
    --color-primary: #10B981;
    --color-primary-hover: #059669;
    --color-primary-dark: #047857;
    --color-primary-light: rgba(16, 185, 129, 0.15);
    --color-primary-border: rgba(52, 211, 153, 0.32);
    --color-primary-ring: rgba(16, 185, 129, 0.25);
    --color-accent: #34D399;
    --color-accent-light: rgba(16, 185, 129, 0.15);
    --color-secondary: #34D399;
    --color-copper: #FB923C;
    --color-copper-hover: #FDBA74;
    --color-copper-light: rgba(251, 146, 60, 0.15);
    --color-copper-border: rgba(251, 146, 60, 0.34);
    --color-copper-text: #FDBA74;
    --color-gold: var(--color-copper);
    --color-gold-hover: var(--color-copper-hover);
    --color-gold-light: var(--color-copper-light);
    --color-gold-border: var(--color-copper-border);
    --color-gold-text: var(--color-copper-text);

    --body-bg: #090F0C;                /* Deep Forest Obsidian Canvas */
    --bg-page: #090F0C;
    --card-bg: #111A15;                /* Rich Elevated Forest Slate Surface */
    --surface-subtle: #18241E;
    --bg-surface-alt: #18241E;
    --color-primary-green: #34D399;
    --color-deep-green: #15803D;
    --surface-elevated: #1F2E26;
    --surface-muted: #283B31;
    --text-heading: #F8FAFC;
    --text-body: #E2E8F0;
    --text-secondary: #CBD5E1;
    --text-muted: #94A3B8;
    --text-subtle: #64748B;
    --border-color: #1E2C24;
    --border-strong: #31453A;
    --border-dark: #1E2C24;
    --border-focus: #34D399;
    --focus-ring: 0 0 0 3px rgba(16, 185, 129, 0.28);

    --shadow-subtle: 0 1px 2px 0 rgba(0, 0, 0, 0.35);
    --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.35), 0 1px 2px -1px rgba(0, 0, 0, 0.25);
    --shadow-hover: 0 6px 16px -2px rgba(0, 0, 0, 0.45), 0 2px 6px -2px rgba(0, 0, 0, 0.32);
    --shadow-dropdown: 0 10px 25px -3px rgba(0, 0, 0, 0.55), 0 4px 10px -4px rgba(0, 0, 0, 0.35);
    --shadow-modal: 0 20px 30px -5px rgba(0, 0, 0, 0.65), 0 8px 12px -6px rgba(0, 0, 0, 0.45);

    /* ─── Adaptive Deep Forest Sidebar (Dark Mode) ────────────────────────── */
    --sidebar-bg: linear-gradient(180deg, #0D1D14 0%, #09140E 60%, #050C08 100%);
    --sidebar-brand-bg: rgba(255, 255, 255, 0.02);
    --sidebar-border: rgba(148, 179, 160, 0.12);
    --sidebar-shadow: 4px 0 24px rgba(0, 0, 0, 0.38);
    --sidebar-heading: #F8FAFC;
    --sidebar-subtext: rgba(203, 213, 225, 0.65);
    --sidebar-text: rgba(241, 245, 249, 0.82);
    --sidebar-text-hover: #FFFFFF;
    --sidebar-sub-text: rgba(226, 232, 240, 0.72);
    --sidebar-hover-bg: rgba(255, 255, 255, 0.065);
    --sidebar-active-bg: linear-gradient(135deg, #15803D 0%, #14532D 100%);
    --sidebar-active-border: rgba(52, 211, 153, 0.45);
    --sidebar-active-shadow: 0 4px 14px -2px rgba(20, 83, 45, 0.50), inset 0 1px 0 rgba(255, 255, 255, 0.14);
    --sidebar-active-text: #FFFFFF;
    --sidebar-active-icon: #6EE7B7;
    --sidebar-group-active-bg: rgba(255, 255, 255, 0.06);
    --sidebar-group-active-border: rgba(148, 179, 160, 0.14);
    --sidebar-group-active-text: #FFFFFF;
    --sidebar-group-active-icon: #34D399;
    --sidebar-sub-active-bg: rgba(16, 185, 129, 0.18);
    --sidebar-sub-active-border: rgba(52, 211, 153, 0.34);
    --sidebar-sub-active-text: #FFFFFF;
    --sidebar-sub-active-icon: #34D399;
    --sidebar-icon: rgba(226, 232, 240, 0.68);
    --sidebar-icon-hover: #34D399;
    --sidebar-icon-active: #6EE7B7;
    --sidebar-caret: rgba(203, 213, 225, 0.5);
    --sidebar-section-title: #94A3B8;
    --sidebar-divider-line: rgba(148, 179, 160, 0.16);
    --sidebar-footer-bg: rgba(0, 0, 0, 0.25);
    --sidebar-widget-bg: rgba(255, 255, 255, 0.045);
    --sidebar-widget-hover-bg: rgba(255, 255, 255, 0.08);
    --sidebar-widget-border: rgba(148, 179, 160, 0.14);
    --sidebar-widget-hover-border: rgba(52, 211, 153, 0.38);
    --sidebar-avatar-bg: rgba(16, 185, 129, 0.18);
    --sidebar-avatar-border: rgba(52, 211, 153, 0.38);
    --sidebar-avatar-color: #34D399;
    --sidebar-badge-bg: rgba(16, 185, 129, 0.16);
    --sidebar-badge-text: #6EE7B7;
    --sidebar-badge-border: rgba(52, 211, 153, 0.38);
    --sidebar-logo-border: rgba(52, 211, 153, 0.52);
    --sidebar-scrollbar-thumb: rgba(255, 255, 255, 0.16);
    --sidebar-tooltip-bg: #0B1A12;
    --sidebar-tooltip-border: rgba(52, 211, 153, 0.45);
    --tree-line: rgba(148, 179, 160, 0.18);

    --topbar-bg: #111A15;
    --topbar-border: #1E2C24;

    --status-active-bg: rgba(16, 185, 129, 0.15);
    --status-active-text: #34D399;
    --status-active-border: rgba(52, 211, 153, 0.3);

    --status-pending-bg: rgba(245, 158, 11, 0.15);
    --status-pending-text: #FBBF24;
    --status-pending-border: rgba(251, 191, 36, 0.3);

    --status-info-bg: rgba(14, 165, 233, 0.15);
    --status-info-text: #38BDF8;
    --status-info-border: rgba(56, 189, 248, 0.3);

    --status-danger-bg: rgba(239, 68, 68, 0.15);
    --status-danger-text: #F87171;
    --status-danger-border: rgba(248, 113, 113, 0.3);

    --status-neutral-bg: #1E293B;
    --status-neutral-text: #94A3B8;
    --status-neutral-border: #334155;

    --status-purple-bg: rgba(168, 85, 247, 0.15);
    --status-purple-text: #C084FC;
    --status-purple-border: rgba(192, 132, 252, 0.3);

    /* ─── Bootstrap 5.3 Brand Alignment (Dark) ────────────────────────────── */
    --bs-primary: #10B981;
    --bs-primary-rgb: 16, 185, 129;
    --bs-primary-text-emphasis: #34D399;
    --bs-primary-bg-subtle: rgba(16, 185, 129, 0.16);
    --bs-primary-border-subtle: rgba(52, 211, 153, 0.32);
    --bs-success: #10B981;
    --bs-success-rgb: 16, 185, 129;
    --bs-success-text-emphasis: #34D399;
    --bs-success-bg-subtle: rgba(16, 185, 129, 0.16);
    --bs-success-border-subtle: rgba(52, 211, 153, 0.32);
    --bs-warning-text-emphasis: #FBBF24;
    --bs-warning-bg-subtle: rgba(245, 158, 11, 0.16);
    --bs-warning-border-subtle: rgba(251, 191, 36, 0.32);
    --bs-body-bg: #090F0C;
    --bs-body-color: #E2E8F0;
    --bs-border-color: #1E2C24;
    --bs-link-color: #34D399;
    --bs-link-color-rgb: 52, 211, 153;
    --bs-link-hover-color: #6EE7B7;
    --bs-link-hover-color-rgb: 110, 231, 183;
}

/* ==========================================================================
   RESET & TYPOGRAPHY STANDARDS
   ========================================================================== */

html {
    font-size: 14px;
    -webkit-text-size-adjust: 100%;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: var(--body-bg);
    color: var(--text-body);
    font-size: 0.875rem;
    line-height: 1.5;
    font-weight: 400;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: clip;
    transition: background-color 0.2s ease, color 0.2s ease;
}

/* Tabular figures for financial numbers, codes, dates, account numbers */
.num-tabular,
.stat-value,
.metric-value,
.inst-stat-value,
td.amount-cell,
.tabular-nums,
[data-tabular="true"] {
    font-feature-settings: 'tnum' 1, 'cv05' 1;
    font-variant-numeric: tabular-nums;
}

h1, h2, h3, h4, h5, h6 {
    color: var(--text-heading);
    font-weight: 600;
    letter-spacing: -0.015em;
    margin-top: 0;
}

a {
    color: var(--color-primary);
    text-decoration: none;
    transition: color 0.12s ease;
}

a:hover {
    color: var(--color-primary-hover);
}

.text-primary {
    color: var(--color-primary) !important;
}

.bg-primary {
    background-color: var(--color-primary) !important;
}

.border-primary {
    border-color: var(--color-primary) !important;
}

.bg-primary-subtle {
    background-color: var(--bs-primary-bg-subtle) !important;
}

.border-primary-subtle {
    border-color: var(--bs-primary-border-subtle) !important;
}

html.dark a {
    color: var(--color-accent);
}

html.dark a:hover {
    color: #6EE7B7;
}

html.dark .text-primary {
    color: var(--color-accent) !important;
}

/* ==========================================================================
   APPLICATION SHELL LAYOUT (Sidebar, Topbar, Main Content)
   ========================================================================== */

#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    height: 100vh;
    height: 100dvh;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--sidebar-border);
    z-index: 1040;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    user-select: none;
    transition: width 0.24s cubic-bezier(0.4, 0, 0.2, 1), transform 0.24s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s ease, border-color 0.2s ease;
    box-shadow: var(--sidebar-shadow);
}

#main-content {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    min-height: 100dvh;
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    display: flex;
    flex-direction: column;
    background: var(--body-bg);
    transition: margin-left 0.24s cubic-bezier(0.4, 0, 0.2, 1), width 0.24s cubic-bezier(0.4, 0, 0.2, 1), max-width 0.24s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
    min-width: 0;
}

/* Collapsed Sidebar State (Desktop) */
html.sidebar-collapsed #sidebar,
body.sidebar-collapsed #sidebar {
    width: var(--sidebar-collapsed-width);
}

html.sidebar-collapsed #main-content,
body.sidebar-collapsed #main-content {
    margin-left: var(--sidebar-collapsed-width);
    width: calc(100% - var(--sidebar-collapsed-width));
    max-width: calc(100% - var(--sidebar-collapsed-width));
}

html.sidebar-collapsed .brand-text,
body.sidebar-collapsed .brand-text,
html.sidebar-collapsed .brand-title-row,
body.sidebar-collapsed .brand-title-row,
html.sidebar-collapsed .brand-badge,
body.sidebar-collapsed .brand-badge,
html.sidebar-collapsed .sidebar-context-body,
body.sidebar-collapsed .sidebar-context-body,
html.sidebar-collapsed .sidebar-footer-text,
body.sidebar-collapsed .sidebar-footer-text,
html.sidebar-collapsed .sidebar-footer-name,
body.sidebar-collapsed .sidebar-footer-name,
html.sidebar-collapsed .sidebar-footer-label,
body.sidebar-collapsed .sidebar-footer-label,
html.sidebar-collapsed .sidebar-signout-btn span,
body.sidebar-collapsed .sidebar-signout-btn span,
html.sidebar-collapsed .nav-group-caret,
body.sidebar-collapsed .nav-group-caret,
html.sidebar-collapsed .tree-root-item span span,
body.sidebar-collapsed .tree-root-item span span,
html.sidebar-collapsed .tree-main-trunk,
body.sidebar-collapsed .tree-main-trunk,
html.sidebar-collapsed .nav-link-item span,
body.sidebar-collapsed .nav-link-item span {
    display: none !important;
}

/* In collapsed mode, section titles turn into clean centered divider lines */
html.sidebar-collapsed .nav-section-title,
body.sidebar-collapsed .nav-section-title {
    width: 28px;
    height: 1px;
    padding: 0 !important;
    margin: 12px auto !important;
    background: var(--sidebar-divider-line);
    overflow: hidden;
    text-indent: -9999px;
}

html.sidebar-collapsed .nav-section-title::after,
body.sidebar-collapsed .nav-section-title::after {
    display: none !important;
}

html.sidebar-collapsed .sidebar-nav-container,
body.sidebar-collapsed .sidebar-nav-container {
    padding: 10px 8px 16px 8px;
}

html.sidebar-collapsed .tree-root-item,
body.sidebar-collapsed .tree-root-item,
html.sidebar-collapsed .nav-link-item,
body.sidebar-collapsed .nav-link-item {
    justify-content: center !important;
    width: 44px;
    height: 42px;
    padding: 0 !important;
    margin: 3px auto !important;
    border-radius: 10px !important;
    position: relative;
}

html.sidebar-collapsed a.tree-root-item.active::before,
body.sidebar-collapsed a.tree-root-item.active::before,
html.sidebar-collapsed a.nav-link-item.active::before,
body.sidebar-collapsed a.nav-link-item.active::before {
    left: 0;
    height: 18px;
}

html.sidebar-collapsed .tree-root-item i,
body.sidebar-collapsed .tree-root-item i,
html.sidebar-collapsed .nav-link-item i,
body.sidebar-collapsed .nav-link-item i {
    margin: 0 !important;
    font-size: 1.15rem !important;
}

/* Body-mounted Floating Tooltip for Collapsed Desktop Mode */
#sidebar-floating-tooltip {
    position: fixed;
    z-index: 1090;
    background: var(--sidebar-tooltip-bg);
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.015em;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    pointer-events: none;
    box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.38), 0 4px 8px -2px rgba(0, 0, 0, 0.22);
    border: 1px solid var(--sidebar-tooltip-border);
    opacity: 0;
    transform: translateY(-50%) translateX(-4px);
    transition: opacity 0.14s ease, transform 0.14s ease;
    display: none;
}

#sidebar-floating-tooltip.visible {
    display: block;
    opacity: 1;
    transform: translateY(-50%) translateX(0);
}

html.sidebar-collapsed .sidebar-brand,
body.sidebar-collapsed .sidebar-brand {
    padding: 0 !important;
    justify-content: center !important;
}

html.sidebar-collapsed .sidebar-brand .brand-wrapper,
body.sidebar-collapsed .sidebar-brand .brand-wrapper {
    justify-content: center !important;
    gap: 0 !important;
}

html.sidebar-collapsed .sidebar-brand .brand-meta,
body.sidebar-collapsed .sidebar-brand .brand-meta {
    display: none !important;
}

html.sidebar-collapsed .sidebar-context-widget,
body.sidebar-collapsed .sidebar-context-widget {
    padding: 7px !important;
    margin: 10px auto 4px auto !important;
    width: 44px;
    height: 44px;
    justify-content: center !important;
    gap: 0 !important;
}

html.sidebar-collapsed .sidebar-footer,
body.sidebar-collapsed .sidebar-footer {
    padding: 10px 8px !important;
}

html.sidebar-collapsed .sidebar-user-info,
body.sidebar-collapsed .sidebar-user-info {
    display: flex;
    justify-content: center;
    padding: 6px !important;
    width: 44px;
    margin: 0 auto 8px auto !important;
}

html.sidebar-collapsed .sidebar-user-info .sidebar-user-row,
body.sidebar-collapsed .sidebar-user-info .sidebar-user-row {
    justify-content: center !important;
    gap: 0 !important;
}

html.sidebar-collapsed .sidebar-signout-btn,
body.sidebar-collapsed .sidebar-signout-btn {
    justify-content: center !important;
    width: 44px;
    height: 38px;
    padding: 0 !important;
    margin: 0 auto !important;
}

/* Sidebar Brand Header */
.sidebar-brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: var(--topbar-height);
    padding: 0 14px;
    background: var(--sidebar-brand-bg);
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
    gap: 10px;
}

.brand-wrapper {
    display: flex;
    align-items: center;
    gap: 11px;
    text-decoration: none;
    min-width: 0;
    flex: 1;
}

.brand-logo {
    width: 40px;
    height: 40px;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FFFFFF;
    border-radius: 50%;
    flex-shrink: 0;
    overflow: hidden;
    border: 1.5px solid var(--sidebar-logo-border);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
    transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.brand-wrapper:hover .brand-logo {
    border-color: var(--color-accent);
    transform: scale(1.03);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.24);
}

.brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
    display: block;
}

.brand-logo i {
    color: var(--color-primary) !important;
    font-size: 1.2rem;
}

.brand-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
    justify-content: center;
    gap: 3px;
}

.brand-title-row {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    flex-wrap: nowrap;
}

.brand-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--sidebar-heading);
    line-height: 1.1;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

.brand-badge {
    font-size: 0.54rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 2px 6px;
    background: var(--sidebar-badge-bg);
    color: var(--sidebar-badge-text);
    border: 1px solid var(--sidebar-badge-border);
    border-radius: 6px;
    text-transform: uppercase;
    white-space: nowrap;
    line-height: 1.2;
    flex-shrink: 0;
}

.brand-sub {
    font-size: 0.61rem;
    color: var(--sidebar-subtext);
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: 0.055em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: uppercase;
}

.sidebar-close-btn {
    width: 30px;
    height: 30px;
    background: var(--sidebar-widget-bg);
    border: 1px solid var(--sidebar-widget-border);
    color: var(--sidebar-text);
    font-size: 0.95rem;
    padding: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.sidebar-close-btn:hover {
    color: var(--sidebar-text-hover);
    background: var(--sidebar-hover-bg);
    border-color: var(--border-strong);
}

/* Sidebar Context Widget */
.sidebar-context-widget {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 11px;
    margin: 10px 10px 2px 10px;
    background: var(--sidebar-widget-bg);
    border: 1px solid var(--sidebar-widget-border);
    border-radius: 10px;
    text-decoration: none;
    flex-shrink: 0;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.sidebar-context-widget:hover {
    background: var(--sidebar-widget-hover-bg);
    border-color: var(--sidebar-widget-hover-border);
}

.sidebar-context-avatar {
    width: 30px;
    height: 30px;
    background: var(--sidebar-avatar-bg);
    border: 1px solid var(--sidebar-avatar-border);
    color: var(--sidebar-avatar-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
}

.sidebar-context-avatar img {
    border-radius: 8px;
}

.sidebar-context-body {
    min-width: 0;
    flex: 1;
}

.sidebar-context-name {
    display: block;
    font-size: 0.785rem;
    font-weight: 600;
    color: var(--sidebar-heading);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}

.sidebar-context-subtitle {
    display: block;
    font-size: 0.68rem;
    color: var(--sidebar-subtext);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}

/* Sidebar Navigation Items */
.sidebar-nav-container {
    flex: 1 1 0%;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain;
    padding: 10px 10px 18px 10px;
    scrollbar-width: thin;
    scrollbar-color: var(--sidebar-scrollbar-thumb) transparent;
}

.sidebar-nav-container::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav-container::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav-container::-webkit-scrollbar-thumb {
    background: var(--sidebar-scrollbar-thumb);
    border-radius: 9999px;
}

.sidebar-nav-container::-webkit-scrollbar-thumb:hover {
    background: var(--color-accent);
}

.nav-section-title {
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--sidebar-section-title);
    padding: 14px 10px 6px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    user-select: none;
}

.nav-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, var(--sidebar-divider-line) 0%, transparent 100%);
}

.tree-diagram-wrap {
    margin: 2px 0;
    position: relative;
}

.tree-root-item,
.nav-link-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 11px;
    color: var(--sidebar-text);
    font-size: 0.825rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border-radius: 10px;
    border: 1px solid transparent;
    background: transparent;
    user-select: none;
    position: relative;
    transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}

.tree-root-item:hover,
.nav-link-item:hover {
    color: var(--sidebar-text-hover);
    background: var(--sidebar-hover-bg);
}

.tree-root-item i,
.nav-link-item i {
    font-size: 1.02rem;
    color: var(--sidebar-icon);
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    transition: color 0.16s ease;
}

.tree-root-item:hover i,
.nav-link-item:hover i {
    color: var(--sidebar-icon-hover);
}

/* Active Single Navigation Item — High-Contrast Classic Banking Navy & Gold Pill */
a.tree-root-item.active,
a.nav-link-item.active {
    background: var(--sidebar-active-bg) !important;
    color: var(--sidebar-active-text) !important;
    font-weight: 600 !important;
    border-color: var(--sidebar-active-border) !important;
    box-shadow: var(--sidebar-active-shadow);
}

a.tree-root-item.active::before,
a.nav-link-item.active::before {
    display: none;
}

a.tree-root-item.active i,
a.nav-link-item.active i {
    color: var(--sidebar-active-icon) !important;
    filter: none;
}

/* Open / Active Parent Group */
div.tree-root-item.open,
div.tree-root-item.active {
    color: var(--sidebar-group-active-text) !important;
    background: var(--sidebar-group-active-bg);
    border-color: var(--sidebar-group-active-border);
    font-weight: 600;
}

div.tree-root-item.open i:not(.nav-group-caret),
div.tree-root-item.active i:not(.nav-group-caret) {
    color: var(--sidebar-group-active-icon);
}

.nav-group-caret {
    font-size: 0.7rem;
    color: var(--sidebar-caret);
    transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), color 0.16s ease;
    margin-left: auto;
    width: auto !important;
}

.tree-root-item.open .nav-group-caret {
    transform: rotate(180deg);
    color: var(--sidebar-group-active-icon);
}

/* Tree Trunk & Branches */
.tree-main-trunk {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    margin-left: 20px;
    padding-left: 11px;
    border-left: 1.5px solid var(--tree-line);
    transition: max-height 0.24s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.18s ease, margin 0.2s ease;
}

.tree-main-trunk.show {
    max-height: 640px;
    opacity: 1;
    margin-top: 4px;
    margin-bottom: 6px;
}

.tree-branch-item {
    margin: 2px 0;
}

.tree-link-node {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 10px;
    color: var(--sidebar-sub-text);
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: all 0.14s ease;
}

.tree-link-node:hover {
    color: var(--sidebar-text-hover);
    background: var(--sidebar-hover-bg);
}

.tree-link-node i {
    font-size: 0.85rem;
    color: var(--sidebar-icon);
    width: 16px;
    text-align: center;
    flex-shrink: 0;
    transition: color 0.14s ease;
}

.tree-link-node:hover i {
    color: var(--sidebar-icon-hover);
}

.tree-link-node.active {
    background: var(--sidebar-sub-active-bg) !important;
    color: var(--sidebar-sub-active-text) !important;
    font-weight: 600 !important;
    border-color: var(--sidebar-sub-active-border) !important;
}

.tree-link-node.active i {
    color: var(--sidebar-sub-active-icon) !important;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 10px 10px 12px 10px;
    background: var(--sidebar-footer-bg);
    border-top: 1px solid var(--sidebar-border);
    flex-shrink: 0;
    margin-top: auto;
}

.sidebar-user-info {
    background: var(--sidebar-widget-bg);
    border: 1px solid var(--sidebar-widget-border);
    border-radius: 10px;
    padding: 8px 10px;
    margin-bottom: 8px;
}

.sidebar-user-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.sidebar-footer-avatar {
    width: 30px;
    height: 30px;
    border-radius: 9999px;
    background: var(--sidebar-avatar-bg);
    border: 1px solid var(--sidebar-avatar-border);
    color: var(--sidebar-avatar-color);
    font-size: 0.72rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.sidebar-footer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 9999px;
}

.sidebar-footer-text {
    min-width: 0;
    flex: 1;
}

.sidebar-footer-label {
    font-size: 0.62rem;
    color: var(--sidebar-section-title);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    line-height: 1.2;
}

.sidebar-footer-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--sidebar-heading);
    line-height: 1.25;
}

.sidebar-signout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--sidebar-widget-bg);
    border: 1px solid var(--sidebar-widget-border);
    border-radius: 10px;
    color: var(--sidebar-text);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sidebar-signout-btn:hover {
    background: var(--status-danger-bg);
    border-color: var(--status-danger-border);
    color: var(--status-danger-text);
}

/* ==========================================================================
   TOPBAR & HEADER
   ========================================================================== */

#topbar {
    height: var(--topbar-height);
    background: var(--topbar-bg);
    border-bottom: 1px solid var(--topbar-border);
    position: sticky;
    top: 0;
    z-index: 1030;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    box-shadow: var(--shadow-subtle);
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.sidebar-toggle-btn {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-body);
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.12s ease;
}

.sidebar-toggle-btn:hover {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--text-heading);
}

.topbar-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-heading);
    letter-spacing: -0.01em;
}

/* Theme Toggle Button in Topbar */
.theme-toggle-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-body);
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.15s ease;
}

.theme-toggle-btn:hover {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--color-primary);
}

html:not(.dark) .theme-icon-light {
    display: none !important;
}

html:not(.dark) .theme-icon-dark {
    display: inline-flex !important;
    color: var(--color-primary);
}

html.dark .theme-icon-dark {
    display: none !important;
}

html.dark .theme-icon-light {
    display: inline-flex !important;
    color: #FB923C;
}

.topbar-icon-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.15s ease;
    text-decoration: none;
    position: relative;
}

.topbar-icon-btn:hover {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--text-heading);
}

.user-avatar,
.agent-avatar,
.agent-table-avatar,
.agent-node-avatar {
    width: 36px;
    height: 36px;
    background: var(--color-primary-light);
    color: var(--color-primary);
    font-weight: 700;
    font-size: 0.825rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid var(--color-primary-border);
    border-radius: var(--radius-pill) !important;
    overflow: hidden;
}

/* ==========================================================================
   CONTENT WRAPPER & PAGE HEADERS
   ========================================================================== */

.content-wrapper {
    flex: 1;
    padding: 24px 28px 40px 28px;
    max-width: 100%;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text-heading);
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin: 0;
}

.page-breadcrumb {
    font-size: 0.775rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-top: 3px;
    line-height: 1.3;
}

.page-breadcrumb a {
    color: var(--text-muted);
}

.page-breadcrumb a:hover {
    color: var(--text-heading);
}

/* ==========================================================================
   CARDS & CONTAINERS — ENTERPRISE FINANCIAL SAAS
   ========================================================================== */

.card,
.dash-card,
.inst-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    margin-bottom: 20px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.card:hover,
.dash-card:hover,
.inst-card:hover {
    border-color: var(--border-strong);
}

.card-header,
.dash-card-header,
.inst-card-header {
    background: var(--card-bg);
    border-bottom: 1px solid var(--border-color);
    padding: 14px 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-heading);
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-body,
.dash-card-body,
.inst-card-body {
    padding: 20px 22px;
}

.card-footer,
.dash-card-footer,
.inst-card-footer {
    background: var(--surface-subtle);
    border-top: 1px solid var(--border-color);
    padding: 12px 20px;
    font-size: 0.785rem;
    color: var(--text-muted);
}

/* ==========================================================================
   ENTERPRISE STAT & METRIC CARDS
   ========================================================================== */

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    height: 100%;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
}

.stat-card:has(.stat-top-row) {
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    gap: 6px;
}

.metric-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    gap: 0;
    height: 100%;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
}

.stat-card:hover,
.metric-card:hover {
    transform: translateY(-2px);
    border-color: var(--border-strong);
    box-shadow: var(--shadow-hover);
    color: inherit;
}

.stat-icon,
.metric-icon-box {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.24rem;
    flex-shrink: 0;
    background: var(--surface-subtle);
    color: var(--text-heading);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65), 0 1px 2px rgba(15, 23, 20, 0.04);
    transition: transform 0.18s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.18s ease, border-color 0.18s ease;
}

.stat-card:hover .stat-icon,
.metric-card:hover .metric-icon-box {
    transform: translateY(-1px) scale(1.04);
}

html.dark .stat-icon,
html.dark .metric-icon-box {
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 2px 6px rgba(0, 0, 0, 0.28);
}

.metric-icon-box.brand,
.stat-icon.brand,
.stat-icon.emerald,
.stat-icon.navy {
    background: #ECFDF5;
    color: #14532D;
    border-color: #A7F3D0;
}

.stat-icon.dark,
.stat-icon.black {
    background: #F1F5F3;
    color: #0F172A;
    border-color: #E2E8E4;
}

.stat-icon.amber,
.stat-icon.gold {
    background: #FFFBEB;
    color: #B45309;
    border-color: #FDE68A;
}

.stat-icon.teal {
    background: #F0F9FF;
    color: #0369A1;
    border-color: #BAE6FD;
}

.stat-icon.violet {
    background: #FAF5FF;
    color: #6B21A8;
    border-color: #E9D5FF;
}

.stat-icon.red {
    background: #FEF2F2;
    color: #B91C1C;
    border-color: #FECACA;
}

html.dark .metric-icon-box.brand,
html.dark .stat-icon.brand,
html.dark .stat-icon.emerald,
html.dark .stat-icon.navy {
    background: rgba(16, 185, 129, 0.15);
    color: #34D399;
    border-color: rgba(52, 211, 153, 0.30);
}

html.dark .stat-icon.dark,
html.dark .stat-icon.black {
    background: var(--surface-elevated);
    color: var(--text-heading);
    border-color: var(--border-color);
}

html.dark .stat-icon.amber,
html.dark .stat-icon.gold {
    background: rgba(245, 158, 11, 0.15);
    color: #FBBF24;
    border-color: rgba(251, 191, 36, 0.30);
}

html.dark .stat-icon.teal {
    background: rgba(14, 165, 233, 0.15);
    color: #38BDF8;
    border-color: rgba(56, 189, 248, 0.30);
}

html.dark .stat-icon.violet {
    background: rgba(168, 85, 247, 0.15);
    color: #C084FC;
    border-color: rgba(192, 132, 252, 0.30);
}

html.dark .stat-icon.red {
    background: rgba(239, 68, 68, 0.15);
    color: #F87171;
    border-color: rgba(248, 113, 113, 0.30);
}

.stat-value,
.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-heading);
    line-height: 1.15;
    letter-spacing: -0.02em;
    font-feature-settings: 'tnum' 1;
}

.stat-label,
.metric-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    line-height: 1.3;
}

.stat-label {
    margin-top: 3px;
}

/* ==========================================================================
   DATA TABLES & PAGINATION
   ========================================================================== */

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--card-bg);
    box-shadow: var(--shadow-card);
}

.table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
    color: var(--text-body);
}

.table th,
.table thead th {
    background: var(--surface-subtle);
    color: var(--text-muted);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 12px 16px;
    border-top: none;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
    vertical-align: middle;
}

.table td,
.table tbody td {
    padding: 13px 16px;
    font-size: 0.84rem;
    color: var(--text-body);
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
    background: var(--card-bg);
}

.table-hover tbody tr:hover td {
    background-color: var(--surface-subtle);
}

.table th.text-end,
.table td.text-end {
    text-align: right;
}

.table th.text-center,
.table td.text-center {
    text-align: center;
}

/* Table Footer & Pagination */
.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: var(--card-bg);
    border-top: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 12px;
}

.pagination {
    margin-bottom: 0;
    gap: 4px;
}

.page-item .page-link {
    font-size: 0.775rem;
    font-weight: 500;
    color: var(--text-body);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 5px 11px;
    background: var(--card-bg);
    transition: all 0.12s ease;
}

.page-item .page-link:hover {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--text-heading);
}

.page-item.active .page-link {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
    font-weight: 700;
}

.page-item.disabled .page-link {
    color: var(--text-muted);
    background: var(--card-bg);
    border-color: var(--border-color);
    opacity: 0.6;
}

/* ==========================================================================
   BUTTONS & ACTIONS
   ========================================================================== */

.btn {
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 7px 16px;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    line-height: 1.4;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    cursor: pointer;
    box-shadow: var(--shadow-subtle);
}

.btn:focus,
.btn:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

.btn-sm {
    padding: 5px 12px;
    font-size: 0.76rem;
    border-radius: var(--radius-sm);
}

.btn-lg {
    padding: 10px 20px;
    font-size: 0.95rem;
    border-radius: var(--radius-lg);
}

/* Primary Button: Deep Agro Green */
.btn-dark,
.btn-primary {
    background: linear-gradient(180deg, #166534 0%, #14532D 100%);
    border-color: #14532D;
    color: #FFFFFF;
    font-weight: 600;
    box-shadow: 0 1px 2px 0 rgba(20, 83, 45, 0.25), inset 0 1px 0 0 rgba(255, 255, 255, 0.14);
}

.btn-dark:hover,
.btn-primary:hover,
.btn-dark:focus,
.btn-primary:focus {
    background: linear-gradient(180deg, #15803D 0%, #0F3F22 100%);
    border-color: #0F3F22;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -2px rgba(20, 83, 45, 0.35);
}

.btn-dark:active,
.btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px 0 rgba(20, 83, 45, 0.22);
}

html.dark .btn-dark,
html.dark .btn-primary {
    background: linear-gradient(180deg, #059669 0%, #047857 100%);
    border-color: #059669;
    color: #FFFFFF;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.35), inset 0 1px 0 0 rgba(255, 255, 255, 0.16);
}

html.dark .btn-dark:hover,
html.dark .btn-primary:hover,
html.dark .btn-dark:focus,
html.dark .btn-primary:focus {
    background: linear-gradient(180deg, #10B981 0%, #059669 100%);
    border-color: #10B981;
    color: #FFFFFF;
    box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.42);
}

.btn-outline-primary {
    background: var(--card-bg);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.btn-outline-primary:hover,
.btn-outline-primary:focus {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
}

html.dark .btn-outline-primary {
    background: transparent;
    border-color: rgba(52, 211, 153, 0.45);
    color: #34D399;
}

html.dark .btn-outline-primary:hover,
html.dark .btn-outline-primary:focus {
    background: #059669;
    border-color: #059669;
    color: #FFFFFF;
}

/* Secondary / Outline Button */
.btn-outline-dark,
.btn-outline-secondary {
    background: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-heading);
    box-shadow: var(--shadow-subtle);
}

.btn-outline-dark:hover,
.btn-outline-secondary:hover,
.btn-outline-dark:focus,
.btn-outline-secondary:focus {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--color-primary);
    transform: translateY(-1px);
}

/* Success Button */
.btn-success {
    background: var(--color-success);
    border-color: var(--color-success);
    color: #FFFFFF;
}
.btn-success:hover {
    filter: brightness(0.92);
    color: #FFFFFF;
}

.btn-outline-success {
    background: var(--color-primary-light);
    border-color: var(--color-primary-border);
    color: var(--color-primary);
}
.btn-outline-success:hover {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
}

/* Danger Button */
.btn-danger {
    background: var(--color-danger);
    border-color: var(--color-danger);
    color: #FFFFFF;
}
.btn-danger:hover {
    filter: brightness(0.92);
    color: #FFFFFF;
}
.btn-outline-danger {
    background: var(--status-danger-bg);
    border-color: var(--status-danger-border);
    color: var(--color-danger);
}
.btn-outline-danger:hover {
    background: var(--color-danger);
    border-color: var(--color-danger);
    color: #FFFFFF;
}

/* Warning Button */
.btn-warning {
    background: var(--color-warning);
    border-color: var(--color-warning);
    color: #FFFFFF;
}
.btn-warning:hover {
    filter: brightness(0.92);
    color: #FFFFFF;
}

/* Table Action Icon Buttons */
.btn-action {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    background: var(--card-bg);
    color: var(--text-muted);
    transition: all 0.12s ease;
}

.btn-action:hover {
    background: var(--surface-subtle);
    border-color: var(--border-strong);
    color: var(--text-heading);
}

/* ==========================================================================
   FORMS & INPUT CONTROLS
   ========================================================================== */

.form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-heading);
    margin-bottom: 6px;
    display: inline-block;
}

.form-control,
.form-select {
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--text-heading);
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 8px 14px;
    height: 40px;
    line-height: 1.4;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
}

.form-control-sm,
.form-select-sm {
    height: 34px;
    padding: 5px 12px;
    font-size: 0.8125rem;
    border-radius: var(--radius-sm);
}

.form-control::placeholder {
    color: var(--text-muted);
    font-weight: 400;
}

.form-control:hover,
.form-select:hover {
    border-color: var(--border-strong);
}

.form-control:focus,
.form-select:focus {
    border-color: var(--color-accent);
    outline: none;
    box-shadow: var(--focus-ring);
    background-color: var(--card-bg);
    color: var(--text-heading);
}

.form-control:disabled,
.form-control[readonly],
.form-select:disabled {
    background-color: var(--surface-subtle);
    border-color: var(--border-color);
    color: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.8;
}

.form-check-input {
    width: 1.2em;
    height: 1.2em;
    margin-top: 0.15em;
    background-color: var(--card-bg);
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-xs);
    cursor: pointer;
}

.form-check-input:checked {
    background-color: var(--color-accent);
    border-color: var(--color-accent);
}

.form-check-input:focus {
    border-color: var(--color-accent);
    box-shadow: var(--focus-ring);
}

.form-text {
    font-size: 0.745rem;
    color: var(--text-muted);
    margin-top: 4px;
}

.invalid-feedback {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-danger);
    margin-top: 4px;
}

.is-invalid {
    border-color: var(--color-danger) !important;
}

/* ==========================================================================
   STATUS BADGES & INDICATORS
   ========================================================================== */

.badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 4px 8px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1.25;
    border: 1px solid transparent;
    border-radius: var(--radius-sm);
}

.badge-active,
.badge-success,
.badge.bg-success {
    background-color: var(--status-active-bg) !important;
    color: var(--status-active-text) !important;
    border-color: var(--status-active-border);
}

.badge-pending,
.badge-warning,
.badge.bg-warning {
    background-color: var(--status-pending-bg) !important;
    color: var(--status-pending-text) !important;
    border-color: var(--status-pending-border);
}

.badge-rejected,
.badge-danger,
.badge.bg-danger {
    background-color: var(--status-danger-bg) !important;
    color: var(--status-danger-text) !important;
    border-color: var(--status-danger-border);
}

.badge-matured,
.badge-info,
.badge.bg-info {
    background-color: var(--status-info-bg) !important;
    color: var(--status-info-text) !important;
    border-color: var(--status-info-border);
}

.badge-inactive,
.badge-secondary,
.badge.bg-secondary {
    background-color: var(--status-neutral-bg) !important;
    color: var(--status-neutral-text) !important;
    border-color: var(--status-neutral-border);
}

.badge-payable {
    background-color: var(--status-active-bg) !important;
    color: var(--status-active-text) !important;
    border-color: var(--status-active-border);
    font-weight: 700;
}

/* Filter Bar Container */
.filter-card,
.card:has(form[method="GET"]) {
    margin-bottom: 20px;
}

/* Monospace Code Tag */
code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.775rem;
    background: var(--surface-subtle);
    color: var(--text-heading);
    padding: 2px 6px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xs);
}

/* ==========================================================================
   AGENT PROFILE & MOBILE BOTTOM NAVIGATION
   ========================================================================== */

.agent-minimal-card {
    background: var(--card-bg);
    padding: 18px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    position: relative;
}

.agent-minimal-avatar {
    width: 52px;
    height: 52px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-pill);
    overflow: hidden;
}

.agent-minimal-avatar-text {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-primary-light);
    color: var(--color-accent);
    font-weight: 700;
    font-size: 1.25rem;
    letter-spacing: -0.02em;
}

.agent-minimal-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 7px;
    background: var(--surface-subtle);
    color: var(--text-body);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xs);
    letter-spacing: 0.03em;
}

.agent-minimal-rank {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 8px;
    background: var(--color-primary-light);
    color: var(--color-accent);
    border: 1px solid var(--color-primary-border);
    border-radius: var(--radius-xs);
}

.agent-minimal-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-heading);
    letter-spacing: -0.02em;
    line-height: 1.25;
    margin-top: 3px;
}

.agent-minimal-sub {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 2px;
    font-weight: 500;
}

.agent-minimal-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding-top: 16px;
    margin-top: 16px;
    border-top: 1px solid var(--border-color);
}

.agent-action-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
}

.agent-action-chip span {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-body);
    margin-top: 6px;
    letter-spacing: -0.01em;
}

.agent-action-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    color: var(--text-heading);
    transition: all 0.12s ease;
}

.agent-action-chip.primary .agent-action-icon {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
}

/* Agent Mobile Bottom Navigation Bar */
.agent-mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 56px;
    background: var(--card-bg);
    border-top: 1px solid var(--border-color);
    z-index: 1040;
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 0 4px calc(env(safe-area-inset-bottom, 0px) + 2px) 4px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    height: 100%;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.68rem;
    font-weight: 500;
    padding: 4px 2px;
    position: relative;
    user-select: none;
    transition: color 0.12s ease;
}

.mobile-nav-item i {
    font-size: 1.2rem;
    line-height: 1.2;
    margin-bottom: 2px;
}

.mobile-nav-item.active {
    color: var(--color-accent);
    font-weight: 700;
}

.mobile-nav-item.active::after {
    content: '';
    position: absolute;
    top: 0;
    width: 24px;
    height: 3px;
    background: var(--color-accent);
    border-radius: var(--radius-pill);
}

.mobile-nav-fab {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    flex: 1;
    height: 100%;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.68rem;
    font-weight: 600;
    position: relative;
    padding-bottom: 5px;
}

.mobile-nav-fab-btn {
    width: 44px;
    height: 44px;
    background: var(--color-primary);
    color: #FFFFFF;
    border-radius: var(--radius-pill);
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: -16px;
    border: 3px solid var(--card-bg);
    box-shadow: var(--shadow-hover);
}

.mobile-nav-fab-btn i {
    font-size: 1.25rem;
    line-height: 1;
}

.mobile-nav-fab.active {
    color: var(--color-accent);
}

.mobile-chip-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
    margin-bottom: 14px;
    scrollbar-width: none;
}

.mobile-chip-bar::-webkit-scrollbar {
    display: none;
}

.mobile-chip {
    padding: 6px 14px;
    font-size: 0.775rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--text-body);
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-pill);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.12s ease;
    flex-shrink: 0;
    box-shadow: var(--shadow-subtle);
}

.mobile-chip:hover {
    color: var(--text-heading);
    background: var(--surface-subtle);
}

.mobile-chip.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #FFFFFF;
}

/* ==========================================================================
   DROPDOWNS & MODALS
   ========================================================================== */

.dropdown-menu {
    border: 1px solid var(--border-color) !important;
    background: var(--card-bg) !important;
    border-radius: var(--radius-lg) !important;
    box-shadow: var(--shadow-dropdown) !important;
    padding: 6px 0 !important;
    z-index: 1060 !important;
}

.dropdown-item {
    font-size: 0.8125rem !important;
    padding: 8px 16px !important;
    color: var(--text-body) !important;
    transition: background 0.12s ease, color 0.12s ease !important;
}

.dropdown-item:hover,
.dropdown-item:focus {
    background-color: var(--surface-subtle) !important;
    color: var(--text-heading) !important;
}

.dropdown-divider {
    border-top: 1px solid var(--border-color) !important;
    margin: 6px 0 !important;
}

.modal-content {
    background: var(--card-bg) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-modal) !important;
    overflow: hidden;
}

.modal-header {
    border-bottom: 1px solid var(--border-color) !important;
    padding: 16px 22px !important;
    background: var(--card-bg) !important;
}

.modal-body {
    padding: 20px 22px !important;
    color: var(--text-body);
}

.modal-footer {
    border-top: 1px solid var(--border-color) !important;
    padding: 14px 22px !important;
    background: var(--surface-subtle) !important;
}

/* ==========================================================================
   MOBILE & RESPONSIVE RULES
   ========================================================================== */

#sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 15, 12, 0.58);
    backdrop-filter: blur(3px);
    z-index: 1039;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

#sidebar-backdrop.show {
    opacity: 1;
    visibility: visible;
}

@media (max-width: 767.98px) {
    body.sidebar-mobile-open {
        overflow: hidden;
    }

    html.sidebar-collapsed #sidebar,
    body.sidebar-collapsed #sidebar,
    #sidebar {
        width: 280px !important;
        max-width: 86vw !important;
        transform: translateX(-100%);
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.45);
    }

    html.sidebar-collapsed #sidebar.open,
    body.sidebar-collapsed #sidebar.open,
    #sidebar.open {
        transform: translateX(0) !important;
    }

    html.sidebar-collapsed #main-content,
    body.sidebar-collapsed #main-content,
    #main-content {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Force visible labels and layout in mobile drawer even if collapsed was saved */
    html.sidebar-collapsed #sidebar .sidebar-brand,
    body.sidebar-collapsed #sidebar .sidebar-brand {
        padding: 0 14px !important;
        justify-content: space-between !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-brand .brand-wrapper,
    body.sidebar-collapsed #sidebar .sidebar-brand .brand-wrapper {
        justify-content: flex-start !important;
        gap: 11px !important;
    }

    html.sidebar-collapsed #sidebar .brand-text,
    body.sidebar-collapsed #sidebar .brand-text {
        display: flex !important;
    }

    html.sidebar-collapsed #sidebar .brand-title-row,
    body.sidebar-collapsed #sidebar .brand-title-row {
        display: flex !important;
    }

    html.sidebar-collapsed #sidebar .brand-badge,
    body.sidebar-collapsed #sidebar .brand-badge {
        display: inline-block !important;
    }

    html.sidebar-collapsed #sidebar .brand-sub,
    body.sidebar-collapsed #sidebar .brand-sub,
    html.sidebar-collapsed #sidebar .sidebar-context-body,
    body.sidebar-collapsed #sidebar .sidebar-context-body,
    html.sidebar-collapsed #sidebar .sidebar-footer-text,
    body.sidebar-collapsed #sidebar .sidebar-footer-text,
    html.sidebar-collapsed #sidebar .sidebar-footer-name,
    body.sidebar-collapsed #sidebar .sidebar-footer-name,
    html.sidebar-collapsed #sidebar .sidebar-footer-label,
    body.sidebar-collapsed #sidebar .sidebar-footer-label {
        display: block !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-signout-btn span,
    body.sidebar-collapsed #sidebar .sidebar-signout-btn span,
    html.sidebar-collapsed #sidebar .nav-group-caret,
    body.sidebar-collapsed #sidebar .nav-group-caret,
    html.sidebar-collapsed #sidebar .tree-root-item span span,
    body.sidebar-collapsed #sidebar .tree-root-item span span,
    html.sidebar-collapsed #sidebar .nav-link-item span,
    body.sidebar-collapsed #sidebar .nav-link-item span {
        display: inline-block !important;
    }

    html.sidebar-collapsed #sidebar .tree-main-trunk,
    body.sidebar-collapsed #sidebar .tree-main-trunk {
        display: block !important;
    }

    html.sidebar-collapsed #sidebar .nav-section-title,
    body.sidebar-collapsed #sidebar .nav-section-title {
        width: auto;
        height: auto;
        padding: 14px 10px 6px 10px !important;
        margin: 0 !important;
        background: transparent;
        text-indent: 0;
    }

    html.sidebar-collapsed #sidebar .nav-section-title::after,
    body.sidebar-collapsed #sidebar .nav-section-title::after {
        display: block !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-nav-container,
    body.sidebar-collapsed #sidebar .sidebar-nav-container {
        padding: 10px 10px 18px 10px;
    }

    html.sidebar-collapsed #sidebar .tree-root-item,
    body.sidebar-collapsed #sidebar .tree-root-item,
    html.sidebar-collapsed #sidebar .nav-link-item,
    body.sidebar-collapsed #sidebar .nav-link-item {
        justify-content: space-between !important;
        width: auto;
        height: auto;
        padding: 9px 11px !important;
        margin: 2px 0 !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-context-widget,
    body.sidebar-collapsed #sidebar .sidebar-context-widget {
        width: auto;
        height: auto;
        padding: 9px 11px !important;
        margin: 10px 10px 2px 10px !important;
        justify-content: flex-start !important;
        gap: 10px !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-footer,
    body.sidebar-collapsed #sidebar .sidebar-footer {
        padding: 10px 10px 12px 10px !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-user-info,
    body.sidebar-collapsed #sidebar .sidebar-user-info {
        display: block;
        width: auto;
        padding: 8px 10px !important;
        margin: 0 0 8px 0 !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-user-info .sidebar-user-row,
    body.sidebar-collapsed #sidebar .sidebar-user-info .sidebar-user-row {
        justify-content: flex-start !important;
        gap: 10px !important;
    }

    html.sidebar-collapsed #sidebar .sidebar-signout-btn,
    body.sidebar-collapsed #sidebar .sidebar-signout-btn {
        width: 100%;
        height: auto;
        padding: 8px 12px !important;
        margin: 0 !important;
    }

    #sidebar-floating-tooltip {
        display: none !important;
    }

    #topbar {
        padding: 0 14px;
    }

    .content-wrapper {
        padding: 16px 14px 68px 14px;
    }

    .page-header {
        margin-bottom: 16px;
        gap: 12px;
    }

    .card-body {
        padding: 16px 14px;
    }

    .stat-card,
    .metric-card {
        padding: 14px 16px;
    }
}

/* ==========================================================================
   GLOBAL DARK MODE UTILITY OVERRIDES
   Ensures complete seamless dark theme coverage across all modules & legacy views
   ========================================================================== */
html.dark .bg-white,
html.dark .card-header.bg-white,
html.dark .card-footer.bg-white {
    background-color: var(--card-bg, #111A15) !important;
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .bg-light {
    background-color: var(--surface-subtle, #18241E) !important;
    color: var(--text-body, #E2E8F0) !important;
}

html.dark .text-dark {
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .text-muted {
    color: var(--text-muted, #94A3B8) !important;
}

html.dark .text-secondary {
    color: var(--text-secondary, #CBD5E1) !important;
}

html.dark .border,
html.dark .border-top,
html.dark .border-bottom,
html.dark .border-start,
html.dark .border-end {
    border-color: var(--border-color, #1E2C24) !important;
}

html.dark .border-light {
    border-color: var(--border-color, #1E2C24) !important;
}

html.dark .badge.bg-light {
    background-color: var(--surface-elevated, #1F2E26) !important;
    color: var(--text-secondary, #CBD5E1) !important;
    border-color: var(--border-color, #1E2C24) !important;
}

html.dark .dropdown-menu {
    background-color: var(--card-bg, #111A15) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-body, #E2E8F0) !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.65) !important;
}

html.dark .dropdown-item {
    color: var(--text-body, #E2E8F0) !important;
}

html.dark .dropdown-item:hover,
html.dark .dropdown-item:focus {
    background-color: var(--surface-elevated, #1F2E26) !important;
    color: var(--color-accent, #34D399) !important;
}

html.dark .dropdown-divider {
    border-top-color: var(--border-color, #1E2C24) !important;
}

html.dark .list-group-item {
    background-color: var(--card-bg, #111A15) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-body, #E2E8F0) !important;
}

html.dark .modal-content {
    background-color: var(--card-bg, #111A15) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-body, #E2E8F0) !important;
}

html.dark .modal-header,
html.dark .modal-footer {
    border-color: var(--border-color, #1E2C24) !important;
    background-color: var(--card-bg, #111A15) !important;
}

html.dark .form-select,
html.dark .form-control {
    background-color: var(--surface-subtle, #18241E) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .form-select:focus,
html.dark .form-control:focus {
    background-color: var(--card-bg, #111A15) !important;
    border-color: var(--color-accent, #34D399) !important;
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .input-group-text {
    background-color: var(--surface-elevated, #1F2E26) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-secondary, #CBD5E1) !important;
}

html.dark .btn-outline-dark {
    border-color: var(--border-strong, #31453A) !important;
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .btn-outline-dark:hover {
    background-color: var(--surface-elevated, #1F2E26) !important;
    border-color: var(--color-accent, #34D399) !important;
    color: var(--color-accent, #34D399) !important;
}

html.dark .btn-light {
    background-color: var(--surface-elevated, #1F2E26) !important;
    border-color: var(--border-color, #1E2C24) !important;
    color: var(--text-heading, #F8FAFC) !important;
}

html.dark .btn-light:hover {
    background-color: var(--color-primary-hover, #059669) !important;
    border-color: var(--color-primary-hover, #059669) !important;
    color: #FFFFFF !important;
}

html.dark .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* ==========================================================================
   PREMIUM VECTOR ICON ALIGNMENT & MICRO-POLISH
   ========================================================================== */
i.bi.mc-icon-hydrated {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    vertical-align: -0.14em;
    flex-shrink: 0;
}

i.bi.mc-icon-hydrated::before {
    content: none !important;
    display: none !important;
}

.mc-svg-icon {
    width: 1em;
    height: 1em;
    display: block;
    flex-shrink: 0;
    pointer-events: none;
    transition: transform 0.18s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Crisp sidebar icon scale & subtle spring hover */
.tree-root-item i,
.nav-link-item i {
    font-size: 1.08rem;
}

.tree-root-item:hover i:not(.nav-group-caret) .mc-svg-icon,
.nav-link-item:hover i .mc-svg-icon {
    transform: scale(1.07);
}

.tree-link-node i {
    font-size: 0.92rem;
}

/* Balanced SVG sizing inside buttons, badges, inputs, and table actions */
.btn i.mc-icon-hydrated,
.badge i.mc-icon-hydrated,
.input-group-text i.mc-icon-hydrated {
    font-size: 0.96em;
    vertical-align: -0.12em;
}

.btn-sm i.mc-icon-hydrated {
    font-size: 0.94em;
}

.topbar-icon-btn i.mc-icon-hydrated,
.theme-toggle-btn i.mc-icon-hydrated,
.sidebar-toggle-btn i.mc-icon-hydrated {
    font-size: 1.08rem;
}

.topbar-icon-btn:hover i .mc-svg-icon,
.theme-toggle-btn:hover i .mc-svg-icon {
    transform: scale(1.08);
}

.empty-state-icon i.mc-icon-hydrated,
.icon-box i.mc-icon-hydrated {
    font-size: 1.5rem;
}

/* ==========================================================================
   GLOBAL PRINT PURIFICATION SAFEGUARD (DARK MODE RESILIENCE)
   ========================================================================== */
@media print {
    :root,
    html,
    html.dark,
    body,
    body.dark,
    [data-theme="dark"],
    [data-bs-theme="dark"] {
        color-scheme: light !important;
        --body-bg: #ffffff !important;
        --bg-page: #ffffff !important;
        --card-bg: #ffffff !important;
        --surface-subtle: #f8fafc !important;
        --surface-elevated: #ffffff !important;
        --surface-muted: #f1f5f9 !important;
        --text-heading: #0f172a !important;
        --text-body: #0f172a !important;
        --text-secondary: #334155 !important;
        --text-muted: #475569 !important;
        --border-color: #cbd5e1 !important;
        --border-strong: #94a3b8 !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
    }

    html,
    body,
    #main-content,
    .content-wrapper,
    main {
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    #sidebar,
    #sidebar-backdrop,
    #topbar,
    .agent-mobile-bottom-nav,
    .theme-toggle-btn,
    .topbar-icon-btn,
    .loading-overlay,
    #loadingOverlay {
        display: none !important;
    }

    html.dark .bg-white,
    html.dark .card-header.bg-white,
    html.dark .card-footer.bg-white {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }

    html.dark .bg-light {
        background-color: #f8fafc !important;
        color: #0f172a !important;
    }

    html.dark .text-dark,
    html.dark .text-body,
    html.dark .text-heading {
        color: #0f172a !important;
    }

    html.dark .text-muted {
        color: #475569 !important;
    }

    html.dark .text-secondary {
        color: #334155 !important;
    }
}
</style>
