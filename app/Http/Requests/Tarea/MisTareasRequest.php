<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-09 D3: filtro_rol opcional para ver un solo rol de Mis tareas, mas
 * busqueda (nombre o codigo) y filtros de estado/atraso/obra para el
 * listado.
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
            "busqueda" => ["sometimes", "nullable", "string", "max:255"],
            "estado" => ["sometimes", "array"],
            "estado.*" => ["string", "in:pendiente,en_progreso,completada,cancelada"],
            "solo_atrasadas" => ["sometimes", "boolean"],
            "unidad_organizacional_id" => ["sometimes", "nullable", "integer", "exists:unidades_organizacionales,id"],
        ];
    }
}
