<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue'; //
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    user: Object
});

const step = ref(1);

const form = useForm({
    role: '',
    name: props.user.name,
    school_id: '',
    contact_number: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const selectRole = (role) => {
    form.role = role;
    step.value = 2;
};

const submit = () => {
    form.post(route('register.complete'));
};
</script>

<template>
    <Head title="Complete Setup" />

    <div class="flex h-screen min-h-full bg-white dark:bg-gray-900 transition-colors duration-300">
        <div class="relative hidden w-0 flex-1 overflow-hidden bg-blue-800 lg:block">
            <div class="flex h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-20 xl:px-24">
                <div class="flex flex-row items-center justify-start">
                    <img class="h100 w-100 -ml-32" src="/images/Logo2.png" alt="Collegio de Naujan" />
                    <div class="-ml-16 flex-shrink-0">
                        <h2 class="text-4xl font-bold tracking-tight text-white">Collegio de Naujan</h2>
                        <p class="mt-2 text-lg text-blue-100">Complete your profile to <br> access your personalized dashboard.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="mt-8 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Welcome, {{ user.name.split(' ')[0] }}!
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <span v-if="step === 1">Please select your account type to continue.</span>
                        <span v-else>Finalize your details to get started.</span>
                    </p>
                </div>

                <div class="mt-8">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 mb-8 rounded-full overflow-hidden">
                        <div class="bg-blue-600 h-full transition-all duration-500 ease-in-out" :style="{ width: step === 1 ? '50%' : '100%' }"></div>
                    </div>

                    <div v-if="step === 1" class="space-y-4 animate-fade-in">
                        <button @click="selectRole('student')" class="w-full flex items-center p-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-gray-800 transition-all group relative">
                            <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" stroke-width="2"/></svg>
                            </div>
                            <div class="ml-4 text-left">
                                <span class="block text-lg font-bold text-gray-900 dark:text-white">Student</span>
                                <span class="text-xs text-gray-500">Access courses & assignments</span>
                            </div>
                        </button>

                        <button @click="selectRole('teacher')" class="w-full flex items-center p-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl hover:border-purple-600 hover:bg-purple-50 dark:hover:bg-gray-800 transition-all group relative">
                            <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                            </div>
                            <div class="ml-4 text-left">
                                <span class="block text-lg font-bold text-gray-900 dark:text-white">Teacher</span>
                                <span class="text-xs text-gray-500">Manage classes & grades</span>
                            </div>
                        </button>
                    </div>

                    <div v-if="step === 2" class="animate-fade-in">
                        <div v-if="form.role === 'teacher'" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg flex gap-3">
                            <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <p class="text-xs text-amber-800 leading-relaxed font-medium">
                                <strong>Note:</strong> Teacher accounts require manual approval by the administrator before you can access the dashboard.
                            </p>
                        </div>

                        <button @click="step = 1" class="mb-6 flex items-center text-xs font-bold text-gray-500 hover:text-blue-600 transition-colors">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            BACK TO ROLES
                        </button>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="school_id" value="School ID" />
                                    <TextInput id="school_id" v-model="form.school_id" type="text" class="mt-1 block w-full text-sm py-2.5" placeholder="e.g. 2026-01" required />
                                    <InputError :message="form.errors.school_id" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="contact" value="Mobile No." />
                                    <TextInput id="contact" v-model="form.contact_number" type="text" class="mt-1 block w-full text-sm py-2.5" placeholder="0912..." required />
                                    <InputError :message="form.errors.contact_number" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="password" value="Create Password" />
                                <PasswordInput id="password" v-model="form.password" class="mt-1 block w-full" placeholder="Min. 8 characters" required />
                                <InputError :message="form.errors.password" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password" />
                                <PasswordInput id="password_confirmation" v-model="form.password_confirmation" class="mt-1 block w-full" required />
                            </div>

                            <div class="flex items-start pt-2">
                                <Checkbox name="terms" v-model:checked="form.terms" required />
                                <div class="ml-3 text-xs">
                                    <label for="terms" class="font-medium text-gray-700 dark:text-gray-300">I agree to the <a href="#" class="text-blue-600 underline">Terms</a> and <a href="#" class="text-blue-600 underline">Privacy Policy</a>.</label>
                                </div>
                            </div>

                            <PrimaryButton class="w-full justify-center py-3 font-bold text-sm uppercase shadow-lg" :disabled="form.processing">
                                Complete Setup
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>