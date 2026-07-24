<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super Admin gets access to all routes
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()->getName();

        if ($routeName) {
            // Map action/endpoint routes to their parent menu route
            $checkRouteName = $routeName;
            if (in_array($routeName, ['dosen.kesediaan.store', 'dosen.kesediaan.destroy'], true)) {
                $checkRouteName = 'dosen.dashboard';
            }

            // Find if this route is defined in active system menus
            // If it is a menu, verify if user has access to it
            $menu = Menu::where('is_active', true)
                ->where('route', $checkRouteName)
                ->first();

            if ($menu) {
                if (!$user->hasMenuAccess($checkRouteName)) {
                    abort(403, 'Anda tidak memiliki hak akses untuk membuka halaman ini.');
                }
            }
        }

        return $next($request);
    }
}
