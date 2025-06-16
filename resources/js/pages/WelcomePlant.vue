<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Icon from "@/components/Icon.vue";

const plantOfTheDay = {
  name: "Garcinia mangostana",
  commonName: "Mangosteen",
  description:
    'Native to tropical Malaysia, the mangosteen produces sweet-tart fruit with thick purple rind. Its distinctive appearance and delicious flavor have earned it the nickname "Queen of Fruits".',
  conservationStatus: "Least Concern",
  image:
    // "https://images.pexels.com/photos/2127433/pexels-photo-2127433.jpeg?auto=compress&cs=tinysrgb&w=1260",
    "https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?w=400&h=300&fit=crop",
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
      "https://images.pexels.com/photos/12625630/pexels-photo-12625630.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    name: "Torch Ginger",
    scientificName: "Etlingera elatior",
    image:
      "https://images.pexels.com/photos/13284551/pexels-photo-13284551.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
  {
    name: "Highland Pitcher Plant",
    scientificName: "Nepenthes rajah",
    image:
      "https://images.pexels.com/photos/756880/pexels-photo-756880.jpeg?auto=compress&cs=tinysrgb&w=1260",
  },
];
</script>

<template>
  <AppLayout title="FloraFinder">
    <!-- Hero Section -->
    <div class="relative overflow-hidden">
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/957024/forest-trees-perspective-bright-957024.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat opacity-30 dark:opacity-20"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-br from-sage-100/90 to-moss-100/90 dark:from-sage-900/90 dark:to-moss-950/90"
      ></div>
      <div
        class="relative z-10 px-6 py-24 mx-auto max-w-7xl sm:py-32 md:flex md:items-center md:justify-between md:gap-x-8 lg:px-8"
      >
        <div class="max-w-xl md:flex-1 md:py-12">
          <h1
            class="text-4xl font-extrabold tracking-tight text-moss-900 dark:text-white sm:text-5xl"
          >
            <span class="block">Discover &amp; Identify</span>
            <span class="block text-sage-600 dark:text-sage-400">Malaysian Plants</span>
          </h1>
          <p class="mt-6 text-lg leading-8 text-sage-700 dark:text-sage-300">
            Explore the rich biodiversity of Malaysian flora using our advanced plant
            identification technology.
          </p>
          <div class="flex flex-col gap-4 mt-10 sm:flex-row">
            <Link href="/plant-identifier" class="group">
              <Button
                class="w-full text-white transition-all duration-200 shadow-lg bg-gradient-to-r from-moss-600 to-sage-600 hover:from-moss-700 hover:to-sage-700 dark:from-moss-800 dark:to-sage-800 dark:hover:from-moss-700 dark:hover:to-sage-700 dark:text-white rounded-xl"
              >
                <div class="flex items-center justify-center gap-2">
                  <Icon name="camera" class="w-4 h-4" />
                  <span>Identify Plants</span>
                </div>
              </Button>
            </Link>
            <Link href="/explore">
              <Button
                variant="outline"
                class="w-full rounded-xl border-moss-300 dark:border-moss-700 hover:bg-moss-50 dark:hover:bg-moss-900/30"
              >
                <div class="flex items-center justify-center gap-2">
                  <Icon name="search" class="w-4 h-4" />
                  <span>Browse Plants</span>
                </div>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Plant Image Card -->
        <div class="flex-1 mt-12 md:mt-0">
          <Card
            class="overflow-hidden border-0 shadow-xl rounded-3xl backdrop-blur-md bg-white/70 dark:bg-moss-900/60 ring-1 ring-gray-200 dark:ring-gray-800"
          >
            <CardHeader class="p-0">
              <img
                src="https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?"
                alt="Malaysian plants"
                class="object-cover object-center w-full h-64"
              />
            </CardHeader>
            <CardContent class="p-4 text-center">
              <span
                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
              >
                <Icon name="leaf" class="w-3 h-3 mr-1" />
                15,000+ Plant Species
              </span>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Plant of the Day -->
    <div class="px-4 py-16 bg-white dark:bg-gray-950 sm:px-6 lg:px-8">
      <div class="max-w-5xl mx-auto">
        <div class="mb-12 text-center">
          <h2
            class="text-2xl font-bold tracking-tight text-moss-900 dark:text-moss-100 sm:text-3xl"
          >
            Plant of the Day
          </h2>
          <p class="mt-3 text-sage-600 dark:text-sage-400">
            Explore a new Malaysian plant each day
          </p>
        </div>

        <Card
          class="overflow-hidden border-0 shadow-xl rounded-3xl backdrop-blur-md bg-white/80 dark:bg-sage-900/60 ring-1 ring-gray-200 dark:ring-gray-800"
        >
          <div class="grid grid-cols-1 md:grid-cols-12">
            <div class="relative md:col-span-5">
              <img
                :src="plantOfTheDay.image"
                :alt="plantOfTheDay.commonName"
                class="object-cover w-full h-64 md:h-full md:rounded-l-3xl"
              />
              <div class="absolute top-4 right-4">
                <span
                  class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 shadow-sm"
                >
                  {{ plantOfTheDay.conservationStatus }}
                </span>
              </div>
            </div>
            <div class="flex flex-col justify-center p-6 md:col-span-7 md:p-8">
              <h3 class="text-2xl font-bold text-sage-900 dark:text-white">
                {{ plantOfTheDay.commonName }}
              </h3>
              <p class="mt-1 mb-4 text-sm italic text-sage-500 dark:text-sage-400">
                {{ plantOfTheDay.name }}
              </p>
              <p class="text-base text-sage-700 dark:text-sage-300">
                {{ plantOfTheDay.description }}
              </p>
              <div class="mt-6">
                <Link href="/plants/mangosteen">
                  <Button
                    variant="outline"
                    class="rounded-full shadow-sm hover:bg-sage-50 dark:hover:bg-sage-900/30"
                  >
                    <div class="flex items-center gap-2">
                      <Icon name="book-open" class="w-4 h-4" />
                      <span>Learn more</span>
                    </div>
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Features Section -->
    <div class="relative px-4 py-16 overflow-hidden sm:px-6 lg:px-8">
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/38136/pexels-photo-38136.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat opacity-20 dark:opacity-10"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-b from-sage-50/90 to-white/90 dark:from-sage-950/90 dark:to-gray-950/90"
      ></div>
      <div class="relative z-10 max-w-5xl mx-auto">
        <div class="mb-12 text-center">
          <h2
            class="text-2xl font-bold tracking-tight text-moss-900 dark:text-moss-100 sm:text-3xl"
          >
            Key Features
          </h2>
          <p class="mt-3 text-sage-600 dark:text-sage-400">
            Everything you need to explore Malaysian flora
          </p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="p-6 overflow-hidden transition-shadow border-0 shadow-lg rounded-2xl backdrop-blur-md bg-white/70 dark:bg-sage-900/40 ring-1 ring-gray-200 dark:ring-gray-800 hover:shadow-xl"
          >
            <div class="flex flex-col items-center text-center">
              <div
                class="flex items-center justify-center p-3 mb-4 rounded-full shadow-inner bg-moss-100 dark:bg-moss-900/60"
              >
                <span class="text-2xl" v-if="feature.icon === 'camera'">📷</span>
                <span class="text-2xl" v-else-if="feature.icon === 'book-open'">📚</span>
                <span class="text-2xl" v-else-if="feature.icon === 'map'">🗺️</span>
                <span class="text-2xl" v-else>🌿</span>
              </div>
              <h3 class="mb-2 text-lg font-semibold text-moss-900 dark:text-white">
                {{ feature.title }}
              </h3>
              <p class="text-sage-600 dark:text-sage-400">{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Popular Plants Section -->
    <div class="px-4 py-16 bg-white dark:bg-gray-950 sm:px-6 lg:px-8">
      <div class="max-w-5xl mx-auto">
        <div class="mb-12 text-center">
          <h2
            class="text-2xl font-bold tracking-tight text-moss-900 dark:text-moss-100 sm:text-3xl"
          >
            Popular Malaysian Plants
          </h2>
          <p class="mt-3 text-sage-600 dark:text-sage-400">
            Discover some of Malaysia's most iconic flora
          </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="plant in popularPlants"
            :key="plant.name"
            class="overflow-hidden transition-all border border-gray-200 shadow-lg group rounded-2xl hover:shadow-xl dark:border-gray-800"
          >
            <div class="relative h-48 overflow-hidden">
              <img
                :src="plant.image"
                :alt="plant.name"
                class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
              />
              <div
                class="absolute inset-0 transition-opacity opacity-0 bg-gradient-to-t from-black/60 to-transparent group-hover:opacity-100"
              ></div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-900">
              <h4 class="font-semibold text-moss-900 dark:text-white">
                {{ plant.name }}
              </h4>
              <p class="text-xs italic text-sage-500 dark:text-sage-400">
                {{ plant.scientificName }}
              </p>
            </div>
          </div>
        </div>

        <div class="mt-10 text-center">
          <Link href="/explore">
            <Button class="rounded-full shadow-sm">
              <div class="flex items-center gap-2">
                <span>Explore All Plants</span>
                <Icon name="arrow-right" class="w-4 h-4" />
              </div>
            </Button>
          </Link>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="relative px-4 py-16 overflow-hidden sm:px-6 lg:px-8">
      <div
        class="absolute inset-0 bg-[url('https://images.pexels.com/photos/1287075/pexels-photo-1287075.jpeg?auto=compress&cs=tinysrgb&w=1260')] bg-cover bg-center bg-no-repeat"
      ></div>
      <div
        class="absolute inset-0 bg-gradient-to-r from-moss-600/90 to-sage-600/90 dark:from-moss-900/90 dark:to-sage-900/90"
      ></div>
      <div class="relative z-10 max-w-5xl mx-auto text-center">
        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
          Ready to discover Malaysian plants?
        </h2>
        <p class="mt-3 text-lg text-white/80">
          Start identifying plants in your surroundings today.
        </p>
        <div class="mt-8">
          <Link href="/plant-identifier">
            <Button
              class="bg-white rounded-full shadow-lg text-moss-800 hover:bg-white/90"
            >
              <div class="flex items-center gap-2">
                <Icon name="camera" class="w-4 h-4" />
                <span>Start Identifying</span>
              </div>
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
