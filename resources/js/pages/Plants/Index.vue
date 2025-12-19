<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Droplets,
    Filter,
    Grid3X3,
    Leaf,
    List,
    RefreshCw,
    Search,
    SearchX,
    ShieldAlert,
    Sparkles,
    Sun,
    Thermometer,
    TreeDeciduous,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

interface Plant {
    id: number;
    scientific_name: string;
    common_name: string | null;
    malay_name: string | null;
    family: string | null;
    genus: string | null;
    habitat: string | null;
    lifespan: string | null;
    is_endemic: boolean;
    is_native: boolean;
    description: string | null;
    light: number | null;
    atmospheric_humidity: number | null;
    minimum_temperature_celsius: number | null;
    maximum_temperature_celsius: number | null;
    minimum_precipitation_mm: number | null;
    maximum_precipitation_mm: number | null;
    soil_texture: number | null;
    soil_humidity: number | null;
    watering_guide: string | null;
    sunlight_guide: string | null;
    soil_guide: string | null;
    temperature_guide: string | null;
    care_summary: string | null;
    care_source: 'gemini' | 'trefle' | null;
    care_cached_at: string | null;
    iucn_category: string | null;
    latest_image: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedPlants {
    data: Plant[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    plants: PaginatedPlants;
    families: string[];
    filters: {
        search?: string;
        family?: string;
        is_endangered?: boolean;
    };
}>();

// Reactive state
const searchQuery = ref(props.filters.search || '');
const selectedFamily = ref(props.filters.family || '');
const isEndangeredOnly = ref(props.filters.is_endangered || false);
const viewMode = ref<'grid' | 'list'>('grid');
const isLoading = ref(false);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([selectedFamily, isEndangeredOnly], () => {
    applyFilters();
});

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        route('plants.index'),
        {
            search: searchQuery.value || undefined,
            family: selectedFamily.value || undefined,
            is_endangered: isEndangeredOnly.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedFamily.value = '';
    isEndangeredOnly.value = false;
};

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedFamily.value || isEndangeredOnly.value;
});

// Compute stats
const stats = computed(() => {
    const withCare = props.plants.data.filter(p => p.care_cached_at !== null).length;
    const uniqueFamilies = new Set(props.plants.data.map(p => p.family).filter(Boolean)).size;
    return {
        total: props.plants.total,
        withCare,
        families: uniqueFamilies,
    };
});

const getLightLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    if (value <= 3) return 'Low Light';
    if (value <= 6) return 'Partial Sun';
    return 'Full Sun';
};

const getHumidityLabel = (value: number | null): string => {
    if (value === null) return 'Unknown';
    if (value <= 3) return 'Low';
    if (value <= 6) return 'Moderate';
    return 'High';
};

const formatTemperature = (min: number | null, max: number | null): string => {
    if (min === null && max === null) return 'Unknown';
    if (min !== null && max !== null) return `${min}°C - ${max}°C`;
    if (min !== null) return `Min ${min}°C`;
    return `Max ${max}°C`;
};

const hasCareDetails = (plant: Plant): boolean => {
    // Check for Gemini text-based care
    const hasGeminiCare = plant.care_source === 'gemini' && !!(
        plant.watering_guide ||
        plant.sunlight_guide ||
        plant.soil_guide ||
        plant.temperature_guide ||
        plant.care_summary
    );

    // Check for Trefle numeric care
    const hasTrefleCare = plant.care_source === 'trefle' && (
        plant.light !== null ||
        plant.minimum_temperature_celsius !== null ||
        plant.minimum_precipitation_mm !== null ||
        plant.soil_texture !== null
    );

    return hasGeminiCare || hasTrefleCare;
};

const getIucnCategoryInfo = (category: string | null): { label: string; color: string; bgColor: string } | null => {
    if (!category) return null;

    const categoryMap: Record<string, { label: string; color: string; bgColor: string }> = {
        'EX': { label: 'Extinct', color: 'text-gray-900', bgColor: 'bg-gray-200' },
        'EW': { label: 'Extinct in Wild', color: 'text-gray-800', bgColor: 'bg-gray-300' },
        'CR': { label: 'Critically Endangered', color: 'text-red-800', bgColor: 'bg-red-100' },
        'EN': { label: 'Endangered', color: 'text-orange-800', bgColor: 'bg-orange-100' },
        'VU': { label: 'Vulnerable', color: 'text-amber-800', bgColor: 'bg-amber-100' },
        'NT': { label: 'Near Threatened', color: 'text-yellow-800', bgColor: 'bg-yellow-100' },
        'LC': { label: 'Least Concern', color: 'text-green-800', bgColor: 'bg-green-100' },
        'DD': { label: 'Data Deficient', color: 'text-blue-800', bgColor: 'bg-blue-100' },
        'NE': { label: 'Not Evaluated', color: 'text-gray-700', bgColor: 'bg-gray-100' },
    };

    return categoryMap[category.toUpperCase()] || { label: category, color: 'text-gray-700', bgColor: 'bg-gray-100' };
};

const isThreatened = (plant: Plant): boolean => {
    if (!plant.iucn_category) return false;
    const threatenedCategories = ['EX', 'EW', 'CR', 'EN', 'VU', 'NT'];
    return threatenedCategories.includes(plant.iucn_category.toUpperCase());
};

const goToPage = (url: string | null) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Plant Library" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header & Stats Combined -->
                <div class="mb-8 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Plant Library</h1>
                            <p class="mt-1 text-gray-600">
                                Browse our collection of plants with detailed care information
                            </p>
                        </div>
                        <Link
                            :href="route('plant-identifier')"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white transition-all bg-gray-900 rounded-xl hover:bg-black shadow-lg shadow-gray-900/10 hover:shadow-gray-900/20"
                        >
                            <Leaf class="w-4 h-4" />
                            Identify Plant
                        </Link>
                    </div>

                    <!-- Modern Stats Strip -->
                    <div class="grid grid-cols-2 gap-px overflow-hidden bg-gray-200 border border-gray-200 shadow-sm rounded-2xl md:grid-cols-4">
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <BookOpen class="w-6 h-6 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ plants.total }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Total Plants</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-blue-50 rounded-xl">
                                <Leaf class="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.withCare }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Care Guides</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-purple-50 rounded-xl">
                                <TreeDeciduous class="w-6 h-6 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ families.length }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Families</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-amber-50 rounded-xl">
                                <Sun class="w-6 h-6 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.families }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">On Page</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating Toolbar (Search & Filter) -->
                <div class="sticky top-4 z-20 mb-8">
                    <div class="p-2 bg-white/80 backdrop-blur-xl border border-gray-200/60 shadow-lg shadow-gray-200/20 rounded-2xl">
                        <div class="grid grid-cols-1 gap-2 lg:grid-cols-12">
                            <!-- Search -->
                            <div class="lg:col-span-8 relative group">
                                <Search class="absolute left-3.5 top-3 h-5 w-5 text-gray-400 group-focus-within:text-gray-600 transition-colors" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name, scientific name, or family..."
                                    class="w-full h-11 pl-11 pr-4 bg-gray-50/50 hover:bg-gray-100/50 focus:bg-white border-transparent focus:border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-gray-900/5"
                                />
                            </div>

                            <!-- Endangered Filter -->
                            <div class="lg:col-span-3">
                                <label class="flex items-center justify-center h-11 w-full gap-2.5 bg-gray-50/50 hover:bg-gray-100/50 cursor-pointer rounded-xl border border-transparent transition-all has-[:checked]:bg-red-600 has-[:checked]:text-white">
                                    <input
                                        v-model="isEndangeredOnly"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-gray-900 focus:ring-offset-red-600 focus:ring-white/20"
                                    />
                                    <ShieldAlert class="w-4 h-4" />
                                    <span class="text-sm font-medium">Endangered Only</span>
                                </label>
                            </div>

                            <!-- View Toggle -->
                            <div class="lg:col-span-1 flex bg-gray-100 p-1 rounded-xl">
                                <button
                                    @click="viewMode = 'grid'"
                                    class="flex-1 flex items-center justify-center rounded-lg transition-all"
                                    :class="viewMode === 'grid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                                >
                                    <Grid3X3 class="w-5 h-5" />
                                </button>
                                <button
                                    @click="viewMode = 'list'"
                                    class="flex-1 flex items-center justify-center rounded-lg transition-all"
                                    :class="viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                                >
                                    <List class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Active Filters Indicator -->
                        <div v-if="hasActiveFilters" class="flex items-center justify-between px-2 pt-2 mt-2 border-t border-gray-100">
                            <span class="text-xs font-medium text-gray-500">Active filters applied</span>
                            <button
                                @click="clearFilters"
                                class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline"
                            >
                                Clear all
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <RefreshCw class="w-8 h-8 text-gray-400 animate-spin" />
                </div>

                <!-- Plants Grid -->
                <div v-else-if="plants.data.length > 0">
                    <!-- Grid View -->
                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Link
                            v-for="plant in plants.data"
                            :key="plant.id"
                            :href="route('plants.show', plant.id)"
                            class="overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm group rounded-xl hover:shadow-lg"
                        >
                            <!-- Plant Header with Full Image -->
                            <div class="relative w-full h-48 overflow-hidden bg-gray-100 border-b border-gray-100">
                                <!-- Full Background Image -->
                                <img
                                    v-if="plant.latest_image"
                                    :src="plant.latest_image"
                                    :alt="plant.common_name || plant.scientific_name"
                                    class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
                                />
                                <div v-else class="flex items-center justify-center w-full h-full bg-gradient-to-br from-green-50 to-emerald-100">
                                    <Leaf class="w-12 h-12 text-emerald-200" />
                                </div>

                                <!-- Overlay Badges -->
                                <div class="absolute inset-0 p-4 pointer-events-none">
                                    <div class="flex items-start justify-between">
                                        <!-- Left Badge (Status) -->
                                        <div v-if="isThreatened(plant)" class="inline-flex">
                                            <span class="px-2.5 py-1 text-xs font-semibold text-white bg-red-500/90 backdrop-blur-md rounded-lg shadow-sm border border-red-400/50">
                                                Conservation
                                            </span>
                                        </div>
                                        <div v-else class="inline-flex">
                                            <!-- Empty placeholder to push right badge -->
                                        </div>

                                        <!-- Right Badge (Care) -->
                                        <div v-if="!isThreatened(plant) && hasCareDetails(plant)" class="inline-flex">
                                            <span class="px-2.5 py-1 text-xs font-semibold text-emerald-800 bg-white/90 backdrop-blur-md rounded-lg shadow-sm border border-white/50">
                                                Care Info
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Plant Content -->
                            <div class="p-5">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-emerald-700 transition-colors line-clamp-1">
                                        {{ plant.common_name || plant.scientific_name }}
                                    </h3>
                                    <p v-if="plant.malay_name" class="text-sm font-medium text-emerald-600 mt-0.5 line-clamp-1">
                                        {{ plant.malay_name }}
                                    </p>
                                    <p class="text-sm font-medium italic text-gray-500 font-serif mt-0.5 line-clamp-1">
                                        {{ plant.scientific_name }}
                                    </p>
                                </div>

                            <!-- Plant Info -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <div v-if="plant.family" class="flex items-center gap-2 text-gray-600">
                                            <TreeDeciduous class="w-4 h-4 text-gray-400" />
                                            <span class="font-medium">{{ plant.family }}</span>
                                        </div>
                                    </div>

                                    <div v-if="plant.iucn_category" class="flex items-center gap-2">
                                        <ShieldAlert class="w-4 h-4 text-gray-400" />
                                        <span
                                            class="px-2.5 py-0.5 text-xs font-semibold rounded-full border border-transparent"
                                            :class="[
                                                getIucnCategoryInfo(plant.iucn_category)?.color,
                                                getIucnCategoryInfo(plant.iucn_category)?.bgColor
                                            ]"
                                        >
                                            {{ getIucnCategoryInfo(plant.iucn_category)?.label }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Conservation Notice for Threatened Plants -->
                                <div v-if="isThreatened(plant)" class="mt-4 pt-3 border-t border-gray-100">
                                    <div class="p-3 bg-red-50 border border-red-100 rounded-lg">
                                        <div class="flex items-start gap-2 mb-1">
                                            <ShieldAlert class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5" />
                                            <p class="text-xs font-bold text-red-800">Protected Species</p>
                                        </div>
                                        <p class="text-xs text-red-600/90 leading-relaxed">
                                            Requires strict conservation measures.
                                        </p>
                                    </div>
                                </div>

                                <!-- Quick Care Stats (Non-Threatened Plants Only) -->
                                <div v-else-if="hasCareDetails(plant)" class="mt-4 pt-3 border-t border-gray-100">
                                    <!-- Trefle Numeric Stats -->
                                    <div v-if="plant.care_source === 'trefle' && plant.light !== null" class="grid grid-cols-2 gap-2">
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <Sun class="w-3.5 h-3.5 text-amber-500" />
                                            <span>{{ getLightLabel(plant.light) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <Droplets class="w-3.5 h-3.5 text-blue-500" />
                                            <span>{{ getHumidityLabel(plant.atmospheric_humidity) }}</span>
                                        </div>
                                        <div class="flex items-center col-span-2 gap-1.5 text-xs text-gray-500">
                                            <Thermometer class="w-3.5 h-3.5 text-red-500" />
                                            <span>{{ formatTemperature(plant.minimum_temperature_celsius, plant.maximum_temperature_celsius) }}</span>
                                        </div>
                                    </div>

                                    <!-- Gemini Text Stats Summary -->
                                    <div v-else-if="plant.care_source === 'gemini'" class="space-y-2">
                                        <div class="flex items-center gap-2 text-xs text-purple-700 font-medium">
                                            <Sparkles class="w-3.5 h-3.5" />
                                            <span>AI Care Guide Available</span>
                                        </div>
                                        <p v-if="plant.care_summary" class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                                            {{ plant.care_summary }}
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="mt-4 pt-3 text-xs italic text-gray-400 border-t border-gray-100">
                                    Care details not yet loaded
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- List View -->
                    <div v-else class="overflow-hidden bg-white shadow-sm rounded-xl">
                        <div class="divide-y divide-gray-100">
                            <Link
                                v-for="plant in plants.data"
                                :key="plant.id"
                                :href="route('plants.show', plant.id)"
                                class="flex items-center gap-4 p-4 transition-colors hover:bg-gray-50"
                            >
                                <!-- Icon/Image -->
                                <div class="relative flex items-center justify-center flex-shrink-0 w-12 h-12 overflow-hidden rounded-lg bg-gray-100">
                                    <img
                                        v-if="plant.latest_image"
                                        :src="plant.latest_image"
                                        :alt="plant.common_name || plant.scientific_name"
                                        class="object-cover w-full h-full"
                                    />
                                    <Leaf v-else class="w-6 h-6 text-gray-600" />
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold text-gray-900">
                                            {{ plant.common_name || plant.scientific_name }}
                                        </h3>
                                        <span
                                            v-if="isThreatened(plant)"
                                            class="px-2 py-0.5 text-xs font-medium text-red-700 bg-red-100 rounded-full"
                                        >
                                            Conservation
                                        </span>
                                        <span
                                            v-else-if="hasCareDetails(plant)"
                                            class="px-2 py-0.5 text-xs font-medium text-gray-700 bg-gray-200 rounded-full"
                                        >
                                            Care Info
                                        </span>
                                        <span
                                            v-if="plant.iucn_category"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="[
                                                getIucnCategoryInfo(plant.iucn_category)?.color,
                                                getIucnCategoryInfo(plant.iucn_category)?.bgColor
                                            ]"
                                        >
                                            <ShieldAlert class="w-3 h-3" />
                                            {{ getIucnCategoryInfo(plant.iucn_category)?.label }}
                                        </span>
                                    </div>
                                    <p class="text-sm italic text-gray-500">{{ plant.scientific_name }}</p>
                                    <p v-if="plant.family" class="mt-1 text-sm text-gray-500">Family: {{ plant.family }}</p>
                                </div>

                                <!-- Conservation Notice for Threatened Plants -->
                                <div v-if="isThreatened(plant)" class="flex items-center gap-2 text-sm">
                                    <div class="flex items-center gap-1.5 text-red-600 bg-red-50 px-3 py-1.5 rounded-full border border-red-200">
                                        <ShieldAlert class="w-4 h-4" />
                                        <span class="font-medium">Protected Species</span>
                                    </div>
                                </div>

                                <!-- Care Stats (Non-Threatened Plants Only) -->
                                <div v-else-if="hasCareDetails(plant)" class="flex items-center gap-6 text-sm text-gray-500">
                                    <template v-if="plant.care_source === 'trefle' && plant.light !== null">
                                        <div class="flex items-center gap-1.5">
                                            <Sun class="w-4 h-4 text-yellow-500" />
                                            <span>{{ getLightLabel(plant.light) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Thermometer class="w-4 h-4 text-red-500" />
                                            <span>{{ formatTemperature(plant.minimum_temperature_celsius, plant.maximum_temperature_celsius) }}</span>
                                        </div>
                                    </template>
                                    <template v-else-if="plant.care_source === 'gemini'">
                                        <div class="flex items-center gap-1.5 text-purple-600 bg-purple-50 px-3 py-1 rounded-full">
                                            <Sparkles class="w-4 h-4" />
                                            <span class="font-medium">AI Care Guide</span>
                                        </div>
                                    </template>
                                </div>

                                <ChevronRight class="flex-shrink-0 w-5 h-5 text-gray-400" />
                            </Link>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="plants.last_page > 1" class="flex justify-center mt-8">
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="goToPage(plants.links[0]?.url)"
                                :disabled="plants.current_page === 1"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <template v-for="(link, index) in plants.links.slice(1, -1)" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="goToPage(link.url)"
                                    class="px-3 py-2 text-sm font-medium transition-colors duration-200 rounded-md"
                                    :class="link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-2 text-gray-400">...</span>
                            </template>

                            <button
                                @click="goToPage(plants.links[plants.links.length - 1]?.url)"
                                :disabled="plants.current_page === plants.last_page"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- No Results -->
                <div v-else class="py-16 text-center bg-white shadow-sm rounded-xl">
                    <SearchX class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No plants found</h3>
                    <p class="mb-6 text-gray-500">
                        {{ hasActiveFilters ? 'Try adjusting your filters.' : 'No plants in the database yet.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200"
                        >
                            Clear Filters
                        </button>
                        <Link
                            :href="route('plant-identifier')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-gray-900 rounded-lg hover:bg-black"
                        >
                            <Leaf class="w-4 h-4" />
                            Identify a Plant
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
