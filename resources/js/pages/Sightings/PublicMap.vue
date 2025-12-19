<!--
  COMPONENT: Sightings/PublicMap.vue
  ROUTE: /sightings-map
  CONTROLLER: SightingController@publicMap
  PURPOSE: Public map showing all users' plant sightings across Malaysia
  FILTERING: Server-side (Laravel with pagination)
  AUTH: Required (but shows data from all users)
  DATA SOURCE: All sightings from the database
-->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronDown,
    Filter,
    MapPin,
    Search,
    X,
    Grid3X3,
    List,
    Map as MapIcon,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SightingMap from '@/components/SightingMap.vue';

interface SightingImage {
    id: number;
    image_url: string;
    organ: string;
}

interface Sighting {
    id: number;
    user_id: number;
    plant_id: number | null;
    scientific_name: string;
    common_name: string | null;
    latitude: number | null;
    longitude: number | null;
    location_name: string | null;
    region: string | null;
    sighted_at: string;
    description: string | null;
    image_url: string;
    created_at: string;
    updated_at: string;
    images: SightingImage[];
    user?: {
        id: number;
        name: string;
    };
    plant?: {
        id: number;
        family: string | null;
        genus: string | null;
        iucn_category: string | null;
    };
}

interface PaginatedSightings {
    data: Sighting[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Stats {
    total_sightings: number;
    unique_species: number;
    unique_families: number;
    unique_regions: number;
}

const props = defineProps<{
    sightings: PaginatedSightings;
    regions: string[];
    families: string[];
    conservationStatuses: Array<{ code: string; label: string }>;
    filters: {
        search?: string;
        region?: string;
        family?: string;
        statuses?: string[];
        date_from?: string;
        date_to?: string;
    };
    stats: Stats;
}>();

// Reactive state
const searchQuery = ref(props.filters.search || '');
const selectedRegion = ref(props.filters.region || '');
const selectedFamily = ref(props.filters.family || '');
const selectedStatuses = ref<string[]>(props.filters.statuses || []);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const viewMode = ref<'grid' | 'list' | 'map'>('grid');
const isLoading = ref(false);
const mobileFiltersOpen = ref(false);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([selectedRegion, selectedFamily, dateFrom, dateTo], () => {
    applyFilters();
});

watch(selectedStatuses, () => {
    applyFilters();
}, { deep: true });

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        route('sightings.map'),
        {
            search: searchQuery.value || undefined,
            region: selectedRegion.value || undefined,
            family: selectedFamily.value || undefined,
            statuses: selectedStatuses.value.length > 0 ? selectedStatuses.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
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
    selectedRegion.value = '';
    selectedFamily.value = '';
    selectedStatuses.value = [];
    dateFrom.value = '';
    dateTo.value = '';
};

const toggleStatus = (statusCode: string) => {
    const index = selectedStatuses.value.indexOf(statusCode);
    if (index > -1) {
        selectedStatuses.value.splice(index, 1);
    } else {
        selectedStatuses.value.push(statusCode);
    }
};

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        selectedRegion.value ||
        selectedFamily.value ||
        selectedStatuses.value.length > 0 ||
        dateFrom.value ||
        dateTo.value
    );
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (searchQuery.value) count++;
    if (selectedRegion.value) count++;
    if (selectedFamily.value) count++;
    if (selectedStatuses.value.length > 0) count += selectedStatuses.value.length;
    if (dateFrom.value) count++;
    if (dateTo.value) count++;
    return count;
});

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getConservationStatusColor = (status: string | null): string => {
    const colors: Record<string, string> = {
        NE: 'bg-blue-500 text-white',
        DD: 'bg-gray-500 text-white',
        LC: 'bg-green-500 text-white',
        NT: 'bg-yellow-500 text-black',
        VU: 'bg-orange-500 text-white',
        EN: 'bg-red-500 text-white',
        CR: 'bg-red-600 text-white',
    };
    return colors[status || ''] || 'bg-gray-500 text-white';
};

const getPrimaryImage = (sighting: Sighting): string => {
    if (sighting.images && sighting.images.length > 0) {
        return sighting.images[0].image_url;
    }
    return sighting.image_url || '/images/placeholder-plant.jpg';
};

// Map data formatting
const mapSightings = computed(() => {
    return props.sightings.data.map(sighting => ({
        id: sighting.id,
        latitude: sighting.latitude,
        longitude: sighting.longitude,
        scientificName: sighting.scientific_name,
        commonName: sighting.common_name,
        location: sighting.location_name || sighting.region,
        date: formatDate(sighting.sighted_at || sighting.created_at),
        conservationStatus: sighting.plant?.iucn_category || 'NE',
        family: sighting.plant?.family || '',
        description: sighting.description || '',
        image: getPrimaryImage(sighting),
    }));
});

const goToPage = (page: number) => {
    if (page < 1 || page > props.sightings.last_page) return;

    router.get(
        route('sightings.map'),
        {
            search: searchQuery.value || undefined,
            region: selectedRegion.value || undefined,
            family: selectedFamily.value || undefined,
            statuses: selectedStatuses.value.length > 0 ? selectedStatuses.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            page: page,
        },
        {
            preserveState: true,
            preserveScroll: false,
        },
    );
};
</script>

<template>
    <Head title="Plant Sightings Map" />

    <AppLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Sightings Map</h1>
                            <p class="mt-1 text-gray-600">
                                Explore reported plants across Malaysia
                            </p>
                        </div>
                    </div>

                    <!-- Stats Strip -->
                    <div class="grid grid-cols-2 gap-px overflow-hidden bg-gray-200 border border-gray-200 shadow-sm rounded-2xl md:grid-cols-4">
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <Users class="w-6 h-6 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_sightings }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Total Sightings</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-blue-50 rounded-xl">
                                <MapPin class="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.unique_regions }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Regions</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-green-50 rounded-xl">
                                <List class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.unique_families }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Families</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-amber-50 rounded-xl">
                                <Grid3X3 class="w-6 h-6 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.unique_species }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Species</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Filters Sidebar -->
                    <aside
                        class="w-full lg:w-80 flex-shrink-0 space-y-6"
                        :class="[
                            mobileFiltersOpen ? 'fixed inset-0 bg-white z-50 overflow-y-auto p-6' : 'hidden lg:block'
                        ]"
                    >
                        <!-- Mobile Close Button -->
                        <button
                            v-if="mobileFiltersOpen"
                            @click="mobileFiltersOpen = false"
                            class="lg:hidden absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600"
                        >
                            <X class="w-6 h-6" />
                        </button>

                        <!-- Filter Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Filter class="w-5 h-5 text-gray-700" />
                                <h3 class="text-lg font-bold text-gray-900">Filters</h3>
                            </div>
                            <button
                                @click="clearFilters"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                            >
                                Reset
                            </button>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ activeFilterCount }} active
                        </div>

                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Search plants</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Scientific or common name..."
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                                />
                            </div>
                        </div>

                        <!-- Plant Family -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-900">Plant family</label>
                                <button
                                    @click="selectedFamily = ''"
                                    class="text-xs text-gray-500 hover:text-gray-900"
                                >
                                    All
                                </button>
                            </div>
                            <div class="relative">
                                <select
                                    v-model="selectedFamily"
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 appearance-none"
                                >
                                    <option value="">All families</option>
                                    <option v-for="family in families" :key="family" :value="family">
                                        {{ family }}
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Conservation Status -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-900">Conservation status</label>
                                <span class="text-xs text-gray-500">{{ selectedStatuses.length }} selected</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="status in conservationStatuses"
                                    :key="status.code"
                                    @click="toggleStatus(status.code)"
                                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-all"
                                    :class="[
                                        selectedStatuses.includes(status.code)
                                            ? getConservationStatusColor(status.code)
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    {{ status.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Region -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-900">State of Malaysia</label>
                                <button
                                    @click="selectedRegion = ''"
                                    class="text-xs text-gray-500 hover:text-gray-900"
                                >
                                    All
                                </button>
                            </div>
                            <div class="relative">
                                <select
                                    v-model="selectedRegion"
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400 appearance-none"
                                >
                                    <option value="">All states</option>
                                    <option v-for="region in regions" :key="region" :value="region">
                                        {{ region }}
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Sighting date</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="relative">
                                    <input
                                        v-model="dateFrom"
                                        type="text"
                                        onfocus="(this.type='date')"
                                        onblur="if(!this.value)this.type='text'"
                                        placeholder="dd / mm / yyyy"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                                    />
                                    <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                                </div>
                                <div class="relative">
                                    <input
                                        v-model="dateTo"
                                        type="text"
                                        onfocus="(this.type='date')"
                                        onblur="if(!this.value)this.type='text'"
                                        placeholder="dd / mm / yyyy"
                                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-400"
                                    />
                                    <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Main Content -->
                    <main class="flex-1 min-w-0">
                        <!-- Header & Controls -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Plant Sightings ({{ props.sightings.total }})
                            </h2>

                        <!-- View Mode Selector -->
                        <div class="inline-flex items-center gap-1 bg-gray-100 rounded-full p-1.5 border border-gray-200">
                            <button
                                @click="viewMode = 'grid'"
                                class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                                :class="viewMode === 'grid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            >
                                Grid
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                                :class="viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            >
                                List
                            </button>
                            <button
                                @click="viewMode = 'map'"
                                class="px-4 py-2 text-sm font-medium rounded-full transition-all"
                                :class="viewMode === 'map' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            >
                                Map
                            </button>
                        </div>
                        </div>

                        <!-- Mobile Filter Toggle Button -->
                        <button
                            @click="mobileFiltersOpen = true"
                            class="lg:hidden mb-6 w-full flex items-center justify-center gap-2 p-3 bg-white border border-gray-200 rounded-lg text-gray-900 font-medium"
                        >
                            <Filter class="w-4 h-4" />
                            Show Filters
                            <span v-if="activeFilterCount > 0" class="bg-gray-900 text-white text-xs px-2 py-0.5 rounded-full">{{ activeFilterCount }}</span>
                        </button>

                        <!-- Map View -->
                        <div v-if="viewMode === 'map'" class="bg-white rounded-lg border border-gray-200 overflow-hidden h-[600px]">
                            <SightingMap :sightings="mapSightings" />
                        </div>

                        <!-- Grid View -->
                        <div v-else-if="viewMode === 'grid' && sightings.data.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            <a
                                v-for="sighting in sightings.data"
                                :key="sighting.id"
                                :href="route('sightings.show', sighting.id)"
                                class="group bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                            >
                                <!-- Image -->
                                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                                    <img
                                        :src="getPrimaryImage(sighting)"
                                        :alt="sighting.common_name || sighting.scientific_name"
                                        class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                    />

                                    <!-- Conservation Status Badge -->
                                    <div class="absolute top-3 right-3">
                                        <span
                                            v-if="sighting.plant?.iucn_category"
                                            class="px-2.5 py-1 text-xs font-medium rounded shadow-sm"
                                            :class="getConservationStatusColor(sighting.plant.iucn_category)"
                                        >
                                            {{ sighting.plant.iucn_category }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-4">
                                    <h3 class="text-base font-bold text-gray-900">
                                        {{ sighting.common_name || sighting.scientific_name }}
                                    </h3>
                                    <p class="text-sm italic text-gray-500 mt-0.5">
                                        {{ sighting.scientific_name }}
                                    </p>

                                    <div class="mt-3 space-y-1.5">
                                        <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-2 text-sm text-gray-600">
                                            <MapPin class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            <span class="truncate">{{ sighting.location_name || sighting.region }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <Calendar class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                        </div>
                                        <div v-if="sighting.user" class="flex items-center gap-2 text-sm text-gray-600">
                                            <Users class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            <span class="truncate">{{ sighting.user.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- List View -->
                        <div v-else-if="viewMode === 'list' && sightings.data.length > 0" class="space-y-3">
                            <a
                                v-for="sighting in sightings.data"
                                :key="sighting.id"
                                :href="route('sightings.show', sighting.id)"
                                class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow cursor-pointer"
                            >
                                <!-- Thumbnail -->
                                <div class="relative w-20 h-20 flex-shrink-0 rounded overflow-hidden bg-gray-100">
                                    <img
                                        :src="getPrimaryImage(sighting)"
                                        :alt="sighting.common_name || sighting.scientific_name"
                                        class="object-cover w-full h-full"
                                    />
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-base font-bold text-gray-900">
                                                {{ sighting.common_name || sighting.scientific_name }}
                                            </h3>
                                            <p class="text-sm italic text-gray-500">
                                                {{ sighting.scientific_name }}
                                            </p>
                                        </div>
                                        <span
                                            v-if="sighting.plant?.iucn_category"
                                            class="px-2 py-1 text-xs font-medium rounded shadow-sm flex-shrink-0"
                                            :class="getConservationStatusColor(sighting.plant.iucn_category)"
                                        >
                                            {{ sighting.plant.iucn_category }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
                                        <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-1.5">
                                            <MapPin class="w-4 h-4 text-gray-400" />
                                            <span>{{ sighting.location_name || sighting.region }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Calendar class="w-4 h-4 text-gray-400" />
                                            <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                        </div>
                                        <div v-if="sighting.user" class="flex items-center gap-1.5">
                                            <Users class="w-4 h-4 text-gray-400" />
                                            <span>{{ sighting.user.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- No Results -->
                        <div v-else class="py-16 text-center">
                            <p class="text-gray-500">
                                {{ hasActiveFilters ? 'No sightings found matching your criteria.' : 'No plant sightings have been reported yet.' }}
                            </p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="['grid', 'list'].includes(viewMode) && sightings.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
                            <button
                                @click="goToPage(sightings.current_page - 1)"
                                :disabled="sightings.current_page === 1"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Previous
                            </button>
                            <span class="px-4 py-2 text-sm font-medium text-gray-900">
                                Page {{ sightings.current_page }} of {{ sightings.last_page }}
                            </span>
                            <button
                                @click="goToPage(sightings.current_page + 1)"
                                :disabled="sightings.current_page === sightings.last_page"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
