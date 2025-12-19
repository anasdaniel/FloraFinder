<script setup lang="ts">
import { defineProps, ref, computed, reactive, watch } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import AppLayout from "@/layouts/AppLayout.vue";
import Heading from "@/components/Heading.vue";
import Icon from "@/components/Icon.vue";
import type { BreadcrumbItem, ForumThread } from "@/types";
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Trash2, MessageCircle, SendHorizontal, Search, Leaf, Users, Plus, Heart, Share2, ChevronLeft, ChevronRight, SearchX, Coffee, ImageIcon, AlertTriangle, Info, FlaskConical, Sparkles } from 'lucide-vue-next';

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

interface PaginatedThreads {
    data: ForumThread[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

// Props
const props = defineProps<{
    threads: PaginatedThreads;
    allTags: any[];
    seasonalAlerts: any[];
    filters: {
        category?: string;
    };
}>();

// Helper to get icon for alert type
const getAlertIcon = (type: string) => {
    if (type === 'peak') return Info;
    if (type === 'starting') return FlaskConical;
    return Sparkles;
};

// Category filter state
const selectedCategory = ref(props.filters.category || "all");

const filteredThreads = computed(() => {
    return props.threads.data;
});

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {
        category: selectedCategory.value !== 'all' ? selectedCategory.value : undefined
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(selectedCategory, (newCategory) => {
    router.get('/forum', {
        category: newCategory !== 'all' ? newCategory : undefined
    }, {
        preserveState: true,
        preserveScroll: true,
    });
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

// Like/Unlike thread
const toggleLike = async (thread: ForumThread) => {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/forum/${thread.id}/like`, {
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
        thread.is_liked_by_user = json.is_liked;
        thread.likes_count = json.likes_count;
    } catch (e) {
        console.error(e);
    }
};

// Share thread
const shareThread = async (thread: ForumThread) => {
    try {
        // Copy thread URL to clipboard
        const url = `${window.location.origin}/forum/${thread.id}`;
        await navigator.clipboard.writeText(url);

        // Increment share counter
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`/forum/${thread.id}/share`, {
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
            thread.shares_count = json.shares_count;
        }

        // Optional: Show a toast notification
        alert('Link copied to clipboard!');
    } catch (e) {
        console.error(e);
        alert('Failed to copy link');
    }
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{ title: "Forum", href: "/forum" }];
</script>

<template>
    <Head title="Forum" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-3xl px-4 py-12 mx-auto">
            <!-- Header -->
            <div class="flex flex-col items-start justify-between gap-4 mb-10 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-4xl font-extrabold text-[#0f172a] tracking-tight">Forum</h1>
                    <p class="mt-2 text-gray-500 font-medium">Ask questions, share knowledge, and connect with the FloraFinder community.</p>
                </div>
                <Link
                    href="/forum/new"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white transition-all bg-gray-900 rounded-xl hover:bg-black shadow-lg shadow-gray-900/10 hover:shadow-gray-900/20"
                >
                    <Plus class="w-4 h-4" />
                    <span class="font-bold">New Post</span>
                </Link>
            </div>

            <!-- Seasonal Alerts -->
            <div v-if="props.seasonalAlerts.length > 0" class="mb-10 bg-white border border-gray-100 rounded-[2.5rem] p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-xl bg-orange-50">
                        <AlertTriangle class="w-5 h-5 text-orange-600" />
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Seasonal Alerts</h2>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="alert in props.seasonalAlerts"
                        :key="alert.id"
                        class="group relative overflow-hidden rounded-3xl border transition-all duration-300"
                        :class="[
                            alert.color === 'orange' ? 'bg-orange-50/50 border-orange-100 hover:border-orange-200' : 'bg-blue-50/50 border-blue-100 hover:border-blue-200'
                        ]"
                    >
                        <div class="p-6 flex items-start gap-5">
                            <div
                                class="flex-shrink-0 p-3 rounded-2xl shadow-sm border transition-transform group-hover:scale-110"
                                :class="[
                                    alert.color === 'orange' ? 'bg-white border-orange-100 text-orange-600' : 'bg-white border-blue-100 text-blue-600'
                                ]"
                            >
                                <component :is="getAlertIcon(alert.type)" class="w-6 h-6" />
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="text-lg font-bold mb-1"
                                    :class="alert.color === 'orange' ? 'text-orange-900' : 'text-blue-900'"
                                >
                                    {{ alert.title }}
                                </h3>
                                <p
                                    class="text-sm font-medium leading-relaxed"
                                    :class="alert.color === 'orange' ? 'text-orange-700/80' : 'text-blue-700/80'"
                                >
                                    {{ alert.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex flex-wrap gap-3 mb-8">
                <button
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium rounded-full transition-all border shadow-sm"
                    :class="selectedCategory === 'all' || selectedCategory === 'general'
                        ? 'bg-[#0f172a] text-white border-[#0f172a]'
                        : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-50'"
                    @click="selectedCategory = 'all'"
                >
                    General
                </button>

                <button
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-full transition-all border bg-white shadow-sm"
                    :class="selectedCategory === 'identification'
                        ? 'ring-2 ring-primary/20 border-primary text-primary'
                        : 'text-gray-700 border-gray-200 hover:bg-gray-50'"
                    @click="selectedCategory = 'identification'"
                >
                    <Search class="w-4 h-4" />
                    Plant Identification
                </button>

                <button
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-full transition-all border bg-white shadow-sm"
                    :class="selectedCategory === 'care'
                        ? 'ring-2 ring-primary/20 border-primary text-primary'
                        : 'text-gray-700 border-gray-200 hover:bg-gray-50'"
                    @click="selectedCategory = 'care'"
                >
                    <Leaf class="w-4 h-4" />
                    Plant Care
                </button>

                <button
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-full transition-all border bg-white shadow-sm"
                    :class="selectedCategory === 'offtopic'
                        ? 'ring-2 ring-primary/20 border-primary text-primary'
                        : 'text-gray-700 border-gray-200 hover:bg-gray-50'"
                    @click="selectedCategory = 'offtopic'"
                >
                    <Coffee class="w-4 h-4" />
                    Off Topic
                </button>
            </div>

            <!-- Threads List -->
            <div class="space-y-6">
                <div v-if="filteredThreads.length === 0" class="text-center py-12 bg-white border border-gray-100 rounded-2xl">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                        <SearchX class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">No threads found</h3>
                    <p class="text-gray-500">Try adjusting your filters or start a new discussion.</p>
                </div>
                <div
                    v-for="thread in filteredThreads"
                    :key="thread.id"
                    class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300"
                >
                    <!-- Header: Author & Meta -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <Avatar class="h-12 w-12 border-2 border-gray-50 shadow-sm">
                                <AvatarImage :src="thread.user?.avatar" />
                                <AvatarFallback class="bg-emerald-50 text-emerald-700 text-sm font-bold">
                                    {{ (thread.user?.name || thread.title).substring(0, 2).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div class="font-bold text-gray-900 text-base">{{ thread.user?.name || 'Unknown' }}</div>
                                <div class="text-xs font-medium text-gray-400">{{ new Date(thread.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                             <button
                                v-if="isOwner(thread)"
                                @click="deleteThread(thread.id)"
                                class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all"
                                title="Delete Thread"
                            >
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <Link :href="`/forum/${thread.id}`" class="block group/title">
                            <h3 class="font-extrabold text-gray-900 text-2xl mb-3 leading-tight group-hover/title:text-emerald-600 transition-colors">{{ thread.title }}</h3>
                        </Link>
                        <p v-if="thread.content" class="text-gray-600 text-base leading-relaxed whitespace-pre-wrap">
                            {{ thread.content }}
                        </p>
                    </div>

                    <!-- Image -->
                     <div v-if="thread.image" class="mb-6 relative rounded-3xl overflow-hidden border border-gray-100 group/image">
                        <img
                            :src="`/storage/${thread.image}`"
                            class="w-full h-auto max-h-[600px] object-cover transition-transform duration-500 group-hover/image:scale-[1.02]"
                        />
                        <div class="absolute bottom-4 right-4 bg-black/60 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2">
                            <ImageIcon class="w-3.5 h-3.5" />
                            1 Image
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="thread.tags?.length || isOwner(thread)" class="flex flex-wrap items-center gap-3 mb-8">
                        <span
                            v-for="tag in thread.tags"
                            :key="tag.id"
                            class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold bg-gray-100 text-gray-700 rounded-full border border-transparent hover:bg-gray-200 transition-colors"
                        >
                            #{{ tag.tag_name }}
                            <button
                                v-if="isOwner(thread)"
                                class="text-gray-400 hover:text-red-500 ml-1"
                                @click="removeTag(thread.id, tag.id)"
                            >
                                ×
                            </button>
                        </span>

                         <div v-if="isOwner(thread)" class="relative">
                            <button
                                class="px-4 py-2 text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-full border border-emerald-100 transition-all flex items-center gap-1.5"
                                @click="showTagDropdown[thread.id] = !showTagDropdown[thread.id]"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                Tag
                            </button>
                            <div
                                v-if="showTagDropdown[thread.id]"
                                class="absolute left-0 mt-2 bg-white shadow-2xl border border-gray-100 rounded-2xl p-4 z-50 w-56 animate-in fade-in zoom-in duration-200"
                            >
                                <select v-model="selectedTag" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-emerald-500/20 outline-none appearance-none bg-gray-50">
                                    <option disabled value="">Select tag</option>
                                    <option v-for="t in allTags" :key="t.id" :value="t.id">
                                        {{ t.tag_name }}
                                    </option>
                                </select>
                                <Button
                                    size="sm"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 rounded-xl"
                                    @click="addTag(thread.id)"
                                >
                                    Add Tag
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-50">
                        <div class="flex items-center gap-6">
                             <button
                                @click="toggleCommentField(thread.id)"
                                class="flex items-center gap-2.5 text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors"
                            >
                                <MessageCircle class="w-5 h-5" />
                                <span>{{ thread.posts_count || 0 }} Comments</span>
                            </button>

                            <button
                                @click="toggleLike(thread)"
                                class="flex items-center gap-2.5 text-sm font-semibold transition-colors"
                                :class="thread.is_liked_by_user ? 'text-red-500 hover:text-red-600' : 'text-gray-500 hover:text-red-500'"
                            >
                                <Heart class="w-5 h-5" :class="{ 'fill-red-500': thread.is_liked_by_user }" />
                                <span>{{ thread.likes_count || 0 }} Likes</span>
                            </button>

                            <button
                                @click="shareThread(thread)"
                                class="flex items-center gap-2.5 text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors"
                            >
                                <Share2 class="w-5 h-5" />
                                <span>Share</span>
                            </button>
                        </div>

                        <!-- Interaction Avatars -->
                        <div class="hidden sm:flex -space-x-2 overflow-hidden">
                            <Avatar v-for="i in 2" :key="i" class="inline-block h-7 w-7 rounded-full ring-2 ring-white">
                                <AvatarImage :src="`https://i.pravatar.cc/150?u=${thread.id + i}`" />
                                <AvatarFallback>U</AvatarFallback>
                            </Avatar>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div v-if="activeCommentThread === thread.id" class="mt-6 pt-8 border-t border-gray-50">
                         <div v-if="commentsLoading" class="text-center py-8">
                             <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div>
                        </div>

                        <div v-else class="space-y-8 mb-8">
                             <div
                                v-if="(commentsMap[thread.id] || []).length === 0"
                                class="text-sm text-gray-400 text-center py-4 font-medium"
                            >
                                No comments yet. Be the first to join the conversation!
                            </div>

                            <div
                                v-for="comment in commentsMap[thread.id] || []"
                                :key="comment.id"
                                class="group"
                            >
                                <div class="flex gap-4">
                                    <Avatar class="h-10 w-10 mt-1 border border-gray-100 shadow-sm">
                                        <AvatarImage :src="comment.user?.avatar" />
                                        <AvatarFallback class="text-xs bg-gray-50 text-gray-600 font-bold">
                                            {{ (comment.user?.name || '?').substring(0, 2).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="bg-gray-50/80 rounded-[1.5rem] px-5 py-4 inline-block min-w-[240px] max-w-full">
                                             <div class="flex justify-between items-center mb-1.5 gap-4">
                                                <span class="font-bold text-sm text-gray-900">{{ comment.user?.name || 'Unknown' }}</span>
                                             </div>
                                            <p class="text-sm text-gray-700 leading-relaxed">{{ comment.content }}</p>
                                        </div>

                                        <div class="flex items-center gap-5 mt-2 ml-4">
                                            <button class="text-xs font-bold text-gray-400 hover:text-gray-900 transition-colors">Like</button>
                                            <button
                                                class="text-xs font-bold text-gray-400 hover:text-gray-900 transition-colors"
                                                @click="activeReplyComment = (activeReplyComment === comment.id ? null : comment.id)"
                                            >
                                                Reply
                                            </button>
                                            <span class="text-xs font-medium text-gray-300">{{ new Date(comment.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
                                             <button
                                                v-if="comment.user_id === $page.props.auth.user.id"
                                                class="text-xs font-bold text-red-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100"
                                                @click="deleteComment(comment.id, thread.id)"
                                            >
                                                Delete
                                            </button>
                                        </div>

                                        <!-- Reply Input -->
                                         <div v-if="activeReplyComment === comment.id" class="mt-4 flex gap-3 items-center pl-4">
                                            <div class="h-10 w-0.5 bg-emerald-100 rounded-full"></div>
                                            <input
                                                v-model="newReplies[comment.id]"
                                                type="text"
                                                placeholder="Write a reply..."
                                                class="flex-1 bg-white border border-gray-100 rounded-xl py-2.5 px-4 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 shadow-sm"
                                                @keydown.enter="postReply(comment.id, thread.id)"
                                            />
                                        </div>

                                        <!-- Replies -->
                                        <div v-if="comment.replies && comment.replies.length" class="mt-5 space-y-5 pl-6 border-l-2 border-gray-100">
                                            <div
                                                v-for="reply in comment.replies"
                                                :key="reply.id"
                                                class="flex gap-3 group/reply"
                                            >
                                                 <Avatar class="h-8 w-8 mt-1 border border-gray-100 shadow-sm">
                                                    <AvatarImage :src="reply.user?.avatar" />
                                                    <AvatarFallback class="text-[10px] bg-gray-50 text-gray-600 font-bold">
                                                        {{ (reply.user?.name || '?').substring(0, 2).toUpperCase() }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div class="bg-gray-50/50 rounded-2xl px-4 py-2.5">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <span class="font-bold text-xs text-gray-900">{{ reply.user?.name }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-700 leading-relaxed">{{ reply.content }}</p>
                                                    </div>
                                                     <div class="flex items-center gap-4 mt-1.5 ml-3">
                                                         <button
                                                            v-if="reply.user_id === $page.props.auth.user.id"
                                                            class="text-[10px] font-bold text-red-300 hover:text-red-500 transition-colors opacity-0 group-hover/reply:opacity-100"
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

                        <!-- Input -->
                        <div class="flex gap-4">
                            <Avatar class="h-10 w-10 hidden sm:block border border-gray-100">
                                <AvatarImage :src="$page.props.auth.user.avatar" />
                                <AvatarFallback class="bg-gray-50 text-gray-600 text-xs font-bold">{{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1 relative group/input">
                                <input
                                    v-model="newComment"
                                    type="text"
                                    placeholder="Write a comment..."
                                    class="w-full bg-gray-50 border-transparent rounded-2xl py-3.5 pl-5 pr-14 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner"
                                    @focus="activeCommentThread = thread.id"
                                    @keydown.enter="postComment(thread.id)"
                                />
                                <button
                                    @click="postComment(thread.id)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!newComment.trim()"
                                >
                                    <SendHorizontal class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="threads.last_page > 1" class="flex justify-center mt-8">
                <nav class="flex items-center space-x-2">
                    <button
                        @click="goToPage(threads.links[0]?.url)"
                        :disabled="threads.current_page === 1"
                        class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <template v-for="(link, index) in threads.links.slice(1, -1)" :key="index">
                        <button
                            v-if="link.url"
                            @click="goToPage(link.url)"
                            class="px-3 py-2 text-sm font-medium transition-colors duration-200 rounded-md"
                            :class="link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                            v-html="link.label"
                        ></button>
                        <span v-else class="px-3 py-2 text-sm font-medium text-gray-400" v-html="link.label"></span>
                    </template>

                    <button
                        @click="goToPage(threads.links[threads.links.length - 1]?.url)"
                        :disabled="threads.current_page === threads.last_page"
                        class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </nav>
            </div>

            <div class="mt-16 pb-12 text-xs text-center text-gray-400 font-medium tracking-wide">
                <span>Powered by FloraFinder Community</span>
            </div>
        </div>
    </AppLayout>
</template>
