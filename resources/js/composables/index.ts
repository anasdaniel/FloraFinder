import { ref, reactive } from 'vue';

// Composables index file
export { useToast } from './useToast';
export { useAppearance } from './useAppearance';
export { useInitials } from './useInitials';
export { useMap } from './useMap';

// Types for sighting data
interface Sighting {
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
      image: 'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=400&h=300&fit=crop',
      description: 'Carnivorous plant endemic to Borneo.'
    }
  ];

  const selectedSighting = ref<Sighting | null>(null);

  return {
    plantFamilies,
    conservationStatuses,
    malaysianRegions,
    filteredSightings: ref(mockSightings),
    paginatedSightings: ref(mockSightings),
    currentPage: ref(1),
    totalPages: ref(1),
    handlePageChange: (page: number) => console.log(`Page changed to ${page}`),
    selectSighting: (sighting: Sighting) => {
      selectedSighting.value = sighting;
      console.log('Selected sighting:', sighting);
    },
    selectedSighting,
    clearFilters: () => console.log('Clear filters'),
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

