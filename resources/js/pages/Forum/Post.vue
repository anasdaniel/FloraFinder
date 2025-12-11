<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Icon from '@/components/Icon.vue';
import { Trash2, MessageCircle, SendHorizontal } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

// Props from Inertia page
const page = usePage();
const thread = ref(page.props.thread);
const authUser = page.props.auth.user;

// UI state
const openReplyBox = ref<number | null>(null);
const commentContent = ref('');
const replyContent = ref('');

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Forum', href: '/forum' },
    { title: thread.value.title, href: `/forum/${thread.value.id}` },
];

const isOwner = (item) => authUser && authUser.id === item.user_id;

// Submit comment
function submitComment() {
    if (!commentContent.value.trim()) return;
    router.post(`/forum/${thread.value.id}/comments`, { content: commentContent.value }, {
        preserveScroll: true,
        onSuccess: () => {
            commentContent.value = '';
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
    router.post(`/forum/${thread.value.id}/reply/${postId}`, { content: replyContent.value }, {
        preserveScroll: true,
        onSuccess: () => {
            replyContent.value = '';
            openReplyBox.value = null;
        }
    });
}

function deleteComment(id: number) {
    if (!confirm("Delete this comment?")) return;
    router.delete(`/forum/comment/${id}`, { preserveScroll: true });
}

function deleteThread() {
    if (confirm("Are you sure you want to delete this thread?")) {
        router.delete(`/forum/${thread.value.id}`);
    }
}
</script>

<template>
    <Head :title="thread.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-3xl px-4 py-8 mx-auto">
            <Button as-child variant="ghost" size="sm" class="gap-2 mb-4 pl-0 hover:bg-transparent hover:underline">
                <Link href="/forum">
                    <Icon name="arrow-left" class="w-4 h-4" /> Back to Forum
                </Link>
            </Button>

            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <!-- Header: Author & Meta -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <Avatar class="h-12 w-12 border border-gray-100">
                            <AvatarImage :src="thread.user?.avatar" />
                            <AvatarFallback class="bg-primary/5 text-primary text-sm font-bold">
                                {{ (thread.user?.name || thread.title).substring(0, 2).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <div class="font-bold text-gray-900 text-base">{{ thread.user?.name || 'Unknown' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ new Date(thread.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium capitalize">
                            {{ thread.category }}
                        </span>
                         <button
                            v-if="isOwner(thread)"
                            @click="deleteThread"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors"
                            title="Delete Thread"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="mb-6">
                    <h1 class="font-bold text-gray-900 text-2xl mb-4 leading-tight">{{ thread.title }}</h1>
                    <p v-if="thread.content" class="text-gray-700 text-base leading-relaxed whitespace-pre-wrap">
                        {{ thread.content }}
                    </p>
                </div>

                <!-- Image -->
                 <div v-if="thread.image" class="mb-8 rounded-xl overflow-hidden border border-gray-100">
                    <img
                        :src="`/storage/${thread.image}`"
                        class="w-full h-auto max-h-[600px] object-cover"
                    />
                </div>

                <!-- Footer Stats -->
                <div class="flex items-center gap-2 pb-6 border-b border-gray-100 mb-6">
                    <MessageCircle class="w-5 h-5 text-gray-400" />
                    <span class="font-medium text-gray-600">{{ thread.posts?.length || 0 }} Comments</span>
                </div>


                <!-- Comments Section -->
                <div class="space-y-8">
                     <!-- Input -->
                    <div class="flex gap-4">
                        <Avatar class="h-10 w-10 hidden sm:block">
                            <AvatarImage :src="$page.props.auth.user.avatar" />
                            <AvatarFallback>{{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                        </Avatar>
                        <div class="flex-1 relative">
                            <input
                                v-model="commentContent"
                                type="text"
                                placeholder="Write a comment..."
                                class="w-full border border-gray-200 rounded-full py-3 pl-5 pr-12 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 shadow-sm transition-all"
                                @keydown.enter="submitComment"
                            />
                            <button
                                @click="submitComment"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-primary text-primary-foreground rounded-full hover:bg-primary/90 transition-colors"
                                :disabled="!commentContent.trim()"
                            >
                                <SendHorizontal class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Comments List -->
                    <div class="space-y-6">
                        <div
                            v-for="post in thread.posts"
                            :key="post.id"
                            class="group"
                        >
                            <div class="flex gap-4">
                                <Avatar class="h-10 w-10 mt-1 border border-gray-200">
                                    <AvatarImage :src="post.user?.avatar" />
                                    <AvatarFallback class="text-xs bg-white text-gray-700">
                                        {{ (post.user?.name || '?').substring(0, 2).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1">
                                    <div class="bg-gray-50/80 border border-gray-100 rounded-2xl px-5 py-4 inline-block min-w-[200px]">
                                         <div class="flex justify-between items-start mb-1">
                                            <span class="font-bold text-sm text-gray-900">{{ post.user?.name || 'Unknown' }}</span>
                                            <span class="text-xs text-gray-400 ml-4">{{ new Date(post.created_at).toLocaleDateString() }}</span>
                                         </div>
                                        <p class="text-sm text-gray-800 leading-relaxed">{{ post.content }}</p>
                                    </div>

                                    <div class="flex items-center gap-4 mt-1.5 ml-2">
                                        <button
                                            class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors"
                                            @click="toggleReplyBox(post.id)"
                                        >
                                            Reply
                                        </button>
                                         <button
                                            v-if="post.user_id === $page.props.auth.user.id"
                                            class="text-xs font-semibold text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100"
                                            @click="deleteComment(post.id)"
                                        >
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Reply Input -->
                                     <div v-if="openReplyBox === post.id" class="mt-3 flex gap-2 items-center max-w-lg">
                                        <div class="h-8 w-0.5 bg-gray-200 ml-2"></div>
                                        <input
                                            v-model="replyContent"
                                            type="text"
                                            placeholder="Write a reply..."
                                            class="flex-1 border border-gray-200 rounded-full py-2 px-4 text-xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                                            @keydown.enter="submitReply(post.id)"
                                        />
                                    </div>

                                    <!-- Replies -->
                                    <div v-if="post.replies && post.replies.length" class="mt-4 space-y-4 pl-4 border-l-2 border-gray-100 ml-4">
                                        <div
                                            v-for="reply in post.replies"
                                            :key="reply.id"
                                            class="flex gap-3 group/reply"
                                        >
                                             <Avatar class="h-8 w-8 mt-1 border border-gray-200">
                                                <AvatarImage :src="reply.user?.avatar" />
                                                <AvatarFallback class="text-xs bg-white text-gray-700">
                                                    {{ (reply.user?.name || '?').substring(0, 2).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="bg-gray-50/80 border border-gray-100 rounded-2xl px-4 py-3">
                                                    <div class="flex items-center justify-between gap-4 mb-1">
                                                        <span class="font-bold text-xs text-gray-900">{{ reply.user?.name }}</span>
                                                        <span class="text-[10px] text-gray-400">{{ new Date(reply.created_at).toLocaleDateString() }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-800">{{ reply.content }}</p>
                                                </div>
                                                 <div class="flex items-center gap-4 mt-1 ml-2">
                                                     <button
                                                        v-if="reply.user_id === $page.props.auth.user.id"
                                                        class="text-[10px] font-semibold text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover/reply:opacity-100"
                                                        @click="deleteComment(reply.id)"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


