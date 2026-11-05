# Flujo de inventario y órdenes: integración actual

## Propósito y alcance

Este documento describe el comportamiento implementado actualmente en el código, no un diseño futuro ni una recomendación. Sirve como mapa para evaluar cambios al flujo de ingresos, despachos y entregas. Se enfoca en los roles, casos de uso, estados y tablas que intervienen desde el ingreso de existencias hasta completar una orden.

## Resumen ejecutivo

El sistema mantiene tres conceptos separados:

1. **Existencia publicada**: documento/líneas que documentan un ingreso y fijan el precio vigente para nuevas órdenes.
2. **Saldo operativo**: unidades físicas por producto y almacén, menos las unidades reservadas. Es la fuente que se consulta y modifica para la disponibilidad.
3. **Documentos de cumplimiento de orden**: una salida registra el despacho desde el almacén; una entrega registra la recepción. Un movimiento de inventario se genera al confirmar la salida como evidencia del cambio de stock.

La cadena simplificada es:

```text
Ingreso (publicaciones_existencias + existencias)
    └─ confirmar → stock_saldos (+ cantidad_actual, precio vigente)

Orden (ordenes + orden_productos)
    └─ aprobar → reservas_stock + stock_saldos (+ cantidad_reservada)
         └─ preparar salida → salidas_almacen BORRADOR + salida_detalles
              └─ confirmar salida → stock_saldos (- físico y reservado)
                   + movimientos + movimiento_detalles + historial del movimiento
                   └─ preparar/confirmar entrega → entregas + entrega_detalles
                        └─ actualizar orden → PARCIALMENTE_ATENDIDA o COMPLETADA
```

## Roles y permisos observados

Los permisos se definen en `database/seeders/RolesPermisosSeeder.php`. Los controles del servicio/política prevalecen aunque un botón no aparezca.

| Rol | Responsabilidad en este flujo (según permisos sembrados) |
| --- | --- |
| `responsable_inventario` | Crear, editar y confirmar ingresos de inventario; consultar existencias, saldos y reservas. No tiene permisos de salida/entrega en el seeder actual. |
| `encargado_ventas` | Revisar/aprobar o rechazar órdenes, verificar pago, cancelar órdenes aprobadas y consultar existencias/saldos/reservas. No tiene permisos de despacho. |
| `encargado_almacen` | Consultar órdenes y salidas; `Entregar:Orden` le permite ejecutar el flujo integrado de entrega de una orden verificada. También tiene `Confirmar:Entrega`. En el seeder actual no tiene `Create:SalidaAlmacen` ni `Confirmar:SalidaAlmacen`; la política da una excepción limitada para la orden verificada. |
| `cliente` / `cliente_ad` | Crear/consultar sus órdenes, pagar y consultar motivos permitidos. El acceso de cliente a órdenes propias está limitado en `OrdenPolicy`. |
| `administrador` / `super_admin` | Tienen todos los permisos definidos por el seeder; pueden operar los recursos si el estado y las reglas del servicio lo permiten. |

Notas importantes sobre roles:

- No hay un rol `entregador` activo en el seeder actual; su bloque está comentado. El archivo de pruebas crea ese rol, por eso sus escenarios de prueba no prueban exactamente la configuración real sembrada para producción.
- El botón **Preparar salida** autoriza `Create:SalidaAlmacen`. `administrador` y `super_admin` lo tienen; `encargado_almacen` no, por lo que normalmente no lo verá aunque la política del servicio admita `Entregar:Orden` para ciertas operaciones.
- La acción **Entregado** se autoriza mediante `Entregar:Orden`; la política permite esa operación únicamente cuando la orden está `VERIFICADO`.
- Los nombres de rol por sí solos no autorizan operaciones: deben existir sus permisos y el estado debe ser válido.

## Tablas y su impacto

### Ingreso de existencias

| Tabla | Función / relación | Impacto operativo |
| --- | --- | --- |
| `publicaciones_existencias` | Cabecera del ingreso: número, fecha, responsable, estado, observaciones. | El registro en borrador no cambia el saldo. La confirmación cambia su estado a `PUBLICADA`. |
| `existencias` | Líneas del ingreso: publicación, producto, almacén, cantidad y precio unitario. | Al confirmar la publicación, cada línea suma cantidad al saldo del mismo producto/almacén y actualiza el precio vigente. Es un registro histórico del ingreso, no el saldo actual. |
| `stock_saldos` | Un registro único por producto y almacén; guarda `cantidad_actual`, `cantidad_reservada`, `precio_venta` y `existencia_vigente_id`. | Fuente operativa de stock y precio vigente. Disponible se calcula como `cantidad_actual - cantidad_reservada`. Ingreso aumenta `cantidad_actual`; aprobación reserva unidades; despacho reduce ambas cantidades. |

En la confirmación del ingreso (`PublicacionExistenciaService::confirmarIngreso`) se valida que las cantidades sean positivas, se publica el documento y se actualizan los saldos dentro de una transacción. No se crea un registro en `movimientos` ni en `movimiento_detalles` para el ingreso. Por ello, los ingresos se auditan mediante `publicaciones_existencias`/`existencias`, mientras que la tabla `movimientos` hoy refleja principalmente salidas.

La publicación también valida productos y almacenes activos, cantidades/precios no negativos y que existan líneas. El formulario no permite repetir producto + almacén en un mismo ingreso.

### Orden y reserva

| Tabla | Función / relación | Impacto operativo |
| --- | --- | --- |
| `ordenes` | Cabecera del pedido, almacén, solicitante, estado, datos comerciales/pago y fechas. | Su estado es la vista resumida del avance de aprobación, despacho y entrega. |
| `orden_productos` | Líneas solicitadas: orden, existencia publicada, producto, cantidad y precio congelado para esa orden. | Se usa para validar stock, crear reservas y comparar despachado/entregado contra lo autorizado. |
| `orden_estados` | Catálogo de estados. | El enum `OrdenEstadoCodigo` contiene los estados que usa la lógica. |
| `orden_historial_estados` | Historial de cambios de estado con responsable y observaciones. | Se escribe al transicionar la orden a través de `OrdenService`. |
| `reservas_stock` | Una reserva por línea de orden; contiene producto, almacén, cantidad y estado. | Se crea/reactiva al aprobar. Despacho consume las cantidades reservadas; cancelación libera la parte aún no despachada. |

La aprobación valida que la existencia asociada corresponda al producto y almacén de la orden y que su publicación esté publicada. La existencia sirve como referencia de catálogo/precio disponible, pero la validación de disponibilidad real se hace contra `stock_saldos`.

### Despacho, movimiento y entrega

| Tabla | Función / relación | Impacto operativo |
| --- | --- | --- |
| `salidas_almacen` | Cabecera del despacho asociado a una orden y almacén; guarda estado, responsable, fechas y firma. | `BORRADOR` no mueve stock. Al confirmar, registra el despacho y gatilla el descuento del saldo. |
| `salida_detalles` | Cantidad despachada por línea `orden_producto_id`. | La suma de detalles en salidas confirmadas determina cuánto de cada línea ya se despachó. Permite entregas/despachos parciales sucesivos. |
| `movimientos` | Asiento de inventario asociado de manera única (`salida_id`) a una salida, tipo `SALIDA`. | Se crea automáticamente en la confirmación de salida. No es una etapa manual necesaria para completar la orden. |
| `movimiento_detalles` | Total del movimiento por producto, con cantidad y precio unitario. | Copia agregada de las cantidades despachadas por producto para el registro de inventario. |
| `movimiento_historial_estados` | Estado del movimiento y responsable. | Se crea con estado confirmado cuando se crea el movimiento de salida. |
| `entregas` | Cabecera de recepción asociada a una salida confirmada; incluye receptor, responsable, fechas, firma y estado. | No descuenta inventario. Confirma que el cliente recibió bienes ya despachados. |
| `entrega_detalles` | Cantidad recibida referida a una línea de `salida_detalles`. | La suma de entregas confirmadas se compara con lo despachado y alimenta el cálculo de completitud de la orden. |

Estados definidos por modelos:

- Salida: `BORRADOR`, `CONFIRMADA`, `ANULADA`.
- Entrega: `BORRADOR`, `CONFIRMADA`, `ANULADA`.
- Movimiento: `BORRADOR`, `CONFIRMADO`; tipo `SALIDA` o `ENTRADA` (la ruta automática de orden solo genera `SALIDA`).
- Reserva: `ACTIVA`, `PARCIAL`, `LIBERADA`.

Los modelos incluyen estados `ANULADA`, pero en los servicios y acciones revisados no encontré un flujo de anulación que restaure saldo o reservas. No tratar una anulación manual/borrado como reversión contable sin implementar y probar esa compensación.

## Flujo paso a paso por orden hasta completar

### 0. Ingreso y disponibilidad para vender

1. `responsable_inventario` crea un borrador de ingreso (`publicaciones_existencias`) con una o más líneas (`existencias`).
2. Mientras esté en borrador, no afecta `stock_saldos`.
3. Al **Confirmar ingreso**, se ejecuta `PublicacionExistenciaService::confirmarIngreso` en transacción:
   - verifica permiso `Publicar:PublicacionExistencia` y estado borrador;
   - marca la publicación como `PUBLICADA`;
   - crea el saldo si no existe;
   - suma `existencias.cantidad` a `stock_saldos.cantidad_actual`;
   - establece `precio_venta` y `existencia_vigente_id` con la línea confirmada.
4. No aumenta ni recalcula `cantidad_reservada`. Los precios ya guardados en órdenes existentes no cambian.

### 1. Crear y enviar la orden a aprobación

1. Cliente/operador crea una orden y líneas en `ordenes`/`orden_productos`, indicando producto, existencia, cantidades y precios.
2. La orden parte normalmente en `BORRADOR` o, según la pantalla de creación, puede crearse directamente en `PENDIENTE_APROBACION`. Revisar la configuración real de cada entrada: el modelo tiene un valor por defecto y la pantalla `ListOrdens` asigna explícitamente pendiente.
3. Una orden borrador no cambia `stock_saldos` ni crea reservas.
4. `enviarAprobacion` valida datos de facturación, almacén y líneas, y transiciona a `PENDIENTE_APROBACION`, guardando el historial.

### 2. Aprobación y reserva

1. `encargado_ventas` aprueba una orden `PENDIENTE_APROBACION`.
2. `OrdenService::aprobar` valida producto/publicación/almacén/cantidad y bloquea saldos para evitar sobre-reservas.
3. Para cada línea:
   - valida que `stock_saldos.disponible` alcance la cantidad solicitada;
   - suma cantidad a `stock_saldos.cantidad_reservada`;
   - crea o actualiza `reservas_stock` con estado `ACTIVA`.
4. La cantidad física no cambia: sigue en el almacén, pero deja de estar disponible para otras órdenes.
5. La orden queda `APROBADA`; se guarda historial y se envía correo de pago.

### 3. Pago y verificación (ramal requerido por entrega integrada)

1. Cliente paga una orden `APROBADA`; se registran número de boleta, comprobante y fecha en `ordenes`; estado `PAGADO`.
2. Si ventas rechaza el comprobante, la orden vuelve a `APROBADA`, se guarda motivo y se elimina el archivo de comprobante anterior.
3. `encargado_ventas` verifica el pago de la orden `PAGADO`, registra fecha tentativa y pasa el estado a `VERIFICADO`.
4. La verificación habilita la acción **Entregado** del flujo integrado de la orden.

El despacho separado también está permitido desde una orden en curso (`APROBADA`, `PAGADO`, `VERIFICADO` o `PARCIALMENTE_ATENDIDA`), sujeto a permiso de creación de salida y confirmación.

### 4A. Ruta separada: preparar/confirmar salida y luego entrega

Esta ruta permite revisar y editar cantidades antes de confirmar cada documento.

1. Desde la orden, **Preparar salida** invoca `SalidaAlmacenService::prepararDesdeOrden`.
2. Solo se prepara si la orden está en curso y no hay otra salida en borrador para ella.
3. Crea `salidas_almacen` en `BORRADOR` y `salida_detalles` por las cantidades pendientes de cada línea. No cambia saldos ni crea movimiento.
4. En la edición de salida, el operador puede ajustar cantidades (no agregar/quitar líneas desde el repeater). Al confirmar se validan pertenencia de líneas, cantidades positivas y límites pendientes autorizados.
5. La confirmación exige saldo físico y reservado suficientes. En una transacción:
   - resta la cantidad despachada a `stock_saldos.cantidad_actual` y `cantidad_reservada`;
   - actualiza la reserva de la línea a `PARCIAL` o `LIBERADA` según lo despachado frente a lo solicitado;
   - crea `movimientos` tipo `SALIDA`, `movimiento_detalles` y `movimiento_historial_estados`;
   - marca la salida `CONFIRMADA` con fechas y responsable;
   - recalcula estado de orden.
6. En una salida confirmada, **Registrar entrega** prepara una `entregas` en `BORRADOR` con `entrega_detalles` por la cantidad que resta recibir de cada detalle de salida.
7. Se revisan las cantidades recibidas y se confirma la entrega. Se valida que ninguna cantidad acumulada supere lo despachado. La entrega actualiza `fecha_entrega` de la orden y vuelve a recalcular estado, sin tocar el stock.
8. Para una orden todavía incompleta, se puede preparar otra salida con las cantidades pendientes autorizadas y repetir los pasos 5–7.

### 4B. Ruta integrada: botón “Entregado” en orden verificada

El botón **Entregado** no solo marca una entrega. `EntregaService::confirmarOrdenVerificada` ejecuta todo en una transacción:

1. Exige que la orden esté `VERIFICADO` y que el usuario tenga `Entregar:Orden`.
2. Prepara una salida para toda cantidad pendiente.
3. Confirma la salida: descuenta stock y crea el movimiento.
4. Prepara una entrega con las cantidades pendientes de esa salida y la confirma inmediatamente.
5. Marca reservas de las líneas de orden como liberadas con la fecha de entrega.

La ruta integrada no muestra al usuario la revisión intermedia de salida/entrega, aunque esos documentos sí quedan persistidos. Si no hay stock/reserva suficiente o alguna validación falla, la transacción revierte todas las operaciones de la acción.

### 5. Regla de estado de la orden

`OrdenService::actualizarEstadoPorCantidades` calcula cantidades despachadas a partir de salidas **confirmadas** y cantidades entregadas a partir de entregas **confirmadas**:

- `COMPLETADA`: todas las líneas tienen entregada al menos la cantidad solicitada.
- `PARCIALMENTE_ATENDIDA`: hubo al menos un despacho o entrega, pero no está todo entregado.
- `APROBADA`: no hay cantidades despachadas/entregadas confirmadas (cuando esta rutina recalcula).

La fuente declarada para despachado es `salida_detalles` unido a salidas confirmadas. La fuente de entregado es `entrega_detalles` unido a entregas confirmadas. Los borradores no cuentan.

## Casos de uso y excepciones

| Caso | Resultado actual esperado |
| --- | --- |
| Ingreso en borrador | No cambia saldo. |
| Confirmar ingreso válido | Suma físico, actualiza precio vigente y publica el documento de ingreso. |
| Orden borrador | No reserva stock. |
| Aprobar con stock disponible | Aumenta reservado, crea/activa reserva y pasa a `APROBADA`. |
| Aprobar con stock insuficiente | Falla sin reservar parcialmente, por transacción. |
| Salida en borrador | No descuenta saldo ni crea movimiento. |
| Confirmar salida de cantidad parcial | Reduce físico y reservado por la cantidad despachada; reserva queda parcial si resta cantidad solicitada. |
| Confirmar segunda salida | Solo permite las cantidades pendientes; al agotar la línea libera su reserva. |
| Confirmar dos veces la misma salida | Es idempotente en el servicio; no duplica descuento ni movimiento. |
| Despachar más de lo autorizado, sin saldo o sin reserva | Falla y revierte la operación. |
| Confirmar entrega | No vuelve a descontar stock; valida lo recibido contra lo despachado. |
| Entrega parcial | La orden sigue parcialmente atendida; puede haber más despacho/entrega. |
| Rechazar orden pendiente | Guarda motivo y libera reservas pendientes, si existen. |
| Cancelar orden aprobada | Guarda motivo, libera solo reserva no despachada; no revierte salidas confirmadas. |
| Rechazar pago | Vuelve a `APROBADA`; conserva la reserva para reintentar pago. |
| Repetir confirmar entrega/salida confirmada | Los servicios salen sin aplicar otra vez el efecto. |
| Anular o borrar documentos confirmados | No se encontró una reversión de stock implementada; requiere política de reverso antes de usarse operativamente. |

## Límites y puntos de atención al reestructurar

1. **No hay ledger común para ingresos y egresos**: ingreso modifica saldo vía publicación/lineas; salida modifica saldo y crea movimiento. `movimientos` admite tipo `ENTRADA`, pero el ingreso descrito no lo crea. Si se requiere trazabilidad uniforme, definir si cada ingreso debe crear su movimiento y cómo evitar doble contabilización.
2. **`stock_saldos` es saldo mutable** y `movimientos` hoy es una proyección documental de salidas, no la fuente universal que reconstruye el saldo.
3. **Hay dos rutas de despacho** (separada e integrada). La integrada solo se ofrece cuando el pago está verificado y confirma documentos de inmediato; la separada deja borradores editables. Definir cuál será el flujo oficial por rol.
4. **Permisos no alineados**: `encargado_almacen` carece de `Create:SalidaAlmacen`/`Confirmar:SalidaAlmacen`, pero recibe `Entregar:Orden` para completar la ruta integrada. El botón Preparar salida exige crear salida y por eso no necesariamente aparece para ese rol.
5. **Ruta no verificada**: las políticas permiten preparar salida desde estados `APROBADA`, `PAGADO` y `PARCIALMENTE_ATENDIDA`, aunque la acción integrada exige pago verificado. Acordar si el despacho debe poder ocurrir antes de verificar pago.
6. **Reserva liberada en etapas distintas**: confirmar salida libera la parte despachada (o toda la línea si se despachó completa); confirmar entrega integrada vuelve a marcar todas las reservas de la orden como liberadas con la fecha de entrega. Determinar si “liberada” significa consumo/despacho o cierre de entrega y preservar significado consistente.
7. **Fecha de entrega en entregas parciales**: cada entrega confirmada fuerza `ordenes.fecha_entrega` a la fecha actual; el campo termina reflejando la última entrega, no necesariamente una fecha de completitud. Confirmar la semántica deseada.
8. **Unicidad y borradores**: el servicio impide más de una salida en borrador por orden y más de una entrega en borrador por salida. Un borrador abandonado bloquea crear otro hasta confirmar, eliminar o implementar anulación.
9. **Las acciones de eliminar y edición deben tratarse con cuidado**: las políticas restringen edición/borrado de documentos confirmados en algunos casos, pero los recursos permiten acciones CRUD y las tablas de detalle tienen permisos independientes. La invariancia debe reforzarse en servicios/políticas, no solo en la interfaz.
10. **Nombre y significado de “publicación”**: el recurso se presenta como “ingreso de inventario”, pero la tabla se llama `publicaciones_existencias`; además `publicar()` por sí sola solo cambia estado, mientras `confirmarIngreso()` cambia estado y stock. Mantener separados esos dos métodos al cambiar el flujo.
11. **Orden y estado inicial**: el modelo y la pantalla de creación asignan estado inicial de forma distinta según el punto de entrada. Unificar antes de rediseñar transiciones.
12. **Historial**: `orden_historial_estados` se mantiene para órdenes. El movimiento tiene `movimiento_historial_estados`; las cabeceras de salida/entrega registran fechas/estado, pero no se observó un historial equivalente de transiciones.

## Mapa de implementación

| Responsabilidad | Código principal |
| --- | --- |
| Confirmar ingreso y actualizar saldo | `app/Services/PublicacionExistenciaService.php` |
| Formulario/acción de ingreso | `app/Filament/Resources/PublicacionExistencias/PublicacionExistenciaResource.php` |
| Aprobar, reservar, pagar, verificar y transicionar órdenes | `app/Services/OrdenService.php` |
| Preparar/confirmar despacho y crear movimiento | `app/Services/SalidaAlmacenService.php` |
| Preparar/confirmar entrega; ruta integrada de orden verificada | `app/Services/EntregaService.php` |
| Botones de orden, incluyendo “Preparar salida” y “Entregado” | `app/Filament/Resources/Ordens/OrdenResource.php` |
| Recursos de salida, entrega y movimiento | `app/Filament/Resources/SalidaAlmacens/SalidaAlmacenResource.php`, `app/Filament/Resources/Entregas/EntregaResource.php`, `app/Filament/Resources/Movimientos/MovimientoResource.php` |
| Roles y permisos | `database/seeders/RolesPermisosSeeder.php`; políticas en `app/Policies/` |
| Casos probados del flujo de inventario | `tests/Feature/FlujoInventarioTest.php` |

## Recomendación para decidir el nuevo flujo

Antes de cambiar tablas, acuerden y documenten estas decisiones: (a) cuándo se reserva stock y cuándo se descuenta físicamente; (b) si el movimiento es el ledger obligatorio de todo ajuste, incluidos ingresos; (c) si despacho y recepción son etapas distintas o una sola; (d) qué rol captura, valida y confirma cada etapa; (e) qué significa liberar una reserva; (f) cómo se corrigen anulaciones/devoluciones/errores después de confirmar; y (g) qué documento es la fuente de verdad para reconstruir saldos e historial.

## Fuentes revisadas

Migraciones de publicaciones/existencias, saldos, reservas, órdenes, salidas, entregas y movimientos; servicios de inventario/orden; modelos y políticas asociadas; recursos Filament; seeder de roles/permisos; `tests/Feature/FlujoInventarioTest.php`.
