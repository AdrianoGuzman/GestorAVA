import { LucideIcon } from 'lucide-react';
import type { NivelJerarquico } from './usuario';

export interface Auth {
    user: User;
    /** RF-03 D2.1: distintos niveles jerarquicos ven distintas opciones de menu. */
    puedeAdministrarEstructura: boolean;
    /** RF-15/RF-17: contador de la campana de notificaciones. */
    notificacionesNoLeidas: number;
    /** Baja de usuarios: solo Directorio, ver NivelJerarquico::puedeEliminarUsuarios(). */
    puedeEliminarUsuarios: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    /** PermisoDenegadoException::render() y Controller::exito() flashean esto -- ver flash-toaster.tsx. */
    flash: { success: string | null; error: string | null };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    nombre_1: string;
    nombre_2: string;
    apellido_1: string;
    apellido_2: string;
    email: string;
    cargo: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    nivel_jerarquico: NivelJerarquico | null;
    unidad_organizacional_id: number | null;
    [key: string]: unknown; // This allows for additional properties...
}
