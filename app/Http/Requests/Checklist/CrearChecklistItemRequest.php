<?php

namespace App\Http\Requests\Checklist;

use Illuminate\Foundation\Http\FormRequest;

class CrearChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "texto" => ["required", "string", "max:255"],
            "dueno_id" => ["nullable", "integer", "exists:users,id"],
        ];
    }
}
