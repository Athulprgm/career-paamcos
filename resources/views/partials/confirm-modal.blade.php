<!-- Global Fixed Confirmation Popup Modal — Swiss Banking Design System -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content custom-confirm-card border">
            <div class="modal-header pb-2 pt-4 px-4 d-flex justify-content-between align-items-start border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div id="globalConfirmModalIconContainer" class="custom-confirm-icon-box type-danger">
                        <i id="globalConfirmModalIcon" class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="globalConfirmModalTitle" style="font-size: 1.05rem; letter-spacing: -0.01em;">
                            Confirm Action
                        </h5>
                        <p class="text-muted small mb-0" id="globalConfirmModalSubtitle" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;">
                            Please confirm to proceed
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="custom-confirm-message-box p-3 mb-1">
                    <p class="mb-0 text-dark" id="globalConfirmModalMessage" style="font-size: 0.875rem; line-height: 1.5; font-weight: 500;">
                        Are you sure you want to proceed?
                    </p>
                </div>
            </div>

            <div class="modal-footer pt-2 pb-3 px-4 d-flex gap-2 justify-content-end bg-light border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 custom-confirm-btn" data-bs-dismiss="modal" id="globalConfirmCancelBtn">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger btn-sm px-3 custom-confirm-btn d-flex align-items-center gap-1" id="globalConfirmSubmitBtn">
                    <span id="globalConfirmSubmitBtnText">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ─── Global Enterprise Confirmation Modal Styles ────────────────────────── */
#globalConfirmModal .modal-dialog {
    margin: 1.75rem auto;
}
@media (max-width: 576px) {
    #globalConfirmModal .modal-dialog {
        margin: 0.5rem auto !important;
        max-width: calc(100% - 1rem) !important;
        width: auto !important;
    }
}

.custom-confirm-card {
    border-radius: 14px !important;
    background: var(--card-bg, #FFFFFF);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid var(--border-color, #E2E8F0) !important;
    overflow: hidden;
}

.custom-confirm-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.custom-confirm-icon-box.type-danger {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

.custom-confirm-icon-box.type-warning {
    background: #FFFBEB;
    color: #D97706;
    border: 1px solid #FDE68A;
}

.custom-confirm-icon-box.type-success {
    background: #ECFDF5;
    color: #065F46;
    border: 1px solid #A7F3D0;
}

.custom-confirm-icon-box.type-info {
    background: #EFF6FF;
    color: #2563EB;
    border: 1px solid #BFDBFE;
}

html.dark .custom-confirm-icon-box.type-success {
    background: rgba(16, 185, 129, 0.16);
    color: #34D399;
    border: 1px solid rgba(52, 211, 153, 0.35);
}

html.dark .custom-confirm-icon-box.type-info {
    background: rgba(59, 130, 246, 0.16);
    color: #60A5FA;
    border-color: rgba(59, 130, 246, 0.32);
}

html.dark .custom-confirm-icon-box.type-danger {
    background: rgba(240, 120, 120, 0.15);
    color: #F07878;
    border-color: rgba(240, 120, 120, 0.3);
}

html.dark .custom-confirm-icon-box.type-warning {
    background: rgba(229, 180, 92, 0.15);
    color: #E5B45C;
    border-color: rgba(229, 180, 92, 0.3);
}

.custom-confirm-message-box {
    background: var(--surface-subtle, #F8FAFC);
    border: 1px solid var(--border-color, #E2E8F0);
    border-radius: 8px !important;
    color: var(--text-body);
}

#globalConfirmModal .modal-header {
    background: var(--card-bg, #FFFFFF);
    border-color: var(--border-color, #E2E8F0) !important;
}

#globalConfirmModal .modal-footer {
    background: var(--surface-subtle, #F8FAFC) !important;
    border-color: var(--border-color, #E2E8F0) !important;
}

.custom-confirm-btn {
    border-radius: 8px !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    font-weight: 600 !important;
    font-size: 0.8125rem !important;
    padding: 6px 16px !important;
    min-width: 84px;
    transition: all 0.1s ease;
}
</style>

<script>
(function() {
    let confirmModalInstance = null;
    let pendingConfirmCallback = null;
    let pendingCancelCallback = null;

    function getModalInstance() {
        const el = document.getElementById('globalConfirmModal');
        if (!el) return null;
        if (!confirmModalInstance && window.bootstrap && window.bootstrap.Modal) {
            confirmModalInstance = new bootstrap.Modal(el, {
                backdrop: true,
                keyboard: true
            });
            el.addEventListener('hidden.bs.modal', function() {
                if (pendingCancelCallback) {
                    const cb = pendingCancelCallback;
                    pendingCancelCallback = null;
                    pendingConfirmCallback = null;
                    cb();
                }
            });
        }
        return confirmModalInstance;
    }

    function detectActionMeta(message, explicitType) {
        let type = explicitType;
        const lower = (message || '').toLowerCase();

        if (!type) {
            if (lower.includes('delete') || lower.includes('remove') || lower.includes('deactivate') || lower.includes('reject') || lower.includes('cannot be undone') || lower.includes('cannot be reversed')) {
                type = 'danger';
            } else if (lower.includes('activate') || lower.includes('approve') || lower.includes('disbursement') || lower.includes('mark ready') || lower.includes('verify')) {
                type = 'success';
            } else if (lower.includes('mature') || lower.includes('payable') || lower.includes('warning')) {
                type = 'warning';
            } else {
                type = 'info';
            }
        }

        let title = 'Confirm Action';
        let btnText = 'Confirm';
        let iconClass = 'bi-question-circle';
        let btnClass = 'btn-primary';

        if (type === 'danger') {
            iconClass = lower.includes('delete') ? 'bi-trash3' : 'bi-exclamation-triangle';
            btnClass = 'btn-danger';
            if (lower.includes('deactivate')) {
                title = 'Confirm Deactivation';
                btnText = 'Deactivate';
            } else if (lower.includes('delete')) {
                title = 'Confirm Deletion';
                btnText = 'Delete';
            } else if (lower.includes('remove')) {
                title = 'Confirm Removal';
                btnText = 'Remove';
            } else {
                title = 'Confirm Action';
                btnText = 'Proceed';
            }
        } else if (type === 'success') {
            iconClass = 'bi-check2';
            btnClass = 'btn-success';
            if (lower.includes('activate')) {
                title = 'Confirm Activation';
                btnText = 'Activate';
            } else if (lower.includes('approve')) {
                title = 'Confirm Approval';
                btnText = 'Approve';
            } else if (lower.includes('disbursement')) {
                title = 'Confirm Disbursement';
                btnText = 'Disburse';
            } else {
                title = 'Confirm Action';
                btnText = 'Confirm';
            }
        } else if (type === 'warning') {
            iconClass = 'bi-exclamation-circle';
            btnClass = 'btn-warning text-dark';
            btnText = 'Confirm';
        } else {
            iconClass = 'bi-question-circle';
            btnClass = 'btn-primary';
            btnText = 'Confirm';
        }

        return { type, title, btnText, iconClass, btnClass };
    }

    window.confirmDialog = function(options) {
        return new Promise(function(resolve) {
            const modalEl = document.getElementById('globalConfirmModal');
            if (!modalEl) {
                const res = window.confirm(options.message || 'Are you sure?');
                resolve(res);
                return;
            }

            const modal = getModalInstance();
            if (!modal) {
                const res = window.confirm(options.message || 'Are you sure?');
                resolve(res);
                return;
            }

            const meta = detectActionMeta(options.message, options.type);
            const finalTitle = options.title || meta.title;
            const finalBtnText = options.confirmText || meta.btnText;
            const finalSubtitle = options.subtitle || 'Please confirm to proceed';

            document.getElementById('globalConfirmModalTitle').textContent = finalTitle;
            document.getElementById('globalConfirmModalSubtitle').textContent = finalSubtitle;
            document.getElementById('globalConfirmModalMessage').textContent = options.message || 'Are you sure you want to proceed?';

            const iconContainer = document.getElementById('globalConfirmModalIconContainer');
            iconContainer.className = 'custom-confirm-icon-box type-' + meta.type;

            const iconEl = document.getElementById('globalConfirmModalIcon');
            iconEl.className = 'bi ' + (options.icon || meta.iconClass);

            const submitBtn = document.getElementById('globalConfirmSubmitBtn');
            submitBtn.className = 'btn btn-sm px-3 custom-confirm-btn d-flex align-items-center gap-1 ' + (options.btnClass || meta.btnClass);
            document.getElementById('globalConfirmSubmitBtnText').textContent = finalBtnText;

            const cancelBtn = document.getElementById('globalConfirmCancelBtn');
            cancelBtn.textContent = options.cancelText || 'Cancel';

            pendingConfirmCallback = function() {
                pendingCancelCallback = null;
                pendingConfirmCallback = null;
                modal.hide();
                resolve(true);
            };

            pendingCancelCallback = function() {
                resolve(false);
            };

            submitBtn.onclick = function() {
                if (pendingConfirmCallback) {
                    pendingConfirmCallback();
                }
            };

            modal.show();
        });
    };

    function extractConfirmMessage(attrStr) {
        if (!attrStr) return 'Are you sure you want to proceed?';
        const regex = /confirm\(\s*(['"`])((?:\\.|[^\\])*?)\1\s*\)/s;
        const match = attrStr.match(regex);
        if (match && match[2]) {
            return match[2].replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }
        return 'Are you sure you want to proceed?';
    }

    function handleFormConfirmation(form, message, metaOverrides) {
        window.confirmDialog({
            message: message,
            title: metaOverrides.title || form.getAttribute('data-confirm-title'),
            type: metaOverrides.type || form.getAttribute('data-confirm-type'),
            confirmText: metaOverrides.btnText || form.getAttribute('data-confirm-btn'),
            subtitle: form.getAttribute('data-confirm-subtitle')
        }).then(function(confirmed) {
            if (confirmed) {
                form.setAttribute('data-confirmed', 'true');
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    if (typeof window.showLoadingOverlay === 'function') {
                        window.showLoadingOverlay();
                    }
                    HTMLFormElement.prototype.submit.call(form);
                }
            }
        });
    }

    function handleElementConfirmation(el, message, metaOverrides) {
        window.confirmDialog({
            message: message,
            title: metaOverrides.title || el.getAttribute('data-confirm-title'),
            type: metaOverrides.type || el.getAttribute('data-confirm-type'),
            confirmText: metaOverrides.btnText || el.getAttribute('data-confirm-btn'),
            subtitle: el.getAttribute('data-confirm-subtitle')
        }).then(function(confirmed) {
            if (confirmed) {
                el.setAttribute('data-confirmed', 'true');
                if (el.tagName === 'A' && el.href) {
                    if (typeof window.showLoadingOverlay === 'function') {
                        window.showLoadingOverlay('Processing Request...', 'Please wait while your request is processed.');
                    }
                    window.location.href = el.href;
                } else if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                    el.click();
                }
            }
        });
    }

    function upgradeConfirmAttributes() {
        document.querySelectorAll('form').forEach(function(form) {
            const onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                const msg = extractConfirmMessage(onsubmitAttr);
                form.setAttribute('data-confirm', msg);
                form.removeAttribute('onsubmit');
                form.onsubmit = null;
            }
        });

        document.querySelectorAll('button, a, input[type="submit"], input[type="button"]').forEach(function(el) {
            const onclickAttr = el.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes('confirm(')) {
                const msg = extractConfirmMessage(onclickAttr);
                el.setAttribute('data-confirm', msg);
                el.removeAttribute('onclick');
                el.onclick = null;
            }
        });
    }

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.nodeType !== 1) return;

        if (form.getAttribute('data-confirmed') === 'true') {
            form.removeAttribute('data-confirmed');
            return;
        }

        const onsubmitAttr = form.getAttribute('onsubmit');
        if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const msg = extractConfirmMessage(onsubmitAttr);
            form.removeAttribute('onsubmit');
            form.onsubmit = null;
            form.setAttribute('data-confirm', msg);
            handleFormConfirmation(form, msg, {});
            return;
        }

        if (form.hasAttribute('data-confirm')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const msg = form.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            handleFormConfirmation(form, msg, {});
        }
    }, true);

    document.addEventListener('click', function(e) {
        const submitBtn = e.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
        if (submitBtn) {
            const form = submitBtn.form || submitBtn.closest('form');
            if (form) {
                if (form.getAttribute('data-confirmed') === 'true') {
                    return;
                }
                const onsubmitAttr = form.getAttribute('onsubmit');
                if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const msg = extractConfirmMessage(onsubmitAttr);
                    form.removeAttribute('onsubmit');
                    form.onsubmit = null;
                    form.setAttribute('data-confirm', msg);
                    handleFormConfirmation(form, msg, {});
                    return;
                }
                if (form.hasAttribute('data-confirm')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const msg = form.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                    handleFormConfirmation(form, msg, {});
                    return;
                }
            }
        }

        const trigger = e.target.closest('[data-confirm], button[onclick*="confirm("], a[onclick*="confirm("]');
        if (trigger) {
            if (trigger.getAttribute('data-confirmed') === 'true') {
                trigger.removeAttribute('data-confirmed');
                return;
            }

            const onclickAttr = trigger.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes('confirm(')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const msg = extractConfirmMessage(onclickAttr);
                trigger.removeAttribute('onclick');
                trigger.onclick = null;
                trigger.setAttribute('data-confirm', msg);
                handleElementConfirmation(trigger, msg, {});
                return;
            }

            if (trigger.hasAttribute('data-confirm')) {
                const form = trigger.closest('form');
                if (form && (trigger.type === 'submit' || trigger.tagName === 'BUTTON')) {
                    return;
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                const msg = trigger.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                handleElementConfirmation(trigger, msg, {});
            }
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function() {
        upgradeConfirmAttributes();

        if (window.MutationObserver) {
            const observer = new MutationObserver(function() {
                upgradeConfirmAttributes();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
})();
</script>
