<style>
/* ==========================================================================
   MULTICRED FINTECH ADMIN DASHBOARD SYSTEM (MONIEX-INSPIRED LAYOUT)
   Light & Dark Theme Parity • Real Core Banking Data
   ========================================================================== */

.mx-shell {
    --mx-series-1: #14532D;
    --mx-series-2: #059669;
    --mx-series-3: #C2410C;
    --mx-series-4: #15803D;
    --mx-series-5: #D97706;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

html.dark .mx-shell {
    --mx-series-1: #10B981;
    --mx-series-2: #34D399;
    --mx-series-3: #FB923C;
    --mx-series-4: #6EE7B7;
    --mx-series-5: #FBBF24;
}

/* ─── Clean Enterprise Header Integration ─────────────────────────────────── */
.mx-shell .page-header {
    margin-bottom: 0.25rem;
}

.mx-fy-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    background: var(--color-primary-light);
    color: var(--color-primary);
    border: 1px solid var(--color-primary-border);
    vertical-align: middle;
}

/* ─── Base Banking Card (Matches Enterprise Theme) ──────────────────────── */
.mx-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg, 14px);
    padding: 1.15rem 1.25rem;
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.mx-card:hover {
    border-color: var(--border-strong);
}

.mx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}

.mx-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-heading);
    letter-spacing: -0.015em;
    margin: 0;
}

.mx-card-sub {
    font-size: 0.73rem;
    color: var(--text-muted);
    margin: 2px 0 0 0;
}

.mx-more-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted) !important;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    text-decoration: none;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.mx-more-btn:hover {
    color: var(--color-primary) !important;
    border-color: var(--color-primary-border);
    background: var(--color-primary-light);
}

/* ─── Top 3 Metric Cards ─────────────────────────────────────────────────── */
.mx-metric-link {
    display: block;
    height: 100%;
    text-decoration: none !important;
    color: inherit;
}

.mx-metric-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg, 14px);
    padding: 1.05rem 1.2rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.7rem;
    box-shadow: var(--shadow-card);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.mx-metric-link:hover .mx-metric-card {
    transform: translateY(-2px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-hover);
}

.mx-metric-label-row {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-secondary);
}

.mx-metric-mini-icon {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    background: var(--surface-subtle);
    color: var(--color-primary);
    border: 1px solid var(--border-color);
}

.mx-metric-mini-icon.copper {
    background: var(--color-copper-light);
    color: var(--color-copper);
    border-color: var(--color-copper-border);
}

.mx-metric-val {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-heading);
    letter-spacing: -0.03em;
    line-height: 1.15;
    font-variant-numeric: tabular-nums;
}

.mx-trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.69rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    width: fit-content;
}

.mx-trend-pill.up {
    background: var(--status-active-bg);
    color: var(--status-active-text);
}

.mx-trend-pill.warn {
    background: var(--status-pending-bg);
    color: var(--status-pending-text);
}

.mx-trend-pill.neutral {
    background: var(--surface-subtle);
    color: var(--text-secondary);
}

/* ─── Portfolio Breakdown Donut + Side Legend ────────────────────────────── */
.mx-breakdown-grid {
    display: grid;
    grid-template-columns: 155px 1fr;
    align-items: center;
    gap: 1.1rem;
    flex: 1;
}

@media (max-width: 575.98px) {
    .mx-breakdown-grid {
        grid-template-columns: 1fr;
    }
}

.mx-donut-box {
    position: relative;
    width: 152px;
    height: 152px;
    margin: 0 auto;
}

.mx-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    pointer-events: none;
    padding: 0 18px;
}

.mx-donut-cap {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
}

.mx-donut-total {
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--text-heading);
    font-variant-numeric: tabular-nums;
    margin-top: 2px;
}

.mx-legend-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.mx-legend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.78rem;
}

.mx-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    flex-shrink: 0;
}

/* ─── Semi-Circle Arc Gauge (Saving / Mobilization Goal) ─────────────────── */
.mx-gauge-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    flex: 1;
    gap: 0.75rem;
}

.mx-gauge-svg-box {
    position: relative;
    width: 210px;
    height: 118px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.mx-gauge-overlay {
    position: absolute;
    bottom: 6px;
    left: 0;
    right: 0;
    text-align: center;
}

.mx-gauge-pct {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--text-heading);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.mx-gauge-sub {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-top: 3px;
}

.mx-goal-stats {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    padding-top: 0.65rem;
    border-top: 1px dashed var(--border-color);
}

/* ─── Segmented Controls & Action Buttons ────────────────────────────────── */
.mx-seg-group {
    display: inline-flex;
    align-items: center;
    padding: 3px;
    border-radius: 8px;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    gap: 2px;
}

.mx-seg-btn {
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.71rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mx-seg-btn.active {
    background: var(--card-bg);
    color: var(--text-heading);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.mx-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0.45rem 0.9rem;
    border-radius: var(--radius-md, 8px);
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    border: 1px solid transparent;
}

.mx-pill-btn.primary,
.mx-pill-btn.brand {
    background: linear-gradient(180deg, #166534 0%, #14532D 100%);
    border-color: #14532D;
    color: #FFFFFF !important;
    box-shadow: 0 2px 6px rgba(20, 83, 45, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.14);
}

.mx-pill-btn.primary:hover,
.mx-pill-btn.brand:hover {
    background: linear-gradient(180deg, #15803D 0%, #0F3F22 100%);
    border-color: #0F3F22;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -2px rgba(20, 83, 45, 0.34);
    color: #FFFFFF !important;
}

html.dark .mx-pill-btn.primary,
html.dark .mx-pill-btn.brand {
    background: linear-gradient(180deg, #059669 0%, #047857 100%);
    border-color: #059669;
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

html.dark .mx-pill-btn.primary:hover,
html.dark .mx-pill-btn.brand:hover {
    background: linear-gradient(180deg, #10B981 0%, #059669 100%);
    border-color: #10B981;
    box-shadow: 0 6px 16px -2px rgba(16, 185, 129, 0.38);
    color: #FFFFFF !important;
}

.mx-pill-btn.ghost {
    background: var(--surface-subtle);
    border-color: var(--border-color);
    color: var(--text-heading) !important;
}

.mx-pill-btn.ghost:hover {
    border-color: var(--color-primary);
    color: var(--color-primary) !important;
    background: var(--color-primary-light);
}

/* ─── Treasury Summary Card (Right Rail) ───────────────────────────────── */
.mx-vault-card {
    position: relative;
    border-radius: 12px;
    padding: 1.1rem 1.2rem;
    color: #FFFFFF;
    background: linear-gradient(135deg, #0F3F22 0%, #14532D 58%, #059669 100%);
    border: 1px solid rgba(20, 83, 45, 0.35);
    overflow: hidden;
    box-shadow: 0 8px 20px -6px rgba(20, 83, 45, 0.28);
    min-height: 146px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

html.dark .mx-vault-card {
    background: linear-gradient(135deg, #0D1D14 0%, #14532D 55%, #059669 100%);
    border: 1px solid rgba(52, 211, 153, 0.28);
    box-shadow: 0 10px 24px -6px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.mx-vault-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.22);
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

html.dark .mx-vault-chip {
    background: rgba(16, 185, 129, 0.18);
    border-color: rgba(52, 211, 153, 0.35);
    color: #A7F3D0;
}

.mx-vault-amount {
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.025em;
    line-height: 1.15;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.14);
}

.mx-balance-bar-track {
    height: 7px;
    border-radius: 9999px;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.mx-balance-bar-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #14532D 0%, #059669 100%);
}

html.dark .mx-balance-bar-fill {
    background: linear-gradient(90deg, #059669 0%, #34D399 100%);
}

/* ─── Goal Arc Gauge Theme Stops ─────────────────────────────────────────── */
.mx-goal-stop-start { stop-color: #14532D; }
.mx-goal-stop-mid   { stop-color: #15803D; }
.mx-goal-stop-end   { stop-color: #059669; }

html.dark .mx-goal-stop-start { stop-color: #059669; }
html.dark .mx-goal-stop-mid   { stop-color: #10B981; }
html.dark .mx-goal-stop-end   { stop-color: #34D399; }

/* ─── Quick Advisor Avatar Strip ─────────────────────────────────────────── */
.mx-advisor-strip {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.mx-advisor-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    min-width: 56px;
    text-decoration: none !important;
    color: var(--text-body);
    transition: transform 0.15s ease;
}

.mx-advisor-chip:hover {
    transform: translateY(-2px);
    color: var(--color-primary);
}

.mx-advisor-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--color-primary-light);
    color: var(--color-primary);
    border: 2px solid var(--card-bg);
    box-shadow: 0 0 0 1.5px var(--color-primary-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: 800;
    overflow: hidden;
}

.mx-advisor-name {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-heading);
    max-width: 60px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── 2x2 Upcoming Maturities & Commissions Mini Cards ───────────────────── */
.mx-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

.mx-mini-card {
    padding: 0.8rem 0.85rem;
    border-radius: 13px;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    text-decoration: none !important;
    transition: border-color 0.15s ease, transform 0.15s ease;
}

.mx-mini-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-1px);
}

.mx-mini-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--color-copper);
}

.mx-mini-icon.green {
    color: var(--color-primary);
}

.mx-mini-title {
    font-size: 0.73rem;
    font-weight: 700;
    color: var(--text-heading);
    line-height: 1.2;
}

.mx-mini-val {
    font-size: 0.94rem;
    font-weight: 800;
    color: var(--text-heading);
    font-variant-numeric: tabular-nums;
}

.mx-mini-sub {
    font-size: 0.68rem;
    color: var(--text-muted);
}

/* ─── Recent Activity Feed ───────────────────────────────────────────────── */
.mx-activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mx-activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
}

.mx-activity-dot {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
    background: var(--color-copper-light);
    color: var(--color-copper);
    border: 1px solid var(--color-copper-border);
}

.mx-activity-dot.brand {
    background: var(--color-primary-light);
    color: var(--color-primary);
    border-color: var(--color-primary-border);
}

/* ─── Clean Ledger Table ─────────────────────────────────────────────────── */
.mx-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.mx-table thead th {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.055em;
    font-weight: 700;
    color: var(--text-muted);
    background: var(--surface-subtle);
    padding: 0.72rem 1rem;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
}

.mx-table tbody td {
    padding: 0.78rem 1rem;
    font-size: 0.81rem;
    color: var(--text-body);
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.mx-table tbody tr:last-child td {
    border-bottom: 0;
}

.mx-table tbody tr:hover td {
    background: var(--surface-subtle);
}

.mx-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--color-primary);
    background: var(--color-primary-light);
    border: 1px solid var(--color-primary-border);
    padding: 2px 7px;
    border-radius: 6px;
}

.mx-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--text-muted);
}

.mx-alert-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.8rem 1.15rem;
    border-radius: 14px;
    background: var(--status-pending-bg);
    border: 1px solid var(--status-pending-border);
    border-left: 4px solid var(--color-copper);
}

/* ─── Minimal Modern Date & Time Bar ─────────────────────────────────────── */
.mx-datetime-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1.25rem;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-lg, 14px);
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-card);
    animation: mxClockEntrance 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
    transition: border-color 0.18s ease;
}

.mx-datetime-strip:hover {
    border-color: var(--border-strong);
}

.mx-datetime-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.mx-analog-clock-wrap {
    width: 38px;
    height: 38px;
    border-radius: 9999px;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.mx-analog-clock-svg {
    width: 32px;
    height: 32px;
}

.mx-clock-hand-hour {
    stroke: var(--text-heading);
    stroke-width: 2;
    stroke-linecap: round;
    transform-box: view-box;
    transform-origin: 18px 18px;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.mx-clock-hand-min {
    stroke: var(--text-secondary);
    stroke-width: 1.5;
    stroke-linecap: round;
    transform-box: view-box;
    transform-origin: 18px 18px;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.mx-clock-hand-sec {
    stroke: var(--color-copper);
    stroke-width: 1.15;
    stroke-linecap: round;
    transform-box: view-box;
    transform-origin: 18px 18px;
    transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.mx-datetime-meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.mx-datetime-eyebrow {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted);
    letter-spacing: 0.01em;
}

.mx-datetime-full-date {
    font-size: 0.94rem;
    font-weight: 700;
    color: var(--text-heading);
    letter-spacing: -0.015em;
    line-height: 1.25;
}

.mx-datetime-right {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.mx-digital-clock {
    display: inline-flex;
    align-items: baseline;
    gap: 2px;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
}

.mx-clock-unit {
    display: inline-block;
    min-width: 1.28em;
    text-align: center;
    font-size: 1.32rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--text-heading);
}

.mx-clock-unit.sec {
    color: var(--color-primary);
    font-weight: 600;
}

.mx-clock-unit.is-ticking {
    animation: mxDigitSlide 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

.mx-clock-sep {
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--text-muted);
    padding: 0 1px;
    animation: mxColonBreathe 1.4s ease-in-out infinite;
}

.mx-clock-ampm {
    margin-left: 5px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.mx-clock-format-toggle {
    display: inline-flex;
    align-items: center;
    padding: 2px;
    border-radius: 8px;
    background: var(--surface-subtle);
    border: 1px solid var(--border-color);
    gap: 2px;
}

.mx-clock-fmt-btn {
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.68rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mx-clock-fmt-btn.active {
    background: var(--card-bg);
    color: var(--text-heading);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

@media (max-width: 575.98px) {
    .mx-datetime-strip {
        padding: 0.75rem 1rem;
    }
    .mx-datetime-right {
        width: 100%;
        justify-content: space-between;
    }
}

@keyframes mxClockEntrance {
    0% { opacity: 0; transform: translateY(-4px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes mxDigitSlide {
    0% { opacity: 0.45; transform: translateY(3px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes mxColonBreathe {
    0%, 100% { opacity: 0.85; }
    50% { opacity: 0.35; }
}

@media (prefers-reduced-motion: reduce) {
    .mx-datetime-strip,
    .mx-clock-unit.is-ticking,
    .mx-clock-sep,
    .mx-clock-hand-hour,
    .mx-clock-hand-min,
    .mx-clock-hand-sec {
        animation: none !important;
        transition: none !important;
    }
}
</style>
