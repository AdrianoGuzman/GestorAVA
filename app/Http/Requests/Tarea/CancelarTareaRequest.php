<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-25: cancelación de tarea con motivo obligatorio.
 */
class CancelarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "motivo" => ["required", "string"],
        ];
    }
}
