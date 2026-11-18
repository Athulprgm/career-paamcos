import {
    createElement,
    Archive,
    ArrowLeft,
    ArrowRight,
    ArrowUpRight,
    Award,
    BadgeCheck,
    Ban,
    Banknote,
    BarChart2,
    BarChart3,
    Barcode,
    Bell,
    BellOff,
    BookMarked,
    BookOpenText,
    BookX,
    Briefcase,
    BriefcaseBusiness,
    Building,
    Building2,
    Calculator,
    Calendar,
    CalendarCheck,
    CalendarCheck2,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    Camera,
    Check,
    CheckCheck,
    ChevronDown,
    ChevronRight,
    ChevronUp,
    ChevronsLeft,
    CircleAlert,
    CircleArrowRight,
    CircleArrowUp,
    CircleCheck,
    CircleCheckBig,
    CircleHelp,
    CircleMinus,
    CirclePause,
    CirclePlay,
    CirclePlus,
    CircleUserRound,
    CircleX,
    Clipboard,
    ClipboardCheck,
    ClipboardList,
    Clock,
    CloudUpload,
    Coins,
    ContactRound,
    Copy,
    CreditCard,
    Crop,
    Crosshair,
    Download,
    Eye,
    EyeOff,
    FileBarChart2,
    FileSpreadsheet,
    FileText,
    Filter,
    FlipHorizontal2,
    Folder,
    FolderOpen,
    FolderX,
    Gauge,
    GitBranch,
    GitFork,
    Globe,
    HeartHandshake,
    History,
    Hourglass,
    House,
    IdCard,
    Inbox,
    IndianRupee,
    Info,
    KeyRound,
    Layers,
    LayoutDashboard,
    LayoutGrid,
    Link2,
    ListChecks,
    ListFilter,
    Lock,
    LogOut,
    Mail,
    MailCheck,
    MapPin,
    MapPinned,
    Maximize2,
    Megaphone,
    Menu,
    MessageSquareQuote,
    Minimize2,
    Minus,
    Moon,
    Network,
    OctagonAlert,
    OctagonX,
    Pencil,
    Percent,
    Phone,
    PhoneIncoming,
    PieChart,
    PiggyBank,
    Plus,
    Printer,
    QrCode,
    RefreshCw,
    RotateCcw,
    RotateCw,
    Save,
    Search,
    Send,
    Settings,
    Settings2,
    Shield,
    ShieldAlert,
    ShieldCheck,
    ShieldOff,
    ShieldPlus,
    SlidersHorizontal,
    Smartphone,
    Sparkles,
    SquarePen,
    Sun,
    Table2,
    Tag,
    Target,
    ToggleRight,
    Trash2,
    TrendingUp,
    TriangleAlert,
    Trophy,
    User,
    UserCheck,
    UserCog,
    UserLock,
    UserMinus,
    UserPlus,
    UserX,
    Users,
    Wallet,
    WandSparkles,
    X,
    Zap,
    ZoomIn,
} from 'lucide';

/**
 * Custom vector IconNode definitions for brand/domain icons not included in Lucide.
 * Formatted as Lucide IconNode tuples [tag, attributes].
 */
const WhatsAppIcon = [
    ['path', { d: 'M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9z' }],
    ['path', { d: 'M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1' }],
];

/**
 * Semantic mapping from Bootstrap Icons (`bi-*`) to crisp Lucide / custom SVG definitions.
 * Icons ending in `-fill` automatically get subtle duotone surface depth.
 */
const BI_TO_VECTOR_MAP = {
    'bi-archive': Archive,
    'bi-arrow-clockwise': RotateCw,
    'bi-arrow-counterclockwise': RotateCcw,
    'bi-arrow-left': ArrowLeft,
    'bi-arrow-repeat': RefreshCw,
    'bi-arrow-right': ArrowRight,
    'bi-arrow-right-circle': CircleArrowRight,
    'bi-arrow-right-short': ArrowRight,
    'bi-arrows-collapse': Minimize2,
    'bi-arrows-expand': Maximize2,
    'bi-arrow-up-circle': CircleArrowUp,
    'bi-arrow-up-right': ArrowUpRight,
    'bi-award': Award,
    'bi-award-fill': Award,
    'bi-bar-chart': BarChart2,
    'bi-bar-chart-line': BarChart3,
    'bi-bell': Bell,
    'bi-bell-fill': Bell,
    'bi-bell-slash': BellOff,
    'bi-box-arrow-in-down': Download,
    'bi-box-arrow-left': LogOut,
    'bi-box-arrow-right': LogOut,
    'bi-briefcase': Briefcase,
    'bi-briefcase-fill': Briefcase,
    'bi-building': Building,
    'bi-buildings': Building2,
    'bi-bullseye': Target,
    'bi-calculator': Calculator,
    'bi-calendar2-check': CalendarCheck2,
    'bi-calendar2-day': CalendarDays,
    'bi-calendar3': Calendar,
    'bi-calendar-check': CalendarCheck,
    'bi-calendar-check-fill': CalendarCheck,
    'bi-calendar-event': CalendarClock,
    'bi-calendar-month': CalendarDays,
    'bi-calendar-range': CalendarRange,
    'bi-camera': Camera,
    'bi-camera-fill': Camera,
    'bi-card-checklist': ClipboardCheck,
    'bi-cash-coin': Coins,
    'bi-cash-stack': Banknote,
    'bi-chat-quote': MessageSquareQuote,
    'bi-check': Check,
    'bi-check2': Check,
    'bi-check2-all': CheckCheck,
    'bi-check2-circle': CircleCheckBig,
    'bi-check-all': CheckCheck,
    'bi-check-circle': CircleCheck,
    'bi-check-circle-fill': CircleCheck,
    'bi-check-lg': Check,
    'bi-chevron-double-left': ChevronsLeft,
    'bi-chevron-down': ChevronDown,
    'bi-chevron-right': ChevronRight,
    'bi-chevron-up': ChevronUp,
    'bi-clipboard': Clipboard,
    'bi-clipboard-check': ClipboardCheck,
    'bi-clipboard-data': ClipboardList,
    'bi-clock': Clock,
    'bi-clock-history': History,
    'bi-cloud-arrow-up': CloudUpload,
    'bi-coin': Coins,
    'bi-collection': Layers,
    'bi-collection-fill': Layers,
    'bi-copy': Copy,
    'bi-credit-card': CreditCard,
    'bi-crop': Crop,
    'bi-crosshair': Crosshair,
    'bi-currency-rupee': IndianRupee,
    'bi-dash': Minus,
    'bi-dash-circle': CircleMinus,
    'bi-dash-lg': Minus,
    'bi-diagram-2': GitFork,
    'bi-diagram-2-fill': GitFork,
    'bi-diagram-3': Network,
    'bi-diagram-3-fill': Network,
    'bi-envelope': Mail,
    'bi-envelope-check': MailCheck,
    'bi-exclamation': CircleAlert,
    'bi-exclamation-circle': CircleAlert,
    'bi-exclamation-circle-fill': CircleAlert,
    'bi-exclamation-octagon': OctagonAlert,
    'bi-exclamation-octagon-fill': OctagonAlert,
    'bi-exclamation-triangle': TriangleAlert,
    'bi-exclamation-triangle-fill': TriangleAlert,
    'bi-eye': Eye,
    'bi-eye-slash': EyeOff,
    'bi-file-earmark-bar-graph': FileBarChart2,
    'bi-file-earmark-excel': FileSpreadsheet,
    'bi-file-earmark-text': FileText,
    'bi-filter': ListFilter,
    'bi-folder2': Folder,
    'bi-folder2-open': FolderOpen,
    'bi-folder-x': FolderX,
    'bi-funnel': Filter,
    'bi-gear': Settings,
    'bi-gear-wide-connected': Settings2,
    'bi-geo-alt': MapPin,
    'bi-geo-alt-fill': MapPin,
    'bi-globe': Globe,
    'bi-graph-up': TrendingUp,
    'bi-graph-up-arrow': TrendingUp,
    'bi-grid': LayoutGrid,
    'bi-grid-1x2': LayoutDashboard,
    'bi-grid-3x3-gap': LayoutGrid,
    'bi-grid-fill': LayoutGrid,
    'bi-hourglass-bottom': Hourglass,
    'bi-hourglass-split': Hourglass,
    'bi-house': House,
    'bi-inbox': Inbox,
    'bi-info': Info,
    'bi-info-circle': Info,
    'bi-info-circle-fill': Info,
    'bi-journal-bookmark': BookMarked,
    'bi-journal-bookmark-fill': BookMarked,
    'bi-journal-text': BookOpenText,
    'bi-journal-x': BookX,
    'bi-key': KeyRound,
    'bi-layers': Layers,
    'bi-lightning-charge': Zap,
    'bi-lightning-charge-fill': Zap,
    'bi-link-45deg': Link2,
    'bi-list': Menu,
    'bi-list-check': ListChecks,
    'bi-lock': Lock,
    'bi-lock-fill': Lock,
    'bi-magic': WandSparkles,
    'bi-megaphone': Megaphone,
    'bi-moon-fill': Moon,
    'bi-patch-check-fill': BadgeCheck,
    'bi-pause-circle': CirclePause,
    'bi-pause-circle-fill': CirclePause,
    'bi-pencil': Pencil,
    'bi-pencil-square': SquarePen,
    'bi-people': Users,
    'bi-people-fill': Users,
    'bi-percent': Percent,
    'bi-person': User,
    'bi-person-badge': IdCard,
    'bi-person-badge-fill': IdCard,
    'bi-person-check': UserCheck,
    'bi-person-check-fill': UserCheck,
    'bi-person-circle': CircleUserRound,
    'bi-person-dash-fill': UserMinus,
    'bi-person-fill': User,
    'bi-person-gear': UserCog,
    'bi-person-heart': HeartHandshake,
    'bi-person-lines-fill': ContactRound,
    'bi-person-lock': UserLock,
    'bi-person-plus': UserPlus,
    'bi-person-plus-fill': UserPlus,
    'bi-person-slash': UserX,
    'bi-person-vcard': IdCard,
    'bi-person-workspace': BriefcaseBusiness,
    'bi-phone': Smartphone,
    'bi-pie-chart': PieChart,
    'bi-pie-chart-fill': PieChart,
    'bi-piggy-bank': PiggyBank,
    'bi-piggy-bank-fill': PiggyBank,
    'bi-pin-map': MapPinned,
    'bi-play-circle': CirclePlay,
    'bi-play-circle-fill': CirclePlay,
    'bi-plus': Plus,
    'bi-plus-circle': CirclePlus,
    'bi-plus-circle-fill': CirclePlus,
    'bi-plus-lg': Plus,
    'bi-printer': Printer,
    'bi-qr-code': QrCode,
    'bi-question-circle': CircleHelp,
    'bi-save': Save,
    'bi-search': Search,
    'bi-send': Send,
    'bi-shield-check': ShieldCheck,
    'bi-shield-exclamation': ShieldAlert,
    'bi-shield-lock': Shield,
    'bi-shield-lock-fill': Shield,
    'bi-shield-plus': ShieldPlus,
    'bi-shield-slash': ShieldOff,
    'bi-signpost-split': GitBranch,
    'bi-slash-circle': Ban,
    'bi-sliders': SlidersHorizontal,
    'bi-speedometer2': Gauge,
    'bi-stars': Sparkles,
    'bi-sun-fill': Sun,
    'bi-symmetry-vertical': FlipHorizontal2,
    'bi-table': Table2,
    'bi-tag': Tag,
    'bi-telephone': Phone,
    'bi-telephone-fill': Phone,
    'bi-telephone-inbound-fill': PhoneIncoming,
    'bi-toggle2-on': ToggleRight,
    'bi-trash': Trash2,
    'bi-trash3': Trash2,
    'bi-trash3-fill': Trash2,
    'bi-trophy': Trophy,
    'bi-trophy-fill': Trophy,
    'bi-upc': Barcode,
    'bi-wallet2': Wallet,
    'bi-whatsapp': WhatsAppIcon,
    'bi-x': X,
    'bi-x-circle': CircleX,
    'bi-x-circle-fill': CircleX,
    'bi-x-lg': X,
    'bi-x-octagon': OctagonX,
    'bi-zoom-in': ZoomIn,
};

/**
 * Icon names that look sharpest with duotone accent fill in stat containers & filled badges.
 */
const HERO_CONTAINER_SELECTOR = '.stat-icon, .metric-icon-box, .comm-kpi-icon-pill, .dash-action-icon, .icon-box, .auth-brand-logo-box, .toast-popup-icon, .custom-confirm-icon-box';

/**
 * Extracts the canonical `bi-*` class present in our vector registry from an element's classList.
 */
function resolveBiClass(el) {
    if (!el || !el.classList) return null;
    for (const cls of el.classList) {
        if (cls.startsWith('bi-') && Object.prototype.hasOwnProperty.call(BI_TO_VECTOR_MAP, cls)) {
            return cls;
        }
    }
    return null;
}

/**
 * Applies subtle Stripe/Phosphor-style duotone fill to closed geometry primitives inside the SVG.
 */
function applyDuotoneDepth(svg, opacity) {
    const shapes = svg.querySelectorAll('circle, rect, polygon, ellipse, path');
    for (const shape of shapes) {
        const tag = shape.tagName.toLowerCase();
        if (tag === 'circle') {
            const r = parseFloat(shape.getAttribute('r') || '0');
            if (r >= 2.2) {
                shape.setAttribute('fill', 'currentColor');
                shape.setAttribute('fill-opacity', String(opacity));
            }
            continue;
        }
        if (tag === 'rect' || tag === 'polygon' || tag === 'ellipse') {
            shape.setAttribute('fill', 'currentColor');
            shape.setAttribute('fill-opacity', String(opacity));
            continue;
        }
        if (tag === 'path') {
            const d = shape.getAttribute('d') || '';
            // Only fill closed subpaths long enough to represent a container shape rather than a tiny dot.
            if (d.length > 16 && /z\s*$/i.test(d)) {
                shape.setAttribute('fill', 'currentColor');
                shape.setAttribute('fill-opacity', String(opacity));
            }
        }
    }
}

/**
 * Hydrates a single icon element into a precision vector SVG while preserving the host element.
 */
function hydrateIconElement(el) {
    const biClass = resolveBiClass(el);
    if (!biClass) return;

    const existingSvg = el.querySelector(':scope > svg.mc-svg-icon');
    if (el.dataset.mcIcon === biClass && existingSvg) {
        if (!el.classList.contains('mc-icon-hydrated')) {
            el.classList.add('mc-icon-hydrated');
        }
        return;
    }

    const iconNode = BI_TO_VECTOR_MAP[biClass];
    if (!iconNode) return;

    const isHero = Boolean(el.closest(HERO_CONTAINER_SELECTOR));
    const isFilled = biClass.endsWith('-fill');

    const svg = createElement(iconNode, {
        class: `mc-svg-icon${isFilled || isHero ? ' mc-svg-duotone' : ''}`,
        width: '1em',
        height: '1em',
        'stroke-width': isHero ? '1.75' : '1.9',
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'shape-rendering': 'geometricPrecision',
        'aria-hidden': 'true',
        focusable: 'false',
    });

    if (isFilled || isHero) {
        applyDuotoneDepth(svg, isFilled ? 0.17 : 0.13);
    }

    // Remove previous injected SVGs without disturbing unrelated attributes or listeners.
    el.querySelectorAll(':scope > svg.mc-svg-icon').forEach((node) => node.remove());
    el.appendChild(svg);
    el.dataset.mcIcon = biClass;
    el.classList.add('mc-icon-hydrated');
}

let observerInstance = null;

/**
 * Scans a DOM subtree and upgrades all icon elements to crisp vector SVGs.
 */
export function hydratePremiumIcons(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') return;

    if (root instanceof Element && root.matches('i.bi, [class*=" bi-"], [class^="bi-"]')) {
        hydrateIconElement(root);
    }

    const candidates = root.querySelectorAll('i.bi, [class*=" bi-"], [class^="bi-"]');
    for (const el of candidates) {
        hydrateIconElement(el);
    }
}

/**
 * Starts the global DOM observer so dynamic AJAX partials, modals, toasts, and icon state toggles stay crisp.
 */
export function initPremiumIcons() {
    document.documentElement.classList.add('mc-icons-ready');
    hydratePremiumIcons(document);

    if (observerInstance || typeof MutationObserver === 'undefined') {
        return;
    }

    observerInstance = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes') {
                const target = mutation.target;
                if (target instanceof Element && (target.classList.contains('bi') || target.dataset.mcIcon)) {
                    const nextBi = resolveBiClass(target);
                    if (nextBi !== target.dataset.mcIcon) {
                        hydrateIconElement(target);
                    }
                }
                continue;
            }

            if (mutation.type === 'childList') {
                for (const node of mutation.addedNodes) {
                    if (node instanceof Element) {
                        hydratePremiumIcons(node);
                    }
                }
            }
        }
    });

    observerInstance.observe(document.documentElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class'],
    });
}

export { BI_TO_VECTOR_MAP };
