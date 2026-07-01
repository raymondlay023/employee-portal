<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale', 'en');

        if (auth()->check()) {
            $locale = auth()->user()->locale ?? $locale;
        } elseif (session()->has('locale')) {
            $locale = session()->get('locale');
        }

        // Strict allowlist validation for security
        if (in_array($locale, ['en', 'id'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
