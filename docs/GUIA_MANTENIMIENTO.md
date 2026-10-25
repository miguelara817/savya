# Guía de mantenimiento y soporte de SAVYA

Guía práctica para entender y modificar este proyecto sin depender de cambios hechos a ciegas. Está escrita contra la estructura actual del repositorio; confirma siempre el código y las versiones instaladas antes de aplicar una receta.

## 1. Mapa rápido

- **Laravel 13 / PHP 8.4**: aplicación y backend. Versiones declaradas en `composer.json`.
- **Filament 5.10.2**: panel en `/admin`; configuración y descubrimiento en `app/Providers/Filament/AdminPanelProvider.php`.
- `app/Filament/Resources`: pantallas de administración (formularios, tablas y acciones). Cada recurso asocia un modelo con páginas.
- `app/Models`: entidades Eloquent, relaciones, casts y comportamiento cercano a los datos.
- `app/Services`: operaciones de negocio que modifican varios registros o requieren consistencia; por ejemplo `OrdenService`, `SalidaAlmacenService` y `EntregaService`.
- `app/Enums`: vocabulario cerrado de dominio; `OrdenEstadoCodigo` es la fuente de verdad de códigos de estado.
- `app/Policies`: autorización por modelo/acción. Filament Shield complementa permisos/roles.
- `database/migrations`: evolución del esquema. `database/seeders`: datos iniciales/referenciales, incluyendo `OrdenEstadoSeeder`.
- `tests/Feature`: verificación de flujos. Entre otros: `FlujoInventarioTest.php`, `DashboardTest.php`.

## 2. Filament: modelo mental y ciclo de registro

Filament proporciona el panel y sus componentes; no reemplaza la lógica de negocio de Laravel.

1. **Panel**: `AdminPanelProvider` registra el panel, URL, autenticación, plugins y descubrimiento de recursos/páginas/widgets.
2. **Resource**: representa la interfaz para un modelo. En Filament 5 este proyecto define `form(Schema $schema): Schema` y `table(Table $table): Table`.
3. **Schema/formulario**: componentes como `TextInput`, `Select`, `Repeater` y `Section` describen campos, reglas de validación, estado reactivo y relaciones. `live()` permite actualizar el formulario al cambiar el estado; `dehydrated()` determina qué valores se envían al guardar. Un campo oculto o deshabilitado no es por sí mismo una frontera de seguridad.
4. **Table**: columnas, filtros, ordenamiento y acciones de fila/toolbar.
5. **Pages**: conectan rutas con operaciones. Las páginas genéricas suelen extender `ListRecords`, `CreateRecord`, `EditRecord` o `ViewRecord`; hooks como `mutateFormDataBeforeCreate()` pueden completar datos antes de guardar.
6. **Guardar**: Filament valida y persiste el formulario/modelo (y relaciones del `Repeater` cuando usa `relationship()`). Si la operación tiene reglas de negocio, concurrencia, varios modelos, stock, historial o correo, la página debe delegar al servicio del dominio, no replicar esas reglas en callbacks de UI.
7. **Seguridad**: validar y autorizar en el backend (policies/Gates y servicio), además de ocultar/deshabilitar controles en la interfaz. Ocultar una acción no impide una llamada directa.

Ejemplo para orientarse: `app/Filament/Resources/Productos/ProductoResource.php` muestra un formulario/tabla relativamente sencillo; `app/Filament/Resources/Ordens/OrdenResource.php` concentra componentes y acciones del flujo de órdenes. Este último es grande: al cambiar una acción, localiza primero el método de servicio que invoca y sus pruebas.

**Nota de soporte al formulario de órdenes:** hoy `cantidad_solicitada` usa `minValue(160)` en el Resource. Confirma con el responsable de negocio si ese mínimo es intencional y asegúrate de que la validación de servidor en `OrdenService` tenga la misma regla: actualmente valida que la cantidad sea mayor que cero. Esta diferencia puede permitir o bloquear valores distintos según el punto de entrada y debe resolverse como una decisión de negocio antes de cambiarla.

### Crear o ajustar un formulario

Antes de editar, decide si se trata de:

- **Solo presentación**: etiqueta, columna o placeholder; normalmente basta el Resource.
- **Regla del dato**: ajusta validación de UI y regla del servidor, junto con restricciones de base de datos apropiadas. Un `required()` en Filament no basta para otros puntos de entrada.
- **Persistencia nueva**: modelo/relación, migración, formulario, tabla y permisos según corresponda.
- **Operación de negocio**: acción de Filament que llama un servicio con transacción y autorización; no guardes manualmente múltiples modelos desde el callback.

En `Repeater`, verifica si está enlazado con `relationship()`, qué nombres de claves espera la relación y cómo se calculan los datos derivados. Los importes/cantidades mostrados por el navegador deben recalcularse o validarse en servidor; no confiar en valores derivados enviados por el cliente.

## 3. Ciclo de vida de un registro

Recorrido recomendado para investigar cualquier dato:

**Pantalla/acción → Resource/Page → validación y autorización → servicio de aplicación → modelo/relaciones → transacción y base de datos → historial/notificaciones → tabla/dashboard → pruebas.**

- La **migración** determina qué puede guardar la base de datos; el **modelo** expresa relaciones y casts; la **interfaz** determina cómo lo captura el usuario.
- En altas simples, Filament puede guardar directamente el modelo. En flujos críticos, una acción debe invocar el servicio responsable.
- Usa transacciones cuando un cambio lógico escribe varias tablas. Si hay saldo compartido o riesgo de solicitudes simultáneas, inspecciona locks y orden de bloqueo existentes.
- Mantén historial explícito cuando el negocio requiera saber quién cambió qué/estado y por qué.
- No edites migraciones ya aplicadas para cambiar producción: crea una migración nueva, reversible cuando sea posible.
- Los seeders deben ser idempotentes cuando se vuelvan a ejecutar (el seeder de estados usa `updateOrCreate`). No ejecutes `migrate:fresh` en una base con datos que quieras conservar.

## 4. Ejemplo de dominio: ciclo de una orden

El código vigente está en `OrdenEstadoCodigo`, `OrdenService`, `OrdenResource`, `OrdenEstadoSeeder` y `OrdenPolicy`.

`BORRADOR` → `PENDIENTE_APROBACION` → `APROBADA` → `PAGADO` → `VERIFICADO` → (preparación/despacho parcial o total) → `PARCIALMENTE_ATENDIDA` / `COMPLETADA`.

También existen `RECHAZADA` y `CANCELADA`. La orden aprobada reserva stock; registrar el pago no verifica el pago automáticamente. La finalización depende de las cantidades realmente entregadas. `OrdenService` realiza transiciones, comprobaciones de estado, autorización y escrituras de historial; la interfaz decide qué acción presentar según estado y permisos.

### Agregar un estado: lista de comprobación

No es solo agregar una opción a un Select. Primero especifica qué estados pueden entrar y salir de él, quién puede hacerlo, qué datos deben cambiar y qué sucede con stock, pago, reservas e historial.

1. Agrega el caso y código estable a `app/Enums/OrdenEstadoCodigo.php`.
2. Alinea `database/seeders/OrdenEstadoSeeder.php` (código, etiqueta y descripción) y verifica que el proceso de despliegue ejecute el seeder de forma segura.
3. Actualiza reglas de dominio en `OrdenService`: estados permitidos, transiciones, efectos secundarios y estado final/preparación si aplican. Evita saltarse este servicio desde Filament.
4. Actualiza `OrdenPolicy` y acciones de `OrdenResource`: visibilidad de cada acción y autorización real.
5. Busca consumidores del enum: widgets/gráficos, filtros, consultas, reportes y tests. El dashboard debe seguir contando cada grupo correctamente.
6. Añade pruebas del camino permitido y de al menos una transición/permiso inválido y efectos importantes (historial, reserva, saldo o entrega según aplique).
7. Revisa datos existentes y despliegue: código persistido, compatibilidad con filas históricas y orden de migración/seeder.

Busca todas las referencias antes de cerrar: `OrdenEstadoCodigo`, el código literal nuevo, `estadoCodigo`, `estaEnEstado`, `esFinal` y `permitePrepararSalida`.

## 5. Recetas de mantenimiento

### Cambiar un campo o añadir uno

1. Encuentra el Resource y el modelo con el nombre del campo (`app/Filament/Resources`, `app/Models`).
2. Determina si requiere columna nueva; inspecciona migraciones y relaciones antes de tocar esquema.
3. Crea migración con Artisan; incluye `up()` y `down()` seguros y considera valores existentes, nullable/default e índices.
4. Añade el campo al formulario, validación de servidor, modelo/cast si se requiere, y tabla/detalle.
5. Revisa policy, visibilidad según rol y exposición de datos sensibles.
6. Prueba creación/edición, entradas inválidas y permisos relevantes.

### Cambiar una transición/acción

Localiza la acción en Resource → método del servicio → policy → historial/efectos → widgets/consultas → pruebas. La interfaz puede dar feedback, pero el servicio debe rechazar un estado inválido aunque se llame por otra vía.

### Cambiar roles o permisos

Revisa `app/Policies`, roles/permisos y configuración de Filament Shield. Confirma tanto que el usuario autorizado puede ejecutar la operación como que otro rol no puede invocarla directamente. No uses únicamente `visible()`/`authorize()` de la acción como control de dominio.

### Diagnóstico de soporte

- Error al cargar panel/ruta: `php artisan route:list --path=admin`, logs de Laravel y `AdminPanelProvider`.
- Campo que no persiste: inspecciona `dehydrated`, `disabled`, `mutateFormDataBeforeCreate`, fillable/guarded, relación del `Repeater`, migración y SQL/logs.
- Acción no aparece: estado actual del registro, `visible()`, policy y permiso del rol.
- Acción aparece pero falla: autorización del servicio, precondiciones, excepción de dominio y estado/historial persistido.
- Totales/stock incorrectos: compara stock físico, reservado y disponible; rastrea movimientos, salidas, entregas y reservas. No corrijas saldos manualmente sin identificar el movimiento que falta.
- Correo: seguir `App\Services\CorreoService` y revisar configuración/logs del mailer.

## 6. Flujo de trabajo seguro

1. Reproduce el problema y registra usuario/rol, registro, estado y pasos exactos; evita usar datos reales sensibles en pruebas.
2. Busca referencias del campo/estado/acción y lee archivos relacionados antes de modificar.
3. Confirma versiones instaladas con `composer show --direct` y `package.json`; consulta documentación de la versión antes de introducir APIs nuevas.
4. Cambia primero el dominio/persistencia y después la UI, manteniendo la autorización en servidor.
5. Añade o adapta pruebas significativas. Este proyecto usa Pest; utiliza fábricas existentes y prueba tanto éxito como fallo importante.
6. Verifica en este orden:

```bash
php artisan test --compact tests/Feature/FlujoInventarioTest.php
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Ejecuta el test más estrecho pertinente, no necesariamente `FlujoInventarioTest.php` para cada cambio. El formateador se requiere al modificar PHP.

7. Revisa `git diff` y `git status`; confirma migraciones, seeder, permisos, reportes y comportamiento en UI antes de desplegar.

Comandos útiles: `php artisan route:list`, `php artisan migrate:status`, `php artisan test --compact`, `php artisan make:migration --help`, `php artisan make:test --help`. En producción, respalda la base antes de migrar y usa el proceso de despliegue establecido para el entorno.

## 7. Cuándo conviene escalar o detenerse

Detén el cambio y aclara el requerimiento si altera reservas/saldos, elimina o reinterpreta estados históricos, cambia quién puede ver datos, requiere recalcular datos existentes o no está claro qué significa “completado”. Define primero reglas y casos concretos; la implementación viene después.
