import { computed, nextTick, ref, watch, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Toast } from '@/composables/useToast';
import type {
  CareSource,
  ChatMessage,
  ImageUpload,
  PlantResult,
} from '@/types/plant-identifier';

interface UsePlantIdentificationOptions {
  initialPlantData?: PlantResult;
  toast: (toast: Omit<Toast, 'id'>) => string;
  uploadedImages: Ref<ImageUpload[]>;
  errors: Record<string, string | undefined>;
}

// Gemini config is handled on the server side via connectors

export function usePlantIdentification({
  initialPlantData,
  toast,
  uploadedImages,
  errors,
}: UsePlantIdentificationOptions) {
  const processing = ref(false);
  const results = ref<PlantResult | null>(initialPlantData ?? null);
  const activeImageIndex = ref(0);
  const selectedResultIndex = ref(0);
  const bookmarkedResults = ref<Record<string, boolean>>({});
  const careDetails = ref<Record<string, unknown> | null>(null);
  const careSource = ref<CareSource>(null);
  const preferredProvider = ref<'gemini' | 'trefle'>('gemini'); // Default to Gemini
  const fetchingCareDetails = ref(false);
  const plantDescription = ref('');
  const descriptionLoading = ref(false);
  const chatMessages = ref<ChatMessage[]>([]);
  const chatInput = ref('');
  const isChatLoading = ref(false);
  const chatEndRef = ref<HTMLElement | null>(null);

  const hasResults = computed(() => {
    return (
      results.value?.success &&
      results.value.data?.results &&
      results.value.data.results.length > 0
    );
  });

  const hasError = computed(() => Boolean(results.value && results.value.success === false));

  const noMatches = computed(() => {
    if (!results.value || !results.value.success) return false;
    return (results.value.data?.results ?? []).length === 0;
  });

  const errorMessage = computed(() => {
    if (!results.value) return '';
    return (
      results.value.message ||
      results.value.error ||
      "We couldn't identify that plant. Please try another photo."
    );
  });

  const selectedResult = computed(() => {
    if (!hasResults.value) return null;
    return results.value?.data?.results[selectedResultIndex.value] ?? null;
  });

  const isCurrentResultBookmarked = computed(() => {
    if (!selectedResult.value) return false;
    return Boolean(bookmarkedResults.value[selectedResult.value.species?.scientificName]);
  });

  const allImagesTagged = computed(() => {
    return (
      uploadedImages.value.length > 0 &&
      uploadedImages.value.every((img) => img.organ !== null)
    );
  });

  const isKnownValue = (value: unknown): boolean => {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string')
      return value.trim().length > 0 && value.toLowerCase() !== 'unknown';
    return true;
  };

  const formatCareValue = (
    value: string | number | null | undefined,
    fallback = 'Not available',
  ) => (isKnownValue(value) ? value : fallback) as string;

  const formatRange = (
    min?: number | string | null,
    max?: number | string | null,
    unit = '',
  ): string => {
    const suffix = unit ? ` ${unit}` : '';
    if (!isKnownValue(min) && !isKnownValue(max)) return 'N/A';
    if (!isKnownValue(min)) return `${max}${suffix}`;
    if (!isKnownValue(max)) return `${min}${suffix}`;
    return `${min} – ${max}${suffix}`;
  };

  const hasCareData = computed(() => {
    const details = careDetails.value;
    if (!details) return false;

    // Check for Gemini text-based care fields
    const geminiFields = ['watering_guide', 'sunlight_guide', 'soil_guide', 'temperature_guide', 'care_summary', 'description', 'care_tips'];
    const hasGeminiData = geminiFields.some((key) => {
      const val = details[key as keyof typeof details];
      return typeof val === 'string' && val.trim().length > 10;
    });
    if (hasGeminiData) return true;

    // Check for Trefle numeric care fields
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

  const isGeminiCare = computed(() => {
    return careSource.value === 'gemini' && Boolean(careDetails.value?.watering_guide);
  });

  const identifyPlant = async () => {
    processing.value = true;
    results.value = null;
    chatMessages.value = [];

    const formData = new FormData();
    uploadedImages.value.forEach((img, index) => {
      formData.append(`images[${index}]`, img.file);
      formData.append(`organs[${index}]`, img.organ || 'auto');
    });

    try {
      router.post(route('plant-identifier.identify'), formData, {
        onSuccess: (page) => {
          const plantData = page.props.plantData as PlantResult | undefined;
          if (!plantData) {
            results.value = null;
            toast({
              title: 'No Data Returned',
              description: 'The server did not return any plant information.',
              variant: 'destructive',
            });
            return;
          }

          results.value = plantData;

          // Update uploaded images with predicted organs from API
          if (plantData.predictedOrgans && plantData.predictedOrgans.length > 0) {
            plantData.predictedOrgans.forEach((predicted, index) => {
              if (uploadedImages.value[index]) {
                // Update organ with predicted value
                uploadedImages.value[index].organ = predicted.organ;
                // Store the score (already 0-1, convert to percentage when displaying)
                uploadedImages.value[index].organScore = predicted.score;
              }
            });
          }

          if (!results.value.success) {
            toast({
              title: 'Identification Failed',
              description: errorMessage.value,
              variant: 'destructive',
            });
            return;
          }

          const matches = results.value.data?.results ?? [];
          if (matches.length === 0) {
            toast({
              title: 'No Matches Found',
              description: 'Try a clearer image or choose a different plant part.',
              variant: 'default',
            });
            return;
          }

          selectedResultIndex.value = 0;
          activeImageIndex.value = 0;
          const topResult = matches[0];
          fetchCareDetails(topResult.species.scientificName);

          toast({
            title: 'Identification Complete',
            description: 'Potential matches found.',
            variant: 'success',
          });
        },
        onError: (errs) => {
          Object.assign(errors, errs);
          toast({
            title: 'Failed',
            description: 'Check inputs and try again.',
            variant: 'destructive',
          });
        },
        onFinish: () => {
          processing.value = false;
        },
        preserveScroll: true,
      });
    } catch (error) {
      console.error('Identification error:', error);
      processing.value = false;
    }
  };

  const fetchCareDetails = async (scientificName: string, forceRefresh = false) => {
    fetchingCareDetails.value = true;
    careSource.value = null;
    try {
      const url = new URL(route('plant-identifier.care-details'));
      url.searchParams.append('scientificName', scientificName);
      url.searchParams.append('provider', preferredProvider.value);
      if (forceRefresh) {
        url.searchParams.append('forceRefresh', '1');
      }
      const res = await fetch(url.toString());
      const data = await res.json();
      if (data.success) {
        careDetails.value = data.data;
        careSource.value = (data.source as CareSource) || 'trefle';
      } else {
        careDetails.value = null;
        careSource.value = 'none';
      }
    } catch (error) {
      console.error(error);
      careSource.value = 'none';
    } finally {
      fetchingCareDetails.value = false;
    }
  };

  const switchProvider = (provider: 'gemini' | 'trefle') => {
    if (provider !== preferredProvider.value) {
      preferredProvider.value = provider;
      // Re-fetch care details with new provider if we have a selected result
      if (selectedResult.value?.species?.scientificName) {
        fetchCareDetails(selectedResult.value.species.scientificName, true);
      }
    }
  };

  const fetchPlantDescription = async (scientificName: string) => {
    if (!scientificName) {
      plantDescription.value = '';
      return;
    }
    descriptionLoading.value = true;
    try {
      const url = new URL(route('plant-identifier.description'));
      url.searchParams.append('scientificName', scientificName);
      const res = await fetch(url.toString());
      const data = await res.json();
      plantDescription.value = data?.description || 'Description unavailable.';
    } catch (error) {
      console.error('Gemini description error:', error);
      plantDescription.value = 'Description unavailable.';
    } finally {
      descriptionLoading.value = false;
    }
  };

  const toggleBookmark = () => {
    if (!selectedResult.value) return;
    const name = selectedResult.value.species?.scientificName;
    if (name) {
      bookmarkedResults.value[name] = !bookmarkedResults.value[name];
    }
  };

  const callGeminiChat = async (plantName: string, history: ChatMessage[], message: string) => {
    // The backend constructs the system prompt for the Gemini model
    // Message history sent to backend will be serialized directly
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await fetch(route('plant-identifier.chat'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
        body: JSON.stringify({ plantName, message, history }),
      });
      const data = await response.json();
      return data.reply ?? "I'm having trouble checking my botanical reference books right now.";
    } catch (error) {
      console.error('Chat API Error:', error);
      return "I'm having trouble checking my botanical reference books right now.";
    }
  };

  const handleChatSend = async () => {
    if (!chatInput.value.trim() || !selectedResult.value) return;
    const userText = chatInput.value;
    const plantName =
      selectedResult.value.species.commonNames?.[0] || selectedResult.value.species.scientificName;

    chatMessages.value.push({ text: userText, role: 'user' });
    chatInput.value = '';
    isChatLoading.value = true;

    const responseText = await callGeminiChat(plantName, chatMessages.value, userText);

    chatMessages.value.push({ text: responseText, role: 'model' });
    isChatLoading.value = false;
  };

  const selectResult = (index: number) => {
    selectedResultIndex.value = index;
    activeImageIndex.value = 0;
    chatMessages.value = [];
    const match = results.value?.data?.results[index];
    if (match) {
      fetchCareDetails(match.species.scientificName);
    }
  };

  const setActiveImage = (index: number) => {
    activeImageIndex.value = index;
  };

  const resetResults = () => {
    results.value = null;
    careDetails.value = null;
    careSource.value = null;
    selectedResultIndex.value = 0;
    activeImageIndex.value = 0;
    chatMessages.value = [];
    plantDescription.value = '';
  };

  watch(chatMessages, async () => {
    await nextTick();
    chatEndRef.value?.scrollIntoView({ behavior: 'smooth' });
  });

  watch(selectedResult, (result) => {
    if (result?.species?.scientificName) {
      fetchPlantDescription(result.species.scientificName);
    } else {
      plantDescription.value = '';
    }
  }, { immediate: true });

  return {
    processing,
    results,
    activeImageIndex,
    selectedResultIndex,
    bookmarkedResults,
    careDetails,
    careSource,
    preferredProvider,
    fetchingCareDetails,
    plantDescription,
    descriptionLoading,
    chatMessages,
    chatInput,
    isChatLoading,
    chatEndRef,
    hasResults,
    hasError,
    noMatches,
    errorMessage,
    selectedResult,
    isCurrentResultBookmarked,
    allImagesTagged,
    hasCareData,
    isGeminiCare,
    formatCareValue,
    formatRange,
    identifyPlant,
    fetchCareDetails,
    switchProvider,
    toggleBookmark,
    handleChatSend,
    selectResult,
    setActiveImage,
    resetResults,
  };
}
