<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     * MED-03: Verifica que el email esté confirmado antes de permitir login.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // MED-03: Verificar que el email esté confirmado
        if (! $user->hasVerifiedEmail()) {
            Auth::logout();

            return $this->sendError(
                'Debes verificar tu correo electrónico antes de iniciar sesión.',
                ['email' => ['Email no verificado.']],
                403
            );
        }

        // Verificar que el usuario tiene al menos un rol
        if ($user->roles->isEmpty()) {
            Auth::logout();

            return $this->sendError(
                'Tu cuenta no tiene permisos asignados. Contacta al administrador del colegio.',
                [],
                403
            );
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->sendResponse(
            [
                'user' => $user->load('roles'),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'Login exitoso'
        );
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->sendResponse(
            [],
            'Sesión cerrada correctamente'
        );
    }
}
