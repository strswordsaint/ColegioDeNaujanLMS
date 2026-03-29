<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome to CDN LMS" />

    <div class="min-h-screen bg-gray-50 flex flex-col">
        
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600">CDN LMS</span>
                </div>

                <nav v-if="canLogin" class="flex gap-4">
                    <div v-if="$page.props.auth.user" class="flex items-center gap-4">
                        <Link :href="route('dashboard')" class="text-gray-700 font-semibold hover:text-blue-600">
                            Dashboard
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-red-600 font-semibold border border-red-200 px-3 py-1 rounded hover:bg-red-50">
                            Log Out
                        </Link>
                    </div>

                    <template v-else>
                        <Link :href="route('register')" class="text-gray-700 font-semibold hover:text-blue-600 px-4 py-2">
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center">
            <div class="max-w-4xl mx-auto px-6 text-center">
                
                <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight mb-6">
                    Master Your Future with <span class="text-blue-600">Colegio de Naujan</span>
                </h1>
                
                <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
                    The all-in-one Learning Management System designed for Colegio de Naujan.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="px-8 py-4 bg-blue-600 text-white text-lg font-bold rounded-full hover:bg-blue-700 transition shadow-lg transform hover:-translate-y-1"
                    >
                        Go to Dashboard
                    </Link>

                    <Link
                        v-else
                        :href="route('login')"
                        class="px-8 py-4 bg-blue-600 text-white text-lg font-bold rounded-full hover:bg-blue-700 transition shadow-lg transform hover:-translate-y-1"
                    >
                        Get Started
                    </Link>

                </div>

            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-8">
            <div class="max-w-7xl mx-auto px-4 text-center text-gray-500">
                &copy; {{ new Date().getFullYear() }} Colegio de Naujan. All rights reserved.
            </div>
        </footer>
    </div>
</template>