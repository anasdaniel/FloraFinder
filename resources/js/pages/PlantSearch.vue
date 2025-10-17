<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  Search,
  Filter,
  ChevronDown,
  Grid3X3,
  List,
  MapPin,
  Leaf,
  TreePine,
  Flower,
  ChevronLeft,
  ChevronRight,
  SearchX,
  X,
} from "lucide-vue-next";

// Reactive state
const searchQuery = ref("");
const selectedFamily = ref("");
const selectedHabitat = ref("");
const selectedConservation = ref("");
const selectedRegion = ref("");
const selectedGrowthForm = ref("");
const selectedFlowering = ref("");
const showAdvancedFilters = ref(false);
const viewMode = ref("grid");
const sortBy = ref("name");
const currentPage = ref(1);
const itemsPerPage = ref(12);
const selectedPlant = ref(null);

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
  common_name: string | null;
  family: string;
  genus: string;
  confidence: number;
  gbif_id: number | null;
  powo_id: string | null;
  iucn_category: string | null;
  region: string | null;
  latitude: number | null;
  longitude: number | null;
  created_at: string;
  updated_at: string;
}

//define props
const props = defineProps<{
  plants?: plantsResult[];
}>();

// Filter options
const plantFamilies = ref([
  "Asteraceae",
  "Rosaceae",
  "Fabaceae",
  "Poaceae",
  "Lamiaceae",
  "Brassicaceae",
  "Apiaceae",
  "Ranunculaceae",
  "Caryophyllaceae",
  "Scrophulariaceae",
]);

const habitats = ref([
  "Forest",
  "Grassland",
  "Wetland",
  "Desert",
  "Mountains",
  "Coastal",
  "Scrubland",
  "Meadow",
  "Woodland",
  "Prairie",
]);

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

const regions = ref([
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

const growthForms = ref([
  "Tree",
  "Shrub",
  "Herb",
  "Grass",
  "Vine",
  "Fern",
  "Moss",
  "Succulent",
]);

const floweringSeasons = ref([
  "Spring",
  "Summer",
  "Fall",
  "Winter",
  "Year-round",
  "Varies",
]);

// Computed properties
const hasActiveFilters = computed(() => {
  return (
    searchQuery.value ||
    selectedFamily.value ||
    selectedConservation.value ||
    selectedRegion.value
  );
});

const filteredPlants = computed(() => {
  let plants = [...(props.plants || [])];

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    plants = plants.filter(
      (plant) =>
        plant.common_name?.toLowerCase().includes(query) ||
        false ||
        plant.scientific_name.toLowerCase().includes(query)
    );
  }

  // Apply family filter
  if (selectedFamily.value) {
    plants = plants.filter((plant) => plant.family === selectedFamily.value);
  }

  // Apply conservation status filter
  if (selectedConservation.value) {
    plants = plants.filter((plant) => plant.iucn_category === selectedConservation.value);
  }

  // Apply region filter
  if (selectedRegion.value) {
    plants = plants.filter((plant) => plant.region === selectedRegion.value);
  }

  // Apply sorting
  plants.sort((a, b) => {
    switch (sortBy.value) {
      case "name":
        const aName = a.common_name || a.scientific_name;
        const bName = b.common_name || b.scientific_name;
        return aName.localeCompare(bName);
      case "family":
        return a.family.localeCompare(b.family);
      case "conservation":
        const statusOrder = {
          NE: 0,
          DD: 0.5,
          LC: 1,
          NT: 2,
          VU: 3,
          EN: 4,
          CR: 5,
          EW: 6,
          EX: 7,
        };
        return (statusOrder[a.iucn_category] || 0) - (statusOrder[b.iucn_category] || 0);
      default:
        return 0;
    }
  });

  return plants;
});

const totalPages = computed(() =>
  Math.ceil(filteredPlants.value.length / itemsPerPage.value)
);

const paginatedPlants = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredPlants.value.slice(start, end);
});

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i);
      }
      pages.push("...");
      pages.push(total);
    } else if (current >= total - 3) {
      pages.push(1);
      pages.push("...");
      for (let i = total - 4; i <= total; i++) {
        pages.push(i);
      }
    } else {
      pages.push(1);
      pages.push("...");
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i);
      }
      pages.push("...");
      pages.push(total);
    }
  }

  return pages;
});

// Methods
const handleSearch = () => {
  currentPage.value = 1;
};

const handleSort = () => {
  currentPage.value = 1;
};

const clearFilters = () => {
  searchQuery.value = "";
  selectedFamily.value = "";
  selectedHabitat.value = "";
  selectedConservation.value = "";
  selectedRegion.value = "";
  selectedGrowthForm.value = "";
  selectedFlowering.value = "";
  currentPage.value = 1;
};

const getConservationStatusColor = (status) => {
  const colors = {
    NE: "bg-blue-100 text-blue-800",
    DD: "bg-gray-200 text-gray-700",
    LC: "bg-green-100 text-green-800",
    NT: "bg-yellow-100 text-yellow-800",
    VU: "bg-orange-100 text-orange-800",
    EN: "bg-red-100 text-red-800",
    CR: "bg-red-200 text-red-900",
    EW: "bg-gray-300 text-gray-900",
    EX: "bg-gray-100 text-gray-800",
  };
  return colors[status] || "bg-gray-100 text-gray-800";
};

const getConservationStatusLabel = (status) => {
  const labels = {
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
</script>

<template>
  <Head title="Plant Search" />

  <AppLayout>
    <div class="min-h-screen py-8 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-12 text-center">
          <h1 class="mb-4 text-4xl font-bold text-gray-900">Plant Database Search</h1>
          <p class="max-w-2xl mx-auto text-lg text-gray-600">
            Discover and explore our comprehensive plant database. Search by name, family,
            habitat, or conservation status.
          </p>
        </div>

        <!-- Search and Filter Section -->
        <div class="p-8 mb-8 bg-white shadow-xl rounded-2xl">
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Main Search Bar -->
            <div class="lg:col-span-6">
              <label for="search" class="block mb-2 text-sm font-medium text-gray-700">
                Search Plants
              </label>
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  id="search"
                  placeholder="Search by name or scientific name..."
                  class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                  @input="handleSearch"
                />
                <Search class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" />
              </div>
            </div>

            <!-- Family Filter -->
            <div class="lg:col-span-3">
              <label for="family" class="block mb-2 text-sm font-medium text-gray-700">
                Plant Family
              </label>
              <select
                v-model="selectedFamily"
                id="family"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                @change="handleSearch"
              >
                <option value="">All Families</option>
                <option v-for="family in plantFamilies" :key="family" :value="family">
                  {{ family }}
                </option>
              </select>
            </div>

            <!-- Conservation Status Filter -->
            <div class="lg:col-span-3">
              <label
                for="conservation"
                class="block mb-2 text-sm font-medium text-gray-700"
              >
                Conservation Status
              </label>
              <select
                v-model="selectedConservation"
                id="conservation"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                @change="handleSearch"
              >
                <option value="">All Statuses</option>
                <option
                  v-for="status in conservationStatuses"
                  :key="status.value"
                  :value="status.value"
                >
                  {{ status.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Advanced Filters Toggle -->
          <div class="mt-6">
            <button
              @click="showAdvancedFilters = !showAdvancedFilters"
              class="flex items-center font-medium text-black-600 hover:text-black-900"
            >
              <Filter class="w-4 h-4 mr-2" />
              Advanced Filters
              <ChevronDown
                class="w-4 h-4 ml-1 transition-transform duration-200"
                :class="{ 'rotate-180': showAdvancedFilters }"
              />
            </button>
          </div>

          <!-- Advanced Filters Panel -->
          <div v-if="showAdvancedFilters" class="pt-6 mt-6 border-t border-gray-200">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-1">
              <!-- Region Filter -->
              <div>
                <label for="region" class="block mb-2 text-sm font-medium text-gray-700">
                  State of Malaysia
                </label>
                <select
                  v-model="selectedRegion"
                  id="region"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                  @change="handleSearch"
                >
                  <option value="">All States</option>
                  <option v-for="region in regions" :key="region" :value="region">
                    {{ region }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Search Results Summary -->
        <div class="flex items-center justify-between mb-6">
          <div class="text-gray-600">
            Found
            <span class="font-semibold text-gray-900">{{ filteredPlants.length }}</span>
            plants
            <span v-if="hasActiveFilters" class="text-sm">
              (filtered from {{ (props.plants || []).length }} total)
            </span>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Sort Options -->
            <select
              v-model="sortBy"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
              @change="handleSort"
            >
              <option value="name">Sort by Name</option>
              <option value="family">Sort by Family</option>
              <option value="conservation">Sort by Conservation Status</option>
            </select>

            <!-- View Toggle -->
            <div class="flex overflow-hidden border border-gray-300 rounded-lg">
              <button
                @click="viewMode = 'grid'"
                class="px-3 py-2 text-sm font-medium transition-colors duration-200"
                :class="
                  viewMode === 'grid'
                    ? 'bg-black text-white'
                    : 'text-gray-700 hover:bg-gray-50'
                "
              >
                <Grid3X3 class="w-4 h-4" />
              </button>
              <button
                @click="viewMode = 'list'"
                class="px-3 py-2 text-sm font-medium transition-colors duration-200"
                :class="
                  viewMode === 'list'
                    ? 'bg-black text-white'
                    : 'text-gray-700 hover:bg-gray-50'
                "
              >
                <List class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="filteredPlants.length > 0">
          <!-- Grid View -->
          <div
            v-if="viewMode === 'grid'"
            class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          >
            <div
              v-for="plant in paginatedPlants"
              :key="plant.id"
              class="overflow-hidden transition-shadow duration-300 bg-white shadow-lg cursor-pointer rounded-xl hover:shadow-xl group"
              @click="selectedPlant = plant"
            >
              <div class="relative h-48 bg-gray-200">
                <img
                  :src="plant.path"
                  :alt="plant.common_name || plant.scientific_name"
                  class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                />
                <div class="absolute top-3 right-3">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded-full"
                    :class="getConservationStatusColor(plant.iucn_category)"
                  >
                    {{ getConservationStatusLabel(plant.iucn_category) }}
                  </span>
                </div>
              </div>

              <div class="p-4">
                <h3
                  class="mb-1 font-semibold text-gray-900 transition-colors group-hover:text-black"
                >
                  {{ plant.common_name || plant.scientific_name }}
                </h3>
                <p class="mb-2 text-sm italic text-gray-600">
                  {{ plant.scientific_name }}
                </p>
                <p class="mb-3 text-sm text-gray-500">{{ plant.family }}</p>

                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span class="flex items-center">
                    <MapPin class="w-3 h-3 mr-1" />
                    {{ plant.region }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- List View -->
          <div v-else class="overflow-hidden bg-white shadow-lg rounded-xl">
            <div class="divide-y divide-gray-200">
              <div
                v-for="plant in paginatedPlants"
                :key="plant.id"
                class="p-6 transition-colors duration-200 cursor-pointer hover:bg-gray-50"
                @click="selectedPlant = plant"
              >
                <div class="flex items-center space-x-4">
                  <img
                    :src="plant.path"
                    :alt="plant.common_name || plant.scientific_name"
                    class="object-cover w-16 h-16 rounded-lg"
                  />

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                      <div>
                        <h3
                          class="text-lg font-semibold text-gray-900 transition-colors hover:text-black"
                        >
                          {{ plant.common_name || plant.scientific_name }}
                        </h3>
                        <p class="text-sm italic text-gray-600">
                          {{ plant.scientific_name }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">{{ plant.family }}</p>
                      </div>

                      <span
                        class="px-3 py-1 text-xs font-medium rounded-full"
                        :class="getConservationStatusColor(plant.iucn_category)"
                      >
                        {{ getConservationStatusLabel(plant.iucn_category) }}
                      </span>
                    </div>

                    <div class="flex items-center mt-3 space-x-6 text-sm text-gray-500">
                      <span class="flex items-center">
                        <MapPin class="w-4 h-4 mr-1" />
                        {{ plant.region }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex justify-center mt-8">
            <nav class="flex items-center space-x-2">
              <button
                @click="currentPage = Math.max(1, currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in visiblePages"
                :key="page"
                @click="currentPage = page"
                class="px-3 py-2 text-sm font-medium transition-colors duration-200 rounded-md"
                :class="
                  page === currentPage
                    ? 'bg-black text-white'
                    : 'text-gray-700 hover:bg-gray-100'
                "
              >
                {{ page }}
              </button>

              <button
                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </nav>
          </div>
        </div>

        <!-- No Results -->
        <div v-else class="py-12 text-center">
          <SearchX class="w-16 h-16 mx-auto mb-4 text-gray-400" />
          <h3 class="mb-2 text-lg font-medium text-gray-900">No plants found</h3>
          <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
          <button
            @click="clearFilters"
            class="px-4 py-2 mt-4 text-white transition-colors duration-200 bg-black rounded-lg hover:bg-gray-800"
          >
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Plant Detail Modal -->
    <div
      v-if="selectedPlant"
      class="fixed inset-0 z-50 overflow-y-auto"
      @click="selectedPlant = null"
    >
      <div
        class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0"
      >
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        <div
          class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
          @click.stop
        >
          <div class="bg-white">
            <div class="relative">
              <img
                :src="selectedPlant.path"
                :alt="selectedPlant.common_name || selectedPlant.scientific_name"
                class="object-cover w-full h-64"
              />
              <button
                @click="selectedPlant = null"
                class="absolute p-2 transition-all duration-200 bg-white rounded-full top-4 right-4 bg-opacity-90 hover:bg-opacity-100"
              >
                <X class="w-6 h-6 text-gray-700" />
              </button>
            </div>

            <div class="p-6">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h2 class="mb-2 text-2xl font-bold text-gray-900">
                    {{ selectedPlant.common_name || selectedPlant.scientific_name }}
                  </h2>
                  <p class="text-lg italic text-gray-600">
                    {{ selectedPlant.scientific_name }}
                  </p>
                  <p class="text-gray-500">{{ selectedPlant.family }}</p>
                </div>
                <span
                  class="px-3 py-1 text-sm font-medium rounded-full"
                  :class="getConservationStatusColor(selectedPlant.iucn_category)"
                >
                  {{ getConservationStatusLabel(selectedPlant.iucn_category) }}
                </span>
              </div>

              <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
                <div>
                  <h3 class="mb-3 text-lg font-semibold text-gray-900">
                    Plant Information
                  </h3>
                  <div class="space-y-2">
                    <div class="flex items-center">
                      <MapPin class="w-5 h-5 mr-3 text-gray-400" />
                      <span class="text-gray-700">{{ selectedPlant.region }}</span>
                    </div>
                    <div class="flex items-center">
                      <span class="text-gray-700"
                        >Confidence: {{ selectedPlant.confidence }}</span
                      >
                    </div>
                  </div>
                </div>

                <div>
                  <h3 class="mb-3 text-lg font-semibold text-gray-900">
                    Additional Details
                  </h3>
                  <p class="leading-relaxed text-gray-700">
                    Genus: {{ selectedPlant.genus }}<br />
                    Organ: {{ selectedPlant.organ }}
                  </p>
                </div>
              </div>

              <div class="flex justify-end space-x-3">
                <button
                  @click="selectedPlant = null"
                  class="px-4 py-2 text-gray-700 transition-colors duration-200 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  Close
                </button>
                <button
                  class="px-4 py-2 text-white transition-colors duration-200 bg-black rounded-lg hover:bg-gray-800"
                >
                  View Full Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
