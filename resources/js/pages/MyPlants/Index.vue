<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
  ChevronLeft,
  ChevronRight,
  Eye,
  Grid3X3,
  List,
  MapPin,
  RefreshCw,
  Search,
  SearchX,
  Leaf,
  Clock,
  Trash2,
  Plus,
  Sparkles,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

interface PlantIdentification {
  id: number;
  user_id: number;
  path: string;
  url: string;
  filename: string;
  mime_type: string;
  size: number;
  organ: string;
  scientific_name: string;
  scientific_name_without_author: string;
  common_name: string | null;
  family: string | null;
  genus: string | null;
  confidence: number;
  gbif_id: string | null;
  powo_id: string | null;
  iucn_category: string | null;
  region: string | null;
  latitude: number | null;
  longitude: number | null;
  created_at: string;
  updated_at: string;
}

interface PaginatedPlants {
  data: PlantIdentification[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
  plants: PaginatedPlants;
  families: string[];
  filters: {
    search?: string;
    family?: string;
  };
}>();

// Reactive state
const searchQuery = ref(props.filters.search || "");
const selectedFamily = ref(props.filters.family || "");
const viewMode = ref<"grid" | "list">("grid");
const isLoading = ref(false);

// Delete confirmation
const deleteDialogOpen = ref(false);
const plantToDelete = ref<PlantIdentification | null>(null);
const isDeleting = ref(false);

// Detail modal
const detailDialogOpen = ref(false);
const selectedPlant = ref<PlantIdentification | null>(null);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;

const applyFilters = () => {
  isLoading.value = true;
  router.get(
    "/my-plants",
    {
      search: searchQuery.value || undefined,
      family: selectedFamily.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isLoading.value = false;
      },
    }
  );
};

watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(applyFilters, 400);
});

watch(selectedFamily, applyFilters);

const clearFilters = () => {
  searchQuery.value = "";
  selectedFamily.value = "";
  applyFilters();
};

const hasActiveFilters = computed(() => {
  return searchQuery.value || selectedFamily.value;
});

// Delete handling
const confirmDelete = (plant: PlantIdentification) => {
  plantToDelete.value = plant;
  deleteDialogOpen.value = true;
};

const deletePlant = () => {
  if (!plantToDelete.value) return;

  isDeleting.value = true;
  router.delete(`/my-plants/${plantToDelete.value.id}`, {
    onSuccess: () => {
      deleteDialogOpen.value = false;
      plantToDelete.value = null;
    },
    onFinish: () => {
      isDeleting.value = false;
    },
  });
};

// View details
const viewDetails = (plant: PlantIdentification) => {
  selectedPlant.value = plant;
  detailDialogOpen.value = true;
};

// Pagination
const goToPage = (url: string | null) => {
  if (!url) return;
  isLoading.value = true;
  router.get(
    url,
    {},
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isLoading.value = false;
      },
    }
  );
};

// Formatting helpers
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatConfidence = (confidence: number) => {
  return `${Math.round(confidence * 100)}%`;
};

const getConfidenceColor = (confidence: number) => {
  if (confidence >= 0.8)
    return "text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30";
  if (confidence >= 0.5)
    return "text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30";
  return "text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30";
};

const getOrganLabel = (organ: string) => {
  const labels: Record<string, string> = {
    flower: "🌸 Flower",
    leaf: "🍃 Leaf",
    fruit: "🍎 Fruit",
    bark: "🪵 Bark",
    habit: "🌳 Habit",
  };
  return labels[organ] || organ;
};
</script>

<template>
  <Head title="My Plants" />
  <AppLayout>
    <div
      class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-green-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-green-950/20"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8"
        >
          <div>
            <h1
              class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3"
            >
              <div class="p-2 bg-green-100 rounded-xl dark:bg-green-900/40">
                <Leaf class="w-7 h-7 text-green-600 dark:text-green-400" />
              </div>
              My Plants
            </h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">
              Your personal plant collection · {{ plants.total }} plants
            </p>
          </div>
          <Link
            href="/plant-identifier"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-medium shadow-lg shadow-green-600/20"
          >
            <Plus class="w-5 h-5" />
            Identify New Plant
          </Link>
        </div>

        <!-- Filters -->
        <div
          class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm"
        >
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center flex-1">
              <!-- Search -->
              <div class="relative flex-1 max-w-md">
                <Search
                  class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by name..."
                  class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                />
              </div>

              <!-- Family Filter -->
              <select
                v-model="selectedFamily"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="">All Families</option>
                <option v-for="family in families" :key="family" :value="family">
                  {{ family }}
                </option>
              </select>

              <!-- Clear Filters -->
              <button
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
              >
                <X class="w-4 h-4" />
                Clear
              </button>
            </div>

            <!-- View Toggle -->
            <div
              class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-900 rounded-lg"
            >
              <button
                @click="viewMode = 'grid'"
                :class="[
                  'p-2 rounded-md transition-colors',
                  viewMode === 'grid'
                    ? 'bg-white dark:bg-gray-800 text-green-600 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                ]"
              >
                <Grid3X3 class="w-4 h-4" />
              </button>
              <button
                @click="viewMode = 'list'"
                :class="[
                  'p-2 rounded-md transition-colors',
                  viewMode === 'list'
                    ? 'bg-white dark:bg-gray-800 text-green-600 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                ]"
              >
                <List class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-12">
          <RefreshCw class="w-6 h-6 text-green-600 animate-spin" />
        </div>

        <!-- Empty State -->
        <div
          v-else-if="plants.data.length === 0"
          class="flex flex-col items-center justify-center py-16 text-center"
        >
          <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
            <SearchX class="w-10 h-10 text-gray-400" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
            {{ hasActiveFilters ? "No plants found" : "No plants yet" }}
          </h3>
          <p class="text-gray-500 dark:text-gray-400 max-w-sm mb-4">
            {{
              hasActiveFilters
                ? "Try adjusting your search or filters."
                : "Start building your plant collection by identifying plants."
            }}
          </p>
          <Link
            v-if="!hasActiveFilters"
            href="/plant-identifier"
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Identify Your First Plant
          </Link>
        </div>

        <!-- Grid View -->
        <div
          v-else-if="viewMode === 'grid'"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
          <div
            v-for="plant in plants.data"
            :key="plant.id"
            class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg hover:border-green-300 dark:hover:border-green-700 transition-all duration-300"
          >
            <!-- Image -->
            <div class="relative aspect-square overflow-hidden">
              <img
                :src="plant.url"
                :alt="plant.common_name || plant.scientific_name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div
                class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"
              />

              <!-- Confidence Badge -->
              <div class="absolute top-3 left-3">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-semibold rounded-full',
                    getConfidenceColor(plant.confidence),
                  ]"
                >
                  {{ formatConfidence(plant.confidence) }}
                </span>
              </div>

              <!-- Organ Badge -->
              <div class="absolute top-3 right-3">
                <span
                  class="px-2 py-1 text-xs font-medium rounded-full bg-white/90 dark:bg-gray-900/90 text-gray-700 dark:text-gray-300"
                >
                  {{ getOrganLabel(plant.organ) }}
                </span>
              </div>

              <!-- Hover Actions -->
              <div
                class="absolute bottom-3 left-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <button
                  @click="viewDetails(plant)"
                  class="flex-1 py-2 bg-white/90 dark:bg-gray-900/90 text-gray-900 dark:text-white rounded-lg text-sm font-medium hover:bg-white dark:hover:bg-gray-800 transition-colors"
                >
                  <Eye class="w-4 h-4 inline mr-1" />
                  View
                </button>
                <button
                  @click="confirmDelete(plant)"
                  class="p-2 bg-red-500/90 text-white rounded-lg hover:bg-red-600 transition-colors"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Content -->
            <div class="p-4">
              <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                {{ plant.common_name || plant.scientific_name_without_author }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 italic truncate">
                {{ plant.scientific_name_without_author }}
              </p>
              <div
                class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
              >
                <Clock class="w-3.5 h-3.5" />
                {{ formatDate(plant.created_at) }}
              </div>
            </div>
          </div>
        </div>

        <!-- List View -->
        <div v-else class="space-y-3">
          <div
            v-for="plant in plants.data"
            :key="plant.id"
            class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-green-300 dark:hover:border-green-700 transition-all"
          >
            <!-- Thumbnail -->
            <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0">
              <img
                :src="plant.url"
                :alt="plant.common_name || plant.scientific_name"
                class="w-full h-full object-cover"
              />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ plant.common_name || plant.scientific_name_without_author }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    {{ plant.scientific_name_without_author }}
                  </p>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-semibold rounded-full flex-shrink-0',
                    getConfidenceColor(plant.confidence),
                  ]"
                >
                  {{ formatConfidence(plant.confidence) }}
                </span>
              </div>
              <div
                class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400"
              >
                <span v-if="plant.family" class="flex items-center gap-1">
                  <Leaf class="w-3.5 h-3.5" />
                  {{ plant.family }}
                </span>
                <span class="flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5" />
                  {{ formatDate(plant.created_at) }}
                </span>
                <span v-if="plant.region" class="flex items-center gap-1">
                  <MapPin class="w-3.5 h-3.5" />
                  {{ plant.region }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button
                @click="viewDetails(plant)"
                class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
              >
                <Eye class="w-5 h-5" />
              </button>
              <button
                @click="confirmDelete(plant)"
                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              >
                <Trash2 class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="plants.last_page > 1"
          class="mt-8 flex items-center justify-center gap-2"
        >
          <button
            @click="goToPage(plants.links[0]?.url)"
            :disabled="!plants.links[0]?.url"
            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <ChevronLeft class="w-5 h-5" />
          </button>

          <div class="flex items-center gap-1">
            <template v-for="(link, index) in plants.links.slice(1, -1)" :key="index">
              <button
                v-if="link.url"
                @click="goToPage(link.url)"
                :class="[
                  'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                  link.active
                    ? 'bg-green-600 text-white'
                    : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300',
                ]"
              >
                {{ link.label }}
              </button>
              <span v-else class="px-2 text-gray-400">...</span>
            </template>
          </div>

          <button
            @click="goToPage(plants.links[plants.links.length - 1]?.url)"
            :disabled="!plants.links[plants.links.length - 1]?.url"
            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <ChevronRight class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="deleteDialogOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete Plant</DialogTitle>
          <DialogDescription>
            Are you sure you want to remove
            <span class="font-semibold">{{
              plantToDelete?.common_name || plantToDelete?.scientific_name
            }}</span>
            from your collection? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2 sm:gap-0">
          <Button
            variant="outline"
            @click="deleteDialogOpen = false"
            :disabled="isDeleting"
          >
            Cancel
          </Button>
          <Button variant="destructive" @click="deletePlant" :disabled="isDeleting">
            <Trash2 v-if="!isDeleting" class="w-4 h-4 mr-2" />
            <RefreshCw v-else class="w-4 h-4 mr-2 animate-spin" />
            {{ isDeleting ? "Deleting..." : "Delete" }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Plant Detail Dialog -->
    <Dialog v-model:open="detailDialogOpen">
      <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Sparkles class="w-5 h-5 text-green-600" />
            {{
              selectedPlant?.common_name || selectedPlant?.scientific_name_without_author
            }}
          </DialogTitle>
        </DialogHeader>

        <div v-if="selectedPlant" class="space-y-4">
          <!-- Image -->
          <div class="rounded-xl overflow-hidden">
            <img
              :src="selectedPlant.url"
              :alt="selectedPlant.common_name || selectedPlant.scientific_name"
              class="w-full max-h-80 object-cover"
            />
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Scientific Name</span
              >
              <span class="font-medium text-gray-900 dark:text-white italic">
                {{ selectedPlant.scientific_name }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Family</span
              >
              <span class="font-medium text-gray-900 dark:text-white">
                {{ selectedPlant.family || "Unknown" }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Genus</span
              >
              <span class="font-medium text-gray-900 dark:text-white">
                {{ selectedPlant.genus || "Unknown" }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Confidence</span
              >
              <span
                :class="[
                  'font-medium px-2 py-0.5 rounded-full text-sm',
                  getConfidenceColor(selectedPlant.confidence),
                ]"
              >
                {{ formatConfidence(selectedPlant.confidence) }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Organ Identified</span
              >
              <span class="font-medium text-gray-900 dark:text-white">
                {{ getOrganLabel(selectedPlant.organ) }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Identified On</span
              >
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatDate(selectedPlant.created_at) }}
              </span>
            </div>
            <div
              v-if="selectedPlant.region"
              class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl col-span-2"
            >
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Location</span
              >
              <span
                class="font-medium text-gray-900 dark:text-white flex items-center gap-1"
              >
                <MapPin class="w-4 h-4 text-gray-400" />
                {{ selectedPlant.region }}
              </span>
            </div>
            <div
              v-if="selectedPlant.iucn_category"
              class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl col-span-2"
            >
              <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1"
                >Conservation Status</span
              >
              <span class="font-medium text-gray-900 dark:text-white">
                {{ selectedPlant.iucn_category }}
              </span>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="detailDialogOpen = false"> Close </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
