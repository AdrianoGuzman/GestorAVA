import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, ListTodo } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Mis tareas',
        url: '/mis-tareas',
        icon: ListTodo,
    },
];

export function AppSidebar() {
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
                            <Link href="/dashboard" prefetch>
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
