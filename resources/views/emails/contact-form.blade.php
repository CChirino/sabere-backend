<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje de Contacto</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding-bottom: 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color: #2dd4bf; width: 36px; height: 36px; border-radius: 8px; text-align: center; vertical-align: middle;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </td>
                                    <td style="padding-left: 10px; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.025em;">
                                        Saberé
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">

                            <!-- Header -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 100%); padding: 28px 32px;">
                                        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: -0.025em;">
                                            📩 Nuevo Mensaje de Contacto
                                        </h1>
                                        <p style="margin: 6px 0 0; font-size: 14px; color: #0f172a; opacity: 0.8;">
                                            Recibido desde el formulario de sabereapp.com
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Content -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding: 32px;">
                                <!-- Name -->
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155;">
                                            <tr>
                                                <td style="padding: 16px 20px;">
                                                    <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Nombre</p>
                                                    <p style="margin: 0; font-size: 16px; font-weight: 600; color: #f1f5f9;">{{ $data['name'] }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Institution -->
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155;">
                                            <tr>
                                                <td style="padding: 16px 20px;">
                                                    <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Institución Educativa</p>
                                                    <p style="margin: 0; font-size: 16px; font-weight: 600; color: #f1f5f9;">{{ $data['institution'] ?? 'No especificada' }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Email & Phone Row -->
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="48%" style="vertical-align: top;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155;">
                                                        <tr>
                                                            <td style="padding: 16px 20px;">
                                                                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Correo</p>
                                                                <a href="mailto:{{ $data['email'] }}" style="font-size: 14px; font-weight: 600; color: #2dd4bf; text-decoration: none;">{{ $data['email'] }}</a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="4%"></td>
                                                <td width="48%" style="vertical-align: top;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155;">
                                                        <tr>
                                                            <td style="padding: 16px 20px;">
                                                                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Teléfono</p>
                                                                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #f1f5f9;">{{ $data['phone'] ?? 'No proporcionado' }}</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Message -->
                                <tr>
                                    <td style="padding-bottom: 28px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <p style="margin: 0 0 8px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Mensaje</p>
                                                    <p style="margin: 0; font-size: 15px; color: #cbd5e1; line-height: 1.6; white-space: pre-wrap;">{{ $data['message'] }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- CTA Button -->
                                <tr>
                                    <td align="center">
                                        <a href="mailto:{{ $data['email'] }}" style="display: inline-block; background-color: #2dd4bf; color: #0f172a; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 12px;">
                                            Responder a {{ $data['name'] }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 28px;">
                            <p style="margin: 0 0 6px; font-size: 12px; color: #64748b;">
                                Este correo fue enviado automáticamente desde el formulario de contacto de
                            </p>
                            <p style="margin: 0 0 16px; font-size: 12px; color: #64748b;">
                                <a href="{{ config('app.url') }}" style="color: #2dd4bf; text-decoration: none; font-weight: 600;">sabereapp.com</a>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #475569;">
                                &copy; {{ date('Y') }} Saberé Technologies. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
