# Contexto del proyecto — Gestor de Proyectos AVA Montajes

Este documento es un resumen para que otra IA (o herramienta de diseño) entienda
el proyecto y pueda proponer/ajustar diseño visual con criterio, sin tener que
leer el código. Está pensado para pegarse directo en otra conversación.

## Qué es el proyecto

Sistema de gestión de tareas para **AVA Montajes**, una empresa minera chilena.
Reemplaza el uso actual de Excel/WhatsApp/correo/llamadas para asignar y hacer
seguimiento de tareas dentro de la empresa. Es el trabajo de título/proyecto de
la asignatura TIS (Sprint 1), con entrega 22 de septiembre de 2026. Equipo de 4
personas, cada uno a cargo de un módulo distinto del backend.

## Stack técnico

- **Backend**: Laravel 11 (PHP), PostgreSQL 16, Redis (colas/cache).
- **Frontend**: Inertia.js + React 19 + TypeScript, Tailwind CSS v4 (config
  CSS-first, sin `tailwind.config.js`), componentes shadcn/ui sobre Radix UI.
- **Infra**: Docker Compose (Laravel, Postgres, Redis, Mailpit para correo de
  prueba, Nginx, workers de cola).
- **Notificaciones**: sistema propio in-app (tabla `notificaciones`) + correo
  real vía Laravel Notifications (Mailpit en local).

## Arquitectura de código (por si la IA de diseño necesita ubicar algo)

`Controller → Service (lógica de negocio) → Repository (solo donde aplica) → Eloquent Model`

- Páginas Inertia: `resources/js/pages/` (ej. `tareas/show.tsx`).
- Componentes de dominio: `resources/js/components/tareas/`.
- Componentes UI genéricos (shadcn): `resources/js/components/ui/`.
- Estilos y tokens de marca: `resources/css/app.css` (bloque `@theme`).
- Tipos TS del dominio tarea: `resources/js/types/tarea.ts`.

## Identidad de marca (fuente: `Ava_Manual_2024.pdf`, manual oficial completo)

**Tipografía**: Pangram es la oficial, pero el propio manual indica usar
**Poppins** (Google Fonts) en "formatos digitales: web, presentaciones
corporativas y documentos digitales" — es lo que usa esta app (vía Bunny
Fonts, mismo catálogo que Google Fonts).

**Paleta** (hex exactos, ya cargados como tokens de Tailwind: `bg-verde-5`,
`text-gris-2`, etc.):

| Token | Hex | Uso |
|---|---|---|
| `gris-1` | `#7A7F85` | Texto secundario / bordes |
| `gris-2` | `#2D3238` | Texto principal / fondos oscuros |
| `verde-1` | `#F3FAEC` | Fondo muy claro |
| `verde-2` | `#ECF3E5` | Fondo claro |
| `verde-3` | `#DBE0D5` | Bordes suaves |
| `verde-4` | `#C1F75E` | Acento claro |
| `verde-5` | `#A0F700` | **Verde principal de marca** |
| `verde-6` | `#86CF00` | Hover / estado activo |
| `rojo-1` | `#F80000` | Error / crítico / atrasada |
| `rojo-2` | `#B70000` | Error oscuro |
| `naranjo-1` | `#FF5900` | Advertencia |
| `amarillo-1` | `#FFCB00` | Advertencia suave |
| `azul-1` | `#008DE7` | Info / enlaces |
| `azul-2` | `#04008B` | Info oscuro |
| `indigo-1` | `#6F00FF` | Acento alternativo |
| `morado-1` | `#8E48FF` | Acento alternativo |
| Negro | `#000000` | Protagonista, junto al blanco |

**Regla explícita del manual**: *"el color verde es un color principal [pero]
debe usarse como un detalle en las gráficas, no debe ser el predominante en
la comunicación, se debe priorizar el uso del blanco y negro como
protagonistas"*. Las dos excepciones que el propio manual permite para usar
verde con más fuerza: **botones primarios** y **estados activos**. Por eso en
esta app el botón de acción principal (ej. "Completar") es verde-5 con texto
oscuro, no negro — es la lectura correcta de esa regla, no una desviación.

**Logo/isotipo**: uso principal negro sobre blanco. Hay variantes para fondos
verdes/negros/grises, área de protección basada en el ancho del asta de la
"A" del logotipo, tamaños mínimos (7.5-11px según contexto), y una lista larga
de usos incorrectos (no estirar, no cambiar colores, no mover elementos, no
usar sobre fondos de bajo contraste). El detalle completo de logos e
iconografía está en `Ava_Manual_2024.pdf` (36 páginas) — pedir ese archivo si
se necesita.

**Feedback de diseño del equipo hasta ahora** (Franco, líder de proyecto):
- Al principio se aplicó la regla del verde de forma muy literal (negro
  dominante, verde casi ausente) y resultó "poco intuitivo" — costaba
  entender los botones de acción a simple vista.
- Se corrigió usando verde con más fuerza en: botón de acción primaria,
  chip que indica el rol del usuario en la tarea, y una franja de acento en
  el header de la tarjeta principal.
- Le gustan los patrones de Trello: selector de personas por click+búsqueda
  (no escribir IDs a mano), y **botones con ícono + texto**, no solo texto.
- Van a conseguir una foto de referencia de "Intrava" (otra plataforma
  interna de AVA) para alinear el estilo visual lo más posible a como ya
  luce esa herramienta.
- Quieren que la vista sea "mucho más visual" en general — recién se sumaron
  íconos por sección y una zona de drag-and-drop para adjuntos.

## Modelo de dominio (lo que existe hoy)

**Jerarquía organizacional** (`NivelJerarquico`, de menor a mayor rango):
Asistente → Jefe de área/obra → Gerencia → Directorio. Cada usuario pertenece
a una `UnidadOrganizacional` (tipos: `directorio`, `gerencia`, `obra`,
`area`), que puede tener una unidad padre (árbol).

**Tarea** (`EstadoTarea`): `pendiente` → `en_progreso` → `completada` |
`cancelada`. `completada` y `cancelada` son terminales. No existe un estado
"rechazada" — reportar que una tarea está mal definida (ver más abajo) es
una notificación, no un cambio de estado. El indicador
**"atrasada"** es independiente del estado (un booleano aparte) — una tarea
puede estar "en progreso" Y "atrasada" a la vez, se muestran ambos a la vez.

Roles sobre una tarea (usados también como filtro/etiqueta en toda la UI):
**Responsable** (uno, dueño formal), **Colaboradores** (varios, comparten la
misma tarea sin poder fragmentar el trabajo — el checklist es para eso),
**Creador** (inmutable, siempre tiene visibilidad aunque no participe), y
**Delegó** (alguien que en algún momento reasignó esta tarea a otra persona).

**Historial**: cada tarea tiene un log cronológico completo, append-only, de
eventos (creación, reasignación, agregar colaborador, transición automática,
completar, retroceder, reportar problema, cancelar, adjuntar archivo, y más
adelante dependencias/checklist). Se muestra como timeline en el detalle.

**Reportar problema** (antes se llamaba "Rechazar", se rediseñó porque el
nombre confundía): si el responsable o un colaborador detecta que la tarea
está mal definida, puede reportarlo con un motivo. A diferencia de una
"reasignación" o un "rechazo" clásico, **esto no cambia el estado de la
tarea ni interrumpe el trabajo** — solo notifica a quien puede corregir la
definición: si reporta el responsable, le llega al creador; si reporta un
colaborador, le llega al responsable.

**Notificaciones**: por correo (Mailpit en local) cuando te asignan como
responsable/colaborador, cuando retrocede un colaborador (le llega al
responsable), cuando alguien reporta un problema (le llega a quien puede
corregirlo), y cuando se cancela una tarea (les llega a los colaboradores).

## Qué está construido (funcional, con tests, integrado a la vista de detalle)

- Crear tarea, reasignar responsable (incl. excepción por ausencia total),
  agregar colaboradores, marcar completada, retroceder a pendiente, reportar
  problema (notifica sin cambiar estado), cancelar (cierre definitivo).
- Vista "Mis tareas" (backend, sin página propia todavía): secciones
  Responsable / Colaborador / Delegadas por mí / Creadas por mí, con
  contadores agregados.
- **Vista de detalle de tarea** (`/tareas/{id}`, página real ya construida):
  título, estado + atrasada, descripción, fechas, responsable/colaboradores/
  creador, tu propio rol respecto a la tarea, botones de acción habilitados
  según permisos, selector de personas estilo Trello, historial timeline, y
  **adjuntar archivos** (PDF/imágenes/Word/Excel/ZIP, drag-and-drop, descarga
  autenticada — nada queda expuesto por URL pública), separados en "Necesarios
  para la tarea" y "Evidencia" (categoría elegida al subir, no por quién sube).

## Qué falta (a cargo de otros compañeros, todavía sin UI ni backend propio)

- **Checklist** dentro de una tarea (ítems simples, con dueño opcional,
  bloquea completar la tarea si queda algo sin marcar) — hay una sección
  placeholder reservada en la vista de detalle.
- **Dependencias entre tareas** (tarea padre/hija, bloquea completar el padre
  si hay hijas pendientes) — también con placeholder reservado.
- **Autenticación/roles** (login, registro, cambio de nombre en el perfil —
  este último tiene un bug conocido, no relacionado con diseño).

## Convenciones a respetar si se propone algo nuevo

- Todo el dominio está en español (nombres de tablas, variables, UI).
- Los estados de tarea tienen colores fijos ya decididos: pendiente=gris,
  en progreso=verde, completada=gris oscuro, cancelada=gris claro,
  atrasada=rojo (siempre, independiente del estado, ver arriba).
- Botones de acción: ícono + texto, nunca solo ícono ni solo texto.
- Evitar fondos verdes grandes/dominantes; el verde va en acentos puntuales.
