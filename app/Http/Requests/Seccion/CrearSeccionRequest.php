<?php

namespace App\Http\Requests\Seccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de una Seccion dentro de un Proyecto. La autorizacion
 * (Directorio/Gerencia) se valida en ProyectoService, no aqui.
 */
class CrearSeccionRequest extends FormRequest
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
