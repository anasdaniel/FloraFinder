<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted, nextTick } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import type { Sighting } from "@/composables";

// Props
const props = defineProps<{
  sightings: Sighting[];
  highlightSighting?: Sighting | null;
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let markersLayer: L.LayerGroup | null = null;

// Conservation status color mapping
const getConservationStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    CR: "#dc2626", // Critically Endangered - vibrant red
    EN: "#ef4444", // Endangered - bright red
    VU: "#f97316", // Vulnerable - bright orange
    NT: "#eab308", // Near Threatened - bright yellow
    LC: "#22c55e", // Least Concern - bright green
    DD: "#6b7280", // Data Deficient - gray
    NE: "#3b82f6", // Not Evaluated - bright blue
    EW: "#374151", // Extinct in the Wild - dark gray
    EX: "#9ca3af", // Extinct - light gray
  };
  return colors[status] || "#3b82f6"; // Default blue
};

// Create simple marker based on conservation status
const createMarker = (lat: number, lng: number, status: string): L.CircleMarker => {
  const color = getConservationStatusColor(status);
  return L.circleMarker([lat, lng], {
    radius: 8,
    fillColor: color,
    color: "#ffffff",
    weight: 2,
    opacity: 1,
    fillOpacity: 1.0,
  });
};

// Initialize map
onMounted(async () => {
  await nextTick(); // Ensure DOM is fully rendered

  if (mapContainer.value) {
    // Create map instance
    map = L.map(mapContainer.value, {
      center: [4.2105, 101.9758], // Centered on Malaysia
      zoom: 7,
      zoomControl: true,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      boxZoom: true,
      keyboard: true,
      dragging: true,
      touchZoom: true,
    });

    // Add OpenStreetMap tiles
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
      detectRetina: true,
    }).addTo(map);

    // Initialize marker layer group
    markersLayer = L.layerGroup().addTo(map);

    // Force map to recalculate size
    setTimeout(() => {
      if (map) {
        map.invalidateSize();
        updateMarkers(props.sightings);
      }
    }, 100);

    // Close popup when clicking outside the map
    document.addEventListener("mousedown", handleDocumentClick);
  }
});

// Center map to fit all markers
const centerMap = () => {
  if (!map || !markersLayer) return;
  const allMarkers = markersLayer.getLayers();
  if (allMarkers.length > 0) {
    const bounds = L.latLngBounds([]);
    allMarkers.forEach((marker: any) => {
      if (marker.getLatLng) bounds.extend(marker.getLatLng());
    });
    if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [20, 20], maxZoom: 15 });
    }
  } else {
    map.setView([4.2105, 101.9758], 7, { animate: true });
  }
};

// Watch for changes in sightings data and update markers
watch(
  () => props.sightings,
  (newSightings) => {
    updateMarkers(newSightings);
  },
  { deep: true }
);

// Watch for highlightSighting and pan/zoom to it
watch(
  () => props.highlightSighting,
  (highlight) => {
    if (highlight && map) {
      const lat = Number(highlight.latitude);
      const lng = Number(highlight.longitude);
      if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
        map.setView([lat, lng], 15, { animate: true });
        // Optionally, add a temporary highlight marker or effect here
      }
    }
  }
);

// Function to update map markers
const updateMarkers = (sightings: Sighting[]) => {
  if (!map || !markersLayer) return;

  // Clear existing markers
  markersLayer.clearLayers();

  if (sightings.length === 0) return;

  const bounds = L.latLngBounds([]);
  let validSightings = 0;

  sightings.forEach((sighting) => {
    const lat = Number(sighting.latitude);
    const lng = Number(sighting.longitude);

    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
      const latLng: L.LatLngExpression = [lat, lng];
      const marker = createMarker(lat, lng, sighting.conservationStatus);

      // Simple popup with plant name and basic info
      marker.bindPopup(
        `<div class="text-sm">
           <strong>${sighting.commonName || "Unknown Plant"}</strong><br/>
           <em>${sighting.scientificName || ""}</em><br/>
           ${sighting.location ? `<div>${sighting.location}</div>` : ""}
           ${sighting.date ? `<div>${sighting.date}</div>` : ""}
        </div>`
      );

      markersLayer!.addLayer(marker);
      bounds.extend(latLng);
      validSightings++;
    }
  });

  // Fit map to show all markers if we have valid sightings
  if (validSightings > 0 && bounds.isValid()) {
    map.fitBounds(bounds, {
      padding: [20, 20],
      maxZoom: 15,
    });
  }
};

// Cleanup on unmount
onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
  if (markersLayer) {
    markersLayer.clearLayers();
    markersLayer = null;
  }
  document.removeEventListener("mousedown", handleDocumentClick);
});

// Close popup when clicking outside the map
function handleDocumentClick(e: MouseEvent) {
  if (!mapContainer.value) return;
  if (!map) return;
  const mapEl = mapContainer.value;
  if (!mapEl.contains(e.target as Node)) {
    map.closePopup();
  }
}

// Legend and sightings info toggle
const legendExpanded = ref(true);

const toggleLegend = () => {
  legendExpanded.value = !legendExpanded.value;
};
</script>

<template>
  <div class="relative">
    <div
      ref="mapContainer"
      class="w-full h-[600px] rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700"
    ></div>

    <!-- Map Controls Overlay -->
    <div class="absolute top-4 right-4 z-[1000] space-y-2">
      <!-- Legend -->
      <div
        class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-3 border border-gray-200 dark:border-gray-700"
      >
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
            Conservation Status
          </h4>
          <button
            @click="toggleLegend"
            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition"
            aria-label="Toggle Legend"
          >
            <svg
              v-if="legendExpanded"
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16m-7 6h7"
              />
            </svg>
          </button>
        </div>
        <div v-if="legendExpanded" class="space-y-1 text-xs">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #dc2626"></div>
            <span class="text-gray-700 dark:text-gray-300">Critically Endangered</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #ef4444"></div>
            <span class="text-gray-700 dark:text-gray-300">Endangered</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #f97316"></div>
            <span class="text-gray-700 dark:text-gray-300">Vulnerable</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #eab308"></div>
            <span class="text-gray-700 dark:text-gray-300">Near Threatened</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #22c55e"></div>
            <span class="text-gray-700 dark:text-gray-300">Least Concern</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #6b7280"></div>
            <span class="text-gray-700 dark:text-gray-300">Data Deficient</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #3b82f6"></div>
            <span class="text-gray-700 dark:text-gray-300">Not Evaluated</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #374151"></div>
            <span class="text-gray-700 dark:text-gray-300">Extinct in the Wild</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: #9ca3af"></div>
            <span class="text-gray-700 dark:text-gray-300">Extinct</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Center Map Button (moved to bottom left) -->
    <button
      @click="centerMap"
      class="absolute bottom-6 right-4 z-[1000] flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-700 transition text-xs font-semibold text-gray-700 dark:text-gray-200"
      title="Center Map"
    >
      <svg
        width="16"
        height="16"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
      >
        <circle cx="12" cy="12" r="10" />
        <circle cx="12" cy="12" r="3" fill="currentColor" />
      </svg>
      Center Map
    </button>
  </div>
</template>

<style scoped>
/* Map container responsive styling */
:deep(.leaflet-container) {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Zoom control styling */
:deep(.leaflet-control-zoom) {
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

:deep(.leaflet-control-zoom a) {
  color: #374151;
  font-weight: bold;
}

:deep(.leaflet-control-zoom a:hover) {
  background-color: #f3f4f6;
}
</style>
