<?php

namespace GiocoPlus\Admin\Middleware;

use Closure;
use GiocoPlus\Admin\Facades\Admin;
use Illuminate\Http\Request;

class Bootstrap
{
    public function handle(Request $request, Closure $next)
    {
        Admin::bootstrap();

        return $next($request);
    }
}
