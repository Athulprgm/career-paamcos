<?php

namespace App\Providers;

use App\Models\DepositScheme;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Production debug assertion ─────────────────────────────────────────
        if (app()->isProduction() && config('app.debug')) {
            throw new \RuntimeException('APP_DEBUG must be false in production.');
        }

        // ── Pagination: use Bootstrap 5 views (app uses Bootstrap, not Tailwind) ─
        Paginator::useBootstrapFive();

        // ── Hashing driver safety guard ────────────────────────────────────────
        // config/hashing.php selects argon2id when PASSWORD_ARGON2ID is defined,
        // but some shared hosts ship PHP without Argon2 compiled in.  If that
        // happens we silently fall back to bcrypt here so the app never throws a
        // "500 / Out-of-Memory" error during user authentication.
        $configuredDriver = config('hashing.driver');

        if ($configuredDriver === 'argon2id' && ! defined('PASSWORD_ARGON2ID')) {
            config(['hashing.driver' => 'bcrypt']);

            Log::warning(
                'Argon2id was requested but PASSWORD_ARGON2ID is not compiled into this '.
                'PHP binary. Falling back to bcrypt automatically. '.
                'Set HASH_DRIVER=bcrypt in .env to suppress this warning.'
            );
        }

        // ── Sidebar deposit schemes ────────────────────────────────────────────
        View::composer('superadmin.layouts.app', function ($view) {
            try {
                if (Schema::hasTable('deposit_schemes')) {
                    $sidebarDepositSchemes = DepositScheme::active()->orderBy('name')->get();
                    $view->with('sidebarDepositSchemes', $sidebarDepositSchemes);
                } else {
                    $view->with('sidebarDepositSchemes', collect());
                }
            } catch (\Throwable $e) {
                $view->with('sidebarDepositSchemes', collect());
            }
        });

        // ── Dynamic session configuration ──────────────────────────────────────
        try {
            if (Schema::hasTable('settings')) {
                $lifetime = Setting::get('system.session_lifetime');
                if ($lifetime) {
                    config(['session.lifetime' => (int) $lifetime]);
                }
                $expireOnClose = Setting::get('system.expire_on_close');
                if ($expireOnClose !== null) {
                    config(['session.expire_on_close' => (bool) $expireOnClose]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore during early bootstrap or migration runs
        }
    }
}
