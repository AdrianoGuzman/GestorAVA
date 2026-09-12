export type NivelJerarquico = 'directorio' | 'gerencia' | 'jefe_area' | 'asistente';

export type TipoUnidad = 'directorio' | 'gerencia' | 'obra' | 'area';

/** RN-01: nombres neutros de nivel jerarquico, no el cargo literal de la persona. */
export const NIVEL_JERARQUICO_LABELS: Record<NivelJerarquico, string> = {
    directorio: 'Directorio',
    gerencia: 'Gerencia',
    jefe_area: 'Jefe de área/obra',
    asistente: 'Asistente',
};

/**
 * Cargos de ejemplo acordes al rubro de AVA Montajes (obras/instalaciones
 * electricas). Lista cerrada para RNF-08 -- si se necesita un cargo fuera
 * de esta lista, agregarlo aca en vez de dejar el campo libre.
 */
export const CARGOS_AVA = [
    'Gerente de Operaciones',
    'Jefe de Obra',
    'Supervisor de Obra',
    'Ingeniero de Proyectos',
    'Prevencionista de Riesgos',
    'Administrativo de Obra',
    'Bodeguero',
    'Maestro Electricista',
    'Electricista',
    'Soldador',
    'Ayudante de Montaje',
] as const;

export type CargoAva = (typeof CARGOS_AVA)[number];

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
