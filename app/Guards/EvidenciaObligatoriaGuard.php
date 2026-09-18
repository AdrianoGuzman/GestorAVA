<?php

namespace App\Guards;

use App\Contracts\GuardCompletarTareaInterface;
use App\Enums\CategoriaAdjunto;
use App\Models\Tarea;

/**
 * AVA Montajes (17-09-2026): si la tarea se marco como "evidencia
 * obligatoria" al crearla o editarla, no se puede completar hasta que
 * alguien suba un adjunto categoria "necesario" (ver adjuntos-section.tsx,
 * ahora etiquetado "Obligatorio" en la UI). Sin adjuntos.section propia --
 * este guard solo revisa que exista al menos uno de esa categoria.
 */
class EvidenciaObligatoriaGuard implements GuardCompletarTareaInterface
{
    public function verificar(Tarea $tarea): array
    {
        if (! $tarea->evidencia_obligatoria) {
            return [];
        }

        $tieneAdjuntoObligatorio = $tarea->adjuntos()
            ->where("categoria", CategoriaAdjunto::Necesario)
            ->exists();

        if ($tieneAdjuntoObligatorio) {
            return [];
        }

        return ["Esta tarea requiere un archivo obligatorio antes de completarse."];
    }
}
