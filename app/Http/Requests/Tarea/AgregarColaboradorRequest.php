<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-06: agregar uno o mas colaboradores a una tarea.
 */
class AgregarColaboradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "colaboradores" => ["required", "array", "min:1"],
            "colaboradores.*" => ["integer", "exists:users,id"],
        ];
    }
}
