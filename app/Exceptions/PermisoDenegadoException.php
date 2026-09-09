<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermisoDenegadoException extends Exception
{
    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(["message" => $this->getMessage()], 403);
        }

        return back()->with("error", $this->getMessage());
    }
}
