<script setup lang="ts">
import { ref, computed } from "vue";
import { formatDate } from "@/utils";

// Direct component imports instead of using index.ts
import AppLayout from "@/layouts/AppLayout.vue";
import Icon from "@/components/Icon.vue";
import Pagination from "@/components/Pagination.vue";
import SightingMap from "@/components/SightingMap.vue"; // Import the new SightingMap component

// UI Components from shadcn/ui
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

// Add missing viewMode ref
const viewMode = ref<"grid" | "list" | "map">("grid"); // Add "map" to viewMode types

interface plantsResult {
  id: number;
  user_id: number;
  path: string;
  filename: string;
  mime_type: string;
  size: number;
  organ: string;
  scientific_name: string;
  scientific_name_without_author: string;
  common_name: string;
  family: string;
  genus: string;
  confidence: number;
  gbif_id: number;
  powo_id: string;
  iucn_category: string;
  region: string;
  latitude: number;
  longitude: number;
  created_at: string;
  updated_at: string;
}

// Dummy data following plantsResult interface
const dummyPlants: plantsResult[] = [
  {
    id: 1,
    user_id: 1,
    path: "/images/plant1.jpg",
    filename: "plant1.jpg",
    mime_type: "image/jpeg",
    size: 102400,
    organ: "leaf",
    scientific_name: "Quercus robur",
    scientific_name_without_author: "Quercus robur",
    common_name: "English Oak",
    family: "Fagaceae",
    genus: "Quercus",
    confidence: 0.95,
    gbif_id: 12345,
    powo_id: "urn:lsid:ipni.org:names:294000-1",
    iucn_category: "LC",
    region: "Selangor",
    latitude: 3.139,
    longitude: 101.6869,
    created_at: "2023-10-01T00:00:00Z",
    updated_at: "2023-10-01T00:00:00Z",
  },
  {
    id: 2,
    user_id: 2,
    path: "/images/plant2.jpg",
    filename: "plant2.jpg",
    mime_type: "image/jpeg",
    size: 204800,
    organ: "flower",
    scientific_name: "Rosa canina",
    scientific_name_without_author: "Rosa canina",
    common_name: "Dog Rose",
    family: "Rosaceae",
    genus: "Rosa",
    confidence: 0.88,
    gbif_id: 67890,
    powo_id: "urn:lsid:ipni.org:names:730000-1",
    iucn_category: "VU",
    region: "Johor",
    latitude: 1.4927,
    longitude: 103.7414,
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
  return plants.value.filter((plant) => {
    const matchesFamily =
      filters.value.family === "all" || plant.family === filters.value.family;
    const matchesStatus =
      filters.value.conservationStatus.length === 0 ||
      filters.value.conservationStatus.includes(plant.iucn_category);
    const matchesRegion =
      filters.value.region === "all" || plant.region === filters.value.region;
    const matchesDate =
      (!filters.value.dateFrom || plant.created_at >= filters.value.dateFrom) &&
      (!filters.value.dateTo || plant.created_at <= filters.value.dateTo);
    const matchesSearch =
      !filters.value.search ||
      plant.scientific_name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      plant.common_name.toLowerCase().includes(filters.value.search.toLowerCase());
    return (
      matchesFamily && matchesStatus && matchesRegion && matchesDate && matchesSearch
    );
  });
});

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
const selectPlant = (plant: plantsResult) => {
  console.log("Selected plant:", plant); // Add logging for debugging
  selectedPlant.value = plant;
};

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
    image: plant.path, // Assuming path is the image URL
    commonName: plant.common_name,
    scientificName: plant.scientific_name,
    conservationStatus: plant.iucn_category,
    location: plant.region,
    date: plant.created_at,
    latitude: plant.latitude,
    longitude: plant.longitude,
    family: plant.family,
    description: `Confidence: ${plant.confidence}`, // Optional description
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
        <div class="max-w-7xl mx-auto py-16 px-4 text-center">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Plant Sightings Map
          </h1>
          <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            Explore documented plant sightings across Malaysia. View locations,
            conservation status, and detailed information about flora discoveries from
            researchers and nature enthusiasts.
          </p>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row gap-8 max-w-7xl mx-auto py-10 px-4">
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0">
          <div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6 sticky top-8">
            <h2 class="text-xl font-bold flex items-center gap-2 mb-6">
              <Icon name="filter" class="w-6 h-6" /> Filters
            </h2>
            <!-- Plant Family -->
            <div class="mb-6">
              <label for="plantFamilyFilter" class="block text-sm font-medium mb-2"
                >Plant Family</label
              >
              <Select id="plantFamilyFilter" v-model="filters.family">
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
            <!-- Conservation Status -->
            <div class="mb-6">
              <label class="block text-sm font-medium mb-2">Conservation Status</label>
              <div class="space-y-2">
                <div
                  v-for="status in conservationStatuses"
                  :key="status.value"
                  class="flex items-center gap-2"
                >
                  <input
                    :id="'status-' + status.value"
                    v-model="filters.conservationStatus"
                    :value="status.value"
                    type="checkbox"
                    class="h-4 w-4 text-moss-600 border-gray-300 rounded"
                  />
                  <label
                    :for="'status-' + status.value"
                    class="flex items-center gap-2 text-sm"
                  >
                    <span
                      :class="status.color"
                      class="inline-block w-3 h-3 rounded-full"
                    ></span>
                    {{ status.label }}
                  </label>
                </div>
              </div>
            </div>
            <!-- Region -->
            <div class="mb-6">
              <label for="regionFilter" class="block text-sm font-medium mb-2"
                >State of Malaysia</label
              >
              <Select id="regionFilter" v-model="filters.region">
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
            <!-- Sighting Date -->
            <div class="mb-6">
              <label class="block text-sm font-medium mb-2">Sighting Date</label>
              <div class="flex gap-2">
                <input
                  v-model="filters.dateFrom"
                  type="date"
                  class="w-1/2 px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-800"
                  placeholder="From"
                />
                <input
                  v-model="filters.dateTo"
                  type="date"
                  class="w-1/2 px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-800"
                  placeholder="To"
                />
              </div>
            </div>
            <!-- Search -->
            <div class="mb-6">
              <label for="plantSearchInput" class="block text-sm font-medium mb-2"
                >Search Plants</label
              >
              <div class="relative">
                <Icon
                  name="search"
                  class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                />
                <input
                  id="plantSearchInput"
                  v-model="filters.search"
                  type="text"
                  placeholder="Scientific or common name..."
                  class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md dark:bg-gray-800"
                />
              </div>
            </div>
            <Button variant="outline" class="w-full" @click="clearFilters"
              >Clear Filters</Button
            >
          </div>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 flex flex-col gap-8">
          <!-- Sightings Card -->
          <div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-4">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold">
                Plant Sightings ({{ filteredPlants.length }}) - Current View:
                {{ viewMode }}
              </h2>
              <div class="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  @click="viewMode = 'grid'"
                  :class="
                    viewMode === 'grid'
                      ? 'bg-black text-white'
                      : 'text-gray-700 hover:bg-gray-50'
                  "
                >
                  <Icon name="grid" class="w-4 h-4" />
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  @click="viewMode = 'list'"
                  :class="
                    viewMode === 'list'
                      ? 'bg-black text-white'
                      : 'text-gray-700 hover:bg-gray-50'
                  "
                >
                  <Icon name="list" class="w-4 h-4" />
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  @click="viewMode = 'map'"
                  :class="
                    viewMode === 'map'
                      ? 'bg-black text-white'
                      : 'text-gray-700 hover:bg-gray-50'
                  "
                >
                  <Icon name="map" class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <!-- Grid View -->
            <div
              v-if="viewMode === 'grid'"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
            >
              <div
                v-for="plant in paginatedPlants"
                :key="plant.id"
                @click="selectPlant(plant)"
                class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow hover:shadow-lg border border-gray-200 dark:border-gray-700 cursor-pointer transition"
              >
                <div class="relative h-44 rounded-t-xl overflow-hidden">
                  <img
                    :src="plant.path"
                    :alt="plant.common_name"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute top-2 right-2">
                    <span
                      class="px-2 py-1 text-xs font-medium rounded-full"
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
                    <Icon name="map-pin" class="w-3 h-3" />
                    <span>{{ plant.region }}</span>
                  </div>
                  <div
                    class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                  >
                    <Icon name="calendar" class="w-3 h-3" />
                    <span>{{ formatDate(plant.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <!-- List View -->
            <div v-else-if="viewMode === 'list'" class="space-y-4">
              <div
                v-for="plant in paginatedPlants"
                :key="plant.id"
                @click="selectPlant(plant)"
                class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-lg cursor-pointer transition"
              >
                <div class="relative w-20 h-20 rounded-lg overflow-hidden flex-shrink-0">
                  <img
                    :src="plant.path"
                    :alt="plant.common_name"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute -top-1 -right-1">
                    <span
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getConservationStatusColor(plant.iucn_category)"
                    >
                      {{ plant.iucn_category }}
                    </span>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ plant.common_name }}
                  </h3>
                  <p class="text-sm italic text-gray-600 dark:text-gray-400">
                    {{ plant.scientific_name }}
                  </p>
                  <div
                    class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                  >
                    <Icon name="map-pin" class="w-3 h-3" />
                    <span>{{ plant.region }}</span>
                  </div>
                  <div
                    class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                  >
                    <Icon name="calendar" class="w-3 h-3" />
                    <span>{{ formatDate(plant.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Map View -->
            <div v-else-if="viewMode === 'map'" class="relative z-0">
              <div v-if="filteredPlants.length === 0" class="text-center py-8">
                <p class="text-gray-500">No sightings to display on map.</p>
              </div>
              <SightingMap
                v-else
                :sightings="sightingsForMap"
                @marker-click="handleMarkerClick"
              />
            </div>
            <!-- Pagination -->
            <div v-if="viewMode !== 'map'" class="flex justify-center mt-6">
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
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto z-[9999]">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <span>{{ selectedPlant?.common_name }}</span>
              <div
                :class="getConservationStatusColor(selectedPlant?.iuc_category || '')"
                class="w-3 h-3 rounded-full"
              ></div>
            </DialogTitle>
          </DialogHeader>
          <div v-if="selectedPlant" class="space-y-4">
            <div class="relative h-64 rounded-lg overflow-hidden">
              <img
                :src="selectedPlant?.path"
                :alt="selectedPlant?.common_name"
                class="w-full h-full object-cover"
              />
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
                  {{ formatDate(selectedPlant?.created_at || "") }}
                </p>
              </div>
            </div>
            <div>
              <span class="font-medium text-gray-900 dark:text-white">Location:</span>
              <p class="text-gray-600 dark:text-gray-400">
                {{ selectedPlant?.region }}
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
            <div>
              <span class="font-medium text-gray-900 dark:text-white">Description:</span>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                Confidence: {{ selectedPlant?.confidence }}
              </p>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
