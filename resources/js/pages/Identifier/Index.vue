<script setup lang="ts">
import BotanistChat from "@/components/identifier/BotanistChat.vue";
import IdentifierResultCard from "@/components/identifier/IdentifierResultCard.vue";
import UploadPanel from "@/components/identifier/UploadPanel.vue";
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { useToast } from "@/composables/useToast";
import { usePlantIdentifierUploads } from "@/composables/usePlantIdentifierUploads";
import { usePlantIdentification } from "@/composables/usePlantIdentification";
import { usePlantSaveModal } from "@/composables/usePlantSaveModal";
import AppLayout from "@/layouts/AppLayout.vue";
import type { PlantResult } from "@/types/plant-identifier";
import { computed } from "vue";

const props = defineProps<{
  plantData?: PlantResult;
}>();

const { toast } = useToast();

const uploads = usePlantIdentifierUploads({ toast });
const identification = usePlantIdentification({
  initialPlantData: props.plantData,
  toast,
  uploadedImages: uploads.uploadedImages,
  errors: uploads.errors,
});

const saveModal = usePlantSaveModal({
  toast,
  selectedResult: identification.selectedResult,
  uploadedImages: uploads.uploadedImages,
  form: uploads.form,
  MALAYSIAN_REGIONS: uploads.MALAYSIAN_REGIONS,
});

// Destructure uploads composable
const {
  MAX_IMAGES,
  ORGANS,
  MALAYSIAN_REGIONS,
  uploadedImages,
  form,
  gettingLocation,
  exifLocation,
  gpsLocation,
  showLocationConflictModal,
  locationConflictResolved,
  locationGroups,
  showMultiLocationWarning,
  hasLocationConflict,
  conflictDistance,
  truncateFileName,
  onImageChange,
  removeImage,
  setOrgan,
  useUploadLocation,
  selectLocation,
  useLocationGroup,
  handleIdentifyClick,
} = uploads;

// Destructure identification composable
const {
  processing,
  results,
  activeImageIndex,
  careDetails,
  careSource,
  preferredProvider,
  fetchingCareDetails,
  plantDescription,
  descriptionLoading,
  chatMessages,
  chatInput,
  isChatLoading,
  hasResults,
  hasError,
  noMatches,
  errorMessage,
  selectedResult,
  isCurrentResultBookmarked,
  allImagesTagged,
  hasCareData,
  isGeminiCare,
  formatCareValue,
  formatRange,
  fetchCareDetails,
  switchProvider,
  toggleBookmark,
  handleChatSend,
  setActiveImage,
  resetResults,
} = identification;

// Destructure save modal composable
const {
  showSaveModal,
  submittingSave,
  saveOptions,
  iucnWarning,
  openSaveModal,
  closeSaveModal,
  getSaveLocation,
  submitSaveAndReport,
} = saveModal;

// Helper to open location conflict modal
const requestLocationConflictModal = () => {
  showLocationConflictModal.value = true;
};

// Wrapper to call identify with proper options
const onIdentify = () => {
  handleIdentifyClick({
    allImagesTagged: allImagesTagged.value,
    identify: identification.identifyPlant,
  });
};

// Reset identifier (combines both)
const resetIdentifier = () => {
  uploads.resetUploads();
  resetResults();
};

const getOrganLabel = (organ: string) => {
  const labels: Record<string, string> = {
    flower: "🌸 Flower",
    leaf: "🍃 Leaf",
    fruit: "🍎 Fruit",
    bark: "🪵 Bark",
    auto: "✨ Auto",
  };
  return labels[organ] || organ;
};

// Check if the identified plant is threatened (CR, EN, VU)
const isThreatenedSpecies = computed(() => {
  const category = selectedResult.value?.iucn?.category?.toUpperCase();
  return category === 'CR' || category === 'EN' || category === 'VU';
});
</script>

<template>
  <AppLayout title="Plant Identifier">
    <div
      class="min-h-screen py-8 bg-gradient-to-br from-gray-50 via-white to-green-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-green-950/20"
    >
      <div class="grid gap-8 px-4 mx-auto max-w-7xl md:grid-cols-12">
        <!-- Upload Panel Column -->
        <div
          :class="[
            'transition-all duration-500',
            hasResults || processing
              ? 'md:col-span-5 lg:col-span-4'
              : 'md:col-span-6 md:col-start-4',
          ]"
        >
          <UploadPanel
            v-model:form="form"
            :uploaded-images="uploadedImages"
            :max-images="MAX_IMAGES"
            :organs="ORGANS"
            :malaysian-regions="MALAYSIAN_REGIONS"
            :processing="processing"
            :has-results="hasResults ?? false"
            :all-images-tagged="allImagesTagged ?? false"
            :getting-location="gettingLocation"
            :exif-location="exifLocation"
            :gps-location="gpsLocation"
            :has-location-conflict="hasLocationConflict"
            :location-conflict-resolved="locationConflictResolved"
            :conflict-distance="conflictDistance"
            :truncate-file-name="truncateFileName"
            :remove-image="removeImage"
            :set-organ="setOrgan"
            :use-upload-location="useUploadLocation"
            :request-location-conflict-modal="requestLocationConflictModal"
            :identify="onIdentify"
            :reset="resetIdentifier"
            :on-image-change="onImageChange"
          />
        </div>

        <!-- Results Column -->
        <div
          v-if="processing || hasResults || hasError || noMatches"
          class="space-y-6 duration-500 animate-in slide-in-from-right-4 md:col-span-7 lg:col-span-8"
        >
          <!-- Processing State -->
          <Card
            v-if="processing"
            class="overflow-hidden border-0 shadow-xl rounded-3xl bg-white/80 backdrop-blur-md dark:bg-black/60"
          >
            <CardContent
              class="flex flex-col items-center justify-center py-20 text-center"
            >
              <div class="relative flex items-center justify-center w-24 h-24 mb-6">
                <div
                  class="absolute inset-0 rounded-full bg-emerald-500/15 blur-xl"
                ></div>
                <div
                  class="absolute inset-0 border rounded-full border-emerald-500/20"
                ></div>
                <div
                  class="absolute inset-1 animate-[spin_2.8s_linear_infinite] rounded-full border-2 border-transparent border-r-emerald-400/20 border-t-emerald-400/90"
                ></div>
                <div
                  class="relative z-10 flex items-center justify-center w-16 h-16 rounded-full shadow-2xl bg-white/85 ring-1 ring-emerald-100/80 dark:bg-gray-900/80 dark:ring-emerald-500/30"
                >
                  <Icon
                    name="loader-2"
                    class="h-7 w-7 animate-[spin_1.15s_linear_infinite] text-emerald-500"
                  />
                </div>
              </div>
              <h3
                class="text-lg font-semibold tracking-tight text-sage-900 dark:text-white"
              >
                Analyzing {{ uploadedImages.length }} Images
              </h3>
              <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
                Our AI is cross-referencing your photos...
              </p>
            </CardContent>
          </Card>

          <!-- Results Display -->
          <template v-else-if="hasResults && selectedResult">
            <Transition
              enter-active-class="duration-500 animate-in slide-in-from-right-4"
              leave-active-class="duration-300 animate-out slide-out-to-right-4"
            >
              <IdentifierResultCard
                v-if="hasResults && selectedResult"
                :selected-result="selectedResult"
                :active-image-index="activeImageIndex"
                :care-details="careDetails"
                :care-source="careSource"
                :preferred-provider="preferredProvider"
                :fetching-care-details="fetchingCareDetails"
                :plant-description="plantDescription"
                :description-loading="descriptionLoading"
                :is-current-result-bookmarked="isCurrentResultBookmarked"
                :is-gemini-care="isGeminiCare"
                :has-care-data="hasCareData"
                :format-care-value="formatCareValue"
                :format-range="formatRange"
                :set-active-image="setActiveImage"
                :toggle-bookmark="toggleBookmark"
                :fetch-care-details="fetchCareDetails"
                :switch-provider="switchProvider"
                :open-save-modal="openSaveModal"
              />
            </Transition>

            <!-- Botanist Chat (Hidden for Threatened Species) -->
            <BotanistChat
              v-if="!isThreatenedSpecies"
              :plant-name="selectedResult.species.commonNames?.[0] || 'plant'"
              :chat-messages="chatMessages"
              :chat-input="chatInput"
              :is-chat-loading="isChatLoading"
              @update:chat-input="chatInput = $event"
              @send="handleChatSend"
            />
          </template>

          <!-- Error State -->
          <Card
            v-else-if="hasError"
            class="overflow-hidden border-0 shadow-xl rounded-3xl bg-white/80 backdrop-blur-md dark:bg-black/60"
          >
            <CardContent
              class="flex flex-col items-center justify-center py-20 text-center"
            >
              <div class="p-4 mb-4 rounded-full shadow bg-red-900/20">
                <Icon name="x-circle" class="w-10 h-10 text-red-500" />
              </div>
              <h3
                class="text-lg font-semibold tracking-tight text-red-600 dark:text-red-400"
              >
                Identification Failed
              </h3>
              <p class="max-w-md mt-3 text-sm text-gray-600 dark:text-gray-300">
                {{ errorMessage }}
              </p>
              <p
                v-if="results?.error"
                class="max-w-md mt-2 text-xs text-gray-500 break-all dark:text-gray-400"
              >
                {{ results.error }}
              </p>
              <Button
                variant="outline"
                size="sm"
                class="mt-6 text-gray-700 border-gray-300 rounded-full hover:bg-gray-100 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-800"
                @click="resetIdentifier"
              >
                <Icon name="refresh-cw" class="w-4 h-4 mr-2" /> Try Again
              </Button>
            </CardContent>
          </Card>

          <!-- No Matches State -->
          <Card
            v-else-if="noMatches"
            class="overflow-hidden border-0 shadow-xl rounded-3xl bg-white/80 backdrop-blur-md dark:bg-gray-900/70"
          >
            <CardContent
              class="flex flex-col items-center justify-center py-20 text-center"
            >
              <div class="p-4 mb-4 rounded-full shadow bg-amber-900/15">
                <Icon name="alert-triangle" class="w-10 h-10 text-amber-500" />
              </div>
              <h3
                class="text-lg font-semibold tracking-tight text-amber-600 dark:text-amber-300"
              >
                No Matches Found
              </h3>
              <p class="max-w-md mt-3 text-sm text-gray-600 dark:text-gray-300">
                We couldn't find a confident match for this photo. Try a clearer image,
                zoom in on the most distinctive plant part, or choose a different organ,
                or select Auto to let AI detect the organ.
              </p>
              <ul
                class="mt-4 text-sm text-gray-500 list-disc list-inside dark:text-gray-400"
              >
                <li>Use daylight and avoid blur.</li>
                <li>Capture flowers or leaves head-on.</li>
                <li>Ensure the plant fills most of the frame.</li>
              </ul>
              <Button
                variant="outline"
                size="sm"
                class="mt-6 text-gray-700 border-gray-300 rounded-full hover:bg-gray-100 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-800"
                @click="resetIdentifier"
              >
                <Icon name="image" class="w-4 h-4 mr-2" /> Try a Different Photo
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Save Modal -->
    <div
      v-if="showSaveModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      @click.self="closeSaveModal"
    >
      <Card class="w-full max-w-lg m-4 max-h-[90vh] overflow-y-auto">
        <CardHeader class="pb-4 border-b">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="p-2 text-green-600 bg-green-100 rounded-lg dark:bg-green-900/40 dark:text-green-300"
              >
                <Icon name="bookmark-plus" class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                  Save Plant
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{
                    selectedResult?.species?.commonNames?.[0] ||
                    selectedResult?.species?.scientificName ||
                    "Unknown Plant"
                  }}
                </p>
              </div>
            </div>
            <button
              @click="closeSaveModal"
              class="p-2 transition rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <Icon name="x" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
        </CardHeader>
        <CardContent class="p-6 space-y-5">
          <!-- Images Preview -->
          <div>
            <label
              class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              Images ({{ uploadedImages.length }})
            </label>
            <div class="flex gap-2 pb-2 overflow-x-auto">
              <div
                v-for="img in uploadedImages"
                :key="img.id"
                class="relative flex-shrink-0 w-16 h-16 overflow-hidden border border-gray-200 rounded-lg dark:border-gray-700"
              >
                <img :src="img.preview" class="object-cover w-full h-full" />
                <span
                  v-if="img.organ"
                  class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] text-center py-0.5 capitalize"
                >
                  {{ getOrganLabel(img.organ) }}
                </span>
              </div>
            </div>
          </div>

          <!-- IUCN Warning Banner -->
          <div
            v-if="iucnWarning"
            class="p-4 rounded-xl border-2"
            :class="iucnWarning.shouldDisableSighting ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800'"
          >
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <Icon
                  name="alert-triangle"
                  class="w-5 h-5"
                  :class="iucnWarning.shouldDisableSighting ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'"
                />
              </div>
              <div>
                <h4
                  class="font-semibold text-sm mb-1"
                  :class="iucnWarning.shouldDisableSighting ? 'text-red-900 dark:text-red-200' : 'text-amber-900 dark:text-amber-200'"
                >
                  Conservation Alert
                </h4>
                <p
                  class="text-sm leading-relaxed"
                  :class="iucnWarning.shouldDisableSighting ? 'text-red-800 dark:text-red-300' : 'text-amber-800 dark:text-amber-300'"
                >
                  {{ iucnWarning.message }}
                </p>
              </div>
            </div>
          </div>

          <!-- Save Options -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              What would you like to do?
            </label>

            <!-- Save to Collection Toggle -->
            <div
              class="flex items-center justify-between p-4 border border-gray-200 rounded-xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
            >
              <div class="flex items-center gap-3">
                <div
                  class="p-2 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/40 dark:text-blue-300"
                >
                  <Icon name="folder-heart" class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Save to My Collection
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Add to your personal plant library
                  </p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  v-model="saveOptions.saveToCollection"
                  class="sr-only peer"
                />
                <div
                  class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-gray-600 dark:bg-gray-700"
                ></div>
              </label>
            </div>

            <!-- Report Sighting Toggle -->
            <div
              class="flex items-center justify-between p-4 border border-gray-200 rounded-xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
              :class="iucnWarning?.shouldDisableSighting ? 'opacity-50' : ''"
            >
              <div class="flex items-center gap-3">
                <div
                  class="p-2 text-green-600 bg-green-100 rounded-lg dark:bg-green-900/40 dark:text-green-300"
                >
                  <Icon name="map-pin" class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Report Public Sighting
                    <span v-if="iucnWarning?.shouldDisableSighting" class="ml-1 text-xs text-red-600">(Disabled)</span>
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ iucnWarning?.shouldDisableSighting ? 'Not applicable for extinct species' : 'Contribute to biodiversity mapping' }}
                  </p>
                </div>
              </div>
              <label class="relative inline-flex items-center" :class="iucnWarning?.shouldDisableSighting ? 'cursor-not-allowed' : 'cursor-pointer'">
                <input
                  type="checkbox"
                  v-model="saveOptions.reportSighting"
                  :disabled="iucnWarning?.shouldDisableSighting"
                  class="sr-only peer"
                />
                <div
                  class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-disabled:opacity-50 peer-disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-700"
                ></div>
              </label>
            </div>
          </div>

          <!-- Location Section (only show when reporting sighting) -->
          <Transition
            enter-active-class="duration-200 animate-in fade-in slide-in-from-top-2"
            leave-active-class="duration-150 animate-out fade-out slide-out-to-top-2"
          >
            <div
              v-if="saveOptions.reportSighting"
              class="p-4 space-y-3 border border-green-200 rounded-xl bg-green-50/50 dark:border-green-800/50 dark:bg-green-900/20"
            >
              <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Sighting Location
                </label>
                <button
                  type="button"
                  @click="getSaveLocation"
                  class="flex items-center gap-1.5 text-xs font-medium text-green-600 hover:text-green-700 dark:text-green-400"
                >
                  <Icon name="crosshair" class="w-3.5 h-3.5" />
                  Use Current GPS
                </button>
              </div>
              <div class="relative">
                <Icon
                  name="map-pin"
                  class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                />
                <input
                  v-model="saveOptions.locationName"
                  placeholder="Location name (e.g., Taman Negara)"
                  class="w-full py-2.5 pr-3 text-sm bg-white border border-gray-200 rounded-xl pl-10 focus:border-green-500 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                />
              </div>
              <div class="relative">
                <Icon
                  name="globe"
                  class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                />
                <select
                  v-model="saveOptions.region"
                  class="w-full py-2.5 pr-8 text-sm bg-white border border-gray-200 rounded-xl appearance-none pl-10 focus:border-green-500 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                  <option
                    v-for="region in MALAYSIAN_REGIONS"
                    :key="region"
                    :value="region"
                  >
                    {{ region }}
                  </option>
                </select>
                <Icon
                  name="chevron-down"
                  class="absolute w-4 h-4 text-gray-400 pointer-events-none right-3 top-3"
                />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="relative">
                  <span
                    class="absolute -top-2 left-2 bg-green-50 dark:bg-green-900/20 px-1 text-[10px] font-medium text-gray-500"
                  >
                    Latitude
                  </span>
                  <input
                    v-model="saveOptions.latitude"
                    type="number"
                    step="0.000001"
                    placeholder="0.000000"
                    class="w-full px-3 py-2.5 font-mono text-sm text-gray-600 bg-white border border-gray-200 rounded-xl focus:border-green-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                  />
                </div>
                <div class="relative">
                  <span
                    class="absolute -top-2 left-2 bg-green-50 dark:bg-green-900/20 px-1 text-[10px] font-medium text-gray-500"
                  >
                    Longitude
                  </span>
                  <input
                    v-model="saveOptions.longitude"
                    type="number"
                    step="0.000001"
                    placeholder="0.000000"
                    class="w-full px-3 py-2.5 font-mono text-sm text-gray-600 bg-white border border-gray-200 rounded-xl focus:border-green-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                  />
                </div>
              </div>

              <!-- Date -->
              <div class="relative">
                <Icon
                  name="calendar"
                  class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                />
                <input
                  v-model="saveOptions.date"
                  type="date"
                  class="w-full py-2.5 pr-3 text-sm bg-white border border-gray-200 rounded-xl pl-10 focus:border-green-500 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                />
              </div>

              <!-- Notes -->
              <textarea
                v-model="saveOptions.notes"
                rows="2"
                placeholder="Notes about habitat, condition, observations... (optional)"
                class="w-full px-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl resize-none focus:border-green-500 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
              ></textarea>
            </div>
          </Transition>

          <!-- Actions -->
          <div class="flex gap-3 pt-2">
            <Button
              @click="submitSaveAndReport"
              :disabled="
                submittingSave ||
                (!saveOptions.saveToCollection && !saveOptions.reportSighting)
              "
              class="flex-1 text-white transition-all bg-green-600 h-11 hover:bg-green-700 rounded-xl disabled:opacity-50"
            >
              <Icon
                v-if="submittingSave"
                name="loader-2"
                class="w-4 h-4 mr-2 animate-spin"
              />
              <Icon v-else name="check" class="w-4 h-4 mr-2" />
              {{ submittingSave ? "Saving..." : "Confirm" }}
            </Button>
            <Button
              variant="outline"
              @click="closeSaveModal"
              :disabled="submittingSave"
              class="px-6 border-gray-200 h-11 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            >
              Cancel
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Location Conflict Modal -->
    <div
      v-if="showLocationConflictModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      @click.self="showLocationConflictModal = false"
    >
      <Card class="w-full max-w-md m-4">
        <CardHeader class="pb-4 border-b">
          <div class="flex items-center gap-3">
            <div
              class="p-2 rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300"
            >
              <Icon name="alert-triangle" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Location Conflict
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Different locations detected
              </p>
            </div>
          </div>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <p class="text-sm text-gray-600 dark:text-gray-300">
            The photo's embedded location is
            <span class="font-semibold text-amber-600"
              >{{ conflictDistance.toFixed(1) }} km</span
            >
            away from your current GPS position. Which location should we use?
          </p>

          <!-- EXIF Location Option -->
          <button
            @click="selectLocation('exif')"
            class="w-full p-4 text-left transition-all border-2 rounded-xl hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20"
            :class="[
              form.latitude === exifLocation?.latitude &&
              form.longitude === exifLocation?.longitude
                ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                : 'border-gray-200 dark:border-gray-700',
            ]"
          >
            <div class="flex items-start gap-3">
              <div
                class="p-2 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/40 dark:text-blue-300"
              >
                <Icon name="image" class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-white">Photo Location</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  From image metadata (EXIF)
                </p>
                <div class="mt-2 text-sm">
                  <p class="text-gray-700 dark:text-gray-300">
                    <Icon name="map-pin" class="inline w-3 h-3 mr-1" />
                    {{ exifLocation?.locationName || "Unknown location" }}
                  </p>
                  <p class="mt-1 font-mono text-xs text-gray-500">
                    {{ exifLocation?.latitude?.toFixed(4) }},
                    {{ exifLocation?.longitude?.toFixed(4) }}
                  </p>
                  <p v-if="exifLocation?.timestamp" class="mt-1 text-xs text-gray-400">
                    <Icon name="clock" class="inline w-3 h-3 mr-1" />
                    Taken: {{ exifLocation.timestamp.toLocaleDateString() }}
                  </p>
                </div>
              </div>
              <Icon
                v-if="
                  form.latitude === exifLocation?.latitude &&
                  form.longitude === exifLocation?.longitude
                "
                name="check-circle"
                class="flex-shrink-0 w-5 h-5 text-green-500"
              />
            </div>
          </button>

          <!-- GPS Location Option -->
          <button
            @click="selectLocation('gps')"
            class="w-full p-4 text-left transition-all border-2 rounded-xl hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20"
            :class="[
              form.latitude === gpsLocation?.latitude &&
              form.longitude === gpsLocation?.longitude
                ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                : 'border-gray-200 dark:border-gray-700',
            ]"
          >
            <div class="flex items-start gap-3">
              <div
                class="p-2 text-green-600 bg-green-100 rounded-lg dark:bg-green-900/40 dark:text-green-300"
              >
                <Icon name="crosshair" class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-white">Current Location</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  From your device GPS
                </p>
                <div class="mt-2 text-sm">
                  <p class="text-gray-700 dark:text-gray-300">
                    <Icon name="map-pin" class="inline w-3 h-3 mr-1" />
                    {{ gpsLocation?.locationName || "Current position" }}
                  </p>
                  <p class="mt-1 font-mono text-xs text-gray-500">
                    {{ gpsLocation?.latitude?.toFixed(4) }},
                    {{ gpsLocation?.longitude?.toFixed(4) }}
                  </p>
                  <p class="mt-1 text-xs text-gray-400">
                    <Icon name="clock" class="inline w-3 h-3 mr-1" />
                    Now
                  </p>
                </div>
              </div>
              <Icon
                v-if="
                  form.latitude === gpsLocation?.latitude &&
                  form.longitude === gpsLocation?.longitude
                "
                name="check-circle"
                class="flex-shrink-0 w-5 h-5 text-green-500"
              />
            </div>
          </button>

          <div
            class="p-3 border border-blue-200 rounded-lg bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800"
          >
            <p class="flex items-start gap-2 text-xs text-blue-700 dark:text-blue-300">
              <Icon name="info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span>
                <strong>Tip:</strong> Use <em>Photo Location</em> if the plant is still
                where the photo was taken. Use <em>Current Location</em> if you're
                reporting where you found it now.
              </span>
            </p>
          </div>

          <div class="flex gap-3 pt-2">
            <Button
              variant="outline"
              @click="showLocationConflictModal = false"
              class="flex-1 h-10 border-gray-200 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            >
              Cancel
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Multi-Location Warning Modal -->
    <div
      v-if="showMultiLocationWarning"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      @click.self="showMultiLocationWarning = false"
    >
      <Card class="w-full max-w-lg m-4 max-h-[85vh] overflow-hidden flex flex-col">
        <CardHeader class="flex-shrink-0 pb-4 border-b">
          <div class="flex items-center gap-3">
            <div
              class="p-2 rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300"
            >
              <Icon name="alert-triangle" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Images From Different Locations
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Select one group to identify
              </p>
            </div>
          </div>
        </CardHeader>
        <CardContent class="flex-1 p-6 space-y-4 overflow-y-auto">
          <div
            class="p-3 border rounded-lg bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800"
          >
            <p class="flex items-start gap-2 text-sm text-amber-700 dark:text-amber-300">
              <Icon name="info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span>
                <strong>PlantNet API requires all images to be of the same plant.</strong>
                Your images appear to be from different locations (more than 1km apart),
                which may indicate different plants.
              </span>
            </p>
          </div>

          <!-- Location Groups -->
          <div class="space-y-3">
            <div
              v-for="(group, idx) in locationGroups"
              :key="idx"
              class="p-4 transition-all border-2 rounded-xl hover:border-green-400 dark:border-gray-700 dark:hover:border-green-500"
              :class="
                group.isUnknown
                  ? 'border-gray-200 bg-gray-50 dark:bg-gray-800/50'
                  : 'border-gray-200 bg-white dark:bg-gray-800'
              "
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Icon
                      :name="group.isUnknown ? 'help-circle' : 'map-pin'"
                      class="w-4 h-4"
                      :class="group.isUnknown ? 'text-gray-400' : 'text-green-600'"
                    />
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ group.locationName || "Location " + (idx + 1) }}
                    </span>
                    <span
                      class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                    >
                      {{ group.images.length }} image{{
                        group.images.length > 1 ? "s" : ""
                      }}
                    </span>
                  </div>

                  <p
                    v-if="!group.isUnknown && group.latitude && group.longitude"
                    class="mb-3 font-mono text-xs text-gray-500"
                  >
                    {{ group.latitude.toFixed(4) }}, {{ group.longitude.toFixed(4) }}
                  </p>
                  <p v-else-if="group.isUnknown" class="mb-3 text-xs text-gray-400">
                    No GPS metadata found in these images
                  </p>

                  <!-- Image Previews -->
                  <div class="flex gap-2 pb-1 overflow-x-auto">
                    <div
                      v-for="img in group.images"
                      :key="img.id"
                      class="relative flex-shrink-0 overflow-hidden border border-gray-200 rounded-lg w-14 h-14 dark:border-gray-600"
                    >
                      <img :src="img.preview" class="object-cover w-full h-full" />
                      <span
                        v-if="img.organ"
                        class="absolute bottom-0 left-0 right-0 bg-black/70 text-white text-[8px] text-center py-0.5 capitalize"
                      >
                        {{ getOrganLabel(img.organ) }}
                      </span>
                    </div>
                  </div>
                </div>

                <Button
                  @click="useLocationGroup(group)"
                  size="sm"
                  class="flex-shrink-0 text-white bg-green-600 hover:bg-green-700"
                >
                  <Icon name="check" class="w-3 h-3 mr-1" />
                  Use
                </Button>
              </div>
            </div>
          </div>

          <div
            class="p-3 border border-blue-200 rounded-lg bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800"
          >
            <p class="flex items-start gap-2 text-xs text-blue-700 dark:text-blue-300">
              <Icon name="lightbulb" class="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span>
                <strong>Tip:</strong> Select one group to identify now. You can identify
                other plants separately by starting a new identification.
              </span>
            </p>
          </div>

          <div class="flex gap-3 pt-2">
            <Button
              variant="outline"
              @click="showMultiLocationWarning = false"
              class="flex-1 h-10 border-gray-200 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            >
              Cancel
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
