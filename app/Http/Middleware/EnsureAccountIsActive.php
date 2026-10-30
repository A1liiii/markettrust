<?php

namespace App\Http\Middleware;

use App\Contracts\Services\PaymentServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user || ! $user->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login' => 'Akun Anda sedang ditangguhkan (SUSPENDED). Silakan hubungi admin.',
                ]);
            }

            try {
                app(PaymentServiceInterface::class)->evaluateOverduePaymentsForUser($user);
            } catch (Throwable) {
                // Jangan gagalkan request jika evaluasi tenggat mengalami kendala eksternal
            }
        }

        return $next($request);
    }
}
