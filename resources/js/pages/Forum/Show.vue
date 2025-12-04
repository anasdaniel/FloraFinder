<script setup lang="ts">
import { defineProps, ref, computed, reactive } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import AppLayout from "@/layouts/AppLayout.vue";
import Heading from "@/components/Heading.vue";
import Icon from "@/components/Icon.vue";
import type { BreadcrumbItem, ForumThread } from "@/types";
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import ForumCreate from "@/pages/Forum/Create.vue";
import { Trash2, MessageCircle, SendHorizontal } from 'lucide-vue-next';

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

    showTagDropdown.value = false;
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
const selectedCategory = ref("all");
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
        commentsMap.value[threadId] = commentsMap.value[threadId] || [];
        commentsMap.value[threadId].push(createdPost);
        // keep reply panel open
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
            const json = await res.json().catch(() => null);
            console.error('Failed to post reply', json || res.status);
            return;
        }

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
            <div class="flex flex-col items-start justify-between gap-4 mb-8 sm:flex-row sm:items-center">
                <Heading
                    title="Forum"
                    description="Ask questions, share knowledge, and connect with the FloraFinder community."
                />
                <!-- open modal instead of navigating -->
                <Button type="button" variant="default" size="sm" class="gap-2" @click="showNewThread = true">
                    <span class="flex items-center gap-2">
                        <Icon name="plus" class="w-4 h-4" />
                        New Thread
                    </span>
                </Button>
            </div>

            <!-- Category Filter Buttons -->
            <div class="flex gap-3 mb-6">
                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory === 'all' ? 'bg-green-600 text-white' : 'bg-gray-200'"
                    @click="selectedCategory = 'all'"
                >
                    All
                </button>

                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory === 'general' ? 'bg-green-600 text-white' : 'bg-gray-200'"
                    @click="selectedCategory= 'general'"
                >
                    General
                </button>

                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory=== 'identification' ? 'bg-green-600 text-white' : 'bg-gray-200'"
                    @click="selectedCategory = 'identification'"
                >
                    Identification
                </button>

                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory === 'care' ? 'bg-green-600 text-white' : 'bg-gray-200'"
                    @click="selectedCategory = 'care'"
                >
                    Care
                </button>

                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory === 'offtopic' ? 'bg-green-600 text-white' : 'bg-gray-200'"
                    @click="selectedCategory = 'offtopic'"
                >
                    Off Topic
                </button>
            </div>

            <!-- Threads List -->
            <div class="space-y-6">
                <Card v-for="thread in filteredThreads" :key="thread.id" class="p-4 space-y-3">
                    <div class="flex items-center gap-2 flex-wrap">

                        <!-- Existing Tags -->
                        <span v-for="tag in thread.tags" :key="tag.id"
                              class="px-2 py-1 bg-green-200 rounded-md text-sm flex items-center gap-1"
                        >
        {{ tag.tag_name }}

                            <!-- X button only visible to owner -->
        <button
            v-if="isOwner(thread)"
            class="text-red-600 font-bold"
            @click="removeTag(thread.id, tag.id)"
        >
            ×
        </button>
    </span>

                        <!-- + Add Tag (visible to owner) -->
                        <div v-if="isOwner(thread)" class="relative">
                            <button
                                class="px-2 py-1 bg-blue-200 rounded-md text-xl"
                                @click="showTagDropdown[thread.id] = !showTagDropdown[thread.id]"
                            >
                                +
                            </button>

                            <!-- Dropdown -->
                            <div
                                v-if="showTagDropdown[thread.id]"
                                class="absolute mt-2 bg-white shadow-lg border rounded-md p-2 z-50"
                                style="min-width: 180px;"
                            >
                                <select v-model="selectedTag" class="border px-2 py-1 w-full">
                                    <option disabled value="">Select tag</option>
                                    <option v-for="t in allTags" :key="t.id" :value="t.id">
                                        {{ t.tag_name }}
                                    </option>
                                </select>

                                <button
                                    class="mt-2 w-full bg-blue-500 text-white px-2 py-1 rounded-md"
                                    @click="addTag(thread.id)"
                                >
                                    Add Tag
                                </button>
                            </div>
                        </div>

                    </div>





                    <div class="flex items-start gap-3 w-full">
                        <!-- Avatar + Title -->
                        <div class="flex items-center gap-3">
                            <Avatar class="h-10 w-10 rounded-full">
                                <AvatarImage :src="null" />
                                <AvatarFallback class="text-xs">
                                    {{ thread.title.substring(0, 2).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="font-semibold text-base">{{ thread.title }}</div>
                        </div>

                        <!-- Category + Date -->
                        <div class="ml-auto text-xs text-muted-foreground flex items-center gap-3 whitespace-nowrap">
                            <span class="capitalize">{{ thread.category }}</span>
                            <span>{{ new Date(thread.created_at).toLocaleDateString() }}</span>
                            <!-- Show trash icon only for the thread owner -->
                            <Trash2
                                v-if="thread.user_id === $page.props.auth.user.id"
                                @click="deleteThread(thread.id)"
                                class="w-4 h-4 text-red-600 hover:text-red-800 cursor-pointer"
                                title="Delete Thread"
                            />
                        </div>
                    </div>

                    <!-- Thread image -->
                    <div v-if="thread.image">
                        <img
                            :src="`/storage/${thread.image}`"
                            class="w-full rounded-md max-h-64 object-cover"
                        />
                    </div>

                    <!-- Thread content -->
                    <div v-if="thread.content" class="mt-2 text-sm">
                        {{ thread.content }}
                    </div>

                    <!-- Action icons -->
                    <div class="flex gap-2 mt-2">
                        <MessageCircle
                            @click="toggleCommentField(thread.id)"
                            class="w-4 h-4 text-blue-600 hover:text-blue-800 cursor-pointer"
                            title="Comment"
                        />
                    </div>

                    <!-- Comment input -->
                    <div v-if="activeCommentThread === thread.id" class="mt-3 flex items-center gap-2">
                        <input
                            v-model="newComment"
                            type="text"
                            placeholder="Write a comment..."
                            class="border rounded p-2 w-full text-sm"
                        />

                        <SendHorizontal
                            @click="postComment(thread.id)"
                            class="w-5 h-5 text-blue-600 hover:text-blue-800 cursor-pointer"
                            title="Post Comment"
                        />
                    </div>

                    <!-- Display comments (only when the comment panel is open) -->
                    <div v-if="activeCommentThread === thread.id" class="mt-4 border-t pt-3">
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
                                class="text-sm mb-2"
                            >
                                <div class="flex justify-between items-start w-full">
                                    <div class="flex flex-col">
                                        <strong>{{ comment.user?.name || 'Unknown' }}:</strong>
                                        <span>{{ comment.content }}</span>

                                        <!-- Timestamp + Reply link inline -->
                                        <div class="text-xs text-gray-400 flex items-center gap-3 mt-1">
                                            <span>{{ new Date(comment.created_at).toLocaleString() }}</span>

                                            <!-- Reply link beside timestamp -->
                                            <span
                                                class="text-blue-600 cursor-pointer"
                                                @click="activeReplyComment = (activeReplyComment === comment.id ? null : comment.id)"
                                            >
                Reply
            </span>
                                        </div>

                                        <!-- Reply input (only when active) -->
                                        <div v-if="activeReplyComment === comment.id" class="ml-0 mt-2 flex items-center gap-2">
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
                                        class="w-4 h-4 text-red-600 hover:text-red-800 cursor-pointer ml-3"
                                        title="Delete Comment"
                                        @click="deleteComment(comment.id, thread.id)"
                                    />
                                </div>


                                <!-- Replies for this comment -->
                                <div v-if="comment.replies && comment.replies.length" class="ml-4 mt-2 text-sm">
                                    <div
                                        v-for="reply in comment.replies"
                                        :key="reply.id"
                                        class="mb-1 flex justify-between items-start"
                                    >
                                        <div>
                                            <strong class="text-xs">{{ reply.user?.name || 'Unknown' }}:</strong>
                                            <span class="text-xs ml-1">{{ reply.content }}</span>
                                        </div>

                                        <!-- Delete reply icon -->
                                        <Trash2
                                            v-if="reply.user_id === $page.props.auth.user.id"
                                            class="w-4 h-4 text-red-600 hover:text-red-800 cursor-pointer ml-3"
                                            title="Delete Reply"
                                            @click="deleteComment(reply.id, thread.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                </Card>
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



<!--      &lt;!&ndash; Forum Categories &ndash;&gt;-->
<!--      <div class="flex flex-wrap gap-2 mb-8">-->
<!--        <button-->
<!--          v-for="cat in categories"-->
<!--          :key="cat.key"-->
<!--          @click="selectedCategory = cat.key"-->
<!--          :class="[-->
<!--            'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition',-->
<!--            selectedCategory === cat.key-->
<!--              ? 'bg-primary text-primary-foreground border-primary shadow'-->
<!--              : 'bg-background text-muted-foreground border-muted hover:bg-muted',-->
<!--          ]"-->
<!--        >-->
<!--          <Icon :name="cat.icon" class="w-4 h-4" />-->
<!--          <span>{{ cat.name }}</span>-->
<!--        </button>-->
<!--      </div>-->
<!--      <div class="space-y-6">-->
<!--        <Card-->
<!--          v-for="thread in threads.filter(-->
<!--            (t) => selectedCategory === 'general' || t.category === selectedCategory-->
<!--          )"-->
<!--          :key="thread.id"-->
<!--          class="transition-shadow hover:shadow-md"-->
<!--        >-->
<!--          <CardHeader class="flex flex-row items-center gap-4 p-6 pb-2">-->
<!--            <Avatar size="sm" shape="circle">-->
<!--              <AvatarImage-->
<!--                v-if="thread.author.avatar"-->
<!--                :src="thread.author.avatar"-->
<!--                :alt="thread.author.name"-->
<!--              />-->
<!--              <AvatarFallback>{{-->
<!--                thread.author.name-->
<!--                  .split(" ")-->
<!--                  .map((n) => n[0])-->
<!--                  .join("")-->
<!--              }}</AvatarFallback>-->
<!--            </Avatar>-->
<!--            <div class="flex-1 min-w-0">-->
<!--              <CardTitle class="text-lg font-semibold truncate">-->
<!--                <Link-->
<!--                  :href="`/forum/${thread.id}`"-->
<!--                  class="transition-colors hover:text-primary"-->
<!--                  >{{ thread.title }}</Link-->
<!--                >-->
<!--              </CardTitle>-->
<!--              <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">-->
<!--                <span>By {{ thread.author.name }}</span>-->
<!--                <span class="mx-1">•</span>-->
<!--                <span>{{ new Date(thread.date).toLocaleDateString() }}</span>-->
<!--              </div>-->
<!--            </div>-->
<!--            <div class="flex items-center gap-1 text-xs text-muted-foreground">-->
<!--              <Icon name="message-circle" class="w-4 h-4" />-->
<!--              <span>{{ thread.replies }}</span>-->
<!--            </div>-->
<!--          </CardHeader>-->
<!--          <CardContent class="pt-2 pb-4 text-sm text-muted-foreground">-->
<!--            {{ thread.excerpt }}-->
<!--          </CardContent>-->
<!--        </Card>-->
<!--      </div>-->
<!--      <div class="mt-10 text-xs text-center text-muted-foreground">-->
<!--        <span>Powered by FloraFinder Community</span>-->
<!--      </div>-->
<!--    </div>-->

<!--// // Example forum threads data-->
<!--// const threads = ref([-->
<!--//   {-->
<!--//     id: 1,-->
<!--//     title: "What is this plant I found in my backyard?",-->
<!--//     author: {-->
<!--//       id: 1,-->
<!--//       name: "Alice Green",-->
<!--//       avatar: "https://randomuser.me/api/portraits/women/44.jpg",-->
<!--//     } as User,-->
<!--//     replies: 12,-->
<!--//     date: "2025-05-04",-->
<!--//     excerpt:-->
<!--//       "I found this unusual plant and would love to know what it is. Anyone can help?",-->
<!--//     category: "identification",-->
<!--//   },-->
<!--//   {-->
<!--//     id: 2,-->
<!--//     title: "Best soil for succulents?",-->
<!--//     author: {-->
<!--//       id: 2,-->
<!--//       name: "Bob Plantman",-->
<!--//       avatar: "",-->
<!--//     } as User,-->
<!--//     replies: 7,-->
<!--//     date: "2025-05-03",-->
<!--//     excerpt:-->
<!--//       "I want to repot my succulents. What soil mix do you recommend for healthy growth?",-->
<!--//     category: "care",-->
<!--//   },-->
<!--//   {-->
<!--//     id: 3,-->
<!--//     title: "How to propagate Monstera?",-->
<!--//     author: {-->
<!--//       id: 3,-->
<!--//       name: "Cathy Leaf",-->
<!--//       avatar: "https://randomuser.me/api/portraits/women/65.jpg",-->
<!--//     } as User,-->
<!--//     replies: 4,-->
<!--//     date: "2025-05-02",-->
<!--//     excerpt: "Looking for tips and tricks to propagate Monstera deliciosa successfully.",-->
<!--//     category: "care",-->
<!--//   },-->
<!--// ]);-->
<!--//-->
<!--// const categories = ref([-->
<!--//   { key: "general", name: "General", icon: "messages-square" },-->
<!--//   { key: "identification", name: "Plant Identification", icon: "search" },-->
<!--//   { key: "care", name: "Plant Care", icon: "leaf" },-->
<!--//   { key: "offtopic", name: "Off Topic", icon: "users" },-->
<!--// ]);-->
<!--// const selectedCategory = ref("general");-->
