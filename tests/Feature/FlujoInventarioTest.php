<?php

use App\Enums\OrdenEstadoCodigo;
use App\Mail\CorreoGenerico;
use App\Models\Almacen;
use App\Models\Entrega;
use App\Models\EntregaDetalle;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\OrdenProducto;
use App\Models\Producto;
use App\Models\PublicacionExistencia;
use App\Models\ReservaStock;
use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use App\Models\StockSaldo;
use App\Models\User;
use App\Services\CorreoService;
use App\Services\EntregaService;
use App\Services\OrdenService;
use App\Services\PublicacionExistenciaService;
use App\Services\SalidaAlmacenService;
use Database\Seeders\OrdenEstadoSeeder;
use Database\Seeders\PersonaTipoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PersonaTipoSeeder::class,
        OrdenEstadoSeeder::class,
        RolesPermisosSeeder::class,
    ]);
});

function usuarioConRol(string $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $usuario;
}

function crearPublicacion(
    User $responsable,
    string $estado = PublicacionExistencia::ESTADO_PUBLICADA,
): PublicacionExistencia {
    return PublicacionExistencia::query()->create([
        'numero' => 'PUB-'.Str::upper(Str::random(8)),
        'fecha_ingreso' => now(),
        'responsable_user_id' => $responsable->getKey(),
        'estado' => $estado,
        'publicada_en' => $estado === PublicacionExistencia::ESTADO_PUBLICADA ? now() : null,
    ]);
}

function crearExistencia(
    PublicacionExistencia $publicacion,
    Producto $producto,
    Almacen $almacen,
    int $cantidad = 100,
    float $precio = 10,
): Existencia {
    return Existencia::query()->create([
        'publicacion_id' => $publicacion->publicacion_id,
        'producto_id' => $producto->producto_id,
        'almacen_id' => $almacen->almacen_id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
    ]);
}

function crearSaldo(
    Producto $producto,
    Almacen $almacen,
    int $actual = 10,
    int $reservada = 0,
    ?Existencia $existencia = null,
): StockSaldo {
    return StockSaldo::query()->create([
        'producto_id' => $producto->producto_id,
        'almacen_id' => $almacen->almacen_id,
        'cantidad_actual' => $actual,
        'cantidad_reservada' => $reservada,
        'precio_venta' => $existencia?->precio_unitario,
        'existencia_vigente_id' => $existencia?->existencia_id,
    ]);
}

/**
 * @param  array<int, array{existencia: Existencia, cantidad: int, precio?: float}>  $lineas
 */
function crearOrden(
    User $solicitante,
    Almacen $almacen,
    array $lineas,
    OrdenEstadoCodigo|string $estado = OrdenEstadoCodigo::Borrador,
): Orden {
    $codigoEstado = $estado instanceof OrdenEstadoCodigo ? $estado->value : $estado;

    $orden = Orden::query()->create([
        'codigo_orden' => (string) Str::uuid(),
        'numero' => 'ORD-'.Str::upper(Str::random(8)),
        'razon_social' => 'Cliente de prueba',
        'nit' => '123456789',
        'almacen_id' => $almacen->almacen_id,
        'orden_estado_id' => OrdenEstado::query()->where('codigo', $codigoEstado)->value('orden_estado_id'),
        'solicitante_user_id' => $solicitante->getKey(),
        'fecha_registro' => now(),
    ]);

    foreach ($lineas as $linea) {
        OrdenProducto::query()->create([
            'orden_id' => $orden->orden_id,
            'existencia_id' => $linea['existencia']->existencia_id,
            'producto_id' => $linea['existencia']->producto_id,
            'cantidad_solicitada' => $linea['cantidad'],
            'precio_unitario' => $linea['precio'] ?? $linea['existencia']->precio_unitario,
        ]);
    }

    return $orden->fresh(['productos', 'estado']);
}

/**
 * @return array{
 *     operador: User,
 *     aprobador: User,
 *     encargado: User,
 *     entregador: User,
 *     almacen: Almacen,
 *     producto: Producto,
 *     publicacion: PublicacionExistencia,
 *     existencia: Existencia,
 *     saldo: StockSaldo,
 *     orden: Orden
 * }
 */
function escenarioSimple(int $stock = 10, int $cantidad = 5): array
{
    $operador = usuarioConRol('operador_ordenes');
    $aprobador = usuarioConRol('aprobador');
    $encargado = usuarioConRol('encargado_almacen');
    $entregador = usuarioConRol('entregador');

    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    $publicacion = crearPublicacion($operador);
    $existencia = crearExistencia($publicacion, $producto, $almacen, cantidad: 100, precio: 10);
    $saldo = crearSaldo($producto, $almacen, actual: $stock, existencia: $existencia);
    $orden = crearOrden($operador, $almacen, [
        ['existencia' => $existencia, 'cantidad' => $cantidad],
    ]);

    return compact(
        'operador',
        'aprobador',
        'encargado',
        'entregador',
        'almacen',
        'producto',
        'publicacion',
        'existencia',
        'saldo',
        'orden',
    );
}

function ordenPendiente(array $escenario): Orden
{
    app(OrdenService::class)->enviarAprobacion($escenario['orden'], $escenario['operador']);

    return $escenario['orden']->fresh(['estado', 'productos']);
}

function ordenAprobada(array $escenario): Orden
{
    $orden = ordenPendiente($escenario);
    app(OrdenService::class)->aprobar($orden, $escenario['aprobador']);

    return $orden->fresh(['estado', 'productos']);
}

/**
 * @return array{0: SalidaAlmacen, 1: SalidaDetalle}
 */
function prepararSalidaConCantidad(array $escenario, int $cantidad): array
{
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden($escenario['orden'], $escenario['encargado']);
    $detalle = $salida->detalles()->firstOrFail();
    $detalle->cantidad_despachada = $cantidad;
    $detalle->save();

    return [$salida->fresh(), $detalle->fresh()];
}

/**
 * @return array{0: Entrega, 1: EntregaDetalle}
 */
function prepararEntregaConCantidad(array $escenario, SalidaAlmacen $salida, int $cantidad): array
{
    $entrega = app(EntregaService::class)->prepararDesdeSalida($salida, $escenario['entregador']);
    $detalle = $entrega->detalles()->firstOrFail();
    $detalle->cantidad_entregada = $cantidad;
    $detalle->save();

    return [$entrega->fresh(), $detalle->fresh()];
}

test('una orden en borrador no afecta fisica ni reservadamente el stock', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe(10)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and(ReservaStock::query()->count())->toBe(0)
        ->and($escenario['orden']->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Borrador->value);
});

test('una orden incompleta no puede enviarse a aprobacion', function (): void {
    $operador = usuarioConRol('operador_ordenes');
    $almacen = Almacen::factory()->create();
    $orden = crearOrden($operador, $almacen, []);

    expect(fn () => app(OrdenService::class)->enviarAprobacion($orden, $operador))
        ->toThrow(DomainException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Borrador->value);
});

test('una orden con stock insuficiente no puede aprobarse y no reserva nada', function (): void {
    $escenario = escenarioSimple(stock: 5, cantidad: 10);
    $orden = ordenPendiente($escenario);

    expect(fn () => app(OrdenService::class)->aprobar($orden, $escenario['aprobador']))
        ->toThrow(DomainException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::PendienteAprobacion->value)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and(ReservaStock::query()->count())->toBe(0);
});

test('aprobar reserva exactamente una vez y no duplica la reserva', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $orden = ordenAprobada($escenario);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Aprobada->value)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(5)
        ->and(ReservaStock::query()->count())->toBe(1);

    expect(fn () => app(OrdenService::class)->aprobar($orden, $escenario['aprobador']))
        ->toThrow(AuthorizationException::class);

    expect($escenario['saldo']->fresh()->cantidad_reservada)->toBe(5)
        ->and(ReservaStock::query()->count())->toBe(1);
});

test('aprobar envia la orden de pago al correo de facturacion', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $orden = ordenPendiente($escenario);
    $orden->forceFill(['correo_facturacion' => 'facturacion@example.com'])->save();
    Mail::fake();

    app(OrdenService::class)->aprobar($orden->fresh(), $escenario['aprobador']);

    Mail::assertSent(CorreoGenerico::class, function (CorreoGenerico $correo) use ($orden): bool {
        return $correo->hasTo('facturacion@example.com')
            && $correo->hasSubject('Orden de pago '.$orden->numero)
            && str_contains($correo->cuerpo, $orden->numero)
            && str_contains($correo->cuerpo, 'Bs 50.00');
    });
});

test('el servicio de correo acepta destinatarios con copia y cuerpo personalizado', function (): void {
    Mail::fake();

    app(CorreoService::class)->enviar(
        destinatarios: ['principal@example.com'],
        asunto: 'Aviso de prueba',
        cuerpo: '<p>Contenido personalizado</p>',
        cc: ['copia@example.com'],
    );

    Mail::assertSent(CorreoGenerico::class, fn (CorreoGenerico $correo): bool => $correo->hasTo('principal@example.com')
        && $correo->hasCc('copia@example.com')
        && $correo->cuerpo === '<p>Contenido personalizado</p>');
});

test('dos ordenes competidoras no pueden sobre-reservar el stock disponible', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 6);
    $segundaOrden = crearOrden($escenario['operador'], $escenario['almacen'], [
        ['existencia' => $escenario['existencia'], 'cantidad' => 6],
    ]);

    $primeraPendiente = ordenPendiente($escenario);
    app(OrdenService::class)->enviarAprobacion($segundaOrden, $escenario['operador']);
    app(OrdenService::class)->aprobar($primeraPendiente, $escenario['aprobador']);

    expect(fn () => app(OrdenService::class)->aprobar($segundaOrden->fresh(), $escenario['aprobador']))
        ->toThrow(DomainException::class);

    expect($escenario['saldo']->fresh()->cantidad_reservada)->toBe(6)
        ->and(ReservaStock::query()->count())->toBe(1);
});

test('un usuario sin permiso no puede aprobar una orden', function (): void {
    $escenario = escenarioSimple();
    $orden = ordenPendiente($escenario);

    expect(fn () => app(OrdenService::class)->aprobar($orden, $escenario['operador']))
        ->toThrow(AuthorizationException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::PendienteAprobacion->value);
});

test('rechazar registra el motivo y no deja reservas activas', function (): void {
    $escenario = escenarioSimple();
    $orden = ordenPendiente($escenario);

    app(OrdenService::class)->rechazar($orden, $escenario['aprobador'], 'Documentación incompleta.');

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Rechazada->value)
        ->and($orden->fresh()->motivo_rechazo)->toBe('Documentación incompleta.')
        ->and(ReservaStock::query()->where('estado', ReservaStock::ESTADO_ACTIVA)->count())->toBe(0)
        ->and($orden->historialEstados()->latest('orden_historial_estado_id')->first()?->observaciones)
        ->toContain('Documentación incompleta.');
});

test('rechazar un pago guarda el motivo para el cliente y devuelve la orden a aprobada', function (): void {
    $cliente = usuarioConRol('cliente');
    $ventas = usuarioConRol('encargado_ventas');
    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    $publicacion = crearPublicacion($ventas);
    $existencia = crearExistencia($publicacion, $producto, $almacen);
    crearSaldo($producto, $almacen, existencia: $existencia);
    $orden = crearOrden($cliente, $almacen, [
        ['existencia' => $existencia, 'cantidad' => 5],
    ], OrdenEstadoCodigo::Pagado);
    $orden->forceFill([
        'numero_boleta_pago' => 'VOUCHER-123',
        'comprobante_pago' => 'comprobantes/ordenes/voucher.pdf',
        'pagado_en' => now(),
    ])->save();

    Storage::fake('local');
    Storage::disk('local')->put('comprobantes/ordenes/voucher.pdf', 'voucher');

    app(OrdenService::class)->rechazarPago($orden, $ventas, 'El comprobante no es legible.');

    $ordenActualizada = $orden->fresh();
    $this->actingAs($cliente);

    expect($ordenActualizada->estadoCodigo())->toBe(OrdenEstadoCodigo::Aprobada->value)
        ->and($ordenActualizada->motivo_rechazo)->toBe('El comprobante no es legible.')
        ->and($ordenActualizada->numero_boleta_pago)->toBeNull()
        ->and($ordenActualizada->comprobante_pago)->toBeNull()
        ->and(Gate::forUser($cliente)->allows('verMotivoRechazo', $ordenActualizada))->toBeTrue();

    Storage::disk('local')->assertMissing('comprobantes/ordenes/voucher.pdf');
});

test('preparar una salida en borrador no descuenta stock ni crea movimientos', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $ordenAprobada = ordenAprobada($escenario);

    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden($ordenAprobada, $escenario['encargado']);

    expect($salida->estado)->toBe(SalidaAlmacen::ESTADO_BORRADOR)
        ->and($salida->detalles()->count())->toBe(1)
        ->and($escenario['saldo']->fresh()->cantidad_actual)->toBe(10)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(5)
        ->and(Movimiento::query()->count())->toBe(0);
});

test('confirmar una salida descuenta el stock fisico y libera la reserva utilizada', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $ordenAprobada = ordenAprobada($escenario);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden($ordenAprobada, $escenario['encargado']);

    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    $movimiento = Movimiento::query()->where('salida_id', $salida->salida_id)->firstOrFail();

    expect($salida->fresh()->estado)->toBe(SalidaAlmacen::ESTADO_CONFIRMADA)
        ->and($escenario['saldo']->fresh()->cantidad_actual)->toBe(5)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and($movimiento->estado)->toBe(Movimiento::ESTADO_CONFIRMADO)
        ->and($movimiento->detalles()->sum('cantidad'))->toBe(5)
        ->and($ordenAprobada->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::ParcialmenteAtendida->value);
});

test('confirmar dos veces la misma salida no duplica descuentos ni movimientos', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );

    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);
    app(SalidaAlmacenService::class)->confirmar($salida->fresh(), $escenario['encargado']);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe(5)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and(Movimiento::query()->count())->toBe(1);
});

test('no se puede despachar una cantidad mayor a la autorizada', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    [$salida] = prepararSalidaConCantidad(
        [...$escenario, 'orden' => ordenAprobada($escenario)],
        6,
    );

    expect(fn () => app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']))
        ->toThrow(DomainException::class);

    expect($salida->fresh()->estado)->toBe(SalidaAlmacen::ESTADO_BORRADOR)
        ->and($escenario['saldo']->fresh()->cantidad_actual)->toBe(10)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(5)
        ->and(Movimiento::query()->count())->toBe(0);
});

test('las salidas parciales mantienen la reserva pendiente y liberan solo lo despachado', function (): void {
    $escenario = escenarioSimple(stock: 20, cantidad: 10);
    $ordenAprobada = ordenAprobada($escenario);

    [$primeraSalida] = prepararSalidaConCantidad(
        [...$escenario, 'orden' => $ordenAprobada],
        4,
    );
    app(SalidaAlmacenService::class)->confirmar($primeraSalida, $escenario['encargado']);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe(16)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(6)
        ->and(ReservaStock::query()->firstOrFail()->estado)->toBe(ReservaStock::ESTADO_PARCIAL);

    $segundaSalida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        $ordenAprobada->fresh(),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($segundaSalida, $escenario['encargado']);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe(10)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and(ReservaStock::query()->firstOrFail()->estado)->toBe(ReservaStock::ESTADO_LIBERADA);
});

test('confirmar una entrega no vuelve a descontar stock', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    $stockDespuesDeSalida = $escenario['saldo']->fresh()->cantidad_actual;
    $entrega = app(EntregaService::class)->prepararDesdeSalida($salida->fresh(), $escenario['entregador']);
    app(EntregaService::class)->confirmar($entrega, $escenario['entregador']);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe($stockDespuesDeSalida)
        ->and($entrega->fresh()->estado)->toBe(Entrega::ESTADO_CONFIRMADA)
        ->and(Movimiento::query()->count())->toBe(1);
});

test('el encargado de almacen puede entregar una orden verificada y liberar sus reservas en la fecha de entrega', function (): void {
    $responsable = User::factory()->create();
    $encargado = usuarioConRol('encargado_almacen');
    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    $publicacion = crearPublicacion($responsable);
    $existencia = crearExistencia($publicacion, $producto, $almacen, cantidad: 10, precio: 10);
    $saldo = crearSaldo($producto, $almacen, actual: 10, reservada: 5, existencia: $existencia);
    $orden = crearOrden($responsable, $almacen, [
        ['existencia' => $existencia, 'cantidad' => 5],
    ], OrdenEstadoCodigo::Verificado);
    $linea = $orden->productos()->firstOrFail();
    $reserva = ReservaStock::query()->create([
        'orden_producto_id' => $linea->orden_producto_id,
        'producto_id' => $producto->producto_id,
        'almacen_id' => $almacen->almacen_id,
        'cantidad' => 5,
        'estado' => ReservaStock::ESTADO_ACTIVA,
        'reservada_en' => now()->subHour(),
    ]);

    app(EntregaService::class)->confirmarOrdenVerificada($orden, $encargado);

    $entrega = Entrega::query()->firstOrFail();

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Completada->value)
        ->and($saldo->fresh()->cantidad_actual)->toBe(5)
        ->and($saldo->fresh()->cantidad_reservada)->toBe(0)
        ->and($entrega->estado)->toBe(Entrega::ESTADO_CONFIRMADA)
        ->and($reserva->fresh()->estado)->toBe(ReservaStock::ESTADO_LIBERADA)
        ->and($reserva->fresh()->liberada_en->equalTo($entrega->fecha_entrega))->toBeTrue()
        ->and(Movimiento::query()->count())->toBe(1);
});

test('el permiso para entregar ordenes autoriza crear la entrega del flujo', function (): void {
    $encargado = usuarioConRol('encargado_almacen');

    expect($encargado->can('create', Entrega::class))->toBeTrue();
});

test('un cliente no puede confirmar la entrega de una orden verificada', function (): void {
    $cliente = usuarioConRol('cliente');
    $almacen = Almacen::factory()->create();
    $orden = crearOrden($cliente, $almacen, [], OrdenEstadoCodigo::Verificado);

    expect(fn () => app(EntregaService::class)->confirmarOrdenVerificada($orden, $cliente))
        ->toThrow(AuthorizationException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Verificado->value)
        ->and(Entrega::query()->count())->toBe(0)
        ->and(SalidaAlmacen::query()->count())->toBe(0);
});

test('no se puede entregar mas de lo despachado', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    [$entrega] = prepararEntregaConCantidad($escenario, $salida->fresh(), 6);

    expect(fn () => app(EntregaService::class)->confirmar($entrega, $escenario['entregador']))
        ->toThrow(DomainException::class);

    expect($entrega->fresh()->estado)->toBe(Entrega::ESTADO_BORRADOR);
});

test('una entrega parcial no completa la orden', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 10);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    [$entregaParcial] = prepararEntregaConCantidad($escenario, $salida->fresh(), 4);
    app(EntregaService::class)->confirmar($entregaParcial, $escenario['entregador']);

    expect($escenario['orden']->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::ParcialmenteAtendida->value);

    $segundaEntrega = app(EntregaService::class)->prepararDesdeSalida($salida->fresh(), $escenario['entregador']);
    app(EntregaService::class)->confirmar($segundaEntrega, $escenario['entregador']);

    expect($escenario['orden']->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Completada->value);
});

test('la orden se completa solo cuando todas las cantidades fueron entregadas', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    $entrega = app(EntregaService::class)->prepararDesdeSalida($salida->fresh(), $escenario['entregador']);
    app(EntregaService::class)->confirmar($entrega, $escenario['entregador']);

    $orden = $escenario['orden']->fresh();

    expect($orden->estadoCodigo())->toBe(OrdenEstadoCodigo::Completada->value)
        ->and($orden->historialEstados()->whereHas('estado', fn ($query) => $query->where('codigo', OrdenEstadoCodigo::Completada->value))->exists())->toBeTrue();
});

test('cancelar libera solo las reservas pendientes y conserva lo ya despachado', function (): void {
    $escenario = escenarioSimple(stock: 20, cantidad: 10);
    $ordenAprobada = ordenAprobada($escenario);

    [$salida] = prepararSalidaConCantidad(
        [...$escenario, 'orden' => $ordenAprobada],
        4,
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    app(OrdenService::class)->cancelar($ordenAprobada->fresh(), $escenario['operador'], 'Cancelación solicitada.');

    expect($escenario['orden']->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Cancelada->value)
        ->and($escenario['saldo']->fresh()->cantidad_actual)->toBe(16)
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and(ReservaStock::query()->firstOrFail()->estado)->toBe(ReservaStock::ESTADO_LIBERADA);
});

test('encargado de ventas puede anular una orden aprobada registrando el motivo', function (): void {
    $escenario = escenarioSimple(stock: 20, cantidad: 10);
    $ventas = usuarioConRol('encargado_ventas');
    $orden = ordenAprobada($escenario);

    app(OrdenService::class)->cancelar($orden, $ventas, 'El cliente decidió no completar el pedido.');

    $ordenActualizada = $orden->fresh();

    expect($ordenActualizada->estadoCodigo())->toBe(OrdenEstadoCodigo::Cancelada->value)
        ->and($ordenActualizada->motivo_rechazo)->toBe('El cliente decidió no completar el pedido.')
        ->and($escenario['saldo']->fresh()->cantidad_reservada)->toBe(0)
        ->and($ordenActualizada->historialEstados()->where('observaciones', 'Orden cancelada. Motivo: El cliente decidió no completar el pedido.')->exists())->toBeTrue();
});

test('el cliente solicitante puede consultar el motivo de anulacion de su orden', function (): void {
    $escenario = escenarioSimple(stock: 20, cantidad: 10);
    $cliente = usuarioConRol('cliente');
    $orden = ordenAprobada($escenario);
    $orden->forceFill(['solicitante_user_id' => $cliente->getKey()])->save();
    app(OrdenService::class)->cancelar($orden->fresh(), $escenario['aprobador'], 'El cliente decidió no completar el pedido.');

    expect($cliente->can('verMotivoRechazo', $orden->fresh()))->toBeTrue();
});

test('solo encargado de ventas puede cancelar ordenes aprobadas', function (): void {
    $escenario = escenarioSimple(stock: 20, cantidad: 10);
    $orden = ordenAprobada($escenario);

    expect(fn () => app(OrdenService::class)->cancelar($orden, $escenario['operador'], 'Cancelación solicitada.'))
        ->toThrow(AuthorizationException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Aprobada->value)
        ->and($orden->fresh()->motivo_rechazo)->toBeNull();
});

test('un fallo al aprobar una orden no deja reservas ni descuentos parciales', function (): void {
    $operador = usuarioConRol('operador_ordenes');
    $aprobador = usuarioConRol('aprobador');
    $almacen = Almacen::factory()->create();
    $productoA = Producto::factory()->create();
    $productoB = Producto::factory()->create();
    $publicacion = crearPublicacion($operador);
    $existenciaA = crearExistencia($publicacion, $productoA, $almacen);
    $existenciaB = crearExistencia($publicacion, $productoB, $almacen);
    $saldoA = crearSaldo($productoA, $almacen, actual: 10, existencia: $existenciaA);
    $saldoB = crearSaldo($productoB, $almacen, actual: 1, existencia: $existenciaB);

    $orden = crearOrden($operador, $almacen, [
        ['existencia' => $existenciaA, 'cantidad' => 5],
        ['existencia' => $existenciaB, 'cantidad' => 5],
    ]);
    app(OrdenService::class)->enviarAprobacion($orden, $operador);

    expect(fn () => app(OrdenService::class)->aprobar($orden->fresh(), $aprobador))
        ->toThrow(DomainException::class);

    expect($saldoA->fresh()->cantidad_reservada)->toBe(0)
        ->and($saldoB->fresh()->cantidad_reservada)->toBe(0)
        ->and(ReservaStock::query()->count())->toBe(0)
        ->and($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::PendienteAprobacion->value);
});

test('la publicacion historica no se modifica al cambiar el stock operativo', function (): void {
    $responsable = usuarioConRol('responsable_inventario');
    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    $publicacion = crearPublicacion($responsable);
    $existencia = crearExistencia($publicacion, $producto, $almacen, cantidad: 10, precio: 5);
    $saldo = crearSaldo($producto, $almacen, actual: 2);

    $saldo->cantidad_actual = 99;
    $saldo->save();

    expect($existencia->fresh()->cantidad)->toBe(10)
        ->and($existencia->fresh()->precio_unitario)->toBe('5.00')
        ->and($saldo->fresh()->cantidad_actual)->toBe(99);

    $borrador = crearPublicacion($responsable, PublicacionExistencia::ESTADO_BORRADOR);
    crearExistencia($borrador, $producto, $almacen, cantidad: 7, precio: 8);

    app(PublicacionExistenciaService::class)->publicar($borrador, $responsable);

    expect($borrador->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_PUBLICADA)
        ->and($saldo->fresh()->cantidad_actual)->toBe(99);
});

test('un movimiento confirmado no puede editarse ni eliminarse', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );
    app(SalidaAlmacenService::class)->confirmar($salida, $escenario['encargado']);

    $movimiento = Movimiento::query()->firstOrFail();
    $administrador = usuarioConRol('administrador');

    expect(Gate::forUser($administrador)->allows('update', $movimiento))->toBeFalse()
        ->and(Gate::forUser($administrador)->allows('delete', $movimiento))->toBeFalse()
        ->and(Gate::forUser($administrador)->allows('create', Movimiento::class))->toBeFalse();
});

test('no se puede enviar a aprobacion una orden con publicacion en borrador', function (): void {
    $operador = usuarioConRol('operador_ordenes');
    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    $publicada = crearPublicacion($operador);
    $existenciaVigente = crearExistencia($publicada, $producto, $almacen);
    crearSaldo($producto, $almacen, existencia: $existenciaVigente);

    $borrador = crearPublicacion($operador, PublicacionExistencia::ESTADO_BORRADOR);
    $existenciaBorrador = crearExistencia($borrador, $producto, $almacen);

    $orden = crearOrden($operador, $almacen, [
        ['existencia' => $existenciaVigente, 'cantidad' => 1],
    ]);

    $linea = $orden->productos()->sole();
    $linea->existencia_id = $existenciaBorrador->existencia_id;
    $linea->saveQuietly();

    expect(fn () => app(OrdenService::class)->enviarAprobacion($orden->fresh(), $operador))
        ->toThrow(DomainException::class);

    expect($orden->fresh()->estadoCodigo())->toBe(OrdenEstadoCodigo::Borrador->value);
});

test('un usuario sin permiso no puede confirmar una salida', function (): void {
    $escenario = escenarioSimple(stock: 10, cantidad: 5);
    $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden(
        ordenAprobada($escenario),
        $escenario['encargado'],
    );

    expect(fn () => app(SalidaAlmacenService::class)->confirmar($salida, $escenario['entregador']))
        ->toThrow(AuthorizationException::class);

    expect($escenario['saldo']->fresh()->cantidad_actual)->toBe(10)
        ->and($salida->fresh()->estado)->toBe(SalidaAlmacen::ESTADO_BORRADOR);
});
