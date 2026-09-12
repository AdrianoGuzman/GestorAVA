import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ListTodo, Settings } from 'lucide-react';
import AppLogo from './app-logo';

/**
 * RF-03 D2.1: el conjunto de opciones de menu depende del nivel jerarquico
 * de quien inicio sesion (calculado en el backend, ver HandleInertiaRequests).
 */
export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Mis tareas',
            url: route('mis-tareas.index'),
            icon: ListTodo,
        },
        ...(auth.puedeAdministrarEstructura
            ? [
                  {
                      title: 'Administración',
                      url: route('usuarios.index'),
                      icon: Settings,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            {/* Trama corporativa (public/Trama_Corporativa.ai) como textura decorativa de
                fondo, cubriendo el sidebar completo -- via mask-image en vez de <img>, para
                que tome el color del sidebar (bg-sidebar-foreground) y se adapte solo entre
                modo claro/oscuro. El resto del contenido va con z-10 para quedar por encima. */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 z-0 bg-sidebar-foreground/[0.06] [-webkit-mask-image:url(/trama-corporativa.png)] [-webkit-mask-position:center] [-webkit-mask-repeat:no-repeat] [-webkit-mask-size:cover] [mask-image:url(/trama-corporativa.png)] [mask-position:center] [mask-repeat:no-repeat] [mask-size:cover]"
            />

            <SidebarHeader className="relative z-10">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('mis-tareas.index')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="relative z-10 bg-transparent">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="relative z-10">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
