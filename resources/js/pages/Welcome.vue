<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { reactive } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAuthed = computed(() => Boolean(user.value));

const imgFailed = reactive<Record<string, boolean>>({});
function markImgFailed(key: string) {
  imgFailed[key] = true;
}

const primaryCta = computed(() => {
  if (isAuthed.value) {
    return { label: "Identify a plant", href: route("plant-identifier"), icon: "camera" };
  }
  return { label: "Create free account", href: route("register"), icon: "user-plus" };
});

const secondaryCta = computed(() => {
  if (isAuthed.value) {
    return { label: "Open dashboard", href: route("dashboard"), icon: "layout" };
  }
  return { label: "See dashboard preview", href: route("dashboard.preview"), icon: "sparkles" };
});

// Placeholder images - in a real app these would be assets
const heroBackground = "https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=2560&auto=format&fit=crop"; // Nature background
const heroImage = "https://images.unsplash.com/photo-1580436541340-29807187421f?q=80&w=2787&auto=format&fit=crop"; // Hibiscus
const potdImage = "https://images.unsplash.com/photo-1572454591674-2739f30d8c40?q=80&w=2787&auto=format&fit=crop"; // Bougainvillea
const plant1 = "https://images.unsplash.com/photo-1616428790326-7248e301297e?q=80&w=2835&auto=format&fit=crop"; // Rafflesia equivalent/exotic
const plant2 = "https://images.unsplash.com/photo-1596727040409-775b9f936f4d?q=80&w=2835&auto=format&fit=crop"; // Torch Ginger equivalent
const plant3 = "https://images.unsplash.com/photo-1596727147705-56a53272d560?q=80&w=2835&auto=format&fit=crop"; // Pitcher plant equivalent

const stats = [
  { label: "Species catalogued", value: "1,240+" },
  { label: "Sightings mapped", value: "3,210" },
  { label: "Community posts", value: "8.7k" },
];

const features = computed(() => {
  const lockedHref = route("register");
  return [
    {
      icon: "camera",
      title: "Instant identification",
      description: "Upload a photo and get matches with confidence scoring and taxonomy.",
      href: isAuthed.value ? route("plant-identifier") : lockedHref,
      locked: !isAuthed.value,
    },
    {
      icon: "map",
      title: "Sightings on a map",
      description: "Document discoveries and explore hotspots with an interactive map.",
      href: isAuthed.value ? route("sightings.map") : lockedHref,
      locked: !isAuthed.value,
    },
    {
      icon: "book-open",
      title: "Plant library",
      description: "Browse profiles, care details, and conservation status in one place.",
      href: isAuthed.value ? route("plants.index") : lockedHref,
      locked: !isAuthed.value,
    },
  ] as const;
});

const popularPlants = [
  { key: "hibiscus", name: "Bunga Raya", scientific: "Hibiscus rosa-sinensis", image: heroImage },
  { key: "rafflesia", name: "Rafflesia", scientific: "Rafflesia arnoldii", image: plant1 },
  { key: "pitcher-plant", name: "Periuk Kera", scientific: "Nepenthes rajah", image: plant3 },
];
</script>

<template>
  <Head title="Welcome to FloraFinder" />

  <div class="min-h-screen bg-gradient-to-b from-white via-white to-emerald-50/40 text-slate-950 font-sans selection:bg-emerald-200 selection:text-slate-950">
    <a
      href="#main"
      class="sr-only focus:not-sr-only focus:fixed focus:left-6 focus:top-6 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900 focus:shadow-lg"
    >
      Skip to content
    </a>

    <!-- Subtle background -->
    <div class="pointer-events-none fixed inset-0 -z-10">
      <div class="absolute -top-32 -left-40 h-[420px] w-[420px] rounded-full bg-emerald-300/25 blur-3xl" />
      <div class="absolute top-24 -right-48 h-[520px] w-[520px] rounded-full bg-indigo-300/15 blur-3xl" />
      <div class="absolute bottom-[-140px] left-1/3 h-[520px] w-[520px] rounded-full bg-teal-300/15 blur-3xl" />
    </div>

    <!-- Navigation -->
    <header class="sticky top-0 z-40 border-b border-black/5 bg-white/70 backdrop-blur">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
        <Link :href="route('home')" class="flex items-center gap-2 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
            <Icon name="leaf" class="h-5 w-5" />
          </span>
          <span class="text-base font-bold tracking-tight text-slate-950">FloraFinder</span>
        </Link>

        <nav aria-label="Primary" class="flex items-center gap-2 sm:gap-3">
          <Link v-if="isAuthed" :href="route('dashboard')" class="hidden sm:block">
            <Button variant="ghost">Dashboard</Button>
          </Link>
          <Link v-else :href="route('dashboard.preview')" class="hidden sm:block">
            <Button variant="ghost">Preview</Button>
          </Link>
          <Link :href="route('login')">
            <Button variant="ghost">Log in</Button>
          </Link>
          <Link :href="route('register')">
            <Button class="bg-slate-900 text-white hover:bg-slate-800">Register</Button>
          </Link>
        </nav>
      </div>
    </header>

    <main id="main">
      <!-- Hero Section -->
      <section class="relative isolate overflow-hidden">
        <img :src="heroBackground" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-[0.08]" />
        <div class="mx-auto max-w-7xl px-6 py-12 lg:py-20">
          <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
            <div class="space-y-8">
              <div class="inline-flex items-center gap-2 rounded-full border border-emerald-600/15 bg-emerald-600/10 px-3 py-1 text-xs font-semibold text-emerald-800">
                <Icon name="sparkles" class="h-3.5 w-3.5" />
                Malaysia-first plant discovery
              </div>
              <div class="space-y-4">
                <h1 class="text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                  Discover and identify Malaysian plants — with a dashboard built for focus.
                </h1>
                <p class="max-w-xl text-lg leading-relaxed text-slate-600">
                  Turn photos into knowledge, sightings into maps, and curiosity into community contributions.
                </p>
              </div>

              <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link :href="primaryCta.href">
                  <Button class="h-11 w-full gap-2 rounded-full bg-slate-900 text-white hover:bg-slate-800 sm:w-auto">
                    <Icon :name="primaryCta.icon" class="h-4 w-4" />
                    {{ primaryCta.label }}
                  </Button>
                </Link>
                <Link :href="secondaryCta.href">
                  <Button variant="outline" class="h-11 w-full gap-2 rounded-full sm:w-auto">
                    <Icon :name="secondaryCta.icon" class="h-4 w-4" />
                    {{ secondaryCta.label }}
                  </Button>
                </Link>
              </div>

              <dl class="grid gap-4 pt-2 sm:grid-cols-3">
                <div v-for="s in stats" :key="s.label" class="rounded-2xl border border-black/5 bg-white/70 p-4 shadow-sm">
                  <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ s.label }}</dt>
                  <dd class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ s.value }}</dd>
                </div>
              </dl>
            </div>

            <div class="relative">
              <div class="overflow-hidden rounded-3xl border border-black/5 bg-white shadow-xl">
                <img
                  v-if="!imgFailed.hero"
                  :src="heroImage"
                  alt="Malaysian Hibiscus"
                  class="h-[420px] w-full object-cover"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                  @error="markImgFailed('hero')"
                />
                <div
                  v-else
                  class="flex h-[420px] w-full items-center justify-center bg-gradient-to-br from-emerald-100 via-white to-indigo-100"
                  role="img"
                  aria-label="Plant photo unavailable"
                >
                  <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5">
                      <Icon name="image" class="h-7 w-7" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-900">Photo unavailable</p>
                    <p class="mt-1 text-xs text-slate-600">We’ll show a placeholder instead.</p>
                  </div>
                </div>
              </div>
              <div class="absolute bottom-5 left-5 rounded-2xl border border-black/5 bg-white/90 px-4 py-3 shadow-lg backdrop-blur">
                <div class="flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600/10 text-emerald-700">
                    <Icon name="leaf" class="h-5 w-5" />
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-slate-950">Personalized dashboard</p>
                    <p class="text-xs text-slate-600">Save identifications + sightings</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Features -->
      <section class="mx-auto max-w-7xl px-6 py-14">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-3xl font-bold tracking-tight text-slate-950">What you can do</h2>
          <p class="mt-3 text-slate-600">Three fast workflows, designed with clear next steps and low friction.</p>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
          <Card v-for="f in features" :key="f.title" class="relative overflow-hidden border-black/5 bg-white/70 shadow-sm backdrop-blur">
            <Link
              :href="f.href"
              class="absolute inset-0 z-10 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
              :aria-label="f.locked ? `Create an account to unlock ${f.title}` : `Open ${f.title}`"
            >
              <span class="sr-only">{{ f.title }}</span>
            </Link>

            <CardHeader class="relative">
              <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600/10 text-emerald-700">
                  <Icon :name="f.icon" class="h-6 w-6" />
                </div>
                <span
                  v-if="f.locked"
                  class="inline-flex items-center gap-1 rounded-full border border-black/5 bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white"
                >
                  <Icon name="lock" class="h-3.5 w-3.5" />
                  Account
                </span>
              </div>
              <CardTitle class="text-lg text-slate-950">{{ f.title }}</CardTitle>
              <CardDescription class="text-slate-600">{{ f.description }}</CardDescription>
            </CardHeader>
            <CardFooter class="relative pt-0">
              <span class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-800">
                {{ f.locked ? "Unlock with free account" : "Open" }}
                <Icon name="arrow-right" class="h-4 w-4" />
              </span>
            </CardFooter>
          </Card>
        </div>
      </section>

      <!-- Plant of the Day -->
      <section class="mx-auto max-w-7xl px-6 pb-16">
        <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-950">Plant of the day</h2>
            <p class="mt-2 text-slate-600">A curated species spotlight with key facts and conservation context.</p>
          </div>
          <Link :href="isAuthed ? route('plants.index') : route('register')">
            <Button variant="outline" class="gap-2">
              <Icon name="book-open" class="h-4 w-4" />
              {{ isAuthed ? "Browse library" : "Create account to browse" }}
            </Button>
          </Link>
        </div>

        <Card class="overflow-hidden border-black/5 bg-white shadow-sm">
          <div class="grid md:grid-cols-2">
            <div class="relative">
              <img
                v-if="!imgFailed.potd"
                :src="potdImage"
                alt="Paperflower (Bougainvillea glabra)"
                class="h-72 w-full object-cover md:h-full"
                loading="lazy"
                referrerpolicy="no-referrer"
                @error="markImgFailed('potd')"
              />
              <div
                v-else
                class="flex h-72 w-full items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-amber-50 md:h-full"
                role="img"
                aria-label="Plant photo unavailable"
              >
                <div class="text-center">
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5">
                    <Icon name="image" class="h-6 w-6" />
                  </div>
                  <p class="mt-3 text-sm font-semibold text-slate-900">Photo unavailable</p>
                </div>
              </div>
              <div class="absolute left-5 top-5 inline-flex items-center gap-2 rounded-full border border-black/5 bg-white/90 px-3 py-1 text-xs font-semibold text-emerald-800 backdrop-blur">
                <Icon name="shield-alert" class="h-3.5 w-3.5" />
                Least Concern
              </div>
            </div>
            <div class="p-8 md:p-10">
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Today’s pick</p>
              <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Paperflower</h3>
              <p class="mt-1 text-sm italic text-slate-500">Bougainvillea glabra</p>
              <p class="mt-5 leading-relaxed text-slate-600">
                A vibrant, hardy flowering plant widely cultivated in tropical regions. Known for its colorful bracts surrounding small white flowers.
              </p>
              <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <Link :href="isAuthed ? route('plants.index') : route('register')">
                  <Button class="gap-2 bg-slate-900 text-white hover:bg-slate-800">
                    <Icon name="arrow-right" class="h-4 w-4" />
                    {{ isAuthed ? "Learn more" : "Create account to learn more" }}
                  </Button>
                </Link>
                <Link :href="secondaryCta.href">
                  <Button variant="outline" class="gap-2">
                    <Icon :name="secondaryCta.icon" class="h-4 w-4" />
                    {{ secondaryCta.label }}
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </Card>
      </section>

      <!-- Key Features -->
      <!-- Popular Plants -->
      <section class="mx-auto max-w-7xl px-6 pb-20">
        <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-950">Popular Malaysian plants</h2>
            <p class="mt-2 text-slate-600">A few iconic picks to spark exploration.</p>
          </div>
          <Link :href="isAuthed ? route('plants.index') : route('register')">
            <Button class="gap-2 bg-slate-900 text-white hover:bg-slate-800">
              Explore all plants
              <Icon name="arrow-right" class="h-4 w-4" />
            </Button>
          </Link>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
          <Card
            v-for="p in popularPlants"
            :key="p.key"
            class="group relative overflow-hidden border-black/5 bg-white shadow-sm"
          >
            <Link
              :href="isAuthed ? route('plants.index') : route('register')"
              class="absolute inset-0 z-10 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
              :aria-label="isAuthed ? `Browse plants` : `Create an account to browse plants`"
            >
              <span class="sr-only">{{ p.name }}</span>
            </Link>
            <div class="aspect-[4/3] overflow-hidden">
              <img
                v-if="!imgFailed[p.key]"
                :src="p.image"
                :alt="p.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                loading="lazy"
                referrerpolicy="no-referrer"
                @error="markImgFailed(p.key)"
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-50 via-white to-emerald-50"
                role="img"
                :aria-label="`${p.name} photo unavailable`"
              >
                <div class="text-center">
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/70 text-emerald-700 shadow-sm ring-1 ring-black/5">
                    <Icon name="image" class="h-6 w-6" />
                  </div>
                  <p class="mt-3 text-sm font-semibold text-slate-900">Photo unavailable</p>
                </div>
              </div>
            </div>
            <CardContent class="pt-6">
              <p class="text-base font-bold text-slate-950">{{ p.name }}</p>
              <p class="mt-1 text-sm italic text-slate-500">{{ p.scientific }}</p>
              <p class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800">
                {{ isAuthed ? "View in library" : "Unlock to view" }}
                <Icon name="arrow-right" class="h-4 w-4" />
              </p>
            </CardContent>
          </Card>
        </div>
      </section>
    </main>

    <!-- Footer CTA -->
    <footer class="border-t border-black/5 bg-white/60 px-6 py-16 backdrop-blur">
      <div class="mx-auto max-w-7xl">
        <div class="grid gap-10 rounded-3xl border border-black/5 bg-gradient-to-br from-emerald-600/10 via-white to-indigo-600/10 p-8 shadow-sm md:grid-cols-2 md:items-center md:p-12">
          <div class="space-y-4">
            <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Ready to discover Malaysian plants?</h2>
            <p class="text-slate-600">Start identifying, logging sightings, and building your personal dashboard today.</p>
          </div>
          <div class="flex flex-col gap-3 sm:flex-row md:justify-end">
            <Link :href="primaryCta.href">
              <Button class="h-11 w-full gap-2 rounded-full bg-slate-900 text-white hover:bg-slate-800 sm:w-auto">
                <Icon :name="primaryCta.icon" class="h-4 w-4" />
                {{ primaryCta.label }}
              </Button>
            </Link>
            <Link :href="secondaryCta.href">
              <Button variant="outline" class="h-11 w-full gap-2 rounded-full sm:w-auto">
                <Icon :name="secondaryCta.icon" class="h-4 w-4" />
                {{ secondaryCta.label }}
              </Button>
            </Link>
          </div>
        </div>

        <div class="mt-10 flex flex-col items-start justify-between gap-4 border-t border-black/5 pt-8 text-sm text-slate-500 sm:flex-row sm:items-center">
          <p>&copy; 2025 FloraFinder. All rights reserved.</p>
          <div class="flex gap-6">
            <Link href="#" class="hover:text-slate-700">Privacy</Link>
            <Link href="#" class="hover:text-slate-700">Terms</Link>
            <Link href="#" class="hover:text-slate-700">Contact</Link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Optional: Add custom animations or overrides here */
</style>
