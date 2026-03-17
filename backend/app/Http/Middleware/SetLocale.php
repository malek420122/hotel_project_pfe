<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = config('languages.available', ['fr', 'en', 'ar']);
        $requested = $request->getPreferredLanguage($available);

        if ($request->has('locale') && in_array($request->query('locale'), $available, true)) {
            $requested = $request->query('locale');
        }

        app()->setLocale($requested ?? config('languages.default', 'fr'));

        return $next($request);
    }
}
