<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    plant: any;
}>();

const form = useForm({
    common_name: props.plant.common_name,
    scientific_name: props.plant.scientific_name,
    description: props.plant.description,
    care_level: props.plant.care_level,
    watering: props.plant.watering,
    sunlight: props.plant.sunlight,
});

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
    { title: 'Plant Management', href: '/admin/plants' },
    { title: 'Edit Plant', href: '#' },
];

const submit = () => {
    form.patch(route('admin.plants.update', props.plant.id));
};
</script>

<template>
    <Head title="Edit Plant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Card class="max-w-2xl mx-auto w-full">
                <CardHeader>
                    <CardTitle>Edit Plant: {{ plant.common_name }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="common_name">Common Name</Label>
                            <Input id="common_name" v-model="form.common_name" required />
                            <div v-if="form.errors.common_name" class="text-sm text-destructive">{{ form.errors.common_name }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="scientific_name">Scientific Name</Label>
                            <Input id="scientific_name" v-model="form.scientific_name" required />
                            <div v-if="form.errors.scientific_name" class="text-sm text-destructive">{{ form.errors.scientific_name }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="care_level">Care Level</Label>
                            <Input id="care_level" v-model="form.care_level" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="watering">Watering</Label>
                                <Input id="watering" v-model="form.watering" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sunlight">Sunlight</Label>
                                <Input id="sunlight" v-model="form.sunlight" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea id="description" v-model="form.description" rows="5" />
                        </div>

                        <div class="flex justify-end gap-4">
                            <Button type="button" variant="outline" @click="$window.history.back()">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">Save Changes</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
