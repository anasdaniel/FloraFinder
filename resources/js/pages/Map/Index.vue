<script setup lang="ts">
import { formatDate } from "@/utils";
import { computed, ref } from "vue";

// Direct component imports instead of using index.ts
import Icon from "@/components/Icon.vue";
import Pagination from "@/components/Pagination.vue";
import SightingMap from "@/components/SightingMap.vue"; // Import the new SightingMap component
import AppLayout from "@/layouts/AppLayout.vue";

// UI Components from shadcn/ui
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

// Add missing viewMode ref
const viewMode = ref<"view" | "grid" | "list" | "map">("view"); // Add "map" to viewMode types

interface SightingImage {
  id: number;
  url: string;
  organ: string;
}

interface plantsResult {
  id: number;
  user_id: number;
  user_name: string | null;
  path: string;
  images: SightingImage[];
  scientific_name: string;
  scientific_name_without_author: string;
  common_name: string;
  family: string | null;
  genus: string | null;
  iucn_category: string | null;
  region: string | null;
  location_name: string | null;
  latitude: number;
  longitude: number;
  description: string | null;
  sighted_at: string;
  created_at: string;
  updated_at: string;
}

// Dummy data following plantsResult interface
const dummyPlants: plantsResult[] = [
  {
    id: 1,
    user_id: 1,
    user_name: "John Doe",
    path: "/images/plant1.jpg",
    images: [
      { id: 1, url: "/images/plant1.jpg", organ: "leaf" },
      { id: 2, url: "/images/plant1-flower.jpg", organ: "flower" },
    ],
    scientific_name: "Quercus robur",
    scientific_name_without_author: "Quercus robur",
    common_name: "English Oak",
    family: "Fagaceae",
    genus: "Quercus",
    iucn_category: "LC",
    region: "Selangor",
    location_name: "Kuala Lumpur",
    latitude: 3.139,
    longitude: 101.6869,
    description: "Spotted near the park",
    sighted_at: "2023-10-01T00:00:00Z",
    created_at: "2023-10-01T00:00:00Z",
    updated_at: "2023-10-01T00:00:00Z",
  },
  {
    id: 2,
    user_id: 2,
    user_name: "Jane Smith",
    path: "/images/plant2.jpg",
    images: [
      { id: 3, url: "/images/plant2.jpg", organ: "flower" },
    ],
    scientific_name: "Rosa canina",
    scientific_name_without_author: "Rosa canina",
    common_name: "Dog Rose",
    family: "Rosaceae",
    genus: "Rosa",
    iucn_category: "VU",
    region: "Johor",
    location_name: "Johor Bahru",
    latitude: 1.4927,
    longitude: 103.7414,
    description: "Found in garden",
    sighted_at: "2023-09-15T00:00:00Z",
    created_at: "2023-09-15T00:00:00Z",
    updated_at: "2023-09-15T00:00:00Z",
  },
  // Add more dummy entries as needed
];

const props = defineProps<{
  plants?: plantsResult[];
}>();

// Use props.plants if provided, else dummy data
const plants = computed(() => props.plants || dummyPlants);

// Local conservation statuses to follow the specified list
const conservationStatuses = ref([
  { value: "NE", label: "Not Evaluated", color: "bg-blue-500 text-white" },
  { value: "DD", label: "Data Deficient", color: "bg-gray-500 text-white" },
  { value: "LC", label: "Least Concern", color: "bg-green-500 text-white" },
  { value: "NT", label: "Near Threatened", color: "bg-yellow-500 text-black" },
  { value: "VU", label: "Vulnerable", color: "bg-orange-500 text-white" },
  { value: "EN", label: "Endangered", color: "bg-red-500 text-white" },
  { value: "CR", label: "Critically Endangered", color: "bg-red-600 text-white" },
  { value: "EW", label: "Extinct in the Wild", color: "bg-gray-700 text-white" },
  { value: "EX", label: "Extinct", color: "bg-gray-400 text-black" },
]);

// Local functions for color and label
const getConservationStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    NE: "bg-blue-500 text-white",
    DD: "bg-gray-500 text-white",
    LC: "bg-green-500 text-white",
    NT: "bg-yellow-500 text-black",
    VU: "bg-orange-500 text-white",
    EN: "bg-red-500 text-white",
    CR: "bg-red-600 text-white",
    EW: "bg-gray-700 text-white",
    EX: "bg-gray-400 text-black",
  };
  return colors[status] || "bg-gray-200 text-gray-800";
};

const getConservationStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    NE: "Not Evaluated",
    DD: "Data Deficient",
    LC: "Least Concern",
    NT: "Near Threatened",
    VU: "Vulnerable",
    EN: "Endangered",
    CR: "Critically Endangered",
    EW: "Extinct in the Wild",
    EX: "Extinct",
  };
  return labels[status] || status;
};

// Local filters
const filters = ref({
  family: "all",
  conservationStatus: [] as string[],
  region: "all",
  dateFrom: "",
  dateTo: "",
  search: "",
});

// Filtered plants
const filteredPlants = computed(() => {
  let plants = [...(props.plants || dummyPlants)];

  // Set default conservation status to "DD" if null or undefined
  plants = plants.map((plant) => ({
    ...plant,
    iucn_category: plant.iucn_category || "DD",
    family: plant.family || "Unknown",
    common_name: plant.common_name || plant.scientific_name,
  }));

  return plants.filter((plant) => {
    const matchesFamily =
      filters.value.family === "all" || plant.family === filters.value.family;
    const matchesStatus =
      filters.value.conservationStatus.length === 0 ||
      filters.value.conservationStatus.includes(plant.iucn_category);
    const matchesRegion =
      filters.value.region === "all" || plant.region === filters.value.region;
    const sightedDate = plant.sighted_at || plant.created_at;
    const matchesDate =
      (!filters.value.dateFrom || sightedDate >= filters.value.dateFrom) &&
      (!filters.value.dateTo || sightedDate <= filters.value.dateTo);
    const matchesSearch =
      !filters.value.search ||
      plant.scientific_name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      (plant.common_name && plant.common_name.toLowerCase().includes(filters.value.search.toLowerCase()));
    return (
      matchesFamily && matchesStatus && matchesRegion && matchesDate && matchesSearch
    );
  });
});

// Summary statistics
const summaryStats = computed(() => {
  const families = new Set(filteredPlants.value.map((p) => p.family));
  const species = new Set(
    filteredPlants.value.map((p) => p.scientific_name_without_author)
  );
  const regions = new Set(filteredPlants.value.map((p) => p.region));
  return {
    total: filteredPlants.value.length,
    species: species.size,
    families: families.size,
    regions: regions.size,
  };
});

const mostRecentSighting = computed(() => {
  return filteredPlants.value.reduce<plantsResult | null>((latest, curr) => {
    if (!latest) return curr;
    const latestDate = latest.sighted_at || latest.created_at;
    const currDate = curr.sighted_at || curr.created_at;
    return new Date(currDate) > new Date(latestDate) ? curr : latest;
  }, null);
});

const activeFiltersCount = computed(() => {
  let count = 0;
  if (filters.value.family !== "all") count++;
  if (filters.value.region !== "all") count++;
  if (filters.value.conservationStatus.length) count++;
  if (filters.value.dateFrom || filters.value.dateTo) count++;
  if (filters.value.search.trim()) count++;
  return count;
});

const toggleConservationStatus = (value: string) => {
  const next = new Set(filters.value.conservationStatus);
  next.has(value) ? next.delete(value) : next.add(value);
  filters.value.conservationStatus = Array.from(next);
};

// Pagination
const currentPage = ref(1);
const itemsPerPage = 12;
const totalPages = computed(() => Math.ceil(filteredPlants.value.length / itemsPerPage));
const paginatedPlants = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredPlants.value.slice(start, start + itemsPerPage);
});

const handlePageChange = (page: number) => {
  currentPage.value = page;
};

// Selection
const selectedPlant = ref<plantsResult | null>(null);
const selectedImageIndex = ref(0);

const selectPlant = (plant: plantsResult) => {
  console.log("Selected plant:", plant); // Add logging for debugging
  selectedPlant.value = plant;
  selectedImageIndex.value = 0; // Reset to first image
};

const nextImage = () => {
  if (selectedPlant.value && selectedPlant.value.images.length > 0) {
    selectedImageIndex.value = (selectedImageIndex.value + 1) % selectedPlant.value.images.length;
  }
};

const prevImage = () => {
  if (selectedPlant.value && selectedPlant.value.images.length > 0) {
    selectedImageIndex.value = selectedImageIndex.value === 0
      ? selectedPlant.value.images.length - 1
      : selectedImageIndex.value - 1;
  }
};

const currentImage = computed(() => {
  if (!selectedPlant.value) return null;
  const images = selectedPlant.value.images;
  if (images && images.length > 0) {
    return images[selectedImageIndex.value];
  }
  return { id: 0, url: selectedPlant.value.path, organ: 'unknown' };
});

// Clear filters
const clearFilters = () => {
  filters.value = {
    family: "all",
    conservationStatus: [],
    region: "all",
    dateFrom: "",
    dateTo: "",
    search: "",
  };
  currentPage.value = 1;
};

// Unique families from plants
const plantFamilies = computed(() => {
  const families = plants.value.map((p) => p.family);
  return [...new Set(families)];
});

// Transform plants to sightings-like for map
const sightingsForMap = computed(() => {
  return filteredPlants.value.map((plant) => ({
    id: plant.id,
    image: plant.path,
    commonName: plant.common_name,
    scientificName: plant.scientific_name,
    conservationStatus: plant.iucn_category,
    location: plant.location_name || plant.region,
    date: plant.sighted_at || plant.created_at,
    latitude: plant.latitude,
    longitude: plant.longitude,
    family: plant.family,
    description: plant.description || '',
  }));
});

// Function to handle marker click from SightingMap
const handleMarkerClick = (sighting: any) => {
  const plant = filteredPlants.value.find((p) => p.id === sighting.id);
  if (plant) selectPlant(plant);
};

// Local Malaysian states
const malaysianStates = ref([
  "Johor",
  "Kedah",
  "Kelantan",
  "Melaka",
  "Negeri Sembilan",
  "Pahang",
  "Perak",
  "Perlis",
  "Pulau Pinang",
  "Sabah",
  "Sarawak",
  "Selangor",
  "Terengganu",
  "Kuala Lumpur",
  "Labuan",
  "Putrajaya",
]);
</script>

<template>
  <AppLayout title="Plant Sightings Map">
    <div class="min-h-screen bg-white dark:bg-gray-950">
      <!-- Page Header -->
      <div class="bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 py-16 text-center">
          <h1 class="mb-4 text-4xl font-bold text-gray-900 dark:text-white">
            Plant Sightings Map
          </h1>
          <p class="mx-auto max-w-3xl text-xl text-gray-600 dark:text-gray-400">
            Explore documented plant sightings across Malaysia. View locations,
            conservation status, and detailed information about flora discoveries from
            researchers and nature enthusiasts.
          </p>
        </div>
      </div>

      <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-10 lg:flex-row">
        <!-- Sidebar Filters -->
        <aside class="w-full flex-shrink-0 lg:w-80">
          <div class="sticky top-8 rounded-2xl dark:bg-gray-900">
            <div class="mb-6 flex items-start justify-between">
              <div>
                <h2 class="flex items-center gap-2 text-xl font-bold">
                  <Icon name="filter" class="h-6 w-6" />
                  Filters
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ activeFiltersCount }} active
                </p>
              </div>
              <Button variant="ghost" size="sm" @click="clearFilters">Reset</Button>
            </div>
            <div class="space-y-6">
              <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                <label for="plantSearchInput" class="mb-2 block text-sm font-medium"
                  >Search plants</label
                >
                <div class="relative">
                  <Icon
                    name="search"
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                  />
                  <input
                    id="plantSearchInput"
                    v-model="filters.search"
                    type="text"
                    placeholder="Scientific or common name..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-3 text-sm dark:border-gray-700 dark:bg-gray-800"
                  />
                </div>
              </div>
              <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="mb-3 flex items-center justify-between text-sm font-medium">
                  <span>Plant family</span>
                  <Button variant="ghost" size="sm" @click="filters.family = 'all'"
                    >All</Button
                  >
                </div>
                <Select v-model="filters.family">
                  <SelectTrigger>
                    <SelectValue placeholder="All families" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All families</SelectItem>
                    <SelectItem
                      v-for="family in plantFamilies"
                      :key="family"
                      :value="family"
                      >{{ family }}</SelectItem
                    >
                  </SelectContent>
                </Select>
              </div>
              <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="mb-3 flex items-center justify-between text-sm font-medium">
                  <span>Conservation status</span>
                  <span class="text-xs text-gray-500"
                    >{{ filters.conservationStatus.length }} selected</span
                  >
                </div>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="status in conservationStatuses"
                    :key="status.value"
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="
                      filters.conservationStatus.includes(status.value)
                        ? status.color + ' shadow'
                        : 'border border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300'
                    "
                    @click="toggleConservationStatus(status.value)"
                  >
                    {{ status.label }}
                  </button>
                </div>
              </div>
              <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="mb-3 flex items-center justify-between text-sm font-medium">
                  <span>State of Malaysia</span>
                  <Button variant="ghost" size="sm" @click="filters.region = 'all'"
                    >All</Button
                  >
                </div>
                <Select v-model="filters.region">
                  <SelectTrigger>
                    <SelectValue placeholder="All states" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All states</SelectItem>
                    <SelectItem
                      v-for="state in malaysianStates"
                      :key="state"
                      :value="state"
                      >{{ state }}</SelectItem
                    >
                  </SelectContent>
                </Select>
              </div>
              <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="mb-3 text-sm font-medium">Sighting date</div>
                <div class="grid grid-cols-2 gap-3">
                  <input
                    v-model="filters.dateFrom"
                    type="date"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    placeholder="From"
                  />
                  <input
                    v-model="filters.dateTo"
                    type="date"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    placeholder="To"
                  />
                </div>
              </div>
            </div>
          </div>
        </aside>
        <!-- Main Content -->
        <main class="flex flex-1 flex-col gap-8">
          <!-- Sightings Card -->
          <div class="rounded-2xl bg-white dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold">
                Plant Sightings ({{ filteredPlants.length }})
              </h2>
              <div
                class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 p-1 text-sm font-medium dark:border-gray-700 dark:bg-gray-800"
              >
                <button
                  v-for="mode in ['view', 'grid', 'list', 'map']"
                  :key="mode"
                  type="button"
                  class="rounded-full px-4 py-2 capitalize transition"
                  :class="
                    viewMode === mode
                      ? 'bg-white text-gray-900 shadow dark:bg-gray-900 dark:text-white'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  "
                  :aria-pressed="viewMode === mode"
                  @click="viewMode = mode as typeof viewMode"
                >
                  {{ mode }}
                </button>
              </div>
            </div>
            <div v-if="viewMode === 'view'" class="space-y-4">
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                  v-for="(value, key) in {
                    'Total Sightings': summaryStats.total,
                    Species: summaryStats.species,
                    Families: summaryStats.families,
                    Regions: summaryStats.regions,
                  }"
                  :key="key"
                  class="rounded-2xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-gray-800"
                >
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ key }}</p>
                  <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ value }}
                  </p>
                </div>
              </div>
              <div
                v-if="mostRecentSighting"
                class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800 sm:flex-row"
              >
                <img
                  :src="mostRecentSighting.path"
                  :alt="mostRecentSighting.common_name"
                  class="h-44 w-full rounded-xl object-cover sm:w-56"
                />
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Latest sighting
                  </p>
                  <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ mostRecentSighting.common_name }}
                  </h3>
                  <p class="text-sm italic text-gray-600 dark:text-gray-400">
                    {{ mostRecentSighting.scientific_name }}
                  </p>
                  <div
                    class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400"
                  >
                    <span class="flex items-center gap-1">
                      <Icon name="map-pin" class="h-4 w-4" />
                      {{ mostRecentSighting.region }}
                    </span>
                    <span class="flex items-center gap-1">
                      <Icon name="calendar" class="h-4 w-4" />
                      {{ formatDate(mostRecentSighting.sighted_at || mostRecentSighting.created_at) }}
                    </span>
                    <span
                      class="flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                      :class="
                        getConservationStatusColor(mostRecentSighting.iucn_category)
                      "
                    >
                      {{ getConservationStatusLabel(mostRecentSighting.iucn_category) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'">
              <div v-if="paginatedPlants.length === 0" class="py-12 text-center">
                <p class="text-gray-500 dark:text-gray-400">No plants to show yet.</p>
              </div>
              <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="plant in paginatedPlants"
                  :key="plant.id"
                  @click="selectPlant(plant)"
                  class="cursor-pointer rounded-xl border border-gray-200 bg-gray-50 shadow transition hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <div class="relative h-44 overflow-hidden rounded-t-xl">
                    <img
                      :src="plant.path"
                      :alt="plant.common_name"
                      class="h-full w-full object-cover"
                    />
                    <div class="absolute right-2 top-2">
                      <span
                        class="rounded-full px-2 py-1 text-xs font-medium"
                        :class="getConservationStatusColor(plant.iucn_category)"
                      >
                        {{ getConservationStatusLabel(plant.iucn_category) }}
                      </span>
                    </div>
                  </div>
                  <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                      {{ plant.common_name }}
                    </h3>
                    <p class="text-sm italic text-gray-600 dark:text-gray-400">
                      {{ plant.scientific_name }}
                    </p>
                    <div
                      class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                      <Icon name="map-pin" class="h-3 w-3" />
                      <span>{{ plant.region }}</span>
                    </div>
                    <div
                      class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                      <Icon name="calendar" class="h-3 w-3" />
                      <span>{{ formatDate(plant.sighted_at || plant.created_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- List View -->
            <div v-else-if="viewMode === 'list'">
              <div v-if="paginatedPlants.length === 0" class="py-12 text-center">
                <p class="text-gray-500 dark:text-gray-400">No plants to show yet.</p>
              </div>
              <div v-else class="space-y-4">
                <div
                  v-for="plant in paginatedPlants"
                  :key="plant.id"
                  @click="selectPlant(plant)"
                  class="flex cursor-pointer items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 shadow transition hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <div
                    class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg"
                  >
                    <img
                      :src="plant.path"
                      :alt="plant.common_name"
                      class="h-full w-full object-cover"
                    />
                    <div class="absolute -right-1 -top-1">
                      <span
                        class="rounded-full px-2 py-1 text-xs font-medium"
                        :class="getConservationStatusColor(plant.iucn_category)"
                      >
                        {{ plant.iucn_category }}
                      </span>
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                      {{ plant.common_name }}
                    </h3>
                    <p class="text-sm italic text-gray-600 dark:text-gray-400">
                      {{ plant.scientific_name }}
                    </p>
                    <div
                      class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                      <Icon name="map-pin" class="h-3 w-3" />
                      <span>{{ plant.region }}</span>
                    </div>
                    <div
                      class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                      <Icon name="calendar" class="h-3 w-3" />
                      <span>{{ formatDate(plant.sighted_at || plant.created_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Map View -->
            <div v-else-if="viewMode === 'map'" class="relative z-0">
              <div v-if="filteredPlants.length === 0" class="py-8 text-center">
                <p class="text-gray-500">No sightings to display on map.</p>
              </div>
              <SightingMap
                v-else
                :sightings="sightingsForMap"
                @marker-click="handleMarkerClick"
              />
            </div>
            <!-- Pagination -->
            <div
              v-if="['grid', 'list'].includes(viewMode)"
              class="mt-6 flex justify-center"
            >
              <Pagination
                :current-page="currentPage"
                :total-pages="totalPages"
                @page-changed="handlePageChange"
              />
            </div>
          </div>
        </main>
      </div>
      <!-- Plant Details Modal -->
      <Dialog :open="!!selectedPlant" @update:open="selectedPlant = null">
        <DialogContent class="z-[9999] max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <span>{{ selectedPlant?.common_name }}</span>
              <div
                :class="getConservationStatusColor(selectedPlant?.iucn_category || '')"
                class="h-3 w-3 rounded-full"
              ></div>
            </DialogTitle>
          </DialogHeader>
          <div v-if="selectedPlant" class="space-y-4">
            <!-- Image Gallery -->
            <div class="relative">
              <div class="relative h-64 overflow-hidden rounded-lg bg-gray-100">
                <img
                  :src="currentImage?.url || selectedPlant?.path"
                  :alt="selectedPlant?.common_name"
                  class="h-full w-full object-cover"
                />
                <!-- Image counter badge -->
                <div v-if="selectedPlant.images && selectedPlant.images.length > 1"
                     class="absolute top-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-full">
                  {{ selectedImageIndex + 1 }} / {{ selectedPlant.images.length }}
                </div>
                <!-- Organ badge -->
                <div v-if="currentImage?.organ"
                     class="absolute top-3 left-3 bg-green-600 text-white text-xs px-2 py-1 rounded-full capitalize">
                  {{ currentImage.organ }}
                </div>
                <!-- Navigation arrows -->
                <template v-if="selectedPlant.images && selectedPlant.images.length > 1">
                  <button
                    @click="prevImage"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition"
                  >
                    <Icon name="chevron-left" class="h-5 w-5" />
                  </button>
                  <button
                    @click="nextImage"
                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition"
                  >
                    <Icon name="chevron-right" class="h-5 w-5" />
                  </button>
                </template>
              </div>
              <!-- Thumbnail strip -->
              <div v-if="selectedPlant.images && selectedPlant.images.length > 1"
                   class="flex gap-2 mt-3 overflow-x-auto pb-2">
                <button
                  v-for="(img, index) in selectedPlant.images"
                  :key="img.id"
                  @click="selectedImageIndex = index"
                  class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition"
                  :class="selectedImageIndex === index ? 'border-green-500 ring-2 ring-green-200' : 'border-transparent hover:border-gray-300'"
                >
                  <img :src="img.url" :alt="`Image ${index + 1}`" class="w-full h-full object-cover" />
                </button>
              </div>
            </div>

            <!-- Spotted by -->
            <div v-if="selectedPlant.user_name" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
              <Icon name="user" class="h-4 w-4" />
              <span>Spotted by <span class="font-medium">{{ selectedPlant.user_name }}</span></span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Scientific Name:</span
                >
                <p class="italic text-gray-600 dark:text-gray-400">
                  {{ selectedPlant?.scientific_name }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white">Family:</span>
                <p class="text-gray-600 dark:text-gray-400">
                  {{ selectedPlant?.family }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Conservation Status:</span
                >
                <p class="text-gray-600 dark:text-gray-400">
                  {{ getConservationStatusLabel(selectedPlant?.iucn_category || "") }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Date Spotted:</span
                >
                <p class="text-gray-600 dark:text-gray-400">
                  {{ formatDate(selectedPlant?.sighted_at || selectedPlant?.created_at || "") }}
                </p>
              </div>
            </div>
            <div>
              <span class="font-medium text-gray-900 dark:text-white">Location:</span>
              <p class="text-gray-600 dark:text-gray-400">
                {{ selectedPlant?.location_name || selectedPlant?.region }}
              </p>
              <div class="mt-2 text-xs text-gray-500">
                Coordinates:
                {{
                  selectedPlant?.latitude && !isNaN(Number(selectedPlant.latitude))
                    ? Number(selectedPlant.latitude).toFixed(4)
                    : "N/A"
                }},
                {{
                  selectedPlant?.longitude && !isNaN(Number(selectedPlant.longitude))
                    ? Number(selectedPlant.longitude).toFixed(4)
                    : "N/A"
                }}
              </div>
            </div>
            <div v-if="selectedPlant?.description">
              <span class="font-medium text-gray-900 dark:text-white">Description:</span>
              <p class="mt-1 text-gray-600 dark:text-gray-400">
                {{ selectedPlant?.description }}
              </p>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
