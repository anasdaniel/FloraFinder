<script setup lang="ts">
import { defineProps, ref, computed, reactive } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Heading from "@/components/Heading.vue";
import Icon from "@/components/Icon.vue";
import type { BreadcrumbItem, ForumThread } from "@/types";
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import ForumCreate from "@/pages/Forum/Create.vue";
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
const showNewThread = ref(false);

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
        router.delete(`/forum/${threadId}`);
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

    router.delete(`/forum/comment/${id}`, {
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
                <!-- open modal instead of navigating -->
                <Button type="button" variant="default" size="sm" class="gap-2 bg-gray-900 hover:bg-gray-800" @click="showNewThread = true">
                    <Plus class="w-4 h-4" />
                    New Post
                </Button>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex flex-wrap gap-2 mb-8">
                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full border transition-all"
                    :class="selectedCategory === 'general'
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    @click="selectedCategory = 'general'"
                >
                    General
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full border transition-all"
                    :class="selectedCategory === 'identification'
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    @click="selectedCategory = 'identification'"
                >
                    <Search class="w-4 h-4" />
                    Plant Identification
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full border transition-all"
                    :class="selectedCategory === 'care'
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    @click="selectedCategory = 'care'"
                >
                    <Leaf class="w-4 h-4" />
                    Plant Care
                </button>

                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full border transition-all"
                    :class="selectedCategory === 'offtopic'
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    @click="selectedCategory = 'offtopic'"
                >
                    <Users class="w-4 h-4" />
                    Off Topic
                </button>
            </div>

            <!-- Threads List -->
            <div class="space-y-4">
                <div
                    v-for="thread in filteredThreads"
                    :key="thread.id"
                    class="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-sm transition-shadow"
                >
                    <!-- Main thread row -->
                    <div class="flex items-start gap-4">
                        <!-- Avatar -->
                        <Avatar class="h-10 w-10 rounded-full flex-shrink-0">
                            <AvatarImage :src="thread.user?.avatar" />
                            <AvatarFallback class="text-sm font-medium bg-gray-100 text-gray-600">
                                {{ (thread.user?.name || thread.title).substring(0, 2).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>

                        <!-- Thread content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-base">{{ thread.title }}</h3>
                                    <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                                        <span>By {{ thread.user?.name || 'Unknown' }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span>{{ new Date(thread.created_at).toLocaleDateString('en-US', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}</span>
                                    </div>
                                </div>

                                <!-- Reply count + actions -->
                                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                    <span class="text-sm font-medium text-gray-500">{{ thread.posts_count || 0 }}</span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="toggleCommentField(thread.id)"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
                                            title="Comments"
                                        >
                                            <MessageCircle class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="isOwner(thread)"
                                            @click="deleteThread(thread.id)"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                            title="Delete Thread"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Thread content preview -->
                            <p v-if="thread.content" class="mt-2 text-sm text-gray-600 line-clamp-2">
                                {{ thread.content }}
                            </p>

                            <!-- Thread image -->
                            <div v-if="thread.image" class="mt-3">
                                <img
                                    :src="`/storage/${thread.image}`"
                                    class="w-full rounded-lg max-h-48 object-cover"
                                />
                            </div>

                            <!-- Tags -->
                            <div v-if="thread.tags?.length" class="flex flex-wrap gap-2 mt-3">
                                <span
                                    v-for="tag in thread.tags"
                                    :key="tag.id"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full"
                                >
                                    {{ tag.tag_name }}
                                    <button
                                        v-if="isOwner(thread)"
                                        class="text-gray-400 hover:text-red-500 ml-1"
                                        @click="removeTag(thread.id, tag.id)"
                                    >
                                        ×
                                    </button>
                                </span>

                                <!-- Add tag button -->
                                <div v-if="isOwner(thread)" class="relative">
                                    <button
                                        class="px-2 py-0.5 text-xs font-medium text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full border border-dashed border-gray-300"
                                        @click="showTagDropdown[thread.id] = !showTagDropdown[thread.id]"
                                    >
                                        + Add
                                    </button>
                                    <div
                                        v-if="showTagDropdown[thread.id]"
                                        class="absolute left-0 mt-2 bg-white shadow-lg border rounded-lg p-3 z-50"
                                        style="min-width: 180px;"
                                    >
                                        <select v-model="selectedTag" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-sm">
                                            <option disabled value="">Select tag</option>
                                            <option v-for="t in allTags" :key="t.id" :value="t.id">
                                                {{ t.tag_name }}
                                            </option>
                                        </select>
                                        <button
                                            class="mt-2 w-full bg-gray-900 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-800"
                                            @click="addTag(thread.id)"
                                        >
                                            Add Tag
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Comment input -->
                            <div class="mt-4 relative">
                                <input
                                    v-model="newComment"
                                    type="text"
                                    placeholder="Write a comment..."
                                    class="w-full border border-gray-200 rounded-lg py-2 pl-3 pr-10 text-sm focus:outline-none focus:border-gray-400 focus:ring-0"
                                    @focus="activeCommentThread = thread.id"
                                />
                                <button
                                    @click="postComment(thread.id)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-blue-600 hover:text-blue-700"
                                >
                                    <SendHorizontal class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Display comments -->
                            <div v-if="activeCommentThread === thread.id" class="mt-4 space-y-4">
                                <div v-if="commentsLoading" class="text-sm text-muted-foreground">Loading comments...</div>

                                <div v-else>
                                    <div
                                        v-if="(commentsMap[thread.id] || []).length === 0"
                                        class="text-sm text-muted-foreground"
                                    >
                                        No comments yet.
                                    </div>

                                    <!-- Comments list -->
                                    <div
                                        v-for="comment in commentsMap[thread.id] || []"
                                        :key="comment.id"
                                        class="text-sm group"
                                    >
                                        <div class="flex justify-between items-start w-full">
                                            <div class="flex flex-col w-full">
                                                <div class="font-semibold text-gray-900">{{ comment.user?.name || 'Unknown' }}:</div>
                                                <div class="text-gray-700 mt-0.5">{{ comment.content }}</div>

                                                <!-- Timestamp + Reply link inline -->
                                                <div class="text-xs text-gray-400 flex items-center gap-3 mt-1">
                                                    <span>{{ new Date(comment.created_at).toLocaleString() }}</span>

                                                    <!-- Reply link beside timestamp -->
                                                    <span
                                                        class="text-blue-600 cursor-pointer hover:underline"
                                                        @click="activeReplyComment = (activeReplyComment === comment.id ? null : comment.id)"
                                                    >
                                                        Reply
                                                    </span>
                                                </div>

                                                <!-- Reply input (only when active) -->
                                                <div v-if="activeReplyComment === comment.id" class="mt-2 flex items-center gap-2">
                                                    <input
                                                        v-model="newReplies[comment.id]"
                                                        type="text"
                                                        placeholder="Write a reply..."
                                                        class="border rounded p-2 w-full text-xs"
                                                    />

                                                    <SendHorizontal
                                                        class="w-4 h-4 text-blue-600 hover:text-blue-800 cursor-pointer"
                                                        title="Post Reply"
                                                        @click="postReply(comment.id, thread.id)"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Delete comment icon -->
                                            <Trash2
                                                v-if="comment.user_id === $page.props.auth.user.id"
                                                class="w-4 h-4 text-red-400 hover:text-red-600 cursor-pointer ml-3 opacity-0 group-hover:opacity-100 transition-opacity"
                                                title="Delete Comment"
                                                @click="deleteComment(comment.id, thread.id)"
                                            />
                                        </div>


                                        <!-- Replies for this comment -->
                                        <div v-if="comment.replies && comment.replies.length" class="ml-4 mt-2 text-sm border-l-2 border-gray-100 pl-3">
                                            <div
                                                v-for="reply in comment.replies"
                                                :key="reply.id"
                                                class="mb-2 flex justify-between items-start group/reply"
                                            >
                                                <div>
                                                    <div class="text-xs font-semibold text-gray-900">{{ reply.user?.name || 'Unknown' }}:</div>
                                                    <div class="text-xs text-gray-600">{{ reply.content }}</div>
                                                </div>

                                                <!-- Delete reply icon -->
                                                <Trash2
                                                    v-if="reply.user_id === $page.props.auth.user.id"
                                                    class="w-3 h-3 text-red-400 hover:text-red-600 cursor-pointer ml-3 opacity-0 group-hover/reply:opacity-100 transition-opacity"
                                                    title="Delete Reply"
                                                    @click="deleteComment(reply.id, thread.id)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: embedded ForumCreate -->
            <div v-if="showNewThread" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg w-full max-w-2xl overflow-auto">
                    <div class="flex justify-end p-2">
                        <button class="text-sm px-3 py-1" @click="showNewThread = false">Close</button>
                    </div>
                    <div class="p-4">
                        <ForumCreate embedded @close="showNewThread = false" />
                    </div>
                </div>
            </div>

            <div class="mt-10 text-xs text-center text-muted-foreground">
                <span>Powered by FloraFinder Community</span>
            </div>
        </div>
    </AppLayout>
</template>
