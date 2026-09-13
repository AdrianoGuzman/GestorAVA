<?php

namespace App\Services;

use App\Enums\CategoriaAdjunto;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\AdjuntoTarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdjuntoService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
    ) {
    }

    /**
     * RF-19: el responsable o un colaborador adjunta un archivo a la tarea.
     * Se guarda en el disco "local" (privado, fuera de storage/app/public),
     * nunca expuesto por URL directa -- solo se sirve via descargar(),
     * detras del control de acceso de la tarea.
     */
    public function agregar(Tarea $tarea, UploadedFile $archivo, User $solicitante, CategoriaAdjunto $categoria): AdjuntoTarea
    {
        if (! $this->permisos->puedeAdjuntar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se pueden adjuntar archivos a una tarea completada o cancelada."
                    : "Solo el responsable o un colaborador de la tarea puede adjuntar archivos."
            );
        }

        $ruta = $archivo->store("adjuntos-tarea/{$tarea->id}", "local");

        $adjunto = AdjuntoTarea::create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $solicitante->id,
            "nombre_original" => $archivo->getClientOriginalName(),
            "ruta" => $ruta,
            "mime_type" => $archivo->getClientMimeType(),
            "tamano_bytes" => $archivo->getSize(),
            "categoria" => $categoria,
        ]);

        $this->historial->registrar($tarea, TipoEvento::AdjuntoAgregado, $solicitante, [
            "nombre_original" => $adjunto->nombre_original,
        ]);

        return $adjunto;
    }

    public function descargar(AdjuntoTarea $adjunto): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk("local")->download($adjunto->ruta, $adjunto->nombre_original);
    }

    /**
     * Evidencia subida en las tareas hijas (RF-21/22, Oscar) de $tarea,
     * para mostrarla junto a los adjuntos "necesarios" de $tarea sin
     * duplicar el archivo ni tener que reenviarlo por fuera del sistema.
     * Requiere permiso de RF-19 sobre la tarea hija? No -- quien ve la
     * tarea padre tiene interes legitimo en ver que la desbloquea, la
     * descarga sigue yendo contra la tarea hija real (ruta propia).
     */
    public function deTareasHijas(Tarea $tarea): Collection
    {
        return $tarea->tareasHijas()
            ->with(["adjuntos" => fn ($query) => $query->where("categoria", CategoriaAdjunto::Evidencia)->with("usuario")])
            ->get()
            ->flatMap(fn (Tarea $hija) => $hija->adjuntos->map(fn (AdjuntoTarea $adjunto) => [
                "id" => $adjunto->id,
                "tarea_id" => $adjunto->tarea_id,
                "nombre_original" => $adjunto->nombre_original,
                "mime_type" => $adjunto->mime_type,
                "tamano_bytes" => $adjunto->tamano_bytes,
                "categoria" => $adjunto->categoria,
                "created_at" => $adjunto->created_at,
                "usuario" => $adjunto->usuario,
                "tarea_hija_titulo" => $hija->titulo,
            ]))
            ->values();
    }
}
