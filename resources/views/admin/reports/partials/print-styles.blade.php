<style>
@media print {
    /* ─── Page Setup ─────────────────────────────────────────── */
    @page {
        size: {{ $orientation ?? 'landscape' }};
        margin: 8mm 8mm 8mm 8mm;
    }

    /* ─── Global Reset & CSS Token Override for Print ───────── */
    /* Resets all dark mode CSS custom properties to clean, ink-friendly light defaults */
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
        --bg-surface-alt: #f8fafc !important;
        --text-heading: #0f172a !important;
        --text-body: #0f172a !important;
        --text-secondary: #334155 !important;
        --text-muted: #475569 !important;
        --text-subtle: #64748b !important;
        --border-color: #cbd5e1 !important;
        --border-strong: #94a3b8 !important;
        --border-dark: #64748b !important;
        --color-primary: #14532d !important;
        --color-primary-green: #14532d !important;
        --color-deep-green: #14532d !important;
        --color-accent: #059669 !important;
        --color-copper: #c2410c !important;
        --color-gold: #c2410c !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
    }

    /* ─── Force All Layout Containers to Pristine White Paper ── */
    html,
    body,
    #main-content,
    .content-wrapper,
    .container-fluid,
    main,
    .card,
    .card-body,
    .table-responsive {
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        box-shadow: none !important;
    }

    html, body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        font-size: 8.5pt !important;
        line-height: 1.3 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* ─── Main Content Root Layout Override ──────────────────── */
    /* Prevents WebKit/Blink multi-page clipping caused by flex containers & dark backgrounds */
    #main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        display: block !important;
        overflow: visible !important;
        position: static !important;
        float: none !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    .content-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        display: block !important;
        overflow: visible !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    /* ─── Neutralize Dark Mode Global Overrides in Print ─────── */
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

    html.dark .text-primary {
        color: #1d4ed8 !important;
    }

    html.dark .text-success {
        color: #15803d !important;
    }

    html.dark .text-warning {
        color: #b45309 !important;
    }

    html.dark .text-danger {
        color: #b91c1c !important;
    }

    html.dark .border,
    html.dark .border-top,
    html.dark .border-bottom,
    html.dark .border-start,
    html.dark .border-end,
    html.dark .border-light {
        border-color: #cbd5e1 !important;
    }

    html.dark .badge.bg-light {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
    }

    html.dark a,
    html.dark a:hover,
    html.dark a.text-dark {
        color: #0f172a !important;
        text-decoration: none !important;
    }

    /* ─── Hide Interactive & Screen-Only Elements ───────────── */
    #sidebar,
    #sidebar-backdrop,
    #topbar,
    .page-header,
    .reports-nav,
    .filter-card,
    .report-actions,
    .pagination,
    .btn,
    .agent-mobile-bottom-nav,
    .d-print-none,
    th.d-print-none,
    td.d-print-none,
    .no-print,
    .action-col,
    .toast-container,
    .modal,
    .modal-backdrop,
    #loadingOverlay,
    .loading-overlay,
    .dropdown-menu {
        display: none !important;
    }

    /* Clean link styling for print */
    a {
        text-decoration: none !important;
        color: inherit !important;
    }

    /* ─── Card Simplification ────────────────────────────────── */
    .card,
    html.dark .card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        background-color: transparent !important;
        margin-bottom: 0 !important;
        padding: 0 !important;
    }

    .card-header,
    html.dark .card-header {
        background: transparent !important;
        background-color: transparent !important;
        border: none !important;
        padding: 4px 0 3px 0 !important;
        font-weight: 700 !important;
        font-size: 8.5pt !important;
        color: #0f172a !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .card-header span,
    html.dark .card-header span {
        color: #0f172a !important;
    }

    .card-header .badge,
    html.dark .card-header .badge {
        font-size: 7.2pt !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        padding: 1px 6px !important;
        border-radius: 3px !important;
    }

    .card-body,
    html.dark .card-body {
        padding: 0 !important;
        background: transparent !important;
        background-color: transparent !important;
    }

    .card-footer,
    html.dark .card-footer {
        display: none !important;
    }

    /* ─── Ink-Friendly Compact Summary Strip ─────────────────── */
    .report-summary-cards,
    html.dark .report-summary-cards {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        gap: 6px !important;
        margin-bottom: 8px !important;
        width: 100% !important;
        background: transparent !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    .report-summary-cards > [class*="col"] {
        flex: 1 1 0 !important;
        width: auto !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    .stat-card,
    .stat-card-overview,
    html.dark .stat-card,
    html.dark .stat-card-overview {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        padding: 5px 8px !important;
        border: 1px solid #94a3b8 !important;
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        box-shadow: none !important;
        border-radius: 4px !important;
        min-height: auto !important;
        height: auto !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .stat-icon {
        display: none !important;
    }

    .stat-value,
    .stat-card .stat-value,
    .stat-card-overview .stat-value,
    html.dark .stat-card .stat-value,
    html.dark .stat-card-overview .stat-value {
        font-size: 10.5pt !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        line-height: 1.15 !important;
        font-family: 'Inter', monospace, sans-serif !important;
    }

    .stat-card .stat-value.text-success,
    .stat-card-overview .stat-value.text-success,
    html.dark .stat-card .stat-value.text-success,
    html.dark .stat-card-overview .stat-value.text-success {
        color: #15803d !important;
    }

    .stat-card .stat-value.text-warning,
    .stat-card-overview .stat-value.text-warning,
    html.dark .stat-card .stat-value.text-warning,
    html.dark .stat-card-overview .stat-value.text-warning {
        color: #b45309 !important;
    }

    .stat-card .stat-value.text-danger,
    .stat-card-overview .stat-value.text-danger,
    html.dark .stat-card .stat-value.text-danger,
    html.dark .stat-card-overview .stat-value.text-danger {
        color: #b91c1c !important;
    }

    .stat-card .stat-value.text-primary,
    .stat-card-overview .stat-value.text-primary,
    html.dark .stat-card .stat-value.text-primary,
    html.dark .stat-card-overview .stat-value.text-primary {
        color: #1d4ed8 !important;
    }

    .stat-label,
    .stat-card .stat-label,
    .stat-card-overview .stat-label,
    html.dark .stat-card .stat-label,
    html.dark .stat-card-overview .stat-label {
        font-size: 6.8pt !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
        color: #475569 !important;
        font-weight: 700 !important;
        margin-top: 1px !important;
        line-height: 1.1 !important;
    }

    .stat-card .small,
    .stat-card .text-muted,
    .stat-card-overview .small,
    .stat-subtext,
    html.dark .stat-card .text-muted,
    html.dark .stat-card-overview .small {
        font-size: 6.8pt !important;
        font-weight: 600 !important;
        color: #334155 !important;
        margin-top: 1px !important;
        line-height: 1.1 !important;
        font-family: 'Inter', monospace, sans-serif !important;
    }

    /* Career Ladder Mini-Cards in Print */
    .ladder-distribution-card,
    html.dark .ladder-distribution-card {
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        padding: 5px 6px !important;
        border-radius: 4px !important;
        box-shadow: none !important;
        color: #0f172a !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* ─── Audit Ledger Table ─────────────────────────────────── */
    .table-responsive,
    html.dark .table-responsive {
        overflow: visible !important;
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    table.table,
    html.dark table.table {
        width: 100% !important;
        table-layout: auto !important;
        border-collapse: collapse !important;
        font-size: 7.8pt !important;
        margin-bottom: 0 !important;
        color: #0f172a !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    table.table thead,
    html.dark table.table thead {
        display: table-header-group !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    table.table tfoot,
    html.dark table.table tfoot {
        display: table-footer-group !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    table.table tbody,
    html.dark table.table tbody {
        display: table-row-group !important;
    }

    table.table tr,
    html.dark table.table tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
    }

    table.table thead th,
    html.dark table.table thead th,
    table.table thead.table-light th,
    html.dark table.table thead.table-light th {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 7.2pt !important;
        text-transform: uppercase !important;
        border: 1px solid #475569 !important;
        padding: 4px 5px !important;
        white-space: nowrap !important;
        letter-spacing: 0.02em !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table.table tbody td,
    html.dark table.table tbody td {
        border: 1px solid #cbd5e1 !important;
        padding: 3.5px 5px !important;
        color: #0f172a !important;
        vertical-align: middle !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table.table tbody tr:nth-child(even) td,
    html.dark table.table tbody tr:nth-child(even) td {
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table.table tfoot td,
    html.dark table.table tfoot td,
    table.table tfoot.table-light td,
    html.dark table.table tfoot.table-light td {
        border: 1px solid #334155 !important;
        background-color: #f1f5f9 !important;
        font-weight: 700 !important;
        padding: 4px 5px !important;
        color: #0f172a !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Links and sub-elements inside table cells */
    table.table td a,
    table.table td a.text-dark,
    html.dark table.table td a,
    html.dark table.table td a.text-dark {
        color: #0f172a !important;
        text-decoration: none !important;
        font-weight: 600 !important;
    }

    table.table td .text-dark,
    html.dark table.table td .text-dark {
        color: #0f172a !important;
    }

    table.table td .text-muted,
    html.dark table.table td .text-muted {
        color: #475569 !important;
    }

    table.table td .text-secondary,
    html.dark table.table td .text-secondary {
        color: #334155 !important;
    }

    table.table td .text-success,
    table.table tfoot td.text-success,
    html.dark table.table td .text-success,
    html.dark table.table tfoot td.text-success {
        color: #15803d !important;
        font-weight: 600 !important;
    }

    table.table td .text-warning,
    html.dark table.table td .text-warning {
        color: #b45309 !important;
    }

    table.table td .text-danger,
    html.dark table.table td .text-danger {
        color: #b91c1c !important;
    }

    table.table td .text-primary,
    html.dark table.table td .text-primary {
        color: #1d4ed8 !important;
    }

    table.table td .font-monospace,
    html.dark table.table td .font-monospace {
        color: #334155 !important;
        font-family: monospace, sans-serif !important;
    }

    /* Format table badges with crisp, ink-friendly borders */
    table.table .badge,
    html.dark table.table .badge {
        display: inline-block !important;
        border: 1px solid #94a3b8 !important;
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        color: #0f172a !important;
        padding: 1px 5px !important;
        font-size: 6.8pt !important;
        font-weight: 600 !important;
        border-radius: 3px !important;
        box-shadow: none !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table.table .badge.bg-success-subtle,
    table.table .badge.text-success,
    table.table .badge.bg-success,
    html.dark table.table .badge.bg-success-subtle,
    html.dark table.table .badge.text-success,
    html.dark table.table .badge.bg-success {
        border-color: #16a34a !important;
        background: #f0fdf4 !important;
        background-color: #f0fdf4 !important;
        color: #14532d !important;
    }

    table.table .badge.bg-warning-subtle,
    table.table .badge.text-warning,
    table.table .badge.bg-warning,
    html.dark table.table .badge.bg-warning-subtle,
    html.dark table.table .badge.text-warning,
    html.dark table.table .badge.bg-warning {
        border-color: #d97706 !important;
        background: #fffbeb !important;
        background-color: #fffbeb !important;
        color: #78350f !important;
    }

    table.table .badge.bg-danger-subtle,
    table.table .badge.text-danger,
    table.table .badge.bg-danger,
    html.dark table.table .badge.bg-danger-subtle,
    html.dark table.table .badge.text-danger,
    html.dark table.table .badge.bg-danger {
        border-color: #dc2626 !important;
        background: #fef2f2 !important;
        background-color: #fef2f2 !important;
        color: #7f1d1d !important;
    }

    table.table .badge.bg-primary-subtle,
    table.table .badge.text-primary,
    table.table .badge.bg-primary,
    html.dark table.table .badge.bg-primary-subtle,
    html.dark table.table .badge.text-primary,
    html.dark table.table .badge.bg-primary {
        border-color: #2563eb !important;
        background: #eff6ff !important;
        background-color: #eff6ff !important;
        color: #1e3a8a !important;
    }

    table.table .badge.bg-secondary-subtle,
    table.table .badge.text-secondary,
    table.table .badge.bg-secondary,
    html.dark table.table .badge.bg-secondary-subtle,
    html.dark table.table .badge.text-secondary,
    html.dark table.table .badge.bg-secondary {
        border-color: #64748b !important;
        background: #f1f5f9 !important;
        background-color: #f1f5f9 !important;
        color: #334155 !important;
    }

    /* ─── Co-operative Header & Footer ───────────────────────── */
    .print-coop-header,
    html.dark .print-coop-header {
        display: block !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        margin-bottom: 8px !important;
        padding-bottom: 6px !important;
        border-bottom: 2px solid #14532d !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-coop-header * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-coop-header .print-meta-pill,
    html.dark .print-coop-header .print-meta-pill {
        display: inline-block !important;
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 3px !important;
        padding: 1px 6px !important;
        margin: 1px 0 1px 5px !important;
    }

    .print-coop-footer,
    html.dark .print-coop-footer {
        display: block !important;
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-top: 14px !important;
        clear: both !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-signoff-table td,
    html.dark .print-signoff-table td {
        background: #fafafa !important;
        background-color: #fafafa !important;
        border: 1px dashed #94a3b8 !important;
        color: #0f172a !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-coop-footer * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

@media screen {
    .print-coop-header,
    .print-coop-footer {
        display: none !important;
    }
}
</style>

<script>
(function() {
    let wasDarkBeforePrint = false;

    window.addEventListener('beforeprint', function() {
        const root = document.documentElement;
        if (root.classList.contains('dark') || root.getAttribute('data-theme') === 'dark') {
            wasDarkBeforePrint = true;
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
            root.setAttribute('data-bs-theme', 'light');
            if (document.body) {
                document.body.classList.remove('dark');
            }
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
            if (document.body) {
                document.body.classList.add('dark');
            }
            wasDarkBeforePrint = false;
        }
    });
})();
</script>
