<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/auth/AuthNatureSplitLayout.vue';
import Icon from '@/components/Icon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const passwordType = computed(() => (showPassword.value ? 'text' : 'password'));
const passwordConfirmType = computed(() => (showPasswordConfirm.value ? 'text' : 'password'));

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Create your account" description="It’s free — save identifications, sightings, and your library">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Full name"
                        :aria-invalid="Boolean(form.errors.name)"
                        :class="form.errors.name ? 'border-red-300 focus-visible:ring-red-300' : ''"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        inputmode="email"
                        :aria-invalid="Boolean(form.errors.email)"
                        :class="form.errors.email ? 'border-red-300 focus-visible:ring-red-300' : ''"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="passwordType"
                            required
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Create a password"
                            :aria-invalid="Boolean(form.errors.password)"
                            :class="['pr-10', form.errors.password ? 'border-red-300 focus-visible:ring-red-300' : '']"
                        />
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-2 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            @click="showPassword = !showPassword"
                        >
                            <Icon :name="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4" />
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">Use at least 8 characters. Mix letters and numbers for stronger security.</p>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            :type="passwordConfirmType"
                            required
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirm password"
                            :aria-invalid="Boolean(form.errors.password_confirmation)"
                            :class="['pr-10', form.errors.password_confirmation ? 'border-red-300 focus-visible:ring-red-300' : '']"
                        />
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-2 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                            :aria-label="showPasswordConfirm ? 'Hide password' : 'Show password'"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                        >
                            <Icon :name="showPasswordConfirm ? 'eye-off' : 'eye'" class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full bg-slate-900 text-white hover:bg-slate-800" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>

                <p class="text-center text-xs text-slate-500">
                    By creating an account, you agree to our
                    <a href="#" class="font-semibold text-emerald-800 hover:text-emerald-700">Terms</a>
                    and
                    <a href="#" class="font-semibold text-emerald-800 hover:text-emerald-700">Privacy Policy</a>.
                </p>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
