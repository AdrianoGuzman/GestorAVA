{{--
    Tabla HTML pura a proposito: Maatwebsite (FromView) la pasa por el lector
    HTML de PhpSpreadsheet, que la convierte en celdas reales de Excel. Ese
    lector solo interpreta estilos puestos directo en el atributo style de
    cada celda -- nada de <style> global, clases, ni CSS de layout (flex/grid)
    como en pdf/tarea.blade.php.
--}}
<table>
    <tr>
        <td colspan="4" style="background-color:#2D3238;color:#FFFFFF;font-weight:bold;font-size:14px;">
            {{ $tarea->titulo }} ({{ $tarea->codigo }})
        </td>
    </tr>
    <tr><td colspan="4"></td></tr>

    <tr>
        <td colspan="4" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">DATOS GENERALES</td>
    </tr>
    @foreach ($datosGenerales as $campo => $valor)
        <tr>
            <td style="font-weight:bold;">{{ $campo }}</td>
            <td colspan="3">{{ $valor }}</td>
        </tr>
    @endforeach
    <tr><td colspan="4"></td></tr>

    <tr>
        <td colspan="4" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">SUBTAREAS</td>
    </tr>
    @if (count($subtareas) === 0)
        <tr><td colspan="4">Sin subtareas.</td></tr>
    @else
        <tr>
            <td style="background-color:#ECF3E5;font-weight:bold;">TEXTO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">DUEÑO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">FECHA LÍMITE</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">COMPLETADA</td>
        </tr>
        @foreach ($subtareas as $item)
            <tr>
                <td>{{ $item['texto'] }}</td>
                <td>{{ $item['dueño'] }}</td>
                <td>{{ $item['fecha límite'] }}</td>
                <td>{{ $item['completada'] }}</td>
            </tr>
        @endforeach
    @endif
    <tr><td colspan="4"></td></tr>

    <tr>
        <td colspan="4" style="background-color:#A0F700;color:#2D3238;font-weight:bold;">HISTORIAL</td>
    </tr>
    @if (count($historial) === 0)
        <tr><td colspan="4">Sin eventos registrados.</td></tr>
    @else
        <tr>
            <td style="background-color:#ECF3E5;font-weight:bold;">FECHA</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">USUARIO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">EVENTO</td>
            <td style="background-color:#ECF3E5;font-weight:bold;">MOTIVO</td>
        </tr>
        @foreach ($historial as $evento)
            <tr>
                <td>{{ $evento['fecha'] }}</td>
                <td>{{ $evento['usuario'] }}</td>
                <td>{{ $evento['evento'] }}</td>
                <td>{{ $evento['motivo'] }}</td>
            </tr>
        @endforeach
    @endif
</table>
