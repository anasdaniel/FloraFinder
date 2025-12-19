<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import BotanistChat from "@/components/identifier/BotanistChat.vue";
import { Button } from "@/components/ui/button";
import type { CareSource, PlantResult, ChatMessage } from "@/types/plant-identifier";
import { computed } from "vue";

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
  preferredProvider: "gemini" | "trefle";
  fetchingCareDetails: boolean;
  plantDescription: string;
  descriptionLoading: boolean;
  isCurrentResultBookmarked: boolean;
  isGeminiCare: boolean;
  hasCareData: boolean;
  isThreatenedSpecies?: boolean;
  formatCareValue: FormatCareValue;
  formatRange: FormatRange;
  setActiveImage: (index: number) => void;
  toggleBookmark: () => void;
  fetchCareDetails: (scientificName: string, forceRefresh?: boolean) => void;
  switchProvider: (provider: "gemini" | "trefle") => void;
  openSaveModal: () => void;
  // Chat props
  chatMessages: ChatMessage[];
  chatInput: string;
  isChatLoading: boolean;
  handleChatSend: () => void;
  updateChatInput: (val: string) => void;
}>();

// IUCN Category helpers
const getIucnInfo = computed(() => {
  const category = props.selectedResult.iucn?.category?.toUpperCase();
  if (!category) return null;

  const categoryMap: Record<
    string,
    {
      label: string;
      color: string;
      bgColor: string;
      textColor: string;
      critical: boolean;
      allowSighting: boolean;
      message: string;
    }
  > = {
    EX: {
      label: "Extinct",
      color: "bg-gray-900",
      bgColor: "bg-gray-100",
      textColor: "text-gray-900",
      critical: true,
      allowSighting: false,
      message:
        "This species is extinct. No living individuals remain. If you believe this identification is correct, please verify carefully as it would be an extraordinary discovery.",
    },
    EW: {
      label: "Extinct in Wild",
      color: "bg-gray-800",
      bgColor: "bg-gray-100",
      textColor: "text-gray-800",
      critical: true,
      allowSighting: false,
      message:
        "This species only exists in cultivation. Wild sightings should not be reported unless you are absolutely certain this is a wild population.",
    },
    CR: {
      label: "Critically Endangered",
      color: "bg-red-600",
      bgColor: "bg-red-50",
      textColor: "text-red-800",
      critical: true,
      allowSighting: true,
      message:
        "This species is critically endangered. Please report this sighting - your data is valuable for conservation efforts!",
    },
    EN: {
      label: "Endangered",
      color: "bg-orange-600",
      bgColor: "bg-orange-50",
      textColor: "text-orange-800",
      critical: false,
      allowSighting: true,
      message:
        "This species is endangered. Reporting this sighting helps conservation efforts.",
    },
    VU: {
      label: "Vulnerable",
      color: "bg-amber-500",
      bgColor: "bg-amber-50",
      textColor: "text-amber-800",
      critical: false,
      allowSighting: true,
      message:
        "This species is vulnerable. Your sighting data contributes to monitoring its status.",
    },
    NT: {
      label: "Near Threatened",
      color: "bg-yellow-500",
      bgColor: "bg-yellow-50",
      textColor: "text-yellow-800",
      critical: false,
      allowSighting: true,
      message: "",
    },
    LC: {
      label: "Least Concern",
      color: "bg-green-500",
      bgColor: "bg-green-50",
      textColor: "text-green-800",
      critical: false,
      allowSighting: true,
      message: "",
    },
    DD: {
      label: "Data Deficient",
      color: "bg-blue-500",
      bgColor: "bg-blue-50",
      textColor: "text-blue-800",
      critical: false,
      allowSighting: true,
      message:
        "Data on this species is limited. Your sighting helps build our knowledge!",
    },
    NE: {
      label: "Not Evaluated",
      color: "bg-gray-500",
      bgColor: "bg-gray-50",
      textColor: "text-gray-700",
      critical: false,
      allowSighting: true,
      message: "",
    },
  };

  return categoryMap[category] || null;
});

// Check if species is threatened (CR, EN, VU and named variants) - should NOT show care details
const isThreatenedSpecies = computed(() => {
  // First check if parent explicitly passed the prop
  if (typeof props.isThreatenedSpecies === "boolean") {
    console.log('Using parent isThreatenedSpecies prop:', props.isThreatenedSpecies);
    return props.isThreatenedSpecies;
  }

  const rawCategory = props.selectedResult.iucn?.category;
  console.log('Checking IUCN category:', rawCategory);

  if (!rawCategory) {
    console.log('No IUCN category found, returning false');
    return false;
  }

  const normalized = rawCategory.trim().toUpperCase();
  const threatenedCategories = new Set([
    "CR",
    "CRITICALLY ENDANGERED",
    "EN",
    "ENDANGERED",
    "VU",
    "VULNERABLE",
    "NT",
    "NEAR THREATENED",
  ]);

  const isThreatened = threatenedCategories.has(normalized);
  console.log(`IUCN category '${normalized}' is ${isThreatened ? 'THREATENED' : 'NOT threatened'}`);

  return isThreatened;
});

// Conservation authority contacts for threatened species
const conservationContacts = [
  {
    name: "PERHILITAN",
    description: "Department of Wildlife and National Parks",
    phone: "+603-9086 6800",
    website: "https://www.wildlife.gov.my",
    email: "pro@wildlife.gov.my",
    icon: "building-2",
  },
  {
    name: "Sabah Parks",
    description: "For sightings in Sabah region",
    phone: "+6088-523 500",
    website: "https://www.sabahparks.org.my",
    icon: "trees",
  },
  {
    name: "Sarawak Forestry",
    description: "For sightings in Sarawak region",
    phone: "+6082-610 088",
    website: "https://www.sarawakforestry.com",
    icon: "tree-pine",
  },
];

// Display name logic: prioritize common name, fallback to scientific name without author
const displayName = computed(() => {
  const commonName = props.selectedResult.species.commonNames?.[0];
  if (commonName) {
    return commonName;
  }
  // Use scientific name without author as fallback
  const scientificNameWithoutAuthor =
    props.selectedResult.species.scientificNameWithoutAuthor;
  if (scientificNameWithoutAuthor) {
    return scientificNameWithoutAuthor;
  }
  // Last resort: use full scientific name or genus
  return (
    props.selectedResult.species.scientificName ||
    props.selectedResult.species.genus?.scientificName ||
    "Unidentified Species"
  );
});

// Check if we're displaying scientific name as the title (no common name available)
const isDisplayingScientificName = computed(() => {
  return !props.selectedResult.species.commonNames?.[0];
});
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
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="px-3 py-1 text-xs font-bold rounded-full shadow-sm bg-green-500/90 backdrop-blur-md"
            >
              {{ Math.round((props.selectedResult.score || 0) * 100) }}% Match
            </span>
            <span
              v-if="getIucnInfo"
              class="flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-full shadow-sm backdrop-blur-md"
              :class="getIucnInfo.color"
            >
              <Icon v-if="getIucnInfo.critical" name="alert-triangle" class="w-3 h-3" />
              {{ getIucnInfo.label }}
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
          :class="{ 'italic font-serif': isDisplayingScientificName }"
        >
          {{ displayName }}
        </h1>
        <div v-if="props.careDetails?.malay_name" class="flex items-center gap-2 mb-2">
          <span class="px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase bg-white/20 backdrop-blur-md rounded text-white/90 border border-white/10">
            Nama Tempatan
          </span>
          <span class="text-xl font-semibold text-yellow-300 drop-shadow-md">
            {{ props.careDetails.malay_name }}
          </span>
        </div>
        <p
          v-if="!isDisplayingScientificName"
          class="font-serif text-lg italic text-green-100 opacity-90 md:text-xl"
        >
          {{ props.selectedResult.species.scientificName }}
        </p>
        <p v-else class="text-sm text-green-200 opacity-80">
          {{ props.selectedResult.species.family?.scientificName || "" }}
          {{
            props.selectedResult.species.genus?.scientificName
              ? `• ${props.selectedResult.species.genus.scientificName}`
              : ""
          }}
        </p>
      </div>
    </div>

    <!-- Conservation Warning Banner -->
    <div
      v-if="getIucnInfo && getIucnInfo.message"
      class="px-6 py-4 border-b dark:border-gray-800"
      :class="getIucnInfo.bgColor"
    >
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0 mt-0.5">
          <Icon
            :name="getIucnInfo.critical ? 'alert-triangle' : 'info'"
            class="w-5 h-5"
            :class="getIucnInfo.textColor"
          />
        </div>
        <div class="flex-1">
          <h4 class="mb-1 font-semibold" :class="getIucnInfo.textColor">
            Conservation Status: {{ getIucnInfo.label }}
          </h4>
          <p class="text-sm leading-relaxed" :class="getIucnInfo.textColor">
            {{ getIucnInfo.message }}
          </p>
        </div>
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
          <div v-if="!isThreatenedSpecies" class="flex items-center justify-between gap-4 mb-4">
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
                class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-purple-100 text-purple-700 whitespace-nowrap dark:bg-purple-900/40 dark:text-purple-300"
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
            <div
              v-if="!isThreatenedSpecies"
              class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg dark:bg-gray-800"
            >
              <button
                @click="props.switchProvider('gemini')"
                :class="[
                  'flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                  props.preferredProvider === 'gemini'
                    ? 'bg-white text-purple-700 shadow-sm dark:bg-gray-700 dark:text-purple-300'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
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
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
                :disabled="props.fetchingCareDetails"
              >
                <Icon name="database" class="w-3 h-3" />
                Trefle
              </button>
            </div>
          </div>
          <div v-else class="flex items-center mb-4">
            <h3
              class="flex items-center text-lg font-normal text-gray-900 dark:text-white"
            >
              <div
                class="mr-3 rounded-lg p-1.5"
                :class="[getIucnInfo?.bgColor, getIucnInfo?.textColor]"
              >
                <Icon name="shield-alert" class="w-5 h-5" />
              </div>
              Conservation Notice
            </h3>
          </div>
          <div class="overflow-hidden rounded-2xl dark:border-gray-700 dark:bg-gray-800">
            <!-- Conservation Warning for Threatened Species -->
            <div
              v-if="isThreatenedSpecies"
              class="p-6 border rounded-2xl"
              :class="[
                getIucnInfo?.bgColor,
                'border-2',
                getIucnInfo?.critical
                  ? 'border-red-300 dark:border-red-800'
                  : 'border-orange-300 dark:border-orange-800',
              ]"
            >
              <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 p-3 bg-white rounded-full dark:bg-gray-800">
                  <Icon
                    name="shield-alert"
                    class="w-6 h-6"
                    :class="getIucnInfo?.textColor"
                  />
                </div>
                <div class="flex-1">
                  <h4 class="mb-2 text-lg font-bold" :class="getIucnInfo?.textColor">
                    Protected Species - Conservation Priority
                  </h4>
                  <p class="mb-3 text-sm leading-relaxed" :class="getIucnInfo?.textColor">
                    This plant is classified as
                    <strong>{{ getIucnInfo?.label }}</strong> by the IUCN Red List. Care
                    guides are not provided for protected species to support conservation
                    efforts.
                  </p>
                  <ul class="space-y-1.5 text-sm mb-4" :class="getIucnInfo?.textColor">
                    <li class="flex items-start gap-2">
                      <Icon name="shield" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                      <span>Protected under wildlife conservation laws</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <Icon name="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                      <span>Collection or cultivation without permit is illegal</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <Icon name="map-pin" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                      <span>Please report sightings to conservation authorities</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Local Authority Contacts Section -->
              <div
                class="pt-4 mt-4 border-t"
                :class="
                  getIucnInfo?.critical
                    ? 'border-red-200 dark:border-red-900'
                    : 'border-orange-200 dark:border-orange-900'
                "
              >
                <h5
                  class="flex items-center gap-2 mb-3 text-sm font-semibold"
                  :class="getIucnInfo?.textColor"
                >
                  <Icon name="phone" class="w-4 h-4" />
                  Report Sightings to Local Authorities
                </h5>
                <div class="flex gap-4 overflow-x-auto pb-4 -mx-2 px-2 snap-x scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                  <div
                    v-for="contact in conservationContacts"
                    :key="contact.name"
                    class="flex-none w-[280px] snap-center p-4 transition bg-white border border-gray-200 dark:bg-gray-800 rounded-xl dark:border-gray-700 hover:shadow-md"
                  >
                    <div class="flex items-start gap-3">
                      <div
                        class="flex-shrink-0 p-2 text-green-700 bg-green-100 rounded-lg dark:bg-green-900/40 dark:text-green-400"
                      >
                        <Icon :name="contact.icon" class="w-5 h-5" />
                      </div>
                      <div class="flex-1">
                        <h6
                          class="mb-1 text-sm font-bold text-gray-900 dark:text-white"
                        >
                          {{ contact.name }}
                        </h6>
                        <p class="mb-3 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                          {{ contact.description }}
                        </p>
                        <div class="space-y-1.5">
                          <a
                            v-if="contact.phone"
                            :href="`tel:${contact.phone.replace(/\s/g, '')}`"
                            class="flex items-center gap-1.5 text-xs font-medium text-green-600 dark:text-green-400 hover:underline"
                          >
                            <Icon name="phone" class="w-3.5 h-3.5 flex-shrink-0" />
                            <span>{{ contact.phone }}</span>
                          </a>
                          <a
                            v-if="contact.website"
                            :href="contact.website"
                            target="_blank"
                            class="flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
                          >
                            <Icon name="external-link" class="w-3.5 h-3.5 flex-shrink-0" />
                            <span>Website</span>
                          </a>
                          <a
                            v-if="contact.email"
                            :href="`mailto:${contact.email}`"
                            class="flex items-center gap-1.5 text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline break-all"
                          >
                            <Icon name="mail" class="w-3.5 h-3.5 flex-shrink-0" />
                            <span>{{ contact.email }}</span>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- International Resources -->
              <div
                class="pt-4 mt-4 border-t"
                :class="
                  getIucnInfo?.critical
                    ? 'border-red-200 dark:border-red-900'
                    : 'border-orange-200 dark:border-orange-900'
                "
              >
                <h5
                  class="flex items-center gap-2 mb-3 text-sm font-semibold"
                  :class="getIucnInfo?.textColor"
                >
                  <Icon name="globe" class="w-4 h-4" />
                  International Conservation Resources
                </h5>
                <div class="flex flex-wrap gap-2">
                  <a
                    href="https://www.iucnredlist.org/"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow transition"
                    :class="getIucnInfo?.textColor"
                  >
                    <Icon name="book-open" class="w-3.5 h-3.5" />
                    IUCN Red List
                  </a>
                  <a
                    href="https://www.cites.org/"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow transition"
                    :class="getIucnInfo?.textColor"
                  >
                    <Icon name="file-text" class="w-3.5 h-3.5" />
                    CITES Convention
                  </a>
                  <a
                    href="https://www.gbif.org/"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow transition"
                    :class="getIucnInfo?.textColor"
                  >
                    <Icon name="database" class="w-3.5 h-3.5" />
                    GBIF Database
                  </a>
                </div>
              </div>

              <!-- Important Notice -->
              <div
                class="p-3 mt-4 border rounded-lg bg-white/50 dark:bg-gray-800/50 border-white/50 dark:border-gray-700/50"
              >
                <p class="text-xs leading-relaxed" :class="getIucnInfo?.textColor">
                  <strong>⚠️ Legal Notice:</strong> Collecting, trading, or cultivating
                  protected species without proper authorization is punishable under the
                  Wildlife Conservation Act. If you encounter this species, please
                  photograph it without disturbing the habitat and report the location to
                  the authorities above.
                </p>
              </div>
            </div>

            <!-- Regular Care Details for Non-Threatened Species -->
            <div
              v-else-if="props.fetchingCareDetails"
              class="flex flex-col items-center justify-center p-8 text-gray-400"
            >
              <Icon name="loader-2" class="w-6 h-6 mb-2 animate-spin" />
              <span class="text-xs">Consulting botanist notes...</span>
            </div>
            <template v-else-if="!isThreatenedSpecies && props.careDetails">
              <div
                v-if="props.isGeminiCare && props.hasCareData"
                class="p-5 border border-gray-200 rounded-2xl bg-gradient-to-br from-gray-50 to-white dark:border-gray-700 dark:from-gray-800/50 dark:to-gray-900"
              >
                <div
                  v-if="props.careDetails?.care_summary"
                  class="pb-4 mb-5 border-b border-gray-200 dark:border-gray-700"
                >
                  <div class="flex items-center gap-2 mb-2">
                    <Icon name="sparkles" class="w-4 h-4 text-purple-500" />
                    <span class="text-sm font-normal text-purple-600 dark:text-purple-400"
                      >Care Summary</span
                    >
                  </div>
                  <p
                    class="text-sm leading-relaxed text-justify text-gray-700 dark:text-gray-300"
                  >
                    {{ props.careDetails.care_summary as string }}
                  </p>
                </div>
                <ul class="space-y-4">
                  <li v-if="props.careDetails?.watering_guide" class="flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                      <div
                        class="p-2 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/40 dark:text-blue-400"
                      >
                        <Icon name="droplets" class="w-4 h-4" />
                      </div>
                    </div>
                    <div>
                      <span
                        class="block mb-1 text-sm font-normal text-blue-600 dark:text-blue-400"
                        >Watering</span
                      >
                      <p
                        class="text-sm leading-relaxed text-justify text-gray-600 dark:text-gray-300"
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
                        class="block mb-1 text-sm font-normal text-amber-600 dark:text-amber-400"
                        >Sunlight</span
                      >
                      <p
                        class="text-sm leading-relaxed text-justify text-gray-600 dark:text-gray-300"
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
                        class="block mb-1 text-sm font-normal text-emerald-600 dark:text-emerald-400"
                        >Soil</span
                      >
                      <p
                        class="text-sm leading-relaxed text-justify text-gray-600 dark:text-gray-300"
                      >
                        {{ props.careDetails.soil_guide as string }}
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
                        class="block mb-1 text-sm font-normal text-rose-600 dark:text-rose-400"
                        >Temperature</span
                      >
                      <p
                        class="text-sm leading-relaxed text-justify text-gray-600 dark:text-gray-300"
                      >
                        {{ props.careDetails.temperature_guide as string }}
                      </p>
                    </div>
                  </li>
                </ul>
                <div
                  class="p-3 mt-4 border border-purple-200 rounded-lg bg-purple-50 dark:bg-purple-900/20 dark:border-purple-800"
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
              <div v-else class="space-y-4">
                <!-- Conservation Focus Card -->
                <div
                  class="p-5 border border-teal-200 rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-50 dark:border-teal-800 dark:from-teal-950/40 dark:to-emerald-950/30"
                >
                  <div class="flex items-start gap-4">
                    <div
                      class="flex-shrink-0 p-3 bg-white shadow-sm dark:bg-gray-800 rounded-xl"
                    >
                      <Icon
                        name="heart-handshake"
                        class="w-6 h-6 text-teal-600 dark:text-teal-400"
                      />
                    </div>
                    <div class="flex-1">
                      <h4
                        class="mb-2 text-base font-semibold text-teal-800 dark:text-teal-200"
                      >
                        Conservation Over Cultivation
                      </h4>
                      <p
                        class="mb-3 text-sm leading-relaxed text-teal-700 dark:text-teal-300"
                      >
                        <span class="italic font-medium">{{
                          props.selectedResult.species.scientificNameWithoutAuthor
                        }}</span>
                        is a species best appreciated in its natural habitat. Home
                        cultivation is not recommended due to:
                      </p>
                      <ul class="space-y-2 text-sm text-teal-700 dark:text-teal-300">
                        <li class="flex items-start gap-2">
                          <Icon
                            name="leaf"
                            class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                          />
                          <span
                            >Specialized ecological requirements that are difficult to
                            replicate</span
                          >
                        </li>
                        <li class="flex items-start gap-2">
                          <Icon
                            name="trees"
                            class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                          />
                          <span
                            >Dependence on specific host plants or symbiotic
                            relationships</span
                          >
                        </li>
                        <li class="flex items-start gap-2">
                          <Icon
                            name="globe"
                            class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                          />
                          <span>Conservation value in protecting wild populations</span>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- How You Can Help -->
                <div
                  class="p-5 border border-green-200 rounded-2xl bg-gradient-to-br from-green-50 to-lime-50 dark:border-green-800 dark:from-green-950/40 dark:to-lime-950/30"
                >
                  <h4
                    class="flex items-center gap-2 mb-3 text-sm font-semibold text-green-800 dark:text-green-200"
                  >
                    <Icon name="hand-helping" class="w-4 h-4" />
                    How You Can Help Protect This Species
                  </h4>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div
                      class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                    >
                      <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                        <Icon
                          name="camera"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        />
                      </div>
                      <div>
                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                          Document & Report
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Share sightings with conservation databases
                        </p>
                      </div>
                    </div>
                    <div
                      class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                    >
                      <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                        <Icon
                          name="map-pin"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        />
                      </div>
                      <div>
                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                          Respect Habitat
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Observe without disturbing the environment
                        </p>
                      </div>
                    </div>
                    <div
                      class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                    >
                      <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                        <Icon
                          name="users"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        />
                      </div>
                      <div>
                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                          Spread Awareness
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Educate others about native species
                        </p>
                      </div>
                    </div>
                    <div
                      class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                    >
                      <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                        <Icon
                          name="building-2"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        />
                      </div>
                      <div>
                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                          Support Conservation
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Donate to habitat protection efforts
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Learn More Links -->
                <div class="flex flex-wrap justify-center gap-2 pt-2">
                  <a
                    :href="`https://www.gbif.org/species/search?q=${encodeURIComponent(
                      props.selectedResult.species.scientificNameWithoutAuthor
                    )}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:shadow transition"
                  >
                    <Icon name="database" class="w-3.5 h-3.5" />
                    View on GBIF
                  </a>
                  <a
                    :href="`https://www.inaturalist.org/taxa/search?q=${encodeURIComponent(
                      props.selectedResult.species.scientificNameWithoutAuthor
                    )}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:shadow transition"
                  >
                    <Icon name="binoculars" class="w-3.5 h-3.5" />
                    iNaturalist
                  </a>
                  <a
                    :href="`https://en.wikipedia.org/wiki/${encodeURIComponent(
                      props.selectedResult.species.scientificNameWithoutAuthor.replace(
                        ' ',
                        '_'
                      )
                    )}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:shadow transition"
                  >
                    <Icon name="book-open" class="w-3.5 h-3.5" />
                    Wikipedia
                  </a>
                </div>

              </div>
            </template>

            <!-- Fallback when careDetails is null/empty and not threatened -->
            <div
              v-else-if="!isThreatenedSpecies && !props.fetchingCareDetails"
              class="space-y-4"
            >
              <!-- Conservation Focus Card -->
              <div
                class="p-5 border border-teal-200 rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-50 dark:border-teal-800 dark:from-teal-950/40 dark:to-emerald-950/30"
              >
                <div class="flex items-start gap-4">
                  <div
                    class="flex-shrink-0 p-3 bg-white shadow-sm dark:bg-gray-800 rounded-xl"
                  >
                    <Icon
                      name="heart-handshake"
                      class="w-6 h-6 text-teal-600 dark:text-teal-400"
                    />
                  </div>
                  <div class="flex-1">
                    <h4
                      class="mb-2 text-base font-semibold text-teal-800 dark:text-teal-200"
                    >
                      Conservation Over Cultivation
                    </h4>
                    <p
                      class="mb-3 text-sm leading-relaxed text-teal-700 dark:text-teal-300"
                    >
                      <span class="italic font-medium">{{
                        props.selectedResult.species.scientificNameWithoutAuthor
                      }}</span>
                      is a species best appreciated in its natural habitat. Home
                      cultivation is not recommended due to:
                    </p>
                    <ul class="space-y-2 text-sm text-teal-700 dark:text-teal-300">
                      <li class="flex items-start gap-2">
                        <Icon
                          name="leaf"
                          class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                        />
                        <span
                          >Specialized ecological requirements that are difficult to
                          replicate</span
                        >
                      </li>
                      <li class="flex items-start gap-2">
                        <Icon
                          name="trees"
                          class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                        />
                        <span
                          >Dependence on specific host plants or symbiotic
                          relationships</span
                        >
                      </li>
                      <li class="flex items-start gap-2">
                        <Icon
                          name="globe"
                          class="w-4 h-4 mt-0.5 flex-shrink-0 text-teal-500"
                        />
                        <span>Conservation value in protecting wild populations</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- How You Can Help -->
              <div
                class="p-5 border border-green-200 rounded-2xl bg-gradient-to-br from-green-50 to-lime-50 dark:border-green-800 dark:from-green-950/40 dark:to-lime-950/30"
              >
                <h4
                  class="flex items-center gap-2 mb-3 text-sm font-semibold text-green-800 dark:text-green-200"
                >
                  <Icon name="hand-helping" class="w-4 h-4" />
                  How You Can Help Protect This Species
                </h4>
                <div class="grid gap-3 sm:grid-cols-2">
                  <div
                    class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                  >
                    <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                      <Icon
                        name="camera"
                        class="w-4 h-4 text-green-600 dark:text-green-400"
                      />
                    </div>
                    <div>
                      <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                        Document & Report
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Share sightings with conservation databases
                      </p>
                    </div>
                  </div>
                  <div
                    class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                  >
                    <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                      <Icon
                        name="map-pin"
                        class="w-4 h-4 text-green-600 dark:text-green-400"
                      />
                    </div>
                    <div>
                      <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                        Respect Habitat
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Observe without disturbing the environment
                      </p>
                    </div>
                  </div>
                  <div
                    class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                  >
                    <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                      <Icon
                        name="users"
                        class="w-4 h-4 text-green-600 dark:text-green-400"
                      />
                    </div>
                    <div>
                      <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                        Spread Awareness
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Educate others about native species
                      </p>
                    </div>
                  </div>
                  <div
                    class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl"
                  >
                    <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900/40">
                      <Icon
                        name="building-2"
                        class="w-4 h-4 text-green-600 dark:text-green-400"
                      />
                    </div>
                    <div>
                      <p class="text-xs font-medium text-gray-800 dark:text-gray-200">
                        Support Conservation
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Donate to habitat protection efforts
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Learn More Links -->
              <div class="flex flex-wrap justify-center gap-2 pt-2">
                <a
                  :href="`https://www.gbif.org/species/search?q=${encodeURIComponent(
                    props.selectedResult.species.scientificNameWithoutAuthor
                  )}`"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 hover:shadow transition"
                >
                  <Icon name="database" class="w-3.5 h-3.5" />
                  View on GBIF
                </a>
                <a
                  :href="`https://www.inaturalist.org/taxa/search?q=${encodeURIComponent(
                    props.selectedResult.species.scientificNameWithoutAuthor
                  )}`"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 hover:shadow transition"
                >
                  <Icon name="binoculars" class="w-3.5 h-3.5" />
                  iNaturalist
                </a>
                <a
                  :href="`https://en.wikipedia.org/wiki/${encodeURIComponent(
                    props.selectedResult.species.scientificNameWithoutAuthor.replace(
                      ' ',
                      '_'
                    )
                  )}`"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 hover:shadow transition"
                >
                  <Icon name="book-open" class="w-3.5 h-3.5" />
                  Wikipedia
                </a>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Full Width Chat Section -->
    <div class="px-6 pb-6 md:px-8 md:pb-8 border-t border-gray-200 dark:border-gray-800">
      <BotanistChat
        class="!border-t-0 !mt-0"
        :plant-name="props.selectedResult.species.commonNames?.[0] || props.selectedResult.species.scientificNameWithoutAuthor || 'plant'"
        :chat-messages="props.chatMessages"
        :chat-input="props.chatInput"
        :is-chat-loading="props.isChatLoading"
        :is-threatened-species="isThreatenedSpecies"
        :iucn-category="props.selectedResult.iucn?.category"
        :has-care-data="props.hasCareData"
        @update:chat-input="props.updateChatInput"
        @send="props.handleChatSend"
      />
      <div class="flex gap-3 pt-2">
        <Button
          @click="props.openSaveModal()"
          class="flex-1 h-12 text-white transition-all bg-green-600 rounded-xl hover:bg-green-700"
        >
          <Icon name="bookmark-plus" class="w-4 h-4 mr-2" /> Save &amp; Report
        </Button>
      </div>
    </div>
  </div>
</template>
