<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronRight, Filter, Grid3X3, List, MapPin, Search, SearchX, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    plants?: plantsResult[];
}>();

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
    let plants = [...(props.plants || [])];

    // Set default conservation status to "DD" if null or undefined
    plants = plants.map((plant) => ({
        ...plant,
        iucn_category: plant.iucn_category || 'DD',
    }));

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        plants = plants.filter(
            (plant) => plant.common_name?.toLowerCase().includes(query) || false || plant.scientific_name.toLowerCase().includes(query),
        );
    }

    // Apply family filter
    if (selectedFamily.value) {
        plants = plants.filter((plant) => plant.family === selectedFamily.value);
    }

    // Apply conservation status filter
    if (selectedConservation.value) {
        plants = plants.filter((plant) => plant.iucn_category === selectedConservation.value);
    }

    // Apply region filter
    if (selectedRegion.value) {
        plants = plants.filter((plant) => plant.region === selectedRegion.value);
    }

    // Apply sorting
    plants.sort((a, b) => {
        switch (sortBy.value) {
            case 'name':
                const aName = a.common_name || a.scientific_name;
                const bName = b.common_name || b.scientific_name;
                return aName.localeCompare(bName);
            case 'family':
                return a.family.localeCompare(b.family);
            case 'conservation':
                const statusOrder = {
                    NE: 0,
                    DD: 0.5,
                    LC: 1,
                    NT: 2,
                    VU: 3,
                    EN: 4,
                    CR: 5,
                    EW: 6,
                    EX: 7,
                };
                return (statusOrder[a.iucn_category] || 0) - (statusOrder[b.iucn_category] || 0);
            default:
                return 0;
        }
    });

    return plants;
});

const totalPages = computed(() => Math.ceil(filteredPlants.value.length / itemsPerPage.value));

const paginatedPlants = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredPlants.value.slice(start, end);
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

// Methods
const handleSearch = () => {
    currentPage.value = 1;
};

const handleSort = () => {
    currentPage.value = 1;
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
        <div class="min-h-screen bg-white py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12 text-center">
                    <h1 class="mb-4 text-4xl font-bold text-gray-900">Plant Database Search</h1>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Discover and explore our comprehensive plant database. Search by name, family, habitat, or conservation status.
                    </p>
                </div>

                <!-- Search and Filter Section -->
                <div class="mb-8 rounded-2xl bg-white p-8 shadow-xl">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <!-- Main Search Bar -->
                        <div class="lg:col-span-6">
                            <label for="search" class="mb-2 block text-sm font-medium text-gray-700"> Search Plants </label>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    id="search"
                                    placeholder="Search by name or scientific name..."
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 pl-12 focus:border-transparent focus:ring-2 focus:ring-black"
                                    @input="handleSearch"
                                />
                                <Search class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <!-- Family Filter -->
                        <div class="lg:col-span-3">
                            <label for="family" class="mb-2 block text-sm font-medium text-gray-700"> Plant Family </label>
                            <select
                                v-model="selectedFamily"
                                id="family"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-black"
                                @change="handleSearch"
                            >
                                <option value="">All Families</option>
                                <option v-for="family in plantFamilies" :key="family" :value="family">
                                    {{ family }}
                                </option>
                            </select>
                        </div>

                        <!-- Conservation Status Filter -->
                        <div class="lg:col-span-3">
                            <label for="conservation" class="mb-2 block text-sm font-medium text-gray-700"> Conservation Status </label>
                            <select
                                v-model="selectedConservation"
                                id="conservation"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-black"
                                @change="handleSearch"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="status in conservationStatuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Advanced Filters Toggle -->
                    <div class="mt-6">
                        <button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            class="text-black-600 hover:text-black-900 flex items-center font-medium"
                        >
                            <Filter class="mr-2 h-4 w-4" />
                            Advanced Filters
                            <ChevronDown class="ml-1 h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': showAdvancedFilters }" />
                        </button>
                    </div>

                    <!-- Advanced Filters Panel -->
                    <div v-if="showAdvancedFilters" class="mt-6 border-t border-gray-200 pt-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-1">
                            <!-- Region Filter -->
                            <div>
                                <label for="region" class="mb-2 block text-sm font-medium text-gray-700"> State of Malaysia </label>
                                <select
                                    v-model="selectedRegion"
                                    id="region"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-black"
                                    @change="handleSearch"
                                >
                                    <option value="">All States</option>
                                    <option v-for="region in regions" :key="region" :value="region">
                                        {{ region }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Results Summary -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="text-gray-600">
                        Found
                        <span class="font-semibold text-gray-900">{{ filteredPlants.length }}</span>
                        plants
                        <span v-if="hasActiveFilters" class="text-sm"> (filtered from {{ (props.plants || []).length }} total) </span>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Sort Options -->
                        <select
                            v-model="sortBy"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-black"
                            @change="handleSort"
                        >
                            <option value="name">Sort by Name</option>
                            <option value="family">Sort by Family</option>
                            <option value="conservation">Sort by Conservation Status</option>
                        </select>

                        <!-- View Toggle -->
                        <div class="flex overflow-hidden rounded-lg border border-gray-300">
                            <button
                                @click="viewMode = 'grid'"
                                class="px-3 py-2 text-sm font-medium transition-colors duration-200"
                                :class="viewMode === 'grid' ? 'bg-black text-white' : 'text-gray-700 hover:bg-gray-50'"
                            >
                                <Grid3X3 class="h-4 w-4" />
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                class="px-3 py-2 text-sm font-medium transition-colors duration-200"
                                :class="viewMode === 'list' ? 'bg-black text-white' : 'text-gray-700 hover:bg-gray-50'"
                            >
                                <List class="h-4 w-4" />
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
                            class="group cursor-pointer overflow-hidden rounded-xl bg-white shadow-lg transition-shadow duration-300 hover:shadow-xl"
                            @click="selectedPlant = plant"
                        >
                            <div class="relative h-48 bg-gray-200">
                                <img
                                    :src="plant.path"
                                    :alt="plant.common_name || plant.scientific_name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <div class="absolute right-3 top-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="getConservationStatusColor(plant.iucn_category)">
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
                                        <MapPin class="mr-1 h-3 w-3" />
                                        {{ plant.region }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="overflow-hidden rounded-xl bg-white shadow-lg">
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="plant in paginatedPlants"
                                :key="plant.id"
                                class="cursor-pointer p-6 transition-colors duration-200 hover:bg-gray-50"
                                @click="selectedPlant = plant"
                            >
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="plant.path"
                                        :alt="plant.common_name || plant.scientific_name"
                                        class="h-16 w-16 rounded-lg object-cover"
                                    />

                                    <div class="min-w-0 flex-1">
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
                                                class="rounded-full px-3 py-1 text-xs font-medium"
                                                :class="getConservationStatusColor(plant.iucn_category)"
                                            >
                                                {{ getConservationStatusLabel(plant.iucn_category) }}
                                            </span>
                                        </div>

                                        <div class="mt-3 flex items-center space-x-6 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <MapPin class="mr-1 h-4 w-4" />
                                                {{ plant.region }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="mt-8 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="currentPage = page"
                                class="rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200"
                                :class="page === currentPage ? 'bg-black text-white' : 'text-gray-700 hover:bg-gray-100'"
                            >
                                {{ page }}
                            </button>

                            <button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- No Results -->
                <div v-else class="py-12 text-center">
                    <SearchX class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No plants found</h3>
                    <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
                    <button
                        @click="clearFilters"
                        class="mt-4 rounded-lg bg-black px-4 py-2 text-white transition-colors duration-200 hover:bg-gray-800"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Plant Detail Modal -->
        <div v-if="selectedPlant" class="fixed inset-0 z-50 overflow-y-auto" @click="selectedPlant = null">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div
                    class="inline-block w-full max-w-5xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <div class="bg-white">
                        <!-- Header Image -->
                        <div class="relative">
                            <img
                                :src="selectedPlant.path"
                                :alt="selectedPlant.common_name || selectedPlant.scientific_name"
                                class="h-80 w-full object-cover"
                            />
                            <button
                                @click="selectedPlant = null"
                                class="absolute right-4 top-4 rounded-full bg-white bg-opacity-90 p-2 transition-all duration-200 hover:bg-opacity-100"
                            >
                                <X class="h-6 w-6 text-gray-700" />
                            </button>
                        </div>

                        <div class="p-8">
                            <!-- Title Section -->
                            <div class="mb-6 flex items-start justify-between border-b border-gray-200 pb-6">
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
                                    class="rounded-full px-4 py-2 text-sm font-medium"
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
                            <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                                <!-- Identification Features -->
                                <div>
                                    <h3 class="mb-4 text-xl font-semibold text-gray-900">Identification Features</h3>
                                    <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                                        <div v-for="(value, key) in getDummyPlantDetails(selectedPlant).identification" :key="key">
                                            <p class="text-sm font-medium capitalize text-gray-500">{{ key }}:</p>
                                            <p class="text-gray-900">{{ value }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Care Requirements -->
                                <div>
                                    <h3 class="mb-4 text-xl font-semibold text-gray-900">Care Requirements</h3>
                                    <div class="space-y-3 rounded-lg bg-green-50 p-4">
                                        <div v-for="(value, key) in getDummyPlantDetails(selectedPlant).care" :key="key">
                                            <p class="text-sm font-medium capitalize text-green-700">{{ key }}:</p>
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
                                        class="rounded-lg border border-gray-200 p-4"
                                    >
                                        <p class="mb-1 text-sm font-medium capitalize text-gray-500">{{ key }}:</p>
                                        <p class="text-gray-900">{{ value }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="mb-6 border-l-4 border-black bg-gray-50 p-4">
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
                            <div class="mb-6 rounded-lg bg-blue-50 p-4">
                                <h4 class="mb-2 text-lg font-semibold text-gray-900">Location Information</h4>
                                <div class="flex items-center space-x-4 text-gray-700">
                                    <div class="flex items-center">
                                        <MapPin class="mr-2 h-5 w-5 text-blue-600" />
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
                                    class="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 transition-colors duration-200 hover:bg-gray-50"
                                >
                                    Close
                                </button>
                                <button class="rounded-lg bg-black px-6 py-2 text-white transition-colors duration-200 hover:bg-gray-800">
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
