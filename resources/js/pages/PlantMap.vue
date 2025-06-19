<script setup lang="ts">
import { ref, computed } from "vue";
import { useFilters, useSightings, type Sighting } from "@/composables"; // Import Sighting type
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

// Add sorting state
const sortOption = ref("date-desc"); // Default sort by date (newest first)

const filters = useFilters();

// Destructure from useSightings
const {
  plantFamilies,
  conservationStatuses,
  malaysianRegions,
  filteredSightings,
  paginatedSightings: unsortedPaginatedSightings,
  currentPage,
  totalPages,
  handlePageChange,
  selectSighting: performSelectSighting,
  selectedSighting,
  clearFilters,
  getConservationStatusColor,
  getConservationStatusLabel,
} = useSightings(filters);

// Create a computed property for sorted sightings
const sortedSightings = computed(() => {
  const sightings = [...unsortedPaginatedSightings.value];

  switch (sortOption.value) {
    case "name-asc":
      return sightings.sort((a, b) =>
        (a.commonName || "").localeCompare(b.commonName || "")
      );
    case "name-desc":
      return sightings.sort((a, b) =>
        (b.commonName || "").localeCompare(a.commonName || "")
      );
    case "date-asc":
      return sightings.sort(
        (a, b) => new Date(a.date).getTime() - new Date(b.date).getTime()
      );
    case "date-desc":
      return sightings.sort(
        (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()
      );
    case "status-asc": {
      // Helper function to get conservation status priority (lower is more endangered)
      const getPriority = (status: string) => {
        const priorities: Record<string, number> = {
          CR: 1,
          EN: 2,
          VU: 3,
          NT: 4,
          LC: 5,
          DD: 6,
          NE: 7,
        };
        return priorities[status] || 100;
      };
      return sightings.sort(
        (a, b) => getPriority(a.conservationStatus) - getPriority(b.conservationStatus)
      );
    }
    case "status-desc": {
      // Helper function to get conservation status priority (lower is more endangered)
      const getPriority = (status: string) => {
        const priorities: Record<string, number> = {
          CR: 1,
          EN: 2,
          VU: 3,
          NT: 4,
          LC: 5,
          DD: 6,
          NE: 7,
        };
        return priorities[status] || 100;
      };
      return sightings.sort(
        (a, b) => getPriority(b.conservationStatus) - getPriority(a.conservationStatus)
      );
    }
    default:
      return sightings;
  }
});

// Function to handle marker click from SightingMap
const handleMarkerClick = (sighting: Sighting) => {
  performSelectSighting(sighting);
};

const highlightedSighting = ref<Sighting | null>(null);

function showOnMap(sighting: Sighting) {
  highlightedSighting.value = sighting;
  selectedSighting.value = null; // Close modal
}
</script>

<template>
  <AppLayout title="Plant Sightings Map">
    <div class="min-h-screen bg-white dark:bg-gray-950">
      <!-- Page Header -->
      <div class="bg-white dark:bg-gray-900">
        <!-- removed: border-b border-gray-200 dark:border-gray-800 -->
        <div class="max-w-7xl mx-auto py-10 px-4 text-center">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Plant Sightings Map
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
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
                >Region</label
              >
              <Select id="regionFilter" v-model="filters.region">
                <SelectTrigger>
                  <SelectValue placeholder="All regions" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All regions</SelectItem>
                  <SelectItem
                    v-for="region in malaysianRegions"
                    :key="region"
                    :value="region"
                    >{{ region }}</SelectItem
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
          <!-- Combined Map and List View (Map above List) -->
          <div class="flex flex-col gap-6">
            <!-- Map Section -->
            <div class="w-full min-h-[400px]">
              <div v-if="filteredSightings.length === 0" class="text-center py-8">
                <p class="text-gray-500">No sightings to display on map.</p>
              </div>
              <SightingMap
                v-else
                :sightings="filteredSightings"
                :highlightSighting="highlightedSighting"
                @marker-click="handleMarkerClick"
              />
            </div>
            <!-- List/Grid Section -->
            <div class="w-full">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">
                  Plant Sightings ({{ filteredSightings.length }})
                </h2>
                <div class="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    @click="viewMode = 'grid'"
                    :class="{ 'bg-moss-100 dark:bg-moss-800': viewMode === 'grid' }"
                  >
                    Grid
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    @click="viewMode = 'list'"
                    :class="{ 'bg-moss-100 dark:bg-moss-800': viewMode === 'list' }"
                  >
                    List
                  </Button>
                </div>
              </div>
              <!-- Sort Option -->
              <div class="mb-4">
                <label for="sortOption" class="block text-sm font-medium mb-2"
                  >Sort By</label
                >
                <Select id="sortOption" v-model="sortOption">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="date-desc">Date: Newest First</SelectItem>
                    <SelectItem value="date-asc">Date: Oldest First</SelectItem>
                    <SelectItem value="name-asc">Name: A to Z</SelectItem>
                    <SelectItem value="name-desc">Name: Z to A</SelectItem>
                    <SelectItem value="status-asc">Status: Low to High Risk</SelectItem>
                    <SelectItem value="status-desc">Status: High to Low Risk</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <!-- List/Grid rendering (existing code) -->
              <div v-if="viewMode === 'grid'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  <div
                    v-for="sighting in sortedSightings"
                    :key="sighting.id"
                    @click="performSelectSighting(sighting)"
                    class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow hover:shadow-lg border border-gray-200 dark:border-gray-700 cursor-pointer transition m-0"
                  >
                    <div class="relative h-44 rounded-t-xl overflow-hidden">
                      <img
                        :src="sighting.image"
                        :alt="sighting.commonName"
                        class="w-full h-full object-cover"
                      />
                      <div
                        :class="[getConservationStatusColor(sighting.conservationStatus), 'text-white']"
                        class="absolute top-2 right-2 px-2 py-1 rounded-md text-xs font-medium shadow"
                      >
                        {{ sighting.conservationStatus }}
                      </div>
                    </div>
                    <div class="p-4">
                      <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ sighting.commonName }}
                      </h3>
                      <p class="text-sm italic text-gray-600 dark:text-gray-400">
                        {{ sighting.scientificName }}
                      </p>
                      <div
                        class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                      >
                        <Icon name="map-pin" class="w-3 h-3" />
                        <span>{{ sighting.location }}</span>
                      </div>
                      <div
                        class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                      >
                        <Icon name="calendar" class="w-3 h-3" />
                        <span>{{ formatDate(sighting.date) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <div
                    v-for="sighting in sortedSightings"
                    :key="sighting.id"
                    @click="performSelectSighting(sighting)"
                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-lg cursor-pointer transition"
                  >
                    <div class="relative w-20 h-20 rounded-lg overflow-hidden flex-shrink-0">
                      <img
                        :src="sighting.image"
                        :alt="sighting.commonName"
                        class="w-full h-full object-cover"
                      />
                      <div
                        :class="[getConservationStatusColor(sighting.conservationStatus), 'text-white']"
                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full border border-white shadow"
                      ></div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ sighting.commonName }}
                      </h3>
                      <p class="text-sm italic text-gray-600 dark:text-gray-400">
                        {{ sighting.scientificName }}
                      </p>
                      <div
                        class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                      >
                        <Icon name="map-pin" class="w-3 h-3" />
                        <span>{{ sighting.location }}</span>
                      </div>
                      <div
                        class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                      >
                        <Icon name="calendar" class="w-3 h-3" />
                        <span>{{ formatDate(sighting.date) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Pagination -->
              <div class="flex justify-center mt-6">
                <Pagination
                  :current-page="currentPage"
                  :total-pages="totalPages"
                  @page-changed="handlePageChange"
                />
              </div>
            </div>
          </div>
        </main>
      </div>
      <!-- Plant Details Modal -->
      <Dialog :open="!!selectedSighting" @update:open="selectedSighting = null">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto z-[9999]">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <span>{{ selectedSighting?.commonName }}</span>
            </DialogTitle>
          </DialogHeader>
          <div v-if="selectedSighting" class="space-y-4">
            <div class="relative h-64 rounded-lg overflow-hidden">
              <img
                :src="selectedSighting?.image"
                :alt="selectedSighting?.commonName"
                class="w-full h-full object-cover"
              />
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Scientific Name:</span
                >
                <p class="italic text-gray-600 dark:text-gray-400">
                  {{ selectedSighting?.scientificName }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white">Family:</span>
                <p class="text-gray-600 dark:text-gray-400">
                  {{ selectedSighting?.family }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Conservation Status:</span
                >
                <p class="text-gray-600 dark:text-gray-400">
                  {{
                    getConservationStatusLabel(selectedSighting?.conservationStatus || "")
                  }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-900 dark:text-white"
                  >Date Spotted:</span
                >
                <p class="text-gray-600 dark:text-gray-400">
                  {{ formatDate(selectedSighting?.date || "") }}
                </p>
              </div>
            </div>
            <div>
              <span class="font-medium text-gray-900 dark:text-white">Location:</span>
              <p class="text-gray-600 dark:text-gray-400">
                {{ selectedSighting?.location }}
              </p>
              <div class="mt-2 text-xs text-gray-500">
                Coordinates: {{ selectedSighting?.latitude?.toFixed(4) }},
                {{ selectedSighting?.longitude?.toFixed(4) }}
              </div>
            </div>
            <div v-if="selectedSighting?.description">
              <span class="font-medium text-gray-900 dark:text-white">Description:</span>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ selectedSighting?.description }}
              </p>
            </div>
            <div class="flex justify-end">
              <Button @click="() => { showOnMap(selectedSighting); selectedSighting = null; }">
                Show on the map
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
