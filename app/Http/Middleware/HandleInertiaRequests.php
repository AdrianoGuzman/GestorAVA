<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                // RF-03 D2.1: distintos niveles ven distintas opciones de menu.
                // Se calcula aca (Enum) para no duplicar la regla en el frontend.
                'puedeAdministrarEstructura' => $request->user()?->nivel_jerarquico?->puedeAdministrarEstructura() ?? false,
                // RF-15/RF-17: contador de la campana, disponible en cualquier
                // pagina sin pedirlo aparte -- el detalle de la lista se pide
                // solo al abrir la campana (ver NotificacionController).
                'notificacionesNoLeidas' => $request->user()?->notificacionesRecibidas()->where('leida', false)->count() ?? 0,
                'puedeEliminarUsuarios' => $request->user()?->nivel_jerarquico?->puedeEliminarUsuarios() ?? false,
            ],
        ]);
    }
}
