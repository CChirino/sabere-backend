<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(Str::random(24)),
                    'email_verified_at' => now(),
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                ]
            );

            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->sendResponse(
                [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
                'Login exitoso con Google'
            );

        } catch (\Exception $e) {
            return $this->sendError(
                'Error al autenticar con Google',
                ['error' => $e->getMessage()],
                401
            );
        }
    }
}
