<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAuthed = computed(() => Boolean(user.value));

const primaryCta = computed(() => {
  if (isAuthed.value) {
    return { label: "Identify a plant", href: route("plant-identifier"), icon: "camera" };
  }
  return { label: "Start your collection", href: route("register"), icon: "arrow-right" };
});

const secondaryCta = computed(() => {
  if (isAuthed.value) {
    return { label: "Go to dashboard", href: route("dashboard") };
  }
  return { label: "Sign in", href: route("login") };
});

// Images
const images = {
  hibiscus:
    "https://images.unsplash.com/photo-1591958911259-bee2173bdccc?q=80&w=800&auto=format&fit=crop",
  rafflesia:
    "https://images.unsplash.com/photo-1628433311474-07115b2a3b01?q=80&w=800&auto=format&fit=crop",
  pitcher:
    "https://images.unsplash.com/photo-1614594975525-e45190c55d0b?q=80&w=800&auto=format&fit=crop",
  potd:
    "https://images.unsplash.com/photo-1562664377-709f2c337eb2?q=80&w=2000&auto=format&fit=crop",
  scan:
    "https://images.unsplash.com/photo-1463936575829-25148e1db1b8?q=80&w=800&auto=format&fit=crop",
};

const stats = [
  { label: "Species", value: "1.2k+" },
  { label: "Sightings", value: "3.2k" },
  { label: "Members", value: "850+" },
];

// Simple scroll reveal effect
const reveal = ref(false);
onMounted(() => {
  setTimeout(() => {
    reveal.value = true;
  }, 100);
});
</script>

<template>
  <Head title="FloraFinder — The Digital Field Guide" />

  <div
    class="min-h-screen bg-[#FDFDFD] text-slate-900 font-sans selection:bg-emerald-200 selection:text-emerald-900 overflow-x-hidden"
  >
    <!-- Navbar -->
    <nav
      class="fixed top-0 left-0 right-0 z-50 border-b border-black/5 bg-white/80 backdrop-blur-md transition-all duration-300"
    >
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <Link :href="route('home')" class="flex items-center gap-2 group">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-950 text-white transition-transform group-hover:scale-110 group-hover:rotate-3"
          >
            <Icon name="leaf" class="h-4 w-4" />
          </div>
          <span class="text-lg font-bold tracking-tight text-slate-900">FloraFinder</span>
        </Link>

        <div class="flex items-center gap-6">
          <Link
            v-if="!isAuthed"
            :href="route('login')"
            class="text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors"
          >
            Sign in
          </Link>
          <Link :href="isAuthed ? route('dashboard') : route('register')">
            <Button
              class="rounded-full bg-emerald-950 px-6 text-white hover:bg-emerald-900 shadow-lg shadow-emerald-900/20 transition-all hover:shadow-emerald-900/40 hover:-translate-y-0.5"
            >
              {{ isAuthed ? "Dashboard" : "Get Started" }}
            </Button>
          </Link>
        </div>
      </div>
    </nav>

    <main class="pt-24">
      <!-- Hero Section -->
      <section class="relative mx-auto max-w-7xl px-6 py-12 lg:py-24">
        <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
          <!-- Text Content -->
          <div
            class="relative z-10 space-y-8 transition-all duration-1000 ease-out"
            :class="{
              'opacity-0 translate-y-10': !reveal,
              'opacity-100 translate-y-0': reveal,
            }"
          >
            <div
              class="inline-flex items-center gap-2 rounded-full border border-emerald-900/10 bg-emerald-50 px-4 py-1.5 text-sm font-medium text-emerald-800"
            >
              <span class="relative flex h-2 w-2">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span
                  class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"
                ></span>
              </span>
              Live in Malaysia
            </div>

            <h1
              class="text-6xl font-extrabold tracking-tight text-slate-950 sm:text-7xl lg:text-8xl leading-[0.9]"
            >
              Nature, <br />
              <span
                class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500"
                >Decoded.</span
              >
            </h1>

            <p class="max-w-lg text-lg leading-relaxed text-slate-600">
              Your pocket companion for Malaysia's biodiversity. Identify species
              instantly, map your findings, and contribute to a growing digital herbarium.
            </p>

            <div class="flex flex-wrap items-center gap-4">
              <Link :href="primaryCta.href">
                <Button
                  class="h-14 rounded-full bg-slate-900 px-8 text-lg font-medium text-white hover:bg-slate-800 shadow-xl shadow-slate-900/20 transition-all hover:scale-105"
                >
                  {{ primaryCta.label }}
                  <Icon :name="primaryCta.icon" class="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Link :href="secondaryCta.href" v-if="!isAuthed">
                <Button
                  variant="ghost"
                  class="h-14 rounded-full px-8 text-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                >
                  {{ secondaryCta.label }}
                </Button>
              </Link>
            </div>

            <div class="flex items-center gap-8 pt-4 border-t border-slate-100">
              <div v-for="stat in stats" :key="stat.label">
                <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">
                  {{ stat.label }}
                </p>
              </div>
            </div>
          </div>

          <!-- Visual Composition -->
          <div
            class="relative hidden lg:block h-[600px] transition-all duration-1000 delay-300 ease-out"
            :class="{
              'opacity-0 translate-x-10': !reveal,
              'opacity-100 translate-x-0': reveal,
            }"
          >
            <!-- Decorative blob -->
            <div
              class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-emerald-100/50 rounded-full blur-3xl -z-10"
            ></div>

            <!-- Floating Cards -->
            <div
              class="absolute top-10 right-10 w-64 rotate-6 transition-transform hover:rotate-0 hover:scale-105 hover:z-20 duration-500"
            >
              <div
                class="bg-white p-3 pb-12 shadow-2xl shadow-black/10 rounded-sm rotate-2"
              >
                <img
                  :src="images.hibiscus"
                  class="aspect-[4/5] w-full object-cover bg-slate-100"
                />
                <div
                  class="mt-3 font-handwriting text-center text-slate-600 text-sm font-medium"
                >
                  Hibiscus rosa-sinensis
                </div>
              </div>
            </div>

            <div
              class="absolute top-40 left-10 w-72 -rotate-3 transition-transform hover:rotate-0 hover:scale-105 hover:z-20 duration-500"
            >
              <div
                class="bg-white p-3 pb-12 shadow-2xl shadow-black/10 rounded-sm -rotate-1"
              >
                <img
                  :src="images.rafflesia"
                  class="aspect-square w-full object-cover bg-slate-100"
                />
                <div
                  class="mt-3 font-handwriting text-center text-slate-600 text-sm font-medium"
                >
                  Rafflesia arnoldii
                </div>
              </div>
            </div>

            <div
              class="absolute bottom-20 right-32 w-56 rotate-12 transition-transform hover:rotate-0 hover:scale-105 hover:z-20 duration-500"
            >
              <div
                class="bg-white p-3 pb-12 shadow-2xl shadow-black/10 rounded-sm rotate-1"
              >
                <img
                  :src="images.pitcher"
                  class="aspect-[3/4] w-full object-cover bg-slate-100"
                />
                <div
                  class="mt-3 font-handwriting text-center text-slate-600 text-sm font-medium"
                >
                  Nepenthes rajah
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Bento Grid Features -->
      <section class="bg-slate-950 py-24 text-white">
        <div class="mx-auto max-w-7xl px-6">
          <div class="mb-16 max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
              Everything you need to explore.
            </h2>
            <p class="mt-4 text-slate-400 text-lg">
              Powerful tools wrapped in a simple interface, designed for the field.
            </p>
          </div>

          <div class="grid gap-6 md:grid-cols-3 md:grid-rows-2 h-[800px] md:h-[500px]">
            <!-- Scan Card (Large) -->
            <div
              class="group relative col-span-1 md:col-span-2 md:row-span-2 overflow-hidden rounded-3xl bg-slate-900 border border-white/10 p-8 transition-colors hover:bg-slate-800/80"
            >
              <div
                class="absolute top-0 right-0 -mt-20 -mr-20 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl transition-all group-hover:bg-emerald-500/30"
              ></div>

              <div class="relative z-10 h-full flex flex-col justify-between">
                <div>
                  <div
                    class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400"
                  >
                    <Icon name="scan" class="h-6 w-6" />
                  </div>
                  <h3 class="text-2xl font-bold">Instant Identification</h3>
                  <p class="mt-2 text-slate-400 max-w-md">
                    Snap a photo and let our AI identify the species instantly. Works with
                    leaves, flowers, and bark.
                  </p>
                </div>

                <!-- Mock UI for scanning -->
                <div
                  class="mt-8 relative w-full flex-1 rounded-xl bg-black/40 border border-white/5 overflow-hidden flex items-center justify-center"
                >
                  <div
                    class="absolute inset-0 bg-cover bg-center opacity-50"
                    :style="{ backgroundImage: `url(${images.scan})` }"
                  ></div>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div
                      class="w-48 h-48 border-2 border-emerald-500/50 rounded-lg relative"
                    >
                      <div
                        class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-emerald-400 -mt-1 -ml-1"
                      ></div>
                      <div
                        class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-emerald-400 -mt-1 -mr-1"
                      ></div>
                      <div
                        class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-emerald-400 -mb-1 -ml-1"
                      ></div>
                      <div
                        class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 border-emerald-400 -mb-1 -mr-1"
                      ></div>
                      <div
                        class="absolute top-1/2 left-0 right-0 h-0.5 bg-emerald-400/50 shadow-[0_0_10px_rgba(52,211,153,0.8)] animate-scan"
                      ></div>
                    </div>
                  </div>
                  <div
                    class="absolute bottom-4 left-4 right-4 bg-black/80 backdrop-blur p-3 rounded-lg border border-white/10 flex items-center gap-3"
                  >
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-xs font-mono text-emerald-400"
                      >MATCH FOUND: 98% CONFIDENCE</span
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Map Card -->
            <div
              class="group relative overflow-hidden rounded-3xl bg-slate-900 border border-white/10 p-6 transition-colors hover:bg-slate-800/80"
            >
              <div
                class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/20 text-blue-400"
              >
                <Icon name="map" class="h-5 w-5" />
              </div>
              <h3 class="text-xl font-bold">Interactive Maps</h3>
              <p class="mt-2 text-sm text-slate-400">See what's growing near you.</p>
              <div
                class="absolute bottom-0 right-0 opacity-20 group-hover:opacity-40 transition-opacity"
              >
                <Icon name="map" class="h-32 w-32 -mb-8 -mr-8" />
              </div>
            </div>

            <!-- Library Card -->
            <div
              class="group relative overflow-hidden rounded-3xl bg-slate-900 border border-white/10 p-6 transition-colors hover:bg-slate-800/80"
            >
              <div
                class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400"
              >
                <Icon name="book-open" class="h-5 w-5" />
              </div>
              <h3 class="text-xl font-bold">Detailed Library</h3>
              <p class="mt-2 text-sm text-slate-400">
                Care guides & conservation status.
              </p>
              <div
                class="absolute bottom-0 right-0 opacity-20 group-hover:opacity-40 transition-opacity"
              >
                <Icon name="book" class="h-32 w-32 -mb-8 -mr-8" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Immersive Plant of the Day -->
      <section class="relative h-[80vh] w-full overflow-hidden">
        <div class="absolute inset-0">
          <img :src="images.potd" class="h-full w-full object-cover" />
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"
          ></div>
        </div>

        <div class="relative z-10 flex h-full items-end pb-20">
          <div class="mx-auto w-full max-w-7xl px-6">
            <div
              class="max-w-xl rounded-2xl bg-white/10 p-8 backdrop-blur-md border border-white/20 text-white"
            >
              <div class="flex items-center gap-2 text-emerald-300 mb-2">
                <Icon name="sun" class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-widest"
                  >Plant of the Day</span
                >
              </div>
              <h2 class="text-4xl font-bold tracking-tight">Paperflower</h2>
              <p class="text-lg italic text-white/80 font-serif">Bougainvillea glabra</p>
              <p class="mt-4 text-white/90 leading-relaxed">
                A vibrant, hardy flowering plant widely cultivated in tropical regions.
                Known for its colorful bracts surrounding small white flowers.
              </p>
              <div class="mt-6">
                <Link :href="isAuthed ? route('plants.index') : route('register')">
                  <Button
                    variant="outline"
                    class="border-white text-white bg-transparent hover:bg-white hover:text-slate-900 transition-colors"
                  >
                    Read full entry
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="py-24 bg-emerald-50">
        <div class="mx-auto max-w-3xl px-6 text-center">
          <h2 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
            Ready to start exploring?
          </h2>
          <p class="mt-6 text-lg text-slate-600">
            Join thousands of nature enthusiasts mapping Malaysia's flora today.
          </p>
          <div class="mt-10 flex justify-center gap-4">
            <Link :href="route('register')">
              <Button
                class="h-12 rounded-full bg-slate-900 px-8 text-white hover:bg-slate-800 shadow-xl"
              >
                Create free account
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-12">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 sm:flex-row"
      >
        <div class="flex items-center gap-2">
          <div
            class="flex h-6 w-6 items-center justify-center rounded bg-emerald-950 text-white"
          >
            <Icon name="leaf" class="h-3 w-3" />
          </div>
          <span class="font-bold text-slate-900">FloraFinder</span>
        </div>
        <p class="text-sm text-slate-500">
          &copy; 2025 FloraFinder. All rights reserved.
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes scan {
  0%,
  100% {
    top: 10%;
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  50% {
    top: 90%;
  }
  90% {
    opacity: 1;
  }
}

.animate-scan {
  animation: scan 3s ease-in-out infinite;
}

.font-handwriting {
  font-family: "Courier New", Courier, monospace; /* Fallback for a handwriting font */
}
</style>
