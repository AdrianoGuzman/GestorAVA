<?php

namespace App\Http\Controllers;

use App\Enums\CategoriaAdjunto;
use App\Http\Requests\Tarea\ActualizarTareaRequest;
use App\Http\Requests\Tarea\AdjuntarArchivoRequest;
use App\Http\Requests\Tarea\AgregarColaboradorRequest;
use App\Http\Requests\Tarea\CancelarTareaRequest;
use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Http\Requests\Tarea\DuplicarTareaRequest;
use App\Http\Requests\Tarea\ReasignarTareaRequest;
use App\Http\Requests\Tarea\ReportarNoParticipacionRequest;
use App\Http\Requests\Tarea\ReportarProblemaRequest;
use App\Http\Requests\Tarea\RetrocederTareaRequest;
use App\Models\AdjuntoTarea;
use App\Models\ChecklistPersonalItem;
use App\Models\Tarea;
use App\Models\User;
use App\Services\AdjuntoService;
use App\Services\CancelacionService;
use App\Services\ColaboradorService;
use App\Services\DuplicarTareaService;
use App\Services\FinalizacionService;
use App\Services\MisTareasService;
use App\Services\NoParticipacionService;
use App\Services\PermisosService;
use App\Services\ReasignacionService;
use App\Services\ReporteProblemaService;
use App\Services\RetrocesoService;
use App\Services\TareaService;
use App\Services\TransicionAutomaticaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TareaController extends Controller
{
    public function __construct(
        private readonly TareaService $tareaService,
        private readonly ReasignacionService $reasignacionService,
        private readonly ColaboradorService $colaboradorService,
        private readonly TransicionAutomaticaService $transicionAutomatica,
        private readonly FinalizacionService $finalizacionService,
        private readonly RetrocesoService $retrocesoService,
        private readonly ReporteProblemaService $reporteProblemaService,
        private readonly NoParticipacionService $noParticipacionService,
        private readonly CancelacionService $cancelacionService,
        private readonly AdjuntoService $adjuntoService,
        private readonly PermisosService $permisos,
        private readonly MisTareasService $misTareasService,
        private readonly DuplicarTareaService $duplicarTareaService,
    ) {
    }

    /**
     * RF-24: vista de detalle unico de la tarea. Abrirla dispara la
     * transicion automatica a "en progreso" cuando corresponde (RF-10).
     */
    public function show(Request $request, Tarea $tarea): Response
    {
        $usuario = $request->user();

        $tarea = $this->transicionAutomatica->procesarApertura($tarea, $usuario);
        $tarea->load([
            "responsable",
            "colaboradores",
            "creador",
            "adjuntos" => fn ($query) => $query->with("usuario")->latest("created_at"),
            "checklistItems" => fn ($query) => $query->with("dueno")->orderBy("created_at"),
            "tareasHijas" => fn ($query) => $query->with("responsable")->orderBy("created_at"),
            "historial" => function ($query) {
                $query->with("usuario")->orderBy("created_at");
            },
        ]);

        return Inertia::render("tareas/show", [
            "tarea" => $tarea,
            "rolUsuario" => $this->misTareasService->rolDe($tarea, $usuario),
            "usuarios" => User::select(["id", "nombre_1", "nombre_2", "apellido_1", "apellido_2", "email"])->get(),
            "checklistPersonal" => ChecklistPersonalItem::where("tarea_id", $tarea->id)
                ->where("usuario_id", $usuario->id)
                ->orderBy("created_at")
                ->get(),
            "adjuntosDeTareasHijas" => $this->adjuntoService->deTareasHijas($tarea),
            "permisos" => [
                "puedeReasignar" => $this->permisos->puedeReasignar($tarea, $usuario),
                "puedeAgregarColaborador" => $this->permisos->puedeAgregarColaborador($tarea, $usuario),
                "puedeCompletar" => $this->permisos->puedeCompletar($tarea, $usuario),
                "puedeRetroceder" => $this->permisos->puedeRetroceder($tarea, $usuario),
                "puedeReportarProblema" => $this->permisos->puedeReportarProblema($tarea, $usuario),
                "puedeReportarNoParticipacion" => $this->permisos->puedeReportarNoParticipacion($tarea, $usuario),
                "puedeCancelar" => $this->permisos->puedeCancelar($tarea, $usuario),
                "puedeAdjuntar" => $this->permisos->puedeAdjuntar($tarea, $usuario),
                "puedeUsarChecklistPersonal" => $this->permisos->puedeUsarChecklistPersonal($tarea, $usuario),
                "puedeUsarChecklist" => $this->permisos->puedeUsarChecklist($tarea, $usuario),
                "puedeAsignarDuenoChecklist" => $this->permisos->puedeAsignarDuenoChecklist($tarea, $usuario),
                "puedeCrearTareaHija" => $this->permisos->puedeCrearTareaHija($tarea, $usuario),
                "puedeEditar" => $this->permisos->puedeEditar($tarea, $usuario),
                // RF-18: duplicar no tiene restriccion de rol en la spec, cualquiera
                // con acceso al detalle puede hacerlo.
                "puedeDuplicar" => true,
            ],
        ]);
    }

    public function actualizar(ActualizarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->tareaService->actualizar($tarea, $request->validated(), $request->user());

        return back()->with("success", "Tarea actualizada correctamente.");
    }

    public function agregarAdjunto(AdjuntarArchivoRequest $request, Tarea $tarea): RedirectResponse
    {
        $categoria = CategoriaAdjunto::from($request->validated("categoria"));

        $this->adjuntoService->agregar($tarea, $request->file("archivo"), $request->user(), $categoria);

        return back()->with("success", "Archivo adjuntado correctamente.");
    }

    public function descargarAdjunto(Tarea $tarea, AdjuntoTarea $adjunto): StreamedResponse
    {
        abort_unless($adjunto->tarea_id === $tarea->id, 404);

        return $this->adjuntoService->descargar($adjunto);
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

    public function retroceder(RetrocederTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->retrocesoService->retroceder($tarea, $request->user(), $request->validated("motivo"));

        return back()->with("success", "Tarea retrocedida a Pendiente.");
    }

    public function reportarProblema(ReportarProblemaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->reporteProblemaService->reportar($tarea, $request->user(), $request->validated("motivo"));

        return back()->with("success", "Problema reportado correctamente.");
    }

    public function cancelar(CancelarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->cancelacionService->cancelar($tarea, $request->user(), $request->validated("motivo"));

        return back()->with("success", "Tarea cancelada.");
    }

    public function reportarNoParticipacion(ReportarNoParticipacionRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->noParticipacionService->reportar($tarea, $request->user(), $request->validated("motivo"));

        return back()->with("success", "Aviso enviado correctamente.");
    }

    public function duplicar(DuplicarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $nueva = $this->duplicarTareaService->duplicar($tarea, $request->validated(), $request->user());

        return redirect()->route("tareas.show", $nueva)->with("success", "Tarea duplicada como \"{$nueva->titulo}\".");
    }
}
