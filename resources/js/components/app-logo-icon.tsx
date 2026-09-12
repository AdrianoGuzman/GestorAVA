import { SVGAttributes } from 'react';

/** Isotipo "AVA" (Logotipo-isotipo-04, public/Logotipo-isotipo-04.svgz) -- fill hereda el color del contenedor via currentColor, para que se adapte igual que el resto del sidebar entre modo claro/oscuro. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="185 400 710 275" xmlns="http://www.w3.org/2000/svg">
            <polygon fill="currentColor" points="236.13,656.15 361.21,423.85 486.31,656.15 432.69,656.15 361.21,523.4 289.73,656.15" />
            <polygon fill="currentColor" points="664.93,423.85 539.85,656.15 414.76,423.85 468.37,423.85 539.85,556.6 611.33,423.85" />
            <polygon fill="currentColor" points="593.69,656.15 718.77,423.85 843.87,656.15 790.25,656.15 718.77,523.4 647.3,656.15" />
        </svg>
    );
}
