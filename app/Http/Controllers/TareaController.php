<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\AgregarColaboradorRequest;
use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Http\Requests\Tarea\ReasignarTareaRequest;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ColaboradorService;
use App\Services\ReasignacionService;
use App\Services\TareaService;
use Illuminate\Http\RedirectResponse;

class TareaController extends Controller
{
    public function __construct(
        private readonly TareaService $tareaService,
        private readonly ReasignacionService $reasignacionService,
        private readonly ColaboradorService $colaboradorService,
    ) {
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
}
