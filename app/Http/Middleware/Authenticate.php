<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // For API requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            // For web requests, redirect to login
            return redirect('/login')->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}