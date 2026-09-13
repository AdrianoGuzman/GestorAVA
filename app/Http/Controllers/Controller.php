<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Redirect clasico (back()) para requests normales -- o un JSON minimo
     * para las acciones disparadas desde dentro del modal de detalle de
     * tarea (ver tarea-detalle-modal.tsx). Ahi la pagina Inertia "actual"
     * sigue siendo Mis Tareas (el modal nunca navega de verdad), asi que un
     * back() real dispara una recarga completa -- aunque invisible, detras
     * del modal -- de esa pagina en el medio de cada accion. El modal pide
     * JSON explicitamente (Accept: application/json) para evitar ese salto
     * y refrescar el detalle el mismo con una sola peticion propia.
     * Mismo patron que PermisoDenegadoException::render().
     */
    protected function exito(string $mensaje): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(["success" => true, "message" => $mensaje]);
        }

        return back()->with("success", $mensaje);
    }
}
