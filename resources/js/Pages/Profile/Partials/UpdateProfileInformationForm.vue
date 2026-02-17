<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;
const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                Profile Information
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Update your account's profile information.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-4 space-y-4">
            <div>
                <InputLabel for="name" value="Name" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full text-sm p-2 rounded bg-slate-100 dark:bg-slate-900/50 text-slate-500 cursor-not-allowed border-slate-200 dark:border-slate-800"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    disabled
                />
                <p class="mt-1 text-[10px] text-slate-400">
                    Email address cannot be changed.
                </p>
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="role" value="Role" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="role"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded bg-slate-100 dark:bg-slate-900/50 text-slate-500 cursor-not-allowed capitalize border-slate-200 dark:border-slate-800"
                    :model-value="user.role"
                    disabled
                />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-800 dark:text-slate-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing" class="text-xs px-4 py-2 bg-blue-600 border-transparent w-full sm:w-auto justify-center">Save Name</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-xs text-slate-400">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>