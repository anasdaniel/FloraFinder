<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
    Sparkles,
    Sprout,
    Sun,
    Thermometer,
    TreeDeciduous,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Sighting {
    id: number;
    user: { id: number; name: string };
    location_name: string | null;
    sighted_at: string;
}

interface Plant {
    id: number;
    scientific_name: string;
    common_name: string | null;
    family: string | null;
    genus: string | null;
    habitat: string | null;
    lifespan: string | null;
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
    care_source: 'gemini' | 'trefle' | null;
    care_cached_at: string | null;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    plant: Plant;
    sightingCount: number;
    recentSightings: Sighting[];
}>();

const isRefreshing = ref(false);

const refreshCareDetails = () => {
    isRefreshing.value = true;
    router.post(
        route('plants.refresh-care', props.plant.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRefreshing.value = false;
            },
        },
    );
};

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const formatMonths = (months: number[] | null): string => {
    if (!months || months.length === 0) return 'Not specified';
    return months.map((m) => monthNames[m - 1]).join(', ');
};

const getLightLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    if (value <= 2) return 'Very Low Light (Shade)';
    if (value <= 4) return 'Low Light (Partial Shade)';
    if (value <= 6) return 'Moderate Light (Partial Sun)';
    if (value <= 8) return 'Bright Light (Full Sun)';
    return 'Very Bright (Intense Sun)';
};

const getHumidityLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    if (value <= 2) return 'Very Dry';
    if (value <= 4) return 'Low Humidity';
    if (value <= 6) return 'Moderate Humidity';
    if (value <= 8) return 'High Humidity';
    return 'Very High Humidity';
};

const getSoilTextureLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    const textures = ['', 'Sandy', 'Loamy Sand', 'Sandy Loam', 'Loam', 'Silt Loam', 'Silt', 'Sandy Clay Loam', 'Clay Loam', 'Silty Clay Loam', 'Sandy Clay'];
    return textures[value] || 'Unknown';
};

const getSoilNutrimentsLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    if (value <= 3) return 'Low Nutrient';
    if (value <= 6) return 'Moderate Nutrient';
    return 'High Nutrient';
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Check if we have Gemini text-based care
const hasGeminiCare = computed(() => {
    return props.plant.care_source === 'gemini' && (
        props.plant.watering_guide ||
        props.plant.sunlight_guide ||
        props.plant.soil_guide ||
        props.plant.temperature_guide ||
        props.plant.care_summary
    );
});

// Check if we have Trefle numeric care
const hasTrefleCare = computed(() => {
    return props.plant.care_source === 'trefle' || (
        props.plant.light !== null ||
        props.plant.minimum_temperature_celsius !== null ||
        props.plant.minimum_precipitation_mm !== null ||
        props.plant.soil_texture !== null
    );
});

// Has any care details (either Gemini or Trefle)
const hasCareDetails = computed(() => hasGeminiCare.value || hasTrefleCare.value);
</script>

<template>
    <Head :title="plant.common_name || plant.scientific_name" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-gray-50">
            <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <Link
                        :href="route('plants.index')"
                        class="inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-gray-900"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Back to Plant Library
                    </Link>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Plant Header Card -->
                        <div class="overflow-hidden bg-white shadow-sm rounded-2xl">
                            <div class="p-6 bg-gradient-to-br from-gray-800 to-gray-900">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="p-2 rounded-lg bg-white/20">
                                                <Leaf class="w-6 h-6 text-white" />
                                            </div>
                                            <span
                                                v-if="hasCareDetails"
                                                class="px-3 py-1 text-xs font-medium text-gray-900 bg-white rounded-full"
                                            >
                                                Care Details Available
                                            </span>
                                        </div>
                                        <h1 class="mb-1 text-2xl font-bold text-white">
                                            {{ plant.common_name || plant.scientific_name }}
                                        </h1>
                                        <p class="text-lg italic text-gray-300">{{ plant.scientific_name }}</p>
                                        <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-300">
                                            <span v-if="plant.family" class="flex items-center gap-1">
                                                <TreeDeciduous class="w-4 h-4" />
                                                {{ plant.family }}
                                            </span>
                                            <span v-if="plant.genus">Genus: {{ plant.genus }}</span>
                                            <span v-if="plant.lifespan" class="capitalize">{{ plant.lifespan }}</span>
                                        </div>
                                    </div>
                                    <button
                                        @click="refreshCareDetails"
                                        :disabled="isRefreshing"
                                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-900 transition-colors bg-white rounded-lg hover:bg-gray-100 disabled:opacity-50"
                                    >
                                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                                        <span class="hidden sm:inline">{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                                    </button>
                                </div>

                                <!-- Stats Bar -->
                                <div class="grid grid-cols-2 gap-3 mt-6 sm:grid-cols-4">
                                    <div class="p-3 rounded-lg bg-white/10">
                                        <div class="flex items-center gap-2">
                                            <Eye class="w-4 h-4 text-gray-300" />
                                            <span class="text-xs text-gray-300">Sightings</span>
                                        </div>
                                        <p class="mt-1 text-xl font-bold text-white">{{ sightingCount }}</p>
                                    </div>
                                    <div v-if="plant.days_to_harvest" class="p-3 rounded-lg bg-white/10">
                                        <div class="flex items-center gap-2">
                                            <Calendar class="w-4 h-4 text-gray-300" />
                                            <span class="text-xs text-gray-300">Harvest</span>
                                        </div>
                                        <p class="mt-1 text-xl font-bold text-white">{{ plant.days_to_harvest }}d</p>
                                    </div>
                                    <div v-if="plant.spread_cm" class="p-3 rounded-lg bg-white/10">
                                        <div class="flex items-center gap-2">
                                            <Sprout class="w-4 h-4 text-gray-300" />
                                            <span class="text-xs text-gray-300">Spread</span>
                                        </div>
                                        <p class="mt-1 text-xl font-bold text-white">{{ plant.spread_cm }}cm</p>
                                    </div>
                                    <div v-if="plant.care_cached_at" class="p-3 rounded-lg bg-white/10">
                                        <div class="flex items-center gap-2">
                                            <RefreshCw class="w-4 h-4 text-gray-300" />
                                            <span class="text-xs text-gray-300">Updated</span>
                                        </div>
                                        <p class="mt-1 text-sm font-medium text-white">{{ formatDate(plant.care_cached_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="plant.description" class="p-6 bg-white shadow-sm rounded-2xl dark:bg-gray-800">
                            <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Description</h2>
                            <p class="leading-relaxed text-gray-600 dark:text-gray-300">{{ plant.description }}</p>
                        </div>

                        <!-- Care Requirements Section -->
                        <div v-if="hasCareDetails">
                            <div class="flex items-center gap-3 mb-4">
                                <h2 class="text-lg font-normal text-gray-900 dark:text-white">Care Requirements</h2>
                                <span
                                    v-if="plant.care_source === 'gemini'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300"
                                >
                                    <Sparkles class="w-3 h-3" />
                                    AI Generated
                                </span>
                                <span
                                    v-else-if="plant.care_source === 'trefle'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                                >
                                    <Leaf class="w-3 h-3" />
                                    Trefle
                                </span>
                            </div>

                            <!-- Gemini Text-Based Care Display -->
                            <div v-if="hasGeminiCare" class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                                <!-- Care Summary -->
                                <div v-if="plant.care_summary" class="mb-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2 mb-2">
                                        <Sparkles class="w-4 h-4 text-purple-500" />
                                        <span class="text-sm font-normal text-purple-600 dark:text-purple-400">Care Summary</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                        {{ plant.care_summary }}
                                    </p>
                                </div>

                                <ul class="space-y-4">
                                    <!-- Watering -->
                                    <li v-if="plant.watering_guide" class="flex gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="p-2 rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                                                <Droplets class="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-normal text-blue-600 dark:text-blue-400 mb-1">Watering</span>
                                            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                                {{ plant.watering_guide }}
                                            </p>
                                        </div>
                                    </li>

                                    <!-- Sunlight -->
                                    <li v-if="plant.sunlight_guide" class="flex gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="p-2 rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                                                <Sun class="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-normal text-amber-600 dark:text-amber-400 mb-1">Sunlight</span>
                                            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                                {{ plant.sunlight_guide }}
                                            </p>
                                        </div>
                                    </li>

                                    <!-- Soil -->
                                    <li v-if="plant.soil_guide" class="flex gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="p-2 rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                                                <TreeDeciduous class="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-normal text-emerald-600 dark:text-emerald-400 mb-1">Soil</span>
                                            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                                {{ plant.soil_guide }}
                                            </p>
                                        </div>
                                    </li>

                                    <!-- Temperature -->
                                    <li v-if="plant.temperature_guide" class="flex gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="p-2 rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400">
                                                <Thermometer class="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-normal text-rose-600 dark:text-rose-400 mb-1">Temperature</span>
                                            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                                {{ plant.temperature_guide }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>

                                <!-- Care Tips -->
                                <div v-if="plant.care_tips" class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Leaf class="w-4 h-4 text-green-500" />
                                        <span class="text-sm font-normal text-green-600 dark:text-green-400">Care Tips</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                        {{ plant.care_tips }}
                                    </p>
                                </div>
                            </div>

                            <!-- Trefle Numeric Care Display -->
                            <div v-else-if="hasTrefleCare" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Light & Humidity -->
                                <div class="p-5 bg-white border border-yellow-100 shadow-sm rounded-xl">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-2 rounded-lg bg-yellow-50">
                                            <Sun class="w-5 h-5 text-yellow-600" />
                                        </div>
                                        <h3 class="font-normal text-gray-900">Light & Atmosphere</h3>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Light Requirements</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ getLightLabel(plant.light) }}</p>
                                            <div v-if="plant.light !== null" class="w-full h-2 mt-2 bg-gray-100 rounded-full">
                                                <div
                                                    class="h-2 bg-yellow-400 rounded-full"
                                                    :style="{ width: `${(plant.light / 10) * 100}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Humidity</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ getHumidityLabel(plant.atmospheric_humidity) }}</p>
                                            <div v-if="plant.atmospheric_humidity !== null" class="w-full h-2 mt-2 bg-gray-100 rounded-full">
                                                <div
                                                    class="h-2 bg-blue-400 rounded-full"
                                                    :style="{ width: `${(plant.atmospheric_humidity / 10) * 100}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Temperature -->
                                <div class="p-5 bg-white border border-red-100 shadow-sm rounded-xl">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-2 rounded-lg bg-red-50">
                                            <Thermometer class="w-5 h-5 text-red-600" />
                                        </div>
                                        <h3 class="font-normal text-gray-900">Temperature</h3>
                                    </div>
                                    <div class="flex justify-between">
                                        <div class="text-center">
                                            <p class="text-xs font-medium text-gray-500 uppercase">Min</p>
                                            <p class="mt-1 text-3xl font-bold text-blue-600">
                                                {{ plant.minimum_temperature_celsius ?? '?' }}°
                                            </p>
                                        </div>
                                        <div class="flex items-center px-4">
                                            <div class="w-16 h-1 rounded-full bg-gradient-to-r from-blue-400 to-red-400"></div>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs font-medium text-gray-500 uppercase">Max</p>
                                            <p class="mt-1 text-3xl font-bold text-red-600">
                                                {{ plant.maximum_temperature_celsius ?? '?' }}°
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Water -->
                                <div class="p-5 bg-white border border-blue-100 shadow-sm rounded-xl">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-2 rounded-lg bg-blue-50">
                                            <Droplets class="w-5 h-5 text-blue-600" />
                                        </div>
                                        <h3 class="font-normal text-gray-900">Water</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Annual Precipitation</p>
                                            <p class="mt-1 font-medium text-gray-900">
                                                <span v-if="plant.minimum_precipitation_mm || plant.maximum_precipitation_mm">
                                                    {{ plant.minimum_precipitation_mm ?? '?' }} - {{ plant.maximum_precipitation_mm ?? '?' }} mm
                                                </span>
                                                <span v-else class="text-gray-400">Not specified</span>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Soil Humidity</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ getHumidityLabel(plant.soil_humidity) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Soil -->
                                <div class="p-5 bg-white border border-amber-100 shadow-sm rounded-xl">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-2 rounded-lg bg-amber-50">
                                            <TreeDeciduous class="w-5 h-5 text-amber-600" />
                                        </div>
                                        <h3 class="font-normal text-gray-900">Soil</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Texture</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ getSoilTextureLabel(plant.soil_texture) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase">Nutrients</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ getSoilNutrimentsLabel(plant.soil_nutriments) }}</p>
                                        </div>
                                        <div v-if="plant.ph_minimum || plant.ph_maximum">
                                            <p class="text-xs font-medium text-gray-500 uppercase">pH Range</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ plant.ph_minimum ?? '?' }} - {{ plant.ph_maximum ?? '?' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Growing Calendar -->
                        <div v-if="plant.growth_months || plant.bloom_months || plant.fruit_months" class="p-6 bg-white shadow-sm rounded-2xl">
                            <h2 class="mb-4 text-lg font-semibold text-gray-900">Growing Calendar</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div v-if="plant.growth_months" class="p-4 border border-gray-200 rounded-xl bg-gray-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Sprout class="w-4 h-4 text-gray-600" />
                                        <span class="font-medium text-gray-900">Growth</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.growth_months) }}</p>
                                </div>
                                <div v-if="plant.bloom_months" class="p-4 border border-pink-100 rounded-xl bg-pink-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Flower2 class="w-4 h-4 text-pink-600" />
                                        <span class="font-medium text-gray-900">Bloom</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.bloom_months) }}</p>
                                </div>
                                <div v-if="plant.fruit_months" class="p-4 border border-orange-100 rounded-xl bg-orange-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Apple class="w-4 h-4 text-orange-600" />
                                        <span class="font-medium text-gray-900">Fruit</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.fruit_months) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sowing -->
                        <div v-if="plant.sowing" class="p-6 bg-white shadow-sm rounded-2xl">
                            <h2 class="mb-3 text-lg font-semibold text-gray-900">Sowing Instructions</h2>
                            <div class="p-4 border-l-4 border-gray-400 rounded-r-lg bg-gray-50">
                                <p class="text-gray-700">{{ plant.sowing }}</p>
                            </div>
                        </div>

                        <!-- No Care Details -->
                        <div v-if="!hasCareDetails" class="p-8 text-center bg-white shadow-sm rounded-2xl">
                            <Leaf class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <h3 class="mb-2 text-lg font-medium text-gray-900">Care Details Not Available</h3>
                            <p class="mb-4 text-gray-500">
                                Care information for this plant hasn't been loaded yet.
                            </p>
                            <button
                                @click="refreshCareDetails"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-gray-900 rounded-lg hover:bg-black disabled:opacity-50"
                            >
                                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                                {{ isRefreshing ? 'Loading...' : 'Load Care Details' }}
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <div class="p-6 bg-white shadow-sm rounded-2xl">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Actions</h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('plant-map')"
                                    class="flex items-center justify-center w-full gap-2 px-4 py-2 text-sm font-medium text-gray-700 transition-colors border border-gray-200 rounded-lg hover:bg-gray-50"
                                >
                                    <MapPin class="w-4 h-4" />
                                    View on Map
                                </Link>
                                <Link
                                    :href="route('plant-identifier')"
                                    class="flex items-center justify-center w-full gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-gray-900 rounded-lg hover:bg-black"
                                >
                                    <Leaf class="w-4 h-4" />
                                    Identify Similar
                                </Link>
                            </div>
                        </div>

                        <!-- Recent Sightings -->
                        <div v-if="recentSightings.length > 0" class="p-6 bg-white shadow-sm rounded-2xl">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Recent Sightings</h3>
                            <div class="space-y-3">
                                <div v-for="sighting in recentSightings" :key="sighting.id" class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                                    <div class="flex items-center justify-center w-10 h-10 bg-gray-200 rounded-full">
                                        <User class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ sighting.user.name }}</p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span v-if="sighting.location_name" class="flex items-center gap-1 truncate">
                                                <MapPin class="w-3 h-3" />
                                                {{ sighting.location_name }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <Calendar class="w-3 h-3" />
                                                {{ formatDate(sighting.sighted_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- External Links -->
                        <div v-if="plant.gbif_id || plant.powo_id" class="p-6 bg-white shadow-sm rounded-2xl">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Resources</h3>
                            <div class="space-y-2">
                                <a
                                    v-if="plant.gbif_id"
                                    :href="`https://www.gbif.org/species/${plant.gbif_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 transition-colors rounded-lg bg-gray-50 hover:bg-gray-100"
                                >
                                    <span>GBIF Database</span>
                                    <span class="text-gray-400">→</span>
                                </a>
                                <a
                                    v-if="plant.powo_id"
                                    :href="`https://powo.science.kew.org/taxon/${plant.powo_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 transition-colors rounded-lg bg-gray-50 hover:bg-gray-100"
                                >
                                    <span>Kew POWO</span>
                                    <span class="text-gray-400">→</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
