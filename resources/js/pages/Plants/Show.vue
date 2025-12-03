<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Clock,
    Droplets,
    Leaf,
    MapPin,
    RefreshCw,
    Sun,
    Thermometer,
    User,
    Sprout,
    TreeDeciduous,
    Flower2,
    Apple,
} from 'lucide-vue-next';
import { ref } from 'vue';

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

const hasCareDetails = props.plant.care_cached_at !== null;
</script>

<template>
    <Head :title="plant.common_name || plant.scientific_name" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-gray-50">
            <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
                <!-- Back Button -->
                <Link
                    :href="route('plants.index')"
                    class="inline-flex items-center gap-2 mb-6 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Back to Plants
                </Link>

                <!-- Main Content -->
                <div class="overflow-hidden bg-white shadow-xl rounded-2xl">
                    <!-- Header -->
                    <div class="p-8 bg-gradient-to-br from-green-600 to-emerald-700">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <Leaf class="w-8 h-8 text-white/80" />
                                    <span
                                        v-if="hasCareDetails"
                                        class="px-3 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full"
                                    >
                                        Care Details Available
                                    </span>
                                </div>
                                <h1 class="mb-2 text-3xl font-bold text-white">
                                    {{ plant.common_name || plant.scientific_name }}
                                </h1>
                                <p class="text-lg italic text-green-100">{{ plant.scientific_name }}</p>
                                <div class="flex items-center gap-4 mt-4 text-sm text-green-100">
                                    <span v-if="plant.family">Family: {{ plant.family }}</span>
                                    <span v-if="plant.genus">Genus: {{ plant.genus }}</span>
                                </div>
                            </div>

                            <button
                                @click="refreshCareDetails"
                                :disabled="isRefreshing"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 transition-colors bg-white rounded-lg hover:bg-green-50 disabled:opacity-50"
                            >
                                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                                {{ isRefreshing ? 'Refreshing...' : 'Refresh Care Data' }}
                            </button>
                        </div>

                        <!-- Stats Bar -->
                        <div class="grid grid-cols-2 gap-4 mt-6 sm:grid-cols-4">
                            <div class="p-3 rounded-lg bg-white/10">
                                <p class="text-xs text-green-200">Sightings</p>
                                <p class="text-2xl font-bold text-white">{{ sightingCount }}</p>
                            </div>
                            <div v-if="plant.days_to_harvest" class="p-3 rounded-lg bg-white/10">
                                <p class="text-xs text-green-200">Days to Harvest</p>
                                <p class="text-2xl font-bold text-white">{{ plant.days_to_harvest }}</p>
                            </div>
                            <div v-if="plant.spread_cm" class="p-3 rounded-lg bg-white/10">
                                <p class="text-xs text-green-200">Spread</p>
                                <p class="text-2xl font-bold text-white">{{ plant.spread_cm }} cm</p>
                            </div>
                            <div v-if="plant.care_cached_at" class="p-3 rounded-lg bg-white/10">
                                <p class="text-xs text-green-200">Last Updated</p>
                                <p class="text-sm font-medium text-white">{{ formatDate(plant.care_cached_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-8">
                        <!-- Description -->
                        <div v-if="plant.description" class="mb-8">
                            <h2 class="mb-3 text-xl font-semibold text-gray-900">Description</h2>
                            <p class="leading-relaxed text-gray-700">{{ plant.description }}</p>
                        </div>

                        <!-- Care Details Grid -->
                        <div v-if="hasCareDetails" class="mb-8">
                            <h2 class="mb-4 text-xl font-semibold text-gray-900">Care Requirements</h2>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Light & Humidity -->
                                <div class="p-5 border border-yellow-100 rounded-xl bg-yellow-50">
                                    <div class="flex items-center gap-2 mb-4">
                                        <Sun class="w-5 h-5 text-yellow-600" />
                                        <h3 class="font-semibold text-gray-900">Light & Atmosphere</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Light Requirements</p>
                                            <p class="font-medium text-gray-900">{{ getLightLabel(plant.light) }}</p>
                                            <div v-if="plant.light !== null" class="w-full h-2 mt-1 bg-gray-200 rounded-full">
                                                <div
                                                    class="h-2 bg-yellow-500 rounded-full"
                                                    :style="{ width: `${(plant.light / 10) * 100}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Atmospheric Humidity</p>
                                            <p class="font-medium text-gray-900">{{ getHumidityLabel(plant.atmospheric_humidity) }}</p>
                                            <div v-if="plant.atmospheric_humidity !== null" class="w-full h-2 mt-1 bg-gray-200 rounded-full">
                                                <div
                                                    class="h-2 bg-blue-400 rounded-full"
                                                    :style="{ width: `${(plant.atmospheric_humidity / 10) * 100}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Temperature -->
                                <div class="p-5 border border-red-100 rounded-xl bg-red-50">
                                    <div class="flex items-center gap-2 mb-4">
                                        <Thermometer class="w-5 h-5 text-red-600" />
                                        <h3 class="font-semibold text-gray-900">Temperature</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <div>
                                                <p class="text-sm text-gray-500">Minimum</p>
                                                <p class="text-2xl font-bold text-blue-600">
                                                    {{ plant.minimum_temperature_celsius ?? '?' }}°C
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-500">Maximum</p>
                                                <p class="text-2xl font-bold text-red-600">
                                                    {{ plant.maximum_temperature_celsius ?? '?' }}°C
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Water & Precipitation -->
                                <div class="p-5 border border-blue-100 rounded-xl bg-blue-50">
                                    <div class="flex items-center gap-2 mb-4">
                                        <Droplets class="w-5 h-5 text-blue-600" />
                                        <h3 class="font-semibold text-gray-900">Water Requirements</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Annual Precipitation</p>
                                            <p class="font-medium text-gray-900">
                                                <span v-if="plant.minimum_precipitation_mm || plant.maximum_precipitation_mm">
                                                    {{ plant.minimum_precipitation_mm ?? '?' }} - {{ plant.maximum_precipitation_mm ?? '?' }} mm
                                                </span>
                                                <span v-else>Not specified</span>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Soil Humidity</p>
                                            <p class="font-medium text-gray-900">{{ getHumidityLabel(plant.soil_humidity) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Soil -->
                                <div class="p-5 border border-amber-100 rounded-xl bg-amber-50">
                                    <div class="flex items-center gap-2 mb-4">
                                        <TreeDeciduous class="w-5 h-5 text-amber-600" />
                                        <h3 class="font-semibold text-gray-900">Soil Requirements</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Soil Texture</p>
                                            <p class="font-medium text-gray-900">{{ getSoilTextureLabel(plant.soil_texture) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Nutrient Level</p>
                                            <p class="font-medium text-gray-900">{{ getSoilNutrimentsLabel(plant.soil_nutriments) }}</p>
                                        </div>
                                        <div v-if="plant.ph_minimum || plant.ph_maximum">
                                            <p class="text-sm text-gray-500">pH Range</p>
                                            <p class="font-medium text-gray-900">{{ plant.ph_minimum ?? '?' }} - {{ plant.ph_maximum ?? '?' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Growing Calendar -->
                        <div v-if="plant.growth_months || plant.bloom_months || plant.fruit_months" class="mb-8">
                            <h2 class="mb-4 text-xl font-semibold text-gray-900">Growing Calendar</h2>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div v-if="plant.growth_months" class="p-4 rounded-lg bg-green-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Sprout class="w-4 h-4 text-green-600" />
                                        <span class="font-medium text-gray-900">Growth Months</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.growth_months) }}</p>
                                </div>
                                <div v-if="plant.bloom_months" class="p-4 rounded-lg bg-pink-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Flower2 class="w-4 h-4 text-pink-600" />
                                        <span class="font-medium text-gray-900">Bloom Months</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.bloom_months) }}</p>
                                </div>
                                <div v-if="plant.fruit_months" class="p-4 rounded-lg bg-orange-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Apple class="w-4 h-4 text-orange-600" />
                                        <span class="font-medium text-gray-900">Fruit Months</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ formatMonths(plant.fruit_months) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sowing Info -->
                        <div v-if="plant.sowing" class="mb-8">
                            <h2 class="mb-3 text-xl font-semibold text-gray-900">Sowing Instructions</h2>
                            <div class="p-4 border-l-4 border-green-500 bg-green-50">
                                <p class="text-gray-700">{{ plant.sowing }}</p>
                            </div>
                        </div>

                        <!-- No Care Details Message -->
                        <div v-if="!hasCareDetails" class="p-8 text-center bg-gray-50 rounded-xl">
                            <Leaf class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <h3 class="mb-2 text-lg font-medium text-gray-900">Care Details Not Available</h3>
                            <p class="mb-4 text-gray-500">
                                Care information for this plant hasn't been loaded yet. Click the button below to fetch the latest data.
                            </p>
                            <button
                                @click="refreshCareDetails"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50"
                            >
                                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                                {{ isRefreshing ? 'Loading...' : 'Load Care Details' }}
                            </button>
                        </div>

                        <!-- Recent Sightings -->
                        <div v-if="recentSightings.length > 0" class="mt-8">
                            <h2 class="mb-4 text-xl font-semibold text-gray-900">Recent Sightings</h2>
                            <div class="divide-y divide-gray-100 rounded-lg bg-gray-50">
                                <div v-for="sighting in recentSightings" :key="sighting.id" class="flex items-center gap-4 p-4">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-full">
                                        <User class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ sighting.user.name }}</p>
                                        <div class="flex items-center gap-3 text-sm text-gray-500">
                                            <span v-if="sighting.location_name" class="flex items-center gap-1">
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
                        <div v-if="plant.gbif_id || plant.powo_id" class="pt-6 mt-8 border-t border-gray-200">
                            <h3 class="mb-3 text-sm font-medium text-gray-500">External Resources</h3>
                            <div class="flex gap-3">
                                <a
                                    v-if="plant.gbif_id"
                                    :href="`https://www.gbif.org/species/${plant.gbif_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200"
                                >
                                    View on GBIF →
                                </a>
                                <a
                                    v-if="plant.powo_id"
                                    :href="`https://powo.science.kew.org/taxon/${plant.powo_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200"
                                >
                                    View on POWO →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
