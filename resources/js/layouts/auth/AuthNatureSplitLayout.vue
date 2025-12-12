<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

const page = usePage();
const name = page.props.name as string | undefined;
const quote = page.props.quote as { message: string; author: string } | undefined;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-dvh bg-gradient-to-b from-white via-white to-emerald-50/50">
        <a
            href="#auth-main"
            class="sr-only focus:not-sr-only focus:fixed focus:left-6 focus:top-6 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900 focus:shadow-lg"
        >
            Skip to content
        </a>

        <!-- Ambient blobs -->
        <div class="pointer-events-none fixed inset-0 -z-10">
            <div class="absolute -top-24 -left-40 h-[420px] w-[420px] rounded-full bg-emerald-300/25 blur-3xl" />
            <div class="absolute top-20 -right-52 h-[520px] w-[520px] rounded-full bg-indigo-300/15 blur-3xl" />
            <div class="absolute bottom-[-160px] left-1/3 h-[520px] w-[520px] rounded-full bg-teal-300/15 blur-3xl" />
        </div>

        <div class="mx-auto grid min-h-dvh max-w-7xl items-stretch gap-10 px-6 py-10 lg:grid-cols-2 lg:gap-12 lg:px-8">
            <!-- Brand panel -->
            <aside class="relative hidden overflow-hidden rounded-3xl border border-black/5 bg-gradient-to-br from-emerald-900 via-slate-950 to-indigo-950 p-10 text-white shadow-xl lg:flex lg:flex-col">
                <div class="absolute inset-0 opacity-40 [background:radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.35),transparent_55%),radial-gradient(circle_at_80%_30%,rgba(99,102,241,0.25),transparent_55%),radial-gradient(circle_at_30%_90%,rgba(20,184,166,0.25),transparent_60%)]" />
                <div class="relative z-10 flex items-center justify-between">
                    <Link :href="route('home')" class="inline-flex items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                            <AppLogoIcon class="size-9 fill-current text-white" />
                        </span>
                        <span class="text-base font-semibold tracking-tight">{{ name ?? 'FloraFinder' }}</span>
                    </Link>
                    <Link :href="route('dashboard.preview')" class="text-sm font-semibold text-white/80 hover:text-white">
                        Preview →
                    </Link>
                </div>

                <div class="relative z-10 mt-16 space-y-6">
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
                        <Icon name="sparkles" class="h-4 w-4" />
                        Built for real-world plant discovery
                    </p>
                    <h2 class="text-3xl font-bold leading-tight tracking-tight">
                        Identify plants, map sightings, and learn conservation context — in one place.
                    </h2>
                    <ul class="space-y-3 text-sm text-white/80">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <Icon name="camera" class="h-4 w-4" />
                            </span>
                            <span><span class="font-semibold text-white">Instant ID</span> from photos with confidence + taxonomy.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <Icon name="map" class="h-4 w-4" />
                            </span>
                            <span><span class="font-semibold text-white">Sightings map</span> to track discoveries and hotspots.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                                <Icon name="book-open" class="h-4 w-4" />
                            </span>
                            <span><span class="font-semibold text-white">Plant library</span> with care details and status.</span>
                        </li>
                    </ul>
                </div>

                <div v-if="quote" class="relative z-10 mt-auto border-t border-white/10 pt-8">
                    <blockquote class="space-y-2">
                        <p class="text-sm leading-relaxed text-white/85">&ldquo;{{ quote.message }}&rdquo;</p>
                        <footer class="text-xs text-white/60">{{ quote.author }}</footer>
                    </blockquote>
                </div>
            </aside>

            <!-- Form panel -->
            <main id="auth-main" class="flex items-center">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-6 flex items-center justify-center lg:hidden">
                        <Link :href="route('home')" class="inline-flex items-center gap-2 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                                <AppLogoIcon class="size-9 fill-current text-white" />
                            </span>
                            <span class="text-base font-semibold tracking-tight text-slate-950">{{ name ?? 'FloraFinder' }}</span>
                        </Link>
                    </div>

                    <Card class="border-black/5 bg-white/70 shadow-sm backdrop-blur">
                        <CardHeader class="space-y-2 text-center">
                            <CardTitle class="text-2xl tracking-tight text-slate-950">{{ title }}</CardTitle>
                            <CardDescription class="text-slate-600">{{ description }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <slot />
                        </CardContent>
                    </Card>

                    <p class="mt-6 text-center text-xs text-slate-500">
                        Tip: you can view the UI without an account on
                        <Link :href="route('dashboard.preview')" class="font-semibold text-emerald-800 hover:text-emerald-700">dashboard preview</Link>.
                    </p>
                </div>
            </main>
        </div>
    </div>
</template>


