<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render terminates HTTPS at its reverse proxy before forwarding to Apache.
        if (env('RENDER') === true || env('RENDER') === 'true') {
            $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*')) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    401 => 'Please sign in.',
                    403 => 'You do not have permission to perform this action.',
                    404 => 'The requested content was not found.',
                    405 => 'This request method is not supported.',
                    419 => 'Your session expired. Please try again.',
                    429 => 'Too many requests. Please try again shortly.',
                    default => $status >= 500 ? 'The service is temporarily unavailable.' : 'The request could not be completed.',
                };

                return response()->json(['message' => $message], $status, $exception->getHeaders());
            }
        });
        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($request->is('api/*')) {
                $conflict = str_starts_with((string) $exception->getCode(), '23');

                return response()->json(['message' => $conflict ? 'This record conflicts with existing or related content.' : 'The database is temporarily unavailable.'], $conflict ? 409 : 503);
            }
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/*') && ! ($exception instanceof HttpExceptionInterface) && ! ($exception instanceof ValidationException) && ! ($exception instanceof AuthenticationException)) {
                return response()->json(['message' => 'The request could not be completed. Please try again.'], 500);
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
