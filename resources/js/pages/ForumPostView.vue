<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Reply {
  id: number;
  content: string;
  date: string;
  author: {
    name: string;
    avatar: string | null;
  };
  parent?: {
    id: number;
    content: string;
    author: {
      name: string;
    };
  } | null;
}

// Define the ForumPost type
interface ForumPost {
  id: number;
  title: string;
  content: string;
  author: {
    name: string;
    avatar: string | null;
  };
  date: string;
  category: string;
  replies: number;
  images?: string[];
  posts?: Reply[];
}

// Get post data from Inertia page props
const page = usePage();
const post = computed<ForumPost>(() => {
  const postData = page.props.post as ForumPost | undefined;
  if (!postData) {
    console.error('Post data not found in page props');
    // Provide default values if post data is missing
    return {
      id: 0,
      title: 'Post not found',
      content: 'The requested post could not be loaded.',
      author: { name: 'Unknown', avatar: null },
      date: new Date().toISOString(),
      category: 'general',
      replies: 0,
      images: [],
      posts: []
    };
  }
  return postData;
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Forum', href: '/forum' },
  { title: post.value.title || 'Post Details', href: `/forum/${post.value.id}` },
];

const form = useForm({
  content: '',
  parent_id: null as number | null,
});

const replyingTo = ref<Reply | null>(null);

const setReplyTo = (reply: Reply) => {
  replyingTo.value = reply;
  form.parent_id = reply.id;
  // Scroll to form
  const formElement = document.getElementById('reply-form');
  if (formElement) {
    formElement.scrollIntoView({ behavior: 'smooth' });
  }
};

const cancelReply = () => {
  replyingTo.value = null;
  form.parent_id = null;
};

const submitReply = () => {
  form.post(`/forum/${post.value.id}/reply`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      cancelReply();
    },
  });
};
</script>

<template>
  <Head :title="post.title || 'Forum Post'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full max-w-2xl px-4 py-8 mx-auto">
      <div class="mb-8">
        <Button as-child variant="outline" size="sm" class="gap-2 mb-4">
          <Link href="/forum">
            <Icon name="arrow-left" class="w-4 h-4" />
            Back to Forum
          </Link>
        </Button>
        <Card v-if="post.id">
          <CardHeader class="flex flex-row items-center gap-4 p-6 pb-2">
            <Avatar size="sm" shape="circle">
              <AvatarImage v-if="post.author && post.author.avatar" :src="post.author.avatar" :alt="post.author.name" />
              <AvatarFallback>{{ post.author && post.author.name ? post.author.name.split(' ').map((n: string) => n[0]).join('') : '?' }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <CardTitle class="text-xl font-semibold">{{ post.title }}</CardTitle>
              <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                <span>By {{ post.author.name }}</span>
                <span class="mx-1">•</span>
                <span>{{ new Date(post.date).toLocaleString() }}</span>
                <span class="mx-1">•</span>
                <span class="capitalize">{{ post.category.replace('-', ' ') }}</span>
              </div>
            </div>
            <div class="flex items-center gap-1 text-xs text-muted-foreground">
              <Icon name="message-circle" class="w-4 h-4" />
              <span>{{ post.replies }}</span>
            </div>
          </CardHeader>
          <CardContent class="pt-2 pb-4 text-base">
            <div class="whitespace-pre-line mb-4">{{ post.content }}</div>
            <div v-if="post.images && post.images.length" class="flex flex-wrap gap-4">
              <img v-for="img in post.images" :key="img" :src="img" class="rounded-lg max-h-48 border object-cover" />
            </div>
          </CardContent>
        </Card>
        <div v-else class="bg-yellow-50 border-l-4 border-yellow-400 p-4 dark:bg-yellow-900/20 dark:border-yellow-600">
          <div class="flex">
            <div class="flex-shrink-0">
              <Icon name="alert-triangle" class="w-5 h-5 text-yellow-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Post Not Found</h3>
              <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                <p>The forum post you're looking for could not be found. It may have been removed or you may have followed an invalid link.</p>
              </div>
              <div class="mt-4">
                <Button as-child variant="outline" size="sm">
                  <Link href="/forum">Return to Forum</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Replies section -->
      <div v-if="post.id" class="mt-8 space-y-6">
        <h2 class="text-lg font-semibold">Replies ({{ post.replies }})</h2>
        
        <!-- Reply Form -->
        <Card id="reply-form">
            <CardContent class="p-4">
                <div v-if="replyingTo" class="mb-4 p-3 bg-muted rounded-md flex justify-between items-start">
                    <div class="text-sm">
                        <span class="font-semibold">Replying to {{ replyingTo.author.name }}:</span>
                        <p class="text-muted-foreground line-clamp-2 mt-1">{{ replyingTo.content }}</p>
                    </div>
                    <Button variant="ghost" size="sm" @click="cancelReply" class="h-6 w-6 p-0">
                        <Icon name="x" class="w-4 h-4" />
                    </Button>
                </div>
                <form @submit.prevent="submitReply" class="space-y-4">
                    <Textarea v-model="form.content" :placeholder="replyingTo ? 'Write a reply to ' + replyingTo.author.name + '...' : 'Write a reply...'" required />
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            {{ replyingTo ? 'Post Reply' : 'Post Comment' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Replies List -->
        <div class="space-y-4">
            <Card v-for="reply in post.posts" :key="reply.id">
                <CardHeader class="flex flex-row items-start gap-4 p-4 pb-2">
                    <Avatar size="sm" shape="circle" class="mt-1">
                        <AvatarImage v-if="reply.author.avatar" :src="reply.author.avatar" :alt="reply.author.name" />
                        <AvatarFallback>{{ reply.author.name.split(' ').map(n => n[0]).join('') }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-sm font-semibold">{{ reply.author.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ new Date(reply.date).toLocaleString() }}</div>
                            </div>
                            <Button variant="ghost" size="sm" class="h-8 text-xs" @click="setReplyTo(reply)">
                                <Icon name="reply" class="w-3 h-3 mr-1" /> Reply
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-4 pt-0 text-sm">
                    <div v-if="reply.parent" class="mb-3 p-3 bg-muted/50 rounded-md border-l-2 border-primary/50 text-xs">
                        <div class="font-medium mb-1">Replying to {{ reply.parent.author.name }}</div>
                        <div class="text-muted-foreground line-clamp-2">{{ reply.parent.content }}</div>
                    </div>
                    <div class="whitespace-pre-line">{{ reply.content }}</div>
                </CardContent>
            </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

