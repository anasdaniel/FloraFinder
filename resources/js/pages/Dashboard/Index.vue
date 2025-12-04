<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import AppLayout from "@/layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";

const plantOfTheDay = {
  name: "Bougainvillea glabra",
  commonName: "Paperflower",
  description:
    "Bougainvillea glabra, commonly known as Paperflower, is a vibrant and hardy flowering plant native to South America but widely cultivated in tropical regions," +
    " including Malaysia. It is renowned for its colorful bracts that surround its small white flowers, creating a stunning display of colors ranging from pink and " +
    "purple to red and orange.",
  conservationStatus: "Least Concern",
  image:
    // "https://images.pexels.com/photos/2568457/pexels-photo-2568457.jpeg?w=400&h=300&fit=crop",
    "https://images.pexels.com/photos/13406833/pexels-photo-13406833.jpeg?w=400&h=300&fit=crop",
};

const features = [
  {
    icon: "camera",
    title: "Instant Plant Identification",
    description:
      "Identify any plant with a simple photo using our advanced AI technology.",
  },
  {
    icon: "book-open",
    title: "Extensive Plant Database",
    description:
      "Access information about thousands of plant species native to Malaysia.",
  },
  {
    icon: "map",
    title: "Conservation Tracking",
    description:
      "Learn about conservation status and contribute to preservation efforts.",
  },
];

const popularPlants = [
  {
    name: "Rafflesia",
    scientificName: "Rafflesia arnoldii",
    image:
      "https://images.pexels.com/photos/15695205/pexels-photo-15695205.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    name: "Torch Ginger",
    scientificName: "Etlingera elatior",
    image:
      "https://images.pexels.com/photos/4141814/pexels-photo-4141814.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    name: "Highland Pitcher Plant",
    scientificName: "Nepenthes rajah",
    image:
      "https://images.pexels.com/photos/12875326/pexels-photo-12875326.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
];

// Performance UX: smooth image loading skeletons
import { ref } from "vue";
const heroCardLoaded = ref(false);
const potdImageLoaded = ref(false);
</script>

<template>
  <AppLayout title="Dashboard">
    <!-- Hero Section -->
    <section aria-labelledby="hero-heading" class="relative overflow-hidden">
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/957024/forest-trees-perspective-bright-957024.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat opacity-20 dark:opacity-10"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-br from-gray-50/95 to-white/95 dark:from-gray-950/95 dark:to-gray-900/95"
      ></div>
      <div
        class="relative z-10 mx-auto max-w-7xl px-6 py-24 sm:py-32 md:flex md:items-center md:justify-between md:gap-x-8 lg:px-8"
      >
        <div class="max-w-xl md:flex-1 md:py-12">
          <h1
            id="hero-heading"
            class="text-gray-900 text-4xl font-extrabold tracking-tight dark:text-white sm:text-5xl"
          >
            <span class="block">Discover &amp; Identify</span>
            <span class="text-gray-600 dark:text-gray-400 block">Malaysian Plants</span>
          </h1>
          <p
            class="text-gray-600 dark:text-gray-300 mt-6 text-lg font-semibold leading-8"
          >
            Explore the rich biodiversity of Malaysian flora using our advanced plant
            identification technology.
          </p>
          <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <Link
              href="/plant-identifier"
              class="group"
              aria-label="Identify plants with your camera"
            >
              <Button
                class="w-full rounded-xl bg-gradient-to-r from-gray-900 to-black hover:from-black hover:to-gray-900 text-white shadow-lg transition-all duration-200 dark:from-white dark:to-gray-100 dark:text-gray-900 dark:hover:from-gray-100 dark:hover:to-white"
              >
                <div class="flex items-center justify-center gap-2">
                  <Icon name="camera" class="h-4 w-4" />
                  <span>Identify Plants</span>
                </div>
              </Button>
            </Link>
            <Link
              href="/plant-search"
              class="group"
              aria-label="Browse the plant database"
            >
              <Button
                variant="outline"
                class="border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 w-full rounded-xl"
              >
                <div class="flex items-center justify-center gap-2">
                  <Icon name="search" class="h-4 w-4" />
                  <span>Browse Plants</span>
                </div>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Plant Image Card -->
        <div class="mt-12 flex-1 md:mt-0">
          <Card
            class="dark:bg-gray-900/80 overflow-hidden rounded-3xl border-0 bg-white/80 shadow-xl ring-1 ring-gray-200 backdrop-blur-md dark:ring-gray-800"
          >
            <CardHeader class="relative p-0">
              <img
                src="https://images.pexels.com/photos/244796/pexels-photo-244796.jpeg?auto=compress&cs=tinysrgb&w=1260"
                alt="Malaysian plants"
                class="h-64 w-full object-cover object-center transition-opacity duration-500"
                loading="eager"
                decoding="async"
                fetchpriority="high"
                @load="heroCardLoaded = true"
                :class="heroCardLoaded ? 'opacity-100' : 'opacity-0'"
              />
              <div
                v-if="!heroCardLoaded"
                class="absolute inset-0 animate-pulse bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900"
                aria-hidden="true"
              />
            </CardHeader>
            <CardContent class="p-4 text-center">
              <span
                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
              >
                <Icon name="leaf" class="mr-1 h-3 w-3" />
                1000+ Plant Species
              </span>
            </CardContent>
          </Card>
        </div>
      </div>
    </section>

    <!-- Plant of the Day -->
    <section
      aria-labelledby="potd-heading"
      class="bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8"
    >
      <div class="mx-auto max-w-5xl">
        <div class="mb-12 text-center">
          <h2
            id="potd-heading"
            class="text-gray-900 dark:text-white text-2xl font-bold tracking-tight sm:text-3xl"
          >
            Plant of the Day
          </h2>
          <p class="text-gray-500 dark:text-gray-400 mt-3">
            Explore a new Malaysian plant each day
          </p>
        </div>

        <Card
          class="dark:bg-gray-900/80 overflow-hidden rounded-3xl border-0 bg-white/90 shadow-xl ring-1 ring-gray-200 backdrop-blur-md dark:ring-gray-800"
        >
          <div class="grid grid-cols-1 md:grid-cols-12">
            <div class="relative md:col-span-5">
              <img
                :src="plantOfTheDay.image"
                :alt="plantOfTheDay.commonName"
                class="h-64 w-full object-cover transition-opacity duration-500 md:h-full md:rounded-l-3xl"
                loading="lazy"
                decoding="async"
                sizes="(min-width: 768px) 40vw, 100vw"
                @load="potdImageLoaded = true"
                :class="potdImageLoaded ? 'opacity-100' : 'opacity-0'"
              />
              <div
                v-if="!potdImageLoaded"
                class="absolute inset-0 animate-pulse bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 md:rounded-l-3xl"
                aria-hidden="true"
              />
              <div class="absolute right-4 top-4">
                <span
                  class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 shadow-sm"
                >
                  <span class="sr-only">Conservation status:</span>
                  {{ plantOfTheDay.conservationStatus }}
                </span>
              </div>
            </div>
            <div class="flex flex-col justify-center p-6 md:col-span-7 md:p-8">
              <h3 class="text-gray-900 text-2xl font-bold dark:text-white">
                {{ plantOfTheDay.commonName }}
              </h3>
              <p class="text-gray-500 dark:text-gray-400 mb-4 mt-1 text-sm italic">
                {{ plantOfTheDay.name }}
              </p>
              <p class="text-gray-600 dark:text-gray-300 text-base">
                {{ plantOfTheDay.description }}
              </p>
              <div class="mt-6">
                <Link href="/plants/mangosteen">
                  <Button
                    variant="outline"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-full shadow-sm"
                  >
                    <div class="flex items-center gap-2">
                      <Icon name="book-open" class="h-4 w-4" />
                      <span>Learn more</span>
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
    <section
      aria-labelledby="features-heading"
      class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8"
    >
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/38136/pexels-photo-38136.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat opacity-20 dark:opacity-10"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-b from-gray-50/95 to-white/95 dark:from-gray-950/95 dark:to-gray-900/95"
      ></div>
      <div class="relative z-10 mx-auto max-w-5xl">
        <div class="mb-12 text-center">
          <h2
            id="features-heading"
            class="text-gray-900 dark:text-white text-2xl font-bold tracking-tight sm:text-3xl"
          >
            Key Features
          </h2>
          <p class="text-gray-500 dark:text-gray-400 mt-3">
            Everything you need to explore Malaysian flora
          </p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="dark:bg-gray-900/60 overflow-hidden rounded-2xl border-0 bg-white/80 p-6 shadow-lg ring-1 ring-gray-200 backdrop-blur-md transition-shadow hover:shadow-xl dark:ring-gray-800"
          >
            <div class="flex flex-col items-center text-center">
              <div
                class="bg-gray-100 dark:bg-gray-800 mb-4 flex items-center justify-center rounded-full p-3 shadow-inner"
              >
                <span class="text-2xl" v-if="feature.icon === 'camera'">📷</span>
                <span class="text-2xl" v-else-if="feature.icon === 'book-open'">📚</span>
                <span class="text-2xl" v-else-if="feature.icon === 'map'">🗺️</span>
                <span class="text-2xl" v-else>🌿</span>
              </div>
              <h3 class="text-gray-900 mb-2 text-lg font-semibold dark:text-white">
                {{ feature.title }}
              </h3>
              <p class="text-gray-500 dark:text-gray-400">{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Popular Plants Section -->
    <section
      aria-labelledby="popular-heading"
      class="bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8"
    >
      <div class="mx-auto max-w-5xl">
        <div class="mb-12 text-center">
          <h2
            id="popular-heading"
            class="text-gray-900 dark:text-white text-2xl font-bold tracking-tight sm:text-3xl"
          >
            Popular Malaysian Plants
          </h2>
          <p class="text-gray-500 dark:text-gray-400 mt-3">
            Discover some of Malaysia's most iconic flora
          </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(plant, idx) in popularPlants"
            :key="plant.name"
            class="group overflow-hidden rounded-2xl border border-gray-200 shadow-lg transition-all hover:shadow-xl dark:border-gray-800"
          >
            <div class="relative h-48 overflow-hidden">
              <img
                :src="plant.image"
                :alt="plant.name"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
                decoding="async"
                sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
              />
              <div
                class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
              ></div>
            </div>
            <div class="bg-white p-4 dark:bg-gray-900">
              <h4 class="text-gray-900 font-semibold dark:text-white">
                {{ plant.name }}
              </h4>
              <p class="text-gray-500 dark:text-gray-400 text-xs italic">
                {{ plant.scientificName }}
              </p>
            </div>
          </div>
        </div>

        <div class="mt-10 text-center">
          <Link href="/explore" aria-label="Explore all plants">
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
    <section
      aria-labelledby="cta-heading"
      class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8"
    >
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/1287075/pexels-photo-1287075.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-r from-gray-900/95 to-black/95 dark:from-black/95 dark:to-gray-900/95"
      ></div>
      <div class="relative z-10 mx-auto max-w-5xl text-center">
        <h2
          id="cta-heading"
          class="text-2xl font-bold tracking-tight text-white sm:text-3xl"
        >
          Ready to discover Malaysian plants?
        </h2>
        <p class="mt-3 text-lg text-white/80">
          Start identifying plants in your surroundings today.
        </p>
        <div class="mt-8">
          <Link href="/plant-identifier" aria-label="Start identifying plants now">
            <Button
              class="text-gray-900 rounded-full bg-white shadow-lg hover:bg-gray-100"
            >
              <div class="flex items-center gap-2">
                <Icon name="camera" class="h-4 w-4" />
                <span>Start Identifying</span>
              </div>
            </Button>
          </Link>
        </div>
      </div>
    </section>
  </AppLayout>
</template>
