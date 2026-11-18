<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Restricted | MultiCred</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('multicred_theme');
                if (!theme) {
                    theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        :root {
            --bg-page: #F8FAF9;
            --card-bg: #FFFFFF;
            --text-heading: #0F172A;
            --text-body: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8E4;
            --color-primary: #14532D;
            --color-primary-hover: #0F3F22;
        }
        html.dark, body.dark {
            --bg-page: #090F0C;
            --card-bg: #111A15;
            --text-heading: #F8FAFC;
            --text-body: #E2E8F0;
            --text-muted: #94A3B8;
            --border-color: #1E2C24;
            --color-primary: #059669;
            --color-primary-hover: #10B981;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-body);
            transition: background 0.2s ease, color 0.2s ease;
        }
        .error-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .icon-box {
            width: 52px;
            height: 52px;
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.25rem auto;
        }
        html.dark .icon-box {
            background: rgba(240, 120, 120, 0.15);
            color: #F07878;
            border-color: rgba(240, 120, 120, 0.35);
        }
        .badge-status {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 10px;
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 12px;
        }
        html.dark .badge-status {
            background: rgba(240, 120, 120, 0.15);
            color: #F07878;
            border-color: rgba(240, 120, 120, 0.35);
        }
        .btn-brand-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            color: #FFFFFF;
            border-radius: 8px;
            font-size: 0.825rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 9px 18px;
            transition: all 0.15s ease;
        }
        .btn-brand-primary:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
            color: #FFFFFF;
        }
        .btn-outline-custom {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-body);
            border-radius: 8px;
            font-size: 0.825rem;
            font-weight: 600;
            padding: 9px 18px;
            transition: all 0.15s ease;
        }
        .btn-outline-custom:hover {
            background-color: var(--bg-page);
            color: var(--text-heading);
            border-color: var(--border-color);
        }
        i.bi.mc-icon-hydrated {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            vertical-align: -0.14em;
            flex-shrink: 0;
        }
        i.bi.mc-icon-hydrated::before {
            content: none !important;
            display: none !important;
        }
        .mc-svg-icon {
            width: 1em;
            height: 1em;
            display: block;
            flex-shrink: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="badge-status">
            HTTP 403 · Access Forbidden
        </div>
        <h4 class="fw-bold mb-2" style="color: var(--text-heading); letter-spacing: -0.015em;">Access Restricted</h4>
        <p class="small mb-4" style="color: var(--text-muted); line-height: 1.6; font-size: 0.84rem;">
            {{ $exception->getMessage() ?: 'You do not possess the required operational authorization or branch permissions to access this financial record.' }}
        </p>

        @php
            $dashboardRoute = route('superadmin.login');
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    $dashboardRoute = route('superadmin.dashboard');
                } elseif ($user->isBranchAdmin()) {
                    $dashboardRoute = route('branch-admin.dashboard');
                } elseif ($user->isAgent()) {
                    $dashboardRoute = route('agent.dashboard');
                } elseif ($user->isStaff()) {
                    $dashboardRoute = route('staff.dashboard');
                }
            }
        @endphp

        <div class="d-flex flex-column gap-2">
            <a href="{{ $dashboardRoute }}" class="btn btn-brand-primary">
                <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-custom">
                Go to Previous Page
            </button>
            @if(auth()->check())
            <form action="{{ route('superadmin.logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-link small p-0 text-decoration-none" style="color: var(--text-muted); font-size: 0.775rem;">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign out to switch account
                </button>
            </form>
            @endif
        </div>
    </div>
</body>
</html>
