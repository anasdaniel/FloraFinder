<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/vue3";

const AUTH_REQUIRED_ACTIONS = [
  {
    icon: "camera",
    title: "Identify a plant",
    description: "Upload a photo and get AI-powered matches in seconds.",
    intent: "plant-id",
    accent: "emerald",
  },
  {
    icon: "map",
    title: "Explore sightings map",
    description: "Track discoveries on an interactive map with filters.",
    intent: "sightings-map",
    accent: "indigo",
  },
  {
    icon: "book-open",
    title: "Browse plant library",
    description: "Search species profiles, care notes, and threat status.",
    intent: "library",
    accent: "amber",
  },
  {
    icon: "users",
    title: "Join the community",
    description: "Ask questions, share finds, and learn together.",
    intent: "forum",
    accent: "pink",
  },
] as const;

const metrics = [
  { label: "Species catalogued", value: "1,240+", hint: "Growing daily" },
  { label: "Community posts", value: "8.7k", hint: "Tips + discussions" },
  { label: "Sightings mapped", value: "3,210", hint: "With geotags" },
];

const principles = [
  {
    title: "Clear, calm interface",
    description: "Designed around readability, spacing, and content hierarchy.",
    icon: "layout",
  },
  {
    title: "Action-first workflows",
    description: "Fast paths for identifying, logging sightings, and learning.",
    icon: "zap",
  },
  {
    title: "Community + conservation",
    description: "Share discoveries and contribute to biodiversity knowledge.",
    icon: "users",
  },
];

const steps = [
  { title: "Create a free account", description: "Save identifications and sightings across devices.", icon: "user-plus" },
  { title: "Identify or log a sighting", description: "Upload photos, add location, and keep notes.", icon: "camera" },
  { title: "Learn and share", description: "Explore profiles, ask the community, and track trends.", icon: "book-open" },
];
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-50 selection:bg-emerald-500/30">
    <Head title="Dashboard Preview" />

    <!-- Ambient Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-[20%] -left-[10%] w-[800px] h-[800px] rounded-full bg-emerald-500/10 blur-[120px]" />
      <div class="absolute top-[40%] -right-[10%] w-[600px] h-[600px] rounded-full bg-indigo-500/10 blur-[100px]" />
      <div class="absolute bottom-[-10%] left-[20%] w-[500px] h-[500px] rounded-full bg-blue-500/10 blur-[100px]" />
    </div>

    <div class="relative mx-auto flex max-w-7xl flex-col gap-12 px-6 py-8 sm:px-8 lg:px-12">
      <!-- Navbar -->
      <nav aria-label="Primary" class="sticky top-4 z-50 flex items-center justify-between rounded-full border border-white/5 bg-slate-900/60 px-4 py-3 backdrop-blur-md shadow-2xl shadow-black/20 sm:top-6 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 shadow-lg shadow-emerald-500/20">
            <Icon name="leaf" class="h-4 w-4 text-white" />
          </div>
          <span class="text-base font-bold tracking-tight text-white">FloraFinder</span>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('login')" class="hidden sm:block">
            <Button
              variant="ghost"
              class="h-9 rounded-full px-4 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white focus-visible:ring-2 focus-visible:ring-emerald-400/60 focus-visible:ring-offset-0"
            >
              Log in
            </Button>
          </Link>
          <Link :href="route('register')">
            <Button class="h-9 rounded-full bg-white px-5 text-sm font-semibold text-slate-950 shadow-lg shadow-white/10 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-0">
              Get Started
            </Button>
          </Link>
        </div>
      </nav>

      <!-- Hero Section -->
      <header class="mt-6 grid gap-10 lg:mt-10 lg:grid-cols-2 lg:items-center">
        <div class="space-y-8">
          <div class="space-y-4">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300 backdrop-blur-sm">
              <Icon name="sparkles" class="h-3.5 w-3.5" />
              Dashboard preview (guest view)
            </div>
            <h1 class="text-4xl font-bold leading-[1.08] tracking-tight text-white sm:text-5xl xl:text-6xl">
              Discover, identify, and log plants — with a dashboard built for focus.
            </h1>
            <p class="max-w-xl text-lg leading-relaxed text-slate-300/90">
              FloraFinder helps you turn photos into knowledge, sightings into maps, and curiosity into community contributions.
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Link :href="route('register')">
              <Button
                size="lg"
                class="h-12 w-full rounded-full bg-emerald-500 px-8 text-base font-semibold text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-400 transition-colors focus-visible:ring-2 focus-visible:ring-emerald-300/70 focus-visible:ring-offset-0 sm:w-auto"
              >
                Create free account
              </Button>
            </Link>
            <a
              href="#preview"
              class="inline-flex h-12 w-full items-center justify-center rounded-full border border-white/10 bg-white/5 px-8 text-base font-medium text-slate-200 backdrop-blur-sm hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/30 sm:w-auto"
            >
              See the preview
            </a>
          </div>

          <div class="grid gap-4 border-t border-white/5 pt-8 sm:grid-cols-3">
            <div v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-white/5 bg-white/5 p-4">
              <p class="text-2xl font-bold tracking-tight text-white">{{ metric.value }}</p>
              <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ metric.label }}</p>
              <p class="mt-2 text-sm text-slate-400">{{ metric.hint }}</p>
            </div>
          </div>
        </div>

        <!-- Dashboard Mockup/Visual -->
        <div id="preview" class="relative w-full lg:h-[600px]">
          <div class="tilt relative z-10 h-full w-full">
            <!-- Main Glass Card -->
            <div class="relative h-full overflow-hidden rounded-3xl border border-white/10 bg-slate-900/60 backdrop-blur-xl shadow-2xl shadow-black/50">
                <!-- Mockup Header -->
                <div class="flex items-center justify-between border-b border-white/5 bg-white/5 px-6 py-4">
                    <div class="flex items-center gap-4">
                        <div class="flex gap-1.5">
                            <div class="h-3 w-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                            <div class="h-3 w-3 rounded-full bg-amber-500/20 border border-amber-500/50"></div>
                            <div class="h-3 w-3 rounded-full bg-emerald-500/20 border border-emerald-500/50"></div>
                        </div>
                        <div class="h-6 w-32 rounded-full bg-white/5"></div>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-white/10"></div>
                </div>

                <!-- Mockup Content -->
                <div class="p-6 grid gap-6 grid-cols-2">
                    <!-- Left Col -->
                    <div class="space-y-6">
                        <div class="rounded-2xl bg-white/5 p-5 border border-white/5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="h-10 w-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <Icon name="camera" class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="h-4 w-24 bg-white/10 rounded mb-1"></div>
                                    <div class="h-3 w-16 bg-white/5 rounded"></div>
                                </div>
                            </div>
                            <div class="h-32 rounded-xl bg-gradient-to-br from-emerald-500/10 to-transparent border border-white/5 mb-3 relative overflow-hidden group">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                                    <span class="text-xs font-medium text-white">Upload Photo</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="h-3 w-20 bg-white/5 rounded"></div>
                                <div class="h-6 w-12 rounded-full bg-emerald-500/20"></div>
                            </div>
                        </div>

                         <div class="rounded-2xl bg-white/5 p-5 border border-white/5">
                             <div class="flex items-center justify-between mb-4">
                                 <span class="text-sm text-slate-400 font-medium">Recent Activity</span>
                                 <Icon name="more-horizontal" class="h-4 w-4 text-slate-500" />
                             </div>
                             <div class="space-y-3">
                                 <div v-for="i in 3" :key="i" class="flex items-center gap-3">
                                     <div class="h-8 w-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20"></div>
                                     <div class="flex-1">
                                         <div class="h-3 w-full bg-white/5 rounded mb-1"></div>
                                         <div class="h-2 w-2/3 bg-white/5 rounded opacity-50"></div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                    </div>

                    <!-- Right Col -->
                    <div class="space-y-6">
                        <div class="h-48 rounded-2xl bg-indigo-500/5 border border-white/5 p-5 relative overflow-hidden">
                             <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(99,102,241,0.15),transparent_60%)]"></div>
                             <div class="relative z-10 flex justify-between items-start">
                                 <div>
                                     <div class="h-4 w-20 bg-indigo-500/20 rounded mb-2"></div>
                                     <div class="h-8 w-12 bg-white/10 rounded"></div>
                                 </div>
                                 <Icon name="map" class="text-indigo-400" />
                             </div>
                             <div class="mt-8 flex gap-2 justify-center">
                                 <div class="h-16 w-16 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center animate-pulse">
                                     <div class="h-8 w-8 rounded-full bg-indigo-500/20"></div>
                                 </div>
                             </div>
                        </div>

                        <div class="rounded-2xl bg-white/5 p-5 border border-white/5">
                             <div class="h-4 w-32 bg-white/10 rounded mb-4"></div>
                             <div class="flex gap-2">
                                 <div class="h-20 flex-1 rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/5 border border-amber-500/10"></div>
                                 <div class="h-20 flex-1 rounded-xl bg-gradient-to-br from-pink-500/10 to-rose-500/5 border border-pink-500/10"></div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Elements -->
            <div class="float absolute -right-6 top-20 rounded-2xl bg-slate-800/90 p-4 shadow-xl shadow-black/50 border border-white/10 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
                        <Icon name="check" class="h-5 w-5 text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Species Identified</p>
                        <p class="text-xs text-emerald-400">Just now</p>
                    </div>
                </div>
            </div>

             <div class="float-delayed absolute -left-4 bottom-20 rounded-2xl bg-slate-800/90 p-4 shadow-xl shadow-black/50 border border-white/10 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full overflow-hidden border border-white/20">
                         <div class="w-full h-full bg-slate-600"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">New Sighting</p>
                        <p class="text-xs text-slate-400">@kuala_lumpur</p>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </header>

      <!-- How it works -->
      <section class="mt-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-white sm:text-3xl">How it works</h2>
          <p class="mt-2 text-slate-400">A simple flow designed for quick wins and long-term learning.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
          <Card v-for="step in steps" :key="step.title" class="border-white/5 bg-white/5">
            <CardHeader>
              <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-white/5 text-white ring-1 ring-white/10">
                <Icon :name="step.icon" class="h-6 w-6" />
              </div>
              <CardTitle class="text-lg font-semibold text-white">{{ step.title }}</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm leading-relaxed text-slate-400">{{ step.description }}</p>
            </CardContent>
          </Card>
        </div>
      </section>

      <!-- Explore tools (guest-safe) -->
      <section class="mt-12">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-2xl font-bold text-white sm:text-3xl">Explore what you’ll unlock</h2>
            <p class="mt-2 text-slate-400">These tools require an account so your discoveries stay saved.</p>
          </div>
          <div class="flex gap-3">
            <Link :href="route('login')">
              <Button variant="ghost" class="rounded-full text-slate-200 hover:bg-white/10">Sign in</Button>
            </Link>
            <Link :href="route('register')">
              <Button class="rounded-full bg-emerald-500 text-white hover:bg-emerald-400">Create account</Button>
            </Link>
          </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <Card
            v-for="action in AUTH_REQUIRED_ACTIONS"
            :key="action.title"
            class="group relative overflow-hidden border-white/5 bg-white/5 transition-all hover:-translate-y-1 hover:bg-white/[0.07] hover:shadow-xl hover:shadow-black/20"
          >
            <Link
              :href="route('register')"
              class="absolute inset-0 z-10 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
              :data-intent="action.intent"
              aria-label="Create an account to unlock this feature"
            >
              <span class="sr-only">Create an account to unlock {{ action.title }}</span>
            </Link>

            <div
              class="absolute inset-0 bg-gradient-to-br opacity-0 transition-opacity duration-500 group-hover:opacity-100"
              :class="{
                'from-emerald-500/10 to-transparent': action.accent === 'emerald',
                'from-indigo-500/10 to-transparent': action.accent === 'indigo',
                'from-amber-500/10 to-transparent': action.accent === 'amber',
                'from-pink-500/10 to-transparent': action.accent === 'pink',
              }"
            />

            <CardHeader class="relative">
              <div class="mb-3 flex items-center justify-between gap-3">
                <div
                  class="flex h-12 w-12 items-center justify-center rounded-xl ring-1 ring-white/10"
                  :class="{
                    'bg-emerald-500/10 text-emerald-300': action.accent === 'emerald',
                    'bg-indigo-500/10 text-indigo-300': action.accent === 'indigo',
                    'bg-amber-500/10 text-amber-200': action.accent === 'amber',
                    'bg-pink-500/10 text-pink-300': action.accent === 'pink',
                  }"
                >
                  <Icon :name="action.icon" class="h-6 w-6" />
                </div>
                <div class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-black/20 px-2.5 py-1 text-[11px] font-semibold text-slate-200">
                  <Icon name="lock" class="h-3.5 w-3.5" />
                  Account
                </div>
              </div>

              <CardTitle class="text-lg font-semibold text-white">{{ action.title }}</CardTitle>
            </CardHeader>
            <CardContent class="relative">
              <p class="text-sm leading-relaxed text-slate-400">{{ action.description }}</p>
              <div class="mt-4">
                <span class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-300">
                  Unlock with free account
                  <Icon name="arrow-right" class="h-4 w-4" />
                </span>
              </div>
            </CardContent>
          </Card>
        </div>
      </section>

      <!-- Design principles -->
      <section class="mt-12">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-white sm:text-3xl">Built with good UX in mind</h2>
          <p class="mt-2 text-slate-400">Less noise, better structure, and clearer next steps.</p>
        </div>
        <div class="grid gap-6 lg:grid-cols-3">
          <Card v-for="item in principles" :key="item.title" class="border-white/5 bg-white/5">
            <CardHeader>
              <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-white/5 text-white ring-1 ring-white/10">
                <Icon :name="item.icon" class="h-6 w-6" />
              </div>
              <CardTitle class="text-lg font-semibold text-white">{{ item.title }}</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm leading-relaxed text-slate-400">{{ item.description }}</p>
            </CardContent>
          </Card>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="mt-16 overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-emerald-900/50 via-slate-900 to-indigo-900/50 px-6 py-16 text-center shadow-2xl shadow-black/20 relative border border-white/5">
         <div class="absolute -top-40 -right-40 h-[400px] w-[400px] rounded-full bg-emerald-500/20 blur-[100px]"></div>
         <div class="absolute -bottom-40 -left-40 h-[400px] w-[400px] rounded-full bg-indigo-500/20 blur-[100px]"></div>

         <div class="relative z-10 mx-auto max-w-2xl space-y-8">
             <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to start logging discoveries?</h2>
             <p class="text-lg text-slate-300">Create a free account to save identifications, map sightings, and join the community.</p>
             <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                 <Link :href="route('register')">
                     <Button size="lg" class="h-12 min-w-[160px] rounded-full bg-white text-base font-bold text-slate-900 hover:bg-slate-100">
                         Get Started
                     </Button>
                 </Link>
                  <Link :href="route('login')">
                     <Button size="lg" variant="ghost" class="h-12 min-w-[160px] rounded-full text-base font-medium text-white hover:bg-white/10">
                         Sign In
                     </Button>
                 </Link>
             </div>
         </div>
      </section>

      <!-- Footer -->
      <footer class="mt-12 flex flex-col items-center justify-between gap-6 border-t border-white/5 py-8 sm:flex-row text-slate-500 text-sm">
          <p>&copy; 2025 FloraFinder. All rights reserved.</p>
          <div class="flex gap-6">
              <Link href="#" class="hover:text-slate-300">Privacy</Link>
              <Link href="#" class="hover:text-slate-300">Terms</Link>
              <Link href="#" class="hover:text-slate-300">Contact</Link>
          </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
/* Subtle tilt on pointer hover (disabled for reduced-motion users). */
.tilt {
  transform-style: preserve-3d;
  transition: transform 700ms cubic-bezier(0.2, 0.8, 0.2, 1);
}
.tilt:hover {
  transform: rotateY(-2deg) rotateX(2deg);
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}
@keyframes float-delayed {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-8px); }
}
.float {
  animation: float 6s ease-in-out infinite;
}
.float-delayed {
  animation: float-delayed 5s ease-in-out infinite;
  animation-delay: 1s;
}

@media (prefers-reduced-motion: reduce) {
  .tilt {
    transition: none;
    transform: none !important;
  }
  .float,
  .float-delayed {
    animation: none !important;
  }
}
</style>

