<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Shield, ShieldAlert, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    users: {
        data: any[];
        links: any[];
    };
}>();

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
    { title: 'User Management', href: '/admin/users' },
];

const toggleAdmin = (user: any) => {
    if (confirm(`Are you sure you want to ${user.is_admin ? 'remove' : 'grant'} admin privileges for ${user.name}?`)) {
        router.patch(route('admin.users.toggle-admin', user.id));
    }
};

const deleteUser = (user: any) => {
    if (confirm(`Are you sure you want to delete user ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Users</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Role</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Joined</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="user in users.data" :key="user.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ user.name }}</td>
                                    <td class="p-4 align-middle">{{ user.email }}</td>
                                    <td class="p-4 align-middle">
                                        <span v-if="user.is_admin" class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                            Admin
                                        </span>
                                        <span v-else class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                                            User
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                @click="toggleAdmin(user)"
                                                :title="user.is_admin ? 'Remove Admin' : 'Make Admin'"
                                            >
                                                <ShieldAlert v-if="user.is_admin" class="h-4 w-4 text-destructive" />
                                                <Shield v-else class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                @click="deleteUser(user)"
                                                class="text-destructive hover:bg-destructive/10"
                                                title="Delete User"
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
                        <div v-for="link in users.links" :key="link.label">
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
