@php
    $resolvedSocietyName = $societyName ?? 'Pristine Agro & Allied Multi State Co-operative Society Ltd.';
    $resolvedShortName = $societyShortName ?? config('app.name', 'PAAMCO');
    $resolvedRegNumber = $societyRegistrationNumber ?? null;

    $inputError = $errors->first('email') ?: $errors->first('login');
    $statusMessage = session('status');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14532D">
    <title>Forgot Password — {{ $resolvedShortName }} | MultiCred</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Pre-paint Theme Initialization -->
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('multicred_theme');
                if (!theme) {
                    theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                var metaTheme = document.querySelector('meta[name="theme-color"]');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                    if (metaTheme) metaTheme.setAttribute('content', '#090F0C');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    if (metaTheme) metaTheme.setAttribute('content', '#14532D');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: light;
            --auth-panel-bg: #F8FAFC;
            --auth-text-main: #0F172A;
            --auth-text-muted: #64748B;
        }

        html.dark,
        [data-theme="dark"] {
            color-scheme: dark;
            --auth-panel-bg: #090F0C;
            --auth-text-main: #F8FAFC;
            --auth-text-muted: #94A3B8;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--auth-panel-bg);
            color: var(--auth-text-main);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .auth-right-panel {
            background-color: var(--auth-panel-bg);
            color: var(--auth-text-main);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .theme-icon-sun { display: none; }
        .theme-icon-moon { display: inline-flex; }
        html.dark .theme-icon-sun { display: inline-flex; }
        html.dark .theme-icon-moon { display: none; }
    </style>
</head>
<body class="min-h-screen w-full transition-colors duration-200 flex flex-col justify-between">

    <!-- Top Bar: Minimal Theme Switcher -->
    <header class="w-full flex items-center justify-end px-6 py-4 sm:px-8">
        <button
            type="button"
            id="themeToggleBtn"
            aria-label="Toggle Light or Dark Theme"
            aria-pressed="false"
            title="Switch Light / Dark Theme"
            class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg text-xs font-medium cursor-pointer bg-white/80 dark:bg-[#15221B] hover:bg-gray-100 dark:hover:bg-[#1E2E25] text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/10 transition-colors shadow-2xs"
        >
            <span class="theme-icon-moon items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span>Dark</span>
            </span>
            <span class="theme-icon-sun items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Light</span>
            </span>
        </button>
    </header>

    <!-- Main Content: Minimal Centered Forgot Password Card -->
    <main class="auth-right-panel w-full flex-1 flex flex-col justify-center items-center px-4 py-4 sm:px-6">
        <div class="w-full max-w-sm sm:max-w-md">

            <!-- Brand Header -->
            <div class="text-center mb-6">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center mb-3 transition-transform hover:scale-105" title="{{ $resolvedSocietyName }}">
                    @if(file_exists(public_path('logo.png')))
                        <img src="{{ asset('logo.png') }}" alt="{{ $resolvedShortName }} Logo" class="w-12 h-12 object-contain">
                    @elseif(file_exists(public_path('logo-transparent.png')))
                        <img src="{{ asset('logo-transparent.png') }}" alt="{{ $resolvedShortName }} Logo" class="w-12 h-12 object-contain">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-bold text-lg shadow-xs">
                            {{ substr($resolvedShortName, 0, 1) }}
                        </div>
                    @endif
                </a>
                <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $resolvedShortName }}
                </h2>
                @if($resolvedRegNumber)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $resolvedRegNumber }}
                    </p>
                @endif
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-[#111A15] border border-gray-200/90 dark:border-white/10 rounded-2xl shadow-xs p-6 sm:p-8">

                <!-- Heading -->
                <div class="mb-5 text-left">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Forgot Password?
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Enter your email address or account ID, and we'll send you a secure link to reset your password.
                    </p>
                </div>

                <!-- Alert Messages -->
                @if($inputError)
                    <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 p-3 text-xs text-red-800 dark:text-red-200" role="alert">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="leading-relaxed font-medium">{{ $inputError }}</span>
                    </div>
                @endif

                @if($statusMessage)
                    <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 p-3 text-xs text-emerald-800 dark:text-emerald-200" role="alert">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="leading-relaxed font-medium">{{ $statusMessage }}</span>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm" class="space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email Address or Account ID
                        </label>
                        <div class="relative rounded-lg border {{ $inputError ? 'border-red-500 bg-red-50/20' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-[#16211B]' }} focus-within:ring-2 focus-within:ring-emerald-600 focus-within:border-emerald-600 transition-all flex items-center">
                            <div class="pl-3 pointer-events-none text-gray-400 dark:text-gray-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="email"
                                name="email"
                                class="w-full bg-transparent border-0 px-2.5 py-2 sm:py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-0"
                                value="{{ old('email') }}"
                                placeholder="e.g. user@paamco.in or STF001"
                                required
                                autofocus
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-1">
                        <button
                            type="submit"
                            id="submitForgotBtn"
                            class="w-full h-10 sm:h-11 rounded-lg text-white font-medium text-sm bg-emerald-700 hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500 shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-70 disabled:cursor-wait"
                        >
                            <svg id="submitSpinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="submitBtnText">Send Reset Link</span>
                        </button>
                    </div>

                    <!-- Back to Login Navigation -->
                    <div class="pt-2 text-center">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 hover:underline transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Back to Login</span>
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-4 px-4 text-center text-xs text-gray-400 dark:text-gray-500">
        <p class="font-medium text-gray-500 dark:text-gray-400">{{ $resolvedSocietyName }}</p>
        <p class="mt-0.5">&copy; {{ date('Y') }} {{ $resolvedShortName }}. MultiCred System.</p>
    </footer>

    <!-- Interactive Script Layer -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const forgotForm = document.getElementById('forgotPasswordForm');
            const submitBtn = document.getElementById('submitForgotBtn');
            const submitSpinner = document.getElementById('submitSpinner');
            const submitBtnText = document.getElementById('submitBtnText');

            const syncThemeButtonState = function () {
                const isDark = document.documentElement.classList.contains('dark');
                if (themeToggleBtn) {
                    themeToggleBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                }
            };

            const applyLoginTheme = function (nextTheme) {
                const metaTheme = document.querySelector('meta[name="theme-color"]');
                if (nextTheme === 'dark') {
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
                syncThemeButtonState();
            };

            const toggleThemeAction = function () {
                const isDark = document.documentElement.classList.contains('dark');
                const next = isDark ? 'light' : 'dark';
                try {
                    localStorage.setItem('multicred_theme', next);
                } catch (e) {}
                applyLoginTheme(next);
            };

            syncThemeButtonState();

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', toggleThemeAction);
            }

            if (forgotForm && submitBtn) {
                forgotForm.addEventListener('submit', function () {
                    if (submitBtn.disabled) return;
                    submitBtn.disabled = true;
                    if (submitSpinner) submitSpinner.classList.remove('hidden');
                    if (submitBtnText) submitBtnText.textContent = 'Sending Link...';
                });

                window.addEventListener('pageshow', function () {
                    submitBtn.disabled = false;
                    if (submitSpinner) submitSpinner.classList.add('hidden');
                    if (submitBtnText) submitBtnText.textContent = 'Send Reset Link';
                });
            }
        });
    </script>
</body>
</html>
