<?php

namespace App\Enums;

enum TipoUnidad: string
{
    case Directorio = 'directorio';
    case Gerencia = 'gerencia';
    case Obra = 'obra';
    case Area = 'area';
}
