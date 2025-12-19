<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MessageSquare, Trash2, Eye, User } from 'lucide-vue-next';

defineProps<{
    threads: {
        data: Array<{
            id: number;
            title: string;
            user: { name: string };
            all_posts_count: number;
            created_at: string;
            tags: Array<{ name: string }>;
        }>;
        links: any[];
    };
}>();

const deleteThread = (id: number) => {
    if (confirm('Are you sure you want to delete this thread and all its posts?')) {
        router.delete(route('admin.forum.threads.destroy', id));
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Forum Moderation" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Forum Moderation</h1>
                    <p class="text-muted-foreground">Manage community discussions and posts.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Threads</CardTitle>
                    <CardDescription>A list of all forum threads.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Author</TableHead>
                                <TableHead>Posts</TableHead>
                                <TableHead>Tags</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="thread in threads.data" :key="thread.id">
                                <TableCell class="font-medium">{{ thread.title }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-muted-foreground" />
                                        {{ thread.user.name }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                                        {{ thread.all_posts_count }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="tag in thread.tags" :key="tag.name" variant="secondary" class="text-[10px]">
                                            {{ tag.name }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>{{ formatDate(thread.created_at) }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('admin.forum.threads.show', thread.id)">
                                            <Button variant="outline" size="icon">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="destructive" size="icon" @click="deleteThread(thread.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="mt-4 flex justify-center gap-2">
                        <Link
                            v-for="link in threads.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1 border rounded text-sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active, 'text-muted-foreground': !link.url }"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
