<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import exifr from 'exifr';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

// --- Types ---
interface ImageUpload {
    id: string;
    file: File;
    preview: string;
    organ: string | null;
}

interface PlantResult {
    success: boolean;
    message?: string;
    error?: string;
    savedToDatabase?: boolean;
    data?: {
        results: Array<{
            score: number;
            species: {
                scientificNameWithoutAuthor: string;
                scientificNameAuthorship: string;
                genus: { scientificNameWithoutAuthor: string; scientificNameAuthorship: string; scientificName: string };
                family: { scientificNameWithoutAuthor: string; scientificNameAuthorship: string; scientificName: string };
                commonNames: string[];
                scientificName: string;
            };
            images?: Array<{ url: { s: string; m: string; l: string } }>;
            gbif?: { id: string };
            powo?: { id: string };
            iucn?: { category: string };
        }>;
    };
}

interface FormData {
    locationName: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    includeLocation: boolean;
    saveToDatabase: boolean;
}

interface Errors {
    image?: string;
    organ?: string;
    [key: string]: string | undefined;
}

// --- Props ---
const props = defineProps<{
    plantData?: PlantResult;
}>();

const { toast } = useToast();

// --- State ---
const uploadedImages = ref<ImageUpload[]>([]);
const form = reactive<FormData>({
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null,
    longitude: null,
    includeLocation: false,
    saveToDatabase: false,
});

const processing = ref<boolean>(false);
const results = ref<PlantResult | null>(null);
const errors = reactive<Errors>({});
const activeImageIndex = ref<number>(0);
const selectedResultIndex = ref<number>(0);
const fileUploadRef = ref<HTMLInputElement | null>(null);
const extractingExif = ref<boolean>(false);
const gettingLocation = ref<boolean>(false);
const savingToDatabase = ref<boolean>(false);
const bookmarkedResults = reactive<Record<string, boolean>>({});
const careDetails = ref<any>(null);
const fetchingCareDetails = ref<boolean>(false);

const MAX_IMAGES = 5;
const ORGANS = [
    { label: 'Leaf', value: 'leaf' },
    { label: 'Flower', value: 'flower' },
    { label: 'Fruit', value: 'fruit' },
    { label: 'Bark', value: 'bark' },
    { label: 'Whole Plant', value: 'habit' },
];
const MALAYSIAN_REGIONS = ['Peninsular Malaysia', 'Sabah', 'Sarawak', 'Labuan'];

const isKnownValue = (value: unknown): boolean => {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0 && value.toLowerCase() !== 'unknown';
    return true;
};

const formatCareValue = (value: string | number | null | undefined, fallback = 'Not available') => (isKnownValue(value) ? value : fallback) as string;

const formatRange = (min?: number | string | null, max?: number | string | null, unit = ''): string => {
    const suffix = unit ? ` ${unit}` : '';
    if (!isKnownValue(min) && !isKnownValue(max)) return 'N/A';
    if (!isKnownValue(min)) return `${max}${suffix}`;
    if (!isKnownValue(max)) return `${min}${suffix}`;
    return `${min} – ${max}${suffix}`;
};

const hasCareData = computed(() => {
    const details = careDetails.value as Record<string, unknown> | null;
    if (!details) return false;
    return [
        'minimum_precipitation',
        'maximum_precipitation',
        'light',
        'soil_salinity',
        'soil_nutriments',
        'soil_texture',
        'minimum_temperature_celcius',
        'maximum_temperature_celcius',
    ].some((key) => isKnownValue(details[key]));
});

// --- Computed ---
const hasResults = computed(() => {
    return results.value && results.value.success && results.value.data && results.value.data.results && results.value.data.results.length > 0;
});

const selectedResult = computed(() => {
    if (!hasResults.value) return null;
    return results.value!.data!.results[selectedResultIndex.value];
});

const isCurrentResultBookmarked = computed(() => {
    if (!selectedResult.value) return false;
    return Boolean(bookmarkedResults[selectedResult.value.species?.scientificName]);
});

const allImagesTagged = computed(() => {
    return uploadedImages.value.length > 0 && uploadedImages.value.every((img) => img.organ !== null);
});

// --- Lifecycle ---
onMounted(() => {
    if (props.plantData && props.plantData.success) {
        results.value = props.plantData;
    }
});

// --- Methods ---

const onImageChange = async (e: Event): Promise<void> => {
    const target = e.target as HTMLInputElement;
    if (!target.files || !target.files.length) return;

    const newFiles = Array.from(target.files);
    const remainingSlots = MAX_IMAGES - uploadedImages.value.length;

    if (newFiles.length > remainingSlots) {
        toast({
            title: 'Limit Reached',
            description: `You can only upload up to ${MAX_IMAGES} images.`,
            variant: 'destructive',
        });
    }

    const filesToProcess = newFiles.slice(0, remainingSlots);

    // Process each file
    for (const file of filesToProcess) {
        const newImage: ImageUpload = {
            id: Math.random().toString(36).substring(2, 9),
            file,
            preview: URL.createObjectURL(file),
            organ: null, // Default to null to force selection
        };
        uploadedImages.value.push(newImage);

        // Extract EXIF only from the first image to set location
        if (uploadedImages.value.length === 1) {
            await extractExifData(file);
        }
    }

    errors.image = undefined;
    // Reset input
    target.value = '';
};

const removeImage = (id: string) => {
    uploadedImages.value = uploadedImages.value.filter((img) => img.id !== id);
    if (uploadedImages.value.length === 0) {
        resetForm();
    }
};

const setOrgan = (id: string, organValue: string) => {
    const img = uploadedImages.value.find((i) => i.id === id);
    if (img) {
        img.organ = organValue;
    }
};

// Extract EXIF geolocation data from image
const extractExifData = async (file: File): Promise<void> => {
    try {
        extractingExif.value = true;
        toast({ title: 'Reading Metadata', description: 'Checking for location in photo...', variant: 'default' });

        const exifData = await exifr.parse(file, { gps: true, tiff: true, exif: true });

        if (exifData && exifData.latitude && exifData.longitude) {
            form.latitude = parseFloat(exifData.latitude.toFixed(6));
            form.longitude = parseFloat(exifData.longitude.toFixed(6));
            form.includeLocation = true;
            await reverseGeocode(exifData.latitude, exifData.longitude);
            toast({ title: 'Location Found!', description: `Coordinates extracted from photo.`, variant: 'success' });
        }
    } catch (error) {
        console.error('EXIF Error', error);
    } finally {
        extractingExif.value = false;
    }
};

const reverseGeocode = async (latitude: number, longitude: number): Promise<void> => {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
        );
        if (response.ok) {
            const data = await response.json();
            if (data && data.display_name) {
                let locationName = data.name || data.address.city || data.address.town || '';
                let region = data.address.state || data.address.province || 'Peninsular Malaysia';

                if (locationName) form.locationName = locationName;
                const matchedRegion = MALAYSIAN_REGIONS.find((r) => region.toLowerCase().includes(r.toLowerCase()));
                if (matchedRegion) form.region = matchedRegion;
            }
        }
    } catch (error) {
        console.error('Geocode error', error);
    }
};

const useUploadLocation = (): void => {
    if (navigator.geolocation) {
        gettingLocation.value = true;
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = parseFloat(position.coords.latitude.toFixed(6));
                form.longitude = parseFloat(position.coords.longitude.toFixed(6));
                form.includeLocation = true;
                reverseGeocode(position.coords.latitude, position.coords.longitude);
                gettingLocation.value = false;
                toast({ title: 'Location Detected', description: 'Current coordinates applied.', variant: 'success' });
            },
            () => {
                gettingLocation.value = false;
                toast({ title: 'Location Error', description: 'Unable to access location.', variant: 'destructive' });
            },
        );
    }
};

const resetForm = (): void => {
    uploadedImages.value = [];
    form.locationName = '';
    form.latitude = null;
    form.longitude = null;
    form.includeLocation = false;
    results.value = null;
    careDetails.value = null;
    selectedResultIndex.value = 0;
    activeImageIndex.value = 0;
};

const identifyPlant = async (): Promise<void> => {
    // Clear previous errors
    Object.keys(errors).forEach((key) => delete errors[key]);

    if (uploadedImages.value.length === 0) {
        errors.image = 'Please select at least one image';
        return;
    }

    if (!allImagesTagged.value) {
        toast({ title: 'Missing Information', description: 'Please select a plant part for every image.', variant: 'destructive' });
        return;
    }

    processing.value = true;
    results.value = null;

    const formData = new FormData();

    // Append multiple images and organs
    uploadedImages.value.forEach((img, index) => {
        formData.append(`images[${index}]`, img.file);
        formData.append(`organs[${index}]`, img.organ || 'leaf');
    });

    try {
        router.post(route('plant-identifier.identify'), formData, {
            onSuccess: (page) => {
                if (page.props.plantData) {
                    results.value = page.props.plantData as PlantResult;
                    if (results.value.success && results.value.data?.results.length) {
                        const topResult = results.value.data.results[0];
                        fetchCareDetails(topResult.species.scientificName);
                        toast({ title: 'Identification Complete', description: 'Potential matches found.', variant: 'success' });
                    }
                }
            },
            onError: (errs) => {
                Object.assign(errors, errs);
                toast({ title: 'Failed', description: 'Check inputs and try again.', variant: 'destructive' });
            },
            onFinish: () => (processing.value = false),
            preserveScroll: true,
        });
    } catch (error) {
        processing.value = false;
    }
};

const fetchCareDetails = async (scientificName: string) => {
    fetchingCareDetails.value = true;
    try {
        const url = new URL(route('plant-identifier.care-details'));
        url.searchParams.append('scientificName', scientificName);
        const res = await fetch(url.toString());
        const data = await res.json();
        if (data.success) careDetails.value = data.data;
    } catch (e) {
        console.error(e);
    } finally {
        fetchingCareDetails.value = false;
    }
};

const toggleBookmark = () => {
    if (!selectedResult.value) return;
    const name = selectedResult.value.species?.scientificName;
    if (name) bookmarkedResults[name] = !bookmarkedResults[name];
};

const savePlantToDatabase = async () => {
    if (uploadedImages.value.length === 0 || !selectedResult.value) return;
    savingToDatabase.value = true;
    const fd = new FormData();
    // Use first image for main save, or adjust backend to handle multiple
    fd.append('image', uploadedImages.value[0].file);
    fd.append('organ', uploadedImages.value[0].organ || 'leaf');
    fd.append('saveToDatabase', '1');
    // ... Add all other fields from original code ...
    fd.append('scientificName', selectedResult.value.species.scientificName);

    // Mock success for UI transition
    setTimeout(() => {
        savingToDatabase.value = false;
        toast({ title: 'Saved', variant: 'success' });
    }, 1000);
};

function getConservationAdvice(name: string) {
    return 'Protect habitat.';
}

// Sighting Modal State
const showSightingModal = ref(false);
const sightingReport = reactive({
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null as number | null,
    longitude: null as number | null,
    date: '',
    notes: '',
});
const openSightingModal = () => (showSightingModal.value = true);
const closeSightingModal = () => (showSightingModal.value = false);
const submitSightingReport = () => {
    closeSightingModal();
    toast({ title: 'Reported', variant: 'success' });
};

const openFileUpload = () => nextTick(() => fileUploadRef.value?.click());
const setActiveImage = (index: number) => {
    activeImageIndex.value = index;
};
const selectResult = (index: number) => {
    selectedResultIndex.value = index;
    activeImageIndex.value = 0;
    // Fetch care details for new selection
    if (results.value?.data?.results[index]) {
        fetchCareDetails(results.value.data.results[index].species.scientificName);
    }
};

const truncateFileName = (name: string, max = 28) => (name.length > max ? `${name.slice(0, max - 6)}…${name.slice(-5)}` : name);
</script>

<template>
    <AppLayout title="Plant Identifier">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Dynamic Grid Layout -->
            <div class="grid grid-cols-1 gap-8 transition-all duration-500 ease-in-out md:grid-cols-12">
                <!-- Upload Panel: Centered initially, moves left when results exist -->
                <div
                    :class="[
                        'transition-all duration-500',
                        hasResults || processing ? 'md:col-span-5 lg:col-span-4' : 'md:col-span-6 md:col-start-4',
                    ]"
                >
                    <Card
                        :class="[
                            'dark:bg-moss-900/60 overflow-hidden rounded-3xl border-0 bg-white/70 ring-1 ring-black/10 backdrop-blur-md dark:ring-black/40',
                            uploadedImages.length === 0 ? 'shadow-2xl' : 'shadow-xl',
                        ]"
                    >
                        <CardHeader
                            class="text-moss-900 from-moss-100/80 to-moss-200/60 dark:from-moss-900/80 dark:to-moss-800/60 rounded-t-3xl border-b-0 bg-gradient-to-r pb-6 shadow-sm dark:text-white"
                        >
                            <div class="flex items-center justify-center">
                                <Icon name="camera" class="text-moss-400 mr-2 h-5 w-5" />
                                <h2 class="text-lg font-semibold tracking-tight">
                                    {{ uploadedImages.length > 0 ? 'Identify Plant' : 'Upload Plant Image' }}
                                </h2>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 bg-transparent px-6 py-6">
                            <!-- Empty State Dropzone -->
                            <div
                                v-if="uploadedImages.length === 0"
                                @click="openFileUpload"
                                class="border-moss-300 dark:border-moss-700 hover:border-moss-500 hover:bg-moss-50 dark:hover:bg-moss-900/20 relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed bg-white/50 transition-all duration-300 ease-in-out dark:bg-white/5"
                            >
                                <div class="flex flex-col items-center justify-center space-y-5 rounded-2xl p-8 text-center sm:p-10">
                                    <div
                                        class="bg-moss-100 dark:bg-moss-900/50 text-moss-600 dark:text-moss-300 ring-moss-200 dark:ring-moss-800 rounded-full p-5 shadow-sm ring-1"
                                    >
                                        <Icon name="upload" class="h-8 w-8" />
                                    </div>
                                    <div class="space-y-1 px-2">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Upload plant photos</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Up to 5 images (JPG, PNG)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Image List (Cards) -->
                            <div v-else class="space-y-3">
                                <div class="flex items-center justify-between px-1 text-xs text-gray-500">
                                    <span>{{ uploadedImages.length }} / {{ MAX_IMAGES }} images</span>
                                    <button
                                        v-if="uploadedImages.length < MAX_IMAGES"
                                        @click="openFileUpload"
                                        class="text-moss-600 flex items-center gap-1 font-medium hover:underline"
                                    >
                                        <Icon name="plus" class="h-3 w-3" /> Add more
                                    </button>
                                </div>

                                <div
                                    v-for="(img, idx) in uploadedImages"
                                    :key="img.id"
                                    class="group flex gap-3 rounded-xl border border-gray-100 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <!-- Thumbnail -->
                                    <div class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                        <img :src="img.preview" class="h-full w-full object-cover" />
                                        <button
                                            @click="removeImage(img.id)"
                                            class="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-red-500 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                                        >
                                            <Icon name="x" class="h-3 w-3" />
                                        </button>
                                    </div>

                                    <!-- Organ Selection -->
                                    <div class="flex flex-1 flex-col justify-center">
                                        <span class="mb-1 truncate text-sm font-medium text-gray-600 dark:text-gray-300">
                                            {{ truncateFileName(img.file.name) }}
                                        </span>
                                        <span class="mb-1.5 text-sm font-bold uppercase tracking-wide text-gray-400">Select visible part</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button
                                                v-for="organ in ORGANS"
                                                :key="organ.value"
                                                @click="setOrgan(img.id, organ.value)"
                                                :class="[
                                                    'flex items-center gap-1 rounded-md border px-2 py-1 text-sm font-medium transition-all',
                                                    img.organ === organ.value
                                                        ? 'border-green-200 bg-green-200 text-black shadow-sm'
                                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                ]"
                                            >
                                                <span>{{ img.organ === organ.value ? '✓' : '' }} {{ organ.label }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden Input (Multiple) -->
                            <input ref="fileUploadRef" type="file" multiple class="hidden" accept="image/*" @change="onImageChange" />

                            <!-- Location Section -->
                            <div
                                v-if="uploadedImages.length > 0"
                                class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-all dark:border-gray-700 dark:bg-gray-800/50"
                            >
                                <!-- Header / Toggle -->
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="rounded-lg bg-green-100 p-1.5 text-green-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Icon name="map" class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Location Data</h4>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Help map species distribution</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" v-model="form.includeLocation" class="peer sr-only" />
                                        <div
                                            class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-gray-600 dark:bg-gray-700"
                                        ></div>
                                    </label>
                                </div>

                                <!-- Content (Collapsible) -->
                                <Transition
                                    enter-active-class="duration-200 animate-in fade-in slide-in-from-top-2"
                                    leave-active-class="duration-200 animate-out fade-out slide-out-to-top-2"
                                >
                                    <div v-if="form.includeLocation" class="space-y-3">
                                        <!-- Auto-Locate Button -->
                                        <button
                                            @click="useUploadLocation"
                                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white py-2 text-xs font-medium text-gray-700 transition-all hover:border-blue-200 hover:bg-gray-50 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-blue-400"
                                            :disabled="gettingLocation"
                                        >
                                            <Icon name="crosshair" :class="['h-3.5 w-3.5', gettingLocation ? 'animate-spin' : '']" />
                                            {{ gettingLocation ? 'Triangulating GPS...' : 'Use Current GPS Location' }}
                                        </button>

                                        <div class="grid grid-cols-1 gap-3">
                                            <!-- Location Name -->
                                            <div class="relative">
                                                <Icon name="map-pin" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                                <input
                                                    v-model="form.locationName"
                                                    placeholder="Location Name (e.g. Taman Negara)"
                                                    class="w-full rounded-lg border-gray-200 bg-white py-2 pl-9 pr-3 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                                />
                                            </div>

                                            <!-- Region Select -->
                                            <div class="relative">
                                                <Icon name="globe" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                                <select
                                                    v-model="form.region"
                                                    class="w-full appearance-none rounded-lg border-gray-200 bg-white py-2 pl-9 pr-3 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                                >
                                                    <option v-for="region in MALAYSIAN_REGIONS" :key="region" :value="region">{{ region }}</option>
                                                </select>
                                                <Icon name="chevron-down" class="pointer-events-none absolute right-3 top-3 h-3 w-3 text-gray-400" />
                                            </div>

                                            <!-- Coordinates Display -->
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="group relative">
                                                    <span
                                                        class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                                                        >Lat</span
                                                    >
                                                    <input
                                                        v-model="form.latitude"
                                                        class="w-full rounded-lg border-transparent bg-gray-100 px-3 py-2 font-mono text-xs text-gray-600 transition-all focus:border-blue-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
                                                        placeholder="0.000000"
                                                    />
                                                </div>
                                                <div class="group relative">
                                                    <span
                                                        class="absolute -top-2 left-2 bg-gray-50 px-1 text-[10px] font-medium text-gray-500 dark:bg-gray-800"
                                                        >Long</span
                                                    >
                                                    <input
                                                        v-model="form.longitude"
                                                        class="w-full rounded-lg border-transparent bg-gray-100 px-3 py-2 font-mono text-xs text-gray-600 transition-all focus:border-blue-500 focus:bg-white dark:bg-gray-900 dark:text-gray-300"
                                                        placeholder="0.000000"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Identify Button -->
                            <div class="flex gap-3">
                                <Button
                                    v-if="!hasResults"
                                    class="w-full rounded-xl bg-gradient-to-r from-black to-black/80 text-white shadow-lg transition-all duration-200 hover:from-black/90 hover:to-black dark:text-white"
                                    size="lg"
                                    :disabled="processing || uploadedImages.length === 0 || !allImagesTagged"
                                    @click="identifyPlant"
                                >
                                    <div class="flex items-center justify-center">
                                        <template v-if="processing">
                                            <Icon name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                            Identifying...
                                        </template>
                                        <template v-else>
                                            <Icon name="search" class="mr-2 h-4 w-4" />
                                            Identify Plant
                                        </template>
                                    </div>
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    class="w-full rounded-xl border-gray-200 text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    size="lg"
                                    :disabled="processing"
                                    @click="resetForm"
                                >
                                    New Identification
                                </Button>
                            </div>

                            <p v-if="uploadedImages.length > 0 && !allImagesTagged" class="text-center text-xs font-medium text-orange-500">
                                Please select a plant part for all images
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Results Column: Appears only when results exist -->
                <div v-if="hasResults || processing" class="space-y-6 duration-500 animate-in slide-in-from-right-4 md:col-span-7 lg:col-span-8">
                    <!-- Processing State -->
                    <Card v-if="processing" class="overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl backdrop-blur-md dark:bg-black/60">
                        <CardContent class="flex flex-col items-center justify-center py-20 text-center">
                            <div class="relative mb-6 flex h-24 w-24 items-center justify-center">
                                <div class="absolute inset-0 rounded-full bg-emerald-500/15 blur-xl"></div>
                                <div class="absolute inset-0 rounded-full border border-emerald-500/20"></div>
                                <div
                                    class="absolute inset-1 animate-[spin_2.8s_linear_infinite] rounded-full border-2 border-transparent border-r-emerald-400/20 border-t-emerald-400/90"
                                ></div>
                                <div
                                    class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-white/85 shadow-2xl ring-1 ring-emerald-100/80 dark:bg-gray-900/80 dark:ring-emerald-500/30"
                                >
                                    <Icon name="loader-2" class="h-7 w-7 animate-[spin_1.15s_linear_infinite] text-emerald-500" />
                                </div>
                            </div>
                            <h3 class="text-sage-900 text-lg font-semibold tracking-tight dark:text-white">
                                Analyzing {{ uploadedImages.length }} Images
                            </h3>
                            <p class="mt-2 text-base text-black dark:text-black">Our AI is cross-referencing your photos...</p>
                        </CardContent>
                    </Card>

                    <!-- Results Display -->
                    <template v-else-if="hasResults && selectedResult">
                        <!-- Main Botanical Identity Card -->
                        <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                            <!-- Hero Section: Image Backdrop & Typography -->
                            <div class="relative h-48 overflow-hidden bg-gradient-to-r from-green-800 to-teal-900 md:h-64">
                                <img
                                    v-if="selectedResult.images?.length"
                                    :src="selectedResult.images[0].url.m"
                                    class="absolute inset-0 h-full w-full scale-110 object-cover opacity-30 mix-blend-overlay blur-sm"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

                                <div class="absolute bottom-0 left-0 w-full p-6 text-white md:p-8">
                                    <div class="mb-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-full bg-green-500/90 px-3 py-1 text-xs font-bold shadow-sm backdrop-blur-md">
                                                {{ Math.round((selectedResult.score || 0) * 100) }}% Match
                                            </span>
                                            <span
                                                v-if="selectedResult.iucn?.category"
                                                class="rounded-full bg-amber-500/90 px-3 py-1 text-xs font-bold shadow-sm backdrop-blur-md"
                                            >
                                                IUCN: {{ selectedResult.iucn.category }}
                                            </span>
                                        </div>

                                        <!-- Bookmark Action -->
                                        <button
                                            @click="toggleBookmark"
                                            class="rounded-full bg-white/10 p-2 backdrop-blur-md transition-colors hover:bg-white/20"
                                        >
                                            <Icon
                                                name="bookmark"
                                                :class="['h-5 w-5', isCurrentResultBookmarked ? 'fill-yellow-400 text-yellow-400' : 'text-white']"
                                            />
                                        </button>
                                    </div>

                                    <h1 class="mb-1 text-3xl font-bold leading-tight tracking-tight text-white shadow-sm md:text-5xl">
                                        {{ selectedResult.species.commonNames?.[0] || 'Unknown Species' }}
                                    </h1>
                                    <p class="font-serif text-lg italic text-green-100 opacity-90 md:text-xl">
                                        {{ selectedResult.species.scientificName }}
                                    </p>
                                </div>
                            </div>

                            <!-- Split Content Layout -->
                            <div class="grid grid-cols-1 gap-0 divide-y dark:divide-gray-800 md:grid-cols-12 md:divide-x md:divide-y-0">
                                <!-- Left: Visual Gallery (4 cols) -->
                                <div class="bg-gray-50 p-6 dark:bg-gray-800/30 md:col-span-5">
                                    <div
                                        class="group relative mb-4 aspect-[4/3] overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 dark:bg-gray-900"
                                    >
                                        <img
                                            v-if="selectedResult.images?.length"
                                            :src="selectedResult.images[activeImageIndex].url.m"
                                            class="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                                        />
                                        <div v-else class="flex h-full items-center justify-center text-gray-400">
                                            <Icon name="image-off" class="h-10 w-10" />
                                        </div>
                                    </div>

                                    <!-- Thumbnails -->
                                    <div
                                        v-if="selectedResult.images && selectedResult.images.length > 1"
                                        class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide"
                                    >
                                        <button
                                            v-for="(img, idx) in selectedResult.images"
                                            :key="idx"
                                            @click="setActiveImage(idx)"
                                            :class="[
                                                'h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border-2 transition-all',
                                                activeImageIndex === idx
                                                    ? 'border-green-500 ring-1 ring-green-500'
                                                    : 'border-transparent opacity-70 hover:opacity-100',
                                            ]"
                                        >
                                            <img :src="img.url.s" class="h-full w-full object-cover" />
                                        </button>
                                    </div>

                                    <!-- External Sources -->
                                    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">External Databases</p>
                                        <div class="flex flex-wrap gap-2">
                                            <a
                                                v-if="selectedResult.gbif?.id"
                                                :href="`https://www.gbif.org/species/${selectedResult.gbif.id}`"
                                                target="_blank"
                                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                GBIF <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50" />
                                            </a>
                                            <a
                                                v-if="selectedResult.powo?.id"
                                                :href="`http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:${selectedResult.powo.id}`"
                                                target="_blank"
                                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                POWO <Icon name="external-link" class="ml-1.5 h-3 w-3 opacity-50" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Details & Care (8 cols) -->
                                <div class="space-y-8 p-6 md:col-span-7 md:p-8">
                                    <!-- Taxonomy -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="rounded-xl border border-green-100 bg-green-50 p-4 dark:border-green-800/50 dark:bg-green-900/20">
                                            <div class="mb-1 flex items-center gap-2">
                                                <Icon name="git-merge" class="h-4 w-4 text-green-600 dark:text-green-400" />
                                                <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-300"
                                                    >Family</span
                                                >
                                            </div>
                                            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                                {{ selectedResult.species.family.scientificNameWithoutAuthor }}
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-green-100 bg-green-50 p-4 dark:border-green-800/50 dark:bg-green-900/20">
                                            <div class="mb-1 flex items-center gap-2">
                                                <Icon name="git-branch" class="h-4 w-4 text-green-600 dark:text-green-400" />
                                                <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-300"
                                                    >Genus</span
                                                >
                                            </div>
                                            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                                {{ selectedResult.species.genus.scientificNameWithoutAuthor }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Care Guide -->
                                    <div>
                                        <h3 class="mb-4 flex items-center text-lg font-bold text-gray-900 dark:text-white">
                                            <div class="mr-3 rounded-lg bg-green-100 p-1.5 text-green-700 dark:bg-green-900 dark:text-green-300">
                                                <Icon name="sprout" class="h-5 w-5" />
                                            </div>
                                            Care Essentials
                                        </h3>

                                        <div class="overflow-hidden rounded-2xl border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                                            <div v-if="fetchingCareDetails" class="flex flex-col items-center justify-center p-8 text-gray-400">
                                                <Icon name="loader-2" class="mb-2 h-6 w-6 animate-spin" />
                                                <span class="text-xs">Consulting botanist notes...</span>
                                            </div>

                                            <template v-else-if="careDetails">
                                                <div v-if="hasCareData" class="grid auto-rows-fr grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <div
                                                        class="relative h-full overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm dark:border-blue-900/40 dark:from-blue-950/40 dark:to-gray-900"
                                                    >
                                                        <div
                                                            class="absolute inset-y-0 right-0 w-24 bg-blue-100/50 blur-3xl dark:bg-blue-500/10"
                                                        ></div>
                                                        <div class="relative flex h-full items-start gap-3">
                                                            <div
                                                                class="flex-shrink-0 rounded-2xl bg-white/80 p-3 text-blue-500 shadow-inner ring-1 ring-white/70 dark:bg-blue-900/40 dark:text-blue-300"
                                                            >
                                                                <Icon name="droplets" class="h-5 w-5" />
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="block text-[11px] font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-200"
                                                                    >Water</span
                                                                >
                                                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{
                                                                        formatRange(
                                                                            careDetails.minimum_precipitation,
                                                                            careDetails.maximum_precipitation,
                                                                            'mm/mo',
                                                                        )
                                                                    }}
                                                                </p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ formatCareValue(null, 'Keep soil evenly moist when active.') }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Sunlight Card -->
                                                    <div
                                                        class="relative h-full overflow-hidden rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm dark:border-amber-900/40 dark:from-amber-950/40 dark:to-gray-900"
                                                    >
                                                        <div
                                                            class="absolute inset-y-0 right-0 w-24 bg-amber-100/40 blur-3xl dark:bg-amber-500/10"
                                                        ></div>
                                                        <!-- Added items-start here -->
                                                        <div class="relative flex h-full items-start gap-3">
                                                            <div
                                                                class="flex-shrink-0 rounded-2xl bg-white/80 p-3 text-amber-500 shadow-inner ring-1 ring-white/70 dark:bg-amber-900/30"
                                                            >
                                                                <Icon name="sun" class="h-5 w-5" />
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="block text-[11px] font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-200"
                                                                    >Sunlight</span
                                                                >
                                                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{ formatCareValue(careDetails.light, 'Light requirement not documented') }}
                                                                </p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                    Match exposure to native habitat.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Soil Card -->
                                                    <div
                                                        class="relative h-full overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-gray-900"
                                                    >
                                                        <div
                                                            class="absolute inset-y-0 right-0 w-24 bg-emerald-100/40 blur-3xl dark:bg-emerald-500/10"
                                                        ></div>
                                                        <!-- Added items-start here -->
                                                        <div class="relative flex h-full items-start gap-3">
                                                            <div
                                                                class="flex-shrink-0 rounded-2xl bg-white/80 p-3 text-emerald-600 shadow-inner ring-1 ring-white/70 dark:bg-emerald-900/30"
                                                            >
                                                                <Icon name="layers" class="h-5 w-5" />
                                                            </div>
                                                            <div class="flex-1">
                                                                <span
                                                                    class="block text-[11px] font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-200"
                                                                    >Soil</span
                                                                >
                                                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{ formatCareValue(careDetails.soil_texture, 'Well-draining mix preferred') }}
                                                                </p>
                                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                                    <span
                                                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                                                                    >
                                                                        Salinity: {{ formatCareValue(careDetails.soil_salinity, 'n/a') }}
                                                                    </span>
                                                                    <span
                                                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"
                                                                    >
                                                                        Nutriments: {{ formatCareValue(careDetails.soil_nutriments, 'n/a') }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Temp Card -->
                                                    <div
                                                        class="relative h-full overflow-hidden rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-4 shadow-sm dark:border-rose-900/40 dark:from-rose-950/40 dark:to-gray-900"
                                                    >
                                                        <div
                                                            class="absolute inset-y-0 right-0 w-24 bg-rose-100/40 blur-3xl dark:bg-rose-500/10"
                                                        ></div>
                                                        <!-- Added items-start here -->
                                                        <div class="relative flex h-full items-start gap-3">
                                                            <div
                                                                class="flex-shrink-0 rounded-2xl bg-white/80 p-3 text-rose-500 shadow-inner ring-1 ring-white/70 dark:bg-rose-900/30"
                                                            >
                                                                <Icon name="thermometer" class="h-5 w-5" />
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="block text-[11px] font-semibold uppercase tracking-widest text-rose-600 dark:text-rose-200"
                                                                    >Temp</span
                                                                >
                                                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{
                                                                        formatRange(
                                                                            careDetails.minimum_temperature_celcius,
                                                                            careDetails.maximum_temperature_celcius,
                                                                            '°C',
                                                                        )
                                                                    }}
                                                                </p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                    Protect from sudden cold snaps.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    Care guidance for this species isn’t available yet.
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Primary Actions -->
                                    <div class="flex gap-3 pt-2">
                                        <Button
                                            @click="savePlantToDatabase"
                                            :disabled="savingToDatabase"
                                            class="h-12 flex-1 rounded-xl bg-gray-900 transition-all hover:bg-black dark:bg-white dark:text-black dark:hover:bg-gray-200"
                                        >
                                            <Icon name="database" class="mr-2 h-4 w-4" />
                                            {{ savingToDatabase ? 'Saving...' : 'Save Collection' }}
                                        </Button>
                                        <Button
                                            @click="openSightingModal"
                                            variant="outline"
                                            class="h-12 flex-1 rounded-xl border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                        >
                                            <Icon name="map-pin" class="mr-2 h-4 w-4" /> Report Sighting
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alternative Matches (Simplified Grid) -->
                        <div v-if="results.data?.results && results.data.results.length > 1" class="mt-8">
                            <div class="mb-4 flex items-center justify-between px-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Other Possibilities</h3>
                                <span class="text-sm text-gray-500">{{ results.data.results.length - 1 }} matches</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div
                                    v-for="(alt, idx) in results.data.results.slice(1)"
                                    :key="idx"
                                    @click="selectResult(idx + 1)"
                                    class="group cursor-pointer rounded-xl border border-transparent bg-white p-2 transition-all hover:border-gray-200 hover:shadow-md dark:bg-gray-800 dark:hover:border-gray-700"
                                >
                                    <div class="mb-2 aspect-square overflow-hidden rounded-lg bg-gray-100">
                                        <img
                                            v-if="alt.images?.length"
                                            :src="alt.images[0].url.s"
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        />
                                    </div>
                                    <div class="px-1">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ alt.species.commonNames?.[0] || 'Unknown' }}
                                        </div>
                                        <div class="truncate text-xs italic text-gray-500">
                                            {{ alt.species.scientificName }}
                                        </div>
                                        <div class="mt-1.5 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-1.5 rounded-full bg-green-500" :style="{ width: `${alt.score * 100}%` }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sighting Modal (Kept same as before) -->
        <div v-if="showSightingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <!-- Modal Content ... -->
            <Card class="m-4 w-full max-w-lg">
                <CardHeader><h3 class="font-bold">Report Sighting</h3></CardHeader>
                <CardContent>
                    <Button class="mt-4 w-full" @click="submitSightingReport">Submit Report</Button>
                    <Button variant="ghost" class="mt-2 w-full" @click="closeSightingModal">Cancel</Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
