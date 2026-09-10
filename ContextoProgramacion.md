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
- **Ya implementado (backend)**: RF-04 (crear tarea), RF-05 (reasignar responsable, incl. RN-12),
  RF-06 (agregar colaborador). Ver `app/Services/TareaService.php`, `ReasignacionService.php`,
  `ColaboradorService.php` como referencia de cómo está armado el patrón Controller→Service.

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
