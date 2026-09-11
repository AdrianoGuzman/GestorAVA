<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-09 D3: filtro_rol opcional para ver una sola sección de Mis tareas.
 */
class MisTareasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "filtro_rol" => ["sometimes", "in:responsable,colaborador,delegadas_por_mi,creadas_por_mi"],
        ];
    }
}
