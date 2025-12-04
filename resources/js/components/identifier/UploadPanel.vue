<script setup lang="ts">
import { computed, ref } from "vue";
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import type {
  IdentificationForm,
  ImageUpload,
  LocationSource,
} from "@/types/plant-identifier";

interface OrganOption {
  value: string;
  label: string;
  icon: string;
}

const props = defineProps<{
  uploadedImages: ImageUpload[];
  maxImages: number;
  organs: OrganOption[];
  malaysianRegions: string[];
  processing: boolean;
  hasResults: boolean;
  allImagesTagged: boolean;
  gettingLocation: boolean;
  exifLocation: LocationSource | null;
  gpsLocation: LocationSource | null;
  hasLocationConflict: boolean;
  locationConflictResolved: boolean;
  conflictDistance: number;
  truncateFileName: (name: string, max?: number) => string;
  removeImage: (id: string) => void;
  setOrgan: (id: string, organ: string) => void;
  useUploadLocation: () => void;
  requestLocationConflictModal: () => void;
  identify: () => void;
  reset: () => void;
  onImageChange: (event: Event) => void;
}>();

const form = defineModel<IdentificationForm>("form", { required: true });

const hasImages = computed(() => props.uploadedImages.length > 0);
const fileInputRef = ref<HTMLInputElement | null>(null);

const openFileUpload = () => {
  fileInputRef.value?.click();
};

const handleIdentifyClick = () => {
  if (props.processing) return;
  props.identify();
};
</script>

<template>
  <Card
    :class="[
      'dark:bg-moss-900/60 overflow-hidden rounded-3xl border-0 bg-white/70 ring-1 ring-black/10 backdrop-blur-md dark:ring-black/40',
      props.uploadedImages.length === 0 ? 'shadow-2xl' : 'shadow-xl',
    ]"
  >
    <CardHeader
      class="pb-6 border-b-0 shadow-sm text-moss-900 from-moss-100/80 to-moss-200/60 dark:from-moss-900/80 dark:to-moss-800/60 rounded-t-3xl bg-gradient-to-r dark:text-white"
    >
      <div class="flex items-center justify-center">
        <Icon name="camera" class="w-5 h-5 mr-2 text-moss-400" />
        <h2 class="text-lg font-semibold tracking-tight">
          {{ props.uploadedImages.length > 0 ? "Identify Plant" : "Upload Plant Image" }}
        </h2>
      </div>
    </CardHeader>
    <CardContent class="px-6 py-6 space-y-6 bg-transparent">
      <div
        v-if="!hasImages"
        @click="openFileUpload()"
        class="border-moss-300 dark:border-moss-700 hover:border-moss-500 hover:bg-moss-50 dark:hover:bg-moss-900/20 relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed bg-white/50 transition-all duration-300 ease-in-out dark:bg-white/5"
      >
        <div class="flex flex-col items-center justify-center p-8 space-y-5 text-center rounded-2xl sm:p-10">
          <div
            class="p-5 rounded-full shadow-sm bg-moss-100 dark:bg-moss-900/50 text-moss-600 dark:text-moss-300 ring-moss-200 dark:ring-moss-800 ring-1"
          >
            <Icon name="upload" class="w-8 h-8" />
          </div>
          <div class="px-2 space-y-1">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
              Upload plant photos
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Up to 5 images (JPG, PNG)</p>
          </div>
        </div>
      </div>

      <div v-else class="space-y-3">
        <div class="flex items-center justify-between px-1 text-xs text-gray-500">
          <span>{{ props.uploadedImages.length }} / {{ props.maxImages }} images</span>
          <button
            v-if="props.uploadedImages.length < props.maxImages"
            @click="openFileUpload()"
            class="flex items-center gap-1 font-medium text-moss-600 hover:underline"
          >
            <Icon name="plus" class="w-3 h-3" /> Add more
          </button>
        </div>
        <div
          v-for="img in props.uploadedImages"
          :key="img.id"
          class="flex gap-3 p-2 bg-white border border-gray-100 shadow-sm group rounded-xl dark:border-gray-700 dark:bg-gray-800"
        >
          <div class="relative flex-shrink-0 w-20 h-20 overflow-hidden bg-gray-100 rounded-lg">
            <img :src="img.preview" class="object-cover w-full h-full" />
            <button
              @click="props.removeImage(img.id)"
              class="absolute p-1 text-red-500 transition-opacity rounded-full shadow-sm opacity-0 right-1 top-1 bg-white/90 group-hover:opacity-100"
            >
              <Icon name="x" class="w-3 h-3" />
            </button>
          </div>
          <div class="flex flex-col justify-center flex-1 min-w-0">
            <span class="mb-1 text-sm font-medium text-gray-600 truncate dark:text-gray-300">
              {{ props.truncateFileName(img.file.name) }}
            </span>
            <div v-if="img.organScore !== undefined" class="flex items-center gap-2">
              <span class="text-xs text-gray-500">Predicted organ:</span>
              <span class="text-xs font-semibold text-moss-600 dark:text-moss-400">{{ Math.round((img.organScore || 0) * 100) }}%</span>
            </div>
            <div v-else class="text-xs text-gray-400">
              Select organ or use Auto
            </div>
          </div>
          <div class="flex items-center ml-3 pl-2 pr-2 flex-shrink-0 w-36">
            <label class="text-xs text-gray-500 mr-2 hidden sm:inline">Organ:</label>
            <select
              :value="img.organ"
              @change="(e) => props.setOrgan(img.id, e.target.value)"
              class="text-sm rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-1 px-2 w-full"
            >
              <option v-for="organ in props.organs" :value="organ.value" :key="organ.value">
                {{ organ.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <input
        ref="fileInputRef"
        type="file"
        multiple
        class="hidden"
        accept="image/*"
        @change="props.onImageChange"
      />

      <div
        v-if="hasImages"
        class="p-4 mt-4 transition-all border border-gray-100 rounded-2xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
      >
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="rounded-lg bg-green-100 p-1.5 text-green-600 dark:bg-blue-900/30 dark:text-blue-400">
              <Icon name="map" class="w-4 h-4" />
            </div>
            <div>
              <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Location Data</h4>
              <p class="text-[10px] text-gray-500 dark:text-gray-400">Help map species distribution</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.includeLocation" class="sr-only peer" />
            <div
              class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-gray-600 dark:bg-gray-700"
            ></div>
          </label>
        </div>
        <Transition
          enter-active-class="duration-200 animate-in fade-in slide-in-from-top-2"
          leave-active-class="duration-200 animate-out fade-out slide-out-to-top-2"
        >
          <div v-if="form.includeLocation" class="space-y-3">
            <div
              v-if="props.exifLocation || props.gpsLocation"
              class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
            >
              <div class="flex items-center gap-2">
                <Icon
                  :name="props.exifLocation && form.latitude === props.exifLocation.latitude ? 'image' : 'crosshair'"
                  class="w-3.5 h-3.5 text-green-600"
                />
                <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300">
                  {{ props.exifLocation && form.latitude === props.exifLocation.latitude ? "Photo metadata" : "GPS location" }}
                </span>
              </div>
              <div v-if="props.exifLocation && props.gpsLocation" class="flex items-center gap-1">
                <span
                  v-if="props.hasLocationConflict && !props.locationConflictResolved"
                  class="text-[9px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 font-medium"
                >
                  {{ props.conflictDistance.toFixed(1) }}km apart
                </span>
                <button
                  @click="props.requestLocationConflictModal()"
                  class="text-[10px] font-medium text-blue-600 hover:underline dark:text-blue-400"
                >
                  Switch
                </button>
              </div>
            </div>

            <button
              @click="props.useUploadLocation()"
              class="flex items-center justify-center w-full gap-2 py-2 text-xs font-medium text-gray-700 transition-all bg-white border border-gray-200 rounded-lg hover:border-green-200 hover:bg-green-50 hover:text-green-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-green-400"
              :disabled="props.gettingLocation"
            >
              <Icon
                name="crosshair"
                :class="`h-3.5 w-3.5 ${props.gettingLocation ? 'animate-spin' : ''}`"
              />
              {{
                props.gettingLocation
                  ? 'Triangulating GPS...'
                  : props.exifLocation
                  ? 'Compare with Current GPS'
                  : 'Use Current GPS Location'
              }}
            </button>
            <div class="grid grid-cols-1 gap-3">
              <div class="relative">
                <Icon name="map-pin" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                <input
                  v-model="form.locationName"
                  placeholder="Location Name (e.g. Taman Negara)"
                  class="w-full py-2 pr-3 text-xs bg-white border-gray-200 rounded-lg pl-9 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                />
              </div>
              <div class="relative">
                <Icon name="globe" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                <select
                  v-model="form.region"
                  class="w-full py-2 pr-3 text-xs bg-white border-gray-200 rounded-lg appearance-none pl-9 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                >
                  <option v-for="region in props.malaysianRegions" :key="region" :value="region">
                    {{ region }}
                  </option>
                </select>
                <Icon
                  name="chevron-down"
                  class="absolute w-3 h-3 text-gray-400 pointer-events-none right-3 top-3"
                />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="relative group">
                  <span
                    class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                    >Lat</span
                  >
                  <input
                    v-model="form.latitude"
                    class="w-full px-3 py-2 font-mono text-xs text-gray-600 transition-all bg-gray-100 border-transparent rounded-lg focus:border-green-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
                    placeholder="0.000000"
                  />
                </div>
                <div class="relative group">
                  <span
                    class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                    >Long</span
                  >
                  <input
                    v-model="form.longitude"
                    class="w-full px-3 py-2 font-mono text-xs text-gray-600 transition-all bg-gray-100 border-transparent rounded-lg focus:border-green-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
                    placeholder="0.000000"
                  />
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <div class="flex gap-3">
        <Button
          v-if="!props.hasResults"
          class="w-full text-white transition-all duration-200 shadow-lg rounded-xl bg-gradient-to-r from-black to-black/80 hover:from-black/90 hover:to-black dark:text-white"
          size="lg"
          :disabled="props.processing || props.uploadedImages.length === 0 || !props.allImagesTagged"
          @click="handleIdentifyClick"
        >
          <div class="flex items-center justify-center">
            <template v-if="props.processing">
              <Icon name="loader-2" class="w-4 h-4 mr-2 animate-spin" /> Identifying...
            </template>
            <template v-else>
              <Icon name="search" class="w-4 h-4 mr-2" /> Identify Plant
            </template>
          </div>
        </Button>
        <Button
          v-else
          variant="outline"
          class="w-full text-gray-700 transition border-gray-200 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
          size="lg"
          :disabled="props.processing"
          @click="props.reset()"
        >
          New Identification
        </Button>
      </div>
      <p
        v-if="props.uploadedImages.length > 0 && !props.allImagesTagged"
        class="text-xs font-medium text-center text-orange-500"
      >
        Please select a plant part for all images
      </p>
    </CardContent>
  </Card>
</template>
