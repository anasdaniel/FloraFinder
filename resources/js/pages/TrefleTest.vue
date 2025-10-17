<script setup lang="ts">
import { ref, onMounted } from "vue";
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
}>();

const expandedSynonyms = ref<Set<number>>(new Set());
const expandedPlants = ref<Set<number>>(new Set());

const toggleSynonyms = (plantId: number) => {
  if (expandedSynonyms.value.has(plantId)) {
    expandedSynonyms.value.delete(plantId);
  } else {
    expandedSynonyms.value.add(plantId);
  }
};

const togglePlantDetails = (plantId: number) => {
  if (expandedPlants.value.has(plantId)) {
    expandedPlants.value.delete(plantId);
  } else {
    expandedPlants.value.add(plantId);
  }
};

const getVisibleSynonyms = (plant: plantsResult) => {
  if (!plant.synonyms) return [];
  if (expandedSynonyms.value.has(plant.id)) {
    return plant.synonyms;
  }
  return plant.synonyms.slice(0, 3);
};

const loadMore = () => {
  // TODO: Implement load more functionality
  console.log("Load more clicked");
};
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
        </div>

        <!-- Search Results -->
        <div v-if="plants && plants.length > 0">
          <div
            class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          >
            <div
              v-for="plant in plants"
              :key="plant.id"
              class="overflow-hidden transition-shadow duration-300 bg-white shadow-lg rounded-xl hover:shadow-xl group"
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

                <!-- Expandable Details Section -->
                <div class="pt-3 mt-3 border-t border-gray-100">
                  <button
                    @click.stop="togglePlantDetails(plant.id)"
                    class="flex items-center justify-between w-full text-sm font-medium text-gray-700 hover:text-green-600"
                  >
                    <span>View Details</span>
                    <svg
                      class="w-4 h-4 transition-transform duration-200"
                      :class="{ 'rotate-180': expandedPlants.has(plant.id) }"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                      />
                    </svg>
                  </button>

                  <div
                    v-if="expandedPlants.has(plant.id)"
                    class="mt-3 space-y-2 text-xs text-gray-600"
                  >
                    <p><strong>ID:</strong> {{ plant.id }}</p>
                    <p v-if="plant.slug"><strong>Slug:</strong> {{ plant.slug }}</p>
                    <p v-if="plant.year"><strong>Year:</strong> {{ plant.year }}</p>
                    <p v-if="plant.author"><strong>Author:</strong> {{ plant.author }}</p>
                    <p v-if="plant.rank"><strong>Rank:</strong> {{ plant.rank }}</p>
                    <p><strong>Genus:</strong> {{ plant.genus }}</p>
                    <p v-if="plant.genus_id">
                      <strong>Genus ID:</strong> {{ plant.genus_id }}
                    </p>
                    <p v-if="plant.bibliography" class="text-xs break-words">
                      <strong>Bibliography:</strong> {{ plant.bibliography }}
                    </p>

                    <div v-if="plant.synonyms && plant.synonyms.length > 0" class="pt-2">
                      <strong>Synonyms:</strong>
                      <ul class="pl-4 mt-1 list-disc">
                        <li
                          v-for="(synonym, index) in getVisibleSynonyms(plant)"
                          :key="index"
                          class="break-words"
                        >
                          {{ synonym }}
                        </li>
                      </ul>
                      <button
                        v-if="plant.synonyms.length > 3"
                        @click.stop="toggleSynonyms(plant.id)"
                        class="mt-2 text-xs text-blue-600 hover:underline focus:outline-none"
                      >
                        {{
                          expandedSynonyms.has(plant.id)
                            ? "Show less"
                            : `Show more (${plant.synonyms.length - 3} more)`
                        }}
                      </button>
                    </div>

                    <div v-if="plant.links" class="pt-2 mt-2 border-t border-gray-100">
                      <strong>Links:</strong>
                      <ul class="pl-4 mt-1 space-y-1">
                        <li>
                          <a
                            :href="plant.links.self"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                          >
                            Self
                          </a>
                        </li>
                        <li>
                          <a
                            :href="plant.links.plant"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                          >
                            Plant
                          </a>
                        </li>
                        <li>
                          <a
                            :href="plant.links.genus"
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

          <!-- Load More Button -->
          <div class="flex justify-center mt-8">
            <button
              @click="loadMore"
              class="px-6 py-3 text-white transition-colors duration-200 bg-gray-900 rounded-lg shadow hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2"
            >
              Load More Plants
            </button>
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
    </div>
  </AppLayout>
</template>
