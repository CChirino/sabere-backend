<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

/**
 * MED-01: Restricts which Host headers the application will trust.
 * Prevents Host Header Injection attacks (e.g., poisoned password reset links).
 */
class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            // Agregar el dominio de producción del colegio aquí:
            // 'sabereapp.com',
            // '*.sabereapp.com',
        ];
    }
}
