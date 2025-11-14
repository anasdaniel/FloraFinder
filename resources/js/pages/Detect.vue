<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import exifr from 'exifr';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

// Define types
interface FormData {
    image: File | null;
    organ: string; // Single selection
    locationName: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    includeLocation: boolean;
    saveToDatabase: boolean;
}

interface PlantResult {
    success: boolean;
    message?: string;
    error?: string;
    savedToDatabase?: boolean;
    savedImage?: {
        path: string;
        url: string;
        filename: string;
        mime_type: string;
        size: number;
        organ: string;
        location_name?: string;
        region?: string;
        latitude?: number;
        longitude?: number;
        uploaded_at: string;
    };
    data?: {
        query?: {
            project?: string;
            images?: string[];
            organs?: string[];
            includeRelatedImages?: boolean;
            noReject?: boolean;
            type?: string | null;
        };
        predictedOrgans?: Array<{
            image?: string;
            filename?: string;
            organ?: string;
            score?: number;
        }>;
        language?: string;
        preferedReferential?: string;
        bestMatch?: string;
        results: Array<{
            score: number;
            species: {
                scientificNameWithoutAuthor: string;
                scientificNameAuthorship: string;
                genus: {
                    scientificNameWithoutAuthor: string;
                    scientificNameAuthorship: string;
                    scientificName: string;
                };
                family: {
                    scientificNameWithoutAuthor: string;
                    scientificNameAuthorship: string;
                    scientificName: string;
                };
                commonNames: string[];
                scientificName: string;
            };
            images?: Array<{
                url: {
                    s: string;
                    m: string;
                    l: string;
                };
                organ?: string;
                author?: string;
                license?: string;
                citation?: string;
            }>;
            gbif?: {
                id: string;
            };
            powo?: {
                id: string;
            };
            iucn?: {
                id: string;
                category: string;
            };
        }>;
        version?: string;
        remainingIdentificationRequests?: number;
    };
}

interface Errors {
    image?: string;
    organ?: string;
    [key: string]: string | undefined;
}

// Props
const props = defineProps<{
    plantData?: PlantResult;
}>();

const { toast } = useToast();

// State
const form = reactive<FormData>({
    image: null,
    organ: 'leaf', // Set default organ to "leaf"
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null,
    longitude: null,
    includeLocation: false,
    saveToDatabase: false,
});

const imagePreview = ref<string | null>(null);
const processing = ref<boolean>(false);
const results = ref<PlantResult | null>(null);
const errors = reactive<Errors>({});
const activeImageIndex = ref<number>(0);
const selectedResultIndex = ref<number>(0);
const fileUploadRef = ref<HTMLInputElement | null>(null);
const extractingExif = ref<boolean>(false);
const savingToDatabase = ref<boolean>(false);
const bookmarkedResults = reactive<Record<string, boolean>>({});

// Add new state for care details
const careDetails = ref<any>(null);
const fetchingCareDetails = ref<boolean>(false);

// Initialize results from props if available
onMounted(() => {
    if (props.plantData && props.plantData.success) {
        results.value = props.plantData;
    }
});

// Computed property to determine if we have results to show
const hasResults = computed(() => {
    return results.value && results.value.success && results.value.data && results.value.data.results && results.value.data.results.length > 0;
});

// Computed property for the selected result
const selectedResult = computed(() => {
    if (!hasResults.value) return null;
    return results.value!.data!.results[selectedResultIndex.value];
});

const isCurrentResultBookmarked = computed(() => {
    if (!selectedResult.value) return false;
    const scientificName = selectedResult.value.species?.scientificName;
    if (!scientificName) return false;
    return Boolean(bookmarkedResults[scientificName]);
});

// Methods
const onImageChange = async (e: Event): Promise<void> => {
    const target = e.target as HTMLInputElement;
    if (!target.files || !target.files.length) return;

    const file = target.files[0];
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
    errors.image = undefined;

    // Extract EXIF data automatically
    await extractExifData(file);
};

// Extract EXIF geolocation data from image
const extractExifData = async (file: File): Promise<void> => {
    try {
        extractingExif.value = true;

        toast({
            title: 'Reading Image Data',
            description: 'Checking for location information in the photo...',
            variant: 'default',
        });

        // Parse EXIF data from the image
        const exifData = await exifr.parse(file, {
            gps: true,
            tiff: true,
            exif: true,
        });

        if (exifData && exifData.latitude && exifData.longitude) {
            // EXIF data found with GPS coordinates
            form.latitude = parseFloat(exifData.latitude.toFixed(6));
            form.longitude = parseFloat(exifData.longitude.toFixed(6));
            form.includeLocation = true;

            // Try to get location name using reverse geocoding
            await reverseGeocode(exifData.latitude, exifData.longitude);

            // Show success toast with additional info
            let additionalInfo = '';
            if (exifData.DateTimeOriginal) {
                const photoDate = new Date(exifData.DateTimeOriginal);
                additionalInfo = ` (Photo taken: ${photoDate.toLocaleDateString()})`;
            }

            toast({
                title: 'Location Found in Photo!',
                description: `GPS Coordinates: ${form.latitude}, ${form.longitude}${additionalInfo}`,
                variant: 'success',
            });

            // Log additional EXIF data for debugging
            if (exifData.Make && exifData.Model) {
                console.log(`Camera: ${exifData.Make} ${exifData.Model}`);
            }
            if (exifData.DateTimeOriginal) {
                console.log(`Photo taken: ${exifData.DateTimeOriginal}`);
            }
        } else {
            // No GPS data in EXIF
            toast({
                title: 'No Location Data',
                description: "This photo doesn't contain GPS information. You can add location manually or use current location.",
                variant: 'default',
            });
        }
    } catch (error) {
        console.error('Error extracting EXIF data:', error);
        // Silent fail - not all images have EXIF data
        toast({
            title: 'No Location Data',
            description: 'Unable to read location from the photo. You can add it manually.',
            variant: 'default',
        });
    } finally {
        extractingExif.value = false;
    }
};

const resetForm = (): void => {
    form.image = null;
    imagePreview.value = null;
    form.organ = 'leaf'; // Reset to default organ
    form.locationName = '';
    form.latitude = null;
    form.longitude = null;
    form.includeLocation = false;

    // Restore results from props if available
    if (props.plantData) {
        results.value = props.plantData;
    } else {
        results.value = null;
    }
};

// Function to use current location during upload
const gettingLocation = ref<boolean>(false);

const useUploadLocation = (): void => {
    if (navigator.geolocation) {
        gettingLocation.value = true;

        // Update button UI to show loading state
        toast({
            title: 'Getting Your Location',
            description: 'Please wait while we access your location...',
            variant: 'default',
        });

        navigator.geolocation.getCurrentPosition(
            (position) => {
                // Format coordinates to 6 decimal places for precision
                form.latitude = parseFloat(position.coords.latitude.toFixed(6));
                form.longitude = parseFloat(position.coords.longitude.toFixed(6));
                form.includeLocation = true;

                // Try to get location name based on coordinates using reverse geocoding
                reverseGeocode(position.coords.latitude, position.coords.longitude);

                // Show success toast
                toast({
                    title: 'Location Detected',
                    description: `Coordinates: ${form.latitude}, ${form.longitude}`,
                    variant: 'success',
                });
                gettingLocation.value = false;
            },
            (error) => {
                // Show error toast with more specific message
                let errorMessage = 'Unable to get your current location.';

                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Location permission denied. Please enable location services in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information is unavailable. Please try again.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out. Please try again.';
                        break;
                }

                toast({
                    title: 'Location Error',
                    description: errorMessage,
                    variant: 'destructive',
                });
                form.includeLocation = false;
                gettingLocation.value = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            },
        );
    } else {
        // Show browser not supported toast
        toast({
            title: 'Not Supported',
            description: "Your browser doesn't support geolocation.",
            variant: 'destructive',
        });
    }
};

const toggleBookmark = (): void => {
    if (!selectedResult.value) return;
    const scientificName = selectedResult.value.species?.scientificName;
    if (!scientificName) return;

    bookmarkedResults[scientificName] = !bookmarkedResults[scientificName];
};

// Attempt to get location name based on coordinates using reverse geocoding
const reverseGeocode = async (latitude: number, longitude: number): Promise<void> => {
    try {
        // This is a simple example using Nominatim OpenStreetMap service
        // In a production app, you might want to use a paid service like Google Maps API
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
        );

        if (response.ok) {
            const data = await response.json();
            if (data && data.display_name) {
                // Extract a simplified location name
                let locationName = data.name || '';

                // Set region based on available admin levels
                let region = '';
                if (data.address) {
                    region = data.address.state || data.address.province || data.address.city || 'Peninsular Malaysia';

                    // If we don't have a location name but have address components, create a name
                    if (!locationName) {
                        locationName = data.address.city || data.address.town || data.address.village || data.address.suburb || '';
                    }
                }

                // Set the form values if we found something useful
                if (locationName) {
                    form.locationName = locationName;
                }

                // Try to find the best match in malaysianRegions
                if (region && Array.isArray(malaysianRegions)) {
                    for (const r of malaysianRegions) {
                        if (region.toLowerCase().includes(r.toLowerCase())) {
                            form.region = r;
                            break;
                        }
                    }
                }

                // Show success toast for location name if found
                if (locationName) {
                    toast({
                        title: 'Location Name Found',
                        description: `Location identified as: ${locationName}`,
                        variant: 'success',
                    });
                }
            }
        }
    } catch (error) {
        console.error('Error during reverse geocoding:', error);
        // We don't show an error toast here as getting coordinates is already successful
    }
};

const openFileUpload = (): void => {
    // Use nextTick to ensure the DOM element is available
    nextTick(() => {
        if (fileUploadRef.value) {
            fileUploadRef.value.click();
        }
    });
};

const handleDragOver = (e: DragEvent): void => {
    if (e.target instanceof HTMLElement) {
        e.target.classList.add('ring-2', 'ring-green-400');
    }
};

const handleDragLeave = (e: DragEvent): void => {
    if (e.target instanceof HTMLElement) {
        e.target.classList.remove('ring-2', 'ring-green-400');
    }
};

const handleDrop = async (e: DragEvent): Promise<void> => {
    if (e.target instanceof HTMLElement) {
        e.target.classList.remove('ring-2', 'ring-green-400');
    }

    if (!processing.value && e.dataTransfer) {
        const files = e.dataTransfer.files;
        if (files && files.length) {
            form.image = files[0];
            imagePreview.value = URL.createObjectURL(files[0]);
            errors.image = undefined;

            // Extract EXIF data from dropped file
            await extractExifData(files[0]);
        }
    }
};

const identifyPlant = async (): Promise<void> => {
    // Clear previous errors
    Object.keys(errors).forEach((key) => delete errors[key]);

    // Validate
    if (!form.image) {
        errors.image = 'Please select an image';
        return;
    }

    processing.value = true;

    // Reset results before new identification
    results.value = null;

    // Create form data
    const formData = new FormData();
    formData.append('image', form.image);
    formData.append('organ', form.organ);

    // Send request
    try {
        router.post(route('plant-identifier.identify'), formData, {
            onSuccess: (page) => {
                // Access the plant data directly from page props
                if (page.props.plantData) {
                    results.value = page.props.plantData as PlantResult;

                    // If identification was successful, reset indices
                    if (results.value.success && results.value.data) {
                        selectedResultIndex.value = 0;
                        activeImageIndex.value = 0;

                        // If identification was successful, fetch care details
                        if (results.value.data.results.length > 0) {
                            const topResult = results.value.data.results[0];
                            fetchCareDetails(topResult.species.scientificName);
                        }

                        // Check if data was saved to database
                        if (results.value.savedToDatabase) {
                            toast({
                                title: 'Success!',
                                description: 'Plant identified and saved to database successfully.',
                                variant: 'success',
                            });
                        } else {
                            toast({
                                title: 'Plant Identified',
                                description: "We've found potential matches for your plant.",
                                variant: 'success',
                            });
                        }
                    }
                } else {
                    // Handle the case where no plant data was returned
                    results.value = {
                        success: false,
                        message: 'No plant data was returned from the server',
                    };
                }
            },
            onError: (validationErrors) => {
                Object.assign(errors, validationErrors);
                toast({
                    title: 'Identification Failed',
                    description: 'Please check your inputs and try again.',
                    variant: 'destructive',
                });
            },
            onFinish: () => {
                processing.value = false;
            },
            preserveScroll: true,
        });
    } catch (error: any) {
        results.value = {
            success: false,
            message: 'An unexpected error occurred',
            error: error.message,
        };
        processing.value = false;
        toast({
            title: 'Something went wrong',
            description: error.message || 'An unexpected error occurred.',
            variant: 'destructive',
        });
    }
};

const setActiveImage = (index: number): void => {
    activeImageIndex.value = index;
};

const selectResult = (index: number): void => {
    selectedResultIndex.value = index;
    activeImageIndex.value = 0;
};

// New function to fetch care details from Trefle API
const fetchCareDetails = async (scientificName: string): Promise<void> => {
    fetchingCareDetails.value = true;
    try {
        const url = new URL(route('plant-identifier.care-details'));

        const test = 'Sorbus aucuparia';

        url.searchParams.append('scientificName', test);

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        const responseText = await response.text();
        console.log('Raw response:', responseText);
        console.log('Response status:', response.status);

        if (!response.ok) {
            console.error('API request failed:', response.status, responseText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = JSON.parse(responseText);

        if (data.success) {
            careDetails.value = data.data;
        } else {
            console.log('API returned success=false:', data.message);
            careDetails.value = null;
        }
    } catch (error) {
        console.error('Error fetching care details:', error);
        careDetails.value = null;
    } finally {
        fetchingCareDetails.value = false;
    }
};

const organs = [
    { value: 'flower', label: 'Flower', icon: 'flower' },
    { value: 'leaf', label: 'Leaf', icon: 'leaf' },
    { value: 'fruit', label: 'Fruit', icon: 'apple' },
    { value: 'bark', label: 'Bark', icon: 'tree' },
    { value: 'habit', label: 'Whole Plant', icon: 'sprout' },
    { value: 'other', label: 'Other', icon: 'help-circle' },
];

// Dummy conservation guide function
function getConservationAdvice(scientificName: string): string {
    // You can expand this with more species or logic
    switch (scientificName.toLowerCase()) {
        case 'rosa canina':
            return 'Protect from overharvesting. Encourage growth by pruning and avoid use of pesticides. Support local pollinators.';
        case 'quercus robur':
            return 'Preserve mature trees, avoid soil compaction around roots, and support natural regeneration.';
        default:
            return 'Maintain natural habitat, avoid overharvesting, and support local biodiversity. Consult local experts for more details.';
    }
}

// Malaysia plant location info with mockup coordinates
interface LocationInfo {
    name: string;
    region: string;
    latitude: number;
    longitude: number;
    elevation: string;
    habitat: string;
}

// Sighting Report interface
interface SightingReport {
    locationName: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    date: string;
    notes: string;
    useCurrentLocation: boolean;
}

// Function to get mockup location for a plant in Malaysia
function getPlantLocationInfo(scientificName: string): LocationInfo {
    // Mockup locations for Malaysian plants
    const locations: Record<string, LocationInfo> = {
        'rafflesia arnoldii': {
            name: 'Gunung Gading National Park',
            region: 'Sarawak',
            latitude: 1.6833,
            longitude: 109.85,
            elevation: '200-900m',
            habitat: 'Primary rainforest',
        },
        'nepenthes rajah': {
            name: 'Mount Kinabalu',
            region: 'Sabah',
            latitude: 6.0753,
            longitude: 116.5582,
            elevation: '1500-2600m',
            habitat: 'Highland cloud forest',
        },
        'etlingera elatior': {
            name: 'Taman Negara',
            region: 'Pahang',
            latitude: 4.3833,
            longitude: 102.4,
            elevation: '80-400m',
            habitat: 'Tropical rainforest',
        },
        'shorea macrophylla': {
            name: 'Lambir Hills National Park',
            region: 'Sarawak',
            latitude: 4.2,
            longitude: 114.0333,
            elevation: '150-465m',
            habitat: 'Dipterocarp forest',
        },
    };

    // Default location (Taman Negara)
    const defaultLocation: LocationInfo = {
        name: 'Taman Negara',
        region: 'Peninsular Malaysia',
        latitude: 4.5167,
        longitude: 102.45,
        elevation: '100-700m',
        habitat: 'Tropical rainforest',
    };

    // Try to match the scientific name (case insensitive)
    const lowercaseName = scientificName.toLowerCase();
    for (const key in locations) {
        if (lowercaseName.includes(key)) {
            return locations[key];
        }
    }

    // If no specific match, return default location
    return defaultLocation;
}

// State for the sighting report modal
const showSightingModal = ref<boolean>(false);
const sightingReport = reactive<SightingReport>({
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null,
    longitude: null,
    date: new Date().toISOString().slice(0, 10),
    notes: '',
    useCurrentLocation: false,
});

// Malaysian regions for the dropdown
const malaysianRegions = [
    'Peninsular Malaysia',
    'Sabah',
    'Sarawak',
    'Labuan',
    'Johor',
    'Kedah',
    'Kelantan',
    'Melaka',
    'Negeri Sembilan',
    'Pahang',
    'Perak',
    'Perlis',
    'Pulau Pinang',
    'Selangor',
    'Terengganu',
];

// Function to open the sighting modal with pre-filled data if available
const openSightingModal = () => {
    // Pre-fill with current plant location data if available
    if (selectedResult.value?.species?.scientificNameWithoutAuthor) {
        const locationInfo = getPlantLocationInfo(selectedResult.value.species.scientificNameWithoutAuthor);
        sightingReport.locationName = locationInfo.name;
        sightingReport.region = locationInfo.region;
        sightingReport.latitude = locationInfo.latitude;
        sightingReport.longitude = locationInfo.longitude;
    }

    showSightingModal.value = true;
};

// Function to close the sighting modal
const closeSightingModal = () => {
    showSightingModal.value = false;
};

// Function to use current location
const useCurrentLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                sightingReport.latitude = position.coords.latitude;
                sightingReport.longitude = position.coords.longitude;
                sightingReport.useCurrentLocation = true;

                // Show success toast
                toast({
                    title: 'Location Detected',
                    description: 'Your current coordinates have been added.',
                    variant: 'success',
                });
            },
            () => {
                // Show error toast
                toast({
                    title: 'Location Error',
                    description: 'Unable to get your current location.',
                    variant: 'destructive',
                });
                sightingReport.useCurrentLocation = false;
            },
        );
    } else {
        // Show browser not supported toast
        toast({
            title: 'Not Supported',
            description: "Your browser doesn't support geolocation.",
            variant: 'destructive',
        });
        sightingReport.useCurrentLocation = false;
    }
};

// Function to submit the sighting report
const submitSightingReport = () => {
    // Here you would typically send the data to your backend
    // For this mockup, we'll just show a success toast

    toast({
        title: 'Sighting Reported',
        description: 'Thank you for contributing to our plant database!',
        variant: 'success',
    });

    // Close the modal
    closeSightingModal();

    // Reset the form for next use
    sightingReport.locationName = '';
    sightingReport.latitude = null;
    sightingReport.longitude = null;
    sightingReport.notes = '';
    sightingReport.useCurrentLocation = false;
};

// Function to save the selected plant to database
const savePlantToDatabase = async () => {
    if (!form.image || !selectedResult.value) {
        toast({
            title: 'Cannot Save',
            description: 'No plant data available to save.',
            variant: 'destructive',
        });
        return;
    }

    savingToDatabase.value = true;

    try {
        // Create form data with the selected plant information
        const formData = new FormData();
        formData.append('image', form.image);
        formData.append('organ', form.organ);
        formData.append('saveToDatabase', '1');

        // Add plant identification data
        formData.append('scientificName', selectedResult.value.species.scientificName);
        formData.append('scientificNameWithoutAuthor', selectedResult.value.species.scientificNameWithoutAuthor);
        formData.append('commonName', selectedResult.value.species.commonNames?.[0] || '');
        formData.append('family', selectedResult.value.species.family.scientificName);
        formData.append('genus', selectedResult.value.species.genus.scientificName);
        formData.append('confidence', selectedResult.value.score.toString());

        // Add GBIF and POWO IDs if available
        if (selectedResult.value.gbif?.id) {
            formData.append('gbifId', selectedResult.value.gbif.id);
        }
        if (selectedResult.value.powo?.id) {
            formData.append('powoId', selectedResult.value.powo.id);
        }
        if (selectedResult.value.iucn?.category) {
            formData.append('iucnCategory', selectedResult.value.iucn.category);
        }

        // Include location data if available
        if (form.includeLocation) {
            if (form.locationName) {
                formData.append('locationName', form.locationName);
            }
            if (form.region) {
                formData.append('region', form.region);
            }
            if (form.latitude !== null) {
                formData.append('latitude', form.latitude.toString());
            }
            if (form.longitude !== null) {
                formData.append('longitude', form.longitude.toString());
            }
        }

        // Send to backend
        router.post(route('plant-identifier.save'), formData, {
            onSuccess: () => {
                if (results.value) {
                    results.value.savedToDatabase = true;
                }

                // Show success toast
                toast({
                    title: 'Plant Saved Successfully! 🌿',
                    description: 'The plant identification has been saved to the database.',
                    variant: 'success',
                });

                // Reset the form after successful save
                form.image = null;
                imagePreview.value = null;
                form.organ = 'leaf'; // Reset to default organ
                form.locationName = '';
                form.region = 'Peninsular Malaysia';
                form.latitude = null;
                form.longitude = null;
                form.includeLocation = false;
                form.saveToDatabase = false;

                // Clear the results to show a clean slate
                results.value = null;
                selectedResultIndex.value = 0;
                activeImageIndex.value = 0;

                // Clear any errors
                Object.keys(errors).forEach((key) => delete errors[key]);

                savingToDatabase.value = false;
            },
            onError: (errors) => {
                console.error('Save error:', errors);
                toast({
                    title: 'Save Failed',
                    description: 'Unable to save plant to database. Please try again.',
                    variant: 'destructive',
                });
                savingToDatabase.value = false;
            },
            onFinish: () => {
                savingToDatabase.value = false;
            },
            preserveScroll: true,
        });
    } catch (error: any) {
        console.error('Save error:', error);
        toast({
            title: 'Save Failed',
            description: error.message || 'An unexpected error occurred.',
            variant: 'destructive',
        });
        savingToDatabase.value = false;
    }
};
</script>

<template>
    <AppLayout title="Plant Identifier">
        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
                <!-- Left Column: Upload Panel -->
                <div class="md:col-span-5 lg:col-span-4">
                    <Card
                        class="dark:bg-moss-900/60 ring-moss-200 dark:ring-moss-800 overflow-hidden rounded-3xl border-0 bg-white/70 shadow-xl ring-1 backdrop-blur-md"
                    >
                        <CardHeader
                            class="text-moss-900 from-moss-100/80 to-moss-200/60 dark:from-moss-900/80 dark:to-moss-800/60 rounded-t-3xl border-b-0 bg-gradient-to-r pb-6 shadow-sm dark:text-white"
                        >
                            <div class="flex items-center justify-center">
                                <Icon name="camera" class="text-moss-400 mr-2 h-5 w-5" />
                                <h2 class="text-lg font-semibold tracking-tight">
                                    {{ imagePreview ? 'Identify Plant' : 'Upload Plant Image' }}
                                </h2>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-8 bg-transparent px-8 py-8">
                            <!-- Image Upload Area -->
                            <div>
                                <div
                                    class="border-moss-300 dark:border-moss-700 dark:bg-moss-900/40 hover:bg-moss-100/60 dark:hover:bg-moss-800/40 group relative flex min-h-[180px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed bg-white/60 shadow-inner backdrop-blur-md transition-all"
                                    :class="{ 'border-none': imagePreview }"
                                    @click="!processing && openFileUpload()"
                                    @dragover.prevent="handleDragOver"
                                    @dragleave.prevent="handleDragLeave"
                                    @drop.prevent="handleDrop"
                                >
                                    <div v-if="!imagePreview" class="flex flex-col items-center justify-center px-6 py-10 text-center">
                                        <p class="text-moss-700 dark:text-moss-200 mb-2 text-base font-medium tracking-tight">
                                            Drag & drop or click to upload
                                        </p>
                                        <p class="text-moss-500 dark:text-moss-400 text-xs">JPG, PNG or GIF (max 10MB)</p>
                                        <div class="text-moss-400 dark:text-moss-400 mt-4 flex items-center gap-2 text-xs">
                                            <Icon name="info" class="h-3 w-3" />
                                            <span>We'll automatically detect location from photo metadata</span>
                                        </div>
                                    </div>
                                    <div v-else class="relative aspect-[4/3] w-full overflow-hidden rounded-2xl shadow-lg">
                                        <img
                                            :src="imagePreview"
                                            alt="Plant preview"
                                            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                        />
                                        <!-- EXIF extraction indicator -->
                                        <div
                                            v-if="extractingExif"
                                            class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                                        >
                                            <div class="flex flex-col items-center gap-2 rounded-lg bg-white/90 p-4 dark:bg-black/90">
                                                <Icon name="loader-2" class="text-moss-600 h-6 w-6 animate-spin" />
                                                <span class="text-moss-700 dark:text-moss-300 text-sm font-medium">Reading location data...</span>
                                            </div>
                                        </div>
                                        <div
                                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/30 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="text-moss-800 rounded-full bg-white/90 shadow hover:bg-white"
                                                @click.stop="openFileUpload"
                                            >
                                                <Icon name="image" class="mr-1 h-4 w-4" />
                                                Change Image
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="rounded-full border-red-300 bg-white/90 text-red-700 shadow hover:bg-red-50"
                                                @click.stop="resetForm"
                                            >
                                                <Icon name="x" class="mr-1 h-4 w-4" />
                                                Clear Image
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <input ref="fileUploadRef" type="file" class="hidden" accept="image/*" @change="onImageChange" />
                                <p v-if="errors.image" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
                                    {{ errors.image }}
                                </p>
                                <!-- EXIF info badge -->
                                <div
                                    v-if="imagePreview && form.latitude && form.longitude"
                                    class="mt-3 flex items-center gap-2 rounded-lg bg-green-50 p-2 dark:bg-green-900/20"
                                >
                                    <Icon name="map-pin" class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    <span class="text-xs font-medium text-green-700 dark:text-green-300">
                                        Location detected from photo EXIF data
                                    </span>
                                </div>
                                <div v-if="imagePreview" class="mt-3 flex justify-center md:hidden">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="rounded-full border-red-300 bg-white/90 text-red-700 shadow hover:bg-red-50"
                                        @click="resetForm"
                                    >
                                        <Icon name="x" class="mr-1 h-4 w-4" />
                                        Clear Image
                                    </Button>
                                </div>
                            </div>
                            <!-- Plant Part Selector -->
                            <div>
                                <h3 class="text-sage-700 dark:text-sage-200 mb-3 text-sm font-semibold tracking-tight">Select Plant Part</h3>
                                <div class="grid grid-cols-3 gap-3" @click.stop>
                                    <div
                                        v-for="organ in organs"
                                        :key="organ.value"
                                        class="group relative cursor-pointer"
                                        @click.stop="!processing && (form.organ = organ.value)"
                                        :aria-pressed="form.organ === organ.value"
                                        role="button"
                                        tabindex="0"
                                        @keydown.enter.space="!processing && (form.organ = organ.value)"
                                    >
                                        <div
                                            class="flex flex-col items-center justify-center rounded-xl border p-3 shadow-sm transition-all duration-200"
                                            :class="
                                                form.organ === organ.value
                                                    ? 'border-moss-600 bg-moss-50/90 dark:border-moss-400 dark:bg-moss-900/60 ring-moss-400 scale-105 shadow-lg ring-2'
                                                    : 'border-sage-200 hover:border-moss-300 hover:bg-moss-50/60 dark:border-sage-700 dark:bg-sage-900/40 dark:hover:border-moss-500 dark:hover:bg-moss-900/30 bg-white/70'
                                            "
                                        >
                                            <span :class="form.organ === organ.value ? 'text-3xl' : 'text-2xl opacity-80'">
                                                {{
                                                    organ.value === 'flower'
                                                        ? '🌸'
                                                        : organ.value === 'leaf'
                                                          ? '🍃'
                                                          : organ.value === 'fruit'
                                                            ? '🍎'
                                                            : organ.value === 'bark'
                                                              ? '🌳'
                                                              : organ.value === 'habit'
                                                                ? '🌱'
                                                                : '❓'
                                                }}
                                            </span>
                                            <span
                                                class="mt-1 text-xs font-medium transition-colors duration-200"
                                                :class="
                                                    form.organ === organ.value
                                                        ? 'text-moss-900 dark:text-moss-200 font-semibold'
                                                        : 'text-sage-600 dark:text-sage-400'
                                                "
                                            >
                                                {{ organ.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div v-if="imagePreview" class="space-y-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-sage-700 dark:text-sage-200 text-sm font-semibold tracking-tight">
                                        Sighting Location (optional)
                                    </h3>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-moss-600 dark:text-moss-300 px-2 py-1 text-xs"
                                        @click="useUploadLocation"
                                        :disabled="gettingLocation"
                                    >
                                        <template v-if="gettingLocation">
                                            <Icon name="loader-2" class="mr-1 h-4 w-4 animate-spin" />
                                            Getting Location...
                                        </template>
                                        <template v-else> <Icon name="map-pin" class="mr-1 h-4 w-4" /> Use Current Location </template>
                                    </Button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-moss-700 dark:text-moss-200 mb-1 block text-xs font-medium">Location Name</label>
                                        <input
                                            v-model="form.locationName"
                                            type="text"
                                            placeholder="e.g. Taman Negara, Gunung Ledang"
                                            class="focus:ring-moss-400 dark:bg-moss-900/40 border-moss-300 dark:border-moss-700 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-moss-700 dark:text-moss-200 mb-1 block text-xs font-medium">Region</label>
                                        <select
                                            v-model="form.region"
                                            class="focus:ring-moss-400 dark:bg-moss-900/40 border-moss-300 dark:border-moss-700 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 dark:text-white"
                                        >
                                            <option v-for="region in malaysianRegions" :key="region" :value="region">
                                                {{ region }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-moss-700 dark:text-moss-200 mb-1 block text-xs font-medium">Latitude</label>
                                        <div class="relative">
                                            <input
                                                v-model.number="form.latitude"
                                                type="number"
                                                step="0.000001"
                                                placeholder="e.g. 4.2105"
                                                class="focus:ring-moss-400 dark:bg-moss-900/40 border-moss-300 dark:border-moss-700 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 dark:text-white"
                                            />
                                            <div v-if="gettingLocation" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <Icon name="loader-2" class="text-moss-400 h-4 w-4 animate-spin" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-moss-700 dark:text-moss-200 mb-1 block text-xs font-medium">Longitude</label>
                                        <div class="relative">
                                            <input
                                                v-model.number="form.longitude"
                                                type="number"
                                                step="0.000001"
                                                placeholder="e.g. 101.9758"
                                                class="focus:ring-moss-400 dark:bg-moss-900/40 border-moss-300 dark:border-moss-700 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 dark:text-white"
                                            />
                                            <div v-if="gettingLocation" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <Icon name="loader-2" class="text-moss-400 h-4 w-4 animate-spin" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prominent Get Location Button -->
                                <div class="mt-3 flex justify-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="w-full border-blue-200 bg-blue-50 px-4 py-2 text-blue-700 shadow-sm transition-colors hover:border-blue-300 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/30"
                                        @click="useUploadLocation"
                                        :disabled="gettingLocation"
                                    >
                                        <template v-if="gettingLocation">
                                            <Icon name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                            Getting Your Current Location...
                                        </template>
                                        <template v-else>
                                            <Icon name="navigation" class="mr-2 h-4 w-4" />
                                            Use My Current Location
                                        </template>
                                    </Button>
                                </div>

                                <div class="mt-3 flex items-center">
                                    <input
                                        id="includeLocation"
                                        type="checkbox"
                                        v-model="form.includeLocation"
                                        class="text-moss-600 border-moss-300 focus:ring-moss-500 dark:bg-moss-900 dark:border-moss-700 h-4 w-4 rounded"
                                    />
                                    <label for="includeLocation" class="text-moss-700 dark:text-moss-200 ml-2 text-xs"
                                        >Include location with identification</label
                                    >
                                </div>
                            </div>

                            <!-- Identify Button -->
                            <Button
                                class="w-full rounded-xl bg-gradient-to-r from-black to-black/80 text-white shadow-lg transition-all duration-200 hover:from-black/90 hover:to-black dark:text-white"
                                size="lg"
                                :disabled="processing || !imagePreview"
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
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Results -->
                <div class="space-y-6 md:col-span-7 lg:col-span-8">
                    <!-- Loading State -->
                    <Card v-if="processing" class="overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl backdrop-blur-md dark:bg-black/60">
                        <CardContent class="flex flex-col items-center justify-center py-20 text-center">
                            <div class="mb-4 animate-bounce rounded-full bg-gradient-to-br from-green-400/40 to-green-700/40 p-4 shadow-lg">
                                <Icon name="loader-2" class="h-10 w-10 animate-spin text-green-500" />
                            </div>
                            <h3 class="text-sage-900 text-lg font-semibold tracking-tight dark:text-white">Identifying Your Plant</h3>
                            <p class="mt-2 text-base text-black dark:text-black">
                                We're analyzing your image using AI to determine the plant species.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Error State -->
                    <Card
                        v-else-if="results && !results.success"
                        class="overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl backdrop-blur-md dark:bg-black/60"
                    >
                        <CardContent class="flex flex-col items-center justify-center py-20 text-center">
                            <div class="mb-4 rounded-full bg-red-900/30 p-4 shadow">
                                <Icon name="x-circle" class="h-10 w-10 text-red-500" />
                            </div>
                            <h3 class="text-lg font-semibold tracking-tight text-red-500">Identification Failed</h3>
                            <p class="mt-2 text-base text-gray-500 dark:text-gray-300">
                                {{ results.message || 'An error occurred while identifying your plant.' }}
                            </p>
                            <p v-if="results.error" class="mt-4 text-xs text-gray-600 dark:text-gray-400">
                                {{ results.error }}
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                class="mt-6 rounded-full border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                @click="resetForm"
                            >
                                <Icon name="refresh-cw" class="mr-2 h-4 w-4" /> Try Again
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Results Display -->
                    <template v-else-if="hasResults">
                        <!-- Primary Result -->
                        <Card
                            class="dark:bg-sage-900/60 ring-sage-200 dark:ring-sage-800 overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl ring-1 backdrop-blur-md"
                        >
                            <CardHeader
                                class="text-sage-900 from-sage-100/80 to-sage-200/60 dark:from-sage-900/80 dark:to-sage-800/60 rounded-t-3xl border-b-0 bg-gradient-to-r pb-6 shadow-sm dark:text-white"
                            >
                                <div class="flex items-center">
                                    <Icon name="check-circle" class="text-moss-400 mr-2 h-5 w-5" />
                                    <h2 class="text-lg font-semibold tracking-tight">Identification Result</h2>
                                    <div class="ml-auto flex items-center">
                                        <span
                                            v-if="selectedResult"
                                            class="rounded-full px-2 py-0.5 text-xs font-medium shadow"
                                            :class="
                                                (selectedResult?.score || 0) > 0.5
                                                    ? 'bg-green-600 text-white'
                                                    : (selectedResult?.score || 0) > 0.25
                                                      ? 'bg-yellow-400 text-yellow-900'
                                                      : 'bg-red-600 text-white'
                                            "
                                        >
                                            {{ Math.round((selectedResult?.score || 0) * 100) }}% confidence
                                        </span>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="px-8 py-8">
                                <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
                                    <!-- Image Gallery -->
                                    <div class="md:col-span-5">
                                        <div class="space-y-6">
                                            <div class="dark:bg-sage-900/40 overflow-hidden rounded-2xl bg-white/60 shadow-lg">
                                                <img
                                                    v-if="selectedResult && selectedResult.images?.length"
                                                    :src="selectedResult.images[activeImageIndex].url.m"
                                                    class="h-72 w-full rounded-2xl object-cover md:h-96"
                                                    alt="Plant image"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-72 w-full items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700 md:h-96"
                                                >
                                                    <Icon name="image-off" class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                                                </div>
                                            </div>

                                            <!-- Image Thumbnails -->
                                            <div
                                                v-if="selectedResult && selectedResult.images && selectedResult.images.length > 1"
                                                class="flex space-x-3 overflow-auto pb-1"
                                            >
                                                <button
                                                    v-for="(img, idx) in selectedResult.images"
                                                    :key="idx"
                                                    @click="setActiveImage(idx)"
                                                    class="flex-shrink-0 overflow-hidden rounded-lg border-2 shadow-sm transition-all hover:scale-105"
                                                    :class="
                                                        activeImageIndex === idx
                                                            ? 'border-green-500 shadow-md dark:border-green-400'
                                                            : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                                    "
                                                >
                                                    <img :src="img.url.s" class="h-16 w-16 rounded-lg object-cover" alt="Thumbnail" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Plant Information -->
                                    <div class="md:col-span-7">
                                        <div class="space-y-6">
                                            <div>
                                                <h3
                                                    class="text-sage-900 flex items-center gap-2 text-2xl font-bold leading-tight tracking-tight dark:text-white"
                                                >
                                                    {{ selectedResult?.species.commonNames?.[0] || 'Unknown' }}
                                                    <span class="text-sage-500 dark:text-sage-300 ml-2 text-lg font-normal italic"
                                                        >({{ selectedResult?.species.scientificName }})</span
                                                    >
                                                </h3>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span
                                                        v-if="selectedResult?.iucn?.category"
                                                        :class="[
                                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold shadow',
                                                            selectedResult.iucn.category === 'Endangered'
                                                                ? 'bg-red-100 text-red-700'
                                                                : 'bg-yellow-100 text-yellow-800',
                                                        ]"
                                                    >
                                                        <Icon name="alert-triangle" class="mr-1 h-3 w-3" />
                                                        {{ selectedResult.iucn.category }}
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 shadow"
                                                    >
                                                        <Icon name="leaf" class="mr-1 h-3 w-3" />
                                                        Habitat: Forest
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 shadow"
                                                    >
                                                        <Icon name="clock" class="mr-1 h-3 w-3" />
                                                        Lifespan: Perennial
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800 shadow"
                                                    >
                                                        <Icon name="watering-can" class="mr-1 h-3 w-3" />
                                                        Care: Moderate
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="bg-sage-50/80 dark:bg-sage-800/40 grid grid-cols-2 gap-6 rounded-2xl p-6 text-sm shadow">
                                                <div>
                                                    <p class="text-sage-500 dark:text-sage-400 text-xs font-semibold uppercase tracking-wide">
                                                        Family
                                                    </p>
                                                    <p class="text-sage-900 mt-1 font-medium dark:text-white">
                                                        {{ selectedResult?.species.family.scientificNameWithoutAuthor }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-sage-500 dark:text-sage-400 text-xs font-semibold uppercase tracking-wide">
                                                        Genus
                                                    </p>
                                                    <p class="text-sage-900 mt-1 font-medium dark:text-white">
                                                        {{ selectedResult?.species.genus.scientificNameWithoutAuthor }}
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- External Links -->
                                            <div class="flex flex-wrap gap-2">
                                                <a
                                                    v-if="selectedResult?.gbif?.id"
                                                    :href="`https://www.gbif.org/species/${selectedResult.gbif.id}`"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-sage-800 bg-sage-100 hover:bg-sage-200 dark:bg-sage-700 dark:text-sage-200 dark:hover:bg-sage-600 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium shadow"
                                                >
                                                    <Icon name="external-link" class="mr-1 h-3 w-3" /> GBIF Database
                                                </a>
                                                <a
                                                    v-if="selectedResult?.powo?.id"
                                                    :href="`http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:${selectedResult.powo.id}`"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-sage-800 bg-sage-100 hover:bg-sage-200 dark:bg-sage-700 dark:text-sage-200 dark:hover:bg-sage-600 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium shadow"
                                                >
                                                    <Icon name="external-link" class="mr-1 h-3 w-3" /> Kew Science
                                                </a>
                                            </div>

                                            <!-- Plant Location Section -->
                                            <div
                                                class="mt-4 rounded-2xl bg-gradient-to-br from-blue-100/80 to-blue-200/60 p-6 shadow dark:from-blue-900/30 dark:to-blue-800/30"
                                            >
                                                <h4 class="mb-2 flex items-center font-semibold tracking-tight text-blue-700 dark:text-blue-300">
                                                    <Icon name="map-pin" class="mr-2 h-4 w-4" />
                                                    Known Locations in Malaysia
                                                </h4>

                                                <!-- Map Placeholder -->
                                                <div
                                                    class="bg-sage-50 dark:bg-sage-800 relative mb-4 aspect-video overflow-hidden rounded-lg shadow-sm"
                                                >
                                                    <div
                                                        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/2923591/pexels-photo-2923591.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center opacity-40"
                                                    ></div>
                                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                        <div class="rounded-lg bg-white/90 p-2 shadow-lg dark:bg-black/60">
                                                            <div class="flex items-center gap-2">
                                                                <Icon name="map-pin" class="h-5 w-5 text-red-500" />
                                                                <span class="text-sage-900 text-sm font-medium dark:text-white">
                                                                    {{
                                                                        getPlantLocationInfo(
                                                                            selectedResult?.species.scientificNameWithoutAuthor || '',
                                                                        ).name
                                                                    }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="absolute bottom-2 right-2 rounded bg-white/80 p-1 text-xs dark:bg-black/60">
                                                        <Icon name="zoom-in" class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                                    </div>
                                                </div>

                                                <!-- Location Details -->
                                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                                    <div>
                                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Region:</span>
                                                        <p class="text-sage-800 dark:text-sage-200">
                                                            {{
                                                                getPlantLocationInfo(selectedResult?.species.scientificNameWithoutAuthor || '').region
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Elevation:</span>
                                                        <p class="text-sage-800 dark:text-sage-200">
                                                            {{
                                                                getPlantLocationInfo(selectedResult?.species.scientificNameWithoutAuthor || '')
                                                                    .elevation
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Coordinates:</span>
                                                        <p class="text-sage-800 dark:text-sage-200">
                                                            {{
                                                                getPlantLocationInfo(
                                                                    selectedResult?.species.scientificNameWithoutAuthor || '',
                                                                ).latitude.toFixed(4)
                                                            }},
                                                            {{
                                                                getPlantLocationInfo(
                                                                    selectedResult?.species.scientificNameWithoutAuthor || '',
                                                                ).longitude.toFixed(4)
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Habitat:</span>
                                                        <p class="text-sage-800 dark:text-sage-200">
                                                            {{
                                                                getPlantLocationInfo(selectedResult?.species.scientificNameWithoutAuthor || '')
                                                                    .habitat
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-3 flex justify-end">
                                                    <button
                                                        class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-900/50 dark:hover:bg-blue-800/50"
                                                    >
                                                        <Icon name="map" class="h-3 w-3" /> View Full Map
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Conservation Guide Section -->
                                            <div
                                                class="mt-4 rounded-2xl bg-gradient-to-br from-green-100/80 to-green-200/60 p-6 shadow dark:from-green-900/30 dark:to-green-800/30"
                                            >
                                                <h4 class="mb-2 font-semibold tracking-tight text-green-700 dark:text-green-300">Care Guide</h4>
                                                <template v-if="fetchingCareDetails">
                                                    <p class="text-sage-800 dark:text-sage-200 text-sm">Loading care details...</p>
                                                </template>
                                                <template v-else-if="careDetails">
                                                    <ul class="text-sage-800 dark:text-sage-200 space-y-1 text-sm">
                                                        <!--                                                        <li><strong>Watering:</strong> {{ careDetails.watering }}</li>-->
                                                        <!--                                                        <li><strong>Sunlight:</strong> {{ careDetails.sunlight }}</li>-->
                                                        <!--                                                        <li><strong>Soil:</strong> {{ careDetails.soil }}</li>-->
                                                        <!--                                                        <li><strong>Temperature:</strong> {{ careDetails.temperature }}</li>-->

                                                        <li><strong>Description:</strong> {{ careDetails.description }}</li>
                                                        <li><strong>Sowing:</strong> {{ careDetails.sowing }}</li>
                                                        <li><strong>Days to Harvest:</strong> {{ careDetails.days_to_harvest }}</li>
                                                        <li><strong>Row Spacing (cm):</strong> {{ careDetails.row_spacing_cm }}</li>
                                                        <li><strong>Spread (cm):</strong> {{ careDetails.spread_cm }}</li>
                                                        <li><strong>pH Maximum:</strong> {{ careDetails.ph_maximum }}</li>
                                                        <li><strong>pH Minimum:</strong> {{ careDetails.ph_minimum }}</li>
                                                        <li><strong>Light:</strong> {{ careDetails.light }}</li>
                                                        <li><strong>Atmospheric Humidity:</strong> {{ careDetails.atmospheric_humidity }}</li>
                                                        <li><strong>Growth Months:</strong> {{ careDetails.growth_months }}</li>
                                                        <li><strong>Bloom Months:</strong> {{ careDetails.bloom_months }}</li>
                                                        <li><strong>Fruit Months:</strong> {{ careDetails.fruit_months }}</li>
                                                        <li><strong>Minimum Precipitation:</strong> {{ careDetails.minimum_precipitation }}</li>
                                                        <li><strong>Maximum Precipitation:</strong> {{ careDetails.maximum_precipitation }}</li>
                                                        <li>
                                                            <strong>Minimum Temperature (°C):</strong> {{ careDetails.minimum_temperature_celcius }}
                                                        </li>
                                                        <li>
                                                            <strong>Maximum Temperature (°C):</strong> {{ careDetails.maximum_temperature_celcius }}
                                                        </li>
                                                        <li><strong>Soil Nutriments:</strong> {{ careDetails.soil_nutriments }}</li>
                                                        <li><strong>Soil Salinity:</strong> {{ careDetails.soil_salinity }}</li>
                                                        <li><strong>Soil Texture:</strong> {{ careDetails.soil_texture }}</li>
                                                        <li><strong>Soil Humidity:</strong> {{ careDetails.soil_humidity }}</li>
                                                    </ul>
                                                </template>
                                                <template v-else>
                                                    <p class="text-sage-800 dark:text-sage-200 text-sm">
                                                        {{ getConservationAdvice(selectedResult?.species.scientificNameWithoutAuthor || '') }}
                                                    </p>
                                                </template>
                                                <p class="text-sage-500 dark:text-sage-400 mt-2 text-xs">
                                                    This is a general guide. For critical conservation, consult local experts.
                                                </p>
                                            </div>
                                            <!-- Action Buttons -->
                                            <div class="mt-4 flex flex-wrap gap-3">
                                                <Button
                                                    variant="outline"
                                                    class="flex items-center gap-1 rounded-full shadow-sm transition-all hover:bg-green-50 dark:hover:bg-green-900/30"
                                                    @click="savePlantToDatabase"
                                                    :disabled="savingToDatabase || results?.savedToDatabase"
                                                >
                                                    <template v-if="savingToDatabase">
                                                        <Icon name="loader-2" class="h-4 w-4 animate-spin" />
                                                        Saving...
                                                    </template>
                                                    <template v-else-if="results?.savedToDatabase">
                                                        <Icon name="check" class="h-4 w-4" />
                                                        Saved to Database
                                                    </template>
                                                    <template v-else>
                                                        <Icon name="database" class="h-4 w-4" />
                                                        Save to Database
                                                    </template>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    :class="[
                                                        'flex items-center gap-1 rounded-full shadow-sm transition-all',
                                                        isCurrentResultBookmarked
                                                            ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/40 dark:text-yellow-200 dark:hover:bg-yellow-800/60'
                                                            : 'hover:bg-green-50 dark:hover:bg-green-900/30',
                                                    ]"
                                                    @click="toggleBookmark"
                                                >
                                                    <Icon
                                                        name="bookmark"
                                                        class="h-4 w-4"
                                                        :class="isCurrentResultBookmarked ? 'text-yellow-600 dark:text-yellow-200' : ''"
                                                    />
                                                    <span>{{ isCurrentResultBookmarked ? 'Bookmarked' : 'Bookmark' }}</span>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    class="flex items-center gap-1 rounded-full shadow-sm transition-all hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                                    @click="openSightingModal"
                                                >
                                                    <Icon name="map-pin" class="h-4 w-4" /> Report Sighting
                                                </Button>
                                            </div>
                                            <!-- Similar Plants Section -->
                                            <div
                                                class="mt-6 rounded-2xl bg-gradient-to-br from-amber-100/80 to-amber-200/60 p-6 shadow dark:from-amber-900/30 dark:to-amber-800/30"
                                            >
                                                <div class="mb-4 flex items-center justify-between">
                                                    <h4 class="flex items-center font-semibold tracking-tight text-amber-700 dark:text-amber-300">
                                                        <Icon name="flower" class="mr-2 h-4 w-4" />
                                                        Similar Plants & Species
                                                    </h4>
                                                    <button
                                                        class="flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:bg-amber-900/50 dark:hover:bg-amber-800/50"
                                                    >
                                                        <Icon name="plus" class="h-3 w-3" /> View More
                                                    </button>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                    <div
                                                        class="dark:bg-sage-900/60 overflow-hidden rounded-lg bg-white/90 shadow-sm transition-all hover:shadow-md"
                                                    >
                                                        <div class="relative h-16 overflow-hidden">
                                                            <img
                                                                src="https://images.pexels.com/photos/2183727/pexels-photo-2183727.jpeg?auto=compress&cs=tinysrgb&w=1260"
                                                                alt="Similar plant 1"
                                                                class="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                        <div class="p-2">
                                                            <p class="text-sage-900 truncate text-xs font-medium dark:text-white">Common Jasmine</p>
                                                            <p class="text-sage-500 dark:text-sage-400 truncate text-xs italic">
                                                                Jasminum officinale
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="dark:bg-sage-900/60 overflow-hidden rounded-lg bg-white/90 shadow-sm transition-all hover:shadow-md"
                                                    >
                                                        <div class="relative h-16 overflow-hidden">
                                                            <img
                                                                src="https://images.pexels.com/photos/1408221/pexels-photo-1408221.jpeg?auto=compress&cs=tinysrgb&w=1260"
                                                                alt="Similar plant 2"
                                                                class="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                        <div class="p-2">
                                                            <p class="text-sage-900 truncate text-xs font-medium dark:text-white">Malayan Orchid</p>
                                                            <p class="text-sage-500 dark:text-sage-400 truncate text-xs italic">
                                                                Phalaenopsis bellina
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="dark:bg-sage-900/60 overflow-hidden rounded-lg bg-white/90 shadow-sm transition-all hover:shadow-md"
                                                    >
                                                        <div class="relative h-16 overflow-hidden">
                                                            <img
                                                                src="https://images.pexels.com/photos/2315309/pexels-photo-2315309.jpeg?auto=compress&cs=tinysrgb&w=1260"
                                                                alt="Similar plant 3"
                                                                class="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                        <div class="p-2">
                                                            <p class="text-sage-900 truncate text-xs font-medium dark:text-white">Borneo Fern</p>
                                                            <p class="text-sage-500 dark:text-sage-400 truncate text-xs italic">Dipteris conjugata</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Alternative Results -->
                        <div v-if="results?.data?.results && results.data.results.length > 1">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Alternative Matches</h3>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ results.data.results.length - 1 }} other possibilities
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                <Card
                                    v-for="(result, idx) in results.data.results"
                                    :key="idx"
                                    :class="[
                                        'cursor-pointer overflow-hidden bg-white transition-shadow hover:shadow-md dark:bg-gray-800/60',
                                        selectedResultIndex === idx ? 'ring-2 ring-green-500 dark:ring-green-400' : '',
                                    ]"
                                    @click="selectResult(idx)"
                                >
                                    <div class="relative">
                                        <img
                                            v-if="result.images && result.images.length"
                                            :src="result.images[0].url.s"
                                            class="h-32 w-full rounded-t-lg object-cover"
                                            alt="Plant image"
                                        />
                                        <div v-else class="flex h-32 w-full items-center justify-center rounded-t-lg bg-gray-100 dark:bg-gray-700">
                                            <Icon name="image-off" class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span
                                            class="absolute right-2 top-2 rounded-full px-2 py-0.5 text-xs font-medium shadow"
                                            :class="
                                                (result.score || 0) > 0.5
                                                    ? 'bg-green-600 text-white'
                                                    : (result.score || 0) > 0.25
                                                      ? 'bg-yellow-400 text-yellow-900'
                                                      : 'bg-red-600 text-white'
                                            "
                                        >
                                            {{ Math.round((result.score || 0) * 100) }}%
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <div class="text-sage-900 truncate font-semibold dark:text-white">
                                            {{ result.species.commonNames?.[0] || 'Unknown' }}
                                        </div>
                                        <div class="text-sage-500 dark:text-sage-300 truncate text-xs italic">
                                            {{ result.species.scientificName }}
                                        </div>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </template>

                    <!-- Empty / Initial State -->
                    <Card
                        v-else-if="!processing"
                        class="overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl backdrop-blur-md dark:bg-gray-800/60"
                    >
                        <CardContent class="flex flex-col items-center justify-center py-20 text-center sm:py-32">
                            <span class="text-2xl">🔍</span>
                            <h3 class="text-sage-900 dark:text-sage-100 text-lg font-semibold tracking-tight">Ready to Identify Plants</h3>
                            <p class="text-sage-600 dark:text-sage-400 mt-2 max-w-md text-base">
                                Upload a clear image of a plant to get started. For best results, use a well-lit photo focusing on distinguishing
                                features like flowers or leaves.
                            </p>
                            <Button class="mt-8 rounded-xl shadow-lg" size="lg" @click="openFileUpload">
                                <Icon name="upload" class="mr-2 h-4 w-4" />
                                {{ imagePreview ? 'Identify Plant' : 'Upload Plant Image' }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Sighting Report Modal -->
        <div v-if="showSightingModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black/50 backdrop-blur-sm">
            <div class="relative mx-4 w-full max-w-lg overflow-hidden rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <!-- Modal Header -->
                <div class="mb-6 flex items-start justify-between">
                    <div>
                        <h3 class="text-sage-900 text-xl font-bold dark:text-white">Report Plant Sighting</h3>
                        <p class="text-sage-500 dark:text-sage-400 mt-1 text-sm">Share where you found this plant in Malaysia</p>
                    </div>
                    <button
                        @click="closeSightingModal"
                        class="text-sage-500 hover:bg-sage-100 dark:hover:bg-sage-800 dark:text-sage-400 -mr-2 rounded-full p-2"
                    >
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <!-- Plant Info -->
                <div class="bg-sage-50 dark:bg-sage-900/40 mb-6 flex items-center rounded-xl p-3">
                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg">
                        <img
                            v-if="selectedResult && selectedResult.images?.length"
                            :src="selectedResult.images[0].url.s"
                            :alt="selectedResult?.species.commonNames?.[0]"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="bg-sage-200 dark:bg-sage-800 flex h-full w-full items-center justify-center">
                            <Icon name="leaf" class="text-sage-500 h-6 w-6" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sage-900 font-medium dark:text-white">
                            {{ selectedResult?.species.commonNames?.[0] || 'Unknown Plant' }}
                        </p>
                        <p class="text-sage-500 dark:text-sage-400 text-xs italic">
                            {{ selectedResult?.species.scientificName }}
                        </p>
                    </div>
                </div>

                <!-- Form -->
                <div class="space-y-5">
                    <!-- Location Name -->
                    <div>
                        <label for="locationName" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Location Name </label>
                        <input
                            id="locationName"
                            v-model="sightingReport.locationName"
                            type="text"
                            class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                            placeholder="e.g., Taman Negara, Mount Kinabalu"
                        />
                    </div>

                    <!-- Region -->
                    <div>
                        <label for="region" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Region </label>
                        <select
                            id="region"
                            v-model="sightingReport.region"
                            class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                        >
                            <option v-for="region in malaysianRegions" :key="region" :value="region">
                                {{ region }}
                            </option>
                        </select>
                    </div>

                    <!-- Coordinates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="latitude" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Latitude </label>
                            <input
                                id="latitude"
                                v-model="sightingReport.latitude"
                                type="number"
                                step="0.0001"
                                class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                                placeholder="e.g., 4.5167"
                            />
                        </div>
                        <div>
                            <label for="longitude" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Longitude </label>
                            <input
                                id="longitude"
                                v-model="sightingReport.longitude"
                                type="number"
                                step="0.0001"
                                class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                                placeholder="e.g., 102.4500"
                            />
                        </div>
                    </div>

                    <!-- Use current location button -->
                    <div class="flex justify-end">
                        <button
                            @click="useCurrentLocation"
                            class="flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-900/50 dark:text-blue-300 dark:hover:bg-blue-800/50"
                        >
                            <Icon name="map-pin" class="h-3 w-3" /> Use My Current Location
                        </button>
                    </div>

                    <!-- Date of Sighting -->
                    <div>
                        <label for="sightingDate" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Date of Sighting </label>
                        <input
                            id="sightingDate"
                            v-model="sightingReport.date"
                            type="date"
                            class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="text-sage-700 dark:text-sage-300 mb-1 block text-sm font-medium"> Notes (Optional) </label>
                        <textarea
                            id="notes"
                            v-model="sightingReport.notes"
                            rows="3"
                            class="text-sage-900 border-sage-300 dark:border-sage-700 dark:bg-sage-900/40 w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white dark:focus:ring-blue-600"
                            placeholder="Add any details about the plant or location..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex items-center justify-end gap-3">
                    <Button
                        variant="outline"
                        class="text-sage-700 border-sage-300 dark:text-sage-300 dark:border-sage-700 px-6 py-2"
                        @click="closeSightingModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        class="bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600"
                        @click="submitSightingReport"
                    >
                        Submit Report
                    </Button>
                </div>

                <!-- Privacy Notice -->
                <p class="text-sage-500 dark:text-sage-400 mt-4 text-center text-xs">
                    <Icon name="shield" class="mr-1 inline-block h-3 w-3" />
                    Your sighting data helps track plant distribution and conservation efforts.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
