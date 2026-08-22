<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Handle a registration request for the application.
     * CRIT-03: El registro API también respeta ALLOW_PUBLIC_REGISTRATION.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        // CRIT-03: Bloquear registro público si no está habilitado
        if (! config('auth.allow_public_registration', false)) {
            return $this->sendError(
                'El registro público no está disponible. Contacta al administrador del colegio.',
                [],
                403
            );
        }

        $user = $this->create($request->validated());
        $user->assignRole('student');

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->sendResponse(
            [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'Registro exitoso',
            201
        );
    }
}
