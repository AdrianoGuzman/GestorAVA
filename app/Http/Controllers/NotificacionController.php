<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RF-15/RF-17: campana de notificaciones -- lo unico que faltaba para que
 * las notificaciones in-app que ya se generaban (asignacion, retroceso,
 * atraso, proximo vencimiento, etc.) fueran realmente visibles en la
 * plataforma. Endpoints livianos en JSON, consumidos por la campana del
 * header sin navegar fuera de la pagina actual.
 */
class NotificacionController extends Controller
{
    public function __construct(private readonly NotificacionService $notificaciones)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            "notificaciones" => $this->notificaciones->recientesDe($request->user()),
        ]);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        $this->notificaciones->marcarLeida($notificacion, $request->user());

        return response()->json(["success" => true]);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $this->notificaciones->marcarTodasLeidas($request->user());

        return response()->json(["success" => true]);
    }
}
