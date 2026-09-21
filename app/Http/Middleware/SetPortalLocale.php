<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPortalLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('site_lang');

        if (in_array($requested, ['pt-BR', 'en-US'], true)) {
            $request->session()->put('site_lang', $requested);
        }

        $siteLang = (string) $request->session()->get('site_lang', 'pt-BR');
        app()->setLocale($siteLang === 'en-US' ? 'en' : 'pt_BR');

        return $next($request);
    }
}
