<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            "nombre_1" => "required|string|max:255",
            "nombre_2" => "required|string|max:255",
            "apellido_1" => "required|string|max:255",
            "apellido_2" => "required|string|max:255",
            "cargo" => "required|string|max:255",
            "rut" => "required|string|max:255|unique:".User::class,
            "email" => "required|string|lowercase|email|max:255|unique:".User::class,
            "password" => ["required", "confirmed", Rules\Password::defaults()],
        ]);

        $user = User::create([
            "nombre_1" => $request->nombre_1,
            "nombre_2" => $request->nombre_2,
            "apellido_1" => $request->apellido_1,
            "apellido_2" => $request->apellido_2,
            "cargo" => $request->cargo,
            "rut" => $request->rut,
            "email" => $request->email,
            "password" => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
