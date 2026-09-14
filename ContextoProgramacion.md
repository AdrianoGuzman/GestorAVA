# Gestor de Proyectos AVA — contexto para IA

Este archivo es contexto compartido para cualquier IA (Claude, ChatGPT, Copilot, etc.)
que ayude a programar este proyecto. Está para que los 4 integrantes trabajen en
paralelo sin pisarse ni duplicar lógica. Si algo de acá quedó desactualizado,
corregirlo en vez de ignorarlo.

**Ojo:** este archivo es el único lugar donde las IAs de los 4 integrantes se pueden
"leer" entre sí. Un compañero se puede enterar de algo por WhatsApp o en el standup,
pero su IA no tiene memoria de esa conversación ni de nada que no esté escrito acá --
si algo importante para otro módulo queda solo dicho de palabra, para efectos
prácticos esa IA no se entera nunca.

## Cuándo actualizar este documento

- **Al mergear tu rama → Dev**: si lo que hiciste le importa a otro módulo (algo que
  estaban esperando para seguir, una decisión de diseño que los afecta, un bug
  compartido, un patrón pensado para que reutilicen), agregalo acá. Ajustes visuales,
  refactors internos o bugs sin impacto afuera del propio módulo no hace falta
  anotarlos -- para eso ya está el historial de git.
- **Al mergear Dev → tu rama** (cuando te traés lo de los demás): el rol se invierte,
  es más leer y reconciliar que escribir. Revisar (1) si algo que tenías anotado acá
  como "pendiente" o "bloqueado por X" ya se resolvió con lo que trajiste, y (2) si
  algo de lo que trajiste vuelve obsoleta o contradice una nota existente -- en ese
  caso, corregirla en el momento, no dejarla como está.

## Módulos y quién es dueño de cada uno (Sprint 1)

- **Elian** — Auth/roles (RF-01 a RF-03).
- **Jeremy** — Checklist (RF-23). Modelo `ChecklistItem` ya existe (`app/Models/ChecklistItem.php`),
  con su migración y factory. Extender ahí, no crear uno nuevo.
- ~~**Oscar** — Dependencias entre tareas (RF-21/RF-22).~~ **Implementado por Jeremy el
  12-09-2026** (ver nota más abajo, sección "Guards de completado") porque era el hueco más
  grande y urgente del sprint y Oscar no había empezado. Oscar: revisa esa nota antes de tocar
  `tareas`/dependencias para no duplicar trabajo -- si te queda algo específico de RF-21/22
  por ajustar, coordínalo con Jeremy en vez de reescribirlo desde cero.
- **Franco + Claude** — ciclo de vida central de la tarea (RF-04 a RF-13) y los servicios
  compartidos: `HistorialService`, `NotificacionService`, `PermisosService`.

Antes de tocar un archivo que no es "tuyo" (sobre todo `app/Models/*`, migraciones, o los
servicios compartidos), avisar en el grupo — ahí es donde salen los conflictos de merge.

## Arquitectura (seguir este patrón para todo lo nuevo)

`Controller → Service (lógica de negocio) → Repository (acceso a datos, solo si aplica) → Eloquent Model`

- Los `FormRequest` (`app/Http/Requests/`) validan input, no tienen lógica de negocio.
- Los Services son los únicos que orquestan transacciones, historial y notificaciones.
- Reglas de negocio complejas (transiciones de estado, jerarquías, permisos) viven en
  **Enums** (`app/Enums/`) como métodos, no repetidas como `if` sueltos en controllers/services.
  Ejemplos: `EstadoTarea::puedeTransicionarA()`, `NivelJerarquico::esSuperiorA()`.

## Qué ya existe (revisar antes de crear algo nuevo)

- **Enums de dominio** (`app/Enums/`): `NivelJerarquico`, `TipoUnidad`, `EstadoTarea`, `TipoEvento`, `TipoNotificacion`.
- **Modelos**: `User`, `UnidadOrganizacional`, `Tarea`, `ChecklistItem`, `HistorialTarea`, `Notificacion`.
  Todos con factory (`use HasFactory;`) para tests.
- **Servicios compartidos, reusar en vez de duplicar lógica**:
  - `HistorialService::registrar()` — registrar cualquier evento sobre una tarea.
  - `NotificacionService::notificarAsignacion()` — crea notificación in-app + dispara mail.
  - `PermisosService` — quién puede reasignar, autorizar excepciones, o agregar colaboradores
    sobre una tarea.
- **Ya implementado (backend + frontend)**: RF-04 (crear tarea; también `TareaService::actualizar()`
  para editar título/descripción/fechas después de creada, sin tocar responsable/colaboradores --
  solo responsable o creador, bloqueado en tareas completadas/canceladas), RF-05 (reasignar
  responsable, incl. RN-12), RF-06 (agregar colaborador), RF-09 (Mis tareas, backend + frontend),
  RF-10 (transición automática), RF-11 (completar), RF-12 (retroceso),
  RF-13 (reportar problema, rediseñado -- ya no cambia el estado de la tarea, ver
  `ReporteProblemaService.php`), RF-14 (detección automática de atraso + notificación al
  responsable/colaboradores, ver nota abajo), RF-15 (recordatorio de vencimiento próximo,
  ver nota abajo), RF-19 (adjuntar evidencia),
  RF-24 (vista de detalle, `resources/js/pages/tareas/show.tsx`), RF-25 (cancelación).
  Ver `app/Services/TareaService.php`, `ReasignacionService.php`, `ColaboradorService.php`,
  `FinalizacionService.php` como referencia de cómo está armado el patrón Controller→Service.
- **Ya implementado por Elian (11/12-09-2026): RF-01, RF-02, RF-03 y RNF-08 completos.**
  Login (ya venía del scaffold de Breeze, generic error message verificado con test), niveles
  jerárquicos + unidad organizacional con seed de prueba (`UnidadOrganizacionalSeeder`,
  `UsuarioSeeder` -- un usuario de prueba por nivel: `gerencia@ava.cl`, `jefearea@ava.cl`,
  `asistente@ava.cl`, todos password `password`; `admin@ava.cl` queda como Directorio),
  redirección post-login a `mis-tareas.index` para los 4 niveles (gracias a que Franco liberó
  el bloqueo de RF-09, ver commit `7a4a12d`), menú diferenciado por nivel (`Administración`
  visible solo para Directorio/Gerencia vía `auth.puedeAdministrarEstructura`, compartido desde
  `HandleInertiaRequests` para no duplicar la regla en el frontend), y pantalla mínima de
  administración de usuarios (`/administracion/usuarios`, crear + editar nivel/unidad).

**RF-14 (11-09-2026): `esta_atrasada` ahora se marca sola.** Antes solo se seteaba a mano en
tests/factories -- una tarea nunca se marcaba atrasada en la app real aunque se pasara la
fecha de compromiso. Ahora `app/Console/Commands/MarcarTareasAtrasadas.php` (via
`DeteccionAtrasoService`) corre cada hora (`routes/console.php`, ya lo levanta el
`worker-schedule` del docker compose, no hace falta tocar infraestructura) y marca como
atrasadas las tareas Pendiente/EnProgreso cuya `fecha_compromiso` ya pasó por completo (al
día siguiente, no el mismo día). Queda un evento nuevo en el historial,
`TipoEvento::TareaAtrasada` -- si tenés algo que filtra o cuenta tipos de evento, agregalo ahí.
Desde el 12-09-2026 también notifica (in-app + mail) al responsable y a cada colaborador --
antes la marca quedaba muda, nadie se enteraba sin entrar a mirar la tarea.

**RF-15 (12-09-2026): recordatorio de vencimiento próximo, ya activo.** Existía la clase
`TareaProximaAVencerNotification` desde el commit `547b5f2` (8-09-2026) pero nunca se llamaba
desde ningún lado. Ahora `app/Console/Commands/NotificarTareasProximasAVencer.php` (via
`RecordatorioVencimientoService`) corre una vez al día a las 08:00 y avisa (in-app + mail) al
responsable y a los colaboradores cuando a una tarea Pendiente/EnProgreso le quedan
**exactamente 2 días** para su `fecha_compromiso`. Es un aviso de una sola vez -- usa la
columna nueva `recordatorio_vencimiento_enviado` para no repetirse (a diferencia de
`esta_atrasada`, que se mantiene mientras la condición sea verdadera, este es un flag que una
vez en `true` no vuelve a `false`). Evento nuevo en el historial: `TipoEvento::TareaProximaAVencer`.

**Campana de notificaciones, por Elian (12-09-2026): las notificaciones in-app ya se veían en
ningún lado.** Las `Notificacion` que crea `NotificacionService` (delegación, retroceso, atraso,
próximo vencimiento, etc.) se guardaban en la tabla pero no había ninguna pantalla que las
mostrara. Ahora hay una campana en el header de toda la app (`AppSidebarHeader` →
`CampanaNotificaciones`) con contador (`auth.notificacionesNoLeidas`, compartido en cada
página), lista de las últimas 15, marcar una o todas como leídas, y click para ir a la tarea.
Esto es automático para **cualquier** notificación ya existente o futura creada vía
`NotificacionService` -- si agregás un tipo nuevo (ej. para dependencias, RF-21/22), solo hace
falta sumarle un ícono en `ICONOS` (`campana-notificaciones.tsx`) y un caso en
`TipoNotificacion`/`resources/js/types/notificacion.ts`; no hay que tocar la campana en sí.

**"Editar tarea" (12-09-2026):** no existía forma de corregir título/descripción/fechas después
de creada -- la única opción era cancelar y crear de nuevo. Ahora `PATCH /tareas/{tarea}`
(`TareaService::actualizar()`) lo permite; solo responsable o creador, bloqueado si la tarea
está completada/cancelada. Si la fecha corregida ya no está vencida, `esta_atrasada` se limpia
sola. No toca responsable/colaboradores, eso sigue con su propio flujo (RF-05/RF-06).

**Dos bugs de fondo que encontró Elian al probar en un entorno real (no solo `composer test`),
relevantes para cualquiera que arme su propio `.env` local:**
1. **`DB_CONNECTION` en `.env` debe ser `usuarios`, no `pgsql`.** `.env.example` y
   `.env.testing` ya lo tienen bien, pero un `.env` local mal copiado con `DB_CONNECTION=pgsql`
   hace que **cualquier** regla de validación `exists:`/`unique:` con nombre de tabla pelado
   (sin prefijo de conexión) busque la tabla en el schema `public` en vez de `usuarios` y tire
   `relation ... does not exist` -- silenciosamente, porque las queries de los *modelos*
   Eloquent sí usan la conexión correcta (`protected $connection = "usuarios"`) y no fallan, así
   que solo se nota al ejercitar una validación real. Si a alguien más le pasa esto: revisar
   `DB_CONNECTION` en su `.env` local antes de sospechar del código.
2. **`storage/logs` y `bootstrap/cache` necesitan permisos abiertos** en el contenedor Docker de
   cada uno (PHP-FPM corre como `www-data`, pero `composer`/`artisan` corren como `root` al
   armar la imagen, así que cualquier archivo nuevo queda `root:root` y `www-data` no puede
   escribirlo). Si a alguien le aparece un 500/504 sin nada en `storage/logs/laravel.log`, correr
   `chmod -R 777 storage bootstrap/cache` dentro del contenedor.

**Fix de Elian (12-09-2026) sobre `ProfileUpdateRequest`/`settings/profile.tsx`:** el "bug
conocido" de cambio de nombre en el perfil (documentado en `docs/contexto-diseno-ia.md`) era
que el formulario editaba un campo `name` que no es una columna real (es el accessor
`getNameAttribute()`), así que guardar no hacía nada. Ahora edita `nombre_1`/`nombre_2`/
`apellido_1`/`apellido_2` directamente, igual que el registro (que ya estaba bien). El
`RegistrationTest`/`ProfileUpdateTest` desactualizados (todavía usaban el `name` viejo de
Breeze) también quedaron al día.

## Guards de completado (importante para Oscar y Jeremy)

RF-11 (completar tarea) tiene que bloquearse si hay dependencias hijas pendientes (RF-22,
Oscar) o ítems de checklist sin marcar (RF-23, Jeremy). En vez de que `FinalizacionService`
conozca la lógica de ambos módulos, existe un punto de extensión:

1. Crear una clase que implemente `App\Contracts\GuardCompletarTareaInterface`
   (método `verificar(Tarea $tarea): array` — devuelve un array de strings con los motivos
   de bloqueo, o `[]` si no bloquea nada).
2. Registrarla en `config/tareas.php`, clave `guards_completar`.

Con eso alcanza — no hay que tocar `FinalizacionService.php` ni `TareaController.php`.
Ejemplo de test que verifica el mecanismo: `tests/Feature/Tarea/CompletarTareaTest.php`
(casos `un_guard_registrado_*`) y `tests/Support/GuardDeBloqueoDePruebas.php`.

**RF-23 (Jeremy, 12-09-2026): `App\Guards\ChecklistPendienteGuard` ya está implementado y
registrado en `config/tareas.php`.** Bloquea completar si `tarea->checklistItems()` tiene algún
`completado = false`.

**RF-21/22 (Jeremy, 12-09-2026): dependencias entre tareas completo, no solo el guard.**
Como Oscar no había empezado y era el hueco más grande del sprint, Jeremy implementó todo:
`App\Services\DependenciaService::crearTareaHija()` (RF-21, reusa `TareaService::crear()` con
`tarea_padre_id`), `App\Guards\DependenciasPendientesGuard` (RF-22, ya registrado en
`config/tareas.php` junto al de checklist), permiso `PermisosService::puedeCrearTareaHija()`
(responsable o colaborador de la tarea padre), y la tarjeta "Dependencias" real en el frontend
(`dependencias-section.tsx`, ya no es el placeholder). Si Oscar necesita algo más de RF-21/22
(ej. otro criterio de permisos, otra vista), partir de esto y coordinar el cambio, no reescribir.
`guards_completar` en `config/tareas.php` es un array compartido -- si se agrega otro guard,
hacerlo con append, no reemplazando el array completo.

## Convenciones de UI para Jeremy y Oscar (cuando construyan su frontend)

La vista de detalle (`resources/js/pages/tareas/show.tsx`) tenía reservadas dos tarjetas
placeholder, una al lado de la otra: **Checklist** (RF-23) y **Dependencias** (RF-21/22).
Ambas ya tienen su componente real (Jeremy construyó las dos, ver nota en "Guards de
completado" sobre RF-21/22) -- si alguien más toca esa vista, seguir reemplazando dentro de
la misma tarjeta, no moverla ni renombrarla.

**Decisiones sobre RF-23 (Jeremy) confirmadas con Franco (10-09-2026, ajustada 12-09-2026), distintas de la spec original:**
- **El responsable o el creador de la tarea asignan el dueño de un ítem** — no hay
  autoasignación por parte de un colaborador, para evitar confusión en la interfaz. Es una
  restricción más estricta que el D1.4 de la spec original ("autoasignación permitida");
  prevalece esta decisión. (Ajuste 12-09-2026: originalmente solo el creador podía hacerlo;
  se amplió al responsable porque es quien más de cerca sigue el trabajo día a día.)
- **La sección de Checklist solo se muestra si la tarea tiene colaboradores.** Si el
  responsable es el único involucrado (sin colaboradores), esa tarjeta no debe aparecer —
  en ese caso el responsable usa un "checklist personal" propio (ver abajo, no es RF-23).
- **Actividad chica y binaria → checklist (RF-23). Actividad grande que necesita su propio
  responsable y seguimiento → tarea hija (RF-21/22), no un ítem de checklist.** Esta es la
  regla para decidir cuándo algo es un ítem de checklist vs. cuándo debería ser una tarea
  dependiente completa.
- **Ajuste 13-09-2026 (Franco): un ítem de checklist ahora puede tener `fecha_limite`
  opcional**, ademas del dueño — se agregó `fecha_limite` (date, nullable) a
  `checklist_items` (ver migración `2026_09_13_180000_add_fecha_limite_to_checklist_items_table`)
  y se expone en `ChecklistItem::$fillable`/`$casts`. Se puede fijar solo al crear o editar el
  ítem (`CrearChecklistItemRequest`/`EditarChecklistItemRequest`, `ChecklistService::crear()`/
  `editar()`), sin permiso especial (a diferencia del dueño, que sí requiere
  `puedeAsignarDuenoChecklist`) — cualquiera con `puedeUsarChecklist` puede ponerla. Al crear
  exige `after:today` (igual que `fecha_compromiso` de una tarea); al editar no, mismo motivo
  que `ActualizarTareaRequest`: no forzar mover una fecha ya vencida solo por corregir el
  texto. Esto relaja un poco la regla de "checklist = binario" de arriba, pero sigue sin
  responsable propio con seguimiento de estado (eso sigue siendo terreno de tarea hija) —
  es solo una fecha de referencia, no una fecha de compromiso con las mismas implicancias.

**Fix de permisos aplicado por Franco (11-09-2026) sobre `ChecklistController`/`ChecklistService`:**
el backend original no tenía ningún control de acceso (cualquier usuario autenticado podía
crear/editar/marcar/eliminar ítems de cualquier tarea vía la URL). Se agregó `PermisosService`
inyectado en `ChecklistService` con tres reglas nuevas:
- `puedeUsarChecklist()` — crear/editar-texto/eliminar: solo responsable o colaborador de la tarea.
- `puedeAsignarDuenoChecklist()` — solo el creador (aplica la regla de arriba, ahora reforzada).
- `puedeMarcarChecklistItem()` — **nueva regla de Franco**: si el ítem tiene `dueno_id`, **solo
  ese usuario** puede marcarlo/desmarcarlo (ni el responsable ni otro colaborador pueden hacerlo
  por él, para evitar que alguien declare terminado un trabajo que no es suyo). Si el ítem no
  tiene dueño asignado, cualquiera con acceso al checklist puede marcarlo.
Si construyes UI nueva sobre estas acciones, estos permisos ya vienen resueltos desde el
controller (lanzan `PermisoDenegadoException` → sesión con `error`), no hace falta duplicarlos
en el frontend, pero sí ocultar/deshabilitar el botón de marcar si `auth.user.id !== item.dueno_id`
para no mostrar una acción que el backend va a rechazar.

**Decisión sobre RF-21/22:** el responsable de una tarea hija se muestra bien visible en la
sección Dependencias de la tarea padre (con avatar, `PersonaAvatar`, link a su propio detalle)
— pero **no se agrega como colaborador** de la tarea padre (`colaboradores_tarea`). Son
conceptualmente distintos: un colaborador comparte la misma tarea y hereda sus permisos
(RF-06); el responsable de una tarea hija tiene su propia tarea separada y no debería tener
permisos sobre la tarea padre solo por estar vinculado como dependencia. Ya implementado así
en `dependencias-section.tsx`.

**Ojo: la sección Dependencias NUNCA se oculta por falta de colaboradores** (a diferencia de
Checklist, ver arriba) — incluso una tarea chica y sin colaboradores puede necesitar pedir
ayuda externa creando una tarea hija, así que esa tarjeta siempre está disponible
(`DependenciaService::crearTareaHija()` no depende de que la tarea padre tenga colaboradores).

**La evidencia cruzada de tareas hijas ya funciona sola, sin nada extra:** cuando alguien sube
un adjunto con categoría "evidencia" (RF-19) en una tarea que tiene `tarea_padre_id` seteado,
ese archivo aparece automáticamente en "Necesarios para la tarea" de la tarea padre (ver
`AdjuntoService::deTareasHijas()` y `resources/js/components/tareas/adjuntos-section.tsx`).
Ahora que `DependenciaService::crearTareaHija()` (RF-21) setea `tarea_padre_id` al crear la
hija, esto ya se ejercita en la práctica sin que nadie tuviera que tocar nada de adjuntos.

**Decisión explícita: cancelar (RF-25) NO se bloquea por tareas hijas pendientes.**
`DependenciasPendientesGuard` solo se consulta al completar (RF-11/RF-22, `guards_completar`);
`CancelacionService` no consulta ningún guard. Es intencional, no un olvido: ni la spec de
RF-22 ni la de RF-25 piden bloquear la cancelación, así que agregarlo sería alcance no
solicitado. Si el equipo decide que sí debería bloquearse, es un cambio chico (agregar la
misma consulta a `tareasHijas()` en `CancelacionService::cancelar()`), pero no se hizo sin que
alguien lo pida explícitamente.

**Decisión explícita (Franco, 13-09-2026): la línea de tiempo de "Actividad" ahora muestra
qué cambió en cada edición, no solo quién y cuándo.** El backend ya guardaba el antes/después
completo de varias acciones (`TareaService::actualizar()`, `ChecklistService::editar()`,
`ReasignacionService::ejecutar()`) pero `historial-timeline.tsx` solo renderizaba el `motivo`
cuando existía — el resto de `datos_evento` se descartaba en el render. `construirDetalles()`
(en `historial-timeline.tsx`) arma esas líneas de detalle por tipo de evento:
- `tarea_editada` / `checklist_item_editado`: diff campo por campo contra `datos_anteriores`,
  mostrando **solo los campos que realmente cambiaron** (`diffCampos()`) — la preocupación de
  Franco era que mostrar los 5 campos de una edición aunque solo se haya movido una fecha
  satura la línea de tiempo sin aportar nada. Los textos largos (descripción, texto de una
  subtarea) se truncan a ~50-60 caracteres.
- `reasignacion` / `reasignacion_excepcional`: responsable anterior → nuevo, resolviendo el id
  a nombre contra la lista completa de `usuarios` (ya se pasaba a `TareaDetalleContent`, solo
  faltaba enhebrarla hasta el timeline). De paso se corrigió que el motivo de la excepción
  (`motivo_excepcion`) no se mostraba nunca — el chequeo original solo miraba la clave `motivo`.
- `colaborador_agregado` / `tarea_hija_creada`: quién se agregó / qué tarea hija se creó, dato
  que el backend ya guardaba (`colaborador_id`, `titulo`) sin usarlo en ningún lado.

Se agregó tracking de `fecha_limite` al historial de edición de una subtarea
(`ChecklistItem::fecha_limite`, ver `ChecklistService::editar()`) porque antes solo quedaba
registrado texto/dueño — un hueco real dado que el propósito de esto es justamente no perder
cambios. Si en algún momento la lista de eventos por tarea crece tanto que estos detalles
saturan igual (fue la preocupación inicial de Franco), la salida más simple es un toggle
compacto/detallado en `HistorialInline`, no implementado todavía porque no hizo falta.

**Decisión explícita (Franco, 13-09-2026): una tarea completada o cancelada es de solo
lectura — no se puede hacer nada más que verla.** Primer intento (bloquear solo "agregar
cosas nuevas" y dejar editar/marcar/eliminar lo existente) quedó corto: Franco encontró que un
ítem de checklist con dueño se podía seguir marcando/desmarcando después de completar la tarea
("el completar todavía deja seleccionar"), y aclaró que la regla real es más simple: **tarea
terminal (`EstadoTarea::esTerminal()`) = de solo lectura, sin excepciones.**

Una vez que `Tarea::estado` es terminal, esto queda bloqueado (rol correcto o no):
- Editar la tarea (`puedeEditar`), reasignar responsable (`puedeReasignar`, incluida la
  excepción RN-12 vía `puedeAutorizarExcepcion`).
- Agregar colaboradores (`puedeAgregarColaborador`), crear tareas hijas (`puedeCrearTareaHija`).
- Checklist compartido y personal completos — crear, editar, marcar/desmarcar, eliminar
  (`puedeUsarChecklist`, `puedeUsarChecklistPersonal`, `puedeMarcarChecklistItem`). Importante:
  el chequeo de dueño de un ítem (`puedeMarcarChecklistItem`) NO pasaba por
  `puedeUsarChecklist`, así que el estado terminal se valida ahí aparte; mismo caso en
  `ChecklistPersonalService::alternar()/eliminar()`, que solo validaban dueño del ítem.
- Adjuntar archivos (`puedeAdjuntar`), reportar problema (`puedeReportarProblema`), avisar no
  participación (`puedeReportarNoParticipacion`).
- Cancelar: ya estaba bloqueado por el guard de estado en `CancelacionService`; lo nuevo es que
  el botón "Cancelar" tampoco se muestra (`puedeMostrarCancelar`).

Lo único que sigue andando sobre una tarea terminal es **ver** su info — la tarea, el
checklist, los adjuntos, el historial, todo sigue siendo visible, solo no editable.

Para `puedeCancelar`/`puedeCompletar`/`puedeEditar`, que ya tenían su propio guard de estado
con mensaje específico en el service (`CancelacionService`, `FinalizacionService`,
`TareaService::actualizar()`) y tests que esperan ese mensaje exacto (`assertSessionHasErrors`),
NO se tocó el método base ni el orden de los checks — se agregó un método
`puedeMostrar*` aparte (`puedeMostrarCancelar`, `puedeMostrarCompletar`, `puedeMostrarEditar`)
usado solo para la UI, que combina el permiso de rol con el estado terminal. Para el resto
(reasignar, colaboradores, tareas hijas, checklist, adjuntos, reportar problema/no
participación), que no tenían ningún guard de estado previo, el chequeo de terminal se agregó
directo al método `puedeX` — sirve a la vez de guard real y de flag para la UI.

**Decisión explícita (Franco, 13-09-2026): en "Mis tareas" (RF-09), las tareas completadas y
canceladas se ocultan por defecto del listado, pero nunca se archivan ni se eliminan.** AVA
pidió trazabilidad fuerte, así que "mover a otra pantalla" o esconderlas sin salida no era
opción — la solución fue cambiar el *default* del filtro de estado que ya existía, no agregar
un concepto nuevo de archivo/historial. `MisTareasService::obtener()` aplica
`ESTADOS_ACTIVOS_POR_DEFECTO` (`pendiente`, `en_progreso`) solo cuando la clave `estado` no
viene en absoluto en `$filtros` (no cuando viene vacía — con Inertia ambos casos son
indistinguibles en la query string, así que "Limpiar filtros" también cae en este default, a
propósito). Los filtros efectivos se devuelven en `$resultado["filtros"]` y viajan al frontend
sin cambios en `mis-tareas/index.tsx`: las chips "Pendiente"/"En progreso" del filtro de Estado
(ya existente) quedan preseleccionadas solas, y ver las completadas es un clic en la chip
"Completada" — no se agregó tab ni componente nuevo. El historial de eventos de cada tarea
sigue intacto y visible en el detalle, sin tocar.

**Decisión explícita (Franco, 13-09-2026): "Reportar problema" (RF-13) y "No puedo ser
parte" ya no se ofrecen cuando el responsable es también el creador de la tarea.**
Ambas acciones notifican "al otro extremo" (`ReporteProblemaService`/
`NoParticipacionService::obtenerDestinatario()`): si reporta el responsable, le llega al
creador; si reporta un colaborador, le llega al responsable. Cuando la misma persona es
responsable y creador, ese destinatario es ella misma -- antes el service ya evitaba
enviar la notificación en ese caso (`$destinatario->id !== $solicitante->id`), pero la
acción seguía disponible y "funcionaba" sin avisarle a nadie, sin que quedara claro por
qué. Ahora `PermisosService::mismaPersonaEnAmbosExtremos()` bloquea el permiso directamente
(`puedeReportarProblema`/`puedeReportarNoParticipacion` devuelven `false`), así el botón ni
aparece en el frontend (ambos ya estaban condicionados a esos permisos) y el intento por
ruta directa devuelve un mensaje explicando la alternativa real: editar la tarea (si el
problema es la definición) o reasignarla (si no podés seguir con ella). Un colaborador que
además sea el creador SÍ puede seguir usando ambas acciones -- le llegan a un responsable
distinto, no hay auto-notificación en ese caso.

**Decisión explícita (Franco, 13-09-2026): exportar una tarea a PDF y Excel (trazabilidad
"para llevar" fuera de la app).** `ExportarTareaController` (rutas `tareas.exportar-pdf` /
`tareas.exportar-excel`, sin permiso propio -- quien puede abrir `/tareas/{tarea}` puede
exportarla) arma las mismas 3 tablas para ambos formatos vía `ExportacionTareaService`
(datos generales, subtareas -- solo el checklist compartido, "Mi checklist" es privado y no
sale del registro de la tarea --, e historial): una sola fuente de verdad en vez de duplicar
el armado de filas. El Excel usa `TareaExport implements FromView` (Maatwebsite) -- el Blade
(`resources/views/exports/tarea.blade.php`) es una tabla HTML con estilos inline por celda,
que Maatwebsite convierte en celdas reales de Excel (colores, fusión, autosize), no una
imagen. Un vistazo real generado y revisado con openpyxl confirma que los colores de marca
(`#A0F700` verde-5 para encabezados de sección, `#ECF3E5` verde-1 para encabezados de tabla)
llegan igual que en las planillas de referencia de AVA (`CONTEXTO/Planillas excel AVA/*.xlsx`).
El PDF (`resources/views/pdf/tarea.blade.php`, dompdf) usa el mismo esquema de colores y
lleva el isotipo AVA (`public/images/logo-ava.png`, recortado del PNG oficial en
`CONTEXTO/ENTREGA FINAL/Logotipo & Isotipo/`).

**Actualización (Franco, 13-09-2026 D2): "igual de detallado" -- se sumó el mismo detalle
campo-por-campo que ya tiene `historial-timeline.tsx`.** `ExportacionTareaService::detalles()`
es un port a PHP de `construirDetalles()`/`diffCampos()` del frontend (mismos campos, mismos
truncados a 50-60 caracteres, misma resolución de `responsable_anterior_id`/`dueno_id` a
nombre) -- **si esa lógica cambia en el frontend, hay que actualizar las dos** (no hay una
sola fuente de verdad entre TS y PHP todavía; se evaluó y no valía la pena la abstracción
extra solo para 2 consumidores). Cada fila de historial trae una lista de líneas (motivo +
diffs), unidas con `<br>` en los Blade de PDF/Excel. En el Excel, `TareaExport` fija anchos de
columna (`WithColumnWidths`, no `ShouldAutoSize` -- con contenido multilínea el autosize deja
una columna absurda) y activa `wrapText` con un evento `AfterSheet` para que el detalle se lea
en varias líneas dentro de la celda en vez de cortarse. `TipoEvento::label()`, `EstadoTarea::
label()` y `PrioridadTarea::label()` (nuevos métodos en los enums) son la única pieza que ya
comparten frontend y backend -- mismo texto que `ETIQUETAS_EVENTO`/`ESTADO_TAREA_LABELS`/
`PRIORIDAD_TAREA_LABELS` en el frontend, a mano por ahora (no hay generación automática).

**RF-18 (duplicar tarea) fue removido por completo, no solo ocultado.** Franco decidió que no
convenía como funcionalidad — se eliminaron `DuplicarTareaService`, `DuplicarTareaRequest`,
`DuplicarTareaDialog`, la ruta `tareas/{tarea}/duplicar` y el flag `puedeDuplicar`. Si en algún
momento se quiere retomar, hay que reconstruirlo desde cero (o desde el historial de git), no
queda nada parcial dando vueltas.

**Checklist personal** (`ChecklistPersonalItem`, distinto del checklist compartido de RF-23):
ya está construido — privado, sin dueño que asignar, no bloquea nada, siempre disponible sin
importar si hay colaboradores. Ver `ChecklistPersonalService.php`.

Reusar en vez de crear de nuevo:
- `PersonaAvatar` (`resources/js/components/tareas/persona-avatar.tsx`) — avatar circular
  con iniciales + tooltip con el nombre. Pedido explícito de Franco: cada ítem del
  checklist debe mostrar el avatar del colaborador dueño del ítem (si tiene uno asignado),
  usando este mismo componente.
- `PersonaPicker` (`resources/js/components/tareas/persona-picker.tsx`) — selector de
  personas estilo Trello (click, buscar, elegir), usado hoy para reasignar/agregar
  colaboradores. Sirve igual para elegir el dueño de un ítem de checklist o la tarea a
  vincular como dependencia.
- Los tokens de color de marca (`bg-verde-*`, `text-gris-*`, etc.) y el patrón de
  ícono + texto en los botones de acción (`lucide-react`) — ver `docs/contexto-diseno-ia.md`
  para la paleta completa y las reglas de uso del verde.

## Base de datos

Dos conexiones/schemas en la misma Postgres: `usuarios` (tablas de dominio, conexión por
defecto) y `laravel` (framework: cache, jobs, migrations, sessions). Ver `config/database.php`.
Todo modelo de dominio nuevo debe declarar `protected $connection = "usuarios";`.

## Docker (GestorAVA-docker) — bug conocido: permisos de storage/ (13-09-2026)

**Si algo que escribe a disco (exportar PDF/Excel, subir un adjunto, o cualquier cosa nueva
que use `Storage`/`storage_path()`) tira `Permission denied` en el navegador pero anduvo bien
en un test o en `tinker`, es esto.**

Causa: `storage/` y `bootstrap/cache/` viven en el bind mount (`../GestorAVA:/var/www/app`,
ver `compose.yml`) -- el `chown www-data:www-data` que hace el `Dockerfile` corre sobre la
imagen en build time, pero en tiempo de ejecución eso queda tapado por los archivos reales
del host. `docker compose exec laravel-app <comando>` entra como **root** (no hay `USER` en
el Dockerfile), así que cualquier comando que escriba algo nuevo ahí -- `composer test`,
`artisan tinker`, etc. -- deja ese archivo `root:root`. `php-fpm` corre como `www-data` (ver
`php-fpm.d/www.conf`) y no puede volver a escribirlo ni loguearlo (por eso el error a veces ni
aparece en `storage/logs/laravel.log` -- Laravel tampoco puede escribir el log).

**Arreglo manual si te pasa:**
```bash
docker compose exec laravel-app sh -c "chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

**Arreglo permanente (ya aplicado en la copia de Franco, pero `GestorAVA-docker` NO es un
repo git -- si tu carpeta Docker es una copia separada, tenés que aplicarlo vos también):**
`php/entrypoint.sh` reaplica ese chown/chmod cada vez que el contenedor arranca, antes de
levantar `php-fpm`/el worker. Wireado en `php/Dockerfile` (`ENTRYPOINT ["entrypoint.sh"]`,
`CMD ["php-fpm"]`) -- aplica a los 4 contenedores que comparten ese Dockerfile (`laravel-app`
y los 3 workers). Después de copiarlo hay que reconstruir: `docker compose build laravel-app
worker-schedule worker-cola-normal worker-cola-pesada` y `docker compose up -d --force-recreate`
esos mismos servicios.

## Tests

Ver [README.md](README.md#tests) — **siempre `composer test`**, nunca `php artisan test` directo
(rompe el setup de dos schemas). Toda funcionalidad nueva lleva sus tests en `tests/Feature/`.

## Git

- Se trabaja en la rama personal de cada uno, nunca directo en `main`.
- Integrar seguido desde `Dev` a la rama propia (mergear Dev→tu rama) para no divergir
  mucho y evitar conflictos grandes.
- Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, etc.), commits chicos y enfocados.
