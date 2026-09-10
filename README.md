# Gestor de Proyectos - AVA Montajes

## Descripción

Proyecto desarrollado para la asignatura **TIS**, basado en una problemática real de **AVA Montajes**.

El proyecto busca desarrollar una plataforma que permita **centralizar y gestionar tareas**, facilitando la asignación de responsables, fechas, avances y la trazabilidad de las actividades.

## Problemática

Actualmente, la gestión de tareas se realiza mediante herramientas como **Excel, correo electrónico, WhatsApp y llamadas**, lo que dificulta mantener la información centralizada y realizar un seguimiento adecuado.

## Solución

Desarrollar un sistema de **Gestión de Proyectos y Tareas** que permita centralizar las actividades y mantener su trazabilidad.

## Estado del proyecto

Actualmente nos encontramos en la etapa de **levantamiento y análisis de requerimientos**.

- [x] Identificación de la problemática
- [x] Entrevista con el cliente
- [x] Identificación inicial de funcionalidades
- [ ] Diseño de la solución
- [ ] Desarrollo
- [ ] Pruebas

## Tests

El proyecto usa Pest sobre una base Postgres separada (`testing`, no la de desarrollo). **Siempre correr los tests con:**

```bash
composer test
```

**No usar `php artisan test` directo** — el proyecto usa conexiones custom a dos schemas de Postgres (`usuarios`/`laravel`, ver `config/database.php`), y `RefreshDatabase` corriendo `migrate:fresh` dentro del mismo proceso de PHPUnit no logra recrear las tablas de forma confiable en ese setup (se investigó a fondo, no es un problema de configuración simple). `composer test` resuelve esto migrando en un paso aparte (`db:wipe` + `migrate`, cada uno su propio proceso de `artisan`) antes de correr los tests, que solo envuelven cada uno en una transacción (sin migrar nada ellos mismos).

La configuración de la base de testing vive en `.env.testing` (versionado, sin secretos reales — no confundir con `.env`, que es local y no se sube).
