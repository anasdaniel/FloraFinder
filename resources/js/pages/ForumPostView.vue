<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

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
      images: []
    };
  }
  return postData;
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Forum', href: '/forum' },
  { title: post.value.title || 'Post Details', href: `/forum/${post.value.id}` },
];
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
                <span>{{ new Date(post.date).toLocaleDateString() }}</span>
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
      <!-- Replies section placeholder -->
      <div v-if="post.id" class="mt-8">
        <h2 class="text-lg font-semibold mb-4">Replies ({{ post.replies }})</h2>
        <div class="text-muted-foreground text-sm">Replies UI coming soon...</div>
      </div>
    </div>
  </AppLayout>
</template>

