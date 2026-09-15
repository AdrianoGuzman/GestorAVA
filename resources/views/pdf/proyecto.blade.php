<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $proyecto->nombre }}</title>
    <style>
        @page { margin: 30px 34px; }
        /* DejaVu Sans (viene con dompdf) en vez de Helvetica: Helvetica no trae el
           glifo de "→" que usan las líneas de detalle del historial y dompdf lo
           reemplaza por "?" en silencio, sin avisar del glifo faltante. */
        body { font-family: 'DejaVu Sans', sans-serif; color: #2D3238; font-size: 11px; }

        .header { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .header img { height: 26px; }
        .header .titulo-doc { text-align: right; font-size: 11px; color: #7A7F85; }

        h1 { font-size: 18px; margin: 0 0 2px; }
        .estado { color: #7A7F85; font-size: 11px; margin: 0 0 14px; }

        h2 {
            font-size: 12px;
            background-color: #A0F700;
            color: #2D3238;
            padding: 5px 9px;
            margin: 16px 0 0;
        }

        table.datos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.datos td { padding: 5px 9px; border-bottom: 1px solid #DBE0D5; vertical-align: top; }
        table.datos td.campo { width: 140px; font-weight: bold; }

        table.filas { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.filas th {
            background-color: #ECF3E5;
            color: #2D3238;
            text-align: left;
            padding: 6px 9px;
            border-bottom: 1px solid #C1F75E;
            font-size: 9px;
            letter-spacing: 0.4px;
        }
        table.filas td { padding: 6px 9px; border-bottom: 1px solid #DBE0D5; font-size: 10px; }

        .vacio { color: #7A7F85; font-style: italic; padding: 6px 9px 0; }

        .footer { margin-top: 22px; font-size: 9px; color: #7A7F85; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><img src="{{ public_path('images/logo-ava.png') }}" alt="AVA"></td>
            <td class="titulo-doc">Ficha de proyecto</td>
        </tr>
    </table>

    <h1>{{ $proyecto->nombre }}</h1>
    <p class="estado">{{ $proyecto->estado->label() }}</p>

    <h2>Datos generales</h2>
    <table class="datos">
        @foreach ($datosGenerales as $campo => $valor)
            <tr>
                <td class="campo">{{ $campo }}</td>
                <td>{{ $valor }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Secciones</h2>
    @if (count($secciones) === 0)
        <p class="vacio">Sin secciones.</p>
    @else
        <table class="filas">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Peso</th>
                    <th>Avance</th>
                    <th>Tareas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($secciones as $seccion)
                    <tr>
                        <td>{{ $seccion['nombre'] }}</td>
                        <td>{{ $seccion['peso'] }}</td>
                        <td>{{ $seccion['avance'] }}</td>
                        <td>{{ $seccion['tareas'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Tareas</h2>
    @if (count($tareas) === 0)
        <p class="vacio">Sin tareas asociadas.</p>
    @else
        <table class="filas">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Sección</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tareas as $tarea)
                    <tr>
                        <td>{{ $tarea['código'] }}</td>
                        <td>{{ $tarea['título'] }}</td>
                        <td>{{ $tarea['sección'] }}</td>
                        <td>{{ $tarea['estado'] }}</td>
                        <td>{{ $tarea['responsable'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Historial</h2>
    @if (count($historial) === 0)
        <p class="vacio">Sin eventos registrados.</p>
    @else
        <table class="filas">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Evento</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($historial as $evento)
                    <tr>
                        <td>{{ $evento['fecha'] }}</td>
                        <td>{{ $evento['usuario'] }}</td>
                        <td>{{ $evento['evento'] }}</td>
                        <td>
                            @foreach ($evento['detalle'] as $linea)
                                {{ $linea }}@if (! $loop->last)
                                    <br>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer">Exportado por {{ $usuarioExportador }} el {{ now()->format('d-m-Y H:i') }} — Gestor de Proyectos AVA</p>
</body>
</html>
