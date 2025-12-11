<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);

// Placeholder images - in a real app these would be assets
const heroBackground = "https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=2560&auto=format&fit=crop"; // Nature background
const heroImage = "https://images.unsplash.com/photo-1580436541340-29807187421f?q=80&w=2787&auto=format&fit=crop"; // Hibiscus
const potdImage = "https://images.unsplash.com/photo-1572454591674-2739f30d8c40?q=80&w=2787&auto=format&fit=crop"; // Bougainvillea
const plant1 = "https://images.unsplash.com/photo-1616428790326-7248e301297e?q=80&w=2835&auto=format&fit=crop"; // Rafflesia equivalent/exotic
const plant2 = "https://images.unsplash.com/photo-1596727040409-775b9f936f4d?q=80&w=2835&auto=format&fit=crop"; // Torch Ginger equivalent
const plant3 = "https://images.unsplash.com/photo-1596727147705-56a53272d560?q=80&w=2835&auto=format&fit=crop"; // Pitcher plant equivalent
</script>

<template>
  <Head title="Welcome to FloraFinder" />

  <div class="min-h-screen bg-gray-50 text-[#1b1b18] font-sans selection:bg-[#FF2D20] selection:text-white">

    <!-- Navigation -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <!-- Logo Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-700">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        <span class="text-xl font-bold tracking-tight text-gray-900">FloraFinder</span>
      </div>

      <nav class="flex items-center gap-4">
        <Link
          v-if="user"
          :href="route('dashboard')"
          class="text-sm font-medium text-gray-700 hover:text-green-700 transition"
        >
          Dashboard
        </Link>
        <template v-else>
          <Link
            :href="route('login')"
            class="text-sm font-medium text-gray-700 hover:text-green-700 transition"
          >
            Log in
          </Link>
          <Link
            :href="route('register')"
            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-full hover:bg-gray-800 transition"
          >
            Register
          </Link>
        </template>
      </nav>
    </header>

    <main>
      <!-- Hero Section -->
      <section class="relative isolate overflow-hidden pt-14">
        <img :src="heroBackground" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-20" />
        <div class="mx-auto max-w-7xl px-6 py-12 lg:py-20">
          <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="space-y-8">
            <h1 class="text-5xl lg:text-6xl font-bold leading-tight tracking-tight text-gray-900">
              Discover & Identify <br>
              <span class="text-green-700">Malaysian Plants</span>
            </h1>
            <p class="text-lg text-gray-600 max-w-lg leading-relaxed">
              Explore the rich biodiversity of Malaysian flora using our advanced plant identification technology.
            </p>
            <div class="flex flex-wrap gap-4">
              <button class="px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
                Identify Plants
              </button>
              <button class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                Browse Plants
              </button>
            </div>
          </div>
          <div class="relative">
            <div class="rounded-2xl overflow-hidden shadow-xl">
              <img :src="heroImage" alt="Malaysian Hibiscus" class="w-full h-[400px] object-cover hover:scale-105 transition duration-700" />
            </div>
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full shadow-lg border border-green-100 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
              <span class="text-sm font-semibold text-green-800">1000+ Plant Species</span>
            </div>
          </div>
        </div>
        </div>
      </section>

      <!-- Plant of the Day -->
      <section class="max-w-5xl mx-auto px-6 py-16">
        <div class="text-center mb-10">
          <h2 class="text-2xl font-bold text-gray-900">Plant of the Day</h2>
          <p class="text-gray-500 mt-2">Explore a new Malaysian plant each day</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden grid md:grid-cols-2">
          <div class="relative h-64 md:h-auto">
            <img :src="potdImage" alt="Paperflower" class="w-full h-full object-cover" />
            <div class="absolute top-4 right-4 bg-[#D1FA59] text-green-900 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
              Least Concern
            </div>
          </div>
          <div class="p-8 md:p-12 flex flex-col justify-center">
            <h3 class="text-3xl font-bold text-gray-900 mb-1">Paperflower</h3>
            <p class="text-sm text-gray-400 italic mb-4">Bougainvillea glabra</p>
            <p class="text-gray-600 mb-6 leading-relaxed">
              Bougainvillea glabra, commonly known as Paperflower, is a vibrant and hardy flowering plant native to South America but widely cultivated in tropical regions, including Malaysia. It is renowned for its colorful bracts that surround its small white flowers.
            </p>
            <div>
              <button class="px-5 py-2.5 bg-white border border-gray-200 text-gray-900 font-medium rounded-lg hover:bg-gray-50 transition flex items-center gap-2 text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Learn more
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Key Features -->
      <section class="max-w-7xl mx-auto px-6 py-16 bg-white/50">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900">Key Features</h2>
          <p class="text-gray-500 mt-4 max-w-2xl mx-auto">Everything you need to explore Malaysian flora</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Instant Plant Identification</h3>
            <p class="text-gray-500 text-sm leading-relaxed">
              Identify any plant with a simple photo using our advanced AI technology.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Extensive Plant Database</h3>
            <p class="text-gray-500 text-sm leading-relaxed">
              Access information about thousands of plant species native to Malaysia.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-6">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-blue-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Conservation Tracking</h3>
            <p class="text-gray-500 text-sm leading-relaxed">
              Learn about conservation status and contribute to preservation efforts.
            </p>
          </div>
        </div>
      </section>

      <!-- Popular Plants -->
      <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900">Popular Malaysian Plants</h2>
          <p class="text-gray-500 mt-2">Discover some of Malaysia's most iconic flora</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 mb-12">
          <!-- Card 1 -->
          <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-lg transition cursor-pointer">
            <div class="aspect-[4/3] overflow-hidden">
              <img :src="plant1" alt="Rafflesia" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
            </div>
            <div class="p-6">
              <h3 class="font-bold text-gray-900">Rafflesia</h3>
              <p class="text-xs text-gray-500 italic">Rafflesia arnoldii</p>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-lg transition cursor-pointer">
            <div class="aspect-[4/3] overflow-hidden">
              <img :src="plant2" alt="Torch Ginger" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
            </div>
            <div class="p-6">
              <h3 class="font-bold text-gray-900">Torch Ginger</h3>
              <p class="text-xs text-gray-500 italic">Etlingera elatior</p>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-lg transition cursor-pointer">
            <div class="aspect-[4/3] overflow-hidden">
              <img :src="plant3" alt="Highland Pitcher Plant" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
            </div>
            <div class="p-6">
              <h3 class="font-bold text-gray-900">Highland Pitcher Plant</h3>
              <p class="text-xs text-gray-500 italic">Nepenthes rajah</p>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 text-white font-medium rounded-full hover:bg-gray-800 transition">
            Explore All Plants
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </button>
        </div>
      </section>
    </main>

    <!-- Footer CTA -->
    <footer class="bg-[#0a0a0a] text-white py-20 px-6">
      <div class="max-w-4xl mx-auto text-center space-y-8">
        <h2 class="text-4xl font-bold">Ready to discover Malaysian plants?</h2>
        <p class="text-gray-400 text-lg">Start identifying plants in your surroundings today.</p>
        <button class="px-8 py-4 bg-white text-black font-semibold rounded-lg hover:bg-gray-100 transition inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
          </svg>
          Start Identifying
        </button>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Optional: Add custom animations or overrides here */
</style>
