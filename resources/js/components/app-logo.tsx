import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
                {/* bg-sidebar-primary es blanco siempre (ver nota en app.css) -- el
                    icono va siempre oscuro para contrastar, sin depender del tema. */}
                <AppLogoIcon className="size-5 fill-current text-gris-2" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">AVA Montajes</span>
            </div>
        </>
    );
}
