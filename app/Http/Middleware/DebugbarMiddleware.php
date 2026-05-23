<?php

namespace App\Http\Middleware;

use Closure;
use Debugbar;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugbarMiddleware
{
    /**
     * Maneja cada request entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * Rutas donde NO queremos mostrar Debugbar.
         */
        $excludedRoutes = [
            'login',
            'register',
            'password.request'
        ];

        /**
         * Obtiene el nombre de la ruta actual.
         * Usamos ?-> para evitar errores si la ruta es null.
         */
        $routeName = $request->route()?->getName();

        /**
         * Si la ruta está excluida, deshabilitamos Debugbar.
         */
        if ($routeName && in_array($routeName, $excludedRoutes)) {
            Debugbar::disable();
        }

        /**
         * Si NO hay usuario autenticado:
         * - Deshabilitamos Debugbar
         * - Continuamos el request
         */
        if (!auth()->check()) {
            Debugbar::disable();

            return $next($request);
        }

        /**
         * Obtiene los roles desde sesión.
         *
         * Antes:
         * session()->get('roles')['roles']
         *
         * Eso rompía cuando "roles" era null.
         *
         * Ahora:
         * - Obtiene roles de forma segura
         * - Si no existe, devuelve []
         */
        $roles = session()->get('roles.roles', []);

        /**
         * Validamos que realmente sea un array.
         */
        if (!is_array($roles)) {
            $roles = [];
        }

        /**
         * Si el usuario NO tiene rol admin (1),
         * ocultamos Debugbar.
         */
        if (!in_array(1, $roles)) {
            Debugbar::disable();
        }

        /**
         * Continúa el flujo normal del request.
         */
        return $next($request);
    }
}
