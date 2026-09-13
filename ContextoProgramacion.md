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

## Tests

Ver [README.md](README.md#tests) — **siempre `composer test`**, nunca `php artisan test` directo
(rompe el setup de dos schemas). Toda funcionalidad nueva lleva sus tests en `tests/Feature/`.

## Git

- Se trabaja en la rama personal de cada uno, nunca directo en `main`.
- Integrar seguido desde `Dev` a la rama propia (mergear Dev→tu rama) para no divergir
  mucho y evitar conflictos grandes.
- Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, etc.), commits chicos y enfocados.
