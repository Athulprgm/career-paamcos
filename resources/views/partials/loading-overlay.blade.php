<!-- Global Fixed Form Loading Animation Overlay — Swiss Banking Design System -->
<div id="globalFormLoadingOverlay" class="global-loading-overlay" aria-hidden="true" role="status" aria-live="polite">
    <div class="global-loading-backdrop"></div>
    <div class="global-loading-card">
        <!-- Minimal Enterprise Spinner & Logo -->
        <div class="global-loading-spinner-wrap">
            <div class="global-loading-ring"></div>
            <div class="global-loading-logo-box">
                <img src="{{ asset(file_exists(public_path('logo-transparent.png')) ? 'logo-transparent.png' : 'logo.png') }}" alt="MultiCred" class="global-loading-logo" onerror="this.style.display='none'; document.getElementById('globalLoadingIconFallback').style.display='inline-flex';">
                <span id="globalLoadingIconFallback" class="global-loading-fallback-icon" style="display:none;">
                    <i class="bi bi-shield-check"></i>
                </span>
            </div>
        </div>

        <!-- Dynamic Title & Subtitle -->
        <h5 id="globalLoadingTitle" class="global-loading-title">Processing Request...</h5>
        <p id="globalLoadingSubtitle" class="global-loading-subtitle">Please wait while your data is securely processed and saved.</p>

        <!-- Flat Rectangular Progress Runner -->
        <div class="global-loading-progress-track">
            <div class="global-loading-progress-bar"></div>
        </div>

        <!-- Failsafe Cancel / Dismiss Option -->
        <button type="button" id="globalLoadingDismissBtn" class="global-loading-dismiss-btn" onclick="window.hideLoadingOverlay(true)">
            Taking longer than expected? Click to dismiss
        </button>
    </div>
</div>

<style>
/* ─── Global Fixed Form Loading Animation Overlay ────────────────────────── */
.global-loading-overlay {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 105000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease, visibility 0.15s;
    user-select: none;
    -webkit-user-select: none;
    pointer-events: all;
}

.global-loading-overlay.active {
    display: flex;
    opacity: 1;
    visibility: visible;
}

.global-loading-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.45);
    transition: opacity 0.15s ease;
}

.global-loading-card {
    position: relative;
    z-index: 2;
    background: var(--card-bg, #FFFFFF);
    width: 90%;
    max-width: 380px;
    padding: 28px 24px;
    border: 1px solid var(--border-color, #E2E8F0);
    border-radius: 14px !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.global-loading-spinner-wrap {
    position: relative;
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
}

.global-loading-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50% !important;
    border: 3px solid rgba(20, 83, 45, 0.16);
    border-top: 3px solid var(--color-primary, #14532D);
    border-right: 3px solid var(--color-copper, #C2410C);
    animation: globalSpinRing 0.9s cubic-bezier(0.55, 0.15, 0.45, 0.85) infinite;
}

html.dark .global-loading-ring {
    border-color: rgba(16, 185, 129, 0.2);
    border-top-color: #34D399;
    border-right-color: #FB923C;
}

@keyframes globalSpinRing {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.global-loading-logo-box {
    width: 48px;
    height: 48px;
    border-radius: 10px !important;
    background: var(--card-bg, #FFFFFF) !important;
    border: 1px solid var(--border-color, #E2E8E4) !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.global-loading-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.global-loading-fallback-icon {
    font-size: 1.5rem;
    color: var(--color-primary, #14532D);
}

.global-loading-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-heading, #0F172A);
    margin: 0 0 6px;
    letter-spacing: -0.015em;
    font-family: inherit;
}

.global-loading-subtitle {
    font-size: 0.8125rem;
    font-weight: 400;
    color: var(--text-muted, #64748B);
    line-height: 1.45;
    margin: 0 0 18px;
    font-family: inherit;
}

.global-loading-progress-track {
    width: 220px;
    max-width: 80%;
    height: 3px;
    background: var(--border-color, #E2E8E4);
    border-radius: 9999px !important;
    overflow: hidden;
    position: relative;
}

.global-loading-progress-bar {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 45%;
    border-radius: 9999px !important;
    background: linear-gradient(90deg, var(--color-primary, #14532D), var(--color-copper, #C2410C));
    animation: globalProgressShimmer 1.4s ease-in-out infinite;
}

@keyframes globalProgressShimmer {
    0% { left: -45%; width: 45%; }
    50% { left: 25%; width: 65%; }
    100% { left: 100%; width: 45%; }
}

.global-loading-dismiss-btn {
    display: none;
    margin-top: 14px;
    background: transparent;
    border: none;
    color: #64748B;
    font-size: 0.76rem;
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.1s ease;
    padding: 4px 8px;
}

.global-loading-dismiss-btn:hover {
    color: #0F172A;
}

.btn-loading-active {
    pointer-events: none !important;
    opacity: 0.82 !important;
    cursor: wait !important;
}
</style>

<script>
(function() {
    let activeForm = null;
    let activeSubmitButton = null;
    let originalButtonHtml = null;
    let dismissTimeoutTimer = null;
    let autoDismissTimer = null;
    let lastClickedSubmitButton = null;

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
        if (btn) {
            lastClickedSubmitButton = btn;
        }
    }, true);

    function getSubmitActionLabels(form, button) {
        let text = '';
        if (button) {
            text = (button.innerText || button.value || '').trim();
        }

        const action = (form.getAttribute('action') || '').toLowerCase();
        const lowerText = text.toLowerCase();

        let title = 'Processing Request...';
        let buttonLabel = 'Processing...';

        if (lowerText.includes('create') || lowerText.includes('add') || action.includes('/store') || action.includes('create')) {
            if (lowerText.includes('branch')) {
                title = 'Creating Branch...';
                buttonLabel = 'Creating Branch...';
            } else if (lowerText.includes('agent')) {
                title = 'Registering Agent...';
                buttonLabel = 'Registering Agent...';
            } else if (lowerText.includes('lead')) {
                title = 'Saving Lead...';
                buttonLabel = 'Saving Lead...';
            } else if (lowerText.includes('deposit')) {
                title = 'Creating Deposit...';
                buttonLabel = 'Saving Deposit...';
            } else if (lowerText.includes('rule')) {
                title = 'Saving Promotion Rule...';
                buttonLabel = 'Saving Rule...';
            } else if (lowerText.includes('scheme') || lowerText.includes('commission')) {
                title = 'Saving Scheme...';
                buttonLabel = 'Saving Scheme...';
            } else if (lowerText.includes('staff')) {
                title = 'Creating Staff...';
                buttonLabel = 'Creating Staff...';
            } else if (lowerText.includes('position')) {
                title = 'Creating Position...';
                buttonLabel = 'Creating Position...';
            } else if (lowerText.includes('rank')) {
                title = 'Creating Agent Rank...';
                buttonLabel = 'Creating Rank...';
            } else {
                title = 'Creating Record...';
                buttonLabel = 'Creating...';
            }
        } else if (lowerText.includes('save')) {
            if (lowerText.includes('lead')) {
                title = 'Saving Lead...';
                buttonLabel = 'Saving Lead...';
            } else if (lowerText.includes('deposit')) {
                title = 'Saving Deposit...';
                buttonLabel = 'Saving Deposit...';
            } else {
                title = 'Saving Details...';
                buttonLabel = 'Saving...';
            }
        } else if (lowerText.includes('update') || action.includes('/update')) {
            title = 'Updating Information...';
            buttonLabel = 'Updating...';
        } else if (lowerText.includes('register')) {
            title = 'Registering Details...';
            buttonLabel = 'Registering...';
        } else if (lowerText.includes('delete') || lowerText.includes('remove')) {
            title = 'Removing Record...';
            buttonLabel = 'Removing...';
        } else if (lowerText.includes('approve')) {
            title = 'Approving Request...';
            buttonLabel = 'Approving...';
        } else if (lowerText.includes('disburse') || lowerText.includes('pay')) {
            title = 'Processing Payment...';
            buttonLabel = 'Processing Payment...';
        } else if (lowerText.includes('sign in') || lowerText.includes('log in') || lowerText.includes('login')) {
            title = 'Signing In...';
            buttonLabel = 'Authenticating...';
        } else if (lowerText.includes('verify')) {
            title = 'Verifying Record...';
            buttonLabel = 'Verifying...';
        } else if (lowerText.includes('submit')) {
            title = 'Submitting Request...';
            buttonLabel = 'Submitting...';
        }

        return { title, buttonLabel };
    }

    window.showLoadingOverlay = function(customTitle, customSubtitle) {
        const overlay = document.getElementById('globalFormLoadingOverlay');
        const titleEl = document.getElementById('globalLoadingTitle');
        const subtitleEl = document.getElementById('globalLoadingSubtitle');
        const dismissBtn = document.getElementById('globalLoadingDismissBtn');

        if (!overlay) return;

        if (customTitle && titleEl) {
            titleEl.textContent = customTitle;
        }
        if (customSubtitle && subtitleEl) {
            subtitleEl.textContent = customSubtitle;
        }

        if (dismissBtn) {
            dismissBtn.style.display = 'none';
        }

        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');

        if (dismissTimeoutTimer) clearTimeout(dismissTimeoutTimer);
        if (autoDismissTimer) clearTimeout(autoDismissTimer);

        dismissTimeoutTimer = setTimeout(function() {
            if (dismissBtn && overlay.classList.contains('active')) {
                dismissBtn.style.display = 'inline-block';
            }
        }, 10000);

        autoDismissTimer = setTimeout(function() {
            window.hideLoadingOverlay(false);
        }, 30000);
    };

    window.hideLoadingOverlay = function() {
        const overlay = document.getElementById('globalFormLoadingOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');
        }

        if (dismissTimeoutTimer) clearTimeout(dismissTimeoutTimer);
        if (autoDismissTimer) clearTimeout(autoDismissTimer);

        if (activeSubmitButton && originalButtonHtml !== null) {
            activeSubmitButton.innerHTML = originalButtonHtml;
            activeSubmitButton.classList.remove('btn-loading-active');
            activeSubmitButton = null;
            originalButtonHtml = null;
        }

        if (activeForm) {
            activeForm.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(b) {
                b.classList.remove('btn-loading-active');
            });

            const injected = activeForm.querySelector('input[data-loading-injected="true"]');
            if (injected) injected.remove();

            activeForm = null;
        }

        lastClickedSubmitButton = null;
    };

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.nodeType !== 1) return;

        if (e.defaultPrevented) return;

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const hasExplicitLoading = form.getAttribute('data-loading') === 'true';
        const hasExplicitOptOut = form.getAttribute('data-loading') === 'false' || form.hasAttribute('data-no-loading');

        if (hasExplicitOptOut) return;
        if (method === 'GET' && !hasExplicitLoading) return;
        if (form.getAttribute('target') === '_blank') return;

        if (form.hasAttribute('data-confirm') && form.getAttribute('data-confirmed') !== 'true') {
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        let submitBtn = lastClickedSubmitButton;
        if (!submitBtn || !form.contains(submitBtn)) {
            submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        }

        const labels = getSubmitActionLabels(form, submitBtn);

        if (submitBtn && submitBtn.name && submitBtn.value) {
            let existingHidden = form.querySelector(`input[type="hidden"][name="${submitBtn.name}"]`);
            if (!existingHidden) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = submitBtn.name;
                hidden.value = submitBtn.value;
                hidden.setAttribute('data-loading-injected', 'true');
                form.appendChild(hidden);
            } else {
                existingHidden.value = submitBtn.value;
            }
        }

        if (submitBtn) {
            activeSubmitButton = submitBtn;
            originalButtonHtml = submitBtn.innerHTML;
            submitBtn.classList.add('btn-loading-active');
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 12px; height: 12px; border-width: 2px;"></span>${labels.buttonLabel}`;
        }

        activeForm = form;
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(b) {
            if (b !== submitBtn) {
                b.classList.add('btn-loading-active');
            }
        });

        window.showLoadingOverlay(labels.title, 'Please wait while your data is securely processed and saved.');
    }, false);

    window.addEventListener('pageshow', function() {
        window.hideLoadingOverlay();
    });

    window.addEventListener('popstate', function() {
        window.hideLoadingOverlay();
    });
})();
</script>
