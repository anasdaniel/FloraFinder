import { ref, nextTick } from 'vue';
import L, { LatLngExpression } from 'leaflet';
import 'leaflet.markercluster';
// import { Sighting } from '@/types'; // Assuming Sighting type is defined - temporarily using any

// Define a type for the conservation status to icon mapping
interface ConservationStatusColors {
  [key: string]: string;
}

const conservationStatusColors: ConservationStatusColors = {
  'Critically Endangered': 'darkred',
  'Endangered': 'red',
  'Vulnerable': 'orange',
  'Near Threatened': 'yellow',
  'Least Concern': 'green',
  'Data Deficient': 'grey',
  'Not Evaluated': 'black',
  'Default': 'blue' // Default color
};

// Temporary Sighting type
interface Sighting {
  id: number | string;
  latitude: string | number;
  longitude: string | number;
  plant?: {
    common_name?: string;
    scientific_name?: string;
    conservation_status?: string;
  };
  sighting_date: string | Date;
  description?: string;
  // Add other properties as needed
}

export function useMap() {
  const map = ref<L.Map | null>(null);
  // markersLayer can be MarkerClusterGroup or LayerGroup, both are Layers
  const markersLayer = ref<L.MarkerClusterGroup | L.LayerGroup | null>(null);
  const isClusteringEnabled = ref(true);

  const createCustomIcon = (status: string | null | undefined): L.DivIcon => {
    const colorKey = status && conservationStatusColors[status] ? status : 'Default';
    const color = conservationStatusColors[colorKey];
    return L.divIcon({
      html: `<span style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; display: inline-block; border: 1px solid white;"></span>`,
      className: 'custom-div-icon',
      iconSize: [12, 12],
      iconAnchor: [6, 6]
    });
  };

  const initializeMap = (elementId: string, center: LatLngExpression, zoom: number) => {
    console.log(`useMap: Initializing map on element '${elementId}' with center`, center, `zoom`, zoom);
    if (map.value) {
      console.log('useMap: Map already initialized. Cleaning up existing instance.');
      map.value.remove();
      map.value = null;
    }

    const mapInstance = L.map(elementId).setView(center, zoom);
    console.log('useMap: Map instance created.');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapInstance);
    console.log('useMap: Tile layer added to map.');

    map.value = mapInstance;

    if (!markersLayer.value) {
      markersLayer.value = L.markerClusterGroup();
      console.log('useMap: MarkerClusterGroup initialized.');
      map.value.addLayer(markersLayer.value as unknown as L.Layer); // Changed type assertion
      console.log('useMap: MarkerClusterGroup added to map.');
    } else {
      console.log('useMap: markersLayer already exists. Ensuring it is added to the map.');
      if (markersLayer.value && !map.value.hasLayer(markersLayer.value as unknown as L.Layer)) { // Changed type assertion
        map.value.addLayer(markersLayer.value as unknown as L.Layer); // Changed type assertion
      }
    }

    nextTick(() => {
      if (map.value) {
        console.log('useMap: Invalidating map size.');
        map.value.invalidateSize(true);

        // Also trigger a pan to ensure tiles load
        const center = map.value.getCenter();
        map.value.panTo(center);
      }
    });

    console.log('useMap: Map initialization complete.');
  };

  const addSightingsToMap = (sightings: Sighting[]) => {
    console.log('useMap: addSightingsToMap called with', sightings.length, 'sightings');
    if (!map.value || !markersLayer.value) {
      console.warn('useMap: Map or markersLayer not initialized yet in addSightingsToMap.');
      return;
    }

    console.log('useMap: Clearing existing markers from markersLayer.');
    markersLayer.value.clearLayers();

    if (sightings.length === 0) {
      console.log('useMap: No sightings to add.');
      return;
    }

    const bounds = L.latLngBounds([]);
    console.log('useMap: Processing sightings to create markers...');

    sightings.forEach(sighting => {
      const lat = Number(sighting.latitude);
      const lng = Number(sighting.longitude);

      if (!isNaN(lat) && !isNaN(lng)) {
        const latLng: LatLngExpression = [lat, lng];
        const customIcon = createCustomIcon(sighting.plant?.conservation_status);

        let popupContent = `<b>${sighting.plant?.common_name || 'Unknown Plant'}</b><br>`;
        popupContent += `Scientific Name: ${sighting.plant?.scientific_name || 'N/A'}<br>`;
        popupContent += `Sighted on: ${new Date(sighting.sighting_date).toLocaleDateString()}<br>`;
        if (sighting.description) {
          popupContent += `Description: ${sighting.description}<br>`;
        }
        popupContent += `Coordinates: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        const marker = L.marker(latLng, { icon: customIcon })
          .bindPopup(popupContent);

        // addLayer is available on both MarkerClusterGroup and LayerGroup
        (markersLayer.value as L.MarkerClusterGroup | L.LayerGroup).addLayer(marker);
        bounds.extend(latLng);
      } else {
        console.warn(`useMap: Sighting ID ${sighting.id} has invalid coordinates: lat=${sighting.latitude}, lng=${sighting.longitude}`);
      }
    });

    if (bounds.isValid()) {
      console.log('useMap: Fitting map to marker bounds.');
      map.value.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
    } else {
      console.log('useMap: No valid bounds to fit.');
    }
    console.log('useMap: Finished adding sightings to map. Total markers in layer:', (markersLayer.value as L.MarkerClusterGroup | L.LayerGroup).getLayers().length);
  };

  const toggleClustering = (enableClustering: boolean) => {
    if (!map.value || !markersLayer.value) {
        console.warn('useMap: Map or markersLayer not initialized for toggleClustering.');
        return;
    }

    console.log(`useMap: Toggling clustering. Requested: ${enableClustering}, Current: ${isClusteringEnabled.value}`);

    if (enableClustering === isClusteringEnabled.value) {
        console.log('useMap: Clustering state is already as requested.');
        return;
    }

    const currentMarkers = (markersLayer.value as L.MarkerClusterGroup | L.LayerGroup).getLayers() as L.Marker[];
    console.log(`useMap: Stored ${currentMarkers.length} current markers.`);

    if (markersLayer.value && map.value.hasLayer(markersLayer.value as unknown as L.Layer)) { // Changed type assertion
        console.log('useMap: Removing current markersLayer from map.');
        map.value.removeLayer(markersLayer.value as unknown as L.Layer); // Changed type assertion
    }

    if (enableClustering) {
        console.log('useMap: Enabling clustering.');
        markersLayer.value = L.markerClusterGroup();
        console.log('useMap: New MarkerClusterGroup created for markersLayer.');
    } else {
        console.log('useMap: Disabling clustering.');
        markersLayer.value = L.layerGroup();
        console.log('useMap: New LayerGroup created for markersLayer (clustering disabled).');
    }

    if (currentMarkers.length > 0) {
        console.log(`useMap: Adding ${currentMarkers.length} markers to the new markersLayer.`);
        currentMarkers.forEach(marker => (markersLayer.value as L.MarkerClusterGroup | L.LayerGroup).addLayer(marker));
    }

    if (markersLayer.value) {
        map.value.addLayer(markersLayer.value as unknown as L.Layer); // Changed type assertion
        console.log('useMap: New/reconfigured markersLayer added back to map.');
    }

    isClusteringEnabled.value = enableClustering;
    console.log(`useMap: Clustering is now ${isClusteringEnabled.value ? 'enabled' : 'disabled'}.`);
  };

  const cleanup = () => {
    console.log('useMap: Cleaning up map resources...');
    if (map.value) {
      console.log('useMap: Removing map instance.');
      map.value.remove();
      map.value = null;
    }
    if (markersLayer.value) {
      console.log('useMap: Clearing layers from markersLayer and nullifying.');
      markersLayer.value.clearLayers();
      markersLayer.value = null;
    }
    console.log('useMap: Cleanup complete.');
  };

  return {
    map,
    markersLayer,
    initializeMap,
    addSightingsToMap,
    toggleClustering,
    cleanup,
    isClusteringEnabled
  };
}
