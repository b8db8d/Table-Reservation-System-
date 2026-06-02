<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import NavUser from '@/components/NavUser.vue';
import { Toaster } from '@/components/ui/sonner';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { Link } from '@inertiajs/vue3';
import { CalendarClock, ClipboardList, Clock, LayoutGrid, Table2, UserCog, Users } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes/admin';
import { index as reservationsIndex, pending } from '@/routes/admin/reservations';
import { index as tablesIndex } from '@/routes/admin/tables';
import { index as groupsIndex } from '@/routes/admin/tables/groups';
import { index as operatingHoursIndex } from '@/routes/admin/settings/operating-hours';
import { index as staffIndex } from '@/routes/admin/staff';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { isCurrentUrl } = useCurrentUrl();
const page = usePage<{ auth: { can: Record<string, boolean> } }>();

const canManageTables = page.props.auth?.can?.['tables.manage'] ?? false;
const canManageHours = page.props.auth?.can?.['operating-hours.manage'] ?? false;
const canManageStaff = page.props.auth?.can?.['staff.manage'] ?? false;

const navItems = [
    { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
    { title: 'All Reservations', href: reservationsIndex.url(), icon: CalendarClock },
    { title: 'Pending Reservations', href: pending.url(), icon: ClipboardList },
    ...(canManageTables ? [
        { title: 'Tables', href: tablesIndex.url(), icon: Table2 },
        { title: 'Joining Groups', href: groupsIndex.url(), icon: Users },
    ] : []),
    ...(canManageHours ? [
        { title: 'Operating Hours', href: operatingHoursIndex.url(), icon: Clock },
    ] : []),
    ...(canManageStaff ? [
        { title: 'Staff Accounts', href: staffIndex.url(), icon: UserCog },
    ] : []),
];
</script>

<template>
    <AppShell variant="sidebar">
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="dashboard.url()">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <div class="px-2 py-0">
                    <p class="px-2 py-1.5 text-xs font-medium text-sidebar-foreground/60">Admin</p>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in navItems" :key="item.title">
                            <SidebarMenuButton
                                as-child
                                :is-active="isCurrentUrl(item.href)"
                                :tooltip="item.title"
                            >
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </div>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>

        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>

        <Toaster />
    </AppShell>
</template>
