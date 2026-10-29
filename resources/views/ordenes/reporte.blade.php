<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="/images/favicon.ico">
    <title>Pedido {{ $orden->numero }}</title>
    <style>
        /* @page { margin: 24mm 16mm; } */
        body { color: #111827; font: 11px DejaVu Sans, sans-serif; }
        h1 { font-size: 13px; text-align: center; padding: 0;}
        h2 { border-bottom: 1px solid #374151; font-size: 13px; margin-top: 22px; padding-bottom: 5px; }
        table { border-collapse: collapse; margin-top: 9px; width: 100%; }
        th, td { border: 1px solid #9ca3af; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .number { text-align: right; white-space: nowrap; }
        .declaration { line-height: 1.6}
        .signatures { margin-top: 20px; width: 100%; border: 1px }
        .signature-field {height: 60px;}
        /* .signature { border-top: 1px solid #111827; padding: 8px; text-align: center; width: 45%; } */
        .signature {text-align: center; }
        .img-container {
            text-align: center;
            background-color: #f7f7f7;
        }
        .logo {
            width: 150px;
            text-align: center;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    {{-- <h1>ORDEN DE PEDIDO</h1> --}}

    <table>
        <tbody>
            <tr>
                {{-- public_path --}}
                <td class="img-container"><img class="logo" src="{{ public_path('images/min-hor.jpeg') }}" alt="logo-ministerio"></td>
                <td>
                    <h1>INSUMOS BOLIVIA</h1>
                    <h1>FORMULARIO DE PEDIDO</h1>
                </td>
                <td class="img-container"><img class="logo" src="{{ public_path('images/insumosbol.jpeg') }}" alt="logo-ministerio"></td>
            </tr>
        </tbody>
    </table>

    <h2>1. Datos del pedido</h2>
    <table>
        <tbody>
            <tr><th>Código de pedido</th><td>{{ $orden->codigo_orden }}</td><th>Fecha de registro</th><td>{{ $orden->fecha_registro?->format('d/m/Y') }}</td></tr>
            <tr><th>Almacén</th><td colspan="3">{{ $orden->almacen?->nombre }}</td></tr>
        </tbody>
    </table>

    <h2>2. Datos del solicitante</h2>
    <table>
        <tbody>
            <tr><th>Nombre</th><td>{{ $orden->solicitante->persona->nombre}} {{ $orden->solicitante->persona?->apellido }}</td><th>Carnet</th><td>{{ $orden->solicitante->persona->carnet }}</td></tr>
            <tr><th>Teléfono</th><td>{{ $orden->solicitante->persona?->telefono }}</td><th>Correo</th><td>{{ $orden->solicitante->persona?->correo }}</td></tr>
            <tr><th>Dirección</th><td colspan="3">{{ $orden->solicitante->persona?->direccion }}</td></tr>
            {{-- <tr><th>Razón social</th><td>{{ $orden->razon_social }}</td><th>NIT</th><td>{{ $orden->nit }}</td></tr>
            <tr><th>Dirección de facturación</th><td>{{ $orden->direccion_facturacion }}</td><th>Correo de facturación</th><td>{{ $orden->correo_facturacion }}</td></tr> --}}
        </tbody>
    </table>

    <h2>3. Datos de facturación</h2>
    <table>
        <tbody>
            <tr><th>Razón social</th><td>{{ $orden->razon_social }}</td><th>NIT</th><td>{{ $orden->nit }}</td></tr>
            <tr><th>Dirección de facturación</th><td>{{ $orden->direccion_facturacion }}</td><th>Correo de facturación</th><td>{{ $orden->correo_facturacion }}</td></tr>
        </tbody>
    </table>

    <h2>4. Detalle del pedido</h2>
    <table>
        <thead>
            <tr><th>Producto</th><th>Presentación</th><th class="number">Cantidad</th><th class="number">Precio unitario</th><th class="number">Precio total</th></tr>
        </thead>
        <tbody>
            @foreach ($orden->productos as $linea)
                <tr>
                    <td>{{ $linea->producto?->nombre }}</td>
                    <td>{{ $linea->producto?->presentacion }}</td>
                    <td class="number">{{ number_format((int) $linea->cantidad_solicitada, 0, ',', '.') }}</td>
                    <td class="number">Bs {{ number_format((float) $linea->precio_unitario, 2, ',', '.') }}</td>
                    <td class="number">Bs {{ number_format((int) $linea->cantidad_solicitada * (float) $linea->precio_unitario, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="2">Totales</th>
                <th class="number">{{ number_format((int) $orden->productos->sum('cantidad_solicitada'), 0, ',', '.') }}</th>
                <th></th>
                <th class="number">Bs {{ number_format((float) $orden->productos->sum(fn ($linea): float => (int) $linea->cantidad_solicitada * (float) $linea->precio_unitario), 2, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <h2>5. Declaración jurada del solicitante</h2>
    <div class="declaration">
        Declaro que la harina adquirida será destinada exclusivamente a la elaboración de pan por los panificadores asociados, que no será revendida ni desviada a otros fines, y que acepto la verificación de su destino por INSUMOS BOLIVIA y EMAPA. La falsedad de esta declaración habilita la suspensión de futuras ventas y las acciones legales que correspondan.
    </div>

    <table class="signatures" >
        <tr><td class="signature-field"></td></tr>
        <tr>
            <td class="signature" >
                <br>Firma del solicitante<br><strong>Nombre:</strong> {{ $orden->solicitante->persona->nombre }} {{ $orden->solicitante->persona?->apellido }}<br><strong>C.I.:</strong> {{ $orden->solicitante->persona->carnet }}
            </td>
            {{-- <td style="border: 0; width: 10%;"></td>
            <td class="signature" style="border: 0;"><br>Firma de recepción / revisión<br>Nombre: ____________________<br>C.I.: ____________________</td> --}}
        </tr>
    </table>
</body>
</html>
