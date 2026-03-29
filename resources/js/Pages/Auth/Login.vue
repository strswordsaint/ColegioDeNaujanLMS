<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue'; // Ensure this component exists
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="flex h-screen min-h-full bg-white">
        
        <div class="relative hidden w-0 flex-1 overflow-hidden bg-blue-800 lg:block">
            <div class="flex h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-20 xl:px-24">
                <div class="flex flex-row items-center justify-start">
                    <img class="h100 w-100 -ml-32" src="/images/Logo2.png" alt="Collegio de Naujan" />
                    <div class="-ml-16 flex-shrink-0">
                        <h2 class="text-4xl font-bold tracking-tight text-white">
                            Colegio de Naujan
                        </h2>
                        <p class="mt-2 text-lg text-blue-100">
                            Welcome to Colegio de Naujan <br> Learning Management System.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="mt-8 text-2xl font-bold tracking-tight text-gray-900">
                        Sign in to your account
                    </h2>
                    <p class="mt-2 text-sm text-slate-900 font-medium">
                        Or
                        <Link :href="route('register')" class="font-bold text-blue-700 hover:text-blue-600 underline">
                            create a new account
                        </Link>
                    </p>
                </div>

                <div class="mt-8">
                    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <a :href="route('auth.google')" class="flex w-full justify-center items-center gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500 transition-all">
                                <svg class="h-5 w-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                <span class="text-sm">Sign in with Google</span>
                            </a>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-2 text-xs text-gray-500 uppercase">Or continue with email</span>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="email" value="Email Address" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full text-black font-medium py-2 text-sm"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" />
                            <PasswordInput
                                id="password"
                                class="mt-1 block w-full text-black font-medium py-2 text-sm"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2 text-sm text-slate-900 font-bold">Remember me</span>
                            </label>

                            <div v-if="canResetPassword" class="text-sm">
                                <Link :href="route('password.request')" class="font-medium text-blue-700 hover:text-blue-600">
                                    Forgot password?
                                </Link>
                            </div>
                        </div>

                        <div>
                            <PrimaryButton class="flex w-full justify-center font-bold py-2.5" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Log in
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>