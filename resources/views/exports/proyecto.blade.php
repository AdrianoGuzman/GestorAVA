{{--
    Tabla HTML pura a proposito: Maatwebsite (FromView) la pasa por el lector
    HTML de PhpSpreadsheet, que la convierte en celdas reales de Excel. Ese
    lector solo interpreta estilos puestos directo en el atributo style de
    cada celda -- nada de <style> global, clases, ni CSS de layout (flex/grid)
    como en pdf/proyecto.blade.php. 5 columnas (A-E): la tabla de Tareas es la
    que mas necesita (código/título/sección/estado/responsable).
--}}
<table>
    <tr>
        <td colspan="5" style="background-color:#2D3238;color:#FFFFFF;font-weight:bold;font-size:14px;">
            {{ $proyecto->nombre }} ({{ $proyecto->estado->label() }})
        </td>
    </tr>
    <tr><td colspan="5"></td></tr>

    <tr>
        <td colspan="5" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">DATOS GENERALES</td>
    </tr>
    @foreach ($datosGenerales as $campo => $valor)
        <tr>
            <td style="font-weight:bold;">{{ $campo }}</td>
            <td colspan="4">{{ $valor }}</td>
        </tr>
    @endforeach
    <tr><td colspan="5"></td></tr>

    <tr>
        <td colspan="5" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">SECCIONES</td>
    </tr>
    @if (count($secciones) === 0)
        <tr><td colspan="5">Sin secciones.</td></tr>
    @else
        <tr>
            <td style="background-color:#ECF3E5;font-weight:bold;">NOMBRE</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">PESO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">AVANCE</td>
            <td style="background-color:#ECF3E5;font-weight:bold;" colspan="2">TAREAS</td>
        </tr>
        @foreach ($secciones as $seccion)
            <tr>
                <td>{{ $seccion['nombre'] }}</td>
                <td>{{ $seccion['peso'] }}</td>
                <td>{{ $seccion['avance'] }}</td>
                <td colspan="2">{{ $seccion['tareas'] }}</td>
            </tr>
        @endforeach
    @endif
    <tr><td colspan="5"></td></tr>

    <tr>
        <td colspan="5" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">TAREAS</td>
    </tr>
    @if (count($tareas) === 0)
        <tr><td colspan="5">Sin tareas asociadas.</td></tr>
    @else
        <tr>
            <td style="background-color:#ECF3E5;font-weight:bold;">CÓDIGO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">TÍTULO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">SECCIÓN</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">ESTADO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">RESPONSABLE</td>
        </tr>
        @foreach ($tareas as $tarea)
            <tr>
                <td>{{ $tarea['código'] }}</td>
                <td>{{ $tarea['título'] }}</td>
                <td>{{ $tarea['sección'] }}</td>
                <td>{{ $tarea['estado'] }}</td>
                <td>{{ $tarea['responsable'] }}</td>
            </tr>
        @endforeach
    @endif
    <tr><td colspan="5"></td></tr>

    <tr>
        <td colspan="5" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">HISTORIAL</td>
    </tr>
    @if (count($historial) === 0)
        <tr><td colspan="5">Sin eventos registrados.</td></tr>
    @else
        <tr>
            <td style="background-color:#ECF3E5;font-weight:bold;">FECHA</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">USUARIO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">EVENTO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;" colspan="2">DETALLE</td>
        </tr>
        @foreach ($historial as $evento)
            <tr>
                <td>{{ $evento['fecha'] }}</td>
                <td>{{ $evento['usuario'] }}</td>
                <td>{{ $evento['evento'] }}</td>
                <td colspan="2" style="vertical-align:top;">
                    @foreach ($evento['detalle'] as $linea)
                        {{ $linea }}@if (! $loop->last)
                            <br>
                        @endif
                    @endforeach
                </td>
            </tr>
        @endforeach
    @endif
</table>
