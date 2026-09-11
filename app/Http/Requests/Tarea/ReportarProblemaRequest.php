<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-13 (rediseñado): reportar que la tarea está mal definida, con motivo
 * obligatorio.
 */
class ReportarProblemaRequest extends FormRequest
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
