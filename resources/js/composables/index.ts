import { ref, reactive, computed, watch } from 'vue';

// Composables index file
export { useToast } from './useToast';
export { useAppearance } from './useAppearance';
export { useInitials } from './useInitials';
export { useMap } from './useMap';

// Types for sighting data
export interface Sighting {
  id: number;
  commonName: string;
  scientificName: string;
  family: string;
  conservationStatus: string;
  location: string;
  latitude: number;
  longitude: number;
  date: string;
  image: string;
  description?: string;
}

interface ConservationStatus {
  value: string;
  label: string;
  color: string;
}

export function useFilters() {
  return reactive({
    family: '',
    conservationStatus: [] as string[],
    region: '',
    dateFrom: '',
    dateTo: '',
    search: ''
  });
}

export function useSightings(filters: any) {
  const plantFamilies = ['Rosaceae', 'Asteraceae', 'Fabaceae', 'Orchidaceae', 'Dipterocarpaceae'];

  const conservationStatuses: ConservationStatus[] = [
    { value: 'LC', label: 'Least Concern', color: 'bg-green-500' },
    { value: 'NT', label: 'Near Threatened', color: 'bg-yellow-500' },
    { value: 'VU', label: 'Vulnerable', color: 'bg-orange-500' },
    { value: 'EN', label: 'Endangered', color: 'bg-red-500' },
    { value: 'CR', label: 'Critically Endangered', color: 'bg-red-700' }
  ];

  const malaysianRegions = ['Peninsular Malaysia', 'Sabah', 'Sarawak', 'Johor', 'Selangor', 'Perak', 'Pahang'];

  // Mock sighting data
  const mockSightings: Sighting[] = [
    {
      id: 1,
      commonName: 'Rafflesia',
      scientificName: 'Rafflesia arnoldii',
      family: 'Rafflesiaceae',
      conservationStatus: 'EN',
      location: 'Taman Negara, Pahang',
      latitude: 4.5167,
      longitude: 102.4167,
      date: '2024-01-15',
      image: 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop',
      description: 'World\'s largest individual flower found in the rainforests of Malaysia.'
    },
    {
      id: 2,
      commonName: 'Pitcher Plant',
      scientificName: 'Nepenthes rajah',
      family: 'Nepenthaceae',
      conservationStatus: 'VU',
      location: 'Mount Kinabalu, Sabah',
      latitude: 6.0753,
      longitude: 116.5582,
      date: '2024-02-20',
        image: 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop',
      description: 'Carnivorous plant endemic to Borneo.'
    },
    {
      id: 3,
      commonName: 'Sea Hibiscus',
      scientificName: 'Hibiscus tiliaceus',
      family: 'Malvaceae',
      conservationStatus: 'LC',
      location: 'Penang National Park, Penang',
      latitude: 5.4164,
      longitude: 100.3327,
      date: '2024-03-10',
        image: 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop',
      description: 'Yellow hibiscus found along Malaysia\'s coastal areas.'
    },
    {
      id: 4,
      commonName: 'Wild Durian',
      scientificName: 'Durio zibethinus',
      family: 'Malvaceae',
      conservationStatus: 'NT',
      location: 'Cameron Highlands, Pahang',
      latitude: 4.4696,
      longitude: 101.3810,
      date: '2024-03-15',
      image: 'https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?w=400&h=300&fit=crop',
      description: 'Wild durian tree found in highland forests.'
    },
    {
      id: 5,
      commonName: 'Bird of Paradise',
      scientificName: 'Strelitzia reginae',
      family: 'Strelitziaceae',
      conservationStatus: 'LC',
      location: 'Kuala Lumpur Bird Park',
      latitude: 3.1478,
      longitude: 101.6953,
      date: '2024-03-22',
      image: 'https://images.unsplash.com/photo-1574684891174-df6b02ab38d7?w=400&h=300&fit=crop',
      description: 'Ornamental bird of paradise flower in urban garden.'
    },
    {
      id: 6,
      commonName: 'Malaysian Orchid',
      scientificName: 'Phalaenopsis violacea',
      family: 'Orchidaceae',
      conservationStatus: 'VU',
      location: 'Danum Valley, Sabah',
      latitude: 4.9669,
      longitude: 117.7957,
      date: '2024-04-05',
      image: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
      description: 'Rare orchid species endemic to Borneo rainforests.'
    },
    {
      id: 7,
      commonName: 'Strangler Fig',
      scientificName: 'Ficus benghalensis',
      family: 'Moraceae',
      conservationStatus: 'LC',
      location: 'Endau-Rompin National Park, Johor',
      latitude: 2.6174,
      longitude: 103.3901,
      date: '2024-04-12',
      image: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop',
      description: 'Large strangler fig tree in primary rainforest.'
    },
    {
      id: 8,
      commonName: 'Bornean Ironwood',
      scientificName: 'Eusideroxylon zwageri',
      family: 'Lauraceae',
      conservationStatus: 'CR',
      location: 'Maliau Basin, Sabah',
      latitude: 4.7394,
      longitude: 116.9739,
      date: '2024-04-18',
      image: 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=400&h=300&fit=crop',
      description: 'Critically endangered ironwood tree in pristine rainforest.'
    },
    {
      id: 9,
      commonName: 'Torch Ginger',
      scientificName: 'Etlingera elatior',
      family: 'Zingiberaceae',
      conservationStatus: 'LC',
      location: 'Fraser\'s Hill, Pahang',
      latitude: 3.7203,
      longitude: 101.7416,
      date: '2024-04-25',
        image: 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=400&h=300&fit=crop',
      description: 'Bright red torch ginger flower in highland forest.'
    },
    {
      id: 10,
      commonName: 'Moonbeam Begonia',
      scientificName: 'Begonia pavonina',
      family: 'Begoniaceae',
      conservationStatus: 'EN',
      location: 'Gunung Mulu National Park, Sarawak',
      latitude: 4.0483,
      longitude: 114.8100,
      date: '2024-05-02',
      image: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
      description: 'Iridescent blue begonia found in limestone caves.'
    }
  ];

  const selectedSighting = ref<Sighting | null>(null);

  // Filtering logic
  const filteredSightings = computed(() => {
    return mockSightings.filter((sighting) => {
      // Family filter
      if (filters.family && filters.family !== 'all' && sighting.family !== filters.family) {
        return false;
      }
      // Conservation Status filter (array)
      if (
        filters.conservationStatus &&
        filters.conservationStatus.length > 0 &&
        !filters.conservationStatus.includes(sighting.conservationStatus)
      ) {
        return false;
      }
      // Region filter
      if (filters.region && filters.region !== 'all' && sighting.location && !sighting.location.includes(filters.region)) {
        return false;
      }
      // Date range filter
      if (filters.dateFrom && sighting.date < filters.dateFrom) {
        return false;
      }
      if (filters.dateTo && sighting.date > filters.dateTo) {
        return false;
      }
      // Search filter (common or scientific name)
      if (filters.search) {
        const search = filters.search.toLowerCase();
        if (
          !sighting.commonName.toLowerCase().includes(search) &&
          !sighting.scientificName.toLowerCase().includes(search)
        ) {
          return false;
        }
      }
      return true;
    });
  });

  // Pagination logic
  const currentPage = ref(1);
  const pageSize = 9;
  const totalPages = computed(() => Math.max(1, Math.ceil(filteredSightings.value.length / pageSize)));
  const paginatedSightings = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredSightings.value.slice(start, start + pageSize);
  });

  // Watch filters and reset page
  watch(
    () => [filters.family, filters.conservationStatus.slice(), filters.region, filters.dateFrom, filters.dateTo, filters.search],
    () => {
      currentPage.value = 1;
    }
  );

  return {
    plantFamilies,
    conservationStatuses,
    malaysianRegions,
    filteredSightings,
    paginatedSightings,
    currentPage,
    totalPages,
    handlePageChange: (page: number) => {
      currentPage.value = page;
    },
    selectSighting: (sighting: Sighting) => {
      selectedSighting.value = sighting;
    },
    selectedSighting,
    clearFilters: () => {
      filters.family = '';
      filters.conservationStatus = [];
      filters.region = '';
      filters.dateFrom = '';
      filters.dateTo = '';
      filters.search = '';
    },
    getConservationStatusColor: (status: string) => {
      const found = conservationStatuses.find(s => s.value === status);
      return found?.color || 'bg-gray-500';
    },
    getConservationStatusLabel: (status: string) => {
      const found = conservationStatuses.find(s => s.value === status);
      return found?.label || status;
    }
  };
}
