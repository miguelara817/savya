<?php

namespace App\Models;

use App\Enums\OrdenEstadoCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'codigo_orden',
    'numero',
    'persona_id',
    'razon_social',
    'nit',
    'direccion_facturacion',
    'correo_facturacion',
    'almacen_id',
    'orden_estado_id',
    'solicitante_user_id',
    'fecha_registro',
    'fecha_efectivizacion',
    'observaciones',
    'cantidad_total',
    'precio_total',
])]
class Orden extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Orden $orden): void {
            if (empty($orden->orden_estado_id)) {
                $estadoId = OrdenEstado::query()
                    ->where('codigo', OrdenEstadoCodigo::PendienteAprobacion->value)
                    ->value('orden_estado_id');

                if ($estadoId) {
                    $orden->orden_estado_id = $estadoId;
                } elseif (empty($orden->orden_estado_id)) {
                    $estadoId = OrdenEstado::query()
                        ->where('codigo', OrdenEstadoCodigo::Borrador->value)
                        ->value('orden_estado_id');

                    if ($estadoId) {
                        $orden->orden_estado_id = $estadoId;
                    }
                }
            }

            if (empty($orden->fecha_registro)) {
                $orden->fecha_registro = now();
            }

            if ((int) ($orden->cantidad_total ?? 0) === 0) {
                $orden->cantidad_total = 0;
            }

            if ((float) ($orden->precio_total ?? 0) === 0.0) {
                $orden->precio_total = 0;
            }
        });

        static::saved(function (Orden $orden): void {
            $orden->recalcularTotales();
        });
    }

    public function recalcularTotales(): void
    {
        $productos = $this->productos()->get();

        $cantidadTotal = $productos->sum(
            fn ($producto): int => (int) $producto->cantidad_solicitada
        );

        $precioTotal = $productos->sum(
            fn ($producto): float => (float) $producto->cantidad_solicitada
                * (float) $producto->precio_unitario
        );

        $this->forceFill([
            'cantidad_total' => $cantidadTotal,
            'precio_total' => round($precioTotal, 2),
        ])->saveQuietly();
    }

    use SoftDeletes;

    protected $table = 'ordenes';

    protected $primaryKey = 'orden_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'fecha_efectivizacion' => 'datetime',
            'firmado_en' => 'datetime',
        ];
    }

    public function estadoCodigo(): ?string
    {
        return $this->estado?->codigo;
    }

    public function estaEnEstado(OrdenEstadoCodigo|string ...$codigos): bool
    {
        $codigosNormalizados = array_map(
            static fn (OrdenEstadoCodigo|string $codigo): string => $codigo instanceof OrdenEstadoCodigo ? $codigo->value : $codigo,
            $codigos,
        );

        return in_array($this->estadoCodigo(), $codigosNormalizados, true);
    }

    public function esEditable(): bool
    {
        return $this->estaEnEstado(OrdenEstadoCodigo::Borrador);
    }

    public function estaEnCurso(): bool
    {
        return $this->estaEnEstado(
            OrdenEstadoCodigo::Aprobada,
            OrdenEstadoCodigo::ParcialmenteAtendida,
        );
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * @return BelongsTo<Almacen, $this>
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    /**
     * @return BelongsTo<OrdenEstado, $this>
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(OrdenEstado::class, 'orden_estado_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_user_id');
    }

    /**
     * @return HasMany<OrdenProducto, $this>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(OrdenProducto::class, 'orden_id');
    }

    /**
     * @return HasMany<OrdenPersona, $this>
     */
    public function personasAdicionales(): HasMany
    {
        return $this->hasMany(OrdenPersona::class, 'orden_id');
    }

    /**
     * @return HasMany<OrdenHistorialEstado, $this>
     */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(OrdenHistorialEstado::class, 'orden_id');
    }

    /**
     * @return HasMany<SalidaAlmacen, $this>
     */
    public function salidas(): HasMany
    {
        return $this->hasMany(SalidaAlmacen::class, 'orden_id');
    }

    /**
     * @return HasMany<Movimiento, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'orden_id');
    }
}
