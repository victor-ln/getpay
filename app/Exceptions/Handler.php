<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Exceptions\ThrottleRequestsException; // ✅ Importe a classe
use App\Events\UserActionOccurred;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Bad Credentials'
                ], 401);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ThrottleRequestsException $e, $request) {
            // Dispara o nosso evento de log de auditoria
            event(new UserActionOccurred(
                user: $request->user(), // Pode ser nulo
                action: 'RATE_LIMIT_EXCEEDED',
                context: [
                    'path' => $request->path(),
                    'email_attempted' => $request->input('email'),
                ],
                message: 'An IP address has exceeded the request limit.',
                level: 'critical' // É um evento de segurança crítico
            ));
        });
    }
}
