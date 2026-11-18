<!-- Fixed Toast Popup Notification System — Swiss Banking Design System -->
<div id="toast-container" class="toast-popup-container" aria-live="polite" aria-atomic="true">
    @if(session('success'))
        <div class="toast-popup toast-popup-success" role="alert" data-auto-dismiss="5000">
            <div class="toast-popup-icon">
                <i class="bi bi-check2"></i>
            </div>
            <div class="toast-popup-content">
                <div class="toast-popup-title">Operation Successful</div>
                <div class="toast-popup-message">{{ session('success') }}</div>
            </div>
            <button type="button" class="toast-popup-close" onclick="dismissToast(this.closest('.toast-popup'))" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-popup-progress">
                <div class="toast-popup-progress-bar"></div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="toast-popup toast-popup-error" role="alert" data-auto-dismiss="6000">
            <div class="toast-popup-icon">
                <i class="bi bi-exclamation"></i>
            </div>
            <div class="toast-popup-content">
                <div class="toast-popup-title">System Alert / Error</div>
                <div class="toast-popup-message">{{ session('error') }}</div>
            </div>
            <button type="button" class="toast-popup-close" onclick="dismissToast(this.closest('.toast-popup'))" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-popup-progress">
                <div class="toast-popup-progress-bar"></div>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="toast-popup toast-popup-warning" role="alert" data-auto-dismiss="5000">
            <div class="toast-popup-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="toast-popup-content">
                <div class="toast-popup-title">Attention Required</div>
                <div class="toast-popup-message">{{ session('warning') }}</div>
            </div>
            <button type="button" class="toast-popup-close" onclick="dismissToast(this.closest('.toast-popup'))" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-popup-progress">
                <div class="toast-popup-progress-bar"></div>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="toast-popup toast-popup-info" role="alert" data-auto-dismiss="5000">
            <div class="toast-popup-icon">
                <i class="bi bi-info"></i>
            </div>
            <div class="toast-popup-content">
                <div class="toast-popup-title">Notice</div>
                <div class="toast-popup-message">{{ session('info') }}</div>
            </div>
            <button type="button" class="toast-popup-close" onclick="dismissToast(this.closest('.toast-popup'))" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-popup-progress">
                <div class="toast-popup-progress-bar"></div>
            </div>
        </div>
    @endif

    @if(session('status'))
        <div class="toast-popup toast-popup-info" role="alert" data-auto-dismiss="5000">
            <div class="toast-popup-icon">
                <i class="bi bi-info"></i>
            </div>
            <div class="toast-popup-content">
                <div class="toast-popup-title">Status Update</div>
                <div class="toast-popup-message">{{ session('status') }}</div>
            </div>
            <button type="button" class="toast-popup-close" onclick="dismissToast(this.closest('.toast-popup'))" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-popup-progress">
                <div class="toast-popup-progress-bar"></div>
            </div>
        </div>
    @endif
</div>

<style>
/* ─── Sharp Enterprise Toast Popup Styles ────────────────────────────────── */
.toast-popup-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100000;
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 380px;
    max-width: calc(100% - 32px);
    pointer-events: none;
}

.toast-popup {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px 14px 14px;
    background: var(--card-bg, #FFFFFF);
    border: 1px solid var(--border-color, #E2E8E4);
    border-left: 3px solid var(--color-primary, #14532D);
    border-radius: 10px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    pointer-events: auto;
    animation: toastSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.toast-popup.toast-popup-hiding {
    animation: toastSlideOut 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes toastSlideIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes toastSlideOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-8px);
    }
}

.toast-popup-success {
    border-left-color: #059669;
}
.toast-popup-success .toast-popup-icon {
    background: #ECFDF5;
    color: #065F46;
    border: 1px solid #A7F3D0;
    border-radius: 8px;
}
.toast-popup-success .toast-popup-progress-bar {
    background: #059669;
}

html.dark .toast-popup {
    background: var(--card-bg, #111A15);
    border-color: var(--border-color, #1E2C24);
}
html.dark .toast-popup-title {
    color: var(--text-heading, #F8FAFC);
}
html.dark .toast-popup-message {
    color: var(--text-muted, #94A3B8);
}
html.dark .toast-popup-close {
    color: var(--text-muted, #94A3B8);
}
html.dark .toast-popup-close:hover {
    color: var(--text-heading, #F8FAFC);
}
html.dark .toast-popup-success {
    border-left-color: #10B981;
}
html.dark .toast-popup-success .toast-popup-icon {
    background: rgba(16, 185, 129, 0.16);
    color: #34D399;
    border: 1px solid rgba(52, 211, 153, 0.32);
}
html.dark .toast-popup-success .toast-popup-progress-bar {
    background: #10B981;
}

.toast-popup-error {
    border-left-color: #DC2626;
}
.toast-popup-error .toast-popup-icon {
    background: #FEF2F2;
    color: #991B1B;
    border: 1px solid #FECACA;
}
.toast-popup-error .toast-popup-progress-bar {
    background: #DC2626;
}
html.dark .toast-popup-error {
    border-left-color: #F87171;
}
html.dark .toast-popup-error .toast-popup-icon {
    background: rgba(239, 68, 68, 0.16);
    color: #FCA5A5;
    border-color: rgba(239, 68, 68, 0.32);
}
html.dark .toast-popup-error .toast-popup-progress-bar {
    background: #F87171;
}

.toast-popup-warning {
    border-left-color: #D97706;
}
.toast-popup-warning .toast-popup-icon {
    background: #FFFBEB;
    color: #92400E;
    border: 1px solid #FDE68A;
}
.toast-popup-warning .toast-popup-progress-bar {
    background: #D97706;
}
html.dark .toast-popup-warning {
    border-left-color: #FBBF24;
}
html.dark .toast-popup-warning .toast-popup-icon {
    background: rgba(245, 158, 11, 0.16);
    color: #FCD34D;
    border-color: rgba(245, 158, 11, 0.32);
}
html.dark .toast-popup-warning .toast-popup-progress-bar {
    background: #FBBF24;
}

.toast-popup-info {
    border-left-color: #2563EB;
}
.toast-popup-info .toast-popup-icon {
    background: #EFF6FF;
    color: #1E40AF;
    border: 1px solid #BFDBFE;
}
.toast-popup-info .toast-popup-progress-bar {
    background: #2563EB;
}
html.dark .toast-popup-info {
    border-left-color: #60A5FA;
}
html.dark .toast-popup-info .toast-popup-icon {
    background: rgba(59, 130, 246, 0.16);
    color: #93C5FD;
    border-color: rgba(59, 130, 246, 0.32);
}
html.dark .toast-popup-info .toast-popup-progress-bar {
    background: #60A5FA;
}

.toast-popup-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}

.toast-popup-content {
    flex: 1;
    min-width: 0;
}

.toast-popup-title {
    font-size: 0.825rem;
    font-weight: 700;
    color: var(--text-heading, #0F172A);
    line-height: 1.25;
    margin-bottom: 2px;
    letter-spacing: -0.01em;
    text-transform: uppercase;
}

.toast-popup-message {
    font-size: 0.8125rem;
    color: var(--text-body, #334155);
    line-height: 1.45;
    word-break: break-word;
}

.toast-popup-close {
    background: transparent;
    border: none;
    color: var(--text-muted, #64748B);
    padding: 2px 4px;
    font-size: 1.1rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.1s ease;
    flex-shrink: 0;
    margin-left: 4px;
}

.toast-popup-close:hover {
    color: var(--text-heading, #0F172A);
}

.toast-popup-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(0, 0, 0, 0.06);
}

.toast-popup-progress-bar {
    height: 100%;
    width: 100%;
    transform-origin: left;
    transition: width linear;
}

@media (max-width: 576px) {
    .toast-popup-container {
        top: 12px;
        right: 12px;
        left: 12px;
        width: auto;
        max-width: none;
    }
}
</style>

<script>
function dismissToast(toastEl) {
    if (!toastEl || toastEl.classList.contains('toast-popup-hiding')) return;
    toastEl.classList.add('toast-popup-hiding');
    setTimeout(() => {
        if (toastEl.parentNode) {
            toastEl.parentNode.removeChild(toastEl);
        }
    }, 220);
}

function initToast(toastEl) {
    const duration = parseInt(toastEl.getAttribute('data-auto-dismiss') || '5000', 10);
    const progressBar = toastEl.querySelector('.toast-popup-progress-bar');
    let startTime = Date.now();
    let remainingTime = duration;
    let timerId = null;
    let isPaused = false;

    function startTimer() {
        if (progressBar) {
            progressBar.style.transition = `width ${remainingTime}ms linear`;
            progressBar.style.width = '0%';
        }
        startTime = Date.now();
        timerId = setTimeout(() => {
            dismissToast(toastEl);
        }, remainingTime);
    }

    function pauseTimer() {
        if (isPaused) return;
        isPaused = true;
        clearTimeout(timerId);
        const elapsed = Date.now() - startTime;
        remainingTime = Math.max(remainingTime - elapsed, 200);
        if (progressBar) {
            const computedWidth = getComputedStyle(progressBar).width;
            progressBar.style.transition = 'none';
            progressBar.style.width = computedWidth;
        }
    }

    function resumeTimer() {
        if (!isPaused) return;
        isPaused = false;
        startTimer();
    }

    toastEl.addEventListener('mouseenter', pauseTimer);
    toastEl.addEventListener('mouseleave', resumeTimer);

    startTimer();
}

window.showToast = function(message, type = 'success', title = null, duration = 5000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-popup-container';
        document.body.appendChild(container);
    }

    const typeConfig = {
        success: { title: title || 'Operation Successful', icon: 'bi-check2', class: 'toast-popup-success' },
        error: { title: title || 'System Alert / Error', icon: 'bi-exclamation', class: 'toast-popup-error' },
        danger: { title: title || 'System Alert / Error', icon: 'bi-exclamation', class: 'toast-popup-error' },
        warning: { title: title || 'Attention Required', icon: 'bi-exclamation-triangle', class: 'toast-popup-warning' },
        info: { title: title || 'Notice', icon: 'bi-info', class: 'toast-popup-info' },
    };

    const cfg = typeConfig[type] || typeConfig.info;

    const toast = document.createElement('div');
    toast.className = `toast-popup ${cfg.class}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('data-auto-dismiss', duration);

    toast.innerHTML = `
        <div class="toast-popup-icon"><i class="bi ${cfg.icon}"></i></div>
        <div class="toast-popup-content">
            <div class="toast-popup-title">${cfg.title}</div>
            <div class="toast-popup-message">${message}</div>
        </div>
        <button type="button" class="toast-popup-close" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
        <div class="toast-popup-progress">
            <div class="toast-popup-progress-bar"></div>
        </div>
    `;

    toast.querySelector('.toast-popup-close').addEventListener('click', () => dismissToast(toast));
    container.appendChild(toast);
    initToast(toast);
};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toast-popup').forEach(initToast);
});
</script>
