export type NivelJerarquico = 'directorio' | 'gerencia' | 'jefe_area' | 'asistente';

export type TipoUnidad = 'directorio' | 'gerencia' | 'obra' | 'area';

/** RN-01: nombres neutros de nivel jerarquico, no el cargo literal de la persona. */
export const NIVEL_JERARQUICO_LABELS: Record<NivelJerarquico, string> = {
    directorio: 'Directorio',
    gerencia: 'Gerencia',
    jefe_area: 'Jefe de área/obra',
    asistente: 'Asistente',
};

export interface UnidadOrganizacional {
    id: number;
    nombre: string;
    tipo: TipoUnidad;
}

export interface UsuarioAdmin {
    id: number;
    name: string;
    email: string;
    cargo: string;
    nivel_jerarquico: NivelJerarquico | null;
    unidad_organizacional_id: number | null;
    unidad_organizacional: UnidadOrganizacional | null;
}
