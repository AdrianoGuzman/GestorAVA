<?php

namespace App\Http\Requests\Seccion;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarSeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nombre" => ["required", "string", "max:255"],
            "peso" => ["required", "numeric", "min:0", "max:1"],
        ];
    }
}
