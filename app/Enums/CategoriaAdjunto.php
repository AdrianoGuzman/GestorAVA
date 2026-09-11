<?php

namespace App\Enums;

/**
 * RF-19: quien sube el archivo elige explicitamente su categoria -- no se
 * infiere de quien lo subio, porque el mismo responsable puede necesitar
 * subir tanto material de referencia como su propia evidencia.
 */
enum CategoriaAdjunto: string
{
    case Necesario = 'necesario';
    case Evidencia = 'evidencia';
}
