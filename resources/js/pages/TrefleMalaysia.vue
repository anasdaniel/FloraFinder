<script setup lang="ts">
import { computed, ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";

interface plantsResult {
  id: number;
  common_name: string | null;
  slug: string;
  scientific_name: string;
  year: number | null;
  bibliography: string | null;
  author: string | null;
  status: string;
  rank: string;
  family_common_name: string | null;
  genus_id: number | null;
  image_url: string | null;
  synonyms: string[] | null;
  genus: string;
  family: string;
  links: {
    self: string;
    plant: string;
    genus: string;
  };
}

const props = defineProps<{
  plants?: plantsResult[];
  pagination?: Record<string, string>;
  currentPage?: number;
}>();

const plantsToDisplay = computed(() => props.plants ?? []);

const selectedPlant = ref<plantsResult | null>(null);
const showToast = ref(false);

const showPlantDetails = (plant: plantsResult) => {
  selectedPlant.value = plant;
  showToast.value = true;
};

const closeToast = () => {
  showToast.value = false;
  selectedPlant.value = null;
};

const paginationButtons = computed(() => {
  const result: {
    prev?: { page: number; url: string };
    next?: { page: number; url: string };
  } = {};

  if (props.pagination?.prev) {
    // Extract page number from prev URL
    const url = new URL(props.pagination.prev, "https://trefle.io");
    const prevPage = url.searchParams.get("page");
    if (prevPage) {
      result.prev = {
        page: parseInt(prevPage),
        url: `/trefle-malaysia?page=${prevPage}`,
      };
    }
  }

  if (props.pagination?.next) {
    // Extract page number from next URL
    const url = new URL(props.pagination.next, "https://trefle.io");
    const nextPage = url.searchParams.get("page");
    if (nextPage) {
      result.next = {
        page: parseInt(nextPage),
        url: `/trefle-malaysia?page=${nextPage}`,
      };
    }
  }

  return Object.keys(result).length > 0 ? result : null;
});
</script>
<template>
  <AppLayout>
    <div class="min-h-screen py-8 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-12 text-center">
          <h1 class="mb-4 text-4xl font-bold text-gray-900">Trefle Plant Database</h1>
          <p class="max-w-2xl mx-auto text-lg text-gray-600">
            Explore a vast collection of plant species from the Trefle database.
          </p>
          <div v-if="props.currentPage && props.currentPage > 1" class="mt-6">
            <span
              class="inline-flex items-center px-6 py-3 rounded-full text-xl font-bold bg-green-100 text-green-800 shadow-lg"
            >
              Page {{ props.currentPage }}
            </span>
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="plantsToDisplay.length > 0">
          <div
            class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          >
            <div
              v-for="plant in plantsToDisplay"
              :key="plant.id"
              class="overflow-hidden transition-shadow duration-300 bg-white shadow-lg rounded-xl hover:shadow-xl group cursor-pointer"
              @click="showPlantDetails(plant)"
            >
              <div class="relative h-48 bg-gray-200">
                <img
                  v-if="plant.image_url"
                  :src="plant.image_url"
                  :alt="plant.common_name || plant.scientific_name"
                  class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                  loading="lazy"
                />
                <div v-else class="flex items-center justify-center w-full h-full">
                  <span class="text-gray-500">No image available</span>
                </div>
                <div v-if="plant.status" class="absolute top-3 right-3">
                  <span
                    class="px-2 py-1 text-xs font-medium text-gray-800 bg-white rounded-full bg-opacity-90"
                  >
                    {{ plant.status }}
                  </span>
                </div>
              </div>

              <div class="p-4">
                <h3
                  class="mb-1 font-semibold text-gray-900 transition-colors group-hover:text-green-600"
                >
                  {{ plant.common_name || plant.scientific_name }}
                </h3>
                <p class="mb-2 text-sm italic text-gray-600">
                  {{ plant.scientific_name }}
                </p>
                <p class="mb-3 text-sm text-gray-500">
                  {{ plant.family_common_name || plant.family }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pagination Buttons -->
          <div
            v-if="paginationButtons"
            class="flex justify-center items-center mt-8 space-x-4"
          >
            <a
              v-if="paginationButtons.prev"
              :href="paginationButtons.prev.url"
              class="px-6 py-3 text-white transition-colors duration-200 bg-gray-900 rounded-lg shadow hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2"
            >
              ← Previous Page
            </a>

            <span
              class="px-6 py-3 text-white bg-gray-900 rounded-lg shadow cursor-default"
            >
              Page {{ props.currentPage || 1 }}
            </span>

            <a
              v-if="paginationButtons.next"
              :href="paginationButtons.next.url"
              class="px-6 py-3 text-white transition-colors duration-200 bg-gray-900 rounded-lg shadow hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2"
            >
              Next Page →
            </a>
          </div>
        </div>

        <!-- No Results -->
        <div v-else class="py-12 text-center">
          <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <h3 class="mb-2 text-lg font-medium text-gray-900">No plants found</h3>
          <p class="text-gray-500">Start exploring the Trefle plant database.</p>
        </div>
      </div>

      <!-- Plant Details Toast -->
      <div
        v-if="showToast && selectedPlant"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="closeToast"
      >
        <div
          class="max-w-md w-full mx-4 bg-white rounded-lg shadow-xl max-h-[80vh] overflow-y-auto"
          @click.stop
        >
          <div class="p-6">
            <div class="flex items-start justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ selectedPlant.common_name || selectedPlant.scientific_name }}
              </h3>
              <button @click="closeToast" class="text-gray-400 hover:text-gray-600">
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  ></path>
                </svg>
              </button>
            </div>

            <div class="space-y-3 text-sm text-gray-600">
              <p><strong>Scientific Name:</strong> {{ selectedPlant.scientific_name }}</p>
              <p>
                <strong>Family:</strong>
                {{ selectedPlant.family_common_name || selectedPlant.family }}
              </p>
              <p><strong>ID:</strong> {{ selectedPlant.id }}</p>
              <p v-if="selectedPlant.slug">
                <strong>Slug:</strong> {{ selectedPlant.slug }}
              </p>
              <p v-if="selectedPlant.year">
                <strong>Year:</strong> {{ selectedPlant.year }}
              </p>
              <p v-if="selectedPlant.author">
                <strong>Author:</strong> {{ selectedPlant.author }}
              </p>
              <p v-if="selectedPlant.rank">
                <strong>Rank:</strong> {{ selectedPlant.rank }}
              </p>
              <p><strong>Genus:</strong> {{ selectedPlant.genus }}</p>
              <p v-if="selectedPlant.genus_id">
                <strong>Genus ID:</strong> {{ selectedPlant.genus_id }}
              </p>
              <p v-if="selectedPlant.bibliography" class="break-words">
                <strong>Bibliography:</strong> {{ selectedPlant.bibliography }}
              </p>

              <div
                v-if="selectedPlant.synonyms && selectedPlant.synonyms.length > 0"
                class="pt-2"
              >
                <strong>Synonyms:</strong>
                <ul class="pl-4 mt-1 list-disc">
                  <li
                    v-for="(synonym, index) in selectedPlant.synonyms"
                    :key="index"
                    class="break-words"
                  >
                    {{ synonym }}
                  </li>
                </ul>
              </div>

              <div v-if="selectedPlant.links" class="pt-2 border-t border-gray-100">
                <strong>Links:</strong>
                <ul class="pl-4 mt-1 space-y-1">
                  <li>
                    <a
                      :href="selectedPlant.links.self"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      Self
                    </a>
                  </li>
                  <li>
                    <a
                      :href="selectedPlant.links.plant"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      Plant
                    </a>
                  </li>
                  <li>
                    <a
                      :href="selectedPlant.links.genus"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      Genus
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
