<template>
  <AppLayout title="Plant Map">
    <div
      class="min-h-screen bg-gradient-to-br from-sage-50 to-moss-50 dark:from-sage-950 dark:to-moss-950"
    >
      <!-- Header Section -->
      <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
          <div class="text-center">
            <h1
              class="text-3xl font-bold tracking-tight text-moss-900 dark:text-moss-100 sm:text-4xl"
            >
              <Icon name="map" class="inline w-8 h-8 mr-3" />
              Plant Sightings Map
            </h1>
            <p class="mt-3 text-lg leading-8 text-sage-600 dark:text-sage-400">
              Explore plant sightings across Malaysia on an interactive map
            </p>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="px-4 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
          <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-3">
              <Card class="sticky top-6">
                <CardHeader>
                  <CardTitle class="flex items-center gap-2">
                    <Icon name="filter" class="w-5 h-5" />
                    Filters
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                  <!-- Plant Family Filter -->
                  <div>
                    <label
                      for="plantFamilyFilter"
                      class="block text-sm font-medium text-sage-700 dark:text-sage-300 mb-2"
                    >
                      Plant Family
                    </label>
                    <Select id="plantFamilyFilter" v-model="filters.family">
                      <SelectTrigger>
                        <SelectValue placeholder="Select a family" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Families</SelectItem>
                        <SelectItem
                          v-for="family in plantFamilies"
                          :key="family"
                          :value="family"
                        >
                          {{ family }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Conservation Status Filter -->
                  <div>
                    <fieldset>
                      <legend
                        class="block text-sm font-medium text-sage-700 dark:text-sage-300 mb-2"
                      >
                        Conservation Status
                      </legend>
                      <div class="space-y-2">
                        <div
                          v-for="status in conservationStatuses"
                          :key="status.value"
                          class="flex items-center"
                        >
                          <input
                            :id="'status-' + status.value"
                            v-model="filters.conservationStatus"
                            :value="status.value"
                            type="checkbox"
                            class="h-4 w-4 text-moss-600 focus:ring-moss-500 border-gray-300 rounded"
                          />
                          <label
                            :for="'status-' + status.value"
                            class="ml-2 text-sm text-sage-700 dark:text-sage-300"
                          >
                            <span
                              :class="status.color"
                              class="inline-block w-3 h-3 rounded-full mr-2"
                            ></span>
                            {{ status.label }}
                          </label>
                        </div>
                      </div>
                    </fieldset>
                  </div>

                  <!-- Region Filter -->
                  <div>
                    <label
                      for="regionFilter"
                      class="block text-sm font-medium text-sage-700 dark:text-sage-300 mb-2"
                    >
                      Region
                    </label>
                    <Select id="regionFilter" v-model="filters.region">
                      <SelectTrigger>
                        <SelectValue placeholder="Select a region" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Regions</SelectItem>
                        <SelectItem
                          v-for="region in malaysianRegions"
                          :key="region"
                          :value="region"
                        >
                          {{ region }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Date Range Filter -->
                  <div>
                    <fieldset>
                      <legend
                        class="block text-sm font-medium text-sage-700 dark:text-sage-300 mb-2"
                      >
                        Sighting Date
                      </legend>
                      <div class="space-y-2">
                        <input
                          v-model="filters.dateFrom"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-moss-500 focus:border-moss-500 dark:bg-gray-700 dark:border-gray-600"
                          placeholder="From"
                          aria-label="Sighting date from"
                        />
                        <input
                          v-model="filters.dateTo"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-moss-500 focus:border-moss-500 dark:bg-gray-700 dark:border-gray-600"
                          placeholder="To"
                          aria-label="Sighting date to"
                        />
                      </div>
                    </fieldset>
                  </div>

                  <!-- Search -->
                  <div>
                    <label
                      for="plantSearchInput"
                      class="block text-sm font-medium text-sage-700 dark:text-sage-300 mb-2"
                    >
                      Search Plants
                    </label>
                    <div class="relative">
                      <Icon
                        name="search"
                        class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
                      />
                      <input
                        id="plantSearchInput"
                        v-model="filters.search"
                        type="text"
                        placeholder="Scientific or common name..."
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-moss-500 focus:border-moss-500 dark:bg-gray-700 dark:border-gray-600"
                      />
                    </div>
                  </div>

                  <!-- Clear Filters -->
                  <Button variant="outline" class="w-full" @click="clearFilters">
                    Clear Filters
                  </Button>
                </CardContent>
              </Card>
            </div>

            <!-- Map and Results -->
            <div class="lg:col-span-9 space-y-6">
              <!-- Map Container -->
              <Card>
                <CardContent class="p-0">
                  <div class="relative">
                    <!-- Map -->
                    <div
                      id="plant-map"
                      ref="mapContainer"
                      class="h-96 lg:h-[500px] w-full rounded-lg transition-opacity duration-200"
                      :class="{ 'opacity-30 pointer-events-none': selectedSighting }"
                    ></div>

                    <!-- Map Controls -->
                    <div
                      class="absolute top-4 right-4 flex flex-col gap-2"
                      :class="{ 'opacity-30 pointer-events-none': selectedSighting }"
                    >
                      <Button
                        size="sm"
                        variant="secondary"
                        @click="resetMapView"
                        class="bg-white/90 backdrop-blur-sm"
                      >
                        <Icon name="home" class="w-4 h-4" />
                      </Button>
                      <Button
                        size="sm"
                        variant="secondary"
                        @click="toggleClustering"
                        class="bg-white/90 backdrop-blur-sm"
                      >
                        <Icon
                          :name="clusteringEnabled ? 'ungroup' : 'group'"
                          class="w-4 h-4"
                        />
                      </Button>
                    </div>

                    <!-- Legend -->
                    <div
                      class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm rounded-lg p-3 text-sm"
                      :class="{ 'opacity-30 pointer-events-none': selectedSighting }"
                    >
                      <div class="font-semibold mb-2">Legend</div>
                      <div class="space-y-1">
                        <div
                          v-for="status in conservationStatuses"
                          :key="status.value"
                          class="flex items-center gap-2"
                        >
                          <div :class="status.color" class="w-3 h-3 rounded-full"></div>
                          <span>{{ status.label }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <!-- Results List -->
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center justify-between">
                    <span>Plant Sightings ({{ filteredSightings.length }})</span>
                    <div class="flex gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        @click="viewMode = 'grid'"
                        :class="{ 'bg-moss-100 dark:bg-moss-800': viewMode === 'grid' }"
                      >
                        <Icon name="grid-3x3" class="w-4 h-4" />
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        @click="viewMode = 'list'"
                        :class="{ 'bg-moss-100 dark:bg-moss-800': viewMode === 'list' }"
                      >
                        <Icon name="list" class="w-4 h-4" />
                      </Button>
                    </div>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <!-- Grid View -->
                  <div
                    v-if="viewMode === 'grid'"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                  >
                    <div
                      v-for="sighting in paginatedSightings"
                      :key="sighting.id"
                      @click="performSelectSighting(sighting)"
                      class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer"
                    >
                      <div class="relative h-48 overflow-hidden rounded-t-lg">
                        <img
                          :src="sighting.image"
                          :alt="sighting.commonName"
                          class="w-full h-full object-cover"
                        />
                        <div
                          :class="getConservationStatusColor(sighting.conservationStatus)"
                          class="absolute top-2 right-2 w-4 h-4 rounded-full border-2 border-white shadow-md"
                        ></div>
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

                  <!-- List View -->
                  <div v-else class="space-y-3">
                    <div
                      v-for="sighting in paginatedSightings"
                      :key="sighting.id"
                      @click="performSelectSighting(sighting)"
                      class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer"
                    >
                      <div
                        class="relative w-16 h-16 rounded-lg overflow-hidden flex-shrink-0"
                      >
                        <img
                          :src="sighting.image"
                          :alt="sighting.commonName"
                          class="w-full h-full object-cover"
                        />
                        <div
                          :class="getConservationStatusColor(sighting.conservationStatus)"
                          class="absolute -top-1 -right-1 w-3 h-3 rounded-full border border-white shadow-md"
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
                </CardContent>
              </Card>

              <!-- Pagination -->
              <div class="flex justify-center">
                <Pagination
                  :current-page="currentPage"
                  :total-pages="totalPages"
                  @page-changed="handlePageChange"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Plant Details Modal -->
      <Dialog :open="!!selectedSighting" @update:open="selectedSighting = null">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto z-[9999]">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <span>{{ selectedSighting?.commonName }}</span>
              <div
                :class="
                  getConservationStatusColor(selectedSighting?.conservationStatus || '')
                "
                class="w-3 h-3 rounded-full"
              ></div>
            </DialogTitle>
          </DialogHeader>

          <div v-if="selectedSighting" class="space-y-4">
            <!-- Plant Image -->
            <div class="relative h-64 rounded-lg overflow-hidden">
              <img
                :src="selectedSighting?.image"
                :alt="selectedSighting?.commonName"
                class="w-full h-full object-cover"
              />
            </div>

            <!-- Basic Info -->
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

            <!-- Location Info -->
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

            <!-- Description -->
            <div v-if="selectedSighting?.description">
              <span class="font-medium text-gray-900 dark:text-white">Description:</span>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ selectedSighting?.description }}
              </p>
            </div>

            <!-- Actions -->
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted, onMounted } from "vue"; // Added onMounted
import { useFilters, useMap, useSightings } from "@/composables";
import { formatDate } from "@/utils";

// Direct component imports instead of using index.ts
import AppLayout from "@/layouts/AppLayout.vue";
import Icon from "@/components/Icon.vue";
import Pagination from "@/components/Pagination.vue";

// UI Components from shadcn/ui
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
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
const viewMode = ref<"grid" | "list">("grid");

const filters = useFilters();
const {
  map,
  mapContainer,
  initializeMap, // Added initializeMap
  resetMapView,
  toggleClustering,
  clusteringEnabled,
  cleanup, // Added cleanup
  addSightingsToMap, // Added addSightingsToMap for future use if markers aren't showing
} = useMap();
const {
  plantFamilies,
  conservationStatuses,
  malaysianRegions,
  filteredSightings, // This is the ref we need to watch for marker updates
  paginatedSightings,
  currentPage,
  totalPages,
  handlePageChange,
  selectSighting: performSelectSighting, // Aliased to avoid conflict
  selectedSighting,
  clearFilters,
  // Ensure getConservationStatusColor, getConservationStatusLabel are available in this scope
  // (e.g., from useSightings, local definition, or utils)
  getConservationStatusColor,
  getConservationStatusLabel,
} = useSightings(filters);

watch(
  filters,
  () => {
    console.log("Filters changed, updating map markers with filteredSightings");
    addSightingsToMap(filteredSightings.value); // Call with the main filtered list
  },
  { deep: true }
);

// Watch for changes in filteredSightings to update the map
watch(filteredSightings, (newSightings) => {
  console.log("filteredSightings changed, updating map markers");
  addSightingsToMap(newSightings);
}, { immediate: true }); // immediate: true ensures it runs on component mount if data is ready

watch(paginatedSightings, (newPaginatedSightings) => {
  // This watcher might be for UI updates related to pagination, 
  // but map markers should generally reflect ALL filtered sightings, not just the paginated ones.
  // If you intend to only show paginated sightings on the map, this could be used, 
  // but typically users expect to see all filtered results on the map.
  console.log("Paginated sightings changed. Current map markers are based on all filtered sightings.");
  // If you only want to show paginated items on map: addSightingsToMap(newPaginatedSightings)
});

// Lifecycle
onMounted(() => {
  initializeMap(); // Call initializeMap when the component is mounted
  // Initially load sightings onto the map if available
  // watch(filteredSightings, (newSightings) => {
  //  addSightingsToMap(newSightings);
  // }, { immediate: true }); // immediate might be needed if sightings are loaded before map
});

onUnmounted(() => {
  cleanup(); // Use the cleanup function from the composable
  delete (window as any).selectSightingFromMap;
});
</script>
