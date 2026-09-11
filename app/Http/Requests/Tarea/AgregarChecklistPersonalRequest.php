<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

class AgregarChecklistPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "texto" => ["required", "string", "max:255"],
        ];
    }
}
