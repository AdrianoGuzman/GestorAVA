<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-18: duplicar copia responsable y unidad organizacional (el server los
 * toma de la tarea origen, no se envian aqui); titulo, descripcion y fechas
 * quedan editables.
 */
class DuplicarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "fecha_inicio" => ["nullable", "date"],
            "fecha_compromiso" => ["required", "date", "after_or_equal:today"],
        ];
    }
}
