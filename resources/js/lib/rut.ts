import { clean, format } from 'rut.js';

/** Un RUT chileno tiene como maximo 8 digitos de cuerpo + 1 digito verificador. */
const LARGO_MAXIMO_RUT = 9;

/**
 * Formatea un RUT chileno a medida que se escribe: solo digitos/K, con un
 * unico guion antes del digito verificador (ej. "12345678-9"), sin puntos,
 * y truncado a los 9 caracteres de un RUT chileno real.
 */
export function formatearRut(valor: string): string {
    const limpio = clean(valor).slice(0, LARGO_MAXIMO_RUT);
    return limpio.length > 1 ? format(limpio, { dots: false }) : limpio;
}
