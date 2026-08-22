<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir al usuario a la página de autenticación de Google.
     */
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Manejar el callback de Google e iniciar sesión web.
     * CRIT-04: No crea usuarios nuevos automáticamente. Solo permite
     * el acceso a usuarios que ya existan en el sistema y hayan sido
     * creados por el administrador.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // CRIT-04: Buscar usuario existente por email
            $user = User::where('email', $googleUser->getEmail())->first();

            // Si el usuario no existe, rechazar el acceso
            if (! $user) {
                Log::warning('Intento de login con Google de usuario no registrado: '.$googleUser->getEmail());

                return redirect()->route('login')->with('error', 'No tienes una cuenta en el sistema. Contacta al administrador del colegio para solicitar acceso.');
            }

            // CRIT-04: Verificar que el usuario tiene al menos un rol asignado
            if ($user->roles->isEmpty()) {
                Log::warning('Usuario sin rol intentó autenticarse con Google: ID '.$user->id);

                return redirect()->route('login')->with('error', 'Tu cuenta no tiene permisos asignados. Contacta al administrador.');
            }

            // Actualizar/vincular el provider de Google si no estaba vinculado
            if (! $user->provider_id || $user->provider !== 'google') {
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            Log::error('Google OAuth Error (web): '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Error al autenticar con Google. Intenta nuevamente.');
        }
    }
}
