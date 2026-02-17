<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-lg mx-auto space-y-4 sm:space-y-6">
                
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Account Settings</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Manage your profile information and security.</p>
                </div>

                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 sm:p-6 rounded-lg shadow-sm">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-full"
                    />
                </div>

                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 sm:p-6 rounded-lg shadow-sm">
                    <UpdatePasswordForm class="max-w-full" />
                </div>

                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 sm:p-6 rounded-lg shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Log Out</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            End your session on this device.
                        </p>
                    </div>
                    
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="w-full sm:w-auto text-center inline-flex justify-center items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none transition"
                    >
                        Log Out
                    </Link>
                </div>

                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 sm:p-6 rounded-lg shadow-sm">
                    <DeleteUserForm class="max-w-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>