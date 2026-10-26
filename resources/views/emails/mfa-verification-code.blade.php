<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Código de verificación</title>
</head>
<body>
    <h2>Verificación de inicio de sesión</h2>

    <p>Hola,</p>

    <p>Usa el siguiente código para completar tu inicio de sesión en SAVYA:</p>

    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">
        {{ $code }}
    </p>

    <p>El código vence en {{ $codeExpiryMinutes }} minutos. Si no solicitaste este código, puedes ignorar este mensaje.</p>
</body>
</html>
