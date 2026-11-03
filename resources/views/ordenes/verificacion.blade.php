<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de pedido</title>
    <style>
        body { color: #082d44; font: 16px Arial, sans-serif; margin: 2rem auto; max-width: 900px; padding: 0 1rem; }
        h1, h2 { color: #082d44; }
        table { border-collapse: collapse; margin-top: 1rem; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: .6rem; text-align: left; }
        th { background: #f3f4f6; }
        .valid { background: #082d44; color: white; border: 1px solid; padding: 1rem; }
    </style>
</head>
<body>
    <h1>Pedido original verificado</h1>
    <p class="valid">Este documento corresponde a un pedido registrado en INSUMOS BOLIVIA - SAVYA.</p>
    <h2>Datos del pedido</h2>
    <table>
        <tbody>
            <tr>
                <th>Código</th>
                <td>{{ $orden->codigo_orden }}</td><th>Fecha</th><td>{{ $orden->fecha_registro?->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Almacén</th>
                <td colspan="3">{{ $orden->almacen?->nombre }}</td>
            </tr>
            <tr>
                <th>Solicitante</th>
                <td colspan="3">{{ $orden->solicitante->persona->nombre}} {{ $orden->solicitante->persona?->apellido }}</td>
                
            </tr>
            <tr>
                <th>Cantidad total</th>
                <td>{{ number_format((int) $orden->cantidad_total, 0, ',', '.') }}</td>

                <th>Precio total</th>
                <td>Bs {{ number_format((float) $orden->precio_total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    <h2>Productos</h2>
    <table>
        <thead><tr><th>Producto</th><th>Presentación</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach ($orden->productos as $linea)
                <tr>
                    <td>{{ $linea->producto?->nombre }}</td>
                    <td>{{ $linea->producto?->presentacion }}</td>
                    <td>{{ number_format((int) $linea->cantidad_solicitada, 0, ',', '.') }}</td>
                    <td>Bs {{ number_format((float) $linea->precio_unitario, 2, ',', '.') }}</td>
                    <td>Bs {{ number_format((int) $linea->cantidad_solicitada * (float) $linea->precio_unitario, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
