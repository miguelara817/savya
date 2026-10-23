<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Crea los permisos funcionales y roles operativos sin eliminar roles existentes.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect([
            'ViewAny:Orden', 'View:Orden', 'Create:Orden', 'Update:Orden', 'Delete:Orden',
            'EnviarAprobacion:Orden', 'Aprobar:Orden', 'Rechazar:Orden', 'Cancelar:Orden',
            'ViewAny:OrdenProducto', 'View:OrdenProducto', 'Create:OrdenProducto', 'Update:OrdenProducto', 'Delete:OrdenProducto',
            'ViewAny:OrdenPersona', 'View:OrdenPersona', 'Create:OrdenPersona', 'Update:OrdenPersona', 'Delete:OrdenPersona',
            'ViewAny:OrdenHistorialEstado', 'View:OrdenHistorialEstado',
            'ViewAny:PublicacionExistencia', 'View:PublicacionExistencia', 'Create:PublicacionExistencia', 'Update:PublicacionExistencia', 'Delete:PublicacionExistencia',
            'Publicar:PublicacionExistencia',
            'ViewAny:Existencia', 'View:Existencia', 'Create:Existencia', 'Update:Existencia', 'Delete:Existencia',
            'ViewAny:StockSaldo', 'View:StockSaldo', 'Create:StockSaldo', 'Update:StockSaldo', 'Delete:StockSaldo',
            'ViewAny:ReservaStock', 'View:ReservaStock',
            'ViewAny:SalidaAlmacen', 'View:SalidaAlmacen', 'Create:SalidaAlmacen', 'Update:SalidaAlmacen', 'Delete:SalidaAlmacen',
            'Confirmar:SalidaAlmacen',
            'ViewAny:SalidaDetalle', 'View:SalidaDetalle', 'Create:SalidaDetalle', 'Update:SalidaDetalle', 'Delete:SalidaDetalle',
            'ViewAny:Entrega', 'View:Entrega', 'Create:Entrega', 'Update:Entrega', 'Delete:Entrega',
            'Confirmar:Entrega',
            'ViewAny:EntregaDetalle', 'View:EntregaDetalle', 'Create:EntregaDetalle', 'Update:EntregaDetalle', 'Delete:EntregaDetalle',
            'ViewAny:Movimiento', 'View:Movimiento', 'ViewAny:MovimientoDetalle', 'View:MovimientoDetalle',
            'ViewAny:MovimientoHistorialEstado', 'View:MovimientoHistorialEstado',
            'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
            'ViewAny:Persona', 'View:Persona', 'Create:Persona', 'Update:Persona',
        ])->unique();

        foreach ($permisos as $permiso) {
            Permission::query()->firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'super_admin' => $permisos->all(),
            'administrador' => $permisos->all(),
            'responsable_inventario' => [
                'ViewAny:PublicacionExistencia', 'View:PublicacionExistencia', 'Create:PublicacionExistencia', 'Update:PublicacionExistencia', 'Delete:PublicacionExistencia',
                'Publicar:PublicacionExistencia',
                'ViewAny:Existencia', 'View:Existencia', 'Create:Existencia', 'Update:Existencia', 'Delete:Existencia',
                'ViewAny:StockSaldo', 'View:StockSaldo', 'Create:StockSaldo', 'Update:StockSaldo',
                'ViewAny:ReservaStock', 'View:ReservaStock',
                'ViewAny:Movimiento', 'View:Movimiento', 'ViewAny:MovimientoDetalle', 'View:MovimientoDetalle',
                'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
            ],
            'operador_ordenes' => [
                'ViewAny:Orden', 'View:Orden', 'Create:Orden', 'Update:Orden',
                'EnviarAprobacion:Orden', 'Cancelar:Orden',
                'ViewAny:OrdenProducto', 'View:OrdenProducto', 'Create:OrdenProducto', 'Update:OrdenProducto', 'Delete:OrdenProducto',
                'ViewAny:OrdenPersona', 'View:OrdenPersona', 'Create:OrdenPersona', 'Update:OrdenPersona', 'Delete:OrdenPersona',
                'ViewAny:OrdenHistorialEstado', 'View:OrdenHistorialEstado',
                'ViewAny:PublicacionExistencia', 'View:PublicacionExistencia',
                'ViewAny:Existencia', 'View:Existencia',
                'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
                'ViewAny:Persona', 'View:Persona', 'Create:Persona', 'Update:Persona',
            ],
            'aprobador' => [
                'ViewAny:Orden', 'View:Orden', 'Aprobar:Orden', 'Rechazar:Orden',
                'ViewAny:OrdenProducto', 'View:OrdenProducto',
                'ViewAny:OrdenHistorialEstado', 'View:OrdenHistorialEstado',
                'ViewAny:PublicacionExistencia', 'View:PublicacionExistencia',
                'ViewAny:Existencia', 'View:Existencia',
                'ViewAny:StockSaldo', 'View:StockSaldo',
                'ViewAny:ReservaStock', 'View:ReservaStock',
                'ViewAny:Persona', 'View:Persona', 'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
            ],
            'encargado_almacen' => [
                'ViewAny:Orden', 'View:Orden',
                'ViewAny:OrdenProducto', 'View:OrdenProducto',
                'ViewAny:SalidaAlmacen', 'View:SalidaAlmacen', 'Create:SalidaAlmacen', 'Update:SalidaAlmacen', 'Delete:SalidaAlmacen',
                'Confirmar:SalidaAlmacen',
                'ViewAny:SalidaDetalle', 'View:SalidaDetalle', 'Create:SalidaDetalle', 'Update:SalidaDetalle', 'Delete:SalidaDetalle',
                'ViewAny:Movimiento', 'View:Movimiento', 'ViewAny:MovimientoDetalle', 'View:MovimientoDetalle',
                'ViewAny:StockSaldo', 'View:StockSaldo',
                'ViewAny:ReservaStock', 'View:ReservaStock',
                'ViewAny:Entrega', 'View:Entrega',
                'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
            ],
            'entregador' => [
                'ViewAny:Orden', 'View:Orden',
                'ViewAny:SalidaAlmacen', 'View:SalidaAlmacen',
                'ViewAny:SalidaDetalle', 'View:SalidaDetalle',
                'ViewAny:Entrega', 'View:Entrega', 'Create:Entrega', 'Update:Entrega', 'Delete:Entrega',
                'Confirmar:Entrega',
                'ViewAny:EntregaDetalle', 'View:EntregaDetalle', 'Create:EntregaDetalle', 'Update:EntregaDetalle', 'Delete:EntregaDetalle',
                'ViewAny:Persona', 'View:Persona', 'Create:Persona', 'Update:Persona',
                'ViewAny:Producto', 'View:Producto', 'ViewAny:Almacen', 'View:Almacen',
            ],
        ];

        foreach ($roles as $nombreRol => $nombresPermisos) {
            $rol = Role::query()->firstOrCreate([
                'name' => $nombreRol,
                'guard_name' => 'web',
            ]);

            if (in_array($nombreRol, ['super_admin', 'administrador'], true)) {
                $rol->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
            } else {
                $rol->syncPermissions(
                    Permission::query()
                        ->where('guard_name', 'web')
                        ->whereIn('name', $nombresPermisos)
                        ->get(),
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
