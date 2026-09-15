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

    /**
     * Inertia serializa un booleano JS como el string literal "true"/"false"
     * en la query string -- la regla "boolean" de Laravel no acepta esas
     * palabras (solo '0'/'1'/0/1/true/false), asi que sin esto la peticion
     * fallaba validacion en silencio y el filtro nunca se aplicaba.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has("solo_atrasadas")) {
            $this->merge([
                "solo_atrasadas" => filter_var($this->input("solo_atrasadas"), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            "filtro_rol" => ["sometimes", "in:responsable,colaborador,delegadas_por_mi,creadas_por_mi"],
            "busqueda" => ["sometimes", "nullable", "string", "max:255"],
            "estado" => ["sometimes", "array"],
            "estado.*" => ["string", "in:pendiente,en_progreso,completada,cancelada"],
            "prioridad" => ["sometimes", "array"],
            "prioridad.*" => ["string", "in:alta,media,baja"],
            "solo_atrasadas" => ["sometimes", "boolean"],
            "unidad_organizacional_id" => ["sometimes", "nullable", "integer", "exists:unidades_organizacionales,id"],
            "proyecto_id" => ["sometimes", "nullable", "integer", "exists:proyectos,id"],
        ];
    }
}
