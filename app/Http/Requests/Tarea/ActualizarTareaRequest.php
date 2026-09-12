<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edicion de titulo/descripcion/fechas de una tarea existente. No incluye
 * "after_or_equal:today" en fecha_compromiso a proposito: una tarea ya
 * atrasada conserva su fecha vencida si el usuario solo corrige el titulo,
 * sin verse forzado a mover la fecha para poder guardar.
 */
class ActualizarTareaRequest extends FormRequest
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
            "fecha_compromiso" => ["required", "date"],
        ];
    }
}
