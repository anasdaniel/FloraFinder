<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Icon from '@/components/Icon.vue';
import { Trash2, MessageCircle, SendHorizontal, Heart, Share2 } from 'lucide-vue-next';
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

// Like/Unlike thread
const toggleLike = async () => {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/forum/${thread.value.id}/like`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            console.error('Failed to toggle like');
            return;
        }

        const json = await res.json();
        thread.value.is_liked_by_user = json.is_liked;
        thread.value.likes_count = json.likes_count;
    } catch (e) {
        console.error(e);
    }
};

// Share thread
const shareThread = async () => {
    try {
        const url = window.location.href;
        await navigator.clipboard.writeText(url);

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/forum/${thread.value.id}/share`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            credentials: 'same-origin',
        });

        if (res.ok) {
            const json = await res.json();
            thread.value.shares_count = json.shares_count;
        }

        alert('Link copied to clipboard!');
    } catch (e) {
        console.error(e);
        alert('Failed to copy link');
    }
};
</script>

<template>
    <Head :title="thread.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-2xl px-4 py-6 mx-auto">
            <Button as-child variant="ghost" size="sm" class="gap-2 mb-3 pl-0 hover:bg-transparent hover:underline">
                <Link href="/forum">
                    <Icon name="arrow-left" class="w-3.5 h-3.5" /> Back to Forum
                </Link>
            </Button>

            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                <!-- Header: Author & Meta -->
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <Avatar class="h-10 w-10 border border-gray-100">
                            <AvatarImage :src="thread.user?.avatar" />
                            <AvatarFallback class="bg-primary/5 text-primary text-xs font-bold">
                                {{ (thread.user?.name || thread.title).substring(0, 2).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">{{ thread.user?.name || 'Unknown' }}</div>
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ new Date(thread.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-medium capitalize">
                            {{ thread.category }}
                        </span>
                         <button
                            v-if="isOwner(thread)"
                            @click="deleteThread"
                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors"
                            title="Delete Thread"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="mb-5">
                    <h1 class="font-bold text-gray-900 text-xl mb-3 leading-tight">{{ thread.title }}</h1>
                    <p v-if="thread.content" class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">
                        {{ thread.content }}
                    </p>
                </div>

                <!-- Image -->
                 <div v-if="thread.image" class="mb-6 rounded-xl overflow-hidden border border-gray-100">
                    <img
                        :src="`/storage/${thread.image}`"
                        class="w-full h-auto max-h-[500px] object-cover"
                    />
                </div>

                <!-- Footer Stats -->
                <div class="flex items-center gap-6 pb-5 border-b border-gray-100 mb-5">
                    <div class="flex items-center gap-2">
                        <MessageCircle class="w-4 h-4 text-gray-400" />
                        <span class="font-medium text-xs text-gray-600">{{ thread.posts?.length || 0 }} Comments</span>
                    </div>
                    
                    <button
                        @click="toggleLike"
                        class="flex items-center gap-2 transition-colors"
                        :class="thread.is_liked_by_user ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-red-500'"
                    >
                        <Heart class="w-4 h-4" :class="{ 'fill-red-500': thread.is_liked_by_user }" />
                        <span class="font-medium text-xs">{{ thread.likes_count || 0 }} Likes</span>
                    </button>

                    <button
                        @click="shareThread"
                        class="flex items-center gap-2 text-gray-400 hover:text-gray-900 transition-colors ml-auto"
                    >
                        <Share2 class="w-4 h-4" />
                        <span class="font-medium text-xs">Share</span>
                    </button>
                </div>


                <!-- Comments Section -->
                <div class="space-y-6">
                     <!-- Input -->
                    <div class="flex gap-3">
                        <Avatar class="h-8 w-8 hidden sm:block">
                            <AvatarImage :src="$page.props.auth.user.avatar" />
                            <AvatarFallback class="text-[10px]">{{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                        </Avatar>
                        <div class="flex-1 relative">
                            <input
                                v-model="commentContent"
                                type="text"
                                placeholder="Write a comment..."
                                class="w-full border border-gray-200 rounded-full py-2.5 pl-4 pr-10 text-xs focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 shadow-sm transition-all"
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
                    <div class="space-y-5">
                        <div
                            v-for="post in thread.posts"
                            :key="post.id"
                            class="group"
                        >
                            <div class="flex gap-3">
                                <Avatar class="h-8 w-8 mt-1 border border-gray-200">
                                    <AvatarImage :src="post.user?.avatar" />
                                    <AvatarFallback class="text-[10px] bg-white text-gray-700">
                                        {{ (post.user?.name || '?').substring(0, 2).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1">
                                    <div class="bg-gray-50/80 border border-gray-100 rounded-2xl px-4 py-3 inline-block min-w-[180px]">
                                         <div class="flex justify-between items-start mb-0.5">
                                            <span class="font-bold text-xs text-gray-900">{{ post.user?.name || 'Unknown' }}</span>
                                            <span class="text-[10px] text-gray-400 ml-4">{{ new Date(post.created_at).toLocaleDateString() }}</span>
                                         </div>
                                        <p class="text-xs text-gray-800 leading-relaxed">{{ post.content }}</p>
                                    </div>

                                    <div class="flex items-center gap-3 mt-1 ml-2">
                                        <button
                                            class="text-[10px] font-semibold text-gray-500 hover:text-gray-900 transition-colors"
                                            @click="toggleReplyBox(post.id)"
                                        >
                                            Reply
                                        </button>
                                         <button
                                            v-if="post.user_id === $page.props.auth.user.id"
                                            class="text-[10px] font-semibold text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100"
                                            @click="deleteComment(post.id)"
                                        >
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Reply Input -->
                                     <div v-if="openReplyBox === post.id" class="mt-2 flex gap-2 items-center max-w-md">
                                        <div class="h-6 w-0.5 bg-gray-200 ml-2"></div>
                                        <input
                                            v-model="replyContent"
                                            type="text"
                                            placeholder="Write a reply..."
                                            class="flex-1 border border-gray-200 rounded-full py-1.5 px-3 text-[10px] focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                                            @keydown.enter="submitReply(post.id)"
                                        />
                                    </div>

                                    <!-- Replies -->
                                    <div v-if="post.replies && post.replies.length" class="mt-3 space-y-3 pl-3 border-l-2 border-gray-100 ml-3">
                                        <div
                                            v-for="reply in post.replies"
                                            :key="reply.id"
                                            class="flex gap-2 group/reply"
                                        >
                                             <Avatar class="h-7 w-7 mt-1 border border-gray-200">
                                                <AvatarImage :src="reply.user?.avatar" />
                                                <AvatarFallback class="text-[10px] bg-white text-gray-700">
                                                    {{ (reply.user?.name || '?').substring(0, 2).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="bg-gray-50/80 border border-gray-100 rounded-2xl px-3 py-2">
                                                    <div class="flex items-center justify-between gap-3 mb-0.5">
                                                        <span class="font-bold text-[10px] text-gray-900">{{ reply.user?.name }}</span>
                                                        <span class="text-[9px] text-gray-400">{{ new Date(reply.created_at).toLocaleDateString() }}</span>
                                                    </div>
                                                    <p class="text-[10px] text-gray-800">{{ reply.content }}</p>
                                                </div>
                                                 <div class="flex items-center gap-3 mt-0.5 ml-2">
                                                     <button
                                                        v-if="reply.user_id === $page.props.auth.user.id"
                                                        class="text-[9px] font-semibold text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover/reply:opacity-100"
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


