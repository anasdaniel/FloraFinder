<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { onClickOutside } from '@vueuse/core';
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
import { ArrowLeft, ImagePlus, X, Loader2, AlertCircle, Plus, Search } from 'lucide-vue-next';
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

const removeTag = (tagId: number) => {
    form.tag_ids = form.tag_ids.filter(id => id !== tagId);
};

// Searchable tags logic
const showTagDropdown = ref(false);
const tagSearchQuery = ref("");
const tagDropdownRef = ref(null);

onClickOutside(tagDropdownRef, () => {
    showTagDropdown.value = false;
});

const filteredTags = computed(() => {
    if (!tagSearchQuery.value) return tags.value || [];
    const query = tagSearchQuery.value.toLowerCase();
    return (tags.value || []).filter(tag => 
        tag.tag_name.toLowerCase().includes(query)
    );
});

const selectTag = (tag: any) => {
    if (!form.tag_ids.includes(tag.id)) {
        form.tag_ids.push(tag.id);
    }
    showTagDropdown.value = false;
    tagSearchQuery.value = "";
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
      <div class="w-full mx-auto" :class="props.embedded ? '' : 'max-w-xl px-4 py-8'">

        <!-- Back button (only when not embedded) -->
        <div v-if="!props.embedded" class="mb-6">
            <Link
                href="/forum"
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-400 transition-colors hover:text-gray-900 group"
            >
                <ArrowLeft class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-1" />
                Back to Forum
            </Link>
        </div>

        <div class="bg-white rounded-3xl border border-gray-200 shadow-xl shadow-gray-200/50 overflow-hidden">
          <div class="px-6 pt-8 pb-4 border-b border-gray-100">
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Create New Post</h1>
            <p class="mt-1 text-sm font-medium text-gray-500">Share your thoughts or ask a question.</p>
          </div>

          <div class="p-6">
            <form @submit.prevent="submitForm" class="space-y-6">

              <!-- Global Error Message -->
              <div
                v-if="formError"
                class="flex items-center gap-3 p-3 text-xs font-bold text-red-600 border border-red-100 rounded-xl bg-red-50"
              >
                <AlertCircle class="flex-shrink-0 w-4 h-4" />
                <span>{{ formError }}</span>
              </div>

              <!-- Title Input -->
              <div class="space-y-2">
                <Label for="title" class="ml-1 text-xs font-bold text-gray-700">Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  placeholder="What's on your mind?"
                  required
                  :disabled="form.processing"
                  class="text-sm transition-all border-gray-200 shadow-inner h-12 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20"
                />
                <p v-if="form.errors.title" class="text-[10px] text-red-500 mt-1 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3 h-3" /> {{ form.errors.title }}
                </p>
              </div>

              <!-- Category Select -->
              <div class="space-y-2">
                <Label for="category" class="ml-1 text-xs font-bold text-gray-700">Category</Label>
                <Select v-model="form.category" :disabled="form.processing">
                  <SelectTrigger class="w-full text-sm transition-all border-gray-200 shadow-inner h-12 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20">
                    <SelectValue placeholder="Select a category" />
                  </SelectTrigger>
                  <SelectContent class="border-gray-200 shadow-2xl rounded-xl">
                    <SelectItem
                      v-for="category in categories"
                      :key="category.key"
                      :value="category.key"
                      class="my-1 rounded-lg"
                    >
                      {{ category.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.category" class="text-[10px] text-red-500 mt-1 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3 h-3" /> {{ form.errors.category }}
                </p>
              </div>

              <!-- Content Textarea -->
              <div class="space-y-2">
                <Label for="content" class="ml-1 text-xs font-bold text-gray-700">Content</Label>
                <Textarea
                  id="content"
                  v-model="form.content"
                  placeholder="Share your thoughts, questions, or knowledge..."
                  rows="6"
                  required
                  :disabled="form.processing"
                  class="resize-none min-h-[150px] text-sm rounded-2xl bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all shadow-inner p-4"
                />
                <p v-if="form.errors.content" class="text-[10px] text-red-500 mt-1 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3 h-3" /> {{ form.errors.content }}
                </p>
              </div>

              <!-- Image Upload -->
              <div class="space-y-2">
                <Label class="ml-1 text-xs font-bold text-gray-700">Image (Optional)</Label>

                <div
                    v-if="!imagePreview"
                    @click="triggerFileInput"
                    class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:bg-emerald-50/30 hover:border-emerald-200 transition-all cursor-pointer group flex flex-col items-center justify-center gap-3 bg-gray-50/50"
                >
                    <input
                        type="file"
                        ref="fileInputRef"
                        class="hidden"
                        accept="image/*"
                        @change="handleImageUpload"
                    />
                    <div class="p-4 transition-transform bg-white border border-gray-200 shadow-sm rounded-2xl group-hover:scale-110">
                        <ImagePlus class="w-8 h-8 text-emerald-600" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="text-sm font-bold text-gray-900">Click to upload</div>
                        <div class="text-[10px] font-medium text-gray-400">SVG, PNG, JPG or GIF (max. 10MB)</div>
                    </div>
                </div>

                <div v-else class="relative rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 group shadow-inner">
                  <img
                    :src="imagePreview"
                    class="w-full max-h-[400px] object-contain p-3"
                    alt="Preview"
                  />
                  <div class="absolute top-3 right-3">
                      <button
                        type="button"
                        class="flex items-center justify-center w-8 h-8 text-gray-500 transition-all rounded-full shadow-lg bg-white/90 backdrop-blur-md hover:text-red-500 hover:scale-110"
                        @click="removeImage"
                      >
                        <X class="w-4 h-4" />
                      </button>
                  </div>
                </div>

                <p v-if="form.errors.image" class="text-[10px] text-red-500 mt-1 ml-1 flex items-center gap-1 font-bold">
                  <AlertCircle class="w-3 h-3" /> {{ form.errors.image }}
                </p>
              </div>

              <!-- Tags Selection -->
              <div class="space-y-3">
                  <Label for="tags" class="ml-1 text-xs font-bold text-gray-700">Tags (optional)</Label>

                  <div class="flex flex-wrap gap-2" v-if="form.tag_ids.length > 0">
                      <span
                          v-for="tagId in form.tag_ids"
                          :key="tagId"
                          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-transparent hover:bg-gray-200 transition-colors"
                      >
                          #{{ tags.find(t => t.id === tagId)?.tag_name }}
                          <button
                              type="button"
                              @click="removeTag(tagId)"
                              class="text-gray-400 transition-colors hover:text-red-500"
                          >
                              <X class="w-3 h-3" />
                          </button>
                      </span>
                  </div>

                  <div class="relative" ref="tagDropdownRef">
                      <button
                          type="button"
                          class="w-full text-left px-4 h-12 rounded-xl bg-gray-50 border border-gray-200 text-sm font-medium text-gray-500 hover:bg-white hover:border-emerald-500/20 transition-all flex items-center justify-between group"
                          @click="showTagDropdown = !showTagDropdown"
                      >
                          <span v-if="form.tag_ids.length === 0">Add tags...</span>
                          <span v-else class="text-emerald-600 font-bold">Add more tags...</span>
                          <Plus class="w-4 h-4 text-gray-400 group-hover:text-emerald-500 transition-colors" />
                      </button>

                      <div
                          v-if="showTagDropdown"
                          class="absolute left-0 right-0 mt-2 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-gray-100 rounded-2xl p-3 z-50 animate-in fade-in zoom-in-95 duration-200 origin-top"
                      >
                          <div class="relative mb-2">
                              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                              <input 
                                  v-model="tagSearchQuery"
                                  type="text" 
                                  placeholder="Search tags..." 
                                  class="w-full bg-gray-50 border border-gray-100 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500/20 transition-all"
                                  autoFocus
                              />
                          </div>
                          <div class="max-h-60 overflow-y-auto custom-scrollbar rounded-xl border border-gray-50">
                              <div v-if="filteredTags.length === 0" class="p-4 text-center text-sm text-gray-400 font-medium">
                                  No tags found
                              </div>
                              <button
                                  v-for="tag in filteredTags.filter(t => !form.tag_ids.includes(t.id))"
                                  :key="tag.id"
                                  type="button"
                                  @click="selectTag(tag)"
                                  class="w-full text-left px-4 py-3 text-sm font-bold text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors flex items-center gap-3 border-b border-gray-50 last:border-0"
                              >
                                  <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                  {{ tag.tag_name }}
                              </button>
                          </div>
                      </div>
                  </div>

                  <p v-if="form.errors.tag_ids" class="text-xs text-red-500 mt-1.5 ml-1 flex items-center gap-1 font-bold">
                      <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.tag_ids }}
                  </p>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-50">
                <button
                  v-if="props.embedded"
                  type="button"
                  class="px-6 py-2.5 text-xs font-bold text-gray-500 transition-colors hover:text-gray-900"
                  :disabled="form.processing"
                  @click="$emit('close')"
                >
                  Cancel
                </button>
                <Link
                  v-else
                  href="/forum"
                  class="px-6 py-2.5 text-xs font-bold text-gray-400 transition-colors hover:text-gray-900"
                  :disabled="form.processing"
                >
                  Cancel
                </Link>

                <button
                  type="submit"
                  class="inline-flex items-center justify-center gap-2 px-6 py-3 text-xs font-bold text-white transition-all bg-gray-900 rounded-xl hover:bg-black shadow-xl shadow-gray-900/10 hover:shadow-gray-900/20 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-[1.02] active:scale-[0.98]"
                  :disabled="form.processing"
                >
                  <Loader2
                    v-if="form.processing"
                    class="w-3.5 h-3.5 animate-spin"
                  />
                  <Plus v-else class="w-3.5 h-3.5" />
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
