<script setup lang="ts">
import { ref } from "vue";
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
import { AlertCircle } from "lucide-vue-next";
import Icon from "@/components/Icon.vue";
import { useToast } from "@/composables/useToast";
import type { BreadcrumbItem } from "@/types";
import { onMounted } from "vue";

const { toast } = useToast();

const tags = ref([]);

// Fetch tags on mount
onMounted(async () => {
    const res = await fetch("/forum/tags", {
        headers: { "Accept": "application/json" },
        credentials: "same-origin" // important for auth cookies
    });
    const json = await res.json();
    tags.value = json.tags || [];
});


const breadcrumbs: BreadcrumbItem[] = [
  { title: "Forum", href: "/forum" },
  { title: "New Thread", href: "/forum/create" },
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
  <!-- If not embedded, render page head + layout. If embedded, only render the form container. -->
  <div>
    <Head v-if="!props.embedded" title="Create New Post" />

    <component :is="props.embedded ? 'div' : AppLayout" v-bind="!props.embedded ? { breadcrumbs } : {}">
      <div class="w-full max-w-2xl px-4 py-8 mx-auto">
        <Button as-child variant="outline" size="sm" class="gap-2 mb-4" v-if="!props.embedded">
          <Link href="/forum">
            <Icon name="arrow-left" class="w-4 h-4" />
            Back to Forum
          </Link>
        </Button>

        <Card class="mb-8">
          <CardHeader>
            <CardTitle class="text-xl font-semibold">Create New Post</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submitForm" class="space-y-6">
              <!-- General form error -->
              <div
                v-if="formError"
                class="p-4 mb-4 text-sm border rounded-md bg-red-50 text-red-600 border-red-200"
              >
                <div class="flex items-start">
                  <Icon name="alert-circle" class="w-5 h-5 mr-2" />
                  <div>{{ formError }}</div>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  placeholder="Enter a descriptive title"
                  required
                  :disabled="form.processing"
                />
                <p
                  v-if="form.errors.title"
                  class="text-sm text-red-600 flex items-center gap-1 mt-1"
                >
                  <AlertCircle class="w-4 h-4" /> {{ form.errors.title }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="category">Category</Label>
                <Select v-model="form.category" :disabled="form.processing">
                  <SelectTrigger class="w-full">
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
                <p
                  v-if="form.errors.category"
                  class="text-sm text-red-600 flex items-center gap-1 mt-1"
                >
                  <AlertCircle class="w-4 h-4" /> {{ form.errors.category }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="content">Content</Label>
                <Textarea
                  id="content"
                  v-model="form.content"
                  placeholder="Share your thoughts, questions, or knowledge..."
                  rows="8"
                  required
                  :disabled="form.processing"
                />
                <p
                  v-if="form.errors.content"
                  class="text-sm text-red-600 flex items-center gap-1 mt-1"
                >
                  <AlertCircle class="w-4 h-4" /> {{ form.errors.content }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="image-upload">Image (Optional)</Label>
                <div class="flex items-center gap-4">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="triggerFileInput"
                    :disabled="form.processing"
                  >
                    <Icon name="image-plus" class="w-4 h-4 mr-2" />
                    {{ imagePreview ? "Change Image" : "Add Image" }}
                  </Button>
                  <input
                    type="file"
                    ref="fileInputRef"
                    class="hidden"
                    accept="image/*"
                    @change="handleImageUpload"
                  />
                  <Button
                    v-if="imagePreview"
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="removeImage"
                    :disabled="form.processing"
                  >
                    <Icon name="trash-2" class="w-4 h-4 mr-2" />
                    Remove
                  </Button>
                </div>
                <div v-if="imagePreview" class="mt-4 relative">
                  <img
                    :src="imagePreview"
                    class="max-h-64 rounded-md border border-border object-cover"
                    alt="Preview"
                  />
                </div>
                <p
                  v-if="form.errors.image"
                  class="text-sm text-red-600 flex items-center gap-1 mt-1"
                >
                  <AlertCircle class="w-4 h-4" /> {{ form.errors.image }}
                </p>
              </div>

                <div class="space-y-2">
                    <Label for="tags">Tags (optional)</Label>
                    <Select v-model="form.tag_ids" multiple>
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select tags" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="tag in tags"
                                :key="tag.id"
                                :value="tag.id"
                            >
                                {{ tag.tag_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.tag_ids" class="text-sm text-red-600 mt-1">
                        <AlertCircle class="w-4 h-4 inline mr-1" /> {{ form.errors.tag_ids }}
                    </p>
                </div>


                <div class="flex items-center justify-end gap-4 pt-4">
                <!-- Update cancel/back buttons to close modal when embedded -->
                <Button
                  v-if="props.embedded"
                  type="button"
                  variant="ghost"
                  :disabled="form.processing"
                  @click="$emit('close')"
                >
                  Cancel
                </Button>
                <Button
                  v-else
                  as-child
                  type="button"
                  variant="ghost"
                  :disabled="form.processing"
                >
                  <Link href="/forum">Cancel</Link>
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing"
                  :class="{ 'opacity-75 cursor-wait': form.processing }"
                >
                  <Icon
                    v-if="form.processing"
                    name="loader-2"
                    class="w-4 h-4 mr-2 animate-spin"
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
