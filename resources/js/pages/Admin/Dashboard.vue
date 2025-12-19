<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Leaf, Camera, MessageSquare } from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        totalUsers: number;
        totalPlants: number;
        totalSightings: number;
        totalForumPosts: number;
    };
    recentSightings: any[];
    recentUsers: any[];
}>();

const breadcrumbs = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link :href="route('admin.users.index')" class="block transition-transform hover:scale-[1.02]">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
                        </CardContent>
                    </Card>
                </Link>
                <Link :href="route('admin.plants.index')" class="block transition-transform hover:scale-[1.02]">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Plants</CardTitle>
                            <Leaf class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.totalPlants }}</div>
                        </CardContent>
                    </Card>
                </Link>
                <Link :href="route('admin.sightings.index')" class="block transition-transform hover:scale-[1.02]">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Sightings</CardTitle>
                            <Camera class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.totalSightings }}</div>
                        </CardContent>
                    </Card>
                </Link>
                <Link :href="route('admin.forum.index')" class="block transition-transform hover:scale-[1.02]">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Forum Posts</CardTitle>
                            <MessageSquare class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.totalForumPosts }}</div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Recent Sightings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <div v-for="sighting in recentSightings" :key="sighting.id" class="flex items-center">
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none">
                                        {{ sighting.plant?.common_name || 'Unknown Plant' }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        by {{ sighting.user?.name }}
                                    </p>
                                </div>
                                <div class="ml-auto font-medium">
                                    {{ new Date(sighting.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                            <div v-if="recentSightings.length === 0" class="text-center text-muted-foreground">
                                No recent sightings.
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Recent Users</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center">
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ user.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                </div>
                                <div class="ml-auto font-medium">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
