<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $tarea->codigo }}</title>
    <style>
        @page { margin: 30px 34px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #2D3238; font-size: 11px; }

        .header { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .header img { height: 26px; }
        .header .titulo-doc { text-align: right; font-size: 11px; color: #7A7F85; }

        h1 { font-size: 18px; margin: 0 0 2px; }
        .codigo { color: #7A7F85; font-size: 11px; margin: 0 0 14px; }

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
            <td class="titulo-doc">Ficha de tarea</td>
        </tr>
    </table>

    <h1>{{ $tarea->titulo }}</h1>
    <p class="codigo">{{ $tarea->codigo }}</p>

    <h2>Datos generales</h2>
    <table class="datos">
        @foreach ($datosGenerales as $campo => $valor)
            <tr>
                <td class="campo">{{ $campo }}</td>
                <td>{{ $valor }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Subtareas</h2>
    @if (count($subtareas) === 0)
        <p class="vacio">Sin subtareas.</p>
    @else
        <table class="filas">
            <thead>
                <tr>
                    <th>Texto</th>
                    <th>Dueño</th>
                    <th>Fecha límite</th>
                    <th>Completada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subtareas as $item)
                    <tr>
                        <td>{{ $item['texto'] }}</td>
                        <td>{{ $item['dueño'] }}</td>
                        <td>{{ $item['fecha límite'] }}</td>
                        <td>{{ $item['completada'] }}</td>
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
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($historial as $evento)
                    <tr>
                        <td>{{ $evento['fecha'] }}</td>
                        <td>{{ $evento['usuario'] }}</td>
                        <td>{{ $evento['evento'] }}</td>
                        <td>{{ $evento['motivo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer">Exportado por {{ $usuarioExportador }} el {{ now()->format('d-m-Y H:i') }} — Gestor de Proyectos AVA</p>
</body>
</html>
