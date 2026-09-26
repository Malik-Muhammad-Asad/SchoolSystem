<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchoolExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($user && $tenant && !$user->is_super_admin) {
            if ($tenant->expiry_date && \Carbon\Carbon::parse($tenant->expiry_date)->isPast()) {
                auth()->logout();
                return redirect()->route('filament.admin.auth.login')->with('error', 'Your school subscription has expired. Please contact the administrator.');
            }
        }

        return $next($request);
    }
}
