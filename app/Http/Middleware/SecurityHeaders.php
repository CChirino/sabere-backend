<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MED-02: Agrega cabeceras de seguridad HTTP a todas las respuestas.
 * Protege contra clickjacking, XSS, content sniffing y data leaks.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir que la app se cargue dentro de un iframe (anti-clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevenir que el navegador "adivine" el tipo MIME del contenido
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Controlar cuánta información se envía en el Referer header
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Deshabilitar funcionalidades del navegador que no se usan
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Forzar HTTPS en producción
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        // Ajustar según los recursos externos que use la aplicación
        [$viteHttp, $viteWs] = $this->getViteDevOrigins();

        $csp = implode('; ', [
            "default-src 'self'",
            trim("script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteHttp}"), // unsafe-eval necesario para Vue/Inertia dev
            trim("style-src 'self' 'unsafe-inline' fonts.bunny.net {$viteHttp}"),
            "font-src 'self' fonts.bunny.net data:",
            "img-src 'self' data: blob:",
            trim("connect-src 'self' {$this->getReverbWsUrl()} {$viteHttp} {$viteWs}"),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * En desarrollo, permite el dev server de Vite (lee public/hot para
     * conocer su origen: http para scripts/estilos y ws para HMR).
     *
     * @return array{0: string, 1: string}
     */
    private function getViteDevOrigins(): array
    {
        if (config('app.env') === 'production') {
            return ['', ''];
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return ['', ''];
        }

        $url = rtrim(trim((string) file_get_contents($hotFile)), '/');

        if (! preg_match('#^https?://#', $url)) {
            return ['', ''];
        }

        $ws = preg_replace('#^http#', 'ws', $url);

        return [$url, $ws];
    }

    /**
     * Obtiene la URL de WebSocket de Reverb para incluirla en CSP.
     */
    private function getReverbWsUrl(): string
    {
        $host = config('reverb.apps.apps.0.options.host', 'localhost');
        $port = config('reverb.apps.apps.0.options.port', 8080);

        // Permitir ambos esquemas: el cliente decide ws/wss según VITE_REVERB_SCHEME
        return "ws://{$host}:{$port} wss://{$host}:{$port}";
    }
}
