@php
use App\Models\Setting;
@endphp

<!DOCTYPE html>
<html lang="es" style="--primary-color: {{ Setting::get('branding.primary_color', '#2563eb') }};">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Documento')</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .verification {
            margin-top: 30px;
            padding: 10px;
            border: 1px dashed #999;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if (Setting::fileUrlByKey('branding.logo_path'))
            <img src="{{ Setting::fileUrlByKey('branding.logo_path') }}" style="max-height: 60px; margin-bottom: 8px;" alt="Logo">
        @endif
        <h1>@yield('document_title')</h1>
        <p>{{ Setting::get('institution.name', config('app.name', 'Sabere')) }}</p>
    </div>

    @yield('content')

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} — Código de verificación: {{ $hash ?? 'N/A' }}
    </div>
</body>
</html>
