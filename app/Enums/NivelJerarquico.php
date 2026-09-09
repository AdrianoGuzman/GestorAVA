<?php

namespace App\Enums;

enum NivelJerarquico: string
{
    case Directorio = 'directorio';
    case Gerencia = 'gerencia';
    case JefeArea = 'jefe_area';
    case Asistente = 'asistente';
}
