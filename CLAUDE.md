# AVA — Gestor de Proyectos (contexto rápido para Claude Code)

Laravel 13 + Inertia.js + React 19 + TypeScript + Tailwind v4 + shadcn/ui. Proyecto de
Franco (TIS, Sprint 1). Este archivo es orientación rápida — el detalle de decisiones,
RF/RN y arquitectura vive en [ContextoProgramacion.md](ContextoProgramacion.md); leelo
antes de tocar reglas de negocio o permisos, tiene el historial de decisiones explícitas
de Franco con fecha.

## Dónde está cada cosa

- Este repo (`D:\AVA G\GestorAVA`) — código de la app.
- `D:\AVA G\GestorAVA-docker` — Docker Compose (Postgres, nginx, vite, workers). La app
  corre ahí, **no** hay `vendor/` ni `node_modules/` funcionales fuera de los contenedores.
- `D:\AVA G\CONTEXTO\` — specs formales fuera del repo: `Contexto_Claude_Code_Sprint1.md`
  (resumen operativo, más al día que cualquier copia en "Claude outputs"),
  `Requisitos_Sprint1_AVA_LaTeX_2.pdf` (RF/RN completos), brand assets.

## Cómo correr cosas (todo vive en Docker)

```bash
docker compose -f "D:/AVA G/GestorAVA-docker/compose.yml" ps   # ver contenedores
docker compose exec laravel-app composer test                  # SIEMPRE composer test
docker compose exec vite sh -lc "npx tsc --noEmit"              # type-check frontend
```

**Nunca `php artisan test` directo** — el proyecto usa dos schemas de Postgres
(`usuarios`/`laravel`) y `RefreshDatabase` no los recrea de forma confiable en el mismo
proceso. `composer test` migra aparte antes de correr Pest. Ver `README.md#tests`.

El navegador sandbox de Claude no llega a `localhost:8080` (Docker corre en el host,
no en el sandbox) — no asumas que podés probar la UI en vivo vos mismo; corré
tests + `tsc`, y pedile a Franco que verifique visualmente los cambios de UI.

## Git

- Se trabaja en la rama `Franco`, nunca en `main`/`Dev` directo.
- Conventional Commits, commits chicos y enfocados (separar por concern cuando el diff
  lo permite razonablemente — no vale la pena forzar una separación quirúrgica si el
  costo en pasos extra no compensa).
- El push lo hace Franco a mano (GitHub Desktop) — no asumas que hay que pushear salvo
  que lo pida explícitamente.
- La atribución (`Co-Authored-By`) está desactivada globalmente
  (`~/.claude/settings.json` → `includeCoAuthoredBy: false`) — no agregarla en mensajes
  de commit ni de PR.

## Estado reciente (13-09-2026)

Últimos 2 commits en `Franco` (2 por delante de `origin/Franco`, sin pushear):
- `refactor(tareas): elimina la función de duplicar tarea (RF-18)` — se sacó por
  completo (servicio, request, dialog, ruta, tests), no solo se ocultó.
- `feat(tareas): una tarea completada o cancelada queda de solo lectura` — ver la nota
  fechada 13-09-2026 en `ContextoProgramacion.md` para el detalle completo (qué queda
  bloqueado, por qué `puedeCancelar`/`puedeCompletar`/`puedeEditar` usan un método
  `puedeMostrar*` aparte en vez de tocar el guard real).

Si el commit de checklist con `fecha_limite`/dueño (RF-23 extendido, ~12/13-09-2026)
te resulta raro comparado con la spec original ("checklist = binario"), es una decisión
real de Franco que la reemplaza — está documentada en `ContextoProgramacion.md`.
