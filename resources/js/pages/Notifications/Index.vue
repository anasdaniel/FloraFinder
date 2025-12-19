<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    notifications: any;
}>();

const breadcrumbs = [
    {
        title: 'Notifications',
        href: '/notifications',
    },
];

const markAsRead = async (id: string) => {
    try {
        await axios.post(route('notifications.read', id));
        router.reload({ only: ['notifications'] });
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.read-all'));
        router.reload({ only: ['notifications'] });
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
};
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Notifications</CardTitle>
                    <Button v-if="notifications.data.some(n => !n.read_at)" variant="outline" size="sm" @click="markAllAsRead">
                        Mark all as read
                    </Button>
                </CardHeader>
                <CardContent>
                    <div v-if="notifications.data.length === 0" class="py-12 text-center text-muted-foreground">
                        You have no notifications yet.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="notification in notifications.data" :key="notification.id" 
                             class="flex items-start justify-between p-4 border rounded-lg transition-colors"
                             :class="{ 'bg-muted/50': !notification.read_at }">
                            <div class="flex-1">
                                <Link :href="route('forum.show', notification.data.thread_id)" class="block">
                                    <div class="flex items-center gap-2 mb-1">
                                        <Badge :variant="notification.data.type === 'like' ? 'default' : (notification.data.type === 'comment' ? 'secondary' : 'outline')">
                                            {{ notification.data.type }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            {{ new Date(notification.created_at).toLocaleString() }}
                                        </span>
                                    </div>
                                    <p class="text-sm" :class="{ 'font-bold': !notification.read_at }">
                                        {{ notification.data.message }}
                                    </p>
                                </Link>
                            </div>
                            <div v-if="!notification.read_at" class="ml-4">
                                <Button variant="ghost" size="sm" @click="markAsRead(notification.id)">
                                    Mark as read
                                </Button>
                            </div>
                        </div>

                        <!-- Pagination (Simple) -->
                        <div v-if="notifications.links.length > 3" class="flex justify-center mt-6">
                            <div class="flex gap-2">
                                <Link v-for="link in notifications.links" :key="link.label" 
                                      :href="link.url || '#'" 
                                      v-html="link.label"
                                      class="px-3 py-1 border rounded text-sm"
                                      :class="{ 'bg-primary text-primary-foreground': link.active, 'text-muted-foreground': !link.url }"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
