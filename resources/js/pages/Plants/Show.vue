<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
  Apple,
  ArrowLeft,
  Calendar,
  Droplets,
  Eye,
  Flower2,
  Leaf,
  MapPin,
  RefreshCw,
  ShieldAlert,
  Sparkles,
  Sprout,
  Sun,
  Thermometer,
  TreeDeciduous,
  User,
} from "lucide-vue-next";
import { computed, ref } from "vue";

interface Sighting {
  id: number;
  user: { id: number; name: string };
  location_name: string | null;
  sighted_at: string;
  image_url: string | null;
}

interface Plant {
  id: number;
  scientific_name: string;
  common_name: string | null;
  malay_name: string | null;
  image_url: string | null;
  family: string | null;
  genus: string | null;
  habitat: string | null;
  lifespan: string | null;
  is_endemic: boolean;
  is_native: boolean;
  gbif_id: string | null;
  powo_id: string | null;
  iucn_category: string | null;
  description: string | null;
  sowing: string | null;
  days_to_harvest: number | null;
  row_spacing_cm: number | null;
  spread_cm: number | null;
  ph_minimum: number | null;
  ph_maximum: number | null;
  light: number | null;
  atmospheric_humidity: number | null;
  growth_months: number[] | null;
  bloom_months: number[] | null;
  fruit_months: number[] | null;
  minimum_precipitation_mm: number | null;
  maximum_precipitation_mm: number | null;
  minimum_temperature_celsius: number | null;
  maximum_temperature_celsius: number | null;
  soil_nutriments: number | null;
  soil_salinity: number | null;
  soil_texture: number | null;
  soil_humidity: number | null;
  // Gemini text-based care fields
  watering_guide: string | null;
  sunlight_guide: string | null;
  soil_guide: string | null;
  temperature_guide: string | null;
  care_summary: string | null;
  care_tips: string | null;
  care_source: "gemini" | "trefle" | null;
  care_cached_at: string | null;
  reference_images: string[] | null;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  plant: Plant;
  sightingCount: number;
  recentSightings: Sighting[];
  sightingImages: string[];
}>();

const isRefreshing = ref(false);
const selectedProvider = ref<'gemini' | 'trefle'>('gemini');
const activeImageIndex = ref(0);
const visibleCount = ref(8); // Initial number of images to show

const refreshCareDetails = () => {
  isRefreshing.value = true;
  router.post(
    route("plants.refresh-care", props.plant.id),
    { provider: selectedProvider.value },
    {
      preserveScroll: true,
      onFinish: () => {
        isRefreshing.value = false;
      },
    }
  );
};

const monthNames = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "May",
  "Jun",
  "Jul",
  "Aug",
  "Sep",
  "Oct",
  "Nov",
  "Dec",
];

const formatMonths = (months: number[] | null): string => {
  if (!months || months.length === 0) return "Not specified";
  return months.map((m) => monthNames[m - 1]).join(", ");
};

const getLightLabel = (value: number | null): string => {
  if (value === null) return "Unknown";
  if (value <= 2) return "Very Low Light (Shade)";
  if (value <= 4) return "Low Light (Partial Shade)";
  if (value <= 6) return "Moderate Light (Partial Sun)";
  if (value <= 8) return "Bright Light (Full Sun)";
  return "Very Bright (Intense Sun)";
};

const getHumidityLabel = (value: number | null): string => {
  if (value === null) return "Unknown";
  if (value <= 2) return "Very Dry";
  if (value <= 4) return "Low Humidity";
  if (value <= 6) return "Moderate Humidity";
  if (value <= 8) return "High Humidity";
  return "Very High Humidity";
};

const getSoilTextureLabel = (value: number | null): string => {
  if (value === null) return "Unknown";
  const textures = [
    "",
    "Sandy",
    "Loamy Sand",
    "Sandy Loam",
    "Loam",
    "Silt Loam",
    "Silt",
    "Sandy Clay Loam",
    "Clay Loam",
    "Silty Clay Loam",
    "Sandy Clay",
  ];
  return textures[value] || "Unknown";
};

const getSoilNutrimentsLabel = (value: number | null): string => {
  if (value === null) return "Unknown";
  if (value <= 3) return "Low Nutrient";
  if (value <= 6) return "Moderate Nutrient";
  return "High Nutrient";
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

// Check if we have Gemini text-based care
const hasGeminiCare = computed(() => {
  return (
    props.plant.care_source === "gemini" &&
    (props.plant.watering_guide ||
      props.plant.sunlight_guide ||
      props.plant.soil_guide ||
      props.plant.temperature_guide ||
      props.plant.care_summary)
  );
});

// Check if we have Trefle numeric care
const hasTrefleCare = computed(() => {
  return (
    props.plant.care_source === "trefle" &&
    (props.plant.light !== null ||
    props.plant.atmospheric_humidity !== null ||
    props.plant.minimum_temperature_celsius !== null ||
    props.plant.maximum_temperature_celsius !== null ||
    props.plant.ph_minimum !== null ||
    props.plant.ph_maximum !== null ||
    props.plant.minimum_precipitation_mm !== null ||
    props.plant.maximum_precipitation_mm !== null ||
    props.plant.soil_texture !== null ||
    props.plant.soil_nutriments !== null ||
    props.plant.growth_months !== null ||
    props.plant.bloom_months !== null ||
    props.plant.fruit_months !== null)
  );
});

// Has any care details (either Gemini or Trefle)
const hasCareDetails = computed(() => hasGeminiCare.value || hasTrefleCare.value);

// Generic parser that converts textual care fields into a list of bullet-friendly strings.
// Accepts arrays, JSON strings, or delimited strings.
const parseCareText = (raw: string | null | undefined): string[] => {
  if (!raw) return [];
  if (Array.isArray(raw)) return raw.map((r) => String(r).trim()).filter(Boolean);
  const str = String(raw).trim();
  try {
    if (str.startsWith('{') || str.startsWith('[')) {
      const parsed = JSON.parse(str);
      if (Array.isArray(parsed)) return parsed.map((p) => String(p).trim()).filter(Boolean);
      if (typeof parsed === 'string') {
        const arr = parsed.split(/\r?\n|;|•|-|\u2022/).map((s) => s.trim()).filter(Boolean);
        if (arr.length) return arr;
      }
    }
  } catch (err) {
    // fall through to heuristics
  }
  // Try splitting common delimiters
  let parts = str.split(/\r?\n|;|•|-|\u2022/).map((s) => s.trim()).filter(Boolean);
  if (parts.length) return parts;
  parts = str.split(/,(?!\d)/).map((s) => s.trim()).filter(Boolean);
  if (parts.length > 1) return parts;
  return [str];
};

// Parse care tips into an array for clean display.
const parsedCareTips = computed(() => {
  return parseCareText(props.plant.care_tips);
});

const galleryImages = computed(() => {
  const images = [];
  if (props.plant.image_url) {
    images.push(props.plant.image_url);
  }

  // Add reference images from API if available
  if (props.plant.reference_images && Array.isArray(props.plant.reference_images)) {
    props.plant.reference_images.forEach((img) => {
      if (img !== props.plant.image_url) {
        images.push(img);
      }
    });
  }

  if (props.sightingImages && props.sightingImages.length > 0) {
    // Filter out duplicates
    const sightingImages = props.sightingImages.filter(
      (img) =>
        img !== props.plant.image_url &&
        (!props.plant.reference_images || !props.plant.reference_images.includes(img))
    );
    images.push(...sightingImages);
  }
  return images;
});

const mainImage = computed(() => {
  return galleryImages.value[activeImageIndex.value] || galleryImages.value[0] || null;
});

const displayedImages = computed(() => {
  return galleryImages.value.slice(0, visibleCount.value);
});

const hasMoreImages = computed(() => {
  return visibleCount.value < galleryImages.value.length;
});

const showMore = () => {
  visibleCount.value += 8;
};

const getIucnCategoryInfo = (category: string | null): { label: string; color: string; bgColor: string } | null => {
  if (!category) return null;

  const categoryMap: Record<string, { label: string; color: string; bgColor: string }> = {
    'EX': { label: 'Extinct', color: 'text-white', bgColor: 'bg-gray-800' },
    'EW': { label: 'Extinct in Wild', color: 'text-white', bgColor: 'bg-gray-700' },
    'CR': { label: 'Critically Endangered', color: 'text-white', bgColor: 'bg-red-600' },
    'EN': { label: 'Endangered', color: 'text-white', bgColor: 'bg-orange-600' },
    'VU': { label: 'Vulnerable', color: 'text-white', bgColor: 'bg-amber-600' },
    'NT': { label: 'Near Threatened', color: 'text-white', bgColor: 'bg-yellow-600' },
    'LC': { label: 'Least Concern', color: 'text-white', bgColor: 'bg-green-600' },
    'DD': { label: 'Data Deficient', color: 'text-white', bgColor: 'bg-blue-600' },
    'NE': { label: 'Not Evaluated', color: 'text-white', bgColor: 'bg-gray-600' },
  };

  return categoryMap[category.toUpperCase()] || { label: category, color: 'text-white', bgColor: 'bg-gray-600' };
};

// Check if plant is threatened and should not have care guide
const isThreatened = computed(() => {
  if (!props.plant.iucn_category) return false;
  const threatenedCategories = ['EX', 'EW', 'CR', 'EN', 'VU', 'NT'];
  return threatenedCategories.includes(props.plant.iucn_category.toUpperCase());
});

// Get conservation message based on status
const getConservationMessage = (category: string | null): string => {
  if (!category) return '';

  const messages: Record<string, string> = {
    'EX': 'This species is extinct with no known individuals remaining. Conservation efforts focus on understanding what led to its extinction and preventing similar outcomes for other species.',
    'EW': 'This species is extinct in the wild and only survives in captivity or as naturalized populations. Conservation efforts aim to reintroduce individuals to their native habitat when conditions permit.',
    'CR': 'This species is critically endangered and faces an extremely high risk of extinction in the wild. Immediate conservation action is essential for its survival.',
    'EN': 'This species is endangered and faces a very high risk of extinction in the wild. Active conservation measures are needed to protect remaining populations.',
    'VU': 'This species is vulnerable to extinction and faces a high risk in the wild. Conservation efforts focus on protecting habitats and monitoring populations.',
    'NT': 'This species is near threatened and may qualify for a threatened category in the near future. Preventive conservation measures can help ensure its survival.',
  };

  return messages[category.toUpperCase()] || 'This species requires conservation attention. Please consult local wildlife authorities before any cultivation attempts.';
};
</script>

<template>
  <Head :title="plant.common_name || plant.scientific_name" />

  <AppLayout>
    <div class="min-h-screen pb-12 bg-gray-50">
      <!-- Hero Section -->
      <div class="relative text-white bg-gray-900">
        <div class="absolute inset-0 overflow-hidden">
          <img
            v-if="mainImage"
            :src="mainImage"
            class="object-cover w-full h-full scale-105 opacity-40 blur-sm"
            alt="Plant background"
          />
          <div
            v-else
            class="w-full h-full bg-gradient-to-br from-green-900 to-gray-900 opacity-90"
          ></div>
          <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>
        </div>

        <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 sm:py-20">
          <div class="flex flex-col items-center justify-between gap-8 md:flex-row md:items-end">
            <div class="flex-1">
              <Link
                :href="route('plants.index')"
                class="inline-flex items-center gap-2 mb-6 text-sm text-gray-300 transition-colors hover:text-white"
              >
                <ArrowLeft class="w-4 h-4" />
                Back to Library
              </Link>

              <div class="flex flex-wrap items-center gap-3 mb-4">
                <span
                  v-if="plant.malay_name"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-100 text-sm backdrop-blur-sm border border-emerald-500/20 font-medium"
                >
                  Nama Tempatan: {{ plant.malay_name }}
                </span>
                <span
                  v-if="plant.is_endemic"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/20 text-amber-100 text-sm backdrop-blur-sm border border-amber-500/20 font-medium"
                >
                  Endemic to Malaysia
                </span>
                <span
                  v-if="plant.is_native"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/20 text-blue-100 text-sm backdrop-blur-sm border border-blue-500/20 font-medium"
                >
                  Native to Malaysia
                </span>
                <span
                  v-if="plant.family"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-sm backdrop-blur-sm border border-white/10"
                >
                  <TreeDeciduous class="w-3.5 h-3.5" />
                  {{ plant.family }}
                </span>
                <span
                  v-if="plant.iucn_category"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm backdrop-blur-sm border font-medium"
                  :class="[
                    getIucnCategoryInfo(plant.iucn_category)?.color,
                    getIucnCategoryInfo(plant.iucn_category)?.bgColor,
                    'border-white/20'
                  ]"
                >
                  <ShieldAlert class="w-3.5 h-3.5" />
                  {{ getIucnCategoryInfo(plant.iucn_category)?.label }}
                </span>
                <span
                  v-if="isThreatened"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500/20 text-red-100 text-sm backdrop-blur-sm border border-red-500/20"
                >
                  <ShieldAlert class="w-3.5 h-3.5" />
                  Conservation Notice
                </span>
                <span
                  v-else-if="hasCareDetails"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-500/20 text-green-100 text-sm backdrop-blur-sm border border-green-500/20"
                >
                  <Leaf class="w-3.5 h-3.5" />
                  Care Guide
                </span>
              </div>

              <h1 class="mb-2 text-4xl font-bold tracking-tight md:text-5xl">
                {{ plant.common_name || plant.scientific_name }}
              </h1>
              <p class="font-serif text-xl italic text-gray-300">
                {{ plant.scientific_name }}
              </p>
            </div>

            <div v-if="mainImage" class="w-full md:w-64 lg:w-80 shrink-0">
              <div class="relative overflow-hidden shadow-2xl aspect-square rounded-2xl border-4 border-white/10 group">
                <img
                  :src="mainImage"
                  class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-110"
                  :alt="plant.common_name || plant.scientific_name"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="relative z-10 px-4 mx-auto -mt-8 max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Main Content -->
          <div class="space-y-8 lg:col-span-2">
            <!-- Quick Stats -->
            <div
              class="grid grid-cols-2 gap-6 p-6 bg-white border border-gray-100 shadow-sm rounded-2xl sm:grid-cols-4"
            >
              <div class="p-4 text-center rounded-xl bg-gray-50">
                <div class="flex justify-center mb-2">
                  <Eye class="w-6 h-6 text-blue-500" />
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ sightingCount }}</div>
                <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">
                  Sightings
                </div>
              </div>
              <div class="p-4 text-center rounded-xl bg-gray-50">
                <div class="flex justify-center mb-2">
                  <Calendar class="w-6 h-6 text-green-500" />
                </div>
                <div class="text-2xl font-bold text-gray-900">
                  {{ plant.days_to_harvest ? plant.days_to_harvest + "d" : "-" }}
                </div>
                <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">
                  Harvest
                </div>
              </div>
              <div class="p-4 text-center rounded-xl bg-gray-50">
                <div class="flex justify-center mb-2">
                  <Sprout class="w-6 h-6 text-emerald-500" />
                </div>
                <div class="text-2xl font-bold text-gray-900">
                  {{ plant.spread_cm ? plant.spread_cm + "cm" : "-" }}
                </div>
                <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">
                  Spread
                </div>
              </div>
              <div class="p-4 text-center rounded-xl bg-gray-50">
                <div class="flex justify-center mb-2">
                  <RefreshCw class="w-6 h-6 text-purple-500" />
                </div>
                <div class="text-sm font-bold text-gray-900 mt-1.5 mb-0.5">
                  {{ plant.care_cached_at ? formatDate(plant.care_cached_at) : "-" }}
                </div>
                <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">
                  Updated
                </div>
              </div>
            </div>

            <!-- Description -->
            <div
              v-if="plant.description"
              class="p-8 bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <h2 class="flex items-center gap-2 mb-4 text-xl font-bold text-gray-900">
                <span class="w-1 h-6 bg-green-500 rounded-full"></span>
                About this Plant
              </h2>
              <p class="text-lg leading-relaxed text-gray-600">{{ plant.description }}</p>
            </div>

            <!-- Image Gallery -->
            <div
              v-if="galleryImages.length > 0"
              class="p-8 bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <h2 class="flex items-center gap-2 mb-6 text-xl font-bold text-gray-900">
                <span class="w-1 h-6 bg-green-500 rounded-full"></span>
                Photo Gallery
              </h2>
              <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                <div
                  v-for="(image, index) in displayedImages"
                  :key="index"
                  class="relative overflow-hidden aspect-square rounded-xl group cursor-pointer border-2 transition-all"
                  :class="activeImageIndex === index ? 'border-green-500 ring-2 ring-green-500/20' : 'border-transparent hover:border-gray-200'"
                  @click="activeImageIndex = index"
                >
                  <img
                    :src="image"
                    class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-110"
                    :alt="`${plant.common_name || plant.scientific_name} gallery image ${index + 1}`"
                  />
                  <div class="absolute inset-0 transition-opacity opacity-0 bg-black/20 group-hover:opacity-100"></div>
                </div>
              </div>

              <!-- Show More Button -->
              <div v-if="hasMoreImages" class="mt-8 flex justify-center">
                <button
                  @click="showMore"
                  class="flex items-center gap-2 px-6 py-3 text-sm font-semibold text-green-700 transition-all bg-green-50 border border-green-100 rounded-xl hover:bg-green-100 hover:border-green-200"
                >
                  <RefreshCw class="w-4 h-4" />
                  Show More Photos ({{ galleryImages.length - visibleCount }} remaining)
                </button>
              </div>
            </div>

            <!-- Conservation Notice for Threatened Plants -->
            <div v-if="isThreatened" class="space-y-6">
              <div class="p-8 border-2 border-red-200 bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl">
                <div class="flex items-start gap-4 mb-6">
                  <div class="p-3 text-red-600 bg-white border-2 border-red-200 shadow-sm rounded-xl">
                    <ShieldAlert class="w-8 h-8" />
                  </div>
                  <div class="flex-1">
                    <h2 class="mb-2 text-2xl font-bold text-red-900">Conservation Notice</h2>
                    <p class="text-lg font-medium text-red-800">
                      {{ getIucnCategoryInfo(plant.iucn_category)?.label }} Species
                    </p>
                  </div>
                </div>

                <div class="p-6 mb-6 bg-white border border-red-100 rounded-xl">
                  <p class="text-base leading-relaxed text-gray-700">
                    {{ getConservationMessage(plant.iucn_category) }}
                  </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="p-5 bg-white border border-red-100 rounded-xl">
                    <h3 class="mb-3 font-semibold text-gray-900">Why This Matters</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-red-500 rounded-full"></span>
                        <span>Threatened species should not be removed from wild habitats</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-red-500 rounded-full"></span>
                        <span>Collection may be illegal without proper permits</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-red-500 rounded-full"></span>
                        <span>Every individual is crucial for species survival</span>
                      </li>
                    </ul>
                  </div>

                  <div class="p-5 bg-white border border-red-100 rounded-xl">
                    <h3 class="mb-3 font-semibold text-gray-900">How You Can Help</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-green-600 rounded-full"></span>
                        <span>Report sightings to conservation organizations</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-green-600 rounded-full"></span>
                        <span>Support habitat protection initiatives</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-green-600 rounded-full"></span>
                        <span>Educate others about conservation needs</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <span class="flex-shrink-0 w-1.5 h-1.5 mt-1.5 bg-green-600 rounded-full"></span>
                        <span>Avoid disturbing plants in their natural habitat</span>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="p-4 mt-4 border-l-4 border-red-600 bg-white/80 rounded-r-xl">
                  <p class="text-sm font-medium text-gray-800">
                    <strong>Important:</strong> This plant should not be cultivated without proper authorization from conservation authorities. Contact local wildlife agencies for guidance on legal conservation efforts.
                  </p>
                </div>

                <!-- Conservation Resources & Contacts -->
                <div class="p-6 mt-4 bg-white border border-gray-200 rounded-xl">
                  <h3 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-900">
                    <span class="w-1 h-5 bg-green-600 rounded-full"></span>
                    Conservation Resources & Contacts
                  </h3>

                  <div class="space-y-4">
                    <div class="p-4 transition-colors border border-gray-100 rounded-lg hover:bg-gray-50">
                      <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 p-2 rounded-lg bg-green-100">
                          <ShieldAlert class="w-5 h-5 text-green-700" />
                        </div>
                        <div class="flex-1">
                          <h4 class="mb-1 font-semibold text-gray-900">IUCN Red List</h4>
                          <p class="mb-2 text-sm text-gray-600">International Union for Conservation of Nature</p>
                          <div class="space-y-1 text-sm">
                            <a href="https://www.iucnredlist.org" target="_blank" class="block text-blue-600 hover:text-blue-800 hover:underline">
                              Website: www.iucnredlist.org
                            </a>
                            <p class="text-gray-600">Email: redlist@iucn.org</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="p-4 transition-colors border border-gray-100 rounded-lg hover:bg-gray-50">
                      <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 p-2 rounded-lg bg-blue-100">
                          <TreeDeciduous class="w-5 h-5 text-blue-700" />
                        </div>
                        <div class="flex-1">
                          <h4 class="mb-1 font-semibold text-gray-900">Botanic Gardens Conservation International (BGCI)</h4>
                          <p class="mb-2 text-sm text-gray-600">Global network for plant conservation</p>
                          <div class="space-y-1 text-sm">
                            <a href="https://www.bgci.org" target="_blank" class="block text-blue-600 hover:text-blue-800 hover:underline">
                              Website: www.bgci.org
                            </a>
                            <p class="text-gray-600">Email: info@bgci.org</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="p-4 transition-colors border border-gray-100 rounded-lg hover:bg-gray-50">
                      <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 p-2 rounded-lg bg-purple-100">
                          <Leaf class="w-5 h-5 text-purple-700" />
                        </div>
                        <div class="flex-1">
                          <h4 class="mb-1 font-semibold text-gray-900">Local Wildlife & Conservation Agencies</h4>
                          <p class="mb-2 text-sm text-gray-600">Contact your regional authorities for local guidance</p>
                          <ul class="space-y-1 text-sm text-gray-600">
                            <li class="flex items-start gap-1.5">
                              <span class="flex-shrink-0 w-1 h-1 mt-2 bg-gray-400 rounded-full"></span>
                              <span>Department of Environment & Natural Resources</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                              <span class="flex-shrink-0 w-1 h-1 mt-2 bg-gray-400 rounded-full"></span>
                              <span>National Parks & Wildlife Services</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                              <span class="flex-shrink-0 w-1 h-1 mt-2 bg-gray-400 rounded-full"></span>
                              <span>Botanical Research Institutions</span>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    <div class="p-4 border border-amber-200 rounded-lg bg-amber-50">
                      <p class="text-sm text-amber-900">
                        <strong>Note:</strong> If you encounter this species in the wild, please report your sighting to help conservation efforts. Document the location, date, and habitat conditions without disturbing the plant.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Care Guide (Only for Non-Threatened Plants) -->
            <div v-else-if="hasCareDetails" class="space-y-6">
              <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Care Guide</h2>
                <div class="flex items-center gap-3">
                  <!-- Provider Toggle -->
                  <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
                    <button
                      @click="selectedProvider = 'gemini'"
                      :class="[
                        'flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                        selectedProvider === 'gemini'
                          ? 'bg-white text-purple-700 shadow-sm'
                          : 'text-gray-500 hover:text-gray-700'
                      ]"
                      :disabled="isRefreshing"
                    >
                      <Sparkles class="w-3 h-3" />
                      Gemini
                    </button>
                    <button
                      @click="selectedProvider = 'trefle'"
                      :class="[
                        'flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                        selectedProvider === 'trefle'
                          ? 'bg-white text-green-700 shadow-sm'
                          : 'text-gray-500 hover:text-gray-700'
                      ]"
                      :disabled="isRefreshing"
                    >
                      <TreeDeciduous class="w-3 h-3" />
                      Trefle
                    </button>
                  </div>
                  <span
                    v-if="plant.care_source === 'gemini'"
                    class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded-full"
                  >
                    <Sparkles class="w-3 h-3" /> AI Generated
                  </span>
                  <span
                    v-else-if="plant.care_source === 'trefle'"
                    class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full"
                  >
                    <TreeDeciduous class="w-3 h-3" /> Trefle
                  </span>
                  <button
                    @click="refreshCareDetails"
                    :disabled="isRefreshing"
                    class="flex items-center gap-1 text-sm text-gray-500 transition-colors hover:text-gray-900"
                  >
                    <RefreshCw
                      class="w-3 h-3"
                      :class="{ 'animate-spin': isRefreshing }"
                    />
                    Refresh
                  </button>
                </div>
              </div>

              <!-- Gemini Care -->
              <div v-if="hasGeminiCare" class="grid gap-6">
                <div
                  v-if="plant.care_summary"
                  class="p-6 border border-purple-100 bg-purple-50 rounded-2xl"
                >
                  <div class="flex items-start gap-4">
                    <div class="p-3 text-purple-600 bg-white shadow-sm rounded-xl">
                      <Sparkles class="w-6 h-6" />
                    </div>
                    <div>
                      <h3 class="mb-2 font-semibold text-purple-900">Summary</h3>
                      <p class="leading-relaxed text-purple-800 text-justify">
                        {{ plant.care_summary }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                  <div
                    v-if="plant.watering_guide"
                    class="p-6 transition-colors bg-white border border-gray-100 shadow-sm rounded-2xl hover:border-blue-200"
                  >
                    <div class="flex items-center gap-3 mb-4">
                      <div class="p-2 text-blue-600 bg-blue-100 rounded-lg">
                        <Droplets class="w-5 h-5" />
                      </div>
                      <h3 class="font-semibold text-gray-900">Watering</h3>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                      {{ plant.watering_guide }}
                    </p>
                  </div>

                  <div
                    v-if="plant.sunlight_guide"
                    class="p-6 transition-colors bg-white border border-gray-100 shadow-sm rounded-2xl hover:border-amber-200"
                  >
                    <div class="flex items-center gap-3 mb-4">
                      <div class="p-2 rounded-lg bg-amber-100 text-amber-600">
                        <Sun class="w-5 h-5" />
                      </div>
                      <h3 class="font-semibold text-gray-900">Sunlight</h3>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                      {{ plant.sunlight_guide }}
                    </p>
                  </div>

                  <div
                    v-if="plant.soil_guide"
                    class="p-6 transition-colors bg-white border border-gray-100 shadow-sm rounded-2xl hover:border-emerald-200"
                  >
                    <div class="flex items-center gap-3 mb-4">
                      <div class="p-2 rounded-lg bg-emerald-100 text-emerald-600">
                        <TreeDeciduous class="w-5 h-5" />
                      </div>
                      <h3 class="font-semibold text-gray-900">Soil</h3>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                      {{ plant.soil_guide }}
                    </p>
                  </div>

                  <div
                    v-if="plant.temperature_guide"
                    class="p-6 transition-colors bg-white border border-gray-100 shadow-sm rounded-2xl hover:border-rose-200"
                  >
                    <div class="flex items-center gap-3 mb-4">
                      <div class="p-2 rounded-lg bg-rose-100 text-rose-600">
                        <Thermometer class="w-5 h-5" />
                      </div>
                      <h3 class="font-semibold text-gray-900">Temperature</h3>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                      {{ plant.temperature_guide }}
                    </p>
                  </div>
                </div>

                <div
                  v-if="plant.care_tips"
                  class="p-6 border border-green-100 bg-green-50 rounded-2xl"
                >
                  <div class="flex items-start gap-4">
                    <div class="p-3 text-green-600 bg-white shadow-sm rounded-xl">
                      <Leaf class="w-6 h-6" />
                    </div>
                    <div>
                      <h3 class="mb-2 font-semibold text-green-900">Pro Tips</h3>
                      <ul class="pl-6 space-y-2 text-green-800 list-disc text-justify">
                        <li v-for="(tip, idx) in parsedCareTips" :key="idx">{{ tip }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Trefle Care (Primary for Database) -->
              <div v-else-if="hasTrefleCare" class="grid gap-6">
                <div class="grid gap-6 sm:grid-cols-2">
                  <!-- Light & Atmosphere -->
                  <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-sm rounded-3xl hover:shadow-md hover:-translate-y-1">
                    <div class="flex items-center gap-3 mb-6">
                      <div class="p-2.5 text-yellow-600 bg-yellow-50 rounded-xl">
                        <Sun class="w-6 h-6" />
                      </div>
                      <h3 class="font-bold text-gray-900">Light & Atmosphere</h3>
                    </div>
                    <div class="space-y-6">
                      <div v-if="plant.light !== null">
                        <div class="flex justify-between mb-2 text-sm">
                          <span class="text-gray-500">Light Level</span>
                          <span class="font-bold text-gray-900">{{ getLightLabel(plant.light) }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden bg-gray-100 rounded-full">
                          <div class="h-full bg-yellow-400 rounded-full" :style="{ width: `${(plant.light || 0) * 10}%` }"></div>
                        </div>
                      </div>
                      <div v-if="plant.atmospheric_humidity !== null">
                        <div class="flex justify-between mb-2 text-sm">
                          <span class="text-gray-500">Humidity</span>
                          <span class="font-bold text-gray-900">{{ getHumidityLabel(plant.atmospheric_humidity) }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden bg-gray-100 rounded-full">
                          <div class="h-full bg-blue-400 rounded-full" :style="{ width: `${(plant.atmospheric_humidity || 0) * 10}%` }"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Temperature & PH -->
                  <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-sm rounded-3xl hover:shadow-md hover:-translate-y-1">
                    <div class="flex items-center gap-3 mb-6">
                      <div class="p-2.5 text-rose-600 bg-rose-50 rounded-xl">
                        <Thermometer class="w-6 h-6" />
                      </div>
                      <h3 class="font-bold text-gray-900">Conditions</h3>
                    </div>
                    <div class="space-y-4">
                      <div v-if="plant.minimum_temperature_celsius !== null || plant.maximum_temperature_celsius !== null" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Temperature</span>
                        <span class="text-sm font-bold text-gray-900">
                          {{ plant.minimum_temperature_celsius }}°C - {{ plant.maximum_temperature_celsius }}°C
                        </span>
                      </div>
                      <div v-if="plant.ph_minimum !== null || plant.ph_maximum !== null" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Soil pH</span>
                        <span class="text-sm font-bold text-gray-900">
                          {{ plant.ph_minimum }} - {{ plant.ph_maximum }}
                        </span>
                      </div>
                      <div v-if="plant.minimum_precipitation_mm !== null" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Min Rain/yr</span>
                        <span class="text-sm font-bold text-gray-900">{{ plant.minimum_precipitation_mm }}mm</span>
                      </div>
                    </div>
                  </div>

                  <!-- Soil Requirements -->
                  <div v-if="plant.soil_texture || plant.soil_nutriments !== null" class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-sm rounded-3xl hover:shadow-md hover:-translate-y-1">
                    <div class="flex items-center gap-3 mb-6">
                      <div class="p-2.5 text-emerald-600 bg-emerald-50 rounded-xl">
                        <Sprout class="w-6 h-6" />
                      </div>
                      <h3 class="font-bold text-gray-900">Soil Requirements</h3>
                    </div>
                    <div class="space-y-4">
                      <div v-if="plant.soil_texture" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Texture</span>
                        <span class="text-sm font-bold text-gray-900">{{ getSoilTextureLabel(plant.soil_texture) }}</span>
                      </div>
                      <div v-if="plant.soil_nutriments !== null" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Nutrients</span>
                        <span class="text-sm font-bold text-gray-900">{{ getSoilNutrimentsLabel(plant.soil_nutriments) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Growth & Space -->
                  <div v-if="plant.spread_cm || plant.row_spacing_cm || plant.days_to_harvest" class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-sm rounded-3xl hover:shadow-md hover:-translate-y-1">
                    <div class="flex items-center gap-3 mb-6">
                      <div class="p-2.5 text-blue-600 bg-blue-50 rounded-xl">
                        <RefreshCw class="w-6 h-6" />
                      </div>
                      <h3 class="font-bold text-gray-900">Growth Specs</h3>
                    </div>
                    <div class="space-y-4">
                      <div v-if="plant.spread_cm" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Plant Spread</span>
                        <span class="text-sm font-bold text-gray-900">{{ plant.spread_cm }} cm</span>
                      </div>
                      <div v-if="plant.days_to_harvest" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <span class="text-sm font-medium text-gray-500">Maturation</span>
                        <span class="text-sm font-bold text-gray-900">{{ plant.days_to_harvest }} days</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Note about data source -->
                <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl flex items-start gap-3">
                  <div class="p-1.5 bg-white rounded-lg shadow-sm mt-0.5">
                    <TreeDeciduous class="w-4 h-4 text-green-600" />
                  </div>
                  <p class="text-xs text-gray-500 leading-relaxed">
                    Data provided by Trefle Plant Database. This scientific data reflects optimal growing conditions found in natural habitats and established agricultural records.
                  </p>
                </div>
              </div>
            </div>

            <!-- Empty State for Care (Non-Threatened Plants Only) -->
            <div
              v-else-if="!isThreatened"
              class="p-12 text-center bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <div
                class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full"
              >
                <Leaf class="w-8 h-8 text-gray-400" />
              </div>
              <h3 class="mb-2 text-lg font-semibold text-gray-900">
                No Care Guide Available
              </h3>
              <p class="max-w-md mx-auto mb-4 text-gray-500">
                We haven't generated a care guide for this plant yet. Select a provider
                and click the button below to fetch details.
              </p>
              <!-- Provider Toggle -->
              <div class="flex items-center justify-center gap-1 p-1 mx-auto mb-6 bg-gray-100 rounded-lg w-fit">
                <button
                  @click="selectedProvider = 'gemini'"
                  :class="[
                    'flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                    selectedProvider === 'gemini'
                      ? 'bg-white text-purple-700 shadow-sm'
                      : 'text-gray-500 hover:text-gray-700'
                  ]"
                  :disabled="isRefreshing"
                >
                  <Sparkles class="w-4 h-4" />
                  Gemini (AI)
                </button>
                <button
                  @click="selectedProvider = 'trefle'"
                  :class="[
                    'flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                    selectedProvider === 'trefle'
                      ? 'bg-white text-green-700 shadow-sm'
                      : 'text-gray-500 hover:text-gray-700'
                  ]"
                  :disabled="isRefreshing"
                >
                  <TreeDeciduous class="w-4 h-4" />
                  Trefle (Database)
                </button>
              </div>
              <button
                @click="refreshCareDetails"
                :disabled="isRefreshing"
                class="inline-flex items-center gap-2 px-6 py-3 font-medium text-white transition-colors bg-gray-900 rounded-xl hover:bg-black disabled:opacity-50"
              >
                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                {{ isRefreshing ? "Generating Guide..." : "Generate Care Guide" }}
              </button>
            </div>

            <!-- Growing Calendar -->
            <div
              v-if="plant.growth_months || plant.bloom_months || plant.fruit_months"
              class="p-8 bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <h2 class="mb-6 text-xl font-bold text-gray-900">Seasonal Calendar</h2>
              <div class="space-y-4">
                <div
                  v-if="plant.growth_months"
                  class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl"
                >
                  <div class="p-2 text-gray-600 bg-white rounded-lg shadow-sm">
                    <Sprout class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">Growth Period</div>
                    <div class="text-sm text-gray-500">
                      {{ formatMonths(plant.growth_months) }}
                    </div>
                  </div>
                </div>
                <div
                  v-if="plant.bloom_months"
                  class="flex items-center gap-4 p-4 bg-pink-50 rounded-xl"
                >
                  <div class="p-2 text-pink-600 bg-white rounded-lg shadow-sm">
                    <Flower2 class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">Blooming Season</div>
                    <div class="text-sm text-gray-500">
                      {{ formatMonths(plant.bloom_months) }}
                    </div>
                  </div>
                </div>
                <div
                  v-if="plant.fruit_months"
                  class="flex items-center gap-4 p-4 bg-orange-50 rounded-xl"
                >
                  <div class="p-2 text-orange-600 bg-white rounded-lg shadow-sm">
                    <Apple class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">Fruiting Season</div>
                    <div class="text-sm text-gray-500">
                      {{ formatMonths(plant.fruit_months) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Actions Card -->
            <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
              <h3 class="mb-4 font-semibold text-gray-900">Quick Actions</h3>
              <div class="space-y-3">
                <Link
                  :href="route('plant-map')"
                  class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-medium text-gray-700 transition-colors bg-gray-50 hover:bg-gray-100 rounded-xl"
                >
                  <MapPin class="w-4 h-4" /> View Distribution
                </Link>
                <Link
                  :href="route('plant-identifier')"
                  class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-medium text-white transition-colors bg-gray-900 hover:bg-black rounded-xl"
                >
                  <Leaf class="w-4 h-4" /> Identify Similar
                </Link>
              </div>
            </div>

            <!-- Recent Sightings -->
            <div
              v-if="recentSightings.length > 0"
              class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Recent Sightings</h3>
                <span
                  class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-full"
                  >{{ recentSightings.length }}</span
                >
              </div>
              <div class="space-y-4">
                <div
                  v-for="sighting in recentSightings"
                  :key="sighting.id"
                  class="flex items-start gap-3 group"
                >
                  <div
                    class="flex items-center justify-center flex-shrink-0 w-10 h-10 overflow-hidden bg-gray-100 rounded-full"
                  >
                    <img
                      v-if="sighting.image_url"
                      :src="sighting.image_url"
                      class="object-cover w-full h-full"
                    />
                    <User v-else class="w-5 h-5 text-gray-400" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p
                      class="text-sm font-medium text-gray-900 truncate transition-colors group-hover:text-green-600"
                    >
                      {{ sighting.user.name }}
                    </p>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                      <span
                        v-if="sighting.location_name"
                        class="truncate max-w-[100px]"
                        >{{ sighting.location_name }}</span
                      >
                      <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                      <span>{{ formatDate(sighting.sighted_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- External Resources -->
            <div
              v-if="plant.gbif_id || plant.powo_id"
              class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
              <h3 class="mb-4 font-semibold text-gray-900">External Data</h3>
              <div class="space-y-2">
                <a
                  v-if="plant.gbif_id"
                  :href="`https://www.gbif.org/species/${plant.gbif_id}`"
                  target="_blank"
                  class="flex items-center justify-between p-3 text-sm font-medium text-gray-700 transition-colors rounded-xl bg-gray-50 hover:bg-gray-100"
                >
                  <span>GBIF Database</span>
                  <span class="text-gray-400">↗</span>
                </a>
                <a
                  v-if="plant.powo_id"
                  :href="`https://powo.science.kew.org/taxon/${plant.powo_id}`"
                  target="_blank"
                  class="flex items-center justify-between p-3 text-sm font-medium text-gray-700 transition-colors rounded-xl bg-gray-50 hover:bg-gray-100"
                >
                  <span>Kew POWO</span>
                  <span class="text-gray-400">↗</span>
                </a>
              </div>
            </div>
          </div> <!-- End Sidebar -->
        </div> <!-- End Grid -->
      </div> <!-- End Container -->
    </div> <!-- End Page -->
  </AppLayout>
</template>
