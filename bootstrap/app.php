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
            // Mobile app API (routes/api_v1.php)
            'api.active' => \App\Http\Middleware\EnsureApiUserActive::class,
            'api.website' => \App\Http\Middleware\VerifyWebsiteKey::class,
            'api.feature' => \App\Http\Middleware\ApiFeatureEnabled::class,
        ]);

        // Module on/off switches (feature flags). Fail-open when no flag is set.
        // Maintenance banner/page, module switches, forced password change (new parent accounts), staff activity log.
        $middleware->web(append: [\App\Http\Middleware\MaintenanceMode::class, \App\Http\Middleware\FeatureRouteGuard::class, \App\Http\Middleware\ForcePasswordChange::class, \App\Http\Middleware\LogActivity::class]);
        // Payment-gateway webhooks are signature-checked, not CSRF-checked.
        $middleware->validateCsrfTokens(except: ['webhook/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The mobile API always answers in JSON, even without an Accept header.
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());

        // Mobile API (v1): errors use the same envelope as successful responses.
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->is('api/v1/*')) return null;

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'success' => false, 'code' => 'validation_failed',
                    'message' => collect($e->errors())->flatten()->first() ?: 'Please check the form.',
                    'errors'  => $e->errors(),
                ], $e->status);
            }
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['success' => false, 'code' => 'unauthenticated', 'message' => 'Please sign in again.'], 401);
            }
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json(['success' => false, 'code' => 'forbidden', 'message' => $e->getMessage() ?: 'Not allowed.'], 403);
            }
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['success' => false, 'code' => 'not_found', 'message' => 'Not found.'], 404);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $code = [401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed', 429 => 'too_many_requests'][$status] ?? 'http_error';
                $msg = $e->getMessage() ?: ($status === 429 ? 'Too many requests. Please wait a moment.' : 'Request failed.');
                return response()->json(['success' => false, 'code' => $code, 'message' => $msg], $status, $e->getHeaders());
            }
            if (config('app.debug')) return null; // full trace in local development

            report($e);
            return response()->json(['success' => false, 'code' => 'server_error', 'message' => 'Something went wrong. Please try again.'], 500);
        });
    })->create();
