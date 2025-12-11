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
import Icon from "@/components/Icon.vue";
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
      <div class="w-full mx-auto" :class="props.embedded ? '' : 'max-w-2xl px-4 py-8'">

        <!-- Back button (only when not embedded) -->
        <Button
          v-if="!props.embedded"
          as-child
          variant="ghost"
          size="sm"
          class="gap-2 mb-4 hover:bg-transparent hover:underline p-0 h-auto"
        >
          <Link href="/forum">
            <Icon name="arrow-left" class="w-4 h-4" />
            Back to Forum
          </Link>
        </Button>

        <Card class="rounded-xl border bg-card text-card-foreground shadow-sm">
          <CardHeader class="pb-6">
            <CardTitle class="text-2xl font-bold tracking-tight">Create New Post</CardTitle>
          </CardHeader>

          <CardContent>
            <form @submit.prevent="submitForm" class="space-y-8">

              <!-- Global Error Message -->
              <div
                v-if="formError"
                class="p-4 rounded-md bg-destructive/10 text-destructive text-sm font-medium flex items-center gap-2"
              >
                <Icon name="alert-circle" class="w-5 h-5 flex-shrink-0" />
                <span>{{ formError }}</span>
              </div>

              <!-- Title Input -->
              <div class="space-y-2">
                <Label for="title" class="text-base font-semibold">Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  placeholder="Enter a descriptive title"
                  required
                  :disabled="form.processing"
                  class="h-12 text-base"
                />
                <p v-if="form.errors.title" class="text-sm text-destructive mt-1 flex items-center gap-1">
                  <Icon name="alert-circle" class="w-4 h-4" /> {{ form.errors.title }}
                </p>
              </div>

              <!-- Category Select -->
              <div class="space-y-2">
                <Label for="category" class="text-base font-semibold">Category</Label>
                <Select v-model="form.category" :disabled="form.processing">
                  <SelectTrigger class="w-full h-12">
                    <SelectValue placeholder="Select a category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="category in categories"
                      :key="category.key"
                      :value="category.key"
                    >
                      {{ category.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.category" class="text-sm text-destructive mt-1 flex items-center gap-1">
                  <Icon name="alert-circle" class="w-4 h-4" /> {{ form.errors.category }}
                </p>
              </div>

              <!-- Content Textarea -->
              <div class="space-y-2">
                <Label for="content" class="text-base font-semibold">Content</Label>
                <Textarea
                  id="content"
                  v-model="form.content"
                  placeholder="Share your thoughts, questions, or knowledge..."
                  rows="8"
                  required
                  :disabled="form.processing"
                  class="resize-y min-h-[200px] text-base"
                />
                <p v-if="form.errors.content" class="text-sm text-destructive mt-1 flex items-center gap-1">
                  <Icon name="alert-circle" class="w-4 h-4" /> {{ form.errors.content }}
                </p>
              </div>

              <!-- Image Upload -->
              <div class="space-y-2">
                <Label class="text-base font-semibold">Image (Optional)</Label>

                <div
                    v-if="!imagePreview"
                    @click="triggerFileInput"
                    class="border-2 border-dashed border-muted-foreground/25 rounded-xl p-10 text-center hover:bg-muted/50 transition-colors cursor-pointer group flex flex-col items-center justify-center gap-3"
                >
                    <input
                        type="file"
                        ref="fileInputRef"
                        class="hidden"
                        accept="image/*"
                        @change="handleImageUpload"
                    />
                    <div class="p-4 rounded-full bg-muted group-hover:bg-background transition-colors shadow-sm">
                        <Icon name="image-plus" class="w-8 h-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-1">
                        <div class="text-sm font-medium text-foreground">Click to upload or drag and drop</div>
                        <div class="text-xs text-muted-foreground">SVG, PNG, JPG or GIF (max. 10MB)</div>
                    </div>
                </div>

                <div v-else class="relative rounded-xl overflow-hidden border bg-muted group">
                  <img
                    :src="imagePreview"
                    class="w-full max-h-[400px] object-contain bg-black/5"
                    alt="Preview"
                  />
                  <div class="absolute top-2 right-2">
                      <Button
                        type="button"
                        variant="secondary"
                        size="icon"
                        class="h-9 w-9 rounded-full shadow-sm hover:bg-destructive hover:text-destructive-foreground transition-colors"
                        @click="removeImage"
                      >
                        <Icon name="x" class="w-5 h-5" />
                      </Button>
                  </div>
                </div>

                <p v-if="form.errors.image" class="text-sm text-destructive mt-1 flex items-center gap-1">
                  <Icon name="alert-circle" class="w-4 h-4" /> {{ form.errors.image }}
                </p>
              </div>

              <!-- Tags Selection -->
              <div class="space-y-3">
                  <Label for="tags" class="text-base font-semibold">Tags (optional)</Label>

                  <div class="flex flex-wrap gap-2" v-if="form.tag_ids.length > 0">
                      <span
                          v-for="tagId in form.tag_ids"
                          :key="tagId"
                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary"
                      >
                          {{ tags.find(t => t.id === tagId)?.tag_name }}
                          <button
                              type="button"
                              @click="removeTag(tagId)"
                              class="text-primary/60 hover:text-primary focus:outline-none"
                          >
                              <Icon name="x" class="w-3.5 h-3.5" />
                          </button>
                      </span>
                  </div>

                  <Select :model-value="''" @update:model-value="addTag">
                      <SelectTrigger class="w-full h-12">
                          <SelectValue placeholder="Select tags to add..." />
                      </SelectTrigger>
                      <SelectContent>
                          <SelectItem
                              v-for="tag in tags.filter(t => !form.tag_ids.includes(t.id))"
                              :key="tag.id"
                              :value="String(tag.id)"
                          >
                              {{ tag.tag_name }}
                          </SelectItem>
                      </SelectContent>
                  </Select>

                  <p v-if="form.errors.tag_ids" class="text-sm text-destructive mt-1 flex items-center gap-1">
                      <Icon name="alert-circle" class="w-4 h-4" /> {{ form.errors.tag_ids }}
                  </p>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-4 border-t">
                <Button
                  v-if="props.embedded"
                  type="button"
                  variant="outline"
                  size="lg"
                  class="h-11 px-6 text-base font-medium"
                  :disabled="form.processing"
                  @click="$emit('close')"
                >
                  Cancel
                </Button>
                <Button
                  v-else
                  as-child
                  type="button"
                  variant="outline"
                  size="lg"
                  class="h-11 px-6 text-base font-medium"
                  :disabled="form.processing"
                >
                  <Link href="/forum">Cancel</Link>
                </Button>

                <Button
                  type="submit"
                  size="lg"
                  class="h-11 px-8 text-base font-medium min-w-[140px]"
                  :disabled="form.processing"
                >
                  <Icon
                    v-if="form.processing"
                    name="loader-2"
                    class="w-5 h-5 mr-2 animate-spin"
                  />
                  <span>{{ form.processing ? "Creating..." : "Create Post" }}</span>
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </component>
  </div>
</template>
