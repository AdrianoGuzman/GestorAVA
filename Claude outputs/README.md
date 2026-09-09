# Gestor de Proyectos - AVA Montajes

> **Nota de confidencialidad:** Este proyecto se desarrolla en base a información y procesos reales de **AVA Montajes**, entregados en el contexto de la asignatura TIS. La información de la empresa (procesos internos, datos de gestión, estructura organizacional) debe tratarse como confidencial y no debe compartirse fuera del equipo de desarrollo y la asignatura.

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

## Stack técnico

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** React 19 + TypeScript, integrado vía Inertia.js
- **Estilos:** Tailwind CSS
- **Base de datos:** PostgreSQL
- **Build tool:** Vite

## Estructura del proyecto

El proyecto sigue la estructura estándar de Laravel:

```
GestorAVA/
├── app/            # Modelos, controladores y lógica de negocio (PHP)
├── bootstrap/      # Bootstrap del framework
├── config/         # Archivos de configuración de Laravel
├── database/       # Migraciones, seeders y factories
├── docs/           # Documentación del proyecto
├── public/         # Punto de entrada web y assets compilados
├── resources/
│   ├── js/         # Código React/TypeScript
│   ├── css/        # Estilos
│   └── views/      # Vistas Blade (mínimas, se usa Inertia)
├── routes/         # Definición de rutas (web.php, api.php, etc.)
├── storage/        # Logs, cache y archivos subidos
└── tests/          # Tests automatizados (PHP)
```

## Requisitos previos

- PHP >= 8.3
- Composer
- Node.js >= 18 y npm
- PostgreSQL

## Instalación

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/AdrianoGuzman/GestorAVA.git
   cd GestorAVA
   ```

2. Instalar dependencias de PHP:
   ```bash
   composer install
   ```

3. Instalar dependencias de Node:
   ```bash
   npm install
   ```

4. Copiar el archivo de entorno y configurarlo con tus credenciales:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Ajustar en `.env` los datos de conexión a PostgreSQL (`PG_HOST`, `PG_PORT`, `PG_DATABASE`, `PG_USERNAME`, `PG_PASSWORD`).

5. Ejecutar las migraciones:
   ```bash
   php artisan migrate
   ```

6. Levantar el entorno de desarrollo:
   ```bash
   php artisan serve
   npm run dev
   ```

## Notas de seguridad

- El archivo `.env` **nunca** debe subirse al repositorio (ya está en `.gitignore`). Contiene credenciales y llaves sensibles.
- Si en algún momento se sube por error una credencial o token, se debe revocar/rotar de inmediato, independientemente de si se limpia o no del historial de git.
