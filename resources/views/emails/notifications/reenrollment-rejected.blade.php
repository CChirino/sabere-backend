<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinscripción Rechazada</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #8b4513;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .error-badge {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #8b4513;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
        }
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
        }
        .reason-box {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Reinscripción Rechazada</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>

            <div class="error-badge">
                Tu solicitud de reinscripción ha sido rechazada
            </div>

            <div class="info-item">
                <div class="info-label">Período Académico</div>
                <div class="info-value">{{ $academicPeriod }}</div>
            </div>

            @if($reason)
            <div class="reason-box">
                <div class="info-label">Motivo del rechazo</div>
                <div class="info-value">{{ $reason }}</div>
            </div>
            @endif

            <p>Si tienes alguna duda o deseas más información sobre esta decisión, por favor contacta a la administración escolar.</p>

            <center>
                <a href="{{ url('/contact') }}" class="btn">Contactar administración</a>
            </center>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }} - Sistema de Gestión Académica</p>
            <p>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
