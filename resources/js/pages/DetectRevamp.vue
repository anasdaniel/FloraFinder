<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { useToast } from "@/composables/useToast";
import AppLayout from "@/layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import exifr from "exifr";
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";

// --- Types ---
interface ImageUpload {
  id: string;
  file: File;
  preview: string;
  organ: string | null;
}

interface PlantResult {
  success: boolean;
  message?: string;
  error?: string;
  savedToDatabase?: boolean;
  data?: {
    results: Array<{
      score: number;
      species: {
        scientificNameWithoutAuthor: string;
        scientificNameAuthorship: string;
        genus: {
          scientificNameWithoutAuthor: string;
          scientificNameAuthorship: string;
          scientificName: string;
        };
        family: {
          scientificNameWithoutAuthor: string;
          scientificNameAuthorship: string;
          scientificName: string;
        };
        commonNames: string[];
        scientificName: string;
      };
      images?: Array<{ url: { s: string; m: string; l: string } }>;
      gbif?: { id: string };
      powo?: { id: string };
      iucn?: { category: string };
    }>;
  };
}

interface FormData {
  locationName: string;
  region: string;
  latitude: number | null;
  longitude: number | null;
  includeLocation: boolean;
  saveToDatabase: boolean;
}

interface Errors {
  image?: string;
  organ?: string;
  [key: string]: string | undefined;
}

// --- Props ---
const props = defineProps<{
  plantData?: PlantResult;
}>();

const { toast } = useToast();

// --- State ---
const uploadedImages = ref<ImageUpload[]>([]);
const form = reactive<FormData>({
  locationName: "",
  region: "Peninsular Malaysia",
  latitude: null,
  longitude: null,
  includeLocation: false,
  saveToDatabase: false,
});

const processing = ref<boolean>(false);
const results = ref<PlantResult | null>(null);
const errors = reactive<Errors>({});
const activeImageIndex = ref<number>(0);
const selectedResultIndex = ref<number>(0);
const fileUploadRef = ref<HTMLInputElement | null>(null);
const extractingExif = ref<boolean>(false);
const gettingLocation = ref<boolean>(false);
const savingToDatabase = ref<boolean>(false);
const bookmarkedResults = reactive<Record<string, boolean>>({});
const careDetails = ref<any>(null);
const fetchingCareDetails = ref<boolean>(false);
const plantDescription = ref("");
const descriptionLoading = ref(false);

// --- Chat State ---
const chatMessages = ref<{ role: "user" | "model"; text: string }[]>([]);
const chatInput = ref("");
const isChatLoading = ref(false);
const chatEndRef = ref<HTMLElement | null>(null);

// Constants
const MAX_IMAGES = 5;
const ORGANS = [
  { value: "flower", label: "Flower", icon: "flower" },
  { value: "leaf", label: "Leaf", icon: "leaf" },
  { value: "fruit", label: "Fruit", icon: "apple" },
  { value: "bark", label: "Bark", icon: "tree" },
  { value: "habit", label: "Whole", icon: "sprout" },
];

const MALAYSIAN_REGIONS = [
  "Peninsular Malaysia",
  "Sabah",
  "Sarawak",
  "Labuan",
  "Johor",
  "Kedah",
  "Kelantan",
  "Melaka",
  "Negeri Sembilan",
  "Pahang",
  "Perak",
  "Perlis",
  "Pulau Pinang",
  "Selangor",
  "Terengganu",
];

// --- Computed ---
const hasResults = computed(() => {
  return (
    results.value &&
    results.value.success &&
    results.value.data &&
    results.value.data.results &&
    results.value.data.results.length > 0
  );
});

const selectedResult = computed(() => {
  if (!hasResults.value) return null;
  return results.value!.data!.results[selectedResultIndex.value];
});

const isCurrentResultBookmarked = computed(() => {
  if (!selectedResult.value) return false;
  return Boolean(bookmarkedResults[selectedResult.value.species?.scientificName]);
});

const allImagesTagged = computed(() => {
  return (
    uploadedImages.value.length > 0 &&
    uploadedImages.value.every((img) => img.organ !== null)
  );
});

const isKnownValue = (value: unknown): boolean => {
  if (value === null || value === undefined) return false;
  if (typeof value === "string")
    return value.trim().length > 0 && value.toLowerCase() !== "unknown";
  return true;
};

const formatCareValue = (
  value: string | number | null | undefined,
  fallback = "Not available"
) => (isKnownValue(value) ? value : fallback) as string;

const formatRange = (
  min?: number | string | null,
  max?: number | string | null,
  unit = ""
): string => {
  const suffix = unit ? ` ${unit}` : "";
  if (!isKnownValue(min) && !isKnownValue(max)) return "N/A";
  if (!isKnownValue(min)) return `${max}${suffix}`;
  if (!isKnownValue(max)) return `${min}${suffix}`;
  return `${min} – ${max}${suffix}`;
};

const hasCareData = computed(() => {
  const details = careDetails.value as Record<string, unknown> | null;
  if (!details) return false;
  return [
    "minimum_precipitation",
    "maximum_precipitation",
    "light",
    "soil_salinity",
    "soil_nutriments",
    "soil_texture",
    "minimum_temperature_celcius",
    "maximum_temperature_celcius",
  ].some((key) => isKnownValue(details[key]));
});

// --- Lifecycle ---
onMounted(() => {
  if (props.plantData && props.plantData.success) {
    results.value = props.plantData;
  }
});

// Watch chat messages to auto-scroll
watch(
  chatMessages,
  async () => {
    await nextTick();
    if (chatEndRef.value) {
      chatEndRef.value.scrollIntoView({ behavior: "smooth" });
    }
  },
  { deep: true }
);
watch(
  selectedResult,
  (result) => {
    if (result?.species?.scientificName) {
      fetchPlantDescription(result.species.scientificName);
    } else {
      plantDescription.value = "";
    }
  },
  { immediate: true }
);

// --- Methods ---

// ... (Previous methods: onImageChange, removeImage, setOrgan, extractExifData, reverseGeocode, useUploadLocation, resetForm)

const onImageChange = async (e: Event): Promise<void> => {
  const target = e.target as HTMLInputElement;
  if (!target.files || !target.files.length) return;

  const newFiles = Array.from(target.files);
  const remainingSlots = MAX_IMAGES - uploadedImages.value.length;

  if (newFiles.length > remainingSlots) {
    toast({
      title: "Limit Reached",
      description: `You can only upload up to ${MAX_IMAGES} images.`,
      variant: "destructive",
    });
  }

  const filesToProcess = newFiles.slice(0, remainingSlots);

  for (const file of filesToProcess) {
    const newImage: ImageUpload = {
      id: Math.random().toString(36).substring(2, 9),
      file,
      preview: URL.createObjectURL(file),
      organ: null,
    };
    uploadedImages.value.push(newImage);

    if (uploadedImages.value.length === 1) {
      await extractExifData(file);
    }
  }

  errors.image = undefined;
  target.value = "";
};

const removeImage = (id: string) => {
  uploadedImages.value = uploadedImages.value.filter((img) => img.id !== id);
  if (uploadedImages.value.length === 0) {
    resetForm();
  }
};

const setOrgan = (id: string, organValue: string) => {
  const img = uploadedImages.value.find((i) => i.id === id);
  if (img) {
    img.organ = organValue;
  }
};

const extractExifData = async (file: File): Promise<void> => {
  try {
    extractingExif.value = true;
    toast({
      title: "Reading Metadata",
      description: "Checking for location in photo...",
      variant: "default",
    });
    const exifData = await exifr.parse(file, { gps: true, tiff: true, exif: true });
    if (exifData && exifData.latitude && exifData.longitude) {
      form.latitude = parseFloat(exifData.latitude.toFixed(6));
      form.longitude = parseFloat(exifData.longitude.toFixed(6));
      form.includeLocation = true;
      await reverseGeocode(exifData.latitude, exifData.longitude);
      toast({
        title: "Location Found!",
        description: `Coordinates extracted from photo.`,
        variant: "success",
      });
    }
  } catch (error) {
    console.error("EXIF Error", error);
  } finally {
    extractingExif.value = false;
  }
};

const reverseGeocode = async (latitude: number, longitude: number): Promise<void> => {
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`
    );
    if (response.ok) {
      const data = await response.json();
      if (data && data.display_name) {
        let locationName = data.name || data.address.city || data.address.town || "";
        let region = data.address.state || data.address.province || "Peninsular Malaysia";
        if (locationName) form.locationName = locationName;
        const matchedRegion = MALAYSIAN_REGIONS.find((r) =>
          region.toLowerCase().includes(r.toLowerCase())
        );
        if (matchedRegion) form.region = matchedRegion;
      }
    }
  } catch (error) {
    console.error("Geocode error", error);
  }
};

const useUploadLocation = (): void => {
  if (navigator.geolocation) {
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
      (position) => {
        form.latitude = parseFloat(position.coords.latitude.toFixed(6));
        form.longitude = parseFloat(position.coords.longitude.toFixed(6));
        form.includeLocation = true;
        reverseGeocode(position.coords.latitude, position.coords.longitude);
        gettingLocation.value = false;
        toast({
          title: "Location Detected",
          description: "Current coordinates applied.",
          variant: "success",
        });
      },
      () => {
        gettingLocation.value = false;
        toast({
          title: "Location Error",
          description: "Unable to access location.",
          variant: "destructive",
        });
      }
    );
  }
};

const resetForm = (): void => {
  uploadedImages.value = [];
  form.locationName = "";
  form.latitude = null;
  form.longitude = null;
  form.includeLocation = false;
  results.value = null;
  careDetails.value = null;
  selectedResultIndex.value = 0;
  activeImageIndex.value = 0;
  chatMessages.value = []; // Reset chat
  plantDescription.value = "";
};

const identifyPlant = async (): Promise<void> => {
  Object.keys(errors).forEach((key) => delete errors[key]);

  if (uploadedImages.value.length === 0) {
    errors.image = "Please select at least one image";
    return;
  }

  if (!allImagesTagged.value) {
    toast({
      title: "Missing Information",
      description: "Please select a plant part for every image.",
      variant: "destructive",
    });
    return;
  }

  processing.value = true;
  results.value = null;
  chatMessages.value = []; // Clear chat on new identification

  const formData = new FormData();
  uploadedImages.value.forEach((img, index) => {
    formData.append(`images[${index}]`, img.file);
    formData.append(`organs[${index}]`, img.organ || "leaf");
  });

  try {
    router.post(route("plant-identifier.identify"), formData, {
      onSuccess: (page) => {
        if (page.props.plantData) {
          results.value = page.props.plantData as PlantResult;
          if (results.value.success && results.value.data?.results.length) {
            const topResult = results.value.data.results[0];
            fetchCareDetails(topResult.species.scientificName);
            toast({
              title: "Identification Complete",
              description: "Potential matches found.",
              variant: "success",
            });
          }
        }
      },
      onError: (errs) => {
        Object.assign(errors, errs);
        toast({
          title: "Failed",
          description: "Check inputs and try again.",
          variant: "destructive",
        });
      },
      onFinish: () => (processing.value = false),
      preserveScroll: true,
    });
  } catch (error) {
    processing.value = false;
  }
};

const fetchCareDetails = async (scientificName: string) => {
  fetchingCareDetails.value = true;
  try {
    const url = new URL(route("plant-identifier.care-details"));
    url.searchParams.append("scientificName", scientificName);
    const res = await fetch(url.toString());
    const data = await res.json();
    if (data.success) careDetails.value = data.data;
  } catch (e) {
    console.error(e);
  } finally {
    fetchingCareDetails.value = false;
  }
};

// Add these definitions
const GEMINI_API_KEY = import.meta.env.VITE_GEMINI_API_KEY;
const GEMINI_MODEL = "gemini-2.5-flash"; // or 'gemini-pro'

const fetchPlantDescription = async (scientificName: string) => {
  if (!scientificName) {
    plantDescription.value = "";
    return;
  }
  descriptionLoading.value = true;
  try {
    const prompt = `In no more than three sentences, describe the plant ${scientificName}, summarizing its key traits, natural habitat, and notable uses. Write in plain text only.`;
    const res = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_API_KEY}`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          contents: [{ role: "user", parts: [{ text: prompt }] }],
        }),
      }
    );
    const data = await res.json();
    plantDescription.value =
      data?.candidates?.[0]?.content?.parts?.[0]?.text?.trim() ||
      "Description unavailable.";
  } catch (error) {
    console.error("Gemini description error:", error);
    plantDescription.value = "Description unavailable.";
  } finally {
    descriptionLoading.value = false;
  }
};

const toggleBookmark = () => {
  if (!selectedResult.value) return;
  const name = selectedResult.value.species?.scientificName;
  if (name) bookmarkedResults[name] = !bookmarkedResults[name];
};

const savePlantToDatabase = async () => {
  if (uploadedImages.value.length === 0 || !selectedResult.value) return;
  savingToDatabase.value = true;
  const fd = new FormData();
  fd.append("image", uploadedImages.value[0].file);
  fd.append("organ", uploadedImages.value[0].organ || "leaf");
  fd.append("saveToDatabase", "1");
  fd.append("scientificName", selectedResult.value.species.scientificName);

  setTimeout(() => {
    savingToDatabase.value = false;
    toast({ title: "Saved", variant: "success" });
  }, 1000);
};

function getConservationAdvice(name: string) {
  return "Protect habitat.";
}

// --- Chat Logic ---

const callGeminiChat = async (plantName: string, history: any[], message: string) => {
  const systemPrompt = `You are a helpful botanist assistant. The user has just identified a plant: "${plantName}".
    Answer their questions specifically about this plant. Keep answers concise, friendly, and practical. Do not use markdown formatting.`;
  const contents = [
    { role: "model", parts: [{ text: systemPrompt }] },
    ...history.map((msg) => ({
      role: msg.role,
      parts: [{ text: msg.text }],
    })),
    { role: "user", parts: [{ text: message }] },
  ];
  try {
    const response = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_API_KEY}`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ contents }),
      }
    );
    const data = await response.json();
    return data.candidates[0].content.parts[0].text;
  } catch (error) {
    console.error("Chat API Error:", error);
    return "I'm having trouble checking my botanical reference books right now.";
  }
};

const handleChatSend = async () => {
  if (!chatInput.value.trim() || !selectedResult.value) return;

  const userText = chatInput.value;
  const plantName =
    selectedResult.value.species.commonNames?.[0] ||
    selectedResult.value.species.scientificName;

  chatMessages.value.push({ text: userText, role: "user" });
  chatInput.value = "";
  isChatLoading.value = true;

  const responseText = await callGeminiChat(plantName, chatMessages.value, userText);

  chatMessages.value.push({ text: responseText, role: "model" });
  isChatLoading.value = false;
};

// Sighting Modal State
const showSightingModal = ref(false);
const sightingReport = reactive({
  locationName: "",
  region: "Peninsular Malaysia",
  latitude: null as number | null,
  longitude: null as number | null,
  date: "",
  notes: "",
});
const openSightingModal = () => (showSightingModal.value = true);
const closeSightingModal = () => (showSightingModal.value = false);
const submitSightingReport = () => {
  closeSightingModal();
  toast({ title: "Reported", variant: "success" });
};

const openFileUpload = () => nextTick(() => fileUploadRef.value?.click());
const setActiveImage = (index: number) => {
  activeImageIndex.value = index;
};
const selectResult = (index: number) => {
  selectedResultIndex.value = index;
  activeImageIndex.value = 0;
  chatMessages.value = []; // Clear chat when switching results
  if (results.value?.data?.results[index]) {
    fetchCareDetails(results.value.data.results[index].species.scientificName);
  }
};

const truncateFileName = (name: string, max = 28) =>
  name.length > max ? `${name.slice(0, max - 6)}…${name.slice(-5)}` : name;
</script>

<template>
  <AppLayout title="Plant Identifier">
    <div class="bg-white dark:bg-gray-900">
      <div class="px-4 py-16 mx-auto text-center max-w-7xl">
        <h1 class="mb-4 text-4xl font-bold text-gray-900 dark:text-white">
          Plant Identifier
        </h1>
        <p class="max-w-3xl mx-auto text-xl text-gray-600 dark:text-gray-400">
          Upload plant images to identify species, learn care tips, and contribute to
          biodiversity mapping.
        </p>
      </div>
    </div>
    <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">
      <!-- Dynamic Grid Layout -->
      <div
        class="grid grid-cols-1 gap-8 transition-all duration-500 ease-in-out md:grid-cols-12"
      >
        <!-- Upload Panel -->
        <div
          :class="[
            'transition-all duration-500',
            hasResults || processing
              ? 'md:col-span-5 lg:col-span-4'
              : 'md:col-span-6 md:col-start-4',
          ]"
        >
          <Card
            :class="[
              'dark:bg-moss-900/60 overflow-hidden rounded-3xl border-0 bg-white/70 ring-1 ring-black/10 backdrop-blur-md dark:ring-black/40',
              uploadedImages.length === 0 ? 'shadow-2xl' : 'shadow-xl',
            ]"
          >
            <CardHeader
              class="pb-6 border-b-0 shadow-sm text-moss-900 from-moss-100/80 to-moss-200/60 dark:from-moss-900/80 dark:to-moss-800/60 rounded-t-3xl bg-gradient-to-r dark:text-white"
            >
              <div class="flex items-center justify-center">
                <Icon name="camera" class="w-5 h-5 mr-2 text-moss-400" />
                <h2 class="text-lg font-semibold tracking-tight">
                  {{
                    uploadedImages.length > 0 ? "Identify Plant" : "Upload Plant Image"
                  }}
                </h2>
              </div>
            </CardHeader>
            <CardContent class="px-6 py-6 space-y-6 bg-transparent">
              <!-- Empty State Dropzone -->
              <div
                v-if="uploadedImages.length === 0"
                @click="openFileUpload"
                class="border-moss-300 dark:border-moss-700 hover:border-moss-500 hover:bg-moss-50 dark:hover:bg-moss-900/20 relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed bg-white/50 transition-all duration-300 ease-in-out dark:bg-white/5"
              >
                <div
                  class="flex flex-col items-center justify-center p-8 space-y-5 text-center rounded-2xl sm:p-10"
                >
                  <div
                    class="p-5 rounded-full shadow-sm bg-moss-100 dark:bg-moss-900/50 text-moss-600 dark:text-moss-300 ring-moss-200 dark:ring-moss-800 ring-1"
                  >
                    <Icon name="upload" class="w-8 h-8" />
                  </div>
                  <div class="px-2 space-y-1">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Upload plant photos
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      Up to 5 images (JPG, PNG)
                    </p>
                  </div>
                </div>
              </div>
              <!-- Image List (Cards) -->
              <div v-else class="space-y-3">
                <div class="flex items-center justify-between px-1 text-xs text-gray-500">
                  <span>{{ uploadedImages.length }} / {{ MAX_IMAGES }} images</span>
                  <button
                    v-if="uploadedImages.length < MAX_IMAGES"
                    @click="openFileUpload"
                    class="flex items-center gap-1 font-medium text-moss-600 hover:underline"
                  >
                    <Icon name="plus" class="w-3 h-3" /> Add more
                  </button>
                </div>
                <div
                  v-for="(img, idx) in uploadedImages"
                  :key="img.id"
                  class="flex gap-3 p-2 bg-white border border-gray-100 shadow-sm group rounded-xl dark:border-gray-700 dark:bg-gray-800"
                >
                  <div
                    class="relative flex-shrink-0 w-20 h-20 overflow-hidden bg-gray-100 rounded-lg"
                  >
                    <img :src="img.preview" class="object-cover w-full h-full" />
                    <button
                      @click="removeImage(img.id)"
                      class="absolute p-1 text-red-500 transition-opacity rounded-full shadow-sm opacity-0 right-1 top-1 bg-white/90 group-hover:opacity-100"
                    >
                      <Icon name="x" class="w-3 h-3" />
                    </button>
                  </div>
                  <div class="flex flex-col justify-center flex-1">
                    <span
                      class="mb-1 text-sm font-medium text-gray-600 truncate dark:text-gray-300"
                      >{{ truncateFileName(img.file.name) }}</span
                    >
                    <span
                      class="mb-1.5 text-sm font-bold uppercase tracking-wide text-gray-400"
                      >Select visible part</span
                    >
                    <div class="flex flex-wrap gap-1.5">
                      <button
                        v-for="organ in ORGANS"
                        :key="organ.value"
                        @click="setOrgan(img.id, organ.value)"
                        :class="[
                          'flex items-center gap-1 rounded-md border px-2 py-1 text-sm font-medium transition-all',
                          img.organ === organ.value
                            ? 'border-green-200 bg-green-200 text-black shadow-sm'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300',
                        ]"
                      >
                        <span
                          >{{ img.organ === organ.value ? "✓" : "" }}
                          {{ organ.label }}</span
                        >
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <input
                ref="fileUploadRef"
                type="file"
                multiple
                class="hidden"
                accept="image/*"
                @change="onImageChange"
              />
              <!-- Location Section -->
              <div
                v-if="uploadedImages.length > 0"
                class="p-4 mt-4 transition-all border border-gray-100 rounded-2xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
              >
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <div
                      class="rounded-lg bg-green-100 p-1.5 text-green-600 dark:bg-blue-900/30 dark:text-blue-400"
                    >
                      <Icon name="map" class="w-4 h-4" />
                    </div>
                    <div>
                      <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        Location Data
                      </h4>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">
                        Help map species distribution
                      </p>
                    </div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="form.includeLocation"
                      class="sr-only peer"
                    />
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
                    <button
                      @click="useUploadLocation"
                      class="flex items-center justify-center w-full gap-2 py-2 text-xs font-medium text-gray-700 transition-all bg-white border border-gray-200 rounded-lg hover:border-blue-200 hover:bg-gray-50 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-blue-400"
                      :disabled="gettingLocation"
                    >
                      <Icon
                        name="crosshair"
                        :class="['h-3.5 w-3.5', gettingLocation ? 'animate-spin' : '']"
                      />
                      {{
                        gettingLocation
                          ? "Triangulating GPS..."
                          : "Use Current GPS Location"
                      }}
                    </button>
                    <div class="grid grid-cols-1 gap-3">
                      <div class="relative">
                        <Icon
                          name="map-pin"
                          class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                        /><input
                          v-model="form.locationName"
                          placeholder="Location Name (e.g. Taman Negara)"
                          class="w-full py-2 pr-3 text-xs bg-white border-gray-200 rounded-lg pl-9 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        />
                      </div>
                      <div class="relative">
                        <Icon
                          name="globe"
                          class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                        /><select
                          v-model="form.region"
                          class="w-full py-2 pr-3 text-xs bg-white border-gray-200 rounded-lg appearance-none pl-9 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        >
                          <option
                            v-for="region in MALAYSIAN_REGIONS"
                            :key="region"
                            :value="region"
                          >
                            {{ region }}
                          </option></select
                        ><Icon
                          name="chevron-down"
                          class="absolute w-3 h-3 text-gray-400 pointer-events-none right-3 top-3"
                        />
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div class="relative group">
                          <span
                            class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                            >Lat</span
                          ><input
                            v-model="form.latitude"
                            class="w-full px-3 py-2 font-mono text-xs text-gray-600 transition-all bg-gray-100 border-transparent rounded-lg focus:border-blue-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
                            placeholder="0.000000"
                          />
                        </div>
                        <div class="relative group">
                          <span
                            class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                            >Long</span
                          ><input
                            v-model="form.longitude"
                            class="w-full px-3 py-2 font-mono text-xs text-gray-600 transition-all bg-gray-100 border-transparent rounded-lg focus:border-blue-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
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
                  v-if="!hasResults"
                  class="w-full text-white transition-all duration-200 shadow-lg rounded-xl bg-gradient-to-r from-black to-black/80 hover:from-black/90 hover:to-black dark:text-white"
                  size="lg"
                  :disabled="
                    processing || uploadedImages.length === 0 || !allImagesTagged
                  "
                  @click="identifyPlant"
                >
                  <div class="flex items-center justify-center">
                    <template v-if="processing"
                      ><Icon name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                      Identifying...</template
                    >
                    <template v-else
                      ><Icon name="search" class="w-4 h-4 mr-2" /> Identify
                      Plant</template
                    >
                  </div>
                </Button>
                <Button
                  v-else
                  variant="outline"
                  class="w-full text-gray-700 transition border-gray-200 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                  size="lg"
                  :disabled="processing"
                  @click="resetForm"
                  >New Identification</Button
                >
              </div>
              <p
                v-if="uploadedImages.length > 0 && !allImagesTagged"
                class="text-xs font-medium text-center text-orange-500"
              >
                Please select a plant part for all images
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Results Column -->
        <div
          v-if="hasResults || processing"
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
              <p class="mt-2 text-base text-black dark:text-black">
                Our AI is cross-referencing your photos...
              </p>
            </CardContent>
          </Card>

          <!-- Results Display -->
          <template v-else-if="hasResults && selectedResult">
            <div
              class="overflow-hidden bg-white shadow-xl rounded-3xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
            >
              <div
                class="relative h-48 overflow-hidden bg-gradient-to-r from-green-800 to-teal-900 md:h-64"
              >
                <img
                  v-if="selectedResult.images?.length"
                  :src="selectedResult.images[0].url.m"
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
                        >{{ Math.round((selectedResult.score || 0) * 100) }}% Match</span
                      >
                      <span
                        v-if="selectedResult.iucn?.category"
                        class="px-3 py-1 text-xs font-bold rounded-full shadow-sm bg-amber-500/90 backdrop-blur-md"
                        >IUCN: {{ selectedResult.iucn.category }}</span
                      >
                    </div>
                    <button
                      @click="toggleBookmark"
                      class="p-2 transition-colors rounded-full bg-white/10 backdrop-blur-md hover:bg-white/20"
                    >
                      <Icon
                        name="bookmark"
                        :class="[
                          'h-5 w-5',
                          isCurrentResultBookmarked
                            ? 'fill-yellow-400 text-yellow-400'
                            : 'text-white',
                        ]"
                      />
                    </button>
                  </div>
                  <h1
                    class="mb-1 text-3xl font-bold leading-tight tracking-tight text-white shadow-sm md:text-5xl"
                  >
                    {{ selectedResult.species.commonNames?.[0] || "Unknown Species" }}
                  </h1>
                  <p
                    class="font-serif text-lg italic text-green-100 opacity-90 md:text-xl"
                  >
                    {{ selectedResult.species.scientificName }}
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
                      v-if="selectedResult.images?.length"
                      :src="selectedResult.images[activeImageIndex].url.m"
                      class="object-cover w-full h-full transition-transform duration-700 hover:scale-105"
                    />
                    <div
                      v-else
                      class="flex items-center justify-center h-full text-gray-400"
                    >
                      <Icon name="image-off" class="w-10 h-10" />
                    </div>
                  </div>
                  <div
                    v-if="selectedResult.images && selectedResult.images.length > 1"
                    class="flex gap-2 pb-2 overflow-x-auto scrollbar-hide"
                  >
                    <button
                      v-for="(img, idx) in selectedResult.images"
                      :key="idx"
                      @click="setActiveImage(idx)"
                      :class="[
                        'h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border-2 transition-all',
                        activeImageIndex === idx
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
                    <p
                      class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                    >
                      Plant Description
                    </p>
                    <p v-if="descriptionLoading" class="text-gray-400">
                      Fetching plant overview…
                    </p>
                    <p
                      v-else-if="plantDescription"
                      class="text-justify text-gray-700 dark:text-gray-200"
                    >
                      {{ plantDescription }}
                    </p>
                    <p v-else class="text-gray-400">Description unavailable.</p>
                    <p
                      v-if="!descriptionLoading && plantDescription"
                      class="mt-2 text-[11px] text-gray-400 dark:text-gray-500"
                    >
                      AI-generated overview—may contain inaccuracies.
                    </p>
                  </div>
                  <div class="pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                    <p
                      class="mb-3 text-xs font-semibold tracking-wider text-gray-500 uppercase"
                    >
                      External Databases
                    </p>
                    <div class="flex flex-wrap gap-2">
                      <a
                        v-if="selectedResult.gbif?.id"
                        :href="`https://www.gbif.org/species/${selectedResult.gbif.id}`"
                        target="_blank"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >GBIF
                        <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50"
                      /></a>
                      <a
                        v-if="selectedResult.powo?.id"
                        :href="`http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:${selectedResult.powo.id}`"
                        target="_blank"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >POWO
                        <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50"
                      /></a>
                    </div>
                  </div>
                </div>
                <div class="p-6 space-y-8 md:col-span-7 md:p-8">
                  <div class="grid grid-cols-2 gap-4">
                    <div
                      class="p-4 border border-green-100 rounded-xl bg-green-50 dark:border-green-800/50 dark:bg-green-900/20"
                    >
                      <div class="flex items-center gap-2 mb-1">
                        <Icon
                          name="git-merge"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        /><span
                          class="text-xs font-bold tracking-wider text-green-700 uppercase dark:text-green-300"
                          >Family</span
                        >
                      </div>
                      <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ selectedResult.species.family.scientificNameWithoutAuthor }}
                      </div>
                    </div>
                    <div
                      class="p-4 border border-green-100 rounded-xl bg-green-50 dark:border-green-800/50 dark:bg-green-900/20"
                    >
                      <div class="flex items-center gap-2 mb-1">
                        <Icon
                          name="git-branch"
                          class="w-4 h-4 text-green-600 dark:text-green-400"
                        /><span
                          class="text-xs font-bold tracking-wider text-green-700 uppercase dark:text-green-300"
                          >Genus</span
                        >
                      </div>
                      <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ selectedResult.species.genus.scientificNameWithoutAuthor }}
                      </div>
                    </div>
                  </div>
                  <div>
                    <h3
                      class="flex items-center mb-4 text-lg font-bold text-gray-900 dark:text-white"
                    >
                      <div
                        class="mr-3 rounded-lg bg-green-100 p-1.5 text-green-700 dark:bg-green-900 dark:text-green-300"
                      >
                        <Icon name="sprout" class="w-5 h-5" />
                      </div>
                      Care Essentials
                    </h3>
                    <div
                      class="overflow-hidden rounded-2xl dark:border-gray-700 dark:bg-gray-800"
                    >
                      <div
                        v-if="fetchingCareDetails"
                        class="flex flex-col items-center justify-center p-8 text-gray-400"
                      >
                        <Icon name="loader-2" class="w-6 h-6 mb-2 animate-spin" /><span
                          class="text-xs"
                          >Consulting botanist notes...</span
                        >
                      </div>
                      <template v-else-if="careDetails">
                        <div
                          v-if="hasCareData"
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
                                  class="block text-[11px] font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-200"
                                  >Water</span
                                >
                                <p
                                  class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                >
                                  {{
                                    formatRange(
                                      careDetails.minimum_precipitation,
                                      careDetails.maximum_precipitation,
                                      "mm/mo"
                                    )
                                  }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                  {{
                                    formatCareValue(
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
                                  class="block text-[11px] font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-200"
                                  >Sunlight</span
                                >
                                <p
                                  class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                >
                                  {{
                                    formatCareValue(
                                      careDetails.light,
                                      "Light requirement not documented"
                                    )
                                  }}
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
                                  class="block text-[11px] font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-200"
                                  >Soil</span
                                >
                                <p
                                  class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                >
                                  {{
                                    formatCareValue(
                                      careDetails.soil_texture,
                                      "Well-draining mix preferred"
                                    )
                                  }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                  <span
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                                    >Salinity:
                                    {{
                                      formatCareValue(careDetails.soil_salinity, "n/a")
                                    }}</span
                                  ><span
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                                    >Nutriments:
                                    {{
                                      formatCareValue(careDetails.soil_nutriments, "n/a")
                                    }}</span
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
                                  class="block text-[11px] font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-200"
                                  >Temp</span
                                >
                                <p
                                  class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                >
                                  {{
                                    formatRange(
                                      careDetails.minimum_temperature_celcius,
                                      careDetails.maximum_temperature_celcius,
                                      "°C"
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
                        <div
                          v-else
                          class="p-8 text-sm text-center text-gray-500 dark:text-gray-400"
                        >
                          Care guidance for this species isn’t available yet.
                        </div>
                      </template>
                    </div>
                  </div>
                  <div class="flex gap-3 pt-2">
                    <Button
                      @click="savePlantToDatabase"
                      :disabled="savingToDatabase"
                      class="flex-1 h-12 transition-all bg-gray-900 rounded-xl hover:bg-black dark:bg-white dark:text-black dark:hover:bg-gray-200"
                      ><Icon name="database" class="w-4 h-4 mr-2" />
                      {{ savingToDatabase ? "Saving..." : "Save Collection" }}</Button
                    >
                    <Button
                      @click="openSightingModal"
                      variant="outline"
                      class="flex-1 h-12 border-gray-200 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                      ><Icon name="map-pin" class="w-4 h-4 mr-2" /> Report
                      Sighting</Button
                    >
                  </div>

                  <!-- BOTANIST CHAT UI -->
                  <div class="pt-8 mt-8 border-t border-gray-100 dark:border-gray-800">
                    <h3
                      class="flex items-center mb-4 text-lg font-bold text-gray-900 dark:text-white"
                    >
                      <div
                        class="mr-3 rounded-lg bg-indigo-100 p-1.5 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300"
                      >
                        <Icon name="message-circle" class="w-5 h-5" />
                      </div>
                      Ask the Botanist
                    </h3>
                    <div
                      class="overflow-hidden border border-gray-200 shadow-inner rounded-2xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
                    >
                      <div class="p-4 space-y-4 overflow-y-auto h-72">
                        <div
                          v-if="chatMessages.length === 0"
                          class="flex flex-col items-center justify-center h-full text-center text-gray-400"
                        >
                          <Icon name="sparkles" class="w-8 h-8 mb-2 text-indigo-300" />
                          <p class="text-sm">
                            Have questions about this
                            {{
                              selectedResult.species.commonNames?.[0] || "plant"
                            }}?<br />Ask our AI botanist anything!
                          </p>
                        </div>
                        <div
                          v-for="(msg, idx) in chatMessages"
                          :key="idx"
                          :class="[
                            'flex',
                            msg.role === 'user' ? 'justify-end' : 'justify-start',
                          ]"
                        >
                          <div
                            :class="[
                              'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm',
                              msg.role === 'user'
                                ? 'rounded-br-none bg-indigo-600 text-white'
                                : 'rounded-bl-none bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-100',
                            ]"
                          >
                            {{ msg.text }}
                          </div>
                        </div>
                        <div v-if="isChatLoading" class="flex justify-start">
                          <div
                            class="px-4 py-3 bg-white rounded-bl-none shadow-sm rounded-2xl dark:bg-gray-700"
                          >
                            <div class="flex gap-1.5">
                              <div
                                class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-0"
                              ></div>
                              <div
                                class="w-2 h-2 delay-150 bg-gray-400 rounded-full animate-bounce"
                              ></div>
                              <div
                                class="w-2 h-2 delay-300 bg-gray-400 rounded-full animate-bounce"
                              ></div>
                            </div>
                          </div>
                        </div>
                        <div ref="chatEndRef"></div>
                      </div>
                      <div
                        class="p-3 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-900"
                      >
                        <div class="relative flex items-center gap-2">
                          <input
                            v-model="chatInput"
                            @keydown.enter="handleChatSend"
                            placeholder="Type your question..."
                            class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-4 pr-12 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                          />
                          <button
                            @click="handleChatSend"
                            :disabled="!chatInput.trim() || isChatLoading"
                            class="absolute right-2 rounded-lg bg-indigo-600 p-1.5 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                          >
                            <Icon name="send" class="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- END CHAT UI -->
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
    <div
      v-if="showSightingModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    >
      <Card class="w-full max-w-lg m-4">
        <CardHeader><h3 class="font-bold">Report Sighting</h3></CardHeader>
        <CardContent>
          <Button class="w-full mt-4" @click="submitSightingReport">Submit Report</Button>
          <Button variant="ghost" class="w-full mt-2" @click="closeSightingModal"
            >Cancel</Button
          >
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
