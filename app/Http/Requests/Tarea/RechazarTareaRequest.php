<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-13: rechazo de una tarea con motivo obligatorio (D1.1).
 */
class RechazarTareaRequest extends FormRequest
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
