<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { type SharedData } from "@/types";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Define types
interface Stat {
  label: string;
  value: string;
  subtext: string;
  subtextClass: string;
  icon: string;
  color: string;
  bgColor: string;
  progress: number;
}

interface Sighting {
  id: number;
  name: string;
  common_name?: string;
  date: string;
  family: string;
  location: string;
  status: string;
  statusClass: string;
  image: string | null;
}

interface MapSighting {
  id: number;
  latitude: number;
  longitude: number;
  scientificName: string | null;
  commonName: string | null;
  location: string | null;
  date: string;
  dateTimestamp: number;
  conservationStatus: string;
  image: string | null;
}

interface ActivityDataPoint {
  month: string;
  sightings: number;
  newSpecies: number;
}

interface Alert {
  title: string;
  description: string;
  time: string;
  type: "warning" | "info" | "success";
}

interface QuickAction {
  label: string;
  icon: string;
  href: string;
  color: string;
}

// Props from backend
const props = defineProps<{
  stats: Stat[];
  recentSightings: Sighting[];
  activityData: ActivityDataPoint[];
  greeting: string;
  currentDate: {
    day: string;
    date: string;
  };
  growthPercentage: number;
  mapSightings: MapSighting[];
  seasonalAlerts: any[];
  filters: {
    range: number;
  };
}>();

const user = computed(() => usePage<SharedData>().props.auth.user);

// Chart state
const hoveredPoint = ref<number | null>(null);
const selectedRange = ref(props.filters?.range?.toString() || "6");

watch(selectedRange, (newRange) => {
  router.get(
    route("welcome-plant"),
    { range: newRange },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["activityData", "filters"],
    }
  );
});

// Map references
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let markersLayer: L.LayerGroup | null = null;

// Conservation status color mapping
const getConservationStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    CR: "#dc2626", // Critically Endangered - red
    EN: "#ef4444", // Endangered - bright red
    VU: "#f97316", // Vulnerable - orange
    NT: "#eab308", // Near Threatened - yellow
    LC: "#22c55e", // Least Concern - green
    DD: "#6b7280", // Data Deficient - gray
    NE: "#3b82f6", // Not Evaluated - blue
  };
  return colors[status] || "#3b82f6";
};

// Get the latest sighting only
const latestSighting = computed(() => {
  if (!props.mapSightings || props.mapSightings.length === 0) return null;

  // Find the sighting with the highest timestamp (most recent)
  return props.mapSightings.reduce((latest, current) => {
    return current.dateTimestamp > latest.dateTimestamp ? current : latest;
  }, props.mapSightings[0]);
});

// Create marker
const createMarker = (sighting: MapSighting): L.CircleMarker => {
  const color = getConservationStatusColor(sighting.conservationStatus);
  return L.circleMarker([sighting.latitude, sighting.longitude], {
    radius: 8,
    fillColor: color,
    color: "#ffffff",
    weight: 2,
    opacity: 1,
    fillOpacity: 0.9,
  });
};

// Update markers on map
const updateMarkers = () => {
  if (!map || !markersLayer) return;

  markersLayer.clearLayers();

  const sighting = latestSighting.value;
  if (sighting && sighting.latitude && sighting.longitude) {
    const marker = createMarker(sighting);

    marker.bindPopup(
      `<div class="text-sm min-w-[150px]">
        <strong class="text-gray-900">${sighting.commonName || "Unknown Plant"}</strong><br/>
        ${sighting.scientificName ? `<em class="text-gray-600">${sighting.scientificName}</em><br/>` : ""}
        ${sighting.location ? `<div class="mt-1 text-xs text-gray-500">📍 ${sighting.location}</div>` : ""}
        <div class="text-xs text-gray-400">${sighting.date}</div>
      </div>`,
      { className: "leaflet-popup-custom" }
    );

    markersLayer.addLayer(marker);

    // Center map on the latest sighting
    map.setView([sighting.latitude, sighting.longitude], 10, { animate: true });
  }
};

// Initialize map
onMounted(async () => {
  await nextTick();

  if (mapContainer.value) {
    map = L.map(mapContainer.value, {
      center: [4.2105, 101.9758], // Malaysia center
      zoom: 6,
      zoomControl: false, // We'll add custom controls
      scrollWheelZoom: true,
      doubleClickZoom: true,
      dragging: true,
      touchZoom: true,
    });

    // Add OpenStreetMap tiles with a nice style
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OSM</a>',
      maxZoom: 19,
    }).addTo(map);

    // Initialize marker layer
    markersLayer = L.layerGroup().addTo(map);

    // Force size calculation and add markers
    setTimeout(() => {
      if (map) {
        map.invalidateSize();
        updateMarkers();
      }
    }, 100);
  }
});

// Watch for sightings changes
watch(() => props.mapSightings, () => {
  updateMarkers();
}, { deep: true });

// Map control functions
const zoomIn = () => map?.zoomIn();
const zoomOut = () => map?.zoomOut();
const centerMap = () => {
  if (latestSighting.value) {
    map?.setView([latestSighting.value.latitude, latestSighting.value.longitude], 10, { animate: true });
  } else {
    map?.setView([4.2105, 101.9758], 6, { animate: true });
  }
};

// Cleanup
onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

// Generate SVG path for activity chart
const chartPath = computed(() => {
  if (!props.activityData || props.activityData.length === 0) {
    return { sightings: { line: "", area: "", points: [] }, species: { line: "", points: [] } };
  }

  const data = props.activityData;
  const maxVal = Math.max(...data.map((d) => Math.max(d.sightings, d.newSpecies)), 1);
  const width = 800;
  const height = 300;
  const paddingX = 0;
  const paddingY = 0;

  const getPoints = (key: 'sightings' | 'newSpecies') => {
    return data.map((d, i) => ({
      x: (i / (data.length - 1)) * width,
      y: height - (d[key] / maxVal) * height,
      value: d[key],
      month: d.month,
    }));
  };

  const generateSmoothPath = (points: any[]) => {
    if (points.length === 0) return "";
    let path = `M${points[0].x},${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
      const p1 = points[i];
      const p2 = points[i + 1];
      const controlX = (p1.x + p2.x) / 2;
      path += ` C${controlX},${p1.y} ${controlX},${p2.y} ${p2.x},${p2.y}`;
    }
    return path;
  };

  const sightingPoints = getPoints('sightings');
  const speciesPoints = getPoints('newSpecies');

  const sightingLine = generateSmoothPath(sightingPoints);
  const speciesLine = generateSmoothPath(speciesPoints);

  const sightingArea = `${sightingLine} L${sightingPoints[sightingPoints.length - 1].x},${height} L${sightingPoints[0].x},${height} Z`;

  return {
    sightings: {
      line: sightingLine,
      area: sightingArea,
      points: sightingPoints,
    },
    species: {
      line: speciesLine,
      points: speciesPoints,
    },
  };
});

// Chart month labels from activity data
const chartMonths = computed(() => {
  if (!props.activityData) return [];
  return props.activityData.map((d) => d.month);
});

// Static alerts data (could be made dynamic in the future)
const alerts = computed(() => {
  if (props.seasonalAlerts && props.seasonalAlerts.length > 0) {
    return props.seasonalAlerts.map(alert => ({
      title: alert.title,
      description: alert.description,
      time: alert.source === 'api' ? 'Live Update' : 'Seasonal',
      type: alert.color === 'orange' ? 'warning' : 'info'
    }));
  }
  
  // Fallback if no dynamic alerts
  return [
    {
      title: "Durian Season Peak",
      description: "Musang King and D24 availability is at its highest in the Pahang region.",
      time: "2 hours ago",
      type: "warning",
    },
    {
      title: "Rambutan Season Starting",
      description: "Early harvests of Anak Sekolah varieties are appearing in northern states.",
      time: "1 day ago",
      type: "info",
    },
  ];
});

const quickActions: QuickAction[] = [
  { label: "Plant Search", icon: "search", href: "/plant-search", color: "text-gray-500" },
  { label: "Edit Profile", icon: "user", href: "/settings/profile", color: "text-gray-500" },
  { label: "Community Forum", icon: "message-square", href: "/forum", color: "text-gray-500" },
  { label: "My Collection", icon: "leaf", href: "/my-plants", color: "text-gray-500" },
];
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="min-h-screen pb-12 font-sans text-gray-900 bg-[#F3F4F6] dark:bg-[#111827] transition-colors duration-200">
      <!-- Header Section -->
      <div class="px-4 py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              Welcome back, {{ user?.name || "Explorer" }}
            </h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">
              Here's what's happening with your Malaysian flora discoveries.
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button
              variant="outline"
              class="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon name="upload" class="w-4 h-4" />
              Import Data
            </Button>
            <Link href="/plant-identifier">
              <Button
                class="bg-[#0f172a] hover:bg-[#1e293b] text-white px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 shadow-md transition-colors"
              >
                <Icon name="camera" class="w-4 h-4" />
                Report Sighting
              </Button>
            </Link>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-6 mt-8 md:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(stat, index) in stats"
            :key="index"
            class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl"
          >
            <div class="flex items-start justify-between mb-4">
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.label }}</h3>
              <div :class="`p-2.5 rounded-xl ${stat.bgColor} dark:bg-opacity-10`">
                <Icon :name="stat.icon" :class="`w-5 h-5 ${stat.color}`" />
              </div>
            </div>
            <div class="flex items-end gap-2">
              <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</span>
            </div>
            <div
              class="flex items-center gap-1 mt-1.5 text-xs font-medium"
              :class="stat.subtextClass"
            >
              <Icon
                v-if="index === 0"
                name="trending-up"
                class="w-3.5 h-3.5"
              />
              {{ stat.subtext }}
            </div>
          </div>
        </div>

        <!-- Middle Section: Chart & Map -->
        <div class="grid grid-cols-1 gap-6 mt-8 lg:grid-cols-3">
          <!-- Sighting Trends Chart -->
          <div class="p-6 bg-white border border-gray-200 shadow-sm lg:col-span-2 dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-lg font-bold text-gray-900 dark:text-white">Sighting Trends</h2>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 text-xs">
                  <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Sightings</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-gray-900 rounded-full dark:bg-gray-400"></span>
                    <span class="text-gray-600 dark:text-gray-400">New Species</span>
                  </div>
                </div>
                <Select v-model="selectedRange">
                  <SelectTrigger class="w-[140px] h-9 bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600">
                    <SelectValue placeholder="Select range" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="3">Last 3 Months</SelectItem>
                    <SelectItem value="6">Last 6 Months</SelectItem>
                    <SelectItem value="12">Last 12 Months</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="relative w-full h-64 group">
              <svg
                class="w-full h-full overflow-visible"
                viewBox="0 0 800 300"
                preserveAspectRatio="none"
                @mouseleave="hoveredPoint = null"
              >
                <defs>
                  <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#10B981" stop-opacity="0.2" />
                    <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                  </linearGradient>
                </defs>

                <!-- Grid lines -->
                <g class="grid-lines">
                  <line x1="0" y1="300" x2="800" y2="300" stroke="#E5E7EB" class="dark:stroke-gray-700" stroke-width="1" />
                  <line x1="0" y1="225" x2="800" y2="225" stroke="#E5E7EB" class="dark:stroke-gray-700" stroke-width="1" stroke-dasharray="4 4" />
                  <line x1="0" y1="150" x2="800" y2="150" stroke="#E5E7EB" class="dark:stroke-gray-700" stroke-width="1" stroke-dasharray="4 4" />
                  <line x1="0" y1="75" x2="800" y2="75" stroke="#E5E7EB" class="dark:stroke-gray-700" stroke-width="1" stroke-dasharray="4 4" />
                  <line x1="0" y1="0" x2="800" y2="0" stroke="#E5E7EB" class="dark:stroke-gray-700" stroke-width="1" stroke-dasharray="4 4" />
                </g>

                <!-- Hover Vertical Line -->
                <line
                  v-if="hoveredPoint !== null"
                  :x1="(hoveredPoint / (activityData.length - 1)) * 800"
                  y1="0"
                  :x2="(hoveredPoint / (activityData.length - 1)) * 800"
                  y2="300"
                  stroke="#e2e8f0"
                  class="dark:stroke-gray-600"
                  stroke-width="1"
                />

                <!-- Sightings Area -->
                <path
                  :d="chartPath.sightings.area"
                  fill="url(#chartGradient)"
                  class="transition-all duration-500"
                />

                <!-- Sightings Line -->
                <path
                  :d="chartPath.sightings.line"
                  fill="none"
                  stroke="#10B981"
                  stroke-width="3"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="transition-all duration-500"
                />

                <!-- New Species Line -->
                <path
                  :d="chartPath.species.line"
                  fill="none"
                  stroke="#1F2937"
                  stroke-width="2"
                  stroke-dasharray="6 4"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="transition-all duration-500 dark:stroke-gray-400"
                />

                <!-- Points for interaction -->
                <g v-for="(point, idx) in activityData" :key="idx">
                  <rect
                    :x="(idx / (activityData.length - 1)) * 800 - 20"
                    y="0"
                    width="40"
                    height="300"
                    fill="transparent"
                    class="cursor-pointer"
                    @mouseenter="hoveredPoint = idx"
                  />
                </g>
              </svg>

              <!-- Tooltip Overlay -->
              <div
                v-if="hoveredPoint !== null"
                class="absolute z-10 p-3 transition-all duration-200 -translate-x-1/2 bg-white border border-gray-100 shadow-lg dark:bg-gray-800 dark:border-gray-700 pointer-events-none rounded-xl min-w-[120px]"
                :style="{
                  left: `${(hoveredPoint / (activityData.length - 1)) * 100}%`,
                  top: '20px'
                }"
              >
                <div class="text-xs font-bold text-gray-400 uppercase mb-1.5">
                  {{ activityData[hoveredPoint].month }}
                </div>
                <div class="flex flex-col gap-1.5">
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-xs text-gray-600 dark:text-gray-400">Sightings</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ activityData[hoveredPoint].sightings }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-xs text-gray-600 dark:text-gray-400">New Species</span>
                    <span class="text-xs font-bold text-emerald-600">{{ activityData[hoveredPoint].newSpecies }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- X-Axis Labels -->
            <div class="flex justify-between mt-6">
              <span
                v-for="(month, idx) in chartMonths"
                :key="idx"
                class="text-xs font-medium text-gray-400 uppercase"
              >
                {{ month }}
              </span>
            </div>
          </div>

          <!-- Recent Discovery Map -->
          <div class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
              <h2 class="text-base font-bold text-gray-900 dark:text-white">Recent Discovery Map</h2>
              <Link href="/plant-map" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">View Full Map</Link>
            </div>
            <div class="relative flex-grow bg-gray-100 dark:bg-gray-900 min-h-[300px]">
              <!-- Leaflet Map Container -->
              <div ref="mapContainer" class="absolute inset-0 z-0"></div>

              <!-- Map Overlay Card -->
              <div v-if="latestSighting" class="absolute bottom-4 left-4 right-4 z-[1000] bg-white dark:bg-gray-800 p-3 rounded-xl shadow-lg flex items-center gap-3 border border-gray-100 dark:border-gray-700">
                <div class="w-12 h-12 overflow-hidden bg-gray-100 rounded-lg shrink-0">
                  <img
                    v-if="latestSighting.image"
                    :src="latestSighting.image"
                    class="object-cover w-full h-full"
                    :alt="latestSighting.commonName || 'Plant'"
                  />
                  <div v-else class="flex items-center justify-center h-full">
                    <Icon name="image" class="w-6 h-6 text-gray-300" />
                  </div>
                </div>
                <div class="flex-grow min-w-0">
                  <h4 class="text-sm font-bold text-gray-900 truncate dark:text-white">
                    {{ latestSighting.commonName || latestSighting.scientificName || "Unknown Plant" }}
                  </h4>
                  <p class="text-xs text-gray-500 truncate dark:text-gray-400">
                    {{ latestSighting.location }} • {{ latestSighting.date }}
                  </p>
                </div>
              </div>
              <div v-else class="absolute inset-0 z-[1000] bg-gray-50/80 dark:bg-gray-900/80 backdrop-blur-[2px] flex flex-col items-center justify-center p-6 text-center">
                <div class="p-3 mb-3 bg-white rounded-full shadow-sm dark:bg-gray-800">
                  <Icon name="map" class="w-6 h-6 text-gray-400" />
                </div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">No locations recorded</h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your plant discoveries will appear on this map.</p>
              </div>

              <!-- Map Controls -->
              <div class="absolute top-4 right-4 flex flex-col gap-2 z-[1000]">
                <button
                  @click="zoomIn"
                  class="p-2 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <Icon name="plus" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                </button>
                <button
                  @click="zoomOut"
                  class="p-2 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <Icon name="minus" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Sightings Table -->
        <div class="mt-8 overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
          <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Sightings</h2>
            <div class="flex items-center gap-3">
              <Link href="/sightings" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">View All</Link>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="text-xs font-medium text-gray-500 uppercase bg-gray-50 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                  <th class="px-6 py-4">Plant Name</th>
                  <th class="px-6 py-4">Family</th>
                  <th class="px-6 py-4">Location</th>
                  <th class="px-6 py-4">Date</th>
                  <th class="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-if="recentSightings.length > 0"
                  v-for="sighting in recentSightings"
                  :key="sighting.id"
                  class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-6 py-5">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 overflow-hidden bg-gray-100 rounded-lg dark:bg-gray-900 shrink-0">
                        <img
                          v-if="sighting.image"
                          :src="sighting.image"
                          class="object-cover w-full h-full"
                          :alt="sighting.name"
                        />
                        <div v-else class="flex items-center justify-center h-full">
                          <Icon name="spa" class="w-5 h-5 text-emerald-600" />
                        </div>
                      </div>
                      <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ sighting.name }}</div>
                        <div class="text-xs italic text-gray-500 dark:text-gray-400">{{ sighting.common_name || sighting.name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-5">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="sighting.status === 'Endangered' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'"
                    >
                      {{ sighting.family }}
                    </span>
                  </td>
                  <td class="px-6 py-5 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center gap-1">
                      <Icon name="map-pin" class="w-3.5 h-3.5 text-gray-400" />
                      {{ sighting.location }}
                    </div>
                  </td>
                  <td class="px-6 py-5 text-sm text-gray-600 dark:text-gray-300">{{ sighting.date }}</td>
                  <td class="px-6 py-5 text-right">
                    <Link :href="`/sightings/${sighting.id}`">
                      <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <Icon name="more-vertical" class="w-5 h-5" />
                      </button>
                    </Link>
                  </td>
                </tr>
                <tr v-else>
                  <td colspan="5" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                      <div class="p-3 mb-4 bg-gray-50 rounded-full dark:bg-gray-900/50">
                        <Icon name="search-x" class="w-8 h-8 text-gray-400" />
                      </div>
                      <h3 class="text-sm font-bold text-gray-900 dark:text-white">No sightings yet</h3>
                      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Start exploring and identify plants to see them here.</p>
                      <Link href="/plant-identifier" class="mt-4">
                        <Button variant="outline" size="sm" class="gap-2">
                          <Icon name="camera" class="w-4 h-4" />
                          Identify Plant
                        </Button>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Bottom Section: Alerts & Actions -->
        <div class="grid grid-cols-1 gap-6 mt-8 lg:grid-cols-2">
          <!-- Seasonal Alerts -->
          <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
            <h2 class="flex items-center gap-2 mb-4 text-lg font-bold text-gray-900 dark:text-white">
              <Icon name="alert-triangle" class="w-5 h-5 text-orange-500" />
              Seasonal Alerts
            </h2>
            <div class="space-y-3">
              <div
                v-for="(alert, index) in alerts"
                :key="index"
                :class="`p-4 border rounded-xl flex items-start gap-3 ${
                  alert.type === 'warning'
                    ? 'bg-orange-50 border-orange-100 dark:bg-orange-900/10 dark:border-orange-900/30'
                    : 'bg-blue-50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-900/30'
                }`"
              >
                <Icon
                  :name="alert.type === 'warning' ? 'info' : 'flask-conical'"
                  :class="`w-5 h-5 mt-0.5 ${alert.type === 'warning' ? 'text-orange-600 dark:text-orange-400' : 'text-blue-600 dark:text-blue-400'}`"
                />
                <div>
                  <h4 :class="`text-sm font-bold ${alert.type === 'warning' ? 'text-orange-800 dark:text-orange-300' : 'text-blue-800 dark:text-blue-300'}`">
                    {{ alert.title }}
                  </h4>
                  <p :class="`text-xs mt-1 ${alert.type === 'warning' ? 'text-orange-700 dark:text-orange-400' : 'text-blue-700 dark:text-blue-400'}`">
                    {{ alert.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
            <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-4">
              <Link
                v-for="action in quickActions"
                :key="action.label"
                :href="action.href"
                class="flex flex-col items-center justify-center p-6 transition-colors bg-gray-50 dark:bg-gray-900 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 group"
              >
                <Icon
                  :name="action.icon"
                  class="w-6 h-6 mb-2 text-gray-500 transition-colors group-hover:text-emerald-600 dark:text-gray-400 dark:group-hover:text-white"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.label }}</span>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* Custom Leaflet popup styles */
.leaflet-popup-custom .leaflet-popup-content-wrapper {
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  padding: 0;
}

.leaflet-popup-custom .leaflet-popup-content {
  margin: 12px 14px;
}

.leaflet-popup-custom .leaflet-popup-tip {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Hide default Leaflet attribution on small screens */
.leaflet-control-attribution {
  font-size: 9px;
  background: rgba(255, 255, 255, 0.8) !important;
  padding: 2px 5px !important;
}

/* Ensure map container has proper dimensions */
.leaflet-container {
  font-family: inherit;
}
</style>
