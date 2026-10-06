<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'remote.portal' => \App\Http\Middleware\VerifyRemotePortal::class,
            'force.password' => \App\Http\Middleware\ForcePasswordChange::class,
            'maintenance.mode' => \App\Http\Middleware\MaintenanceMode::class,
        ]);

        // Module on/off switches (feature flags). Fail-open when no flag is set.
        // Maintenance banner/page, module switches, forced password change (new parent accounts), staff activity log.
        $middleware->web(append: [\App\Http\Middleware\MaintenanceMode::class, \App\Http\Middleware\FeatureRouteGuard::class, \App\Http\Middleware\ForcePasswordChange::class, \App\Http\Middleware\LogActivity::class]);
        // Payment-gateway webhooks are signature-checked, not CSRF-checked.
        $middleware->validateCsrfTokens(except: ['webhook/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
