<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AgentMiddleware;
use App\Http\Middleware\BranchAdminMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'superadmin' => SuperAdminMiddleware::class,
            'admin' => AdminMiddleware::class,
            'branch-admin' => BranchAdminMiddleware::class,
            'agent' => AgentMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('superadmin.login'));

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user?->isSuperAdmin()) {
                return route('superadmin.dashboard');
            }
            if ($user?->isBranchAdmin()) {
                return route('branch-admin.dashboard');
            }
            if ($user?->isAgent()) {
                return route('agent.dashboard');
            }
            if ($user?->isStaff()) {
                return route('staff.dashboard');
            }

            return route('superadmin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
