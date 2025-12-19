<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { ArrowLeft, ImagePlus, X, Loader2, AlertCircle, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import type { BreadcrumbItem } from "@/types";

const { toast } = useToast();

const tags = ref<any[]>([]);

// Fetch tags on mount
onMounted(async () => {
    try {
        const res = await fetch("/forum/tags", {
            headers: { "Accept": "application/json" },
            credentials: "same-origin" // important for auth cookies
        });
        const json = await res.json();
        tags.value = json.tags || [];
    } catch (e) {
        console.error("Failed to fetch tags", e);
    }
});


const breadcrumbs: BreadcrumbItem[] = [
  { title: "Forum", href: "/forum" },
  { title: "New Thread", href: "/forum/new" },
];

// Categories for the forum
const categories = ref([
  { key: "general", name: "General Discussion" },
  { key: "identification", name: "Plant Identification" },
  { key: "care", name: "Plant Care" },
  { key: "offtopic", name: "Off Topic" },
]);

// Make this component usable embedded (modal) or as a standalone page
const props = defineProps({
  embedded: { type: Boolean, default: false },
});
const emit = defineEmits(["close", "created"]);

// Preview of uploaded image
const imagePreview = ref<string | null>(null);

// Form with Inertia
const form = useForm({
  title: "",
  content: "",
  category: "general",
  image: null as File | null,
    tag_ids: [] as number[],
});

// Form error and success handling
const isSubmitting = ref(false);
const formError = ref<string | null>(null);

// Handle image upload
const handleImageUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    form.image = input.files[0];

    // Create preview URL
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(input.files[0]);
  }
};

// Reference to the file input element
const fileInputRef = ref<HTMLInputElement | null>(null);

// Function to trigger file input click
const triggerFileInput = () => {
  if (fileInputRef.value) {
    fileInputRef.value.click();
  }
};

// Remove uploaded image
const removeImage = () => {
  form.image = null;
  imagePreview.value = null;
  // Reset the file input value
  if (fileInputRef.value) {
    fileInputRef.value.value = "";
  }
};

// Tag management
const addTag = (value: string) => {
    const tagId = parseInt(value);
    if (!form.tag_ids.includes(tagId)) {
        form.tag_ids.push(tagId);
    }
};

const removeTag = (tagId: number) => {
    form.tag_ids = form.tag_ids.filter(id => id !== tagId);
};

// Submit the form
const submitForm = () => {
  // Reset previous errors
  formError.value = null;
  isSubmitting.value = true;

  // Show a toast notification for the user
  toast({
    title: "Submitting...",
    description: "Creating your post, please wait.",
    variant: "default",
  });

  // Use the Inertia form helper
  form.post("/forum", {
    preserveScroll: true,
    forceFormData: true, // Force FormData for file uploads
    onSuccess: () => {
      // Reset form after successful submission
      form.reset();
      imagePreview.value = null;

      // If embedded, notify parent to close / refresh
      if (props.embedded) {
        emit("created");
        emit("close");
      }

      // Show success toast (though the redirect will happen before user sees this)
      toast({
        title: "Success!",
        description: "Your post has been created.",
        variant: "success",
      });
    },
    onError: (errors) => {
      console.error("Form submission errors:", errors);
      formError.value =
        "There was a problem creating your post. Please check the form for errors.";

      // Show error toast
      toast({
        title: "Error",
        description: "There was a problem creating your post.",
        variant: "destructive",
      });
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};
</script>

<template>
  <div>
    <Head v-if="!props.embedded" title="Create New Post" />

    <component :is="props.embedded ? 'div' : AppLayout" v-bind="!props.embedded ? { breadcrumbs } : {}">
      <div class="w-full mx-auto" :class="props.embedded ? '' : 'max-w-2xl px-4 py-12'">

        <!-- Back button (only when not embedded) -->
        <div v-if="!props.embedded" class="mb-8">
            <Link
                href="/forum"
                class="inline-flex items-center gap-2 text-sm font-bold text-gray-400 hover:text-gray-900 transition-colors group"
            >
                <ArrowLeft class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
                Back to Forum
            </Link>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-gray-200 shadow-xl shadow-gray-200/50 overflow-hidden">
          <div class="px-8 pt-10 pb-6 border-b border-gray-100">
            <h1 class="text-3xl font-extrabold text-[#0f172a] tracking-tight">Create New Post</h1>
            <p class="mt-2 text-gray-500 font-medium">Share your thoughts or ask a question to the community.</p>
          </div>

          <div class="p-8">
            <form @submit.prevent="submitForm" class="space-y-8">

              <!-- Global Error Message -->
              <div
                v-if="formError"
                class="p-4 rounded-2xl bg-red-50 text-red-600 text-sm font-bold flex items-center gap-3 border border-red-100"
              >
                <AlertCircle class="w-5 h-5 flex-shrink-0" />
                <span>{{ formError }}</span>
              </div>

              <!-- Title Input -->
              <div class="space-y-3">
                <Label for="title" class="text-sm font-bold text-gray-700 ml-1">Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  placeholder="What's on your mind?"
                  required
                  :disabled="form.processing"
                  class="h-14 text-base rounded-2xl bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner"
                />
                <p v-if="form.errors.title" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.title }}
                </p>
              </div>

              <!-- Category Select -->
              <div class="space-y-3">
                <Label for="category" class="text-sm font-bold text-gray-700 ml-1">Category</Label>
                <Select v-model="form.category" :disabled="form.processing">
                  <SelectTrigger class="w-full h-14 rounded-2xl bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner text-base">
                    <SelectValue placeholder="Select a category" />
                  </SelectTrigger>
                  <SelectContent class="rounded-2xl border-gray-200 shadow-2xl">
                    <SelectItem
                      v-for="category in categories"
                      :key="category.key"
                      :value="category.key"
                      class="rounded-xl my-1"
                    >
                      {{ category.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.category" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.category }}
                </p>
              </div>

              <!-- Content Textarea -->
              <div class="space-y-3">
                <Label for="content" class="text-sm font-bold text-gray-700 ml-1">Content</Label>
                <Textarea
                  id="content"
                  v-model="form.content"
                  placeholder="Share your thoughts, questions, or knowledge..."
                  rows="8"
                  required
                  :disabled="form.processing"
                  class="resize-none min-h-[200px] text-base rounded-[2rem] bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner p-6"
                />
                <p v-if="form.errors.content" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.content }}
                </p>
              </div>

              <!-- Image Upload -->
              <div class="space-y-3">
                <Label class="text-sm font-bold text-gray-700 ml-1">Image (Optional)</Label>

                <div
                    v-if="!imagePreview"
                    @click="triggerFileInput"
                    class="border-2 border-dashed border-gray-300 rounded-[2rem] p-12 text-center hover:bg-emerald-50/30 hover:border-emerald-200 transition-all cursor-pointer group flex flex-col items-center justify-center gap-4 bg-gray-50/50"
                >
                    <input
                        type="file"
                        ref="fileInputRef"
                        class="hidden"
                        accept="image/*"
                        @change="handleImageUpload"
                    />
                    <div class="p-5 rounded-3xl bg-white group-hover:scale-110 transition-transform shadow-sm border border-gray-200">
                        <ImagePlus class="w-10 h-10 text-emerald-600" />
                    </div>
                    <div class="space-y-1">
                        <div class="text-base font-bold text-gray-900">Click to upload or drag and drop</div>
                        <div class="text-xs text-gray-400 font-medium">SVG, PNG, JPG or GIF (max. 10MB)</div>
                    </div>
                </div>

                <div v-else class="relative rounded-[2rem] overflow-hidden border border-gray-200 bg-gray-50 group shadow-inner">
                  <img
                    :src="imagePreview"
                    class="w-full max-h-[500px] object-contain p-4"
                    alt="Preview"
                  />
                  <div class="absolute top-4 right-4">
                      <button
                        type="button"
                        class="h-10 w-10 rounded-full bg-white/90 backdrop-blur-md shadow-lg flex items-center justify-center text-gray-500 hover:text-red-500 hover:scale-110 transition-all"
                        @click="removeImage"
                      >
                        <X class="w-5 h-5" />
                      </button>
                  </div>
                </div>

                <p v-if="form.errors.image" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.image }}
                </p>
              </div>

              <!-- Tags Selection -->
              <div class="space-y-4">
                  <Label for="tags" class="text-sm font-bold text-gray-700 ml-1">Tags (optional)</Label>

                  <div class="flex flex-wrap gap-2.5" v-if="form.tag_ids.length > 0">
                      <span
                          v-for="tagId in form.tag_ids"
                          :key="tagId"
                          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-transparent hover:bg-gray-200 transition-colors"
                      >
                          #{{ tags.find(t => t.id === tagId)?.tag_name }}
                          <button
                              type="button"
                              @click="removeTag(tagId)"
                              class="text-gray-400 hover:text-red-500 transition-colors"
                          >
                              <X class="w-3.5 h-3.5" />
                          </button>
                      </span>
                  </div>

                  <Select :model-value="''" @update:model-value="addTag">
                      <SelectTrigger class="w-full h-14 rounded-2xl bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner text-base">
                          <SelectValue placeholder="Add tags to your post..." />
                      </SelectTrigger>
                      <SelectContent class="rounded-2xl border-gray-200 shadow-2xl">
                          <SelectItem
                              v-for="tag in tags.filter(t => !form.tag_ids.includes(t.id))"
                              :key="tag.id"
                              :value="String(tag.id)"
                              class="rounded-xl my-1"
                          >
                              {{ tag.tag_name }}
                          </SelectItem>
                      </SelectContent>
                  </Select>

                  <p v-if="form.errors.tag_ids" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                      <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.tag_ids }}
                  </p>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-4 pt-8 border-t border-gray-50">
                <button
                  v-if="props.embedded"
                  type="button"
                  class="px-8 py-3 text-sm font-bold text-gray-500 hover:text-gray-900 transition-colors"
                  :disabled="form.processing"
                  @click="$emit('close')"
                >
                  Cancel
                </button>
                <Link
                  v-else
                  href="/forum"
                  class="px-8 py-3 text-sm font-bold text-gray-400 hover:text-gray-900 transition-colors"
                  :disabled="form.processing"
                >
                  Cancel
                </Link>

                <button
                  type="submit"
                  class="inline-flex items-center justify-center gap-2 px-8 py-3.5 text-sm font-bold text-white transition-all bg-gray-900 rounded-2xl hover:bg-black shadow-xl shadow-gray-900/10 hover:shadow-gray-900/20 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-[1.02] active:scale-[0.98]"
                  :disabled="form.processing"
                >
                  <Loader2
                    v-if="form.processing"
                    class="w-4 h-4 animate-spin"
                  />
                  <Plus v-else class="w-4 h-4" />
                  <span>{{ form.processing ? "Creating..." : "Create Post" }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </component>
  </div>
</template>
