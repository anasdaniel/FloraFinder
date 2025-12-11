<script setup lang="ts">
import Icon from "@/components/Icon.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/vue3";

const metrics = [
  { label: "Species catalogued", value: "1,240+", accent: "from-teal-400 to-emerald-500" },
  { label: "Community posts", value: "8.7k", accent: "from-violet-400 to-indigo-500" },
  { label: "Sightings mapped", value: "3,210", accent: "from-amber-300 to-orange-400" },
];

const quickActions = [
  {
    icon: "camera",
    title: "Identify a plant",
    description: "Upload a photo and get instant AI results.",
    href: "/plant-identifier",
  },
  {
    icon: "map",
    title: "View live map",
    description: "See sightings glow across Malaysia in real-time.",
    href: "/sightings-map",
  },
  {
    icon: "book-open",
    title: "Browse the library",
    description: "Dive into curated species profiles and care.",
    href: "/plants",
  },
  {
    icon: "users",
    title: "Visit the forum",
    description: "Ask experts, share finds, and join discussions.",
    href: "/forum",
  },
];

const highlights = [
  {
    tag: "New",
    title: "Fluid, glassy UI",
    copy: "Translucent panels, soft gradients, and generous spacing keep focus on your plants.",
  },
  {
    tag: "Realtime",
    title: "Live insights",
    copy: "See trending species, conservation signals, and fresh uploads at a glance.",
  },
  {
    tag: "Guided",
    title: "Onboarding built-in",
    copy: "Clear steps for identifying, mapping, and sharing — even before you sign in.",
  },
];
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-50">
    <Head title="FloraFinder Dashboard Preview" />

    <div class="absolute inset-0 overflow-hidden">
      <div
        class="pointer-events-none absolute left-1/2 top-[-10%] h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-emerald-500/20 blur-[160px]"
      />
      <div
        class="pointer-events-none absolute right-[-5%] top-[20%] h-[380px] w-[380px] rounded-full bg-indigo-500/20 blur-[150px]"
      />
    </div>

    <div class="relative mx-auto flex max-w-6xl flex-col gap-10 px-6 pb-16 pt-10 sm:px-10">
      <!-- Header -->
      <header class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-5 py-4 backdrop-blur">
        <div class="flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-indigo-500 shadow-lg shadow-emerald-500/20">
            <Icon name="leaf" class="h-5 w-5 text-white" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Dashboard Preview</p>
            <p class="text-lg font-semibold text-white">FloraFinder</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('login')">
            <Button variant="ghost" class="text-slate-100 hover:bg-white/10">Sign in</Button>
          </Link>
          <Link :href="route('register')">
            <Button class="rounded-full bg-gradient-to-r from-emerald-400 to-indigo-500 text-slate-950 shadow-lg shadow-emerald-500/30 hover:opacity-90">
              Create account
            </Button>
          </Link>
        </div>
      </header>

      <!-- Hero -->
      <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="flex flex-col gap-6">
          <div class="inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-emerald-100 shadow-sm shadow-emerald-500/30 ring-1 ring-white/15">
            Live preview · No sign-in required
          </div>
          <div class="space-y-4">
            <h1 class="text-4xl font-semibold leading-tight text-white sm:text-5xl">
              A sleek dashboard built for plant lovers — before you even log in.
            </h1>
            <p class="text-lg text-slate-200/80">
              Explore the experience you get after sign-in: glassy cards, ambient gradients, instant actions, and a calming layout crafted for discovery.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <Link :href="route('register')">
              <Button class="rounded-full bg-white text-slate-900 shadow-lg shadow-white/30 hover:bg-slate-100">
                Get started free
              </Button>
            </Link>
            <Link :href="route('login')">
              <Button variant="outline" class="rounded-full border-white/30 bg-white/10 text-white hover:bg-white/20">
                Continue to sign in
              </Button>
            </Link>
            <span class="text-sm text-slate-300">See the flow before committing.</span>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card
              v-for="metric in metrics"
              :key="metric.label"
              class="border-white/10 bg-white/5 text-white shadow-lg shadow-black/20 backdrop-blur"
            >
              <CardHeader class="pb-3">
                <div
                  class="inline-flex items-center rounded-full bg-gradient-to-r px-3 py-1 text-xs font-semibold text-slate-950 shadow-sm"
                  :class="metric.accent"
                >
                  {{ metric.label }}
                </div>
              </CardHeader>
              <CardContent class="pt-0">
                <p class="text-3xl font-semibold">{{ metric.value }}</p>
                <p class="text-sm text-slate-300/80">Updated live</p>
              </CardContent>
            </Card>
          </div>
        </div>

        <div
          class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-white/15 via-white/5 to-slate-900/40 p-6 shadow-2xl shadow-emerald-500/20 backdrop-blur lg:p-8"
        >
          <div class="absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(94,234,212,0.2),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(129,140,248,0.25),transparent_40%)]" />
          </div>
          <div class="relative flex flex-col gap-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-100">Preview</p>
                <h2 class="text-2xl font-semibold">Ambient insight</h2>
                <p class="text-sm text-slate-200/70">See what your dashboard looks like once you're in.</p>
              </div>
              <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-white">Dark-ready</span>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div class="rounded-2xl bg-white/5 p-4 shadow-inner shadow-black/20 ring-1 ring-white/10">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-950">
                    <Icon name="camera" class="h-5 w-5" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-white">Instant ID</p>
                    <p class="text-xs text-slate-300/80">AI-backed recognitions in seconds.</p>
                  </div>
                </div>
                <div class="mt-4 h-28 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-300/10 ring-1 ring-white/10" />
              </div>
              <div class="rounded-2xl bg-white/5 p-4 shadow-inner shadow-black/20 ring-1 ring-white/10">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-slate-950">
                    <Icon name="map" class="h-5 w-5" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-white">Living map</p>
                    <p class="text-xs text-slate-300/80">Sightings glow where nature thrives.</p>
                  </div>
                </div>
                <div class="mt-4 h-28 rounded-xl bg-gradient-to-br from-indigo-500/20 to-emerald-300/10 ring-1 ring-white/10" />
              </div>
            </div>
            <div class="rounded-2xl bg-slate-950/70 p-4 ring-1 ring-white/10">
              <p class="text-sm text-slate-200">
                "We wanted a calm, cinematic dashboard that showcases flora before you even log in. This preview mirrors the signed-in experience so you know exactly what awaits."
              </p>
              <p class="mt-3 text-xs font-semibold text-emerald-100">The FloraFinder Team</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick actions -->
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-100">Jump in</p>
            <h3 class="text-xl font-semibold text-white">Do more, even before signing in</h3>
            <p class="text-sm text-slate-300/80">Preview the core flows in one tap.</p>
          </div>
          <Link :href="route('register')">
            <Button variant="outline" class="rounded-full border-white/20 bg-white/5 text-white hover:bg-white/15">
              Create account
            </Button>
          </Link>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <Card
            v-for="action in quickActions"
            :key="action.title"
            class="group border-white/10 bg-white/5 text-white shadow-lg shadow-black/20 backdrop-blur transition hover:-translate-y-1 hover:border-white/20 hover:shadow-emerald-500/20"
          >
            <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10">
                <Icon :name="action.icon" class="h-5 w-5 text-emerald-100" />
              </div>
              <Link :href="action.href" class="text-xs text-emerald-100 hover:text-emerald-200">Open</Link>
            </CardHeader>
            <CardContent class="space-y-2">
              <CardTitle class="text-lg">{{ action.title }}</CardTitle>
              <p class="text-sm text-slate-300/80">
                {{ action.description }}
              </p>
            </CardContent>
          </Card>
        </div>
      </section>

      <!-- Highlights -->
      <section class="grid gap-4 lg:grid-cols-3">
        <Card
          v-for="highlight in highlights"
          :key="highlight.title"
          class="border-white/10 bg-gradient-to-br from-white/5 via-white/5 to-white/0 text-white backdrop-blur"
        >
          <CardHeader class="space-y-3">
            <div class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-100">
              {{ highlight.tag }}
            </div>
            <CardTitle class="text-xl">{{ highlight.title }}</CardTitle>
            <p class="text-sm text-slate-300/80">{{ highlight.copy }}</p>
          </CardHeader>
        </Card>
      </section>

      <!-- CTA -->
      <section
        class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-emerald-500/20 via-emerald-400/10 to-indigo-500/20 p-8 text-center shadow-2xl shadow-emerald-500/20 backdrop-blur"
      >
        <div class="mx-auto max-w-3xl space-y-4">
          <p class="text-xs uppercase tracking-[0.25em] text-white/80">Ready when you are</p>
          <h4 class="text-2xl font-semibold text-white sm:text-3xl">Sign in to continue where this preview leaves off.</h4>
          <p class="text-base text-white/80">
            Your discoveries, maps, and conversations are a tap away. Create an account or sign back in to unlock the full experience.
          </p>
          <div class="flex flex-wrap justify-center gap-3">
            <Link :href="route('login')">
              <Button class="rounded-full bg-white text-slate-900 shadow-lg shadow-white/30 hover:bg-slate-100">
                Sign in
              </Button>
            </Link>
            <Link :href="route('register')">
              <Button variant="outline" class="rounded-full border-white/50 bg-white/10 text-white hover:bg-white/20">
                Create account
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>


