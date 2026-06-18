<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AgentMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (Auth::user()->role === 'agent' || Auth::user()->role === 'admin')) {
            return $next($request);
        }

        return redirect('/login')->with('error', 'Unauthorized. Agent access required.');
    }
}
