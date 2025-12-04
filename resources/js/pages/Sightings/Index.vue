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
    Clock,
    User,
    Trash2,
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

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getConservationStatusColor = (status: string | null): string => {
    const colors: Record<string, string> = {
        NE: 'bg-blue-100 text-blue-700',
        DD: 'bg-gray-100 text-gray-700',
        LC: 'bg-green-100 text-green-700',
        NT: 'bg-yellow-100 text-yellow-700',
        VU: 'bg-orange-100 text-orange-700',
        EN: 'bg-red-100 text-red-700',
        CR: 'bg-red-200 text-red-800',
        EW: 'bg-gray-200 text-gray-800',
        EX: 'bg-gray-300 text-gray-900',
    };
    return colors[status || ''] || 'bg-gray-100 text-gray-600';
};

const getConservationStatusLabel = (status: string | null): string => {
    const labels: Record<string, string> = {
        NE: 'Not Evaluated',
        DD: 'Data Deficient',
        LC: 'Least Concern',
        NT: 'Near Threatened',
        VU: 'Vulnerable',
        EN: 'Endangered',
        CR: 'Critically Endangered',
        EW: 'Extinct in Wild',
        EX: 'Extinct',
    };
    return labels[status || ''] || 'Unknown';
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
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">My Sightings</h1>
                            <p class="mt-1 text-gray-600">
                                Track and manage your plant sighting reports
                            </p>
                        </div>
                        <Link
                            :href="route('plant-identifier')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-gray-900 rounded-lg hover:bg-black"
                        >
                            <Leaf class="w-4 h-4" />
                            Report New Sighting
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
                    <div class="p-4 bg-white border border-gray-100 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-gray-100">
                                <Eye class="w-5 h-5 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ sightings.total }}</p>
                                <p class="text-sm text-gray-500">Total Sightings</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-blue-50">
                                <MapPin class="w-5 h-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ regions.length }}</p>
                                <p class="text-sm text-gray-500">Regions</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-purple-50">
                                <ImageIcon class="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ sightings.data.reduce((sum, s) => sum + (s.images?.length || 1), 0) }}
                                </p>
                                <p class="text-sm text-gray-500">Photos</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white border border-gray-100 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-amber-50">
                                <Leaf class="w-5 h-5 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ new Set(sightings.data.map(s => s.scientific_name)).size }}
                                </p>
                                <p class="text-sm text-gray-500">Species</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="p-6 mb-6 bg-white shadow-sm rounded-xl">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                        <!-- Search Bar -->
                        <div class="lg:col-span-4">
                            <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Search
                            </label>
                            <div class="relative group">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by plant name..."
                                    class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm transition-all focus:border-gray-400 focus:bg-white focus:ring-gray-400"
                                />
                                <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <!-- Region Filter -->
                        <div class="lg:col-span-3">
                            <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Region
                            </label>
                            <select
                                v-model="selectedRegion"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 text-sm focus:border-green-500 focus:ring-green-500"
                            >
                                <option value="">All Regions</option>
                                <option v-for="region in regions" :key="region" :value="region">
                                    {{ region }}
                                </option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="lg:col-span-2">
                            <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                From
                            </label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 text-sm focus:border-gray-400 focus:ring-gray-400"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="lg:col-span-2">
                            <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                To
                            </label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 text-sm focus:border-gray-400 focus:ring-gray-400"
                            />
                        </div>

                        <!-- View Toggle -->
                        <div class="lg:col-span-1">
                            <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                View
                            </label>
                            <div class="flex p-1 border border-gray-200 rounded-lg bg-gray-50">
                                <button
                                    @click="viewMode = 'grid'"
                                    class="flex-1 p-2 transition-all rounded-md"
                                    :class="viewMode === 'grid' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400'"
                                >
                                    <Grid3X3 class="w-4 h-4 mx-auto" />
                                </button>
                                <button
                                    @click="viewMode = 'list'"
                                    class="flex-1 p-2 transition-all rounded-md"
                                    :class="viewMode === 'list' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400'"
                                >
                                    <List class="w-4 h-4 mx-auto" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <div v-if="hasActiveFilters" class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <Filter class="w-4 h-4" />
                            <span>Showing filtered results</span>
                        </div>
                        <button
                            @click="clearFilters"
                            class="text-xs font-medium text-red-500 hover:text-red-700"
                        >
                            Clear filters
                        </button>
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
                            class="overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm group rounded-xl hover:shadow-lg"
                        >
                            <!-- Image -->
                            <Link :href="route('sightings.show', sighting.id)" class="relative block aspect-[4/3] overflow-hidden">
                                <img
                                    :src="getPrimaryImage(sighting)"
                                    :alt="sighting.common_name || sighting.scientific_name"
                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                />
                                <!-- Image count badge -->
                                <div v-if="sighting.images && sighting.images.length > 1"
                                    class="absolute flex items-center gap-1 px-2 py-1 text-xs font-medium text-white rounded-full bottom-2 right-2 bg-black/60">
                                    <ImageIcon class="w-3 h-3" />
                                    {{ sighting.images.length }}
                                </div>
                                <!-- Conservation status -->
                                <div v-if="sighting.plant?.iucn_category"
                                    class="absolute px-2 py-1 text-xs font-medium rounded-full top-2 left-2"
                                    :class="getConservationStatusColor(sighting.plant.iucn_category)">
                                    {{ sighting.plant.iucn_category }}
                                </div>
                            </Link>

                            <!-- Content -->
                            <div class="p-4">
                                <Link :href="route('sightings.show', sighting.id)">
                                    <h3 class="font-semibold text-gray-900 transition-colors hover:text-gray-600">
                                        {{ sighting.common_name || sighting.scientific_name }}
                                    </h3>
                                </Link>
                                <p class="text-sm italic text-gray-500">{{ sighting.scientific_name }}</p>

                                <div class="mt-3 space-y-1.5">
                                    <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-2 text-xs text-gray-500">
                                        <MapPin class="w-3.5 h-3.5 flex-shrink-0" />
                                        <span class="truncate">{{ sighting.location_name || sighting.region }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <Calendar class="w-3.5 h-3.5 flex-shrink-0" />
                                        <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-3 mt-3 border-t border-gray-100">
                                    <Link
                                        :href="route('sightings.show', sighting.id)"
                                        class="text-xs font-medium text-gray-600 hover:text-gray-900"
                                    >
                                        View Details →
                                    </Link>
                                    <button
                                        @click="confirmDelete(sighting)"
                                        class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50"
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
                                <Link :href="route('sightings.show', sighting.id)" class="relative flex-shrink-0 overflow-hidden rounded-lg w-20 h-20">
                                    <img
                                        :src="getPrimaryImage(sighting)"
                                        :alt="sighting.common_name || sighting.scientific_name"
                                        class="object-cover w-full h-full"
                                    />
                                    <div v-if="sighting.images && sighting.images.length > 1"
                                        class="absolute flex items-center justify-center w-5 h-5 text-xs font-medium text-white rounded-full bottom-1 right-1 bg-black/60">
                                        {{ sighting.images.length }}
                                    </div>
                                </Link>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <Link :href="route('sightings.show', sighting.id)">
                                            <h3 class="font-semibold text-gray-900 hover:text-gray-600">
                                                {{ sighting.common_name || sighting.scientific_name }}
                                            </h3>
                                        </Link>
                                        <span v-if="sighting.plant?.iucn_category"
                                            class="px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="getConservationStatusColor(sighting.plant.iucn_category)">
                                            {{ sighting.plant.iucn_category }}
                                        </span>
                                    </div>
                                    <p class="text-sm italic text-gray-500">{{ sighting.scientific_name }}</p>
                                    <div class="flex items-center gap-4 mt-1">
                                        <div v-if="sighting.location_name || sighting.region" class="flex items-center gap-1 text-xs text-gray-500">
                                            <MapPin class="w-3 h-3" />
                                            <span>{{ sighting.location_name || sighting.region }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <Calendar class="w-3 h-3" />
                                            <span>{{ formatDate(sighting.sighted_at || sighting.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('sightings.show', sighting.id)"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                    >
                                        View
                                    </Link>
                                    <button
                                        @click="confirmDelete(sighting)"
                                        class="p-1.5 text-gray-400 rounded-lg hover:text-red-500 hover:bg-red-50 transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
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
