import type { Ref } from 'vue';

export interface ImageUpload {
  id: string;
  file: File;
  preview: string;
  organ: string | null;
}

export interface PlantResult {
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
      images?: Array<{ url: { s: string; m: string; l: string } }>;
      gbif?: { id: string };
      powo?: { id: string };
      iucn?: { category: string };
    }>;
  };
}

export interface IdentificationForm {
  locationName: string;
  region: string;
  latitude: number | null;
  longitude: number | null;
  includeLocation: boolean;
  saveToDatabase: boolean;
}

export interface IdentifierErrors {
  image?: string;
  organ?: string;
  [key: string]: string | undefined;
}

export interface SaveOptions {
  saveToCollection: boolean;
  reportSighting: boolean;
  locationName: string;
  region: string;
  latitude: number | null;
  longitude: number | null;
  date: string;
  notes: string;
}

export interface LocationSource {
  latitude: number;
  longitude: number;
  locationName: string;
  region: string;
  source: 'exif' | 'gps';
  label: string;
  timestamp?: Date;
}

export interface ImageLocationData {
  imageId: string;
  latitude: number | null;
  longitude: number | null;
  locationName?: string;
}

export interface LocationGroup {
  latitude: number | null;
  longitude: number | null;
  locationName: string;
  region: string;
  images: ImageUpload[];
  isUnknown: boolean;
}

export type CareSource = 'trefle' | 'gemini' | 'none' | null;

export interface ChatMessage {
  role: 'user' | 'model';
  text: string;
}

export type RefLike<T> = Ref<T> | { value: T };
