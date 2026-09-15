<?php

namespace App\Http\Controllers;

use App\Enums\CategoriaAdjunto;
use App\Http\Requests\Tarea\ActualizarTareaRequest;
use App\Http\Requests\Tarea\AdjuntarArchivoRequest;
use App\Http\Requests\Tarea\AgregarColaboradorRequest;
use App\Http\Requests\Tarea\CancelarTareaRequest;
use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Http\Requests\Tarea\ReasignarTareaRequest;
use App\Http\Requests\Tarea\ReportarNoParticipacionRequest;
use App\Http\Requests\Tarea\ReportarProblemaRequest;
use App\Http\Requests\Tarea\RetrocederTareaRequest;
use App\Models\AdjuntoTarea;
use App\Models\ChecklistPersonalItem;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\AdjuntoService;
use App\Services\CancelacionService;
use App\Services\ColaboradorService;
use App\Services\FinalizacionService;
use App\Services\MisTareasService;
use App\Services\NoParticipacionService;
use App\Services\PermisosService;
use App\Services\ReasignacionService;
use App\Services\ReporteProblemaService;
use App\Services\RetrocesoService;
use App\Services\TareaService;
use App\Services\TransicionAutomaticaService;
use Illuminate\Http\JsonResponse;
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
            "proyecto",
            "seccion",
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
            "proyectos" => Proyecto::orderBy("nombre")->get(["id", "nombre", "fecha_inicio", "fecha_termino"]),
            "secciones" => Seccion::orderBy("nombre")->get(["id", "nombre", "proyecto_id"]),
            "checklistPersonal" => ChecklistPersonalItem::where("tarea_id", $tarea->id)
                ->where("usuario_id", $usuario->id)
                ->orderBy("created_at")
                ->get(),
            "adjuntosDeTareasHijas" => $this->adjuntoService->deTareasHijas($tarea),
            "permisos" => [
                "puedeReasignar" => $this->permisos->puedeReasignar($tarea, $usuario),
                "puedeAgregarColaborador" => $this->permisos->puedeAgregarColaborador($tarea, $usuario),
                "puedeCompletar" => $this->permisos->puedeMostrarCompletar($tarea, $usuario),
                "puedeRetroceder" => $this->permisos->puedeRetroceder($tarea, $usuario),
                "puedeReportarProblema" => $this->permisos->puedeReportarProblema($tarea, $usuario),
                "puedeReportarNoParticipacion" => $this->permisos->puedeReportarNoParticipacion($tarea, $usuario),
                "puedeCancelar" => $this->permisos->puedeMostrarCancelar($tarea, $usuario),
                "puedeAdjuntar" => $this->permisos->puedeAdjuntar($tarea, $usuario),
                "puedeUsarChecklistPersonal" => $this->permisos->puedeUsarChecklistPersonal($tarea, $usuario),
                "puedeUsarChecklist" => $this->permisos->puedeUsarChecklist($tarea, $usuario),
                "puedeAsignarDuenoChecklist" => $this->permisos->puedeAsignarDuenoChecklist($tarea, $usuario),
                "puedeCrearTareaHija" => $this->permisos->puedeCrearTareaHija($tarea, $usuario),
                "puedeEditar" => $this->permisos->puedeMostrarEditar($tarea, $usuario),
            ],
        ]);
    }

    public function actualizar(ActualizarTareaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->tareaService->actualizar($tarea, $request->validated(), $request->user());

        return $this->exito("Tarea actualizada correctamente.");
    }

    public function agregarAdjunto(AdjuntarArchivoRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $categoria = CategoriaAdjunto::from($request->validated("categoria"));

        $this->adjuntoService->agregar($tarea, $request->file("archivo"), $request->user(), $categoria);

        return $this->exito("Archivo adjuntado correctamente.");
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

    public function reasignar(ReasignarTareaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
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

        return $this->exito("Responsable reasignado correctamente.");
    }

    public function agregarColaborador(AgregarColaboradorRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->colaboradorService->agregar($tarea, $request->validated("colaboradores"), $request->user());

        return $this->exito("Colaborador(es) agregado(s) correctamente.");
    }

    public function completar(Request $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->finalizacionService->completar($tarea, $request->user());

        return $this->exito("Tarea marcada como completada.");
    }

    public function retroceder(RetrocederTareaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->retrocesoService->retroceder($tarea, $request->user(), $request->validated("motivo"));

        return $this->exito("Tarea retrocedida a Pendiente.");
    }

    public function reportarProblema(ReportarProblemaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->reporteProblemaService->reportar($tarea, $request->user(), $request->validated("motivo"));

        return $this->exito("Problema reportado correctamente.");
    }

    public function cancelar(CancelarTareaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->cancelacionService->cancelar($tarea, $request->user(), $request->validated("motivo"));

        return $this->exito("Tarea cancelada.");
    }

    public function reportarNoParticipacion(ReportarNoParticipacionRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->noParticipacionService->reportar($tarea, $request->user(), $request->validated("motivo"));

        return $this->exito("Aviso enviado correctamente.");
    }
}
