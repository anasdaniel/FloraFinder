<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronRight, Filter, Grid3X3, List, MapPin, Search, SearchX, X, Leaf, AlertCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// Reactive state
const searchQuery = ref('');
const selectedFamily = ref('');
const selectedHabitat = ref('');
const selectedConservation = ref('');
const selectedRegion = ref('');
const selectedGrowthForm = ref('');
const selectedFlowering = ref('');
const showAdvancedFilters = ref(false);
const viewMode = ref('grid');
const sortBy = ref('name');
const currentPage = ref(1);
const itemsPerPage = ref(12);
const selectedPlant = ref(null);

interface plantsResult {
    id: number;
    user_id: number;
    path: string;
    filename: string;
    mime_type: string;
    size: number;
    organ: string;
    scientific_name: string;
    scientific_name_without_author: string;
    common_name: string | null;
    family: string;
    genus: string;
    confidence: number;
    gbif_id: number | null;
    powo_id: string | null;
    iucn_category: string | null;
    region: string | null;
    latitude: number | null;
    longitude: number | null;
    created_at: string;
    updated_at: string;
}

//define props
const props = defineProps<{
    plants?: {
        data: plantsResult[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
        };
    };
    filters?: {
        search?: string;
        family?: string;
        conservation?: string;
        region?: string;
    };
}>();

// Initialize state from props
if (props.filters) {
    searchQuery.value = props.filters.search || '';
    selectedFamily.value = props.filters.family || '';
    selectedConservation.value = props.filters.conservation || '';
    selectedRegion.value = props.filters.region || '';
}

if (props.plants?.meta) {
    currentPage.value = props.plants.meta.current_page;
}

// Filter options
const plantFamilies = ref([
    'Asteraceae',
    'Rosaceae',
    'Fabaceae',
    'Poaceae',
    'Lamiaceae',
    'Brassicaceae',
    'Apiaceae',
    'Ranunculaceae',
    'Caryophyllaceae',
    'Scrophulariaceae',
]);

const habitats = ref(['Forest', 'Grassland', 'Wetland', 'Desert', 'Mountains', 'Coastal', 'Scrubland', 'Meadow', 'Woodland', 'Prairie']);

const conservationStatuses = ref([
    { value: 'NE', label: 'Not Evaluated' },
    { value: 'DD', label: 'Data Deficient' },
    { value: 'LC', label: 'Least Concern' },
    { value: 'NT', label: 'Near Threatened' },
    { value: 'VU', label: 'Vulnerable' },
    { value: 'EN', label: 'Endangered' },
    { value: 'CR', label: 'Critically Endangered' },
    { value: 'EW', label: 'Extinct in the Wild' },
    { value: 'EX', label: 'Extinct' },
]);

const regions = ref([
    'Johor',
    'Kedah',
    'Kelantan',
    'Melaka',
    'Negeri Sembilan',
    'Pahang',
    'Perak',
    'Perlis',
    'Pulau Pinang',
    'Sabah',
    'Sarawak',
    'Selangor',
    'Terengganu',
    'Kuala Lumpur',
    'Labuan',
    'Putrajaya',
]);

const growthForms = ref(['Tree', 'Shrub', 'Herb', 'Grass', 'Vine', 'Fern', 'Moss', 'Succulent']);

const floweringSeasons = ref(['Spring', 'Summer', 'Fall', 'Winter', 'Year-round', 'Varies']);

// Computed properties
const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedFamily.value || selectedConservation.value || selectedRegion.value;
});

const filteredPlants = computed(() => {
    return props.plants?.data || [];
});

const totalPages = computed(() => props.plants?.meta?.last_page || 1);

const paginatedPlants = computed(() => {
    return props.plants?.data || [];
});

const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(total);
        } else if (current >= total - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = total - 4; i <= total; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(total);
        }
    }

    return pages;
});

// Watchers for server-side filtering
const updateResults = () => {
    router.get(
        route('plant-search'),
        {
            search: searchQuery.value,
            family: selectedFamily.value,
            conservation: selectedConservation.value,
            region: selectedRegion.value,
            page: currentPage.value,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
};

watch([selectedFamily, selectedConservation, selectedRegion], () => {
    currentPage.value = 1;
    updateResults();
});

// Debounced search
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        updateResults();
    }, 300);
});

watch(currentPage, (newPage, oldPage) => {
    if (newPage !== oldPage) {
        updateResults();
    }
});

// Methods
const handleSearch = () => {
    // Handled by watchers
};

const handleSort = () => {
    // Client-side sorting for the current page
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedFamily.value = '';
    selectedHabitat.value = '';
    selectedConservation.value = '';
    selectedRegion.value = '';
    selectedGrowthForm.value = '';
    selectedFlowering.value = '';
    currentPage.value = 1;
    updateResults();
};

// Dummy plant details
const getDummyPlantDetails = (plant) => {
    return {
        description:
            'This is a fascinating plant species native to tropical regions. It features distinctive characteristics that make it easily identifiable in its natural habitat. The plant has adapted well to various environmental conditions and plays an important role in its ecosystem.',
        identification: {
            height: '1-3 meters',
            leaves: 'Oval-shaped, dark green with serrated edges',
            flowers: 'Small, white to pale yellow, clustered',
            fruit: 'Berry-like, red when mature',
            bark: 'Smooth, grayish-brown',
        },
        care: {
            light: 'Partial shade to full sun',
            water: 'Moderate watering, well-drained soil',
            temperature: '18-28°C (64-82°F)',
            soil: 'Rich, loamy soil with good drainage',
            fertilizer: 'Monthly during growing season',
            pruning: 'Prune in early spring to maintain shape',
        },
        ecology: {
            nativeRange: 'Southeast Asian tropical forests',
            habitat: 'Lowland and montane forests, forest edges',
            pollinators: 'Bees, butterflies, and small birds',
            threats: 'Habitat loss, over-collection, climate change',
            uses: 'Traditional medicine, ornamental purposes, wildlife food source',
        },
        growthRate: 'Moderate',
        lifespan: '15-25 years',
        toxicity: 'Non-toxic to humans and pets',
    };
};

const getConservationStatusColor = (status) => {
    const colors = {
        NE: 'bg-blue-100 text-blue-800',
        DD: 'bg-gray-200 text-gray-700',
        LC: 'bg-green-100 text-green-800',
        NT: 'bg-yellow-100 text-yellow-800',
        VU: 'bg-orange-100 text-orange-800',
        EN: 'bg-red-100 text-red-800',
        CR: 'bg-red-200 text-red-900',
        EW: 'bg-gray-300 text-gray-900',
        EX: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getConservationStatusLabel = (status) => {
    const labels = {
        NE: 'Not Evaluated',
        DD: 'Data Deficient',
        LC: 'Least Concern',
        NT: 'Near Threatened',
        VU: 'Vulnerable',
        EN: 'Endangered',
        CR: 'Critically Endangered',
        EW: 'Extinct in the Wild',
        EX: 'Extinct',
    };
    return labels[status] || 'Data Deficient';
};
</script>

<template>
    <Head title="Plant Search" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-white">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12 text-center">
                    <h1 class="mb-4 text-4xl font-bold text-gray-900">Plant Database Search</h1>
                    <p class="max-w-2xl mx-auto text-lg text-gray-600">
                        Discover and explore our comprehensive plant database. Search by name, family, habitat, or conservation status.
                    </p>
                </div>

                <!-- Search and Filter Section -->
                <div class="p-6 mb-8 bg-white shadow-lg rounded-2xl ring-1 ring-gray-100">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                        <!-- Main Search Bar -->
                        <div class="lg:col-span-6">
                            <label for="search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500"> Search </label>
                            <div class="relative group">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    id="search"
                                    placeholder="Search by common or scientific name..."
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                    @input="handleSearch"
                                />
                                <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600" />
                            </div>
                        </div>

                        <!-- Family Filter -->
                        <div class="lg:col-span-3">
                            <label for="family" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500"> Family </label>
                            <div class="relative group">
                                <select
                                    v-model="selectedFamily"
                                    id="family"
                                    class="w-full appearance-none rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-8 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                    @change="handleSearch"
                                >
                                    <option value="">All Families</option>
                                    <option v-for="family in plantFamilies" :key="family" :value="family">
                                        {{ family }}
                                    </option>
                                </select>
                                <Leaf class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600" />
                                <ChevronDown class="absolute w-4 h-4 text-gray-400 pointer-events-none right-3 top-3" />
                            </div>
                        </div>

                        <!-- Conservation Status Filter -->
                        <div class="lg:col-span-3">
                            <label for="conservation" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500"> Status </label>
                            <div class="relative group">
                                <select
                                    v-model="selectedConservation"
                                    id="conservation"
                                    class="w-full appearance-none rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-8 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                    @change="handleSearch"
                                >
                                    <option value="">All Statuses</option>
                                    <option v-for="status in conservationStatuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <AlertCircle class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600" />
                                <ChevronDown class="absolute w-4 h-4 text-gray-400 pointer-events-none right-3 top-3" />
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters Toggle -->
                    <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100">
                        <button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            class="flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-black"
                        >
                            <div class="flex items-center justify-center w-6 h-6 text-gray-500 bg-gray-100 rounded-full">
                                <Filter class="w-3 h-3" />
                            </div>
                            {{ showAdvancedFilters ? 'Hide Filters' : 'More Filters' }}
                            <ChevronDown
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': showAdvancedFilters }"
                            />
                        </button>

                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline"
                        >
                            Clear all
                        </button>
                    </div>

                    <!-- Advanced Filters Panel -->
                    <div v-if="showAdvancedFilters" class="grid grid-cols-1 gap-4 mt-4 duration-200 animate-in slide-in-from-top-2 md:grid-cols-3">
                        <!-- Region Filter -->
                        <div>
                            <label for="region" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500"> Region </label>
                            <div class="relative group">
                                <select
                                    v-model="selectedRegion"
                                    id="region"
                                    class="w-full appearance-none rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-10 pr-8 text-sm transition-all focus:border-black focus:bg-white focus:ring-black group-hover:bg-white"
                                    @change="handleSearch"
                                >
                                    <option value="">All States</option>
                                    <option v-for="region in regions" :key="region" :value="region">
                                        {{ region }}
                                    </option>
                                </select>
                                <MapPin class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600" />
                                <ChevronDown class="absolute w-4 h-4 text-gray-400 pointer-events-none right-3 top-3" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Results Summary -->
                <div class="flex items-center justify-between mb-6">
                    <div class="text-gray-600">
                        Found
                        <span class="font-semibold text-gray-900">{{ props.plants?.meta?.total || 0 }}</span>
                        plants
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Sort Options -->
                        <select
                            v-model="sortBy"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-transparent focus:ring-2 focus:ring-black"
                            @change="handleSort"
                        >
                            <option value="name">Sort by Name</option>
                            <option value="family">Sort by Family</option>
                            <option value="conservation">Sort by Conservation Status</option>
                        </select>

                        <!-- View Toggle -->
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
                </div>

                <!-- Search Results -->
                <div v-if="filteredPlants.length > 0">
                    <!-- Grid View -->
                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <div
                            v-for="plant in paginatedPlants"
                            :key="plant.id"
                            class="overflow-hidden transition-shadow duration-300 bg-white shadow-lg cursor-pointer group rounded-xl hover:shadow-xl"
                            @click="selectedPlant = plant"
                        >
                            <div class="relative h-48 bg-gray-200">
                                <img
                                    :src="plant.path"
                                    :alt="plant.common_name || plant.scientific_name"
                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                />
                                <div class="absolute right-3 top-3">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getConservationStatusColor(plant.iucn_category)">
                                        {{ getConservationStatusLabel(plant.iucn_category) }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4">
                                <h3 class="mb-1 font-semibold text-gray-900 transition-colors group-hover:text-black">
                                    {{ plant.common_name || plant.scientific_name }}
                                </h3>
                                <p class="mb-2 text-sm italic text-gray-600">
                                    {{ plant.scientific_name }}
                                </p>
                                <p class="mb-3 text-sm text-gray-500">{{ plant.family }}</p>

                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span class="flex items-center">
                                        <MapPin class="w-3 h-3 mr-1" />
                                        {{ plant.region }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="overflow-hidden bg-white shadow-lg rounded-xl">
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="plant in paginatedPlants"
                                :key="plant.id"
                                class="p-6 transition-colors duration-200 cursor-pointer hover:bg-gray-50"
                                @click="selectedPlant = plant"
                            >
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="plant.path"
                                        :alt="plant.common_name || plant.scientific_name"
                                        class="object-cover w-16 h-16 rounded-lg"
                                    />

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900 transition-colors hover:text-black">
                                                    {{ plant.common_name || plant.scientific_name }}
                                                </h3>
                                                <p class="text-sm italic text-gray-600">
                                                    {{ plant.scientific_name }}
                                                </p>
                                                <p class="mt-1 text-sm text-gray-500">{{ plant.family }}</p>
                                            </div>

                                            <span
                                                class="px-3 py-1 text-xs font-medium rounded-full"
                                                :class="getConservationStatusColor(plant.iucn_category)"
                                            >
                                                {{ getConservationStatusLabel(plant.iucn_category) }}
                                            </span>
                                        </div>

                                        <div class="flex items-center mt-3 space-x-6 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <MapPin class="w-4 h-4 mr-1" />
                                                {{ plant.region }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex justify-center mt-8">
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="currentPage = page"
                                class="px-3 py-2 text-sm font-medium transition-colors duration-200 rounded-md"
                                :class="page === currentPage ? 'bg-black text-white' : 'text-gray-700 hover:bg-gray-100'"
                            >
                                {{ page }}
                            </button>

                            <button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
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
                        @click="clearFilters"
                        class="px-4 py-2 mt-4 text-white transition-colors duration-200 bg-black rounded-lg hover:bg-gray-800"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Plant Detail Modal -->
        <div v-if="selectedPlant" class="fixed inset-0 z-50 overflow-y-auto" @click="selectedPlant = null">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                <div
                    class="inline-block w-full max-w-5xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <div class="bg-white">
                        <!-- Header Image -->
                        <div class="relative">
                            <img
                                :src="selectedPlant.path"
                                :alt="selectedPlant.common_name || selectedPlant.scientific_name"
                                class="object-cover w-full h-80"
                            />
                            <button
                                @click="selectedPlant = null"
                                class="absolute p-2 transition-all duration-200 bg-white rounded-full right-4 top-4 bg-opacity-90 hover:bg-opacity-100"
                            >
                                <X class="w-6 h-6 text-gray-700" />
                            </button>
                        </div>

                        <div class="p-8">
                            <!-- Title Section -->
                            <div class="flex items-start justify-between pb-6 mb-6 border-b border-gray-200">
                                <div>
                                    <h2 class="mb-2 text-3xl font-bold text-gray-900">
                                        {{ selectedPlant.common_name || selectedPlant.scientific_name }}
                                    </h2>
                                    <p class="mb-1 text-xl italic text-gray-600">
                                        {{ selectedPlant.scientific_name }}
                                    </p>
                                    <p class="text-lg text-gray-500">Family: {{ selectedPlant.family }}</p>
                                </div>
                                <span
                                    class="px-4 py-2 text-sm font-medium rounded-full"
                                    :class="getConservationStatusColor(selectedPlant.iucn_category)"
                                >
                                    {{ getConservationStatusLabel(selectedPlant.iucn_category) }}
                                </span>
                            </div>

                            <!-- Description -->
                            <div class="mb-8">
                                <h3 class="mb-3 text-xl font-semibold text-gray-900">Description</h3>
                                <p class="leading-relaxed text-gray-700">
                                    {{ getDummyPlantDetails(selectedPlant).description }}
                                </p>
                            </div>

                            <!-- Two Column Layout -->
                            <div class="grid grid-cols-1 gap-8 mb-8 lg:grid-cols-2">
                                <!-- Identification Features -->
                                <div>
                                    <h3 class="mb-4 text-xl font-semibold text-gray-900">Identification Features</h3>
                                    <div class="p-4 space-y-3 rounded-lg bg-gray-50">
                                        <div v-for="(value, key) in getDummyPlantDetails(selectedPlant).identification" :key="key">
                                            <p class="text-sm font-medium text-gray-500 capitalize">{{ key }}:</p>
                                            <p class="text-gray-900">{{ value }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Care Requirements -->
                                <div>
                                    <h3 class="mb-4 text-xl font-semibold text-gray-900">Care Requirements</h3>
                                    <div class="p-4 space-y-3 rounded-lg bg-green-50">
                                        <div v-for="(value, key) in getDummyPlantDetails(selectedPlant).care" :key="key">
                                            <p class="text-sm font-medium text-green-700 capitalize">{{ key }}:</p>
                                            <p class="text-gray-900">{{ value }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ecology & Conservation -->
                            <div class="mb-8">
                                <h3 class="mb-4 text-xl font-semibold text-gray-900">Ecology & Conservation</h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div
                                        v-for="(value, key) in getDummyPlantDetails(selectedPlant).ecology"
                                        :key="key"
                                        class="p-4 border border-gray-200 rounded-lg"
                                    >
                                        <p class="mb-1 text-sm font-medium text-gray-500 capitalize">{{ key }}:</p>
                                        <p class="text-gray-900">{{ value }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="p-4 mb-6 border-l-4 border-black bg-gray-50">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Growth Rate</p>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ getDummyPlantDetails(selectedPlant).growthRate }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Lifespan</p>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ getDummyPlantDetails(selectedPlant).lifespan }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Toxicity</p>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ getDummyPlantDetails(selectedPlant).toxicity }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div class="p-4 mb-6 rounded-lg bg-blue-50">
                                <h4 class="mb-2 text-lg font-semibold text-gray-900">Location Information</h4>
                                <div class="flex items-center space-x-4 text-gray-700">
                                    <div class="flex items-center">
                                        <MapPin class="w-5 h-5 mr-2 text-blue-600" />
                                        <span>Region: {{ selectedPlant.region }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span>Genus: {{ selectedPlant.genus }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span>Detection Confidence: {{ (selectedPlant.confidence * 100).toFixed(1) }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3">
                                <button
                                    @click="selectedPlant = null"
                                    class="px-6 py-2 text-gray-700 transition-colors duration-200 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Close
                                </button>
                                <button class="px-6 py-2 text-white transition-colors duration-200 bg-black rounded-lg hover:bg-gray-800">
                                    View Full Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
