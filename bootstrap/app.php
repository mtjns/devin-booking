<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemCrashNotice;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Reverse proxy support: trusts the proxies defined in the TRUSTED_PROXIES environment variable.
        // It accepts a comma-separated list of IP addresses or subnets (e.g., "192.168.1.10,10.0.0.0/8").
        // If the variable is empty or 'false', it doesn't trust any proxies. If it is set to '*', it trusts all proxies.
        $trustedProxies = env('TRUSTED_PROXIES');

        if (!empty($trustedProxies) && $trustedProxies !== 'false') {
            if ($trustedProxies === '*') {
                $middleware->trustProxies(at: '*');
            } else {
                $proxies = array_map('trim', explode(',', $trustedProxies));
                $middleware->trustProxies(at: $proxies);
            }
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->reportable(function (Throwable $e) {

            if (app()->environment('production')) {
                try {
                    $recipients = config('mail.crash_emails');

                    if (!empty($recipients)) {
                        Mail::to($recipients)->send(new SystemCrashNotice($e, 'Main Application'));
                    }
                } catch (Throwable $mailException) {
                    // Silently swallow
                }
            }

        });
    })->create();
