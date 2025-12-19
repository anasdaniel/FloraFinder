<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Trash2, MapPin, User, Leaf } from "lucide-vue-next";

defineProps<{
  sightings: {
    data: Array<{
      id: number;
      common_name: string;
      scientific_name: string;
      location_name: string;
      user: { name: string };
      plant?: { common_name: string };
      image_url: string;
      sighted_at: string;
    }>;
    links: any[];
  };
}>();

const deleteSighting = (id: number) => {
  if (confirm("Are you sure you want to delete this sighting?")) {
    router.delete(route("admin.sightings.destroy", id));
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>

<template>
  <Head title="Sighting Management" />

  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Sighting Management</h1>
          <p class="text-muted-foreground">
            Review and manage plant sightings reported by users.
          </p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Sightings</CardTitle>
          <CardDescription>A list of all reported plant sightings.</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Image</TableHead>
                <TableHead>Plant</TableHead>
                <TableHead>Location</TableHead>
                <TableHead>Reporter</TableHead>
                <TableHead>Date</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="sighting in sightings.data" :key="sighting.id">
                <TableCell>
                  <img
                    v-if="sighting.image_url"
                    :src="sighting.image_url"
                    class="h-12 w-12 object-cover rounded-md border"
                    alt="Sighting"
                  />
                  <div
                    v-else
                    class="h-12 w-12 bg-muted rounded-md flex items-center justify-center"
                  >
                    <Leaf class="h-6 w-6 text-muted-foreground" />
                  </div>
                </TableCell>
                <TableCell>
                  <div class="font-medium">{{ sighting.common_name || "Unknown" }}</div>
                  <div class="text-xs text-muted-foreground italic">
                    {{ sighting.scientific_name }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-1">
                    <MapPin class="h-3 w-3 text-muted-foreground" />
                    {{ sighting.location_name || "Unknown" }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <User class="h-4 w-4 text-muted-foreground" />
                    {{ sighting.user.name }}
                  </div>
                </TableCell>
                <TableCell>{{ formatDate(sighting.sighted_at) }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Link :href="route('sightings.show', sighting.id)">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Button
                      variant="destructive"
                      size="icon"
                      @click="deleteSighting(sighting.id)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div class="mt-4 flex justify-center gap-2">
            <Link
              v-for="link in sightings.links"
              :key="link.label"
              :href="link.url || '#'"
              class="px-3 py-1 border rounded text-sm"
              :class="{
                'bg-primary text-primary-foreground': link.active,
                'text-muted-foreground': !link.url,
              }"
              v-html="link.label"
            />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
