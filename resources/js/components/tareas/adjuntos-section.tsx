import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { cn } from '@/lib/utils';
import type { AdjuntoDeTareaHija, AdjuntoTarea, CategoriaAdjunto } from '@/types/tarea';
import { Download, File as FileIcon, FileImage, FileSpreadsheet, FileText, Link2, Paperclip } from 'lucide-react';
import { useState } from 'react';
import { useDropzone } from 'react-dropzone';

function iconoParaMime(mime: string) {
    if (mime.includes('pdf')) return FileText;
    if (mime.startsWith('image/')) return FileImage;
    if (mime.includes('sheet') || mime.includes('excel')) return FileSpreadsheet;
    return FileIcon;
}

function formatearTamano(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

/** Cada adjunto trae su propio tarea_id (puede ser de una tarea hija), asi el link de descarga siempre apunta a la tarea dueña real. */
function ListaAdjuntos({ adjuntos, vacio }: { adjuntos: (AdjuntoTarea | AdjuntoDeTareaHija)[]; vacio: string }) {
    if (adjuntos.length === 0) {
        return <p className="text-center text-sm text-muted-foreground">{vacio}</p>;
    }

    return (
        <ul className="space-y-2">
            {adjuntos.map((adjunto) => {
                const Icono = iconoParaMime(adjunto.mime_type);
                const deTareaHija = 'tarea_hija_titulo' in adjunto;
                return (
                    <li key={`${adjunto.tarea_id}-${adjunto.id}`} className="flex items-center gap-3 rounded-md border border-border p-2.5">
                        <Icono className="size-5 shrink-0 text-gris-1" />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-foreground">{adjunto.nombre_original}</p>
                            <p className="text-xs text-muted-foreground">
                                {formatearTamano(adjunto.tamano_bytes)} · {adjunto.usuario?.name ?? 'Sistema'} ·{' '}
                                {new Date(adjunto.created_at).toLocaleDateString('es-CL')}
                            </p>
                            {deTareaHija && (
                                <p className="mt-0.5 flex items-center gap-1 text-xs text-verde-6">
                                    <Link2 className="size-3" /> De la tarea "{(adjunto as AdjuntoDeTareaHija).tarea_hija_titulo}"
                                </p>
                            )}
                        </div>
                        <a
                            href={route('tareas.adjuntos.descargar', [adjunto.tarea_id, adjunto.id])}
                            className="shrink-0 rounded-md p-1.5 text-gris-1 hover:bg-verde-1 hover:text-gris-2"
                            title="Descargar"
                        >
                            <Download className="size-4" />
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}

/**
 * RF-19: adjuntar evidencia. Solo el responsable o un colaborador puede
 * subir (D1). Quien sube elige explícitamente la categoría (Necesario para
 * la tarea / Evidencia) -- no se infiere de quién lo sube, porque la misma
 * persona puede necesitar subir ambos tipos.
 *
 * "Necesarios para la tarea" también incluye la evidencia subida en las
 * tareas hijas (RF-21/22): si pediste ayuda externa creando una tarea
 * dependiente, lo que esa persona suba aparece acá directo, sin tener que
 * reenviarlo por fuera del sistema.
 */
export function AdjuntosSection({
    tareaId,
    adjuntos,
    adjuntosDeTareasHijas,
    puedeAdjuntar,
}: {
    tareaId: number;
    adjuntos: AdjuntoTarea[];
    adjuntosDeTareasHijas: AdjuntoDeTareaHija[];
    puedeAdjuntar: boolean;
}) {
    const [categoria, setCategoria] = useState<CategoriaAdjunto>('evidencia');
    const [error, setError] = useState<string | null>(null);
    const { enviar, processing: subiendo } = useAccionTarea();

    const onDrop = (archivosAceptados: File[]) => {
        const archivo = archivosAceptados[0];
        if (!archivo) return;

        setError(null);
        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('categoria', categoria);

        enviar('post', route('tareas.adjuntos.store', tareaId), formData, {
            forceFormData: true,
            onError: (errores) => setError(errores.archivo ?? errores.categoria ?? 'No se pudo subir el archivo.'),
        });
    };

    const { getRootProps, getInputProps, isDragActive } = useDropzone({ onDrop, multiple: false, disabled: subiendo });

    const necesarios: (AdjuntoTarea | AdjuntoDeTareaHija)[] = [
        ...adjuntos.filter((a) => a.categoria === 'necesario'),
        ...adjuntosDeTareasHijas,
    ];
    const evidencia = adjuntos.filter((a) => a.categoria === 'evidencia');

    return (
        <div className="space-y-4">
            {puedeAdjuntar && (
                <div>
                    <div className="mb-2 flex items-center justify-center gap-1 rounded-md border border-border bg-muted/40 p-1 text-sm">
                        <button
                            type="button"
                            onClick={() => setCategoria('necesario')}
                            className={cn(
                                'flex-1 rounded-sm px-3 py-1.5 font-medium transition-colors',
                                categoria === 'necesario' ? 'bg-verde-5 text-gris-2' : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            Obligatorio
                        </button>
                        <button
                            type="button"
                            onClick={() => setCategoria('evidencia')}
                            className={cn(
                                'flex-1 rounded-sm px-3 py-1.5 font-medium transition-colors',
                                categoria === 'evidencia' ? 'bg-verde-5 text-gris-2' : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            Es mi evidencia
                        </button>
                    </div>

                    <div
                        {...getRootProps()}
                        className={cn(
                            'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-md border-2 border-dashed p-6 text-center transition-colors',
                            isDragActive ? 'border-verde-5 bg-verde-1' : 'border-gris-3 hover:border-verde-4 hover:bg-verde-1/50',
                            subiendo && 'pointer-events-none opacity-60',
                        )}
                    >
                        <input {...getInputProps()} />
                        <Paperclip className="size-6 text-gris-1" />
                        <p className="text-sm text-foreground">
                            {subiendo ? 'Subiendo...' : isDragActive ? 'Suelta el archivo acá...' : 'Arrastra un archivo o haz click para elegirlo'}
                        </p>
                        <p className="text-xs text-muted-foreground">PDF, imágenes, Word, Excel o ZIP — máx. 10MB</p>
                    </div>
                    {error && <p className="mt-1 text-center text-sm text-rojo-1">{error}</p>}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <h4 className="mb-2 text-center text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        Obligatorios
                    </h4>
                    <ListaAdjuntos adjuntos={necesarios} vacio="Sin archivos de referencia todavía." />
                </div>
                <div>
                    <h4 className="mb-2 text-center text-xs font-semibold tracking-wide text-muted-foreground uppercase">Evidencia</h4>
                    <ListaAdjuntos adjuntos={evidencia} vacio="Todavía no hay evidencia subida." />
                </div>
            </div>
        </div>
    );
}
