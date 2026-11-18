import Alpine from 'alpinejs';
import { initFlowbite } from 'flowbite';
import { createIcons, icons } from 'lucide';
import Chart from 'chart.js/auto';
import { hydratePremiumIcons, initPremiumIcons } from './premium-icons';

// Attach globally for inline Blade scripts and reactivity
window.Alpine = Alpine;
window.Chart = Chart;
window.initFlowbite = initFlowbite;
window.lucide = { createIcons, icons };

window.refreshIcons = (root = document) => {
    createIcons({ icons });
    hydratePremiumIcons(root);
};

// Global Theme Management
window.getPreferredTheme = function() {
    const saved = localStorage.getItem('multicred_theme');
    if (saved) return saved;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

window.applyTheme = function(theme) {
    const metaTheme = document.querySelector('meta[name="theme-color"]');
    if (theme === 'dark') {
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
};

window.toggleTheme = function() {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem('multicred_theme', next);
    } catch (e) {}
    window.applyTheme(next);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: next } }));
};

// Upgrade icons immediately if DOM nodes are already parsed, then observe dynamically added nodes
initPremiumIcons();

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    initPremiumIcons();
    initFlowbite();
});

// Start Alpine
Alpine.start();
