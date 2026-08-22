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
     * Display the registration view.
     * CRIT-03: Solo muestra el formulario si el registro público está habilitado.
     */
    public function create(): Response|RedirectResponse
    {
        if (! config('auth.allow_public_registration', false)) {
            return redirect()->route('login')
                ->with('error', 'El registro público no está disponible. Contacta al administrador del colegio.');
        }

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     * CRIT-03: Bloquea el registro si ALLOW_PUBLIC_REGISTRATION=false.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // CRIT-03: Verificar si el registro público está habilitado
        if (! config('auth.allow_public_registration', false)) {
            abort(403, 'El registro público no está disponible. Contacta al administrador del colegio.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('student');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
