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
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, ArrowLeft, User, Calendar } from 'lucide-vue-next';

const props = defineProps<{
    thread: {
        id: number;
        title: string;
        content: string;
        user: { name: string };
        created_at: string;
        tags: Array<{ name: string }>;
        all_posts: Array<{
            id: number;
            content: string;
            user: { name: string };
            created_at: string;
        }>;
    };
}>();

const deleteThread = () => {
    if (confirm('Are you sure you want to delete this entire thread?')) {
        router.delete(route('admin.forum.threads.destroy', props.thread.id));
    }
};

const deletePost = (id: number) => {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(route('admin.forum.posts.destroy', id));
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head :title="`Thread: ${thread.title}`" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.forum.index')">
                    <Button variant="outline" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ thread.title }}</h1>
                    <div class="flex items-center gap-4 text-sm text-muted-foreground mt-1">
                        <span class="flex items-center gap-1"><User class="h-3 w-3" /> {{ thread.user.name }}</span>
                        <span class="flex items-center gap-1"><Calendar class="h-3 w-3" /> {{ formatDate(thread.created_at) }}</span>
                    </div>
                </div>
                <div class="ml-auto">
                    <Button variant="destructive" @click="deleteThread">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Delete Thread
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <Badge v-for="tag in thread.tags" :key="tag.name" variant="secondary">
                            {{ tag.name }}
                        </Badge>
                    </div>
                    <CardTitle>Original Post</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {{ thread.content }}
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <h2 class="text-xl font-semibold">Replies ({{ thread.all_posts.length }})</h2>

                <Card v-for="post in thread.all_posts" :key="post.id" class="relative">
                    <CardContent class="pt-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <User class="h-4 w-4 text-muted-foreground" />
                                {{ post.user.name }}
                                <span class="text-muted-foreground font-normal ml-2">{{ formatDate(post.created_at) }}</span>
                            </div>
                            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive hover:bg-destructive/10" @click="deletePost(post.id)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        <div class="text-sm">
                            {{ post.content }}
                        </div>
                    </CardContent>
                </Card>

                <div v-if="thread.all_posts.length === 0" class="text-center py-12 text-muted-foreground border rounded-lg border-dashed">
                    No replies yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
