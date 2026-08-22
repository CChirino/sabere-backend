<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // MED-05: Solo compartir datos esenciales del usuario con el frontend.
                // Evitar exponer email_verified_at y otros datos sensibles en el JS del cliente.
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->roles->pluck('name'),
                    'cedula' => $request->user()->cedula,
                    'phone' => $request->user()->phone,
                    'birth_date' => $request->user()->birth_date?->format('Y-m-d'),
                    'avatar' => $request->user()->avatar,
                    'avatar_url' => $request->user()->avatar_url,
                    'bio' => $request->user()->bio,
                    'address' => $request->user()->address,
                    'emergency_contact_name' => $request->user()->emergency_contact_name,
                    'emergency_contact_phone' => $request->user()->emergency_contact_phone,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
