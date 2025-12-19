<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Bell } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const notifications = ref([]);
const unreadCount = ref(0);

const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('notifications.recent'));
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

const markAsRead = async (id: string) => {
    try {
        await axios.post(route('notifications.read', id));
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.read-all'));
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
};

onMounted(() => {
    fetchNotifications();
    // Refresh every minute
    setInterval(fetchNotifications, 60000);
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <Badge v-if="unreadCount > 0" class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-[10px]" variant="destructive">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button v-if="unreadCount > 0" variant="ghost" size="sm" @click="markAllAsRead" class="text-xs h-auto p-1">
                    Mark all as read
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div v-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                No notifications yet.
            </div>
            <div v-else class="max-h-[300px] overflow-y-auto">
                <DropdownMenuItem v-for="notification in notifications" :key="notification.id" class="flex flex-col items-start p-3 focus:bg-accent" @click="markAsRead(notification.id)">
                    <Link :href="route('forum.show', notification.data.thread_id)" class="w-full">
                        <div class="flex items-center gap-2 mb-1">
                            <Badge :variant="notification.read_at ? 'outline' : 'default'" class="text-[10px] px-1 py-0 h-4">
                                {{ notification.data.type }}
                            </Badge>
                            <span class="text-[10px] text-muted-foreground ml-auto">
                                {{ new Date(notification.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                        <p class="text-sm font-medium leading-none" :class="{ 'font-bold': !notification.read_at }">
                            {{ notification.data.message }}
                        </p>
                    </Link>
                </DropdownMenuItem>
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child class="justify-center">
                <Link :href="route('notifications.index')" class="text-xs text-primary font-medium w-full text-center">
                    View all notifications
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
