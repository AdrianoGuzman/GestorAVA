<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-12: retroceso de En progreso a Pendiente, con motivo obligatorio (D1.1).
 */
class RetrocederTareaRequest extends FormRequest
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
