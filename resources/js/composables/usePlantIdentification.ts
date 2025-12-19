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
  const fetchingThreatStatus = ref(false);
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

          // Fetch threat status first, then care details (both fire async, care waits slightly)
          fetchThreatStatus(topResult.species.scientificName, 0);
          // Small delay to avoid simultaneous Gemini API calls (rate limiting)
          setTimeout(() => {
            fetchCareDetails(topResult.species.scientificName, false, {
              commonName: topResult.species.commonNames?.[0],
              family: topResult.species.family?.scientificNameWithoutAuthor,
              genus: topResult.species.genus?.scientificNameWithoutAuthor,
              gbifId: topResult.gbif?.id,
              powoId: topResult.powo?.id,
              iucnCategory: topResult.iucn?.category,
              imageUrl: topResult.images?.[0]?.url?.m || topResult.images?.[0]?.url?.o,
              referenceImages: topResult.images?.map(img => img.url?.m || img.url?.o).filter(Boolean),
            });
          }, 500);

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

  const fetchCareDetails = async (
    scientificName: string,
    forceRefresh = false,
    additionalData: any = {},
  ) => {
    fetchingCareDetails.value = true;
    careSource.value = null;
    try {
      const payload = {
        scientificName,
        provider: preferredProvider.value,
        ...additionalData,
        forceRefresh: forceRefresh ? 1 : 0,
      };

      const res = await fetch(route('plant-identifier.care-details'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
        body: JSON.stringify(payload),
      });

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

  const fetchThreatStatus = async (
    scientificName: string,
    resultIndex: number = 0,
  ) => {
    console.log(`[fetchThreatStatus] Called for: ${scientificName} at index ${resultIndex}`);

    if (!scientificName || fetchingThreatStatus.value) {
      console.log('[fetchThreatStatus] Skipping: no name or already fetching');
      return;
    }

    if (!results.value?.data?.results?.[resultIndex]) {
      console.log('[fetchThreatStatus] No result at index', resultIndex);
      return;
    }

    const targetResult = results.value.data.results[resultIndex];
    console.log('[fetchThreatStatus] Existing IUCN:', targetResult.iucn);

    // Skip if already have valid IUCN data (non-empty, non-null category)
    const existingCategory = targetResult.iucn?.category?.trim();
    if (existingCategory && existingCategory.length > 0) {
      console.log(`[fetchThreatStatus] Skipping: already have category '${existingCategory}'`);
      return;
    }

    console.log('[fetchThreatStatus] Fetching threat status from API...');
    fetchingThreatStatus.value = true;
    try {
      const url = new URL(route('plant-identifier.threat-status'));
      url.searchParams.append('scientificName', scientificName);
      const res = await fetch(url.toString());
      const data = await res.json();

      console.log('[fetchThreatStatus] API response:', data);

      if (data?.success && data?.category) {
        console.log(`[fetchThreatStatus] Updating with category: ${data.category}`);

        // Update the results ref to ensure reactivity
        if (results.value?.data?.results) {
          // Create a new results array with the updated result
          const updatedResults = [...results.value.data.results];
          updatedResults[resultIndex] = {
            ...updatedResults[resultIndex],
            iucn: { category: data.category }
          };

          // Update the results ref
          results.value = {
            ...results.value,
            data: {
              ...results.value.data,
              results: updatedResults
            }
          };

          console.log(`[fetchThreatStatus] ✓ Successfully updated IUCN category for ${scientificName}: ${data.category}`, {
            reasoning: data.reasoning,
            updatedResult: updatedResults[resultIndex]
          });
        } else {
          console.error('[fetchThreatStatus] No results.value.data.results available');
        }
      } else {
        console.log('[fetchThreatStatus] No valid category in response');
      }
    } catch (error) {
      console.error('[fetchThreatStatus] Threat status error:', error);
    } finally {
      fetchingThreatStatus.value = false;
    }
  };

  const switchProvider = (provider: 'gemini' | 'trefle') => {
    if (provider !== preferredProvider.value) {
      preferredProvider.value = provider;
      // Re-fetch care details with new provider if we have a selected result
      // Use cached data if available (false = don't force refresh)
      if (selectedResult.value?.species?.scientificName) {
        const result = selectedResult.value;
        fetchCareDetails(result.species.scientificName, false, {
          commonName: result.species.commonNames?.[0],
          family: result.species.family?.scientificNameWithoutAuthor,
          genus: result.species.genus?.scientificNameWithoutAuthor,
          gbifId: result.gbif?.id,
          powoId: result.powo?.id,
          iucnCategory: result.iucn?.category,
          imageUrl: result.images?.[0]?.url?.m || result.images?.[0]?.url?.o,
          referenceImages: result.images?.map(img => img.url?.m || img.url?.o).filter(Boolean),
        });
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
      // Get CSRF token from meta tag (preferred) or XSRF cookie (fallback)
      let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      if (!csrfToken) {
        // Fallback: get XSRF-TOKEN from cookies (Laravel sets this)
        const xsrfCookie = document.cookie
          .split('; ')
          .find(row => row.startsWith('XSRF-TOKEN='));
        if (xsrfCookie) {
          csrfToken = decodeURIComponent(xsrfCookie.split('=')[1]);
        }
      }

      if (!csrfToken) {
        console.error('No CSRF token found in meta tag or cookies');
        return "Session expired. Please refresh the page and try again.";
      }

      const response = await fetch(route('plant-identifier.chat'), {
        method: 'POST',
        credentials: 'include', // Include cookies for session
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-XSRF-TOKEN': csrfToken, // Laravel accepts both
          'Accept': 'application/json'
        },
        body: JSON.stringify({ plantName, message, history }),
      });

      // Check if response is OK (status 200-299)
      if (!response.ok) {
        console.error('Chat API returned error status:', response.status);
        const errorData = await response.json().catch(() => ({}));
        console.error('Error details:', errorData);

        if (response.status === 419) {
          return "Session expired. Please refresh the page and try again.";
        }
        return "I'm having trouble checking my botanical reference books right now.";
      }

      const data = await response.json();
      return data.reply ?? "I couldn't get a response. Please try again.";
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

    // Add user message to UI immediately
    chatMessages.value.push({ text: userText, role: 'user' });
    chatInput.value = '';
    isChatLoading.value = true;

    // Send history WITHOUT the just-added user message (API will add it)
    const historyWithoutLast = chatMessages.value.slice(0, -1);
    const responseText = await callGeminiChat(plantName, historyWithoutLast, userText);

    chatMessages.value.push({ text: responseText, role: 'model' });
    isChatLoading.value = false;
  };

  const selectResult = (index: number) => {
    selectedResultIndex.value = index;
    activeImageIndex.value = 0;
    chatMessages.value = [];
    const match = results.value?.data?.results[index];
    if (match) {
      fetchCareDetails(match.species.scientificName, false, {
        commonName: match.species.commonNames?.[0],
        family: match.species.family?.scientificNameWithoutAuthor,
        genus: match.species.genus?.scientificNameWithoutAuthor,
        gbifId: match.gbif?.id,
        powoId: match.powo?.id,
        iucnCategory: match.iucn?.category,
        imageUrl: match.images?.[0]?.url?.m || match.images?.[0]?.url?.o,
        referenceImages: match.images?.map(img => img.url?.m || img.url?.o).filter(Boolean),
      });
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
