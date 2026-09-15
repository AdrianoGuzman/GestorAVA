<?php

namespace App\Http\Requests\Proyecto;

use App\Enums\EstadoProyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Edicion de un Proyecto existente: nombre/descripcion, y su estado
 * (activo/cerrado -- "cerrarlo" es esto, no un borrado, para no perder la
 * trazabilidad de las tareas que agrupó).
 */
class ActualizarProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nombre" => ["required", "string", "max:255", Rule::unique("usuarios.proyectos", "nombre")->ignore($this->route("proyecto"))],
            "descripcion" => ["nullable", "string"],
            "estado" => ["required", new Enum(EstadoProyecto::class)],
            "fecha_inicio" => ["required", "date"],
            "fecha_termino" => ["required", "date", "after_or_equal:fecha_inicio"],
        ];
    }
}
