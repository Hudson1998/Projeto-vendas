<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        PageVisit::create([
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'user_id' => $request->user()?->id,
            'created_at' => now(),
        ]);

        return $next($request);
    }
}
