<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "No puedo/quiero ser parte de esto": motivo obligatorio.
 */
class ReportarNoParticipacionRequest extends FormRequest
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
