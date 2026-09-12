<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('mis-tareas.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * RF-01, D2.2: el sistema no debe indicar cual de los dos datos
     * (usuario o contrasena) fue el incorrecto. Una contrasena erronea y un
     * correo inexistente deben producir exactamente el mismo mensaje.
     */
    public function test_login_failure_message_is_generic_regardless_of_which_field_was_wrong()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $mensajeConPasswordInvalida = session('errors')->first('email');

        $this->post('/login', [
            'email' => 'no-existe@ava.cl',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $mensajeConCorreoInexistente = session('errors')->first('email');

        $this->assertSame($mensajeConPasswordInvalida, $mensajeConCorreoInexistente);
        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
