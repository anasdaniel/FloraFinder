<script setup lang="ts">
import { defineProps, ref, computed, reactive } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import AppLayout from "@/layouts/AppLayout.vue";
import Heading from "@/components/Heading.vue";
import Icon from "@/components/Icon.vue";
import type { BreadcrumbItem, ForumThread } from "@/types";
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Trash2, MessageCircle, SendHorizontal, Search, Leaf, Users, Plus } from 'lucide-vue-next';

const activeCommentThread = ref<number | null>(null);
const newComment = ref("");

// Tag management per thread
const showTagDropdown = reactive<Record<number, boolean>>({});
const selectedTag = ref(null);


const page = usePage();
const authUser = page.props.auth.user;

// Add this at the top with your other refs
const allTags = ref<{ id: number; tag_name: string }[]>([]);

// Fetch or define all tags (could be from page props or API)
allTags.value = page.props.allTags || []; // if passed from backend

const isOwner = (thread) => authUser && authUser.id === thread.user_id;

const addTag = (threadId) => {
    if (!selectedTag.value) return;

    router.post(`/forum/${threadId}/tags`,
        { tag_id: selectedTag.value },
        { preserveScroll: true }
    );

    showTagDropdown[threadId] = false;
    selectedTag.value = null;
};

const removeTag = (threadId, tagId) => {
    if (!confirm("Are you sure you want to remove this tag?")) {
        return;
    }

    router.delete(`/forum/${threadId}/tags/${tagId}`, {
        preserveScroll: true,
    });
};




// Local cache of comments per thread
const commentsMap = ref<Record<number, any[]>>({});
const commentsLoading = ref(false);

// New: replies state per comment + active reply toggler
const activeReplyComment = ref<number | null>(null);
// reactive object keyed by comment id to hold reply input strings
const newReplies = reactive<Record<number, string>>({});

// Props
const props = defineProps<{
    threads: ForumThread[];
}>();

// Category filter state
const selectedCategory = ref("general");

const filteredThreads = computed(() => {
    if (selectedCategory.value === "all") {
        return props.threads;
    }
    return props.threads.filter(
        t => t.category.toLowerCase() === selectedCategory.value.toLowerCase()
    );
});

// Delete thread
const deleteThread = (threadId: number) => {
    if (confirm("Are you sure you want to delete this thread?")) {
        Inertia.delete(`/forum/${threadId}`);
    }
};

// Toggle showing the comment input field and fetch comments when opening
const toggleCommentField = async (threadId: number) => {
    if (activeCommentThread.value === threadId) {
        activeCommentThread.value = null;
        return;
    }

    activeCommentThread.value = threadId;

    // If already have comments cached, skip fetch
    if (commentsMap.value[threadId] && commentsMap.value[threadId].length > 0) {
        return;
    }

    await fetchComments(threadId);
};

// Fetch latest comments (top-level posts) for a thread
const fetchComments = async (threadId: number) => {
    try {
        commentsLoading.value = true;
        const res = await fetch(`/forum/${threadId}/posts`, {
            headers: { "Accept": "application/json" },
            credentials: "same-origin",
        });
        if (!res.ok) {
            console.error("Failed to fetch comments for thread", threadId);
            commentsMap.value[threadId] = [];
            return;
        }
        const json = await res.json();
        commentsMap.value[threadId] = json.posts || [];
    } catch (e) {
        console.error(e);
        commentsMap.value[threadId] = [];
    } finally {
        commentsLoading.value = false;
    }
};

// Post a comment and refresh the comment list
const postComment = async (threadId: number) => {
    if (!newComment.value.trim()) return;

    try {
        console.debug('Posting comment', { threadId, content: newComment.value });
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/forum/${threadId}/comments`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ content: newComment.value }),
        });

        if (!res.ok) {
            const json = await res.json().catch(() => null);
            console.error('Failed to post comment', json || res.status);
            return;
        }

        const json = await res.json();
        const createdPost = json.post;
        createdPost.replies = createdPost.replies || [];

        newComment.value = '';
        // Insert into cached comments
        commentsMap.value[threadId] = commentsMap.value[threadId] || [];
        commentsMap.value[threadId].push(createdPost);
    } catch (e) {
        console.error(e);
    }
};

// Post a reply to a comment (threadId & commentId come from template)
const postReply = async (commentId: number, threadId: number) => {
    const content = (newReplies[commentId] || "").trim();
    if (!content) return;

    try {
        console.debug('Posting reply', { threadId, commentId, content });
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const res = await fetch(`/forum/${threadId}/reply/${commentId}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ content }),
        });

        if (!res.ok) {
            // If validation error returns json, you could display it here
            const json = await res.json().catch(() => null);
            console.error('Failed to post reply', json || res.status);
            return;
        }

        // If server responded with non-JSON (e.g. redirect to HTML), log and bail
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            console.error('Unexpected response content type when posting reply', contentType);
            return;
        }

        const json = await res.json();
        const createdReply = json.post;

        // clear the reply input for that comment and collapse input
        newReplies[commentId] = "";
        activeReplyComment.value = null;

        // Update comments locally: find the parent comment in the cached comments and add the reply
        const comments = commentsMap.value[threadId] || [];
        const parent = comments.find((c: any) => c.id === commentId);
        if (parent) {
            parent.replies = parent.replies || [];
            parent.replies.push(createdReply);
        } else {
            // if local cache doesn't have comments, fetch them
            fetchComments(threadId);
        }
    } catch (e) {
        console.error(e);
    }
};

// delete comment (or reply). Accept optional threadId to refresh comments after deletion
const deleteComment = (id: number, threadId?: number) => {
    if (!confirm("Delete this comment?")) return;

    Inertia.delete(`/forum/comment/${id}`, {
        onSuccess: () => {
            if (threadId) {
                fetchComments(threadId);
            }
        }
    });
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{ title: "Forum", href: "/forum" }];
</script>

<template>
    <Head title="Forum" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-3xl px-4 py-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Forum</h1>
                    <p class="mt-1 text-sm text-gray-500">Ask questions, share knowledge, and connect with the FloraFinder community.</p>
                </div>
                <Link href="/forum/new">
                    <Button type="button" variant="default" size="sm" class="gap-2 bg-gray-900 hover:bg-gray-800">
                        <Plus class="w-4 h-4" />
                        New Post
                    </Button>
                </Link>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex flex-wrap gap-2 mb-8 p-1 bg-gray-100/50 rounded-xl w-fit">
                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    :class="selectedCategory === 'general'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-200/50'"
                    @click="selectedCategory = 'general'"
                >
                    General
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    :class="selectedCategory === 'identification'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-200/50'"
                    @click="selectedCategory = 'identification'"
                >
                    <Search class="w-4 h-4" />
                    Plant Identification
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    :class="selectedCategory === 'care'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-200/50'"
                    @click="selectedCategory = 'care'"
                >
                    <Leaf class="w-4 h-4" />
                    Plant Care
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    :class="selectedCategory === 'offtopic'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-200/50'"
                    @click="selectedCategory = 'offtopic'"
                >
                    <Users class="w-4 h-4" />
                    Off Topic
                </button>
            </div>

            <!-- Threads List -->
            <div class="space-y-6">
                <div
                    v-for="thread in filteredThreads"
                    :key="thread.id"
                    class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-200"
                >
                    <!-- Header: Author & Meta -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <Avatar class="h-10 w-10 border border-gray-100">
                                <AvatarImage :src="thread.user?.avatar" />
                                <AvatarFallback class="bg-primary/5 text-primary text-xs font-bold">
                                    {{ (thread.user?.name || thread.title).substring(0, 2).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">{{ thread.user?.name || 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ new Date(thread.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</div>
                            </div>
                        </div>

                        <!-- Actions Dropdown or Buttons -->
                        <div class="flex items-center gap-2">
                             <button
                                v-if="isOwner(thread)"
                                @click="deleteThread(thread.id)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors"
                                title="Delete Thread"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-4">
                        <Link :href="`/forum/${thread.id}`" class="block group/title">
                            <h3 class="font-bold text-gray-900 text-lg mb-2 leading-tight group-hover/title:text-primary transition-colors">{{ thread.title }}</h3>
                        </Link>
                        <p v-if="thread.content" class="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap">
                            {{ thread.content }}
                        </p>
                    </div>

                    <!-- Image -->
                     <div v-if="thread.image" class="mb-4 rounded-xl overflow-hidden border border-gray-100">
                        <img
                            :src="`/storage/${thread.image}`"
                            class="w-full h-auto max-h-[500px] object-cover"
                        />
                    </div>

                    <!-- Tags -->
                    <div v-if="thread.tags?.length || isOwner(thread)" class="flex flex-wrap gap-2 mb-4">
                        <span
                            v-for="tag in thread.tags"
                            :key="tag.id"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium bg-gray-50 text-gray-600 rounded-md border border-gray-100"
                        >
                            {{ tag.tag_name }}
                            <button
                                v-if="isOwner(thread)"
                                class="text-gray-400 hover:text-red-500"
                                @click="removeTag(thread.id, tag.id)"
                            >
                                ×
                            </button>
                        </span>

                         <div v-if="isOwner(thread)" class="relative">
                            <button
                                class="px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-md border border-dashed border-gray-200 transition-colors"
                                @click="showTagDropdown[thread.id] = !showTagDropdown[thread.id]"
                            >
                                + Tag
                            </button>
                            <div
                                v-if="showTagDropdown[thread.id]"
                                class="absolute left-0 mt-2 bg-white shadow-xl border rounded-lg p-3 z-50 w-48"
                            >
                                <select v-model="selectedTag" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-sm mb-2 focus:ring-2 focus:ring-primary/20 outline-none">
                                    <option disabled value="">Select tag</option>
                                    <option v-for="t in allTags" :key="t.id" :value="t.id">
                                        {{ t.tag_name }}
                                    </option>
                                </select>
                                <Button
                                    size="sm"
                                    class="w-full"
                                    @click="addTag(thread.id)"
                                >
                                    Add Tag
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                        <div class="flex items-center gap-4">
                             <button
                                @click="toggleCommentField(thread.id)"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 transition-colors"
                            >
                                <MessageCircle class="w-5 h-5" />
                                <span class="font-medium">{{ thread.posts_count || 0 }} Comments</span>
                            </button>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div v-if="activeCommentThread === thread.id" class="mt-4 pt-4 border-t border-gray-50 bg-gray-50/50 -mx-6 px-6 pb-6 rounded-b-2xl">
                         <!-- Input -->
                        <div class="flex gap-3 mb-6">
                            <Avatar class="h-8 w-8 hidden sm:block">
                                <AvatarImage :src="$page.props.auth.user.avatar" />
                                <AvatarFallback>{{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1 relative">
                                <input
                                    v-model="newComment"
                                    type="text"
                                    placeholder="Write a comment..."
                                    class="w-full border border-gray-200 rounded-full py-2.5 pl-4 pr-12 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 shadow-sm transition-all"
                                    @focus="activeCommentThread = thread.id"
                                    @keydown.enter="postComment(thread.id)"
                                />
                                <button
                                    @click="postComment(thread.id)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-primary text-primary-foreground rounded-full hover:bg-primary/90 transition-colors"
                                    :disabled="!newComment.trim()"
                                >
                                    <SendHorizontal class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                         <div v-if="commentsLoading" class="text-center py-4">
                             <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div>
                        </div>

                        <div v-else class="space-y-6">
                             <div
                                v-if="(commentsMap[thread.id] || []).length === 0"
                                class="text-sm text-gray-500 text-center py-2"
                            >
                                Be the first to comment.
                            </div>

                            <div
                                v-for="comment in commentsMap[thread.id] || []"
                                :key="comment.id"
                                class="group"
                            >
                                <div class="flex gap-3">
                                    <Avatar class="h-8 w-8 mt-1 border border-gray-200">
                                        <AvatarImage :src="comment.user?.avatar" />
                                        <AvatarFallback class="text-xs bg-white text-gray-700">
                                            {{ (comment.user?.name || '?').substring(0, 2).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 shadow-sm inline-block min-w-[200px]">
                                             <div class="flex justify-between items-start mb-1">
                                                <span class="font-semibold text-sm text-gray-900">{{ comment.user?.name || 'Unknown' }}</span>
                                                <span class="text-xs text-gray-400">{{ new Date(comment.created_at).toLocaleDateString() }}</span>
                                             </div>
                                            <p class="text-sm text-gray-700 leading-relaxed">{{ comment.content }}</p>
                                        </div>

                                        <div class="flex items-center gap-4 mt-1 ml-2">
                                            <button
                                                class="text-xs font-medium text-gray-500 hover:text-gray-900 transition-colors"
                                                @click="activeReplyComment = (activeReplyComment === comment.id ? null : comment.id)"
                                            >
                                                Reply
                                            </button>
                                             <button
                                                v-if="comment.user_id === $page.props.auth.user.id"
                                                class="text-xs font-medium text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100"
                                                @click="deleteComment(comment.id, thread.id)"
                                            >
                                                Delete
                                            </button>
                                        </div>

                                        <!-- Reply Input -->
                                         <div v-if="activeReplyComment === comment.id" class="mt-3 flex gap-2 items-center">
                                            <div class="h-8 w-0.5 bg-gray-200"></div>
                                            <input
                                                v-model="newReplies[comment.id]"
                                                type="text"
                                                placeholder="Write a reply..."
                                                class="flex-1 border border-gray-200 rounded-full py-2 px-4 text-xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                                                @keydown.enter="postReply(comment.id, thread.id)"
                                            />
                                        </div>

                                        <!-- Replies -->
                                        <div v-if="comment.replies && comment.replies.length" class="mt-3 space-y-3 pl-4 border-l-2 border-gray-200/50">
                                            <div
                                                v-for="reply in comment.replies"
                                                :key="reply.id"
                                                class="flex gap-3 group/reply"
                                            >
                                                 <Avatar class="h-6 w-6 mt-1 border border-gray-200">
                                                    <AvatarImage :src="reply.user?.avatar" />
                                                    <AvatarFallback class="text-[10px] bg-white text-gray-700">
                                                        {{ (reply.user?.name || '?').substring(0, 2).toUpperCase() }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div class="bg-white border border-gray-100 rounded-2xl px-3 py-2 shadow-sm">
                                                        <div class="flex items-center gap-2 mb-0.5">
                                                            <span class="font-semibold text-xs text-gray-900">{{ reply.user?.name }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-700">{{ reply.content }}</p>
                                                    </div>
                                                     <div class="flex items-center gap-4 mt-1 ml-2">
                                                         <button
                                                            v-if="reply.user_id === $page.props.auth.user.id"
                                                            class="text-[10px] font-medium text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover/reply:opacity-100"
                                                            @click="deleteComment(reply.id, thread.id)"
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


            <div class="mt-10 text-xs text-center text-muted-foreground">
                <span>Powered by FloraFinder Community</span>
            </div>
        </div>
    </AppLayout>
</template>
