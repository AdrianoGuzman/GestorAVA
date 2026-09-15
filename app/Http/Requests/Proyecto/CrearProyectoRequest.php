<?php

namespace App\Http\Requests\Proyecto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de un Proyecto (agrupa tareas de varias unidades bajo una misma
 * iniciativa). La autorizacion (Directorio/Gerencia) se valida en
 * ProyectoService, no aqui, siguiendo el mismo patron que Usuario.
 */
class CrearProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nombre" => ["required", "string", "max:255", "unique:usuarios.proyectos,nombre"],
            "descripcion" => ["nullable", "string"],
            // RN: "los proyectos siempre tienen plazo" (Franco, 14-09-2026).
            "fecha_inicio" => ["required", "date"],
            "fecha_termino" => ["required", "date", "after_or_equal:fecha_inicio"],
        ];
    }
}
