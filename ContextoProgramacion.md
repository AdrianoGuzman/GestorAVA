# Gestor de Proyectos AVA — contexto para IA

Este archivo es contexto compartido para cualquier IA (Claude, ChatGPT, Copilot, etc.)
que ayude a programar este proyecto. Está para que los 4 integrantes trabajen en
paralelo sin pisarse ni duplicar lógica. Si algo de acá quedó desactualizado,
corregirlo en vez de ignorarlo.

## Módulos y quién es dueño de cada uno (Sprint 1)

- **Elian** — Auth/roles (RF-01 a RF-03).
- **Jeremy** — Checklist (RF-23). Modelo `ChecklistItem` ya existe (`app/Models/ChecklistItem.php`),
  con su migración y factory. Extender ahí, no crear uno nuevo.
- **Oscar** — Dependencias entre tareas (RF-21/RF-22). Todavía no hay modelo/migración para esto,
  hay que crearlo (probablemente tabla pivote o columna en `tareas`).
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
- **Ya implementado (backend + frontend)**: RF-04 (crear tarea), RF-05 (reasignar responsable,
  incl. RN-12), RF-06 (agregar colaborador), RF-09 (Mis tareas, backend), RF-10 (transición
  automática), RF-11 (completar), RF-12 (retroceso), RF-13 (reportar problema, rediseñado --
  ya no cambia el estado de la tarea, ver `ReporteProblemaService.php`), RF-19 (adjuntar evidencia),
  RF-24 (vista de detalle, `resources/js/pages/tareas/show.tsx`), RF-25 (cancelación).
  Ver `app/Services/TareaService.php`, `ReasignacionService.php`, `ColaboradorService.php`,
  `FinalizacionService.php` como referencia de cómo está armado el patrón Controller→Service.

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

## Convenciones de UI para Jeremy y Oscar (cuando construyan su frontend)

La vista de detalle (`resources/js/pages/tareas/show.tsx`) ya tiene reservadas dos tarjetas
placeholder, una al lado de la otra: **Checklist** (RF-23, Jeremy) y **Dependencias**
(RF-21/22, Oscar). Reemplazar el contenido de esa tarjeta con el componente real, no
mover ni renombrar la tarjeta en sí.

**Decisiones sobre RF-23 (Jeremy) confirmadas con Franco (10-09-2026), distintas de la spec original:**
- **Solo el creador de la tarea asigna el dueño de un ítem** — no hay autoasignación por parte
  de un colaborador, para evitar confusión en la interfaz. Es una restricción más estricta
  que el D1.4 de la spec original ("autoasignación permitida"); prevalece esta decisión.
- **La sección de Checklist solo se muestra si la tarea tiene colaboradores.** Si el
  responsable es el único involucrado (sin colaboradores), esa tarjeta no debe aparecer —
  en ese caso el responsable usa un "checklist personal" propio (ver abajo, no es RF-23).
- **Actividad chica y binaria → checklist (RF-23). Actividad grande que necesita su propio
  responsable y seguimiento → tarea hija (RF-21/22, Oscar), no un ítem de checklist.** Esta
  es la regla para decidir cuándo algo es un ítem de checklist vs. cuándo debería ser una
  tarea dependiente completa.

**Decisión sobre RF-21/22 (Oscar):** el responsable de una tarea hija debe mostrarse bien
visible en la sección Dependencias de la tarea padre (con avatar, `PersonaAvatar`, link a su
propio detalle) — pero **no se agrega como colaborador** de la tarea padre (`colaboradores_tarea`).
Son conceptualmente distintos: un colaborador comparte la misma tarea y hereda sus permisos
(RF-06); el responsable de una tarea hija tiene su propia tarea separada y no debería tener
permisos sobre la tarea padre solo por estar vinculado como dependencia.

**Nueva idea, todavía sin construir, no es de nadie en particular todavía:** un "checklist
personal" por tarea — privado, visible solo para quien lo crea, no bloquea nada (a diferencia
del checklist de RF-23), y siempre disponible sin importar si hay colaboradores o no. Distinto
de RF-23, no confundir los dos al momento de nombrar componentes/tablas.

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
