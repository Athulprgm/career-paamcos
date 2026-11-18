<style>
/* ==========================================================================
   SUPER ADMIN DASHBOARD — DARK GLASSMORPHIC BRAND SYSTEM
   Deep Navy Blue (#0B1B3B / #0A0F2C) + Dark Olive Green (#2C5530 / #4F6F52)
   + Electric Lime Green (#B6D83E / #C7F000)
   Scoped to Super Admin Dashboard dark theme; light mode preserves Agro Green.
   ========================================================================== */

html.dark.sa-dashboard-page,
html.dark.sa-dashboard-page body {
    --sa-navy-deep: #0A0F2C;
    --sa-navy-card: #0B1B3B;
    --sa-olive-deep: #2C5530;
    --sa-olive-muted: #4F6F52;
    --sa-lime-soft: #B6D83E;
    --sa-lime-electric: #C7F000;

    --body-bg: #0A0F2C;
    --bg-page: #0A0F2C;
    --card-bg: rgba(11, 27, 59, 0.72);
    --surface-subtle: rgba(44, 85, 48, 0.24);
    --bg-surface-alt: rgba(44, 85, 48, 0.24);
    --surface-elevated: rgba(11, 27, 59, 0.90);
    --surface-muted: rgba(79, 111, 82, 0.34);

    --color-primary: #B6D83E;
    --color-primary-hover: #C7F000;
    --color-primary-dark: #2C5530;
    --color-primary-light: rgba(182, 216, 62, 0.14);
    --color-primary-border: rgba(182, 216, 62, 0.38);
    --color-primary-ring: rgba(199, 240, 0, 0.26);
    --color-accent: #C7F000;
    --color-accent-light: rgba(199, 240, 0, 0.14);
    --color-secondary: #B6D83E;
    --color-primary-green: #C7F000;
    --color-deep-green: #2C5530;

    --text-heading: #F8FAFC;
    --text-body: #E2E8F0;
    --text-secondary: #CBD5E1;
    --text-muted: #94A3B8;
    --text-subtle: #64748B;

    --border-color: rgba(79, 111, 82, 0.44);
    --border-strong: rgba(182, 216, 62, 0.45);
    --border-dark: rgba(79, 111, 82, 0.5);
    --border-focus: #C7F000;
    --focus-ring: 0 0 0 3px rgba(199, 240, 0, 0.26);

    --shadow-subtle: 0 4px 12px rgba(4, 7, 22, 0.45);
    --shadow-card: 0 16px 36px -12px rgba(3, 6, 20, 0.72), inset 0 1px 0 rgba(255, 255, 255, 0.06);
    --shadow-hover: 0 20px 44px -10px rgba(3, 6, 20, 0.84), 0 0 0 1px rgba(182, 216, 62, 0.22);
    --shadow-dropdown: 0 18px 40px -8px rgba(3, 6, 20, 0.85);
    --shadow-modal: 0 24px 48px -10px rgba(2, 5, 18, 0.9);

    --sidebar-bg: linear-gradient(180deg, #0B1B3B 0%, #0A0F2C 100%);
    --sidebar-brand-bg: rgba(11, 27, 59, 0.48);
    --sidebar-border: rgba(79, 111, 82, 0.36);
    --sidebar-shadow: 4px 0 28px rgba(3, 6, 20, 0.55);
    --sidebar-hover-bg: rgba(44, 85, 48, 0.28);
    --sidebar-active-bg: linear-gradient(135deg, #2C5530 0%, #4F6F52 100%);
    --sidebar-active-border: rgba(199, 240, 0, 0.52);
    --sidebar-active-shadow: 0 6px 18px -4px rgba(199, 240, 0, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.14);
    --sidebar-active-text: #FFFFFF;
    --sidebar-active-icon: #C7F000;
    --sidebar-group-active-bg: rgba(44, 85, 48, 0.30);
    --sidebar-group-active-border: rgba(79, 111, 82, 0.52);
    --sidebar-group-active-icon: #B6D83E;
    --sidebar-sub-active-bg: rgba(182, 216, 62, 0.15);
    --sidebar-sub-active-border: rgba(199, 240, 0, 0.42);
    --sidebar-sub-active-icon: #C7F000;
    --sidebar-icon-hover: #C7F000;
    --sidebar-icon-active: #C7F000;
    --sidebar-widget-bg: rgba(44, 85, 48, 0.22);
    --sidebar-widget-border: rgba(79, 111, 82, 0.40);
    --sidebar-widget-hover-border: rgba(182, 216, 62, 0.45);
    --sidebar-badge-bg: rgba(182, 216, 62, 0.16);
    --sidebar-badge-text: #C7F000;
    --sidebar-badge-border: rgba(199, 240, 0, 0.42);
    --sidebar-logo-border: rgba(182, 216, 62, 0.55);
    --sidebar-tooltip-bg: #0B1B3B;
    --sidebar-tooltip-border: rgba(199, 240, 0, 0.45);
    --tree-line: rgba(79, 111, 82, 0.42);

    --topbar-bg: rgba(10, 15, 44, 0.78);
    --topbar-border: rgba(79, 111, 82, 0.38);

    --status-active-bg: rgba(182, 216, 62, 0.16);
    --status-active-text: #C7F000;
    --status-active-border: rgba(199, 240, 0, 0.42);

    --bs-primary: #B6D83E;
    --bs-primary-rgb: 182, 216, 62;
    --bs-primary-text-emphasis: #C7F000;
    --bs-primary-bg-subtle: rgba(182, 216, 62, 0.15);
    --bs-primary-border-subtle: rgba(182, 216, 62, 0.36);
    --bs-link-color: #B6D83E;
    --bs-link-hover-color: #C7F000;
}

html.dark.sa-dashboard-page body {
    background:
        radial-gradient(circle at 14% 6%, rgba(44, 85, 48, 0.30), transparent 44%),
        radial-gradient(circle at 86% 10%, rgba(182, 216, 62, 0.08), transparent 36%),
        radial-gradient(circle at 74% 88%, rgba(79, 111, 82, 0.18), transparent 46%),
        linear-gradient(180deg, #0A0F2C 0%, #0B1B3B 52%, #080C24 100%) !important;
    background-attachment: fixed !important;
}

html.dark.sa-dashboard-page #topbar {
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

html.dark.sa-dashboard-page .mx-card,
html.dark.sa-dashboard-page .mx-metric-card,
html.dark.sa-dashboard-page .mx-alert-strip,
html.dark.sa-dashboard-page .mx-onboarding-card {
    background: linear-gradient(145deg, rgba(11, 27, 59, 0.78) 0%, rgba(10, 15, 44, 0.72) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(79, 111, 82, 0.46);
    box-shadow: 0 18px 40px -14px rgba(2, 5, 18, 0.78), inset 0 1px 0 rgba(255, 255, 255, 0.07);
}

html.dark.sa-dashboard-page .mx-card:hover {
    border-color: rgba(182, 216, 62, 0.36);
}

html.dark.sa-dashboard-page .mx-metric-link:hover .mx-metric-card {
    border-color: #B6D83E;
    box-shadow: 0 22px 44px -12px rgba(2, 5, 18, 0.85), 0 0 0 1px rgba(199, 240, 0, 0.25);
}

html.dark.sa-dashboard-page .mx-telemetry-card,
html.dark.sa-dashboard-page .mx-portfolio-card {
    background: linear-gradient(145deg, rgba(11, 27, 59, 0.82) 0%, rgba(16, 36, 68, 0.68) 65%, rgba(44, 85, 48, 0.24) 100%);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-color: rgba(79, 111, 82, 0.52);
}

html.dark.sa-dashboard-page .mx-mini-card,
html.dark.sa-dashboard-page .mx-seg-group,
html.dark.sa-dashboard-page .mx-more-btn,
html.dark.sa-dashboard-page .mx-metric-mini-icon,
html.dark.sa-dashboard-page .mx-table thead th {
    background: rgba(44, 85, 48, 0.24);
    border-color: rgba(79, 111, 82, 0.48);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

html.dark.sa-dashboard-page .mx-mini-card:hover {
    background: rgba(44, 85, 48, 0.36);
    border-color: #B6D83E;
}

html.dark.sa-dashboard-page .mx-seg-btn.active {
    background: linear-gradient(135deg, #2C5530 0%, #4F6F52 100%);
    color: #C7F000;
    border: 1px solid rgba(199, 240, 0, 0.42);
    box-shadow: 0 4px 12px rgba(4, 7, 22, 0.45);
}

html.dark.sa-dashboard-page .mx-live-chip {
    background: rgba(182, 216, 62, 0.14);
    color: #C7F000;
    border: 1px solid rgba(199, 240, 0, 0.42);
    box-shadow: 0 0 18px rgba(199, 240, 0, 0.12);
}

html.dark.sa-dashboard-page .mx-metric-val,
html.dark.sa-dashboard-page .mx-gauge-pct,
html.dark.sa-dashboard-page .mx-mini-val {
    color: #F8FAFC;
}

html.dark.sa-dashboard-page .mx-metric-highlight {
    color: #C7F000 !important;
}

html.dark.sa-dashboard-page .mx-pill-btn.primary,
html.dark.sa-dashboard-page .mx-pill-btn.brand {
    background: linear-gradient(135deg, #B6D83E 0%, #C7F000 100%);
    color: #0A0F2C !important;
    border-color: rgba(199, 240, 0, 0.65);
    box-shadow: 0 8px 20px -4px rgba(199, 240, 0, 0.28);
}

html.dark.sa-dashboard-page .mx-pill-btn.primary:hover,
html.dark.sa-dashboard-page .mx-pill-btn.brand:hover {
    background: linear-gradient(135deg, #C7F000 0%, #D9FF33 100%);
    color: #0A0F2C !important;
    box-shadow: 0 10px 24px -4px rgba(199, 240, 0, 0.40);
}

html.dark.sa-dashboard-page .mx-pill-btn.ghost {
    background: rgba(44, 85, 48, 0.28);
    border-color: rgba(79, 111, 82, 0.56);
    color: #E2E8F0 !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

html.dark.sa-dashboard-page .mx-pill-btn.ghost:hover {
    background: rgba(44, 85, 48, 0.48);
    border-color: #B6D83E;
    color: #C7F000 !important;
}

html.dark.sa-dashboard-page .mx-vault-card {
    background: linear-gradient(135deg, #0B1B3B 0%, #17344A 50%, #2C5530 100%);
    border: 1px solid rgba(182, 216, 62, 0.34);
    box-shadow: 0 18px 36px -10px rgba(2, 5, 18, 0.82), inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

html.dark.sa-dashboard-page .mx-vault-chip {
    background: rgba(182, 216, 62, 0.18);
    color: #C7F000;
    border: 1px solid rgba(199, 240, 0, 0.38);
}

html.dark.sa-dashboard-page .mx-balance-bar-fill {
    background: linear-gradient(90deg, #4F6F52 0%, #B6D83E 60%, #C7F000 100%);
}

html.dark.sa-dashboard-page .mx-table tbody tr:hover td {
    background: rgba(44, 85, 48, 0.20);
}

html.dark.sa-dashboard-page .mx-code {
    color: #C7F000;
    background: rgba(44, 85, 48, 0.34);
    border-color: rgba(182, 216, 62, 0.36);
}

/* Goal Gauge Stop Colors */
.mx-goal-stop-start { stop-color: #C2410C; }
.mx-goal-stop-mid   { stop-color: #EA580C; }
.mx-goal-stop-end   { stop-color: #059669; }

html.dark.sa-dashboard-page .mx-goal-stop-start { stop-color: #4F6F52; }
html.dark.sa-dashboard-page .mx-goal-stop-mid   { stop-color: #B6D83E; }
html.dark.sa-dashboard-page .mx-goal-stop-end   { stop-color: #C7F000; }

/* Telemetry Chart Canvas Transparency (Prevents White Flashes on Re-render) */
.mx-telemetry-canvas-wrap {
    height: 255px;
    position: relative;
    background: transparent;
}

#monthlyTrendsChart,
#portfolioDonutChart {
    background-color: transparent !important;
}

/* Onboarding Banner Card when Deposit Ledger is Empty */
.mx-onboarding-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 1.05rem 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.75rem;
    box-shadow: var(--shadow-card);
}
</style>
