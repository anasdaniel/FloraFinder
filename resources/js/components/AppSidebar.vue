<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Eye, Folder, LeafIcon, Map, SearchIcon, UsersRoundIcon, ShieldCheck, Users, MessageSquare, MapPin } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = page.props.auth.user;

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/welcome-plant',
        icon: LeafIcon,
    },
];

if (user && user.is_admin) {
    mainNavItems.push(
        {
            title: 'Admin Dashboard',
            href: '/admin/dashboard',
            icon: ShieldCheck,
        },
        {
            title: 'User Management',
            href: '/admin/users',
            icon: Users,
        },
        {
            title: 'Plant Management',
            href: '/admin/plants',
            icon: LeafIcon,
        },
        {
            title: 'Forum Moderation',
            href: '/admin/forum',
            icon: MessageSquare,
        },
        {
            title: 'Sighting Management',
            href: '/admin/sightings',
            icon: MapPin,
        }
    );
}

mainNavItems.push(
    {
        title: 'Identify Plant',
        href: '/plant-identifier',
        icon: SearchIcon,
    },
    {
        title: 'My Sightings',
        href: '/sightings',
        icon: Eye,
    },
    {
        title: 'Plant Library',
        href: '/plants',
        icon: BookOpen,
    },
    {
        title: 'Sightings Map',
        href: '/sightings-map',
        icon: Map,
    },
    {
        title: 'Forum',
        href: '/forum',
        icon: UsersRoundIcon,
    },
);

const footerNavItems: NavItem[] = [

];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
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
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
