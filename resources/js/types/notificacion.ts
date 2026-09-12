export type TipoNotificacion =
    | 'delegacion'
    | 'retroceso'
    | 'problema_reportado'
    | 'no_participacion_reportada'
    | 'cancelacion'
    | 'dependencia_creada'
    | 'atraso'
    | 'proximo_vencimiento';

export interface Notificacion {
    id: number;
    tarea_id: number;
    tipo: TipoNotificacion;
    mensaje: string;
    leida: boolean;
    created_at: string;
    tarea: { id: number; titulo: string } | null;
}
