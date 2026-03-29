<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verify Email" />

    <div class="flex h-screen min-h-full bg-white dark:bg-gray-900 transition-colors duration-300">
        <div class="relative hidden w-0 flex-1 overflow-hidden bg-blue-800 lg:block">
            <div class="flex h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-20 xl:px-24">
                <div class="flex flex-row items-center justify-start">
                    <img class="h100 w-100 -ml-32" src="/images/Logo2.png" alt="Colegio de Naujan" />
                    <div class="-ml-16 flex-shrink-0">
                        <h2 class="text-4xl font-bold tracking-tight text-white">Colegio de Naujan</h2>
                        <p class="mt-2 text-lg text-blue-100">Excellence in Digital Learning.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                        Verify your email
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                        Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                    </p>
                </div>

                <div v-if="verificationLinkSent" class="mb-6 font-medium text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    A new verification link has been sent to the email address you provided during registration.
                </div>

                <form @submit.prevent="submit">
                    <div class="mt-6 flex flex-col gap-4">
                        <PrimaryButton class="w-full justify-center py-3 font-bold text-sm uppercase shadow-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Send Verification Email
                        </PrimaryButton>

                        <div class="flex items-center justify-between mt-2">
                            <Link
                                :href="route('profile.edit')"
                                class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Edit Profile
                            </Link>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-xs text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Log Out
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>