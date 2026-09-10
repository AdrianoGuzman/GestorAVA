<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\AgregarColaboradorRequest;
use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Http\Requests\Tarea\ReasignarTareaRequest;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ColaboradorService;
use App\Services\FinalizacionService;
use App\Services\ReasignacionService;
use App\Services\TareaService;
use App\Services\TransicionAutomaticaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function __construct(
        private readonly TareaService $tareaService,
        private readonly ReasignacionService $reasignacionService,
        private readonly ColaboradorService $colaboradorService,
        private readonly TransicionAutomaticaService $transicionAutomatica,
        private readonly FinalizacionService $finalizacionService,
    ) {
    }

    /**
     * RF-10: abrir el detalle dispara la transicion automatica a "en
     * progreso" cuando corresponde. Endpoint minimo por ahora (responde
     * JSON) -- RF-24 lo va a reemplazar por la vista de detalle real.
     */
    public function show(Request $request, Tarea $tarea): JsonResponse
    {
        $tarea = $this->transicionAutomatica->procesarApertura($tarea, $request->user());

        return response()->json($tarea);
    }

    public function store(CrearTareaRequest $request): RedirectResponse
    {
        $tarea = $this->tareaService->crear($request->validated(), $request->user());

        return back()->with("success", "Tarea \"{$tarea->titulo}\" creada correctamente.");
    }

    public function reasignar(ReasignarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $datos = $request->validated();
        $nuevoResponsable = User::findOrFail($datos["nuevo_responsable_id"]);
        $mantenerComoColaborador = $datos["mantener_como_colaborador"] ?? false;

        if ($datos["es_excepcion"] ?? false) {
            $this->reasignacionService->reasignarComoExcepcion(
                $tarea,
                $nuevoResponsable,
                $request->user(),
                $datos["motivo_excepcion"],
                $mantenerComoColaborador,
            );
        } else {
            $this->reasignacionService->reasignar($tarea, $nuevoResponsable, $request->user(), $mantenerComoColaborador);
        }

        return back()->with("success", "Responsable reasignado correctamente.");
    }

    public function agregarColaborador(AgregarColaboradorRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->colaboradorService->agregar($tarea, $request->validated("colaboradores"), $request->user());

        return back()->with("success", "Colaborador(es) agregado(s) correctamente.");
    }

    public function completar(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->finalizacionService->completar($tarea, $request->user());

        return back()->with("success", "Tarea marcada como completada.");
    }
}
