import { reactive, ref, computed, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Toast } from '@/composables/useToast';
import type { IdentificationForm, ImageUpload } from '@/types/plant-identifier';

interface UsePlantSaveModalOptions {
  toast: (toast: Omit<Toast, 'id'>) => string;
  selectedResult: Readonly<Ref<any>>; // Selected plant result from composable
  uploadedImages: Readonly<Ref<ImageUpload[]>>;
  form: IdentificationForm;
  MALAYSIAN_REGIONS: string[];
}

// IUCN Category Helper
function getIucnWarning(category: string | undefined | null): { 
  shouldWarnSighting: boolean; 
  shouldDisableSighting: boolean;
  message: string;
} | null {
  if (!category) return null;
  
  const cat = category.toUpperCase();
  
  if (cat === 'EX') {
    return {
      shouldWarnSighting: true,
      shouldDisableSighting: true,
      message: 'This species is extinct. Public sightings should not be reported unless this is an extraordinary rediscovery.'
    };
  }
  
  if (cat === 'EW') {
    return {
      shouldWarnSighting: true,
      shouldDisableSighting: true,
      message: 'This species is extinct in the wild and only exists in cultivation. Do not report as a wild sighting.'
    };
  }
  
  if (['CR', 'EN', 'VU'].includes(cat)) {
    return {
      shouldWarnSighting: false,
      shouldDisableSighting: false,
      message: 'This species is threatened. Reporting this sighting is valuable for conservation!'
    };
  }
  
  return null;
}

export function usePlantSaveModal({
  toast,
  selectedResult,
  uploadedImages,
  form,
  MALAYSIAN_REGIONS,
}: UsePlantSaveModalOptions) {
  const showSaveModal = ref(false);
  const submittingSave = ref(false);
  const saveOptions = reactive({
    saveToCollection: true,
    reportSighting: true,
    locationName: '',
    region: 'Peninsular Malaysia',
    latitude: null as number | null,
    longitude: null as number | null,
    date: new Date().toISOString().split('T')[0],
    notes: '',
  });

  // Check IUCN category for warnings
  const iucnWarning = computed(() => {
    const category = selectedResult.value?.iucn?.category;
    return getIucnWarning(category);
  });

  const openSaveModal = () => {
    saveOptions.saveToCollection = true;
    
    // Auto-disable sighting for extinct species
    const warning = iucnWarning.value;
    if (warning?.shouldDisableSighting) {
      saveOptions.reportSighting = false;
    } else {
      saveOptions.reportSighting = true;
    }
    
    saveOptions.locationName = form.locationName || '';
    saveOptions.region = form.region || 'Peninsular Malaysia';
    saveOptions.latitude = form.latitude;
    saveOptions.longitude = form.longitude;
    saveOptions.date = new Date().toISOString().split('T')[0];
    saveOptions.notes = '';
    showSaveModal.value = true;
  };

  const closeSaveModal = () => {
    showSaveModal.value = false;
  };

  const getSaveLocation = () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        saveOptions.latitude = parseFloat(position.coords.latitude.toFixed(6));
        saveOptions.longitude = parseFloat(position.coords.longitude.toFixed(6));
        try {
          const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}&zoom=18&addressdetails=1`,
          );
          if (response.ok) {
            const data = await response.json();
            if (data?.display_name) {
              saveOptions.locationName = data.name || data.address?.city || data.address?.town || '';
              const region = data.address?.state || data.address?.province || '';
              const matchedRegion = MALAYSIAN_REGIONS.find((r) =>
                region.toLowerCase().includes(r.toLowerCase()),
              );
              if (matchedRegion) saveOptions.region = matchedRegion;
            }
          }
        } catch (error) {
          console.error('Geocode error:', error);
        }
        toast({
          title: 'Location Detected',
          description: 'GPS coordinates applied.',
          variant: 'success',
        });
      },
      () => {
        toast({
          title: 'Location Error',
          description: 'Unable to access location.',
          variant: 'destructive',
        });
      },
    );
  };

  const submitSaveAndReport = async () => {
    const match = selectedResult.value;
    if (!match || uploadedImages.value.length === 0) {
      toast({
        title: 'Missing Data',
        description: 'No plant identification or images available.',
        variant: 'destructive',
      });
      return;
    }

    if (!saveOptions.saveToCollection && !saveOptions.reportSighting) {
      toast({
        title: 'No Action Selected',
        description: 'Please select at least one option.',
        variant: 'destructive',
      });
      return;
    }

    submittingSave.value = true;

    const formData = new FormData();
    formData.append('scientific_name', match.species.scientificName);
    formData.append('common_name', match.species.commonNames?.[0] || '');
    formData.append('family', match.species.family?.scientificNameWithoutAuthor || '');
    formData.append('genus', match.species.genus?.scientificNameWithoutAuthor || '');
    formData.append('confidence', String(match.score || 0));
    formData.append('gbif_id', match.gbif?.id || '');
    formData.append('powo_id', match.powo?.id || '');
    formData.append('iucn_category', match.iucn?.category || '');

    formData.append('save_to_collection', saveOptions.saveToCollection ? '1' : '0');
    formData.append('report_sighting', saveOptions.reportSighting ? '1' : '0');

    if (saveOptions.reportSighting) {
      if (saveOptions.latitude !== null) {
        formData.append('latitude', saveOptions.latitude.toString());
      }
      if (saveOptions.longitude !== null) {
        formData.append('longitude', saveOptions.longitude.toString());
      }
      if (saveOptions.locationName) {
        formData.append('location_name', saveOptions.locationName);
      }
      if (saveOptions.region) {
        formData.append('region', saveOptions.region);
      }
      if (saveOptions.date) {
        formData.append('sighted_at', saveOptions.date);
      }
      if (saveOptions.notes) {
        formData.append('description', saveOptions.notes);
      }
    }

    uploadedImages.value.forEach((img, index) => {
      formData.append(`images[${index}]`, img.file);
      formData.append(`organs[${index}]`, img.organ || 'auto');
      // Include organ score as percentage (0-100)
      if (img.organScore !== undefined) {
        formData.append(`organ_scores[${index}]`, Math.round(img.organScore * 100).toString());
      }
    });

    try {
      router.post(route('sightings.store'), formData, {
        onSuccess: () => {
          closeSaveModal();
          const actions: string[] = [];
          if (saveOptions.saveToCollection) actions.push('saved to collection');
          if (saveOptions.reportSighting) actions.push('sighting reported');
          toast({
            title: 'Success!',
            description: `Plant ${actions.join(' and ')}.`,
            variant: 'success',
          });
        },
        onError: (errs) => {
          console.error('Submission errors:', errs);
          toast({
            title: 'Submission Failed',
            description: 'Please check your inputs and try again.',
            variant: 'destructive',
          });
        },
        onFinish: () => {
          submittingSave.value = false;
        },
        preserveScroll: true,
      });
    } catch (error) {
      submittingSave.value = false;
      toast({
        title: 'Error',
        description: 'An unexpected error occurred.',
        variant: 'destructive',
      });
    }
  };

  const resetSaveState = () => {
    showSaveModal.value = false;
    submittingSave.value = false;
    saveOptions.saveToCollection = true;
    saveOptions.reportSighting = true;
    saveOptions.locationName = '';
    saveOptions.region = 'Peninsular Malaysia';
    saveOptions.latitude = null;
    saveOptions.longitude = null;
    saveOptions.date = new Date().toISOString().split('T')[0];
    saveOptions.notes = '';
  };

  return {
    showSaveModal,
    submittingSave,
    saveOptions,
    iucnWarning,
    openSaveModal,
    closeSaveModal,
    getSaveLocation,
    submitSaveAndReport,
    resetSaveState,
  };
}
