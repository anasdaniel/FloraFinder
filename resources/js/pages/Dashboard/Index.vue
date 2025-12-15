<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/AppLayout.vue";
import { type SharedData } from "@/types";
import { Link, usePage } from "@inertiajs/vue3";
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
}>();

const user = computed(() => usePage<SharedData>().props.auth.user);

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
    return { line: "", area: "", points: [] };
  }

  const data = props.activityData;
  const maxSightings = Math.max(...data.map((d) => d.sightings), 1);
  const width = 300;
  const height = 150;
  const padding = 10;

  // Calculate points
  const points = data.map((d, i) => ({
    x: (i / (data.length - 1)) * (width - padding * 2) + padding,
    y: height - padding - (d.sightings / maxSightings) * (height - padding * 2),
    sightings: d.sightings,
    month: d.month,
  }));

  // Create smooth curve using cubic bezier
  let linePath = `M${points[0].x},${points[0].y}`;

  for (let i = 0; i < points.length - 1; i++) {
    const p0 = points[Math.max(i - 1, 0)];
    const p1 = points[i];
    const p2 = points[i + 1];
    const p3 = points[Math.min(i + 2, points.length - 1)];

    const cp1x = p1.x + (p2.x - p0.x) / 6;
    const cp1y = p1.y + (p2.y - p0.y) / 6;
    const cp2x = p2.x - (p3.x - p1.x) / 6;
    const cp2y = p2.y - (p3.y - p1.y) / 6;

    linePath += ` C${cp1x},${cp1y} ${cp2x},${cp2y} ${p2.x},${p2.y}`;
  }

  // Create area path (close to bottom)
  const areaPath = `${linePath} L${points[points.length - 1].x},${height} L${points[0].x},${height} Z`;

  return {
    line: linePath,
    area: areaPath,
    points,
  };
});

// Chart month labels from activity data
const chartMonths = computed(() => {
  if (!props.activityData) return [];
  return props.activityData.map((d) => d.month);
});

// Static alerts data (could be made dynamic in the future)
const alerts: Alert[] = [
  {
    title: "Rafflesia Season Ending",
    description: "Sightings in Perak region are decreasing as the blooming season ends this week.",
    time: "2 hours ago",
    type: "warning",
  },
  {
    title: "Taxonomy Update",
    description: "Scientific names updated for the Dipterocarpaceae family. Check your library.",
    time: "1 day ago",
    type: "info",
  },
  {
    title: "New Conservation Zone",
    description: "Mossy Forest area expanded. New reporting guidelines available.",
    time: "2 days ago",
    type: "success",
  },
];

const quickActions: QuickAction[] = [
  { label: "New Scan", icon: "scan", href: "/plant-identifier", color: "text-emerald-600" },
  { label: "Export CSV", icon: "download", href: "#", color: "text-blue-600" },
  { label: "Profile", icon: "user", href: "/settings/profile", color: "text-purple-600" },
  { label: "Community", icon: "message-square", href: "/forum", color: "text-amber-600" },
];
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="min-h-screen pb-12 font-sans text-gray-900 bg-gray-50">
      <!-- Header Section -->
      <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
              <div
                class="flex items-center gap-2 mb-2 text-sm font-medium text-emerald-600"
              >
                <span
                  class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs uppercase tracking-wide font-bold"
                  >{{ greeting }}</span
                >
                <span class="text-gray-300">|</span>
                <span class="text-gray-500"
                  >{{ currentDate.day }}, {{ currentDate.date }}</span
                >
              </div>
              <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Welcome back, {{ user?.name || "Explorer" }}
              </h1>
              <p class="mt-2 text-lg text-gray-500">
                Your contribution to Malaysian flora identification has grown by
                <span
                  :class="growthPercentage >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                  class="font-semibold"
                  >{{ growthPercentage >= 0 ? "+" : "" }}{{ growthPercentage }}%</span
                >
                this month.
              </p>
            </div>
            <div class="flex items-center gap-3">
              <Button
                variant="outline"
                class="gap-2 text-gray-700 border-gray-300 shadow-sm hover:bg-gray-50 hover:text-gray-900"
              >
                <Icon name="settings" class="w-4 h-4" />
                Customize Widgets
              </Button>
              <Link href="/plant-identifier">
                <Button
                  class="gap-2 text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200 hover:shadow-lg hover:shadow-emerald-200"
                >
                  <Icon name="plus" class="w-4 h-4" />
                  New Sighting
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div class="px-4 py-8 mx-auto space-y-8 max-w-7xl sm:px-6 lg:px-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card
            v-for="(stat, index) in stats"
            :key="index"
            class="transition-shadow duration-200 bg-white border border-gray-100 shadow-sm hover:shadow-md"
          >
            <CardContent class="p-6">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-500">{{ stat.label }}</p>
                  <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-3xl font-bold text-gray-900">{{ stat.value }}</span>
                    <span :class="stat.subtextClass">{{ stat.subtext }}</span>
                  </div>
                </div>
                <div :class="`p-3 rounded-xl ${stat.bgColor}`">
                  <Icon :name="stat.icon" :class="`w-6 h-6 ${stat.color}`" />
                </div>
              </div>
              <div class="mt-4 w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                <div
                  :class="[
                    'h-full rounded-full transition-all duration-500',
                    index === 0
                      ? 'bg-emerald-500'
                      : index === 1
                      ? 'bg-amber-500'
                      : index === 2
                      ? 'bg-blue-500'
                      : 'bg-rose-500',
                  ]"
                  :style="{ width: `${stat.progress}%` }"
                ></div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Middle Section: Map & Chart -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Discovery Map -->
          <div class="lg:col-span-2">
            <Card
              class="flex flex-col h-full overflow-hidden transition-shadow duration-200 bg-white border border-gray-100 shadow-sm hover:shadow-md"
            >
              <CardHeader
                class="flex flex-row items-center justify-between px-6 py-4 border-b border-gray-100"
              >
                <div>
                  <CardTitle class="text-lg font-bold text-gray-900"
                    >Latest Sighting</CardTitle
                  >
                  <p class="mt-1 text-sm text-gray-500">Most recent plant discovery</p>
                </div>
                <div class="flex gap-2">
                  <Link href="/plant-map">
                    <Button
                      variant="outline"
                      size="sm"
                      class="h-8 gap-1.5 text-gray-600 text-xs font-medium"
                    >
                      <Icon name="maximize-2" class="w-3.5 h-3.5" />
                      View All
                    </Button>
                  </Link>
                </div>
              </CardHeader>
              <div class="flex-1 bg-gray-50 relative min-h-[320px] overflow-hidden">
                <!-- Leaflet Map Container -->
                <div ref="mapContainer" class="absolute inset-0 z-0"></div>

                <!-- Map Controls -->
                <div class="absolute bottom-4 right-4 flex flex-col gap-1.5 z-[1000]">
                  <Button
                    size="icon"
                    variant="secondary"
                    class="w-8 h-8 bg-white border border-gray-100 shadow-sm hover:bg-gray-50"
                    @click="zoomIn"
                  >
                    <Icon name="plus" class="w-4 h-4 text-gray-600" />
                  </Button>
                  <Button
                    size="icon"
                    variant="secondary"
                    class="w-8 h-8 bg-white border border-gray-100 shadow-sm hover:bg-gray-50"
                    @click="zoomOut"
                  >
                    <Icon name="minus" class="w-4 h-4 text-gray-600" />
                  </Button>
                  <Button
                    size="icon"
                    variant="secondary"
                    class="w-8 h-8 bg-white border border-gray-100 shadow-sm hover:bg-gray-50"
                    @click="centerMap"
                  >
                    <Icon name="locate" class="w-4 h-4 text-gray-600" />
                  </Button>
                </div>

                <!-- Sightings count badge -->
                <div v-if="latestSighting" class="absolute top-4 left-4 z-[1000]">
                  <div
                    class="bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-sm border border-gray-100"
                  >
                    <div class="flex items-center gap-2 mb-1">
                      <div
                        class="w-2.5 h-2.5 rounded-full animate-pulse"
                        :style="{
                          backgroundColor: getConservationStatusColor(
                            latestSighting.conservationStatus
                          ),
                        }"
                      ></div>
                      <span class="text-xs font-semibold text-gray-900"
                        >Latest Sighting</span
                      >
                    </div>
                    <p class="text-xs text-gray-600 truncate max-w-[150px]">
                      {{
                        latestSighting.commonName ||
                        latestSighting.scientificName ||
                        "Unknown"
                      }}
                    </p>
                  </div>
                </div>

                <!-- Empty state overlay -->
                <div
                  v-if="!mapSightings || mapSightings.length === 0"
                  class="absolute inset-0 flex items-center justify-center bg-gray-50/90 z-[500]"
                >
                  <div class="text-center">
                    <div
                      class="inline-flex items-center justify-center p-4 mb-3 bg-white rounded-full shadow-sm"
                    >
                      <Icon name="map" class="w-8 h-8 text-gray-300" />
                    </div>
                    <p class="font-medium text-gray-500">No sightings yet</p>
                    <Link
                      href="/plant-identifier"
                      class="inline-block mt-2 text-sm font-medium text-emerald-600 hover:underline"
                    >
                      Add your first sighting →
                    </Link>
                  </div>
                </div>
              </div>
              <div
                class="flex flex-wrap items-center justify-between gap-2 px-6 py-3 bg-white border-t border-gray-100"
              >
                <!-- Status Legend -->
                <div class="flex items-center gap-1 text-xs text-gray-500">
                  <span class="mr-2 font-medium text-gray-600">Status:</span>
                  <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-red-50">
                    <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                    <span class="text-red-700">Endangered</span>
                  </div>
                  <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-orange-50">
                    <div class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>
                    <span class="text-orange-700">Vulnerable</span>
                  </div>
                  <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-green-50">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                    <span class="text-green-700">Least Concern</span>
                  </div>
                </div>

                <!-- View all link -->
                <Link
                  href="/plant-map"
                  class="flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 hover:underline"
                >
                  View all on map
                  <Icon name="arrow-right" class="w-3 h-3" />
                </Link>
              </div>
            </Card>
          </div>

          <!-- Activity Chart -->
          <div class="lg:col-span-1">
            <Card
              class="flex flex-col h-full transition-shadow duration-200 bg-white border border-gray-100 shadow-sm hover:shadow-md"
            >
              <CardHeader
                class="flex flex-row items-center justify-between px-6 py-4 border-b border-gray-50"
              >
                <div>
                  <CardTitle class="text-lg font-bold text-gray-900">Activity</CardTitle>
                  <p class="mt-1 text-sm text-gray-500">Sightings over time</p>
                </div>
                <div class="flex items-center gap-2">
                  <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                    <span>Sightings</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent class="flex flex-col justify-end flex-1 p-6">
                <!-- Chart container with hover effects -->
                <div class="h-[200px] w-full relative group">
                  <svg
                    class="w-full h-full overflow-visible"
                    viewBox="0 0 300 150"
                    preserveAspectRatio="none"
                  >
                    <defs>
                      <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.3" />
                        <stop offset="50%" stop-color="#10b981" stop-opacity="0.1" />
                        <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                      </linearGradient>
                      <filter id="glow">
                        <feGaussianBlur stdDeviation="2" result="coloredBlur" />
                        <feMerge>
                          <feMergeNode in="coloredBlur" />
                          <feMergeNode in="SourceGraphic" />
                        </feMerge>
                      </filter>
                    </defs>

                    <!-- Grid lines -->
                    <line
                      x1="0"
                      y1="150"
                      x2="300"
                      y2="150"
                      stroke="#e5e7eb"
                      stroke-width="1"
                    />
                    <line
                      x1="0"
                      y1="112.5"
                      x2="300"
                      y2="112.5"
                      stroke="#f3f4f6"
                      stroke-width="1"
                      stroke-dasharray="4 4"
                    />
                    <line
                      x1="0"
                      y1="75"
                      x2="300"
                      y2="75"
                      stroke="#f3f4f6"
                      stroke-width="1"
                      stroke-dasharray="4 4"
                    />
                    <line
                      x1="0"
                      y1="37.5"
                      x2="300"
                      y2="37.5"
                      stroke="#f3f4f6"
                      stroke-width="1"
                      stroke-dasharray="4 4"
                    />

                    <!-- Dynamic Area -->
                    <path
                      :d="chartPath.area"
                      fill="url(#chartGradient)"
                      class="transition-all duration-500"
                    />

                    <!-- Dynamic Line -->
                    <path
                      :d="chartPath.line"
                      fill="none"
                      stroke="#10b981"
                      stroke-width="2.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="transition-all duration-500"
                      filter="url(#glow)"
                    />

                    <!-- Dynamic Points with tooltips -->
                    <g v-for="(point, idx) in chartPath.points" :key="idx">
                      <circle
                        :cx="point.x"
                        :cy="point.y"
                        r="4"
                        fill="#ffffff"
                        stroke="#10b981"
                        stroke-width="2"
                        class="transition-all duration-300 cursor-pointer hover:r-6"
                      />
                      <!-- Hover area for tooltip -->
                      <circle
                        :cx="point.x"
                        :cy="point.y"
                        r="12"
                        fill="transparent"
                        class="cursor-pointer"
                      >
                        <title>{{ point.month }}: {{ point.sightings }} sightings</title>
                      </circle>
                    </g>
                  </svg>

                  <!-- Empty state overlay -->
                  <div
                    v-if="!activityData || activityData.every((d) => d.sightings === 0)"
                    class="absolute inset-0 flex items-center justify-center rounded-lg bg-gray-50/80"
                  >
                    <div class="text-center">
                      <Icon
                        name="bar-chart-2"
                        class="w-8 h-8 mx-auto mb-2 text-gray-300"
                      />
                      <p class="text-sm text-gray-500">No activity yet</p>
                      <p class="text-xs text-gray-400">Start adding sightings!</p>
                    </div>
                  </div>
                </div>

                <!-- Dynamic month labels -->
                <div
                  class="flex justify-between mt-4 text-xs font-medium tracking-wider text-gray-400 uppercase"
                >
                  <span v-for="month in chartMonths" :key="month">{{ month }}</span>
                </div>

                <!-- Summary stats -->
                <div
                  class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100"
                >
                  <div class="text-xs text-gray-500">
                    <span class="font-semibold text-gray-900">{{
                      activityData?.reduce((sum, d) => sum + d.sightings, 0) || 0
                    }}</span>
                    total sightings
                  </div>
                  <div class="text-xs text-gray-500">
                    <span class="font-semibold text-gray-900">{{
                      activityData?.reduce((sum, d) => sum + d.newSpecies, 0) || 0
                    }}</span>
                    new species
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Bottom Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Recent Sightings -->
          <div class="lg:col-span-2">
            <Card
              class="h-full overflow-hidden transition-shadow duration-200 bg-white border border-gray-100 shadow-sm hover:shadow-md"
            >
              <CardHeader
                class="flex flex-row items-center justify-between px-6 py-5 border-b border-gray-100"
              >
                <div>
                  <CardTitle class="text-lg font-bold text-gray-900"
                    >Recent Sightings</CardTitle
                  >
                  <p class="mt-1 text-sm text-gray-500">Your latest contributions</p>
                </div>
                <Link
                  href="/sightings"
                  class="flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 hover:underline"
                >
                  View All History
                  <Icon name="arrow-right" class="w-3.5 h-3.5" />
                </Link>
              </CardHeader>
              <div class="overflow-x-auto">
                <table
                  v-if="recentSightings && recentSightings.length > 0"
                  class="w-full text-sm text-left"
                >
                  <thead
                    class="text-xs font-semibold tracking-wider text-gray-500 uppercase bg-gray-50"
                  >
                    <tr>
                      <th class="px-6 py-4">Plant Species</th>
                      <th class="px-6 py-4">Family</th>
                      <th class="px-6 py-4">Location</th>
                      <th class="px-6 py-4">Status</th>
                      <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr
                      v-for="sighting in recentSightings"
                      :key="sighting.id"
                      class="transition-colors hover:bg-gray-50/80 group"
                    >
                      <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                          <div
                            class="flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 border border-gray-200 rounded-lg shrink-0"
                          >
                            <img
                              v-if="sighting.image"
                              :src="sighting.image"
                              class="object-cover w-full h-full"
                              :alt="sighting.name"
                            />
                            <Icon v-else name="image" class="w-5 h-5 text-gray-400" />
                          </div>
                          <div>
                            <p
                              class="font-semibold text-gray-900 transition-colors group-hover:text-emerald-700"
                            >
                              {{ sighting.name }}
                            </p>
                            <p class="text-xs text-gray-500">{{ sighting.date }}</p>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-gray-600">
                        <span
                          class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded text-xs font-medium"
                          >{{ sighting.family }}</span
                        >
                      </td>
                      <td class="px-6 py-4 text-gray-600">
                        <div class="flex items-center gap-1.5">
                          <Icon name="map-pin" class="w-3.5 h-3.5 text-gray-400" />
                          {{ sighting.location }}
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div
                          class="flex items-center gap-2 text-xs font-medium"
                          :class="sighting.statusClass"
                        >
                          <div
                            class="w-2 h-2 bg-current rounded-full ring-2 ring-current ring-opacity-20"
                          ></div>
                          {{ sighting.status }}
                        </div>
                      </td>
                      <td class="px-6 py-4 text-right">
                        <Link :href="`/sightings/${sighting.id}`">
                          <Button
                            variant="ghost"
                            size="icon"
                            class="w-8 h-8 text-gray-400 hover:text-gray-900 hover:bg-gray-100"
                          >
                            <Icon name="eye" class="w-4 h-4" />
                          </Button>
                        </Link>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Empty state -->
                <div v-else class="px-6 py-12 text-center">
                  <div
                    class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-gray-50"
                  >
                    <Icon name="flower-2" class="w-8 h-8 text-gray-300" />
                  </div>
                  <h3 class="mb-1 text-sm font-semibold text-gray-900">
                    No sightings yet
                  </h3>
                  <p class="mb-4 text-sm text-gray-500">
                    Start exploring and log your first plant sighting!
                  </p>
                  <Link href="/plant-identifier">
                    <Button class="gap-2 text-white bg-emerald-600 hover:bg-emerald-700">
                      <Icon name="plus" class="w-4 h-4" />
                      Add Your First Sighting
                    </Button>
                  </Link>
                </div>
              </div>
            </Card>
          </div>

          <!-- Right Sidebar: Alerts & Actions -->
          <div class="space-y-6">
            <!-- Alerts -->
            <Card
              class="transition-shadow duration-200 bg-white border border-gray-100 shadow-sm hover:shadow-md"
            >
              <CardHeader
                class="flex flex-row items-center justify-between px-6 py-5 pb-2"
              >
                <CardTitle class="text-lg font-bold text-gray-900"
                  >Alerts & News</CardTitle
                >
                <Link
                  href="#"
                  class="text-xs font-semibold text-gray-500 hover:text-gray-900 hover:underline"
                  >View All</Link
                >
              </CardHeader>
              <CardContent class="p-0">
                <div class="divide-y divide-gray-100">
                  <div
                    v-for="(alert, index) in alerts"
                    :key="index"
                    class="p-4 transition-colors cursor-pointer hover:bg-gray-50 group"
                  >
                    <div class="flex gap-4">
                      <div class="mt-0.5 shrink-0">
                        <div
                          :class="`w-10 h-10 rounded-full flex items-center justify-center shadow-sm ${
                            alert.type === 'warning'
                              ? 'bg-amber-100 text-amber-600'
                              : alert.type === 'success'
                              ? 'bg-emerald-100 text-emerald-600'
                              : 'bg-blue-100 text-blue-600'
                          }`"
                        >
                          <Icon
                            :name="
                              alert.type === 'warning'
                                ? 'alert-triangle'
                                : alert.type === 'success'
                                ? 'tree-pine'
                                : 'info'
                            "
                            class="w-5 h-5"
                          />
                        </div>
                      </div>
                      <div>
                        <h4
                          class="text-sm font-semibold text-gray-900 transition-colors group-hover:text-emerald-700"
                        >
                          {{ alert.title }}
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500">
                          {{ alert.description }}
                        </p>
                        <p
                          class="text-[10px] text-gray-400 mt-2 font-medium uppercase tracking-wide"
                        >
                          {{ alert.time }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Quick Actions (Light Mode) -->
            <Card
              class="relative overflow-hidden transition-shadow duration-200 bg-white border border-gray-200 shadow-sm hover:shadow-md"
            >
              <div
                class="absolute inset-0 opacity-50 bg-gradient-to-br from-gray-50 to-white"
              ></div>
              <CardHeader
                class="relative z-10 flex flex-row items-center justify-between px-6 py-5 border-b border-gray-100"
              >
                <CardTitle class="text-base font-bold text-gray-900"
                  >Quick Actions</CardTitle
                >
                <div class="bg-amber-100 p-1.5 rounded-full">
                  <Icon name="zap" class="w-3.5 h-3.5 text-amber-600" />
                </div>
              </CardHeader>
              <CardContent class="relative z-10 p-6">
                <div class="grid grid-cols-2 gap-4">
                  <Link
                    v-for="action in quickActions"
                    :key="action.label"
                    :href="action.href"
                    class="block group"
                  >
                    <div
                      class="flex flex-col items-center justify-center h-full gap-3 p-4 text-center transition-all duration-200 border border-gray-100 rounded-xl bg-gray-50 hover:bg-white hover:border-emerald-200 hover:shadow-md"
                    >
                      <div
                        :class="`p-2.5 rounded-full bg-white shadow-sm ring-1 ring-gray-100 group-hover:ring-emerald-100 ${action.color}`"
                      >
                        <Icon :name="action.icon" class="w-5 h-5" />
                      </div>
                      <span
                        class="text-xs font-semibold text-gray-700 group-hover:text-gray-900"
                        >{{ action.label }}</span
                      >
                    </div>
                  </Link>
                </div>
              </CardContent>
            </Card>
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
