<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Eye, Folder, LeafIcon, Map, SearchIcon, UsersRoundIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    // {
    //     title: 'Dashboard',
    //     href: '/dashboard',
    //     icon: LayoutGrid,
    // },

    {
        title: 'Dashboard',
        href: '/welcome-plant',
        icon: LeafIcon,
    },
    {
        title: 'Identify Plant',
        href: '/plant-identifier',
        icon: SearchIcon,
    },
    // {
    //     title: 'My Plants',
    //     href: '/my-plants',
    //     icon: Folder,
    // },
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
        href: '/plant-map',
        icon: Map,
    },
    {
        title: 'Forum',
        href: '/forum',
        icon: UsersRoundIcon,
    },
];

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
