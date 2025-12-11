<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Eye,
    Filter,
    Grid3X3,
    List,
    MapPin,
    RefreshCw,
    Search,
    SearchX,
    Leaf,
    Image as ImageIcon,
    Trash2,
    ShieldAlert,
    ChevronDown,
    TreeDeciduous,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    sightings: PaginatedSightings;
    regions: string[];
    filters: {
        search?: string;
        region?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

// Reactive state
const searchQuery = ref(props.filters.search || '');
const selectedRegion = ref(props.filters.region || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const viewMode = ref<'grid' | 'list'>('grid');
const isLoading = ref(false);

// Delete confirmation
const deleteDialogOpen = ref(false);
const sightingToDelete = ref<Sighting | null>(null);
const isDeleting = ref(false);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([selectedRegion, dateFrom, dateTo], () => {
    applyFilters();
});

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        route('sightings.index'),
        {
            search: searchQuery.value || undefined,
            region: selectedRegion.value || undefined,
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
    dateFrom.value = '';
    dateTo.value = '';
};

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedRegion.value || dateFrom.value || dateTo.value;
});

const stats = computed(() => {
    const totalPhotos = props.sightings.data.reduce((sum, s) => sum + (s.images?.length || 1), 0);
    const uniqueSpecies = new Set(props.sightings.data.map(s => s.scientific_name)).size;
    return {
        total: props.sightings.total,
        regions: props.regions.length,
        photos: totalPhotos,
        species: uniqueSpecies
    };
});

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getConservationStatusInfo = (status: string | null) => {
    if (!status) return null;
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
    return categoryMap[status.toUpperCase()] || { label: status, color: 'text-gray-700', bgColor: 'bg-gray-100' };
};

const goToPage = (url: string | null) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const confirmDelete = (sighting: Sighting) => {
    sightingToDelete.value = sighting;
    deleteDialogOpen.value = true;
};

const deleteSighting = () => {
    if (!sightingToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('sightings.destroy', sightingToDelete.value.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            sightingToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const getPrimaryImage = (sighting: Sighting): string => {
    if (sighting.images && sighting.images.length > 0) {
        return sighting.images[0].image_url;
    }
    return sighting.image_url || '/images/placeholder-plant.jpg';
};
</script>

<template>
    <Head title="My Sightings" />

    <AppLayout>
        <div class="min-h-screen py-8 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header & Stats Combined -->
                <div class="mb-8 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-gray-900">My Sightings</h1>
                            <p class="mt-1 text-gray-600">
                                Track and manage your plant sighting reports
                            </p>
                        </div>
                        <Link
                            :href="route('plant-identifier')"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white transition-all bg-gray-900 rounded-xl hover:bg-black shadow-lg shadow-gray-900/10 hover:shadow-gray-900/20"
                        >
                            <Leaf class="w-4 h-4" />
                            Report New Sighting
                        </Link>
                    </div>

                    <!-- Modern Stats Strip -->
                    <div class="grid grid-cols-2 gap-px overflow-hidden bg-gray-200 border border-gray-200 shadow-sm rounded-2xl md:grid-cols-4">
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <Eye class="w-6 h-6 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Total Sightings</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-blue-50 rounded-xl">
                                <MapPin class="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.regions }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Regions</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-purple-50 rounded-xl">
                                <ImageIcon class="w-6 h-6 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.photos }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Photos</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-6 bg-white">
                            <div class="p-3 bg-amber-50 rounded-xl">
                                <Leaf class="w-6 h-6 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.species }}</p>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Species</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating Toolbar (Search & Filter) -->
                <div class="sticky top-4 z-20 mb-8">
                    <div class="p-2 bg-white/80 backdrop-blur-xl border border-gray-200/60 shadow-lg shadow-gray-200/20 rounded-2xl">
                        <div class="grid grid-cols-1 gap-2 lg:grid-cols-12">
                            <!-- Search -->
                            <div class="lg:col-span-4 relative group">
                                <Search class="absolute left-3.5 top-3 h-5 w-5 text-gray-400 group-focus-within:text-gray-600 transition-colors" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by plant name..."
                                    class="w-full h-11 pl-11 pr-4 bg-gray-50/50 hover:bg-gray-100/50 focus:bg-white border-transparent focus:border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-gray-900/5"
                                />
                            </div>

                            <!-- Region Select -->
                            <div class="lg:col-span-3 relative">
                                <MapPin class="absolute left-3.5 top-3 h-5 w-5 text-gray-400 pointer-events-none" />
                                <select
                                    v-model="selectedRegion"
                                    class="w-full h-11 pl-11 pr-8 bg-gray-50/50 hover:bg-gray-100/50 focus:bg-white border-transparent focus:border-gray-200 rounded-xl text-sm appearance-none transition-all focus:ring-2 focus:ring-gray-900/5 cursor-pointer"
                                >
                                    <option value="">All Regions</option>
                                    <option v-for="region in regions" :key="region" :value="region">
                                        {{ region }}
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-3.5 top-3.5 h-4 w-4 text-gray-400 pointer-events-none" />
                            </div>

                            <!-- Date From -->
                            <div class="lg:col-span-2 relative">
                                <Calendar class="absolute left-3.5 top-3 h-5 w-5 text-gray-400 pointer-events-none" />
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    class="w-full h-11 pl-11 pr-4 bg-gray-50/50 hover:bg-gray-100/50 focus:bg-white border-transparent focus:border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-gray-900/5"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="lg:col-span-2 relative">
                                <Calendar class="absolute left-3.5 top-3 h-5 w-5 text-gray-400 pointer-events-none" />
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    class="w-full h-11 pl-11 pr-4 bg-gray-50/50 hover:bg-gray-100/50 focus:bg-white border-transparent focus:border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-gray-900/5"
                                />
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

                <!-- Sightings Grid -->
                <div v-else-if="sightings.data.length > 0">
                    <!-- Grid View -->
                    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <div
                            v-for="sighting in sightings.data"
                            :key="sighting.id"
                            class="overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm group rounded-xl hover:shadow-lg flex flex-col"
                        >
                            <!-- Plant Header with Full Image -->
                            <Link :href="route('sightings.show', sighting.id)" class="relative w-full h-48 overflow-hidden bg-gray-100 border-b border-gray-100 block">
                                <img
                                    :src="getPrimaryImage(sighting)"
                                    :alt="sighting.common_name || sighting.scientific_name"
                                    class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
                                />

                                <!-- Overlay Badges -->
                                <div class="absolute inset-0 p-4 pointer-events-none">
                                    <div class="flex items-start justify-between">
                                        <!-- Left Badge (Status) -->
                                        <div v-if="sighting.plant?.iucn_category" class="inline-flex">
                                            <span
                                                class="px-2.5 py-1 text-xs font-semibold backdrop-blur-md rounded-lg shadow-sm border"
                                                :class="[
                                                    getConservationStatusInfo(sighting.plant.iucn_category)?.color.replace('text-', 'bg-').replace('-800', '-500/90').replace('-900', '-500/90'),
                                                    'text-white border-white/20'
                                                ]"
                                            >
                                                {{ sighting.plant.iucn_category }}
                                            </span>
                                        </div>
                                        <div v-else class="inline-flex"></div>

                                        <!-- Right Badge (Image Count) -->
                                        <div v-if="sighting.images && sighting.images.length > 1" class="inline-flex">
                                            <span class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-black/60 backdrop-blur-md rounded-lg shadow-sm border border-white/10">
                                                <ImageIcon class="w-3 h-3" />
                                                {{ sighting.images.length }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>

                            <!-- Plant Content -->
                            <div class="p-5 flex flex-col flex-1">
                                <div class="mb-4">
                                    <Link :href="route('sightings.show', sighting.id)">
                                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-emerald-700 transition-colors line-clamp-1">
                                            {{ sighting.common_name || sighting.scientific_name }}
                                        </h3>
                                    </Link>
                                    <p class="text-sm font-medium italic text-gray-500 font-serif mt-0.5 line-clamp-1">
                                        {{ sighting.scientific_name }}
                                    </p>
                                </div>

                                <!-- Info -->
                                <div class="space-y-3 mb-4">
                                    <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-2 text-gray-600 text-sm">
                                        <MapPin class="w-4 h-4 text-gray-400" />
                                        <span class="truncate">{{ sighting.location_name || sighting.region }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-600 text-sm">
                                        <Calendar class="w-4 h-4 text-gray-400" />
                                        <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto pt-3 border-t border-gray-100 flex items-center justify-between">
                                    <Link
                                        :href="route('sightings.show', sighting.id)"
                                        class="text-xs font-medium text-gray-600 hover:text-gray-900"
                                    >
                                        View Details →
                                    </Link>
                                    <button
                                        @click="confirmDelete(sighting)"
                                        class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50"
                                        title="Delete Sighting"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="overflow-hidden bg-white shadow-sm rounded-xl">
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="sighting in sightings.data"
                                :key="sighting.id"
                                class="flex items-center gap-4 p-4 transition-colors hover:bg-gray-50"
                            >
                                <!-- Thumbnail -->
                                <Link :href="route('sightings.show', sighting.id)" class="relative flex-shrink-0 w-12 h-12 overflow-hidden rounded-lg bg-gray-100 group">
                                    <img
                                        :src="getPrimaryImage(sighting)"
                                        :alt="sighting.common_name || sighting.scientific_name"
                                        class="object-cover w-full h-full"
                                    />
                                    <div v-if="sighting.images && sighting.images.length > 1"
                                        class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-xs font-bold text-white flex items-center gap-0.5">
                                            <ImageIcon class="w-3 h-3" />
                                            {{ sighting.images.length }}
                                        </span>
                                    </div>
                                </Link>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link :href="route('sightings.show', sighting.id)">
                                            <h3 class="font-semibold text-gray-900 hover:text-gray-600">
                                                {{ sighting.common_name || sighting.scientific_name }}
                                            </h3>
                                        </Link>
                                        <span
                                            v-if="sighting.plant?.iucn_category"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="[
                                                getConservationStatusInfo(sighting.plant.iucn_category)?.color,
                                                getConservationStatusInfo(sighting.plant.iucn_category)?.bgColor
                                            ]"
                                        >
                                            <ShieldAlert class="w-3 h-3" />
                                            {{ getConservationStatusInfo(sighting.plant.iucn_category)?.label }}
                                        </span>
                                    </div>
                                    <p class="text-sm italic text-gray-500">{{ sighting.scientific_name }}</p>
                                    <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                                        <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-1">
                                            <MapPin class="w-3 h-3" />
                                            <span class="truncate max-w-[150px]">{{ sighting.location_name || sighting.region }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Calendar class="w-3 h-3" />
                                            <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('sightings.show', sighting.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                                    >
                                        <ChevronRight class="w-5 h-5" />
                                    </Link>
                                    <button
                                        @click="confirmDelete(sighting)"
                                        class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50"
                                    >
                                        <Trash2 class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="sightings.last_page > 1" class="flex justify-center mt-8">
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="goToPage(sightings.links[0]?.url)"
                                :disabled="sightings.current_page === 1"
                                class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <template v-for="(link, index) in sightings.links.slice(1, -1)" :key="index">
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
                                @click="goToPage(sightings.links[sightings.links.length - 1]?.url)"
                                :disabled="sightings.current_page === sightings.last_page"
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
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No sightings found</h3>
                    <p class="mb-6 text-gray-500">
                        {{ hasActiveFilters ? 'Try adjusting your filters.' : "You haven't reported any sightings yet." }}
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
                            Report Your First Sighting
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Sighting</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this sighting of
                        <strong>{{ sightingToDelete?.common_name || sightingToDelete?.scientific_name }}</strong>?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false" :disabled="isDeleting">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteSighting" :disabled="isDeleting">
                        <RefreshCw v-if="isDeleting" class="w-4 h-4 mr-2 animate-spin" />
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
