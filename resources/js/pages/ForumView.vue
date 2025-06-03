<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Heading from "@/components/Heading.vue";
import Icon from "@/components/Icon.vue";
import { useToast } from "@/composables/useToast";
import type { BreadcrumbItem, User } from "@/types";

const { toast } = useToast();
const page = usePage();

// Define flash message types
interface FlashMessages {
  success?: string;
  error?: string;
}

// Access flash messages from page props
const flash = page.props.flash as FlashMessages;

const breadcrumbs: BreadcrumbItem[] = [{ title: "Forum", href: "/forum" }];

// Check for flash messages on component mount
onMounted(() => {
  // Show success message if it exists in the flash session
  if (flash?.success) {
    toast({
      title: "Success",
      description: flash.success,
      variant: "success",
    });
  }
});

// Example forum threads data
const threads = ref([
  {
    id: 1,
    title: "What is this plant I found in my backyard?",
    author: {
      id: 1,
      name: "Alice Green",
      avatar: "https://randomuser.me/api/portraits/women/44.jpg",
    } as User,
    replies: 12,
    date: "2025-05-04",
    excerpt:
      "I found this unusual plant and would love to know what it is. Anyone can help?",
    category: "identification",
  },
  {
    id: 2,
    title: "Best soil for succulents?",
    author: {
      id: 2,
      name: "Bob Plantman",
      avatar: "",
    } as User,
    replies: 7,
    date: "2025-05-03",
    excerpt:
      "I want to repot my succulents. What soil mix do you recommend for healthy growth?",
    category: "care",
  },
  {
    id: 3,
    title: "How to propagate Monstera?",
    author: {
      id: 3,
      name: "Cathy Leaf",
      avatar: "https://randomuser.me/api/portraits/women/65.jpg",
    } as User,
    replies: 4,
    date: "2025-05-02",
    excerpt: "Looking for tips and tricks to propagate Monstera deliciosa successfully.",
    category: "care",
  },
]);

const categories = ref([
  { key: "general", name: "General", icon: "messages-square" },
  { key: "identification", name: "Plant Identification", icon: "search" },
  { key: "care", name: "Plant Care", icon: "leaf" },
  { key: "offtopic", name: "Off Topic", icon: "users" },
]);
const selectedCategory = ref("general");
</script>

<template>
  <Head title="Forum" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full max-w-3xl px-4 py-8 mx-auto">
      <div
        class="flex flex-col items-start justify-between gap-4 mb-8 sm:flex-row sm:items-center"
      >
        <Heading
          title="Forum"
          description="Ask questions, share knowledge, and connect with the FloraFinder community."
        />
        <Button as-child variant="default" size="sm" class="gap-2">
          <Link href="/forum/new">
            <Icon name="plus" class="w-4 h-4" />
            New Post
          </Link>
        </Button>
      </div>
      <!-- Forum Categories -->
      <div class="flex flex-wrap gap-2 mb-8">
        <button
          v-for="cat in categories"
          :key="cat.key"
          @click="selectedCategory = cat.key"
          :class="[
            'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition',
            selectedCategory === cat.key
              ? 'bg-primary text-primary-foreground border-primary shadow'
              : 'bg-background text-muted-foreground border-muted hover:bg-muted',
          ]"
        >
          <Icon :name="cat.icon" class="w-4 h-4" />
          <span>{{ cat.name }}</span>
        </button>
      </div>
      <div class="space-y-6">
        <Card
          v-for="thread in threads.filter(
            (t) => selectedCategory === 'general' || t.category === selectedCategory
          )"
          :key="thread.id"
          class="transition-shadow hover:shadow-md"
        >
          <CardHeader class="flex flex-row items-center gap-4 p-6 pb-2">
            <Avatar size="sm" shape="circle">
              <AvatarImage
                v-if="thread.author.avatar"
                :src="thread.author.avatar"
                :alt="thread.author.name"
              />
              <AvatarFallback>{{
                thread.author.name
                  .split(" ")
                  .map((n) => n[0])
                  .join("")
              }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <CardTitle class="text-lg font-semibold truncate">
                <Link
                  :href="`/forum/${thread.id}`"
                  class="transition-colors hover:text-primary"
                  >{{ thread.title }}</Link
                >
              </CardTitle>
              <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                <span>By {{ thread.author.name }}</span>
                <span class="mx-1">•</span>
                <span>{{ new Date(thread.date).toLocaleDateString() }}</span>
              </div>
            </div>
            <div class="flex items-center gap-1 text-xs text-muted-foreground">
              <Icon name="message-circle" class="w-4 h-4" />
              <span>{{ thread.replies }}</span>
            </div>
          </CardHeader>
          <CardContent class="pt-2 pb-4 text-sm text-muted-foreground">
            {{ thread.excerpt }}
          </CardContent>
        </Card>
      </div>
      <div class="mt-10 text-xs text-center text-muted-foreground">
        <span>Powered by FloraFinder Community</span>
      </div>
    </div>
  </AppLayout>
</template>
