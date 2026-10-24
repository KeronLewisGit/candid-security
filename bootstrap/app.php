<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        // Two kinds of account: staff (web guard) and applicants (applicant guard).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal*', 'apply/*') ? route('portal.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal*') ? route('portal.dashboard') : route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Zip-package layout (INSTALL-HOSTINGER.md, option A): the app lives in public_html/candid-app
// and public_html is the web root. With the Git layout public/ exists and this does nothing.
$publicHtml = dirname(__DIR__, 2);
if (! is_dir($app->basePath('public')) && is_file($publicHtml.'/index.php') && is_dir($publicHtml.'/build')) {
    $app->usePublicPath($publicHtml);
}

return $app;
