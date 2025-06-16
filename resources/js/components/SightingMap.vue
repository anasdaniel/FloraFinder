<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import type { Sighting } from '@/composables'; // Assuming Sighting type is exported from composables

// Props
const props = defineProps<{
  sightings: Sighting[];
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const markers = L.markerClusterGroup(); // Change let to const

// Initialize map
onMounted(() => {
  if (mapContainer.value) {
    map = L.map(mapContainer.value).setView([4.2105, 101.9758], 7); // Centered on Malaysia

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    map.addLayer(markers);
    updateMarkers(props.sightings);
  }
});

// Watch for changes in sightings data and update markers
watch(() => props.sightings, (newSightings) => {
  updateMarkers(newSightings);
});

// Function to update map markers
const updateMarkers = (sightings: Sighting[]) => {
  if (!map) return;
  markers.clearLayers();
  sightings.forEach(sighting => {
    if (sighting.latitude && sighting.longitude) {
      const marker = L.marker([sighting.latitude, sighting.longitude]);
      marker.bindPopup(`<b>${sighting.commonName}</b><br>${sighting.location}`);
      markers.addLayer(marker);
    }
  });
};
</script>

<template>
  <div ref="mapContainer" style="height: 500px; width: 100%;"></div>
</template>
