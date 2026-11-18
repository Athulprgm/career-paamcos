{{-- Reusable Searchable Select Component (Styles & Script) --}}
@once
<style>
.searchable-select-wrapper {
    position: relative;
    width: 100%;
}

.searchable-select-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 38px;
    padding: 0.425rem 0.75rem;
    font-size: 0.925rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--text-body, #1E293B);
    background-color: var(--card-bg, #ffffff);
    border: 1px solid var(--border-color, #E2E8F0);
    border-radius: 0.375rem;
    text-align: left;
    cursor: pointer;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out, background-color .15s ease;
}

.searchable-select-btn:hover {
    border-color: var(--color-primary, #14532D);
    background-color: var(--surface-subtle, #F8FAF9);
}

.searchable-select-btn:focus,
.searchable-select-btn.is-active {
    outline: 0;
    border-color: var(--color-primary, #14532D);
    box-shadow: var(--focus-ring, 0 0 0 3px rgba(20, 83, 45, 0.18));
    background-color: var(--card-bg, #ffffff);
}

.searchable-select-btn.is-invalid {
    border-color: #DC4545 !important;
    box-shadow: 0 0 0 0.25rem rgba(220, 69, 69, 0.15) !important;
}

.searchable-select-btn .btn-arrow {
    transition: transform .2s ease;
    color: var(--text-muted, #64748B);
    font-size: 0.8rem;
    margin-left: 0.5rem;
    flex-shrink: 0;
}

.searchable-select-btn.is-active .btn-arrow {
    transform: rotate(180deg);
    color: var(--color-primary, #14532D);
}

.searchable-select-btn .searchable-select-placeholder {
    color: var(--text-subtle, #94A3B8);
}

.searchable-select-menu {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 1055;
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-color, #E2E8E4);
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    padding: 0.5rem;
    display: none;
    animation: searchableSelectFade .15s ease-out;
}

@keyframes searchableSelectFade {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

.searchable-select-search-box {
    position: relative;
    margin-bottom: 0.5rem;
}

.searchable-select-search-box input {
    width: 100%;
    padding: 0.4rem 2rem 0.4rem 2.15rem;
    font-size: 0.875rem;
    border: 1px solid var(--border-color, #E2E8E4);
    border-radius: 0.375rem;
    background-color: var(--surface-subtle, #F8FAF9);
    color: var(--text-heading, #0F172A);
}

.searchable-select-search-box input:focus {
    background-color: var(--card-bg, #ffffff);
    border-color: var(--color-primary, #14532D);
    outline: none;
    box-shadow: var(--focus-ring, 0 0 0 3px rgba(20, 83, 45, 0.18));
}

.searchable-select-search-box .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-subtle, #94A3B8);
    pointer-events: none;
    font-size: 0.85rem;
}

.searchable-select-search-box .clear-search-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-subtle, #94A3B8);
    cursor: pointer;
    padding: 0.2rem 0.35rem;
    font-size: 0.8rem;
    line-height: 1;
    display: none;
    border-radius: 4px;
}

.searchable-select-search-box .clear-search-btn:hover {
    color: var(--text-heading, #0F172A);
    background-color: var(--border-color, #E2E8E4);
}

.searchable-select-list {
    max-height: 220px;
    overflow-y: auto;
    padding-right: 2px;
}

.searchable-select-list::-webkit-scrollbar {
    width: 6px;
}
.searchable-select-list::-webkit-scrollbar-thumb {
    background-color: #CBD5E1;
    border-radius: 4px;
}

.searchable-select-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.65rem;
    margin-bottom: 2px;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color .12s ease;
    user-select: none;
    color: var(--text-body, #1E293B);
}

.searchable-select-option:hover,
.searchable-select-option.is-focused {
    background-color: var(--surface-subtle, #F8FAF9);
}

.searchable-select-option.is-selected {
    background-color: var(--color-primary-light, #ECFDF5);
    color: var(--color-primary, #14532D);
    font-weight: 600;
}

.searchable-select-option.is-selected .check-icon {
    color: var(--color-primary, #14532D);
    font-size: 0.95rem;
    font-weight: bold;
    display: inline-block;
}

.searchable-select-option .check-icon {
    display: none;
    margin-left: 0.5rem;
}

.searchable-select-option .option-badge {
    font-size: 0.725rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    padding: 0.15rem 0.45rem;
    background: var(--surface-subtle, #F8FAF9);
    color: var(--text-muted, #64748B);
    border: 1px solid var(--border-color, #E2E8E4);
    border-radius: 0.25rem;
    margin-left: 0.5rem;
    flex-shrink: 0;
}

.searchable-select-option.is-selected .option-badge {
    background: var(--color-primary-light, #ECFDF5);
    color: var(--color-primary, #14532D);
    border-color: var(--color-primary-border, #A7F3D0);
}

.searchable-select-empty {
    text-align: center;
    padding: 1.25rem 0.5rem;
    color: var(--text-muted, #64748B);
    font-size: 0.85rem;
    display: none;
}

/* Dark Mode Tokens */
html.dark .searchable-select-btn {
    background-color: var(--card-bg, #111A15);
    border-color: var(--border-color, #1E2C24);
    color: var(--text-heading, #F8FAFC);
}

html.dark .searchable-select-btn:hover {
    border-color: var(--color-accent, #34D399);
    background-color: var(--surface-subtle, #18241E);
}

html.dark .searchable-select-btn:focus,
html.dark .searchable-select-btn.is-active {
    border-color: var(--color-accent, #34D399);
    box-shadow: var(--focus-ring, 0 0 0 3px rgba(16, 185, 129, 0.28));
    background-color: var(--surface-subtle, #18241E);
}

html.dark .searchable-select-btn.is-invalid {
    border-color: #F07878 !important;
    box-shadow: 0 0 0 0.25rem rgba(240, 120, 120, 0.2) !important;
}

html.dark .searchable-select-btn .btn-arrow {
    color: var(--text-muted, #94A3B8);
}

html.dark .searchable-select-btn.is-active .btn-arrow {
    color: var(--color-accent, #34D399);
}

html.dark .searchable-select-btn .searchable-select-placeholder {
    color: var(--text-subtle, #64748B);
}

html.dark .searchable-select-menu {
    background: var(--card-bg, #111A15);
    border-color: var(--border-color, #1E2C24);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.55);
}

html.dark .searchable-select-search-box input {
    background-color: var(--surface-subtle, #18241E);
    border-color: var(--border-color, #1E2C24);
    color: var(--text-heading, #F8FAFC);
}

html.dark .searchable-select-search-box input:focus {
    background-color: var(--surface-subtle, #18241E);
    border-color: var(--color-accent, #34D399);
    box-shadow: var(--focus-ring, 0 0 0 3px rgba(16, 185, 129, 0.28));
}

html.dark .searchable-select-search-box .search-icon {
    color: var(--text-subtle, #64748B);
}

html.dark .searchable-select-search-box .clear-search-btn {
    color: var(--text-muted, #94A3B8);
}

html.dark .searchable-select-search-box .clear-search-btn:hover {
    color: var(--text-heading, #F8FAFC);
    background-color: var(--surface-elevated, #1F2E26);
}

html.dark .searchable-select-list::-webkit-scrollbar-thumb {
    background-color: var(--border-strong, #31453A);
}

html.dark .searchable-select-option {
    color: var(--text-body, #E2E8F0);
}

html.dark .searchable-select-option:hover,
html.dark .searchable-select-option.is-focused {
    background-color: var(--surface-elevated, #1F2E26);
}

html.dark .searchable-select-option.is-selected {
    background-color: rgba(16, 185, 129, 0.16);
    color: var(--color-accent, #34D399);
}

html.dark .searchable-select-option.is-selected .check-icon {
    color: var(--color-accent, #34D399);
}

html.dark .searchable-select-option .option-badge {
    background: var(--surface-subtle, #18241E);
    color: var(--text-muted, #94A3B8);
    border-color: var(--border-color, #1E2C24);
}

html.dark .searchable-select-option.is-selected .option-badge {
    background: rgba(16, 185, 129, 0.16);
    color: var(--color-accent, #34D399);
    border-color: rgba(52, 211, 153, 0.35);
}

html.dark .searchable-select-empty {
    color: var(--text-muted, #94A3B8);
}
</style>

<script>
(function() {
    function initSearchableSelect(select) {
        if (!select || select.dataset.searchableInitialized === 'true') return;
        select.dataset.searchableInitialized = 'true';

        // Keep native select in DOM for form submit & validation, but visually hide it
        select.style.cssText = 'position:absolute!important;opacity:0!important;pointer-events:none!important;height:0!important;width:0!important;margin:0!important;padding:0!important;border:0!important;';

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-wrapper';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        const searchPlaceholder = select.dataset.searchPlaceholder || 'Type to search...';
        const isRequired = select.hasAttribute('required');
        const hasServerError = select.classList.contains('is-invalid');

        // Create Trigger Button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'searchable-select-btn' + (hasServerError ? ' is-invalid' : '');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const triggerContent = document.createElement('span');
        triggerContent.className = 'searchable-select-content d-flex align-items-center gap-2 text-truncate';
        
        const triggerArrow = document.createElement('span');
        triggerArrow.className = 'btn-arrow';
        triggerArrow.innerHTML = '<i class="bi bi-chevron-down"></i>';

        trigger.appendChild(triggerContent);
        trigger.appendChild(triggerArrow);
        wrapper.appendChild(trigger);

        // Create Dropdown Menu
        const menu = document.createElement('div');
        menu.className = 'searchable-select-menu';

        // Search Input Box
        const searchBox = document.createElement('div');
        searchBox.className = 'searchable-select-search-box';
        searchBox.innerHTML = `
            <i class="bi bi-search search-icon"></i>
            <input type="text" placeholder="${searchPlaceholder}" autocomplete="off" spellcheck="false">
            <button type="button" class="clear-search-btn" title="Clear search"><i class="bi bi-x-lg"></i></button>
        `;
        menu.appendChild(searchBox);

        const searchInput = searchBox.querySelector('input');
        const clearSearchBtn = searchBox.querySelector('.clear-search-btn');

        // Options List
        const list = document.createElement('div');
        list.className = 'searchable-select-list';
        menu.appendChild(list);

        // Empty Search Results Notice
        const emptyState = document.createElement('div');
        emptyState.className = 'searchable-select-empty';
        emptyState.innerHTML = '<i class="bi bi-search d-block mb-1 fs-5 text-secondary"></i><span>No matching results found</span>';
        menu.appendChild(emptyState);

        wrapper.appendChild(menu);

        function renderOptions() {
            list.innerHTML = '';
            const options = Array.from(select.options);

            options.forEach((opt, idx) => {
                const optVal = opt.value;
                const optText = opt.text.trim();
                const isSelected = opt.selected;

                // Extract badge if in format "Name (CODE)" or data-badge
                let mainText = optText;
                let badgeText = opt.getAttribute('data-badge') || '';
                
                if (!badgeText && optVal !== '') {
                    const match = optText.match(/^(.*?)\s*\(([^)]+)\)$/);
                    if (match) {
                        mainText = match[1].trim();
                        badgeText = match[2].trim();
                    }
                }

                const item = document.createElement('div');
                item.className = 'searchable-select-option' + (isSelected ? ' is-selected' : '');
                item.dataset.value = optVal;
                item.dataset.index = idx;
                item.dataset.searchText = optText.toLowerCase() + ' ' + badgeText.toLowerCase();

                let html = `<span class="option-label text-truncate">${mainText}</span>`;
                if (badgeText) {
                    html += `<span class="option-badge">${badgeText}</span>`;
                }
                html += '<i class="bi bi-check2 check-icon"></i>';
                item.innerHTML = html;

                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectValue(optVal);
                    closeMenu();
                    trigger.focus();
                });

                list.appendChild(item);
            });
        }

        function updateTriggerDisplay() {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.value !== '') {
                const text = selectedOpt.text.trim();
                let mainText = text;
                let badgeText = selectedOpt.getAttribute('data-badge') || '';

                if (!badgeText) {
                    const match = text.match(/^(.*?)\s*\(([^)]+)\)$/);
                    if (match) {
                        mainText = match[1].trim();
                        badgeText = match[2].trim();
                    }
                }

                let displayHtml = `<span class="fw-medium text-dark text-truncate">${mainText}</span>`;
                if (badgeText) {
                    displayHtml += `<span class="badge bg-light text-dark border font-monospace ms-auto" style="font-size:0.75rem;">${badgeText}</span>`;
                }
                triggerContent.innerHTML = displayHtml;
            } else {
                const placeholder = select.getAttribute('data-placeholder') || (selectedOpt ? selectedOpt.text : 'Select an option');
                triggerContent.innerHTML = `<span class="searchable-select-placeholder text-truncate">${placeholder}</span>`;
            }
        }

        function selectValue(val) {
            select.value = val;
            updateTriggerDisplay();

            // Sync option highlight
            list.querySelectorAll('.searchable-select-option').forEach(item => {
                if (item.dataset.value === String(val)) {
                    item.classList.add('is-selected');
                } else {
                    item.classList.remove('is-selected');
                }
            });

            // Remove invalid class if valid selection made
            if (val !== '') {
                trigger.classList.remove('is-invalid');
                select.classList.remove('is-invalid');
            }

            // Dispatch native change & input events
            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function filterOptions(query) {
            const cleanQuery = query.trim().toLowerCase();
            const items = list.querySelectorAll('.searchable-select-option');
            let visibleCount = 0;

            items.forEach(item => {
                if (!cleanQuery) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    const matches = item.dataset.searchText.includes(cleanQuery);
                    item.style.display = matches ? 'flex' : 'none';
                    if (matches) visibleCount++;
                }
            });

            clearSearchBtn.style.display = cleanQuery ? 'block' : 'none';
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function openMenu() {
            // Close all other open searchable select menus
            document.querySelectorAll('.searchable-select-menu').forEach(m => {
                if (m !== menu) {
                    m.style.display = 'none';
                    const parentBtn = m.parentNode.querySelector('.searchable-select-btn');
                    if (parentBtn) {
                        parentBtn.classList.remove('is-active');
                        parentBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            menu.style.display = 'block';
            trigger.classList.add('is-active');
            trigger.setAttribute('aria-expanded', 'true');

            // Reset search filter
            searchInput.value = '';
            filterOptions('');

            // Scroll selected item into view
            const selectedItem = list.querySelector('.searchable-select-option.is-selected');
            if (selectedItem) {
                selectedItem.scrollIntoView({ block: 'nearest' });
            }

            // Focus search input
            setTimeout(() => searchInput.focus(), 50);
        }

        function closeMenu() {
            menu.style.display = 'none';
            trigger.classList.remove('is-active');
            trigger.setAttribute('aria-expanded', 'false');
        }

        // Trigger Click
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (menu.style.display === 'block') {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Search Input Events
        searchInput.addEventListener('input', function() {
            filterOptions(this.value);
        });

        clearSearchBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            searchInput.value = '';
            filterOptions('');
            searchInput.focus();
        });

        // Keyboard Navigation
        searchInput.addEventListener('keydown', function(e) {
            const visibleItems = Array.from(list.querySelectorAll('.searchable-select-option:not([style*="display: none"])'));
            if (!visibleItems.length) return;

            let focusedIndex = visibleItems.findIndex(item => item.classList.contains('is-focused'));

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                focusedIndex = (focusedIndex + 1) % visibleItems.length;
                visibleItems.forEach((item, i) => {
                    item.classList.toggle('is-focused', i === focusedIndex);
                    if (i === focusedIndex) item.scrollIntoView({ block: 'nearest' });
                });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                focusedIndex = (focusedIndex - 1 + visibleItems.length) % visibleItems.length;
                visibleItems.forEach((item, i) => {
                    item.classList.toggle('is-focused', i === focusedIndex);
                    if (i === focusedIndex) item.scrollIntoView({ block: 'nearest' });
                });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focusedIndex >= 0 && visibleItems[focusedIndex]) {
                    visibleItems[focusedIndex].click();
                } else if (visibleItems.length === 1) {
                    visibleItems[0].click();
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeMenu();
                trigger.focus();
            }
        });

        // Close on Click Outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                closeMenu();
            }
        });

        // HTML5 Validation Handling
        select.addEventListener('invalid', function(e) {
            trigger.classList.add('is-invalid');
            trigger.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        const form = select.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (isRequired && !select.value) {
                    trigger.classList.add('is-invalid');
                }
            });
            form.addEventListener('reset', function() {
                setTimeout(() => {
                    renderOptions();
                    updateTriggerDisplay();
                    trigger.classList.remove('is-invalid');
                }, 10);
            });
        }

        // Initial setup
        renderOptions();
        updateTriggerDisplay();
    }

    // Initialize all .searchable-select elements on page
    function initAll() {
        document.querySelectorAll('select.searchable-select').forEach(initSearchableSelect);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.initSearchableSelects = initAll;
})();
</script>
@endonce
