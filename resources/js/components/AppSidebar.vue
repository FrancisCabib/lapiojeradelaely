<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FolderTree, LayoutGrid, Layers, PanelsTopLeft, ShoppingBag, Tag } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Panel',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Servicios',
        href: '/admin/services',
        icon: Layers,
    },
    {
        title: 'Etiquetas',
        href: '/admin/tags',
        icon: Tag,
    },
    {
        title: 'Categorías',
        href: '/admin/categories',
        icon: FolderTree,
    },
    {
        title: 'Secciones',
        href: '/admin/sections',
        icon: PanelsTopLeft,
    },
    {
        title: 'Pedidos',
        href: '/admin/orders',
        icon: ShoppingBag,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
