<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import type { CareSource, PlantResult } from "@/types/plant-identifier";

type PlantMatch = NonNullable<NonNullable<PlantResult["data"]>["results"]>[number];

type CareDetails = Record<string, unknown> | null;

type FormatCareValue = (
  value: string | number | null | undefined,
  fallback?: string
) => string;
type FormatRange = (
  min?: number | string | null,
  max?: number | string | null,
  unit?: string
) => string;

const props = defineProps<{
  selectedResult: PlantMatch;
  activeImageIndex: number;
  careDetails: CareDetails;
  careSource: CareSource;
  preferredProvider: 'gemini' | 'trefle';
  fetchingCareDetails: boolean;
  plantDescription: string;
  descriptionLoading: boolean;
  isCurrentResultBookmarked: boolean;
  isGeminiCare: boolean;
  hasCareData: boolean;
  formatCareValue: FormatCareValue;
  formatRange: FormatRange;
  setActiveImage: (index: number) => void;
  toggleBookmark: () => void;
  fetchCareDetails: (scientificName: string, forceRefresh?: boolean) => void;
  switchProvider: (provider: 'gemini' | 'trefle') => void;
  openSaveModal: () => void;
}>();
</script>

<template>
  <div
    v-if="props.selectedResult"
    class="overflow-hidden bg-white shadow-xl rounded-3xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
  >
    <div
      class="relative h-48 overflow-hidden bg-gradient-to-r from-green-800 to-teal-900 md:h-64"
    >
      <img
        v-if="props.selectedResult.images?.length"
        :src="props.selectedResult.images[0].url.m"
        class="absolute inset-0 object-cover w-full h-full scale-110 opacity-30 mix-blend-overlay blur-sm"
      />
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"
      ></div>
      <div class="absolute bottom-0 left-0 w-full p-6 text-white md:p-8">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span
              class="px-3 py-1 text-xs font-bold rounded-full shadow-sm bg-green-500/90 backdrop-blur-md"
            >
              {{ Math.round((props.selectedResult.score || 0) * 100) }}% Match
            </span>
            <span
              v-if="props.selectedResult.iucn?.category"
              class="px-3 py-1 text-xs font-bold rounded-full shadow-sm bg-amber-500/90 backdrop-blur-md"
            >
              IUCN: {{ props.selectedResult.iucn.category }}
            </span>
          </div>
          <button
            @click="props.toggleBookmark()"
            class="p-2 transition-colors rounded-full bg-white/10 backdrop-blur-md hover:bg-white/20"
          >
            <Icon
              name="bookmark"
              :class="`h-5 w-5 ${
                props.isCurrentResultBookmarked
                  ? 'fill-yellow-400 text-yellow-400'
                  : 'text-white'
              }`"
            />
          </button>
        </div>
        <h1
          class="mb-1 text-3xl font-bold leading-tight tracking-tight text-white shadow-sm md:text-5xl"
        >
          {{ props.selectedResult.species.commonNames?.[0] || "Unknown Species" }}
        </h1>
        <p class="font-serif text-lg italic text-green-100 opacity-90 md:text-xl">
          {{ props.selectedResult.species.scientificName }}
        </p>
      </div>
    </div>
    <div
      class="grid grid-cols-1 gap-0 divide-y dark:divide-gray-800 md:grid-cols-12 md:divide-x md:divide-y-0"
    >
      <div class="p-6 bg-gray-50 dark:bg-gray-800/30 md:col-span-5">
        <div
          class="group relative mb-4 aspect-[4/3] overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 dark:bg-gray-900"
        >
          <img
            v-if="props.selectedResult.images?.length"
            :src="props.selectedResult.images[props.activeImageIndex].url.m"
            class="object-cover w-full h-full transition-transform duration-700 hover:scale-105"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-400">
            <Icon name="image-off" class="w-10 h-10" />
          </div>
        </div>
        <div
          v-if="props.selectedResult.images && props.selectedResult.images.length > 1"
          class="flex gap-2 pb-2 overflow-x-auto scrollbar-hide"
        >
          <button
            v-for="(img, idx) in props.selectedResult.images"
            :key="idx"
            @click="props.setActiveImage(idx)"
            :class="[
              'h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border-2 transition-all',
              props.activeImageIndex === idx
                ? 'border-green-500 ring-1 ring-green-500'
                : 'border-transparent opacity-70 hover:opacity-100',
            ]"
          >
            <img :src="img.url.s" class="object-cover w-full h-full" />
          </button>
        </div>
        <div
          class="p-4 mt-6 text-sm leading-relaxed bg-white border border-gray-200 rounded-2xl dark:border-gray-700 dark:bg-gray-900"
        >
          <p class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">
            Plant Description
          </p>
          <p v-if="props.descriptionLoading" class="text-gray-400">
            Fetching plant overview…
          </p>
          <p
            v-else-if="props.plantDescription"
            class="text-justify text-gray-700 dark:text-gray-200"
          >
            {{ props.plantDescription }}
          </p>
          <p v-else class="text-gray-400">Description unavailable.</p>
          <p
            v-if="!props.descriptionLoading && props.plantDescription"
            class="mt-2 text-[11px] text-gray-400 dark:text-gray-500"
          >
            AI-generated overview—may contain inaccuracies.
          </p>
        </div>
        <div class="pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
          <p class="mb-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            External Databases
          </p>
          <div class="flex flex-wrap gap-2">
            <a
              v-if="props.selectedResult.gbif?.id"
              :href="`https://www.gbif.org/species/${props.selectedResult.gbif.id}`"
              target="_blank"
              class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
              GBIF
              <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50" />
            </a>
            <a
              v-if="props.selectedResult.powo?.id"
              :href="`http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:${props.selectedResult.powo.id}`"
              target="_blank"
              class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
              POWO
              <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50" />
            </a>
          </div>
        </div>
      </div>
      <div class="p-6 space-y-8 md:col-span-7 md:p-8">
        <div class="grid grid-cols-2 gap-4">
          <div
            class="p-4 border border-green-100 rounded-xl bg-green-50 dark:border-green-800/50 dark:bg-green-900/20"
          >
            <div class="flex items-center gap-2 mb-1">
              <Icon name="git-merge" class="w-4 h-4 text-green-600 dark:text-green-400" />
              <span
                class="text-xs font-bold tracking-wider text-green-700 uppercase dark:text-green-300"
                >Family</span
              >
            </div>
            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
              {{ props.selectedResult.species.family.scientificNameWithoutAuthor }}
            </div>
          </div>
          <div
            class="p-4 border border-green-100 rounded-xl bg-green-50 dark:border-green-800/50 dark:bg-green-900/20"
          >
            <div class="flex items-center gap-2 mb-1">
              <Icon
                name="git-branch"
                class="w-4 h-4 text-green-600 dark:text-green-400"
              />
              <span
                class="text-xs font-bold tracking-wider text-green-700 uppercase dark:text-green-300"
                >Genus</span
              >
            </div>
            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
              {{ props.selectedResult.species.genus.scientificNameWithoutAuthor }}
            </div>
          </div>
        </div>
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3
              class="flex items-center text-lg font-normal text-gray-900 dark:text-white"
            >
              <div
                class="mr-3 rounded-lg bg-green-100 p-1.5 text-green-700 dark:bg-green-900 dark:text-green-300"
              >
                <Icon name="sprout" class="w-5 h-5" />
              </div>
              Care Essentials
              <span
                v-if="props.isGeminiCare"
                class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300"
              >
                <Icon name="sparkles" class="w-3 h-3" /> AI Generated
              </span>
              <span
                v-else-if="props.careSource === 'trefle'"
                class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
              >
                <Icon name="database" class="w-3 h-3" /> Trefle
              </span>
            </h3>
            <!-- Provider Toggle -->
            <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg dark:bg-gray-800">
              <button
                @click="props.switchProvider('gemini')"
                :class="[
                  'flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                  props.preferredProvider === 'gemini'
                    ? 'bg-white text-purple-700 shadow-sm dark:bg-gray-700 dark:text-purple-300'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                ]"
                :disabled="props.fetchingCareDetails"
              >
                <Icon name="sparkles" class="w-3 h-3" />
                Gemini
              </button>
              <button
                @click="props.switchProvider('trefle')"
                :class="[
                  'flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                  props.preferredProvider === 'trefle'
                    ? 'bg-white text-green-700 shadow-sm dark:bg-gray-700 dark:text-green-300'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                ]"
                :disabled="props.fetchingCareDetails"
              >
                <Icon name="database" class="w-3 h-3" />
                Trefle
              </button>
            </div>
          </div>
          <div class="overflow-hidden rounded-2xl dark:border-gray-700 dark:bg-gray-800">
            <div
              v-if="props.fetchingCareDetails"
              class="flex flex-col items-center justify-center p-8 text-gray-400"
            >
              <Icon name="loader-2" class="w-6 h-6 mb-2 animate-spin" />
              <span class="text-xs">Consulting botanist notes...</span>
            </div>
            <template v-else-if="props.careDetails">
              <div
                v-if="props.isGeminiCare && props.hasCareData"
                class="p-5 border rounded-2xl border-gray-200 bg-gradient-to-br from-gray-50 to-white dark:border-gray-700 dark:from-gray-800/50 dark:to-gray-900"
              >
                <div
                  v-if="props.careDetails?.care_summary"
                  class="mb-5 pb-4 border-b border-gray-200 dark:border-gray-700"
                >
                  <div class="flex items-center gap-2 mb-2">
                    <Icon name="sparkles" class="w-4 h-4 text-purple-500" />
                    <span class="text-sm font-normal text-purple-600 dark:text-purple-400"
                      >Care Summary</span
                    >
                  </div>
                  <p
                    class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 text-justify"
                  >
                    {{ props.careDetails.care_summary as string }}
                  </p>
                </div>
                <ul class="space-y-4">
                  <li v-if="props.careDetails?.watering_guide" class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                      <div
                        class="p-2 rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                      >
                        <Icon name="droplets" class="w-4 h-4" />
                      </div>
                    </div>
                    <div>
                      <span
                        class="block text-sm font-normal text-blue-600 dark:text-blue-400 mb-1"
                        >Watering</span
                      >
                      <p
                        class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 text-justify"
                      >
                        {{ props.careDetails.watering_guide as string }}
                      </p>
                    </div>
                  </li>
                  <li v-if="props.careDetails?.sunlight_guide" class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                      <div
                        class="p-2 rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                      >
                        <Icon name="sun" class="w-4 h-4" />
                      </div>
                    </div>
                    <div>
                      <span
                        class="block text-sm font-normal text-amber-600 dark:text-amber-400 mb-1"
                        >Sunlight</span
                      >
                      <p
                        class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 text-justify"
                      >
                        {{ props.careDetails.sunlight_guide as string }}
                      </p>
                    </div>
                  </li>
                  <li v-if="props.careDetails?.soil_guide" class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                      <div
                        class="p-2 rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400"
                      >
                        <Icon name="layers" class="w-4 h-4" />
                      </div>
                    </div>
                    <div>
                      <span
                        class="block text-sm font-normal text-emerald-600 dark:text-emerald-400 mb-1"
                        >Soil</span
                      >
                      <p
                        class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 text-justify"
                      >
                        {{ props.careDetails.watering_guide as string }}
                      </p>
                    </div>
                  </li>
                  <li v-if="props.careDetails?.temperature_guide" class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                      <div
                        class="p-2 rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400"
                      >
                        <Icon name="thermometer" class="w-4 h-4" />
                      </div>
                    </div>
                    <div>
                      <span
                        class="block text-sm font-normal text-rose-600 dark:text-rose-400 mb-1"
                        >Temperature</span
                      >
                      <p
                        class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 text-justify"
                      >
                        {{ props.careDetails.temperature_guide as string }}
                      </p>
                    </div>
                  </li>
                </ul>
                <div
                  class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg"
                >
                  <p
                    class="flex items-start gap-2 text-xs text-purple-700 dark:text-purple-300"
                  >
                    <Icon name="info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <span>
                      AI Generated Content: This care information is generated by
                      artificial intelligence and may contain inaccuracies. Always verify
                      with reliable botanical sources before making care decisions.
                    </span>
                  </p>
                </div>
              </div>
              <div
                v-else-if="props.hasCareData"
                class="grid grid-cols-1 gap-4 auto-rows-fr sm:grid-cols-2"
              >
                <div
                  class="relative h-full p-4 overflow-hidden border border-blue-100 shadow-sm rounded-2xl bg-gradient-to-br from-blue-50 to-white dark:border-blue-900/40 dark:from-blue-950/40 dark:to-gray-900"
                >
                  <div
                    class="absolute inset-y-0 right-0 w-24 bg-blue-100/50 blur-3xl dark:bg-blue-500/10"
                  ></div>
                  <div class="relative flex items-start h-full gap-3">
                    <div
                      class="flex-shrink-0 p-3 text-blue-500 shadow-inner rounded-2xl bg-white/80 ring-1 ring-white/70 dark:bg-blue-900/40 dark:text-blue-300"
                    >
                      <Icon name="droplets" class="w-5 h-5" />
                    </div>
                    <div>
                      <span
                        class="block text-[11px] font-normal uppercase tracking-widest text-blue-600 dark:text-blue-200"
                        >Water</span
                      >
                      <p class="text-lg font-normal text-gray-900 dark:text-gray-100">
                        {{ props.formatRange(
                          props.careDetails?.minimum_precipitation as number,
                          props.careDetails?.maximum_precipitation as number,
                          'mm/mo'
                        ) }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{
                          props.formatCareValue(
                            null,
                            "Keep soil evenly moist when active."
                          )
                        }}
                      </p>
                    </div>
                  </div>
                </div>
                <div
                  class="relative h-full p-4 overflow-hidden border shadow-sm rounded-2xl border-amber-100 bg-gradient-to-br from-amber-50 to-white dark:border-amber-900/40 dark:from-amber-950/40 dark:to-gray-900"
                >
                  <div
                    class="absolute inset-y-0 right-0 w-24 bg-amber-100/40 blur-3xl dark:bg-amber-500/10"
                  ></div>
                  <div class="relative flex items-start h-full gap-3">
                    <div
                      class="flex-shrink-0 p-3 shadow-inner rounded-2xl bg-white/80 text-amber-500 ring-1 ring-white/70 dark:bg-amber-900/30"
                    >
                      <Icon name="sun" class="w-5 h-5" />
                    </div>
                    <div>
                      <span
                        class="block text-[11px] font-normal uppercase tracking-widest text-amber-600 dark:text-amber-200"
                        >Sunlight</span
                      >
                      <p class="text-lg font-normal text-gray-900 dark:text-gray-100">
                        {{ props.formatCareValue(props.careDetails?.light as string, 'Light requirement not documented') }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Match exposure to native habitat.
                      </p>
                    </div>
                  </div>
                </div>
                <div
                  class="relative h-full p-4 overflow-hidden border shadow-sm rounded-2xl border-emerald-100 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-gray-900"
                >
                  <div
                    class="absolute inset-y-0 right-0 w-24 bg-emerald-100/40 blur-3xl dark:bg-emerald-500/10"
                  ></div>
                  <div class="relative flex items-start h-full gap-3">
                    <div
                      class="flex-shrink-0 p-3 shadow-inner rounded-2xl bg-white/80 text-emerald-600 ring-1 ring-white/70 dark:bg-emerald-900/30"
                    >
                      <Icon name="layers" class="w-5 h-5" />
                    </div>
                    <div class="flex-1">
                      <span
                        class="block text-[11px] font-normal uppercase tracking-widest text-emerald-700 dark:text-emerald-200"
                        >Soil</span
                      >
                      <p class="text-lg font-normal text-gray-900 dark:text-gray-100">
                        {{ props.formatCareValue(props.careDetails?.soil_texture as string, 'Well-draining mix preferred') }}
                      </p>
                      <div class="flex flex-wrap gap-1.5 mt-2">
                        <span
                          class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-normal uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                          >Salinity:
                          {{ props.formatCareValue(props.careDetails?.soil_salinity as string, 'n/a') }}</span
                        >
                        <span
                          class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-normal uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                          >Nutriments:
                          {{ props.formatCareValue(props.careDetails?.soil_nutriments as string, 'n/a') }}</span
                        >
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  class="relative h-full p-4 overflow-hidden border shadow-sm rounded-2xl border-rose-100 bg-gradient-to-br from-rose-50 to-white dark:border-rose-900/40 dark:from-rose-950/40 dark:to-gray-900"
                >
                  <div
                    class="absolute inset-y-0 right-0 w-24 bg-rose-100/40 blur-3xl dark:bg-rose-500/10"
                  ></div>
                  <div class="relative flex items-start h-full gap-3">
                    <div
                      class="flex-shrink-0 p-3 shadow-inner rounded-2xl bg-white/80 text-rose-500 ring-1 ring-white/70 dark:bg-rose-900/30"
                    >
                      <Icon name="thermometer" class="w-5 h-5" />
                    </div>
                    <div>
                      <span
                        class="block text-[11px] font-normal uppercase tracking-widest text-rose-600 dark:text-rose-200"
                        >Temperature</span
                      >
                      <p class="text-lg font-normal text-gray-900 dark:text-gray-100">
                        {{
                          props.formatRange(
                            props.careDetails?.minimum_temperature_celcius as number,
                            props.careDetails?.maximum_temperature_celcius as number,
                            '°C'
                          )
                        }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Protect from sudden cold snaps.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="p-6 text-center">
                <div
                  class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800"
                >
                  <Icon name="leaf" class="w-6 h-6 text-gray-400" />
                </div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Care guidance not available
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Detailed care info for this species isn't in our database yet.
                </p>
                <button
                  @click="
                    props.fetchCareDetails(props.selectedResult.species.scientificName)
                  "
                  :disabled="props.fetchingCareDetails"
                  class="inline-flex items-center gap-2 px-4 py-2 mt-4 text-xs font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                  <Icon
                    name="refresh-cw"
                    :class="`w-3.5 h-3.5 ${
                      props.fetchingCareDetails ? 'animate-spin' : ''
                    }`"
                  />
                  {{ props.fetchingCareDetails ? "Searching..." : "Try Again" }}
                </button>
              </div>
            </template>
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <Button
            @click="props.openSaveModal()"
            class="flex-1 h-12 transition-all bg-green-600 rounded-xl hover:bg-green-700 text-white"
          >
            <Icon name="bookmark-plus" class="w-4 h-4 mr-2" /> Save &amp; Report
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
