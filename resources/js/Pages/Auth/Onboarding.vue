<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'; 
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue'; 
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    user: Object
});

const form = useForm({
    name: props.user.name,
    school_id: '',
    program: '', // NEW FIELD ADDED HERE
    contact_number: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

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
                        <h2 class="text-4xl font-bold tracking-tight text-white">Colegio de Naujan</h2>
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
                        Finalize your student details to get started.
                    </p>
                </div>

                <div class="mt-8">
                    <div class="animate-fade-in">
                        <form @submit.prevent="submit" class="space-y-5">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="school_id" value="ID Number" />
                                    <TextInput id="school_id" v-model="form.school_id" type="text" class="mt-1 block w-full text-sm py-2.5" placeholder="e.g. 2026-01" required />
                                    <InputError :message="form.errors.school_id" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="program" value="Program" />
                                    <TextInput id="program" v-model="form.program" type="text" class="mt-1 block w-full text-sm py-2.5" placeholder="BSIT, BSBA, etc." required />
                                    <InputError :message="form.errors.program" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="contact" value="Mobile Number" />
                                <TextInput id="contact" v-model="form.contact_number" type="text" class="mt-1 block w-full text-sm py-2.5" placeholder="0912..." required />
                                <InputError :message="form.errors.contact_number" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="password" value="Password" />
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

                            <div class="mt-4 text-center">
                                <Link :href="route('logout')" method="post" as="button" class="text-sm text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors underline">
                                    Used the wrong account? Sign out
                                </Link>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>