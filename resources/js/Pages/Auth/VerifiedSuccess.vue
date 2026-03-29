<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const countdown = ref(5);

onMounted(() => {
    const timer = setInterval(() => {
        countdown.value--;
        if (countdown.value === 0) {
            clearInterval(timer);
            // Automatically redirect to the login page
            window.location.href = route('login');
        }
    }, 1000);
});
</script>

<template>
    <Head title="Account Verified" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50 dark:bg-gray-900 px-4">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg text-center max-w-md w-full border border-gray-100 dark:border-gray-700">
            
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 dark:bg-green-900/30 mb-6 relative">
                <svg class="animate-spin absolute h-full w-full text-green-200 dark:text-green-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg class="h-10 w-10 text-green-600 dark:text-green-400 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Account Verified!</h2>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                Your Colegio de Naujan email address has been successfully verified. Your account is now fully active.
            </p>
            
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <p class="text-xs font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">
                    Redirecting to login in {{ countdown }} seconds...
                </p>
            </div>
            
            <div class="mt-4">
                <a :href="route('login')" class="text-sm text-blue-600 hover:underline dark:text-blue-400 font-medium">Click here to log in now</a>
            </div>
        </div>
    </div>
</template>