<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Edit, Trash2, Plus } from 'lucide-vue-next';

const props = defineProps<{
    plants: {
        data: any[];
        links: any[];
    };
}>();

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
    { title: 'Plant Management', href: '/admin/plants' },
];

const deletePlant = (plant: any) => {
    if (confirm(`Are you sure you want to delete ${plant.common_name}?`)) {
        router.delete(route('admin.plants.destroy', plant.id));
    }
};
</script>

<template>
    <Head title="Plant Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Plants</h1>
                <!-- <Button as-child>
                    <Link :href="route('admin.plants.create')">
                        <Plus class="mr-2 h-4 w-4" /> Add Plant
                    </Link>
                </Button> -->
            </div>

            <Card>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Common Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Scientific Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Care Level</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="plant in plants.data" :key="plant.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ plant.common_name }}</td>
                                    <td class="p-4 align-middle italic">{{ plant.scientific_name }}</td>
                                    <td class="p-4 align-middle">{{ plant.care_level }}</td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="icon" as-child>
                                                <Link :href="route('admin.plants.edit', plant.id)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                @click="deletePlant(plant)"
                                                class="text-destructive hover:bg-destructive/10"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex items-center justify-end space-x-2 py-4">
                        <div v-for="link in plants.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                                :class="{ 'bg-accent text-accent-foreground': link.active }"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium opacity-50 border border-input bg-background h-9 px-4 py-2"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
