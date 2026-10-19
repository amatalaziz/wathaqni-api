<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $exception) => $request->is('api/v1/*') || $request->expectsJson());

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (! $request->is('api/v1/*') || $response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $message = match ($status) {
                401 => 'Unauthenticated or invalid credentials.',
                403 => 'This action is not authorized.',
                404 => 'Not found.',
                405 => 'Method not allowed.',
                422 => 'The given data was invalid.',
                429 => 'Too many attempts. Please try again later.',
                default => $status >= 500 ? 'Server error.' : (Response::$statusTexts[$status] ?? 'Request failed.'),
            };

            // Retain throttling headers; never expose exception messages or traces.
            $response->setContent(json_encode([
                'message' => $message,
                'errors' => $exception instanceof ValidationException ? $exception->errors() : (object) [],
            ], JSON_THROW_ON_ERROR));
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        });
    })->create();
