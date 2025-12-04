import { computed, reactive, ref } from 'vue';
import exifr from 'exifr';
import type { Toast } from '@/composables/useToast';
import type {
  IdentificationForm,
  IdentifierErrors,
  ImageLocationData,
  ImageUpload,
  LocationGroup,
  LocationSource,
} from '@/types/plant-identifier';

type IdentifyCallback = () => void | Promise<void>;

interface UsePlantIdentifierUploadsOptions {
  toast: (toast: Omit<Toast, 'id'>) => string;
}

interface HandleIdentifyOptions {
  allImagesTagged: boolean;
  identify: IdentifyCallback;
}

export function usePlantIdentifierUploads({ toast }: UsePlantIdentifierUploadsOptions) {
  const MAX_IMAGES = 5;
  const ORGANS = [
    { value: 'auto', label: 'Auto (AI detect)', icon: 'sparkles' },
    { value: 'leaf', label: 'Leaf', icon: 'leaf' },
    { value: 'flower', label: 'Flower', icon: 'flower' },
    { value: 'fruit', label: 'Fruit', icon: 'apple' },
    { value: 'bark', label: 'Bark', icon: 'tree' },
  ];

  const MALAYSIAN_REGIONS = [
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

  const uploadedImages = ref<ImageUpload[]>([]);
  const form = reactive<IdentificationForm>({
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null,
    longitude: null,
    includeLocation: false,
    saveToDatabase: false,
  });

  const errors = reactive<IdentifierErrors>({});
  const fileUploadRef = ref<HTMLInputElement | null>(null);
  const extractingExif = ref(false);
  const gettingLocation = ref(false);
  const exifLocation = ref<LocationSource | null>(null);
  const gpsLocation = ref<LocationSource | null>(null);
  const showLocationConflictModal = ref(false);
  const locationConflictResolved = ref(false);
  const imageLocations = ref<Map<string, ImageLocationData>>(new Map());
  const locationGroups = ref<LocationGroup[]>([]);
  const showMultiLocationWarning = ref(false);

  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number) => {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  };

  const hasLocationConflict = computed(() => {
    if (!form.includeLocation) return false;
    if (!exifLocation.value || !gpsLocation.value) return false;
    const distance = calculateDistance(
      exifLocation.value.latitude,
      exifLocation.value.longitude,
      gpsLocation.value.latitude,
      gpsLocation.value.longitude,
    );
    return distance > 1;
  });

  const conflictDistance = computed(() => {
    if (!form.includeLocation) return 0;
    if (!exifLocation.value || !gpsLocation.value) return 0;
    return calculateDistance(
      exifLocation.value.latitude,
      exifLocation.value.longitude,
      gpsLocation.value.latitude,
      gpsLocation.value.longitude,
    );
  });

  const truncateFileName = (name: string, max = 28) =>
    name.length > max ? `${name.slice(0, max - 6)}…${name.slice(-5)}` : name;

  const openFileUpload = () => fileUploadRef.value?.click();

  const extractExifFromFile = async (file: File) => {
    try {
      const exifData = await exifr.parse(file, { gps: true, tiff: true, exif: true });
      if (exifData && exifData.latitude && exifData.longitude) {
        return {
          latitude: parseFloat(exifData.latitude.toFixed(6)),
          longitude: parseFloat(exifData.longitude.toFixed(6)),
          timestamp: exifData.DateTimeOriginal ? new Date(exifData.DateTimeOriginal) : undefined,
        };
      }
    } catch (error) {
      console.error('EXIF parse error for file:', file.name, error);
    }
    return null;
  };

  const reverseGeocodeAsync = async (latitude: number, longitude: number) => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
      );
      if (response.ok) {
        const data = await response.json();
        if (data && data.display_name) {
          const locationName = data.name || data.address?.city || data.address?.town || '';
          const regionCandidate = data.address?.state || data.address?.province || 'Peninsular Malaysia';
          const matchedRegion = MALAYSIAN_REGIONS.find((r) =>
            regionCandidate.toLowerCase().includes(r.toLowerCase()),
          );
          return {
            locationName,
            region: matchedRegion || 'Peninsular Malaysia',
          };
        }
      }
    } catch (error) {
      console.error('Geocode error', error);
    }
    return { locationName: '', region: 'Peninsular Malaysia' };
  };

  const reverseGeocode = async (latitude: number, longitude: number) => {
    const result = await reverseGeocodeAsync(latitude, longitude);
    if (result.locationName) form.locationName = result.locationName;
    form.region = result.region;
  };

  const extractExifDataFromImages = async () => {
    if (uploadedImages.value.length === 0) return;

    extractingExif.value = true;
    imageLocations.value.clear();

    toast({
      title: 'Searching for Location',
      description: `Checking ${uploadedImages.value.length} image${
        uploadedImages.value.length > 1 ? 's' : ''
      } for GPS data...`,
      variant: 'default',
    });

    let firstLocationFound = false;
    let imagesWithLocation = 0;

    for (const img of uploadedImages.value) {
      const location = await extractExifFromFile(img.file);
      imageLocations.value.set(img.id, {
        imageId: img.id,
        latitude: location?.latitude ?? null,
        longitude: location?.longitude ?? null,
      });

      if (location) {
        imagesWithLocation++;
        if (!firstLocationFound) {
          const locationInfo = await reverseGeocodeAsync(location.latitude, location.longitude);
          exifLocation.value = {
            latitude: location.latitude,
            longitude: location.longitude,
            locationName: locationInfo.locationName,
            region: locationInfo.region,
            source: 'exif',
            label: 'Photo Location (from image metadata)',
            timestamp: location.timestamp,
          };

          form.latitude = location.latitude;
          form.longitude = location.longitude;
          form.locationName = locationInfo.locationName;
          form.region = locationInfo.region;
          form.includeLocation = true;
          locationConflictResolved.value = false;
          firstLocationFound = true;
        }
      }
    }

    if (imagesWithLocation > 0) {
      toast({
        title: 'Location Found!',
        description:
          imagesWithLocation === uploadedImages.value.length
            ? `All ${imagesWithLocation} images have GPS data.`
            : `Found GPS data in ${imagesWithLocation} of ${uploadedImages.value.length} images.`,
        variant: 'success',
      });
    } else {
      toast({
        title: 'No Location Data',
        description: 'None of the uploaded photos contain GPS metadata. You can add location manually.',
        variant: 'default',
      });
    }

    extractingExif.value = false;
  };

  const onImageChange = async (e: Event) => {
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

    for (const file of filesToProcess) {
      uploadedImages.value.push({
        id: Math.random().toString(36).substring(2, 9),
        file,
        preview: URL.createObjectURL(file),
        organ: 'auto',
      });
    }

    if (!exifLocation.value) {
      await extractExifDataFromImages();
    }

    errors.image = undefined;
    target.value = '';
  };

  const removeImage = (id: string) => {
    uploadedImages.value = uploadedImages.value.filter((img) => img.id !== id);
    if (uploadedImages.value.length === 0) {
      resetUploads();
    } else if (exifLocation.value) {
      extractExifDataFromImages();
    }
  };

  const setOrgan = (id: string, organValue: string) => {
    const img = uploadedImages.value.find((i) => i.id === id);
    if (img) img.organ = organValue;
  };

  const useUploadLocation = () => {
    if (!navigator.geolocation) return;
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const lat = parseFloat(position.coords.latitude.toFixed(6));
        const lng = parseFloat(position.coords.longitude.toFixed(6));
        const locationInfo = await reverseGeocodeAsync(lat, lng);
        gpsLocation.value = {
          latitude: lat,
          longitude: lng,
          locationName: locationInfo.locationName,
          region: locationInfo.region,
          source: 'gps',
          label: 'Current Location (GPS)',
          timestamp: new Date(),
        };

        if (exifLocation.value && !locationConflictResolved.value) {
          const distance = calculateDistance(
            exifLocation.value.latitude,
            exifLocation.value.longitude,
            lat,
            lng,
          );

          if (distance > 1) {
            showLocationConflictModal.value = true;
            gettingLocation.value = false;
            return;
          }
        }

        form.latitude = lat;
        form.longitude = lng;
        form.locationName = locationInfo.locationName;
        form.region = locationInfo.region;
        form.includeLocation = true;

        gettingLocation.value = false;
        toast({
          title: 'Location Detected',
          description: 'Current GPS coordinates applied.',
          variant: 'success',
        });
      },
      () => {
        gettingLocation.value = false;
        toast({
          title: 'Location Error',
          description: 'Unable to access location.',
          variant: 'destructive',
        });
      },
    );
  };

  const selectLocation = (source: 'exif' | 'gps') => {
    const location = source === 'exif' ? exifLocation.value : gpsLocation.value;
    if (!location) return;
    form.latitude = location.latitude;
    form.longitude = location.longitude;
    form.locationName = location.locationName;
    form.region = location.region;
    form.includeLocation = true;
    locationConflictResolved.value = true;
    showLocationConflictModal.value = false;
    toast({
      title: 'Location Selected',
      description: `Using ${source === 'exif' ? 'photo metadata' : 'current GPS'} location.`,
      variant: 'success',
    });
  };

  const groupImagesByLocation = async () => {
    const groups: LocationGroup[] = [];
    const unknownGroup: LocationGroup = {
      latitude: null,
      longitude: null,
      locationName: 'Unknown Location',
      region: 'Unknown',
      images: [],
      isUnknown: true,
    };

    for (const img of uploadedImages.value) {
      const locData = imageLocations.value.get(img.id);
      if (!locData || locData.latitude === null || locData.longitude === null) {
        unknownGroup.images.push(img);
        continue;
      }

      let foundGroup = false;
      for (const group of groups) {
        if (group.latitude !== null && group.longitude !== null) {
          const distance = calculateDistance(
            group.latitude,
            group.longitude,
            locData.latitude,
            locData.longitude,
          );
          if (distance <= 1) {
            group.images.push(img);
            foundGroup = true;
            break;
          }
        }
      }

      if (!foundGroup) {
        const locationInfo = await reverseGeocodeAsync(locData.latitude, locData.longitude);
        groups.push({
          latitude: locData.latitude,
          longitude: locData.longitude,
          locationName:
            locationInfo.locationName || `${locData.latitude.toFixed(4)}, ${locData.longitude.toFixed(4)}`,
          region: locationInfo.region,
          images: [img],
          isUnknown: false,
        });
      }
    }

    if (unknownGroup.images.length > 0) {
      groups.push(unknownGroup);
    }

    return groups;
  };

  const checkLocationConflicts = async () => {
    if (!form.includeLocation) return false;
    const groups = await groupImagesByLocation();
    locationGroups.value = groups;
    const locationGroupCount = groups.filter((g) => !g.isUnknown).length;
    if (locationGroupCount > 1) {
      showMultiLocationWarning.value = true;
      return true;
    }
    return false;
  };

  const useLocationGroup = async (group: LocationGroup, onProceed?: IdentifyCallback) => {
    const imageIdsToKeep = new Set(group.images.map((img) => img.id));
    uploadedImages.value = uploadedImages.value.filter((img) => imageIdsToKeep.has(img.id));

    if (!group.isUnknown && group.latitude !== null && group.longitude !== null) {
      form.latitude = group.latitude;
      form.longitude = group.longitude;
      form.locationName = group.locationName;
      form.region = group.region;
      form.includeLocation = true;
      exifLocation.value = {
        latitude: group.latitude,
        longitude: group.longitude,
        locationName: group.locationName,
        region: group.region,
        source: 'exif',
        label: 'Photo Location',
      };
    }

    showMultiLocationWarning.value = false;

    toast({
      title: 'Images Updated',
      description: `Using ${group.images.length} image(s) from ${group.locationName || 'selected location'}.`,
      variant: 'success',
    });

    await onProceed?.();
  };

  const resetUploads = () => {
    uploadedImages.value = [];
    form.locationName = '';
    form.latitude = null;
    form.longitude = null;
    form.includeLocation = false;
    form.saveToDatabase = false;
    errors.image = undefined;
    Object.keys(errors).forEach((key) => delete errors[key]);
    exifLocation.value = null;
    gpsLocation.value = null;
    locationConflictResolved.value = false;
    showLocationConflictModal.value = false;
    showMultiLocationWarning.value = false;
    imageLocations.value.clear();
    locationGroups.value = [];
  };

  const handleIdentifyClick = async ({ allImagesTagged, identify }: HandleIdentifyOptions) => {
    Object.keys(errors).forEach((key) => delete errors[key]);

    if (uploadedImages.value.length === 0) {
      errors.image = 'Please select at least one image';
      return;
    }

    if (!allImagesTagged) {
      toast({
        title: 'Missing Information',
        description: 'Please select a plant part for every image.',
        variant: 'destructive',
      });
      return;
    }

    if (form.includeLocation && uploadedImages.value.length > 1 && imageLocations.value.size > 0) {
      const hasConflicts = await checkLocationConflicts();
      if (hasConflicts) {
        return;
      }
    }

    await identify();
  };

  return {
    MAX_IMAGES,
    ORGANS,
    MALAYSIAN_REGIONS,
    uploadedImages,
    form,
    errors,
    fileUploadRef,
    extractingExif,
    gettingLocation,
    exifLocation,
    gpsLocation,
    showLocationConflictModal,
    locationConflictResolved,
    imageLocations,
    locationGroups,
    showMultiLocationWarning,
    hasLocationConflict,
    conflictDistance,
    truncateFileName,
    openFileUpload,
    onImageChange,
    removeImage,
    setOrgan,
    extractExifDataFromImages,
    reverseGeocode,
    reverseGeocodeAsync,
    useUploadLocation,
    selectLocation,
    useLocationGroup,
    checkLocationConflicts,
    resetUploads,
    handleIdentifyClick,
  };
}
