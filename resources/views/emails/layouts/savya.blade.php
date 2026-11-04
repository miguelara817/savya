<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notificación SAVYA' }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f6f8; color: #263746; font-family: Arial, Helvetica, sans-serif; }
        .email-shell { width: 100%; padding: 32px 12px; background-color: #f3f6f8; }
        .email-card { width: 100%; max-width: 640px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dce5ea; border-radius: 10px; }
        .email-header { padding: 28px 32px 22px; text-align: center; border-bottom: 4px solid #0b6078; }
        .email-logo { display: block; width: 210px; max-width: 100%; height: auto; margin: 0 auto 18px; }
        .email-title { margin: 0; color: #0b6078; font-size: 20px; }
        .email-content { padding: 28px 32px 12px; font-size: 15px; line-height: 1.7; }
        .email-content p { margin: 0 0 18px; }
        .email-content h1, .email-content h2 { margin: 0 0 12px; color: #0b6078; font-size: 17px; }
        .email-highlight { padding: 16px 18px; background-color: #f1f7f8; border-left: 4px solid #0b6078; }
        .email-footer { padding: 16px 32px; background-color: #f7f9fa; border-top: 1px solid #e3eaee; color: #71818b; text-align: center; font-size: 12px; line-height: 1.6; }
        .verification-code { padding: 16px; background-color: #f1f7f8; border: 1px dashed #0b6078; color: #0b6078; font-size: 28px; font-weight: bold; letter-spacing: 8px; text-align: center; }
        @media screen and (max-width: 480px) {
            .email-header, .email-content { padding-right: 20px !important; padding-left: 20px !important; }
            .email-footer { padding-right: 20px !important; padding-left: 20px !important; }
        }
    </style>
</head>
<body>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-shell">
        <tr>
            <td>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-card">
                    <tr>
                        <td class="email-header">
                            <img class="email-logo" src="{{ asset('images/01_SAVYA_horizontal_color_transparente.png') }}" alt="SAVYA" width="210">
                            <h1 class="email-title">{{ $title ?? 'Notificación SAVYA' }}</h1>
                            @isset($subtitle)
                                <p style="margin:6px 0 0; color:#617481; font-size:14px;">{{ $subtitle }}</p>
                            @endisset
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="email-footer">Este es un mensaje automático de SAVYA. Por favor, no responda a este correo.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
