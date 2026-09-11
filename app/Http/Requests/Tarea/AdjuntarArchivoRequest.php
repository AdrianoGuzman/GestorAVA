<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-19 D1.1: formatos aceptados PDF, imagenes (JPEG/PNG), Word y Excel.
 * Limite de tamano (10MB por archivo) no especificado en la spec -- default
 * razonable, ajustable si hace falta.
 */
class AdjuntarArchivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "archivo" => ["required", "file", "mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx", "max:10240"],
        ];
    }
}
