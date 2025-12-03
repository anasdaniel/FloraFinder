<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Filter,
    Grid3X3,
    Leaf,
    List,
    RefreshCw,
    Search,
    SearchX,
    Droplets,
    Sun,
    Thermometer,
    Clock,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Plant {
    id: number;
    scientific_name: string;
    common_name: string | null;
    family: string | null;
    genus: string | null;
    habitat: string | null;
    lifespan: string | null;
    description: string | null;
    light: number | null;
    atmospheric_humidity: number | null;
    minimum_temperature_celsius: number | null;
    maximum_temperature_celsius: number | null;
    minimum_precipitation_mm: number | null;
    maximum_precipitation_mm: number | null;
    soil_texture: number | null;
    soil_humidity: number | null;
    care_cached_at: string | null;
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
        has_care?: boolean;
    };
}>();

// Reactive state
const searchQuery = ref(props.filters.search || '');
const selectedFamily = ref(props.filters.family || '');
const hasCareOnly = ref(props.filters.has_care || false);
const viewMode = ref<'grid' | 'list'>('grid');
const isLoading = ref(false);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([selectedFamily, hasCareOnly], () => {
    applyFilters();
});

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        route('plants.index'),
        {
            search: searchQuery.value || undefined,
            family: selectedFamily.value || undefined,
            has_care: hasCareOnly.value || undefined,
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
    hasCareOnly.value = false;
};

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedFamily.value || hasCareOnly.value;
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
    return plant.care_cached_at !== null;
};

const goToPage = (url: string | null) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Plant Database" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-white">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12 text-center">
                    <h1 class="mb-4 text-4xl font-bold text-gray-900">Plant Database</h1>
                    <p class="max-w-2xl mx-auto text-lg text-gray-600">
                        Browse our collection of plants with detailed care information. Each plant includes growing conditions, temperature
                        requirements, and more.
                    </p>
                </div>

                <!-- Search and Filter Section -->
                <div class="p-6 mb-8 bg-white shadow-lg rounded-2xl ring-1 ring-gray-100">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                        <!-- Main Search Bar -->
                        <div class="lg:col-span-5">
                            <label for="search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Search
                            </label>
                            <div class="relative group">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    id="search"
                                    placeholder="Search by name, family..."
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                />
                                <Search
                                    class="absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600"
                                />
                            </div>
                        </div>

                        <!-- Family Filter -->
                        <div class="lg:col-span-4">
                            <label for="family" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Family
                            </label>
                            <div class="relative group">
                                <select
                                    v-model="selectedFamily"
                                    id="family"
                                    class="w-full appearance-none rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-8 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                >
                                    <option value="">All Families</option>
                                    <option v-for="family in families" :key="family" :value="family">
                                        {{ family }}
                                    </option>
                                </select>
                                <Leaf
                                    class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600"
                                />
                                <ChevronDown class="absolute w-4 h-4 text-gray-400 pointer-events-none right-3 top-3" />
                            </div>
                        </div>

                        <!-- Has Care Details Toggle -->
                        <div class="lg:col-span-3">
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500"> Filter </label>
                            <label
                                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 cursor-pointer hover:bg-white transition-all"
                            >
                                <input
                                    v-model="hasCareOnly"
                                    type="checkbox"
                                    class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black"
                                />
                                <span class="text-sm text-gray-700">With care details</span>
                            </label>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <Filter class="w-4 h-4" />
                            <span>{{ plants.total }} plants found</span>
                        </div>

                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline"
                        >
                            Clear all filters
                        </button>
                    </div>
                </div>

                <!-- View Toggle -->
                <div class="flex items-center justify-end mb-6">
                    <div class="flex items-center p-1 bg-white border border-gray-200 rounded-xl">
                        <button
                            @click="viewMode = 'grid'"
                            class="p-2 transition-all duration-200 rounded-lg"
                            :class="viewMode === 'grid' ? 'bg-black text-white shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                            title="Grid View"
                        >
                            <Grid3X3 class="w-5 h-5" />
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            class="p-2 transition-all duration-200 rounded-lg"
                            :class="viewMode === 'list' ? 'bg-black text-white shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                            title="List View"
                        >
                            <List class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <RefreshCw class="w-8 h-8 text-gray-400 animate-spin" />
                </div>

                <!-- Plants Grid -->
                <div v-else-if="plants.data.length > 0">
                    <!-- Grid View -->
                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Link
                            v-for="plant in plants.data"
                            :key="plant.id"
                            :href="route('plants.show', plant.id)"
                            class="overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm cursor-pointer group rounded-xl hover:shadow-lg hover:border-gray-200"
                        >
                            <!-- Plant Header -->
                            <div class="p-5 border-b border-gray-100 bg-gradient-to-br from-green-50 to-emerald-50">
                                <div class="flex items-start justify-between mb-2">
                                    <Leaf class="w-8 h-8 text-green-600" />
                                    <span
                                        v-if="hasCareDetails(plant)"
                                        class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full"
                                    >
                                        Care Info
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-900 transition-colors group-hover:text-green-700">
                                    {{ plant.common_name || plant.scientific_name }}
                                </h3>
                                <p class="text-sm italic text-gray-600">{{ plant.scientific_name }}</p>
                            </div>

                            <!-- Plant Info -->
                            <div class="p-4 space-y-3">
                                <div v-if="plant.family" class="flex items-center text-sm text-gray-600">
                                    <span class="font-medium text-gray-500 w-16">Family:</span>
                                    <span>{{ plant.family }}</span>
                                </div>

                                <!-- Quick Care Stats -->
                                <div v-if="hasCareDetails(plant)" class="grid grid-cols-2 gap-2 pt-2">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <Sun class="w-3.5 h-3.5 text-yellow-500" />
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

                                <div v-else class="pt-2 text-xs text-gray-400 italic">
                                    Care details not yet loaded
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- List View -->
                    <div v-else class="overflow-hidden bg-white shadow-lg rounded-xl">
                        <div class="divide-y divide-gray-200">
                            <Link
                                v-for="plant in plants.data"
                                :key="plant.id"
                                :href="route('plants.show', plant.id)"
                                class="flex items-center p-6 transition-colors duration-200 hover:bg-gray-50"
                            >
                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mr-4 rounded-lg bg-green-50">
                                    <Leaf class="w-6 h-6 text-green-600" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ plant.common_name || plant.scientific_name }}
                                        </h3>
                                        <span
                                            v-if="hasCareDetails(plant)"
                                            class="px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full"
                                        >
                                            Care Info
                                        </span>
                                    </div>
                                    <p class="text-sm italic text-gray-600">{{ plant.scientific_name }}</p>
                                    <p v-if="plant.family" class="mt-1 text-sm text-gray-500">Family: {{ plant.family }}</p>
                                </div>

                                <div v-if="hasCareDetails(plant)" class="flex items-center gap-6 text-sm text-gray-500">
                                    <div class="flex items-center gap-1.5">
                                        <Sun class="w-4 h-4 text-yellow-500" />
                                        <span>{{ getLightLabel(plant.light) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Thermometer class="w-4 h-4 text-red-500" />
                                        <span>{{ formatTemperature(plant.minimum_temperature_celsius, plant.maximum_temperature_celsius) }}</span>
                                    </div>
                                </div>

                                <ChevronRight class="flex-shrink-0 w-5 h-5 ml-4 text-gray-400" />
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
                                    :class="link.active ? 'bg-black text-white' : 'text-gray-700 hover:bg-gray-100'"
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
                <div v-else class="py-12 text-center">
                    <SearchX class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No plants found</h3>
                    <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="px-4 py-2 mt-4 text-white transition-colors duration-200 bg-black rounded-lg hover:bg-gray-800"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
