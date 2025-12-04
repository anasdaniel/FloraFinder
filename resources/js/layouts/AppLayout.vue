<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    title: undefined,
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :title="title">
        <slot />
    </AppLayout>
</template>
