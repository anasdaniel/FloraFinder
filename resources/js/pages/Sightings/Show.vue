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

const getOrganLabel = (organ: string): string => {
    const labels: Record<string, string> = {
        flower: '🌸 Flower',
        leaf: '🍃 Leaf',
        fruit: '🍎 Fruit',
        bark: '🪵 Bark',
        habit: '🌳 Habit',
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
        <div class="min-h-screen py-8 bg-gray-50">
            <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <Link
                        :href="route('sightings.index')"
                        class="inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-gray-900"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Back to My Sightings
                    </Link>
                </div>

                <div class="grid gap-8 lg:grid-cols-5">
                    <!-- Image Gallery -->
                    <div class="lg:col-span-3">
                        <div class="overflow-hidden bg-white shadow-sm rounded-2xl">
                            <!-- Main Image -->
                            <div class="relative aspect-[4/3] bg-gray-100">
                                <img
                                    :src="currentImage.image_url"
                                    :alt="sighting.common_name || sighting.scientific_name"
                                    class="object-contain w-full h-full"
                                />

                                <!-- Image counter -->
                                <div v-if="images.length > 1"
                                    class="absolute px-3 py-1 text-sm font-medium text-white rounded-full top-4 right-4 bg-black/60">
                                    {{ currentImageIndex + 1 }} / {{ images.length }}
                                </div>

                                <!-- Organ badge -->
                                <div v-if="currentImage.organ && currentImage.organ !== 'unknown'"
                                    class="absolute px-3 py-1 text-sm font-medium text-white bg-gray-800 rounded-full top-4 left-4">
                                    {{ getOrganLabel(currentImage.organ) }}
                                </div>

                                <!-- Navigation arrows -->
                                <template v-if="images.length > 1">
                                    <button
                                        @click="prevImage"
                                        class="absolute p-2 text-white transition-colors -translate-y-1/2 rounded-full left-4 top-1/2 bg-black/50 hover:bg-black/70"
                                    >
                                        <ChevronLeft class="w-6 h-6" />
                                    </button>
                                    <button
                                        @click="nextImage"
                                        class="absolute p-2 text-white transition-colors -translate-y-1/2 rounded-full right-4 top-1/2 bg-black/50 hover:bg-black/70"
                                    >
                                        <ChevronRight class="w-6 h-6" />
                                    </button>
                                </template>
                            </div>

                            <!-- Thumbnail Strip -->
                            <div v-if="images.length > 1" class="p-4 border-t border-gray-100">
                                <div class="flex gap-2 overflow-x-auto">
                                    <button
                                        v-for="(img, index) in images"
                                        :key="img.id"
                                        @click="currentImageIndex = index"
                                        class="relative flex-shrink-0 w-16 h-16 overflow-hidden border-2 rounded-lg transition-all"
                                        :class="currentImageIndex === index
                                            ? 'border-gray-800 ring-2 ring-gray-300'
                                            : 'border-transparent hover:border-gray-300'"
                                    >
                                        <img
                                            :src="img.image_url"
                                            :alt="`Image ${index + 1}`"
                                            class="object-cover w-full h-full"
                                        />
                                        <div v-if="img.organ && img.organ !== 'unknown'"
                                            class="absolute bottom-0 left-0 right-0 px-1 py-0.5 text-[10px] text-center text-white capitalize bg-black/60">
                                            {{ img.organ }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="sighting.description" class="p-6 mt-6 bg-white shadow-sm rounded-2xl">
                            <h3 class="mb-3 text-lg font-semibold text-gray-900">Description</h3>
                            <p class="leading-relaxed text-gray-600">{{ sighting.description }}</p>
                        </div>

                        <!-- Location Map Preview -->
                        <div v-if="sighting.latitude && sighting.longitude" class="p-6 mt-6 bg-white shadow-sm rounded-2xl">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Location</h3>
                                <button
                                    @click="openInMaps"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                                >
                                    <ExternalLink class="w-4 h-4" />
                                    Open in Maps
                                </button>
                            </div>
                            <div class="overflow-hidden rounded-xl">
                                <iframe
                                    :src="`https://www.openstreetmap.org/export/embed.html?bbox=${sighting.longitude - 0.01},${sighting.latitude - 0.01},${sighting.longitude + 0.01},${sighting.latitude + 0.01}&layer=mapnik&marker=${sighting.latitude},${sighting.longitude}`"
                                    class="w-full h-64 border-0"
                                    loading="lazy"
                                ></iframe>
                            </div>
                            <p class="mt-3 text-sm text-gray-500">
                                Coordinates: {{ Number(sighting.latitude).toFixed(6) }}, {{ Number(sighting.longitude).toFixed(6) }}
                            </p>
                        </div>
                    </div>

                    <!-- Sidebar Info -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Plant Info Card -->
                        <div class="p-6 bg-white shadow-sm rounded-2xl">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="p-2 rounded-lg bg-gray-100">
                                    <Leaf class="w-6 h-6 text-gray-600" />
                                </div>
                                <div>
                                    <h1 class="text-xl font-bold text-gray-900">
                                        {{ sighting.common_name || sighting.scientific_name }}
                                    </h1>
                                    <p class="text-sm italic text-gray-500">{{ sighting.scientific_name }}</p>
                                </div>
                            </div>

                            <!-- Conservation Status -->
                            <div v-if="sighting.plant?.iucn_category" class="mb-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full border"
                                    :class="getConservationStatusColor(sighting.plant.iucn_category)"
                                >
                                    {{ getConservationStatusLabel(sighting.plant.iucn_category) }}
                                </span>
                            </div>

                            <!-- Plant Details -->
                            <div class="pt-4 space-y-3 border-t border-gray-100">
                                <div v-if="sighting.plant?.family" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Family</span>
                                    <span class="font-medium text-gray-900">{{ sighting.plant.family }}</span>
                                </div>
                                <div v-if="sighting.plant?.genus" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Genus</span>
                                    <span class="font-medium text-gray-900">{{ sighting.plant.genus }}</span>
                                </div>
                                <div v-if="sighting.plant?.habitat" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Habitat</span>
                                    <span class="font-medium text-gray-900">{{ sighting.plant.habitat }}</span>
                                </div>
                            </div>

                            <!-- View Plant Button -->
                            <div v-if="sighting.plant_id" class="mt-4">
                                <Link
                                    :href="route('plants.show', sighting.plant_id)"
                                    class="flex items-center justify-center w-full gap-2 px-4 py-2 text-sm font-medium text-gray-700 transition-colors border border-gray-200 rounded-lg bg-gray-50 hover:bg-gray-100"
                                >
                                    <Leaf class="w-4 h-4" />
                                    View Plant Details
                                </Link>
                            </div>
                        </div>

                        <!-- Sighting Details Card -->
                        <div class="p-6 bg-white shadow-sm rounded-2xl">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Sighting Details</h3>

                            <div class="space-y-4">
                                <!-- Date -->
                                <div class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-blue-50">
                                        <Calendar class="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Date Spotted</p>
                                        <p class="text-sm text-gray-900">{{ formatDate(sighting.sighted_at || sighting.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Location -->
                                <div v-if="sighting.location_name || sighting.region" class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-purple-50">
                                        <MapPin class="w-4 h-4 text-purple-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Location</p>
                                        <p class="text-sm text-gray-900">{{ sighting.location_name || sighting.region }}</p>
                                        <p v-if="sighting.region && sighting.location_name" class="text-xs text-gray-500">{{ sighting.region }}</p>
                                    </div>
                                </div>

                                <!-- Zone -->
                                <div v-if="sighting.zone" class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-amber-50">
                                        <Navigation class="w-4 h-4 text-amber-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Zone</p>
                                        <p class="text-sm text-gray-900">{{ sighting.zone.zone_name }}</p>
                                    </div>
                                </div>

                                <!-- Reported By -->
                                <div v-if="sighting.user" class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-gray-50">
                                        <User class="w-4 h-4 text-gray-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Reported By</p>
                                        <p class="text-sm text-gray-900">{{ sighting.user.name }}</p>
                                    </div>
                                </div>

                                <!-- Recorded At -->
                                <div class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-gray-50">
                                        <Clock class="w-4 h-4 text-gray-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Recorded</p>
                                        <p class="text-sm text-gray-900">{{ formatDateTime(sighting.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Images Count -->
                                <div class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg bg-pink-50">
                                        <ImageIcon class="w-4 h-4 text-pink-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Photos</p>
                                        <p class="text-sm text-gray-900">{{ images.length }} image{{ images.length !== 1 ? 's' : '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Card -->
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
                                <button
                                    @click="deleteDialogOpen = true"
                                    class="flex items-center justify-center w-full gap-2 px-4 py-2 text-sm font-medium text-red-600 transition-colors border border-red-200 rounded-lg hover:bg-red-50"
                                >
                                    <Trash2 class="w-4 h-4" />
                                    Delete Sighting
                                </button>
                            </div>
                        </div>
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
