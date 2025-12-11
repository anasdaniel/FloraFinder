<!--
  COMPONENT: MyPlants/Map.vue
  ROUTE: /plant-map
  PURPOSE: Display the authenticated user's personal plant collection on an interactive map
  FILTERING: Client-side (Vue computed properties)
  AUTH: Required
  DATA SOURCE: User's saved plant identifications
-->
<script setup lang="ts">
import { formatDate } from "@/utils";
import { computed, ref } from "vue";
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronDown,
    Filter,
    MapPin,
    Search,
    X,
    Grid3X3,
    List,
    Map as MapIcon,
    Users,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';

// Direct component imports
import Icon from "@/components/Icon.vue";
import SightingMap from "@/components/SightingMap.vue";
import AppLayout from "@/layouts/AppLayout.vue";

// UI Components from shadcn/ui
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

// View mode state
const viewMode = ref<"grid" | "list" | "map">("grid");
const mobileFiltersOpen = ref(false);

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

// Dummy data
const dummyPlants: plantsResult[] = [
  {
    id: 1,
    user_id: 1,
    user_name: "John Doe",
    path: "/images/plant1.jpg",
    images: [
      { id: 1, url: "/images/plant1.jpg", organ: "leaf" },
    ],
    scientific_name: "Mangifera indica L.",
    scientific_name_without_author: "Mangifera indica",
    common_name: "Mango",
    family: "Anacardiaceae",
    genus: "Mangifera",
    iucn_category: "DD",
    region: "Pahang",
    location_name: "Pahang",
    latitude: 3.139,
    longitude: 101.6869,
    description: "Spotted near the park",
    sighted_at: "2025-12-04T00:00:00Z",
    created_at: "2025-12-04T00:00:00Z",
    updated_at: "2025-12-04T00:00:00Z",
  },
  {
    id: 2,
    user_id: 2,
    user_name: "Jane Smith",
    path: "/images/plant2.jpg",
    images: [],
    scientific_name: "Mangifera indica L.",
    scientific_name_without_author: "Mangifera indica",
    common_name: "Mango",
    family: "Anacardiaceae",
    genus: "Mangifera",
    iucn_category: "DD",
    region: "Peninsular Malaysia",
    location_name: "Peninsular Malaysia",
    latitude: 1.4927,
    longitude: 103.7414,
    description: "Found in garden",
    sighted_at: "2025-12-05T00:00:00Z",
    created_at: "2025-12-05T00:00:00Z",
    updated_at: "2025-12-05T00:00:00Z",
  },
  {
    id: 3,
    user_id: 3,
    user_name: "Bob",
    path: "/images/plant3.jpg",
    images: [],
    scientific_name: "Mangifera indica L.",
    scientific_name_without_author: "Mangifera indica",
    common_name: "Mango",
    family: "Anacardiaceae",
    genus: "Mangifera",
    iucn_category: "DD",
    region: "Pahang",
    location_name: "Pahang",
    latitude: 3.5,
    longitude: 102.0,
    description: "Another mango tree",
    sighted_at: "2025-12-06T00:00:00Z",
    created_at: "2025-12-06T00:00:00Z",
    updated_at: "2025-12-06T00:00:00Z",
  },
  {
    id: 4,
    user_id: 4,
    user_name: "Alice",
    path: "/images/plant4.jpg",
    images: [],
    scientific_name: "Mangifera indica L.",
    scientific_name_without_author: "Mangifera indica",
    common_name: "Mango",
    family: "Anacardiaceae",
    genus: "Mangifera",
    iucn_category: "DD",
    region: "Pahang",
    location_name: "Pahang",
    latitude: 3.6,
    longitude: 102.1,
    description: "Mango sighting",
    sighted_at: "2025-12-06T00:00:00Z",
    created_at: "2025-12-06T00:00:00Z",
    updated_at: "2025-12-06T00:00:00Z",
  },
];

const props = defineProps<{
  plants?: plantsResult[];
}>();

const plants = computed(() => props.plants || dummyPlants);

const conservationStatuses = ref([
  { value: "NE", label: "Not Evaluated" },
  { value: "DD", label: "Data Deficient" },
  { value: "LC", label: "Least Concern" },
  { value: "NT", label: "Near Threatened" },
  { value: "VU", label: "Vulnerable" },
  { value: "EN", label: "Endangered" },
  { value: "CR", label: "Critically Endangered" },
  { value: "EW", label: "Extinct in the Wild" },
  { value: "EX", label: "Extinct" },
]);

const getConservationStatusColor = (status: string | null): string => {
    const colors: Record<string, string> = {
        NE: 'bg-blue-500 text-white',
        DD: 'bg-gray-500 text-white',
        LC: 'bg-green-500 text-white',
        NT: 'bg-yellow-500 text-black',
        VU: 'bg-orange-500 text-white',
        EN: 'bg-red-500 text-white',
        CR: 'bg-red-600 text-white',
        EW: 'bg-gray-700 text-white',
        EX: 'bg-gray-400 text-black',
    };
    return colors[status || ''] || 'bg-gray-500 text-white';
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

const filters = ref({
  family: "",
  conservationStatus: [] as string[],
  region: "",
  dateFrom: "",
  dateTo: "",
  search: "",
});

const filteredPlants = computed(() => {
  let plantsList = [...(plants.value)];

  plantsList = plantsList.map((plant) => ({
    ...plant,
    iucn_category: plant.iucn_category || "DD",
    family: plant.family || "Unknown",
    common_name: plant.common_name || plant.scientific_name,
  }));

  return plantsList.filter((plant) => {
    const matchesFamily =
      filters.value.family === "" || plant.family === filters.value.family;
    const matchesStatus =
      filters.value.conservationStatus.length === 0 ||
      filters.value.conservationStatus.includes(plant.iucn_category || "");
    const matchesRegion =
      filters.value.region === "" || plant.region === filters.value.region;
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

const activeFiltersCount = computed(() => {
  let count = 0;
  if (filters.value.family !== "") count++;
  if (filters.value.region !== "") count++;
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

// Selection
const selectedPlant = ref<plantsResult | null>(null);
const selectedImageIndex = ref(0);

const selectPlant = (plant: plantsResult) => {
  selectedPlant.value = plant;
  selectedImageIndex.value = 0;
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

const clearFilters = () => {
  filters.value = {
    family: "",
    conservationStatus: [],
    region: "",
    dateFrom: "",
    dateTo: "",
    search: "",
  };
  currentPage.value = 1;
};

const plantFamilies = computed(() => {
  const families = plants.value.map((p) => p.family);
  return [...new Set(families)].filter(Boolean) as string[];
});

const sightingsForMap = computed(() => {
  return filteredPlants.value.map((plant) => ({
    id: plant.id,
    image: plant.path,
    commonName: plant.common_name,
    scientificName: plant.scientific_name,
    conservationStatus: plant.iucn_category || '',
    location: plant.location_name || plant.region || '',
    date: plant.sighted_at || plant.created_at,
    latitude: plant.latitude,
    longitude: plant.longitude,
    family: plant.family || '',
    description: plant.description || '',
  }));
});

const handleMarkerClick = (sighting: any) => {
  const plant = filteredPlants.value.find((p) => p.id === sighting.id);
  if (plant) selectPlant(plant);
};

const malaysianStates = ref([
  "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang",
  "Perak", "Perlis", "Pulau Pinang", "Sabah", "Sarawak", "Selangor",
  "Terengganu", "Kuala Lumpur", "Labuan", "Putrajaya",
]);
</script>

<template>
  <Head title="Plant Sightings Map" />
  <AppLayout title="Plant Sightings Map">
    <div class="min-h-screen bg-gray-50">
      <div class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Filters Sidebar -->
            <aside
                class="w-full lg:w-80 flex-shrink-0 space-y-6"
                :class="[
                    mobileFiltersOpen ? 'fixed inset-0 bg-white z-50 overflow-y-auto p-6' : 'hidden lg:block'
                ]"
            >
                <!-- Mobile Close Button -->
                <button
                    v-if="mobileFiltersOpen"
                    @click="mobileFiltersOpen = false"
                    class="lg:hidden absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600"
                >
                    <X class="w-6 h-6" />
                </button>

                <!-- Filter Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Filter class="w-5 h-5 text-gray-700" />
                        <h3 class="text-lg font-bold text-gray-900">Filters</h3>
                    </div>
                    <button
                        @click="clearFilters"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Reset
                    </button>
                </div>
                <div class="text-sm text-gray-500">
                    {{ activeFiltersCount }} active
                </div>

                 <!-- Search -->
                 <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Search plants</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Scientific or common name..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                        />
                    </div>
                </div>

                <!-- Plant Family -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-900">Plant family</label>
                        <button
                            @click="filters.family = ''"
                            class="text-xs text-gray-500 hover:text-gray-900"
                        >
                            All
                        </button>
                    </div>
                    <div class="relative">
                        <select
                            v-model="filters.family"
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 appearance-none"
                        >
                            <option value="">All families</option>
                            <option v-for="family in plantFamilies" :key="family" :value="family">
                                {{ family }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Conservation Status -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-900">Conservation status</label>
                        <span class="text-xs text-gray-500">{{ filters.conservationStatus.length }} selected</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="status in conservationStatuses"
                            :key="status.value"
                            @click="toggleConservationStatus(status.value)"
                            class="rounded-full px-3 py-1.5 text-xs font-medium transition-all"
                            :class="[
                                filters.conservationStatus.includes(status.value)
                                    ? 'bg-gray-200 text-gray-900'
                                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            ]"
                        >
                            {{ status.label }}
                        </button>
                    </div>
                </div>

                <!-- Region -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-900">State of Malaysia</label>
                        <button
                            @click="filters.region = ''"
                            class="text-xs text-gray-500 hover:text-gray-900"
                        >
                            All
                        </button>
                    </div>
                    <div class="relative">
                        <select
                            v-model="filters.region"
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 appearance-none"
                        >
                            <option value="">All states</option>
                            <option v-for="state in malaysianStates" :key="state" :value="state">
                                {{ state }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>

                 <!-- Date Range -->
                 <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Sighting date</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative">
                            <input
                                v-model="filters.dateFrom"
                                type="text"
                                onfocus="(this.type='date')"
                                onblur="if(!this.value)this.type='text'"
                                placeholder="dd / mm / yyyy"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                            />
                            <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                        </div>
                        <div class="relative">
                            <input
                                v-model="filters.dateTo"
                                type="text"
                                onfocus="(this.type='date')"
                                onblur="if(!this.value)this.type='text'"
                                placeholder="dd / mm / yyyy"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                            />
                            <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-w-0">
                <!-- Header & Controls -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                    <h2 class="text-xl font-bold text-gray-900">
                        Plant Sightings ({{ filteredPlants.length }})
                    </h2>

                    <!-- View Mode Selector -->
                    <div class="inline-flex items-center gap-1 bg-gray-100 rounded-full p-1.5 border border-gray-200">
                        <span class="px-3 text-sm font-medium text-gray-500">View</span>
                        <button
                            @click="viewMode = 'grid'"
                            class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                            :class="viewMode === 'grid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            Grid
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                            :class="viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            List
                        </button>
                        <button
                            @click="viewMode = 'map'"
                            class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                            :class="viewMode === 'map' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            Map
                        </button>
                    </div>
                </div>

                <!-- Mobile Filter Toggle Button -->
                <button
                    @click="mobileFiltersOpen = true"
                    class="lg:hidden mb-6 w-full flex items-center justify-center gap-2 p-3 bg-white border border-gray-200 rounded-lg text-gray-900 font-medium"
                >
                    <Filter class="w-4 h-4" />
                    Show Filters
                    <span v-if="activeFiltersCount > 0" class="bg-gray-900 text-white text-xs px-2 py-0.5 rounded-full">{{ activeFiltersCount }}</span>
                </button>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'">
                    <div v-if="paginatedPlants.length === 0" class="py-16 text-center">
                         <p class="text-gray-500">No sightings found matching your criteria.</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <div
                            v-for="plant in paginatedPlants"
                            :key="plant.id"
                            @click="selectPlant(plant)"
                            class="group bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                                <img
                                    :src="plant.path"
                                    :alt="plant.common_name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <!-- Badge -->
                                <div class="absolute top-3 right-3">
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium rounded shadow-sm"
                                        :class="getConservationStatusColor(plant.iucn_category)"
                                    >
                                        {{ getConservationStatusLabel(plant.iucn_category || 'DD') }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-base font-bold text-gray-900">
                                    {{ plant.common_name }}
                                </h3>
                                <p class="text-sm italic text-gray-500 mt-0.5">
                                    {{ plant.scientific_name }}
                                </p>
                                <div class="mt-3 space-y-1.5">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <MapPin class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <span>{{ plant.location_name || plant.region }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <Calendar class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <span>{{ formatDate(plant.sighted_at || plant.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else-if="viewMode === 'list'">
                    <div v-if="paginatedPlants.length === 0" class="py-16 text-center">
                        <p class="text-gray-500">No sightings found matching your criteria.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="plant in paginatedPlants"
                            :key="plant.id"
                            @click="selectPlant(plant)"
                            class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow cursor-pointer"
                        >
                            <div class="relative w-20 h-20 flex-shrink-0 rounded overflow-hidden bg-gray-100">
                                <img
                                    :src="plant.path"
                                    :alt="plant.common_name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-bold text-gray-900">
                                            {{ plant.common_name }}
                                        </h3>
                                        <p class="text-sm italic text-gray-500">
                                            {{ plant.scientific_name }}
                                        </p>
                                    </div>
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded shadow-sm flex-shrink-0"
                                        :class="getConservationStatusColor(plant.iucn_category)"
                                    >
                                        {{ getConservationStatusLabel(plant.iucn_category || 'DD') }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-1.5">
                                        <MapPin class="h-4 w-4 text-gray-400" />
                                        <span>{{ plant.location_name || plant.region }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        <span>{{ formatDate(plant.sighted_at || plant.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map View -->
                <div v-else-if="viewMode === 'map'" class="bg-white rounded-lg border border-gray-200 overflow-hidden h-[600px]">
                    <div v-if="filteredPlants.length === 0" class="h-full flex items-center justify-center">
                        <p class="text-gray-500">No sightings to display on map.</p>
                    </div>
                    <SightingMap
                        v-else
                        :sightings="sightingsForMap"
                        @marker-click="handleMarkerClick"
                    />
                </div>

                <!-- Pagination -->
                <div v-if="['grid', 'list'].includes(viewMode) && totalPages > 1" class="mt-8 flex items-center justify-center gap-2">
                    <button
                        @click="currentPage--"
                        :disabled="currentPage === 1"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Previous
                    </button>
                    <span class="px-4 py-2 text-sm font-medium text-gray-900">
                        Page {{ currentPage }} of {{ totalPages }}
                    </span>
                    <button
                        @click="currentPage++"
                        :disabled="currentPage === totalPages"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </main>
        </div>
      </div>

      <!-- Plant Details Modal -->
      <Dialog :open="!!selectedPlant" @update:open="selectedPlant = null">
        <DialogContent class="z-[9999] max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <span>{{ selectedPlant?.common_name }}</span>
              <span
                v-if="selectedPlant?.iucn_category"
                :class="getConservationStatusColor(selectedPlant.iucn_category)"
                class="px-2 py-0.5 text-xs rounded"
              >
                  {{ getConservationStatusLabel(selectedPlant.iucn_category) }}
              </span>
            </DialogTitle>
          </DialogHeader>
          <div v-if="selectedPlant" class="space-y-4">
            <div class="relative">
              <div class="relative h-64 overflow-hidden rounded-lg bg-gray-100">
                <img
                  :src="currentImage?.url || selectedPlant?.path"
                  :alt="selectedPlant?.common_name"
                  class="h-full w-full object-cover"
                />
                <div v-if="selectedPlant.images && selectedPlant.images.length > 1"
                     class="absolute top-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded">
                  {{ selectedImageIndex + 1 }} / {{ selectedPlant.images.length }}
                </div>
                <template v-if="selectedPlant.images && selectedPlant.images.length > 1">
                  <button
                    @click="prevImage"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition"
                  >
                    <ChevronLeft class="h-5 w-5" />
                  </button>
                  <button
                    @click="nextImage"
                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition"
                  >
                    <ChevronRight class="h-5 w-5" />
                  </button>
                </template>
              </div>
            </div>

            <div v-if="selectedPlant.user_name" class="flex items-center gap-2 text-sm text-gray-600">
              <Users class="h-4 w-4" />
              <span>Spotted by <span class="font-medium">{{ selectedPlant.user_name }}</span></span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-900">Scientific Name:</span>
                <p class="italic text-gray-600">{{ selectedPlant?.scientific_name }}</p>
              </div>
              <div>
                <span class="font-medium text-gray-900">Family:</span>
                <p class="text-gray-600">{{ selectedPlant?.family }}</p>
              </div>
              <div>
                <span class="font-medium text-gray-900">Conservation Status:</span>
                <p class="text-gray-600">{{ getConservationStatusLabel(selectedPlant?.iucn_category || "") }}</p>
              </div>
              <div>
                <span class="font-medium text-gray-900">Date Spotted:</span>
                <p class="text-gray-600">{{ formatDate(selectedPlant?.sighted_at || selectedPlant?.created_at || "") }}</p>
              </div>
            </div>
            <div>
              <span class="font-medium text-gray-900">Location:</span>
              <p class="text-gray-600">{{ selectedPlant?.location_name || selectedPlant?.region }}</p>
            </div>
            <div v-if="selectedPlant?.description">
              <span class="font-medium text-gray-900">Description:</span>
              <p class="mt-1 text-gray-600">{{ selectedPlant?.description }}</p>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
