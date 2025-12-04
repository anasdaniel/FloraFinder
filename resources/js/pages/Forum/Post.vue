<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

// Props from Inertia page
const page = usePage();
const thread = ref(page.props.thread);

// UI state
const openCommentBox = ref(false);
const openReplyBox = ref<number | null>(null);
const commentContent = ref('');
const replyContent = ref('');

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Forum', href: '/forum' },
    { title: thread.value.title, href: `/forum/${thread.value.id}` },
];

// Toggle comment input
function toggleCommentBox() {
    openCommentBox.value = !openCommentBox.value;
}

// Submit comment
function submitComment() {
    if (!commentContent.value.trim()) return;
    router.post(route('forum.comment', thread.value.id), { content: commentContent.value }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload();
            commentContent.value = '';
            openCommentBox.value = false;
        }
    });
}

// Toggle reply input
function toggleReplyBox(postId: number) {
    openReplyBox.value = openReplyBox.value === postId ? null : postId;
}

// Submit reply
function submitReply(postId: number) {
    if (!replyContent.value.trim()) return;
    router.post(route('forum.reply', { thread: thread.value.id, post: postId }), { content: replyContent.value }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload();
            replyContent.value = '';
            openReplyBox.value = null;
        }
    });
}
</script>

<template>
    <Head :title="thread.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-2xl px-4 py-8 mx-auto">
            <Button as-child variant="outline" size="sm" class="gap-2 mb-4">
                <Link href="/forum">
                    <Icon name="arrow-left" class="w-4 h-4" /> Back to Forum
                </Link>
            </Button>

            <!-- Main thread -->
            <Card>
                <CardHeader class="flex items-center gap-4 p-6 pb-2">
                    <Avatar size="sm" shape="circle">
                        <AvatarFallback>{{ thread.user.name.split(' ').map(n => n[0]).join('') }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <CardTitle class="text-xl font-semibold">{{ thread.title }}</CardTitle>
                        <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                            <span>By {{ thread.user.name }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ new Date(thread.created_at).toLocaleDateString() }}</span>
                            <span class="mx-1">•</span>
                            <span class="capitalize">{{ thread.category }}</span>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pt-2 pb-4 text-base">
                    <div class="whitespace-pre-line">{{ thread.posts[0]?.content }}</div>
                    <div v-if="thread.posts[0]?.image" class="mt-2">
                        <img :src="`/storage/${thread.posts[0].image}`" class="rounded-lg max-h-48 object-cover" />
                    </div>
                </CardContent>
            </Card>

            <!-- Add comment -->
            <div class="mt-4">
                <Button @click="toggleCommentBox" class="text-sm text-blue-600 hover:underline">
                    💬 Add Comment
                </Button>
                <div v-if="openCommentBox" class="mt-2 flex gap-2">
                    <input v-model="commentContent" class="flex-1 border rounded px-3 py-1 text-sm" placeholder="Write a comment..." />
                    <button @click="submitComment" class="text-green-600">📤</button>
                </div>
            </div>

            <!-- Comments & Replies -->
            <div class="mt-6 space-y-4">
                <div v-for="post in thread.posts" :key="post.id" class="border-l pl-4">
                    <!-- Only top-level comments -->
                    <div v-if="!post.parent_id">
                        <div class="flex items-center gap-2 text-sm">
                            <strong>{{ post.user.name }}</strong>
                            <span class="text-muted-foreground">{{ new Date(post.created_at).toLocaleDateString() }}</span>
                        </div>
                        <div class="text-sm mt-1">{{ post.content }}</div>

                        <!-- Reply button -->
                        <button @click="toggleReplyBox(post.id)" class="text-xs text-blue-500 hover:underline mt-1">Reply</button>

                        <!-- Reply input -->
                        <div v-if="openReplyBox === post.id" class="mt-2 flex gap-2">
                            <input v-model="replyContent" class="flex-1 border rounded px-3 py-1 text-sm" placeholder="Write a reply..." />
                            <button @click="submitReply(post.id)" class="text-green-600">📤</button>
                        </div>

                        <!-- Replies -->
                        <div v-for="reply in post.replies" :key="reply.id" class="ml-6 mt-2 border-l pl-3">
                            <div class="text-sm flex items-center gap-2">
                                <strong>{{ reply.user.name }}</strong>
                                <span class="text-muted-foreground">{{ new Date(reply.created_at).toLocaleDateString() }}</span>
                            </div>
                            <div class="text-sm mt-1">{{ reply.content }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>


