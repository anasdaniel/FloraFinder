<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Clock,
    Edit,
    ExternalLink,
    Leaf,
    MapPin,
    Navigation,
    RefreshCw,
    Trash2,
    User,
    Image as ImageIcon,
    Share2,
    Info,
    Maximize2
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
        scientific_name: string;
        common_name: string | null;
        family: string | null;
        genus: string | null;
        iucn_category: string | null;
        habitat: string | null;
        description: string | null;
    };
    zone?: {
        id: number;
        zone_name: string;
    };
}

const props = defineProps<{
    sighting: Sighting;
}>();

// Image gallery state
const currentImageIndex = ref(0);
const deleteDialogOpen = ref(false);
const isDeleting = ref(false);
const isImageModalOpen = ref(false);

const images = computed(() => {
    if (props.sighting.images && props.sighting.images.length > 0) {
        return props.sighting.images;
    }
    return [{ id: 0, image_url: props.sighting.image_url, organ: 'unknown' }];
});

const currentImage = computed(() => images.value[currentImageIndex.value]);

const nextImage = () => {
    currentImageIndex.value = (currentImageIndex.value + 1) % images.value.length;
};

const prevImage = () => {
    currentImageIndex.value = currentImageIndex.value === 0
        ? images.value.length - 1
        : currentImageIndex.value - 1;
};

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
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
        NE: 'bg-blue-100 text-blue-700 border-blue-200',
        DD: 'bg-gray-100 text-gray-700 border-gray-200',
        LC: 'bg-green-100 text-green-700 border-green-200',
        NT: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        VU: 'bg-orange-100 text-orange-700 border-orange-200',
        EN: 'bg-red-100 text-red-700 border-red-200',
        CR: 'bg-red-200 text-red-800 border-red-300',
        EW: 'bg-gray-200 text-gray-800 border-gray-300',
        EX: 'bg-gray-300 text-gray-900 border-gray-400',
    };
    return colors[status || ''] || 'bg-gray-100 text-gray-600 border-gray-200';
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

const formatCoordinates = (lat: number, lng: number): string => {
    const latDir = lat >= 0 ? 'N' : 'S';
    const lngDir = lng >= 0 ? 'E' : 'W';
    return `${latDir} ${Math.abs(lat).toFixed(5)}, ${lngDir} ${Math.abs(lng).toFixed(5)}`;
};

const getOrganLabel = (organ: string): string => {
    const labels: Record<string, string> = {
        flower: '🌸 Flower',
        leaf: '🍃 Leaf',
        fruit: '🍎 Fruit',
        bark: '🪵 Bark',
        auto: '✨ Auto',
    };
    return labels[organ] || organ;
};

const openInMaps = () => {
    if (props.sighting.latitude && props.sighting.longitude) {
        const url = `https://www.google.com/maps?q=${props.sighting.latitude},${props.sighting.longitude}`;
        window.open(url, '_blank');
    }
};

const deleteSighting = () => {
    isDeleting.value = true;
    router.delete(route('sightings.destroy', props.sighting.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${sighting.common_name || sighting.scientific_name} - Sighting`" />

    <AppLayout>
        <div class="min-h-screen pb-12 bg-gray-50">
            <!-- Hero Section -->
            <div class="relative text-white bg-gray-900">
                <div class="absolute inset-0 overflow-hidden">
                    <img
                        :src="currentImage.image_url"
                        class="object-cover w-full h-full scale-105 opacity-40 blur-sm"
                        alt="Sighting background"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>
                </div>

                <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 sm:py-20">
                    <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
                        <div class="flex-1">
                            <Link
                                :href="route('sightings.index')"
                                class="inline-flex items-center gap-2 mb-6 text-sm text-gray-300 transition-colors hover:text-white"
                            >
                                <ArrowLeft class="w-4 h-4" />
                                Back to My Sightings
                            </Link>

                            <div class="flex items-center gap-3 mb-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-sm backdrop-blur-sm border border-white/10">
                                    <Calendar class="w-3.5 h-3.5" />
                                    {{ formatDate(sighting.sighted_at) }}
                                </span>
                                <span v-if="sighting.zone" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/20 text-amber-100 text-sm backdrop-blur-sm border border-amber-500/20">
                                    <Navigation class="w-3.5 h-3.5" />
                                    {{ sighting.zone.zone_name }}
                                </span>
                            </div>

                            <h1 class="mb-2 text-4xl font-bold tracking-tight md:text-5xl">
                                {{ sighting.common_name || sighting.scientific_name }}
                            </h1>
                            <p class="font-serif text-xl italic text-gray-300">
                                {{ sighting.scientific_name }}
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="openInMaps"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors rounded-lg bg-white/10 hover:bg-white/20 backdrop-blur-sm"
                            >
                                <MapPin class="w-4 h-4" />
                                View Map
                            </button>
                            <button
                                @click="deleteDialogOpen = true"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-200 transition-colors border rounded-lg bg-red-500/20 hover:bg-red-500/30 backdrop-blur-sm border-red-500/20"
                            >
                                <Trash2 class="w-4 h-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 px-4 mx-auto -mt-8 max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-8 lg:col-span-2">

                        <!-- Image Gallery -->
                        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                            <div class="relative aspect-[4/3] bg-gray-100 group">
                                <img
                                    :src="currentImage.image_url"
                                    :alt="sighting.common_name || sighting.scientific_name"
                                    class="object-contain w-full h-full cursor-zoom-in"
                                    @click="isImageModalOpen = true"
                                />

                                <!-- Image counter -->
                                <div v-if="images.length > 1"
                                    class="absolute px-3 py-1 text-sm font-medium text-white rounded-full top-4 right-4 bg-black/60 backdrop-blur-sm">
                                    {{ currentImageIndex + 1 }} / {{ images.length }}
                                </div>

                                <!-- Organ badge -->
                                <div v-if="currentImage.organ && currentImage.organ !== 'unknown'"
                                    class="absolute px-3 py-1 text-sm font-medium text-white capitalize rounded-full bg-black/60 backdrop-blur-sm top-4 left-4">
                                    {{ getOrganLabel(currentImage.organ) }}
                                </div>

                                <!-- Navigation arrows -->
                                <template v-if="images.length > 1">
                                    <button
                                        @click.stop="prevImage"
                                        class="absolute p-3 text-white transition-all -translate-y-1/2 rounded-full opacity-0 left-4 top-1/2 bg-black/30 hover:bg-black/60 group-hover:opacity-100"
                                    >
                                        <ChevronLeft class="w-6 h-6" />
                                    </button>
                                    <button
                                        @click.stop="nextImage"
                                        class="absolute p-3 text-white transition-all -translate-y-1/2 rounded-full opacity-0 right-4 top-1/2 bg-black/30 hover:bg-black/60 group-hover:opacity-100"
                                    >
                                        <ChevronRight class="w-6 h-6" />
                                    </button>
                                </template>
                            </div>

                            <!-- Thumbnail Strip -->
                            <div v-if="images.length > 1" class="p-4 border-t border-gray-100 bg-gray-50/50">
                                <div class="flex gap-3 pb-2 overflow-x-auto scrollbar-hide">
                                    <button
                                        v-for="(img, index) in images"
                                        :key="img.id"
                                        @click="currentImageIndex = index"
                                        class="relative flex-shrink-0 w-20 h-20 overflow-hidden transition-all duration-200 rounded-lg"
                                        :class="currentImageIndex === index
                                            ? 'ring-2 ring-offset-2 ring-gray-900 shadow-md scale-105'
                                            : 'opacity-70 hover:opacity-100 hover:scale-105'"
                                    >
                                        <img
                                            :src="img.image_url"
                                            :alt="`Image ${index + 1}`"
                                            class="object-cover w-full h-full"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="sighting.description" class="p-8 bg-white border border-gray-100 shadow-sm rounded-2xl">
                            <h2 class="flex items-center gap-2 mb-4 text-xl font-bold text-gray-900">
                                <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
                                Field Notes
                            </h2>
                            <p class="text-lg leading-relaxed text-gray-600">{{ sighting.description }}</p>
                        </div>

                        <!-- Location Map -->
                        <div v-if="sighting.latitude && sighting.longitude" class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="flex items-center gap-2 text-xl font-bold text-gray-900">
                                    <span class="w-1 h-6 bg-purple-500 rounded-full"></span>
                                    Location
                                </h2>
                                <div class="px-3 py-1 font-mono text-sm text-gray-500 bg-gray-100 rounded-md">
                                    {{ formatCoordinates(sighting.latitude, sighting.longitude) }}
                                </div>
                            </div>
                            <div class="overflow-hidden border border-gray-200 shadow-inner rounded-xl">
                                <iframe
                                    :src="`https://www.openstreetmap.org/export/embed.html?bbox=${Number(sighting.longitude) - 0.01},${Number(sighting.latitude) - 0.01},${Number(sighting.longitude) + 0.01},${Number(sighting.latitude) + 0.01}&layer=mapnik&marker=${sighting.latitude},${sighting.longitude}`"
                                    class="w-full border-0 h-80"
                                    loading="lazy"
                                ></iframe>
                            </div>
                            <div class="flex items-center justify-between mt-4 text-sm">
                                <span class="flex items-center gap-2 text-gray-600">
                                    <MapPin class="w-4 h-4 text-gray-400" />
                                    {{ sighting.location_name || 'Unknown Location' }}
                                </span>
                                <button @click="openInMaps" class="flex items-center gap-1 font-medium text-blue-600 hover:text-blue-700">
                                    Open in Google Maps <ExternalLink class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Plant Info Card -->
                        <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
                            <h3 class="mb-4 font-semibold text-gray-900">Identification</h3>

                            <div class="flex items-start gap-4 mb-6">
                                <div class="p-3 text-green-600 rounded-xl bg-green-50">
                                    <Leaf class="w-6 h-6" />
                                </div>
                                <div>
                                    <h4 class="mb-1 text-lg font-bold leading-tight text-gray-900">
                                        {{ sighting.common_name || sighting.scientific_name }}
                                    </h4>
                                    <p class="italic text-gray-500">{{ sighting.scientific_name }}</p>
                                </div>
                            </div>

                            <div class="pt-4 space-y-4 border-t border-gray-100">
                                <div v-if="sighting.plant?.family" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Family</span>
                                    <span class="text-sm font-medium text-gray-900">{{ sighting.plant.family }}</span>
                                </div>
                                <div v-if="sighting.plant?.genus" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Genus</span>
                                    <span class="text-sm font-medium text-gray-900">{{ sighting.plant.genus }}</span>
                                </div>
                                <div v-if="sighting.plant?.iucn_category" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Status</span>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full border"
                                        :class="getConservationStatusColor(sighting.plant.iucn_category)"
                                    >
                                        {{ getConservationStatusLabel(sighting.plant.iucn_category) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="sighting.plant_id" class="pt-4 mt-6 border-t border-gray-100">
                                <Link
                                    :href="route('plants.show', sighting.plant_id)"
                                    class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-medium text-white transition-colors bg-gray-900 hover:bg-black rounded-xl"
                                >
                                    <Info class="w-4 h-4" />
                                    View Full Plant Profile
                                </Link>
                            </div>
                        </div>

                        <!-- Sighting Details Card -->
                        <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
                            <h3 class="mb-4 font-semibold text-gray-900">Sighting Details</h3>

                            <div class="space-y-4">
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                                    <div class="p-2 text-gray-500 bg-white rounded-lg shadow-sm">
                                        <User class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-gray-500 uppercase">Observer</div>
                                        <div class="text-sm font-medium text-gray-900">{{ sighting.user?.name || 'Unknown' }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                                    <div class="p-2 text-gray-500 bg-white rounded-lg shadow-sm">
                                        <Clock class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-gray-500 uppercase">Recorded At</div>
                                        <div class="text-sm font-medium text-gray-900">{{ formatDateTime(sighting.created_at) }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                                    <div class="p-2 text-gray-500 bg-white rounded-lg shadow-sm">
                                        <ImageIcon class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-gray-500 uppercase">Evidence</div>
                                        <div class="text-sm font-medium text-gray-900">{{ images.length }} Photo{{ images.length !== 1 ? 's' : '' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <Dialog :open="isImageModalOpen" @update:open="isImageModalOpen = $event">
            <DialogContent class="max-w-4xl p-0 overflow-hidden bg-black border-0">
                <div class="relative w-full h-[80vh] flex items-center justify-center">
                    <img
                        :src="currentImage.image_url"
                        :alt="sighting.common_name"
                        class="object-contain max-w-full max-h-full"
                    />
                    <button
                        @click="isImageModalOpen = false"
                        class="absolute p-2 text-white rounded-full top-4 right-4 bg-black/50 hover:bg-black/70"
                    >
                        <Maximize2 class="w-5 h-5" />
                    </button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Sighting</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this sighting of
                        <strong>{{ sighting.common_name || sighting.scientific_name }}</strong>?
                        This action cannot be undone and all associated images will be removed.
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
