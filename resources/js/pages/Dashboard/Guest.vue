<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/vue3";
import { reactive, ref } from "vue";

const heroBackground =
  "https://images.pexels.com/photos/957024/forest-trees-perspective-bright-957024.jpeg?auto=compress&cs=tinysrgb&w=1260";
const heroCardImage =
  "https://images.pexels.com/photos/244796/pexels-photo-244796.jpeg?auto=compress&cs=tinysrgb&w=1260";
const featuresBackground =
  "https://images.pexels.com/photos/38136/pexels-photo-38136.jpeg?auto=compress&cs=tinysrgb&w=1260";
const ctaBackground =
  "https://images.pexels.com/photos/1287075/pexels-photo-1287075.jpeg?auto=compress&cs=tinysrgb&w=1260";

const plantOfTheDay = {
  name: "Bougainvillea glabra",
  commonName: "Paperflower",
  description:
    "Bougainvillea glabra, commonly known as Paperflower, is a vibrant and hardy flowering plant widely cultivated in tropical regions. It is renowned for its colorful bracts that surround its small white flowers.",
  conservationStatus: "Least Concern",
  image:
    "https://images.pexels.com/photos/13406833/pexels-photo-13406833.jpeg?w=400&h=300&fit=crop",
};

const features = [
  {
    icon: "camera",
    title: "Instant Plant Identification",
    description: "Identify any plant with a simple photo using our advanced AI technology.",
  },
  {
    icon: "book-open",
    title: "Extensive Plant Database",
    description: "Access information about thousands of plant species native to Malaysia.",
  },
  {
    icon: "map",
    title: "Conservation Tracking",
    description: "Learn about conservation status and contribute to preservation efforts.",
  },
];

const popularPlants = [
  {
    key: "rafflesia",
    name: "Rafflesia",
    scientificName: "Rafflesia arnoldii",
    image: "https://images.pexels.com/photos/15695205/pexels-photo-15695205.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    key: "torch-ginger",
    name: "Torch Ginger",
    scientificName: "Etlingera elatior",
    image: "https://images.pexels.com/photos/4141814/pexels-photo-4141814.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    key: "pitcher-plant",
    name: "Highland Pitcher Plant",
    scientificName: "Nepenthes rajah",
    image: "https://images.pexels.com/photos/12875326/pexels-photo-12875326.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
];

const heroCardLoaded = ref(false);
const potdImageLoaded = ref(false);

const imgFailed = reactive<Record<string, boolean>>({});
function markImgFailed(key: string) {
  imgFailed[key] = true;
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 selection:bg-emerald-200 selection:text-slate-950 dark:bg-gray-950 dark:text-white">
    <Head title="Dashboard Preview" />

    <!-- Top bar (kept minimal for guests, but aligned with dashboard tone) -->
    <header class="sticky top-0 z-40 border-b border-black/5 bg-white/70 backdrop-blur dark:border-white/10 dark:bg-gray-950/60">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
            <Icon name="leaf" class="h-5 w-5" />
          </div>
          <div>
            <p class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white">FloraFinder</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Dashboard preview</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('login')">
            <Button variant="ghost">Log in</Button>
          </Link>
          <Link :href="route('register')">
            <Button class="bg-gray-900 text-white hover:bg-black dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
              Create account
            </Button>
          </Link>
        </div>
      </div>
    </header>

    <main>
      <!-- Hero (mirrors Dashboard/Index.vue structure) -->
      <section aria-labelledby="hero-heading" class="relative overflow-hidden">
        <div
          class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20 dark:opacity-10"
          :style="{ backgroundImage: `url('${heroBackground}')` }"
        />
        <div class="absolute inset-0 bg-gradient-to-br from-gray-50/95 to-white/95 dark:from-gray-950/95 dark:to-gray-900/95" />
        <div class="relative z-10 mx-auto max-w-7xl px-6 py-20 sm:py-28 md:flex md:items-center md:justify-between md:gap-x-8 lg:px-8">
          <div class="max-w-xl md:flex-1 md:py-10">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-600/15 bg-emerald-600/10 px-3 py-1 text-xs font-semibold text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200">
              <Icon name="sparkles" class="h-4 w-4" />
              Preview mode · actions require an account
            </div>
            <h1 id="hero-heading" class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
              <span class="block">Discover &amp; Identify</span>
              <span class="block text-gray-600 dark:text-gray-400">Malaysian Plants</span>
            </h1>
            <p class="mt-6 text-lg font-semibold leading-8 text-gray-600 dark:text-gray-300">
              Explore the rich biodiversity of Malaysian flora using our advanced plant identification technology.
            </p>
            <div class="mt-10 flex flex-col gap-4 sm:flex-row">
              <Link :href="route('register')" class="group" aria-label="Create an account to identify plants">
                <Button
                  class="w-full rounded-xl bg-gradient-to-r from-gray-900 to-black text-white shadow-lg transition-all duration-200 hover:from-black hover:to-gray-900 dark:from-white dark:to-gray-100 dark:text-gray-900 dark:hover:from-gray-100 dark:hover:to-white"
                >
                  <div class="flex items-center justify-center gap-2">
                    <Icon name="camera" class="h-4 w-4" />
                    <span>Identify Plants</span>
                    <Icon name="lock" class="h-4 w-4 opacity-80" />
                  </div>
                </Button>
              </Link>
              <Link :href="route('register')" class="group" aria-label="Create an account to browse plants">
                <Button
                  variant="outline"
                  class="w-full rounded-xl border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50"
                >
                  <div class="flex items-center justify-center gap-2">
                    <Icon name="search" class="h-4 w-4" />
                    <span>Browse Plants</span>
                    <Icon name="lock" class="h-4 w-4 opacity-60" />
                  </div>
                </Button>
              </Link>
            </div>
          </div>

          <!-- Plant Image Card (same look as real dashboard) -->
          <div class="mt-12 flex-1 md:mt-0">
            <Card class="overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl ring-1 ring-gray-200 backdrop-blur-md dark:bg-gray-900/80 dark:ring-gray-800">
              <CardHeader class="relative p-0">
                <img
                  v-if="!imgFailed.heroCard"
                  :src="heroCardImage"
                  alt="Malaysian plants"
                  class="h-64 w-full object-cover object-center transition-opacity duration-500"
                  loading="eager"
                  decoding="async"
                  fetchpriority="high"
                  @load="heroCardLoaded = true"
                  @error="markImgFailed('heroCard')"
                  :class="heroCardLoaded ? 'opacity-100' : 'opacity-0'"
                />
                <div
                  v-else
                  class="flex h-64 w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900"
                  role="img"
                  aria-label="Photo unavailable"
                >
                  <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:text-emerald-200 dark:ring-white/10">
                      <Icon name="image" class="h-6 w-6" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Photo unavailable</p>
                  </div>
                </div>
                <div
                  v-if="!heroCardLoaded && !imgFailed.heroCard"
                  class="absolute inset-0 animate-pulse bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900"
                  aria-hidden="true"
                />
              </CardHeader>
              <CardContent class="p-4 text-center">
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-500/10 dark:text-green-200">
                  <Icon name="leaf" class="mr-1 h-3 w-3" />
                  1000+ Plant Species
                </span>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      <!-- Plant of the Day -->
      <section aria-labelledby="potd-heading" class="bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
          <div class="mb-12 text-center">
            <h2 id="potd-heading" class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Plant of the Day</h2>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Explore a new Malaysian plant each day</p>
          </div>

          <Card class="overflow-hidden rounded-3xl border-0 bg-white/90 shadow-xl ring-1 ring-gray-200 backdrop-blur-md dark:bg-gray-900/80 dark:ring-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-12">
              <div class="relative md:col-span-5">
                <img
                  v-if="!imgFailed.potd"
                  :src="plantOfTheDay.image"
                  :alt="plantOfTheDay.commonName"
                  class="h-64 w-full object-cover transition-opacity duration-500 md:h-full md:rounded-l-3xl"
                  loading="lazy"
                  decoding="async"
                  sizes="(min-width: 768px) 40vw, 100vw"
                  @load="potdImageLoaded = true"
                  @error="markImgFailed('potd')"
                  :class="potdImageLoaded ? 'opacity-100' : 'opacity-0'"
                />
                <div
                  v-else
                  class="flex h-64 w-full items-center justify-center bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 md:h-full md:rounded-l-3xl"
                  role="img"
                  aria-label="Photo unavailable"
                >
                  <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:text-emerald-200 dark:ring-white/10">
                      <Icon name="image" class="h-6 w-6" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Photo unavailable</p>
                  </div>
                </div>
                <div
                  v-if="!potdImageLoaded && !imgFailed.potd"
                  class="absolute inset-0 animate-pulse bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 md:rounded-l-3xl"
                  aria-hidden="true"
                />
                <div class="absolute right-4 top-4">
                  <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 shadow-sm dark:bg-yellow-500/10 dark:text-yellow-200">
                    <span class="sr-only">Conservation status:</span>
                    {{ plantOfTheDay.conservationStatus }}
                  </span>
                </div>
              </div>
              <div class="flex flex-col justify-center p-6 md:col-span-7 md:p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ plantOfTheDay.commonName }}</h3>
                <p class="mb-4 mt-1 text-sm italic text-gray-500 dark:text-gray-400">{{ plantOfTheDay.name }}</p>
                <p class="text-base text-gray-600 dark:text-gray-300">{{ plantOfTheDay.description }}</p>
                <div class="mt-6">
                  <Link :href="route('register')">
                    <Button variant="outline" class="rounded-full shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800/50">
                      <div class="flex items-center gap-2">
                        <Icon name="book-open" class="h-4 w-4" />
                        <span>Learn more</span>
                        <Icon name="lock" class="h-4 w-4 opacity-60" />
                      </div>
                    </Button>
                  </Link>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </section>

      <!-- Features Section -->
      <section aria-labelledby="features-heading" class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8">
        <div
          class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20 dark:opacity-10"
          :style="{ backgroundImage: `url('${featuresBackground}')` }"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50/95 to-white/95 dark:from-gray-950/95 dark:to-gray-900/95" />
        <div class="relative z-10 mx-auto max-w-5xl">
          <div class="mb-12 text-center">
            <h2 id="features-heading" class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Key Features</h2>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Everything you need to explore Malaysian flora</p>
          </div>

          <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div
              v-for="feature in features"
              :key="feature.title"
              class="overflow-hidden rounded-2xl border-0 bg-white/80 p-6 shadow-lg ring-1 ring-gray-200 backdrop-blur-md transition-shadow hover:shadow-xl dark:bg-gray-900/60 dark:ring-gray-800"
            >
              <div class="flex flex-col items-center text-center">
                <div class="mb-4 flex items-center justify-center rounded-full bg-gray-100 p-3 shadow-inner dark:bg-gray-800">
                  <span class="text-2xl" v-if="feature.icon === 'camera'">📷</span>
                  <span class="text-2xl" v-else-if="feature.icon === 'book-open'">📚</span>
                  <span class="text-2xl" v-else-if="feature.icon === 'map'">🗺️</span>
                  <span class="text-2xl" v-else>🌿</span>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ feature.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Popular Plants Section -->
      <section aria-labelledby="popular-heading" class="bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
          <div class="mb-12 text-center">
            <h2 id="popular-heading" class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Popular Malaysian Plants</h2>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Discover some of Malaysia's most iconic flora</p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="plant in popularPlants"
              :key="plant.key"
              class="group relative overflow-hidden rounded-2xl border border-gray-200 shadow-lg transition-all hover:shadow-xl dark:border-gray-800"
            >
              <Link
                :href="route('register')"
                class="absolute inset-0 z-10 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                aria-label="Create an account to view this plant"
              >
                <span class="sr-only">Create an account to view {{ plant.name }}</span>
              </Link>
              <div class="relative h-48 overflow-hidden">
                <img
                  v-if="!imgFailed[plant.key]"
                  :src="plant.image"
                  :alt="plant.name"
                  class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                  loading="lazy"
                  decoding="async"
                  sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                  @error="markImgFailed(plant.key)"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900">
                  <div class="text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:text-emerald-200 dark:ring-white/10">
                      <Icon name="image" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-xs font-semibold text-gray-900 dark:text-white">Photo unavailable</p>
                  </div>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
              </div>
              <div class="bg-white p-4 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ plant.name }}</h4>
                    <p class="text-xs italic text-gray-500 dark:text-gray-400">{{ plant.scientificName }}</p>
                  </div>
                  <span class="inline-flex items-center gap-1 rounded-full bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white dark:bg-white dark:text-gray-900">
                    <Icon name="lock" class="h-3.5 w-3.5" />
                    Account
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-10 text-center">
            <Link :href="route('register')" aria-label="Create an account to explore all plants">
              <Button class="rounded-full shadow-sm">
                <div class="flex items-center gap-2">
                  <span>Explore All Plants</span>
                  <Icon name="arrow-right" class="h-4 w-4" />
                </div>
              </Button>
            </Link>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section aria-labelledby="cta-heading" class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8">
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" :style="{ backgroundImage: `url('${ctaBackground}')` }" />
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900/95 to-black/95 dark:from-black/95 dark:to-gray-900/95" />
        <div class="relative z-10 mx-auto max-w-5xl text-center">
          <h2 id="cta-heading" class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Ready to discover Malaysian plants?</h2>
          <p class="mt-3 text-lg text-white/80">Create an account to start identifying plants and saving your discoveries.</p>
          <div class="mt-8">
            <Link :href="route('register')" aria-label="Create an account to start identifying plants">
              <Button class="rounded-full bg-white text-gray-900 shadow-lg hover:bg-gray-100">
                <div class="flex items-center gap-2">
                  <Icon name="camera" class="h-4 w-4" />
                  <span>Start Identifying</span>
                </div>
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
/* Intentionally empty: page styling uses Tailwind to stay consistent with Dashboard/Index.vue. */
</style>

