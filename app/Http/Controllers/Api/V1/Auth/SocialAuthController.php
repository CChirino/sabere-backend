<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     * CRIT-04: No crea usuarios nuevos automáticamente. Solo permite
     * el acceso a usuarios que ya existan en el sistema y hayan sido
     * creados por el administrador.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // CRIT-04: Buscar usuario existente por email
            $user = User::where('email', $googleUser->getEmail())->first();

            // Si el usuario no existe, rechazar el acceso
            if (! $user) {
                Log::warning('Intento de login con Google de usuario no registrado: '.$googleUser->getEmail());

                return $this->sendError(
                    'No tienes una cuenta en el sistema. Contacta al administrador del colegio para solicitar acceso.',
                    [],
                    403
                );
            }

            // CRIT-04: Verificar que el usuario tiene al menos un rol asignado
            if ($user->roles->isEmpty()) {
                Log::warning('Usuario sin rol intentó autenticarse con Google: ID '.$user->id);

                return $this->sendError(
                    'Tu cuenta no tiene permisos asignados. Contacta al administrador.',
                    [],
                    403
                );
            }

            // Actualizar/vincular el provider de Google si no estaba vinculado
            if (! $user->provider_id || $user->provider !== 'google') {
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            $token = $user->createToken('auth-token-google')->plainTextToken;

            return $this->sendResponse(
                [
                    'user' => $user->load('roles'),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
                'Login exitoso con Google'
            );

        } catch (\Exception $e) {
            Log::error('Google OAuth Error: '.$e->getMessage());

            return $this->sendError(
                'Error al autenticar con Google. Intenta nuevamente.',
                [],
                500
            );
        }
    }
}
