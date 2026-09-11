<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\AdjuntoTarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
    public function agregar(Tarea $tarea, UploadedFile $archivo, User $solicitante): AdjuntoTarea
    {
        if (! $this->permisos->puedeAdjuntar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede adjuntar archivos."
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
}
