
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tus credenciales</title>
</head>
<body>
    <h2>Tus credenciales de acceso</h2>

    <p>Hola,</p>

    <p>Estas son tus credenciales de acceso:</p>

    <p>
        <strong>Usuario:</strong>
        {{ $username }}
    </p>

    <p>
        <strong>Contraseña:</strong>
        {{ $password }}
    </p>

    {{-- <p>Por seguridad, cambia tu contraseña después de ingresar.</p> --}}
</body>
</html>
