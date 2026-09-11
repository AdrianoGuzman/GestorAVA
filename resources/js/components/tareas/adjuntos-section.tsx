import { cn } from '@/lib/utils';
import type { AdjuntoTarea, CategoriaAdjunto } from '@/types/tarea';
import { router } from '@inertiajs/react';
import { Download, File as FileIcon, FileImage, FileSpreadsheet, FileText, Paperclip } from 'lucide-react';
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

function ListaAdjuntos({ tareaId, adjuntos, vacio }: { tareaId: number; adjuntos: AdjuntoTarea[]; vacio: string }) {
    if (adjuntos.length === 0) {
        return <p className="text-center text-sm text-muted-foreground">{vacio}</p>;
    }

    return (
        <ul className="space-y-2">
            {adjuntos.map((adjunto) => {
                const Icono = iconoParaMime(adjunto.mime_type);
                return (
                    <li key={adjunto.id} className="flex items-center gap-3 rounded-md border border-border p-2.5">
                        <Icono className="size-5 shrink-0 text-gris-1" />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-foreground">{adjunto.nombre_original}</p>
                            <p className="text-xs text-muted-foreground">
                                {formatearTamano(adjunto.tamano_bytes)} · {adjunto.usuario?.name ?? 'Sistema'} ·{' '}
                                {new Date(adjunto.created_at).toLocaleDateString('es-CL')}
                            </p>
                        </div>
                        <a
                            href={route('tareas.adjuntos.descargar', [tareaId, adjunto.id])}
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
 */
export function AdjuntosSection({
    tareaId,
    adjuntos,
    puedeAdjuntar,
}: {
    tareaId: number;
    adjuntos: AdjuntoTarea[];
    puedeAdjuntar: boolean;
}) {
    const [categoria, setCategoria] = useState<CategoriaAdjunto>('evidencia');
    const [subiendo, setSubiendo] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const onDrop = (archivosAceptados: File[]) => {
        const archivo = archivosAceptados[0];
        if (!archivo) return;

        setError(null);
        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('categoria', categoria);

        router.post(route('tareas.adjuntos.store', tareaId), formData, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setSubiendo(true),
            onFinish: () => setSubiendo(false),
            onError: (errores) => setError(errores.archivo ?? errores.categoria ?? 'No se pudo subir el archivo.'),
        });
    };

    const { getRootProps, getInputProps, isDragActive } = useDropzone({ onDrop, multiple: false, disabled: subiendo });

    const necesarios = adjuntos.filter((a) => a.categoria === 'necesario');
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
                            Necesario para la tarea
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
                            {subiendo ? 'Subiendo...' : isDragActive ? 'Soltá el archivo acá...' : 'Arrastrá un archivo o hacé click para elegirlo'}
                        </p>
                        <p className="text-xs text-muted-foreground">PDF, imágenes, Word o Excel — máx. 10MB</p>
                    </div>
                    {error && <p className="mt-1 text-center text-sm text-rojo-1">{error}</p>}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <h4 className="mb-2 text-center text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        Necesarios para la tarea
                    </h4>
                    <ListaAdjuntos tareaId={tareaId} adjuntos={necesarios} vacio="Sin archivos de referencia todavía." />
                </div>
                <div>
                    <h4 className="mb-2 text-center text-xs font-semibold tracking-wide text-muted-foreground uppercase">Evidencia</h4>
                    <ListaAdjuntos tareaId={tareaId} adjuntos={evidencia} vacio="Todavía no hay evidencia subida." />
                </div>
            </div>
        </div>
    );
}
