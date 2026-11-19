<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ImageCropperModal from '@/Components/ImageCropperModal.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Camera, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
    programs: { type: Array, default: () => [] },
    departmentName: { type: String, default: null },
});

const user = usePage().props.auth.user;

// Check if user's existing saved program is in the Admin Academic Programs list or is custom
const initialProgramInList = computed(() =>
    props.programs.some(p => p.name === user.program)
);

const selectedProgram = ref(
    !user.program
        ? ''
        : initialProgramInList.value
            ? user.program
            : 'Other'
);
const customProgram = ref(
    user.program && !initialProgramInList.value ? user.program : ''
);

const fileInput = ref(null);
const previewUrl = ref(null);
const showAvatarCropper = ref(false);
const avatarFileToCrop = ref(null);

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    school_id: user.school_id || '',
    program: user.program || '',
    contact_number: user.contact_number || '',
    avatar: null,
    reset_avatar: false,
});

const isCustomUploadedAvatar = computed(() => {
    if (previewUrl.value) return true;
    if (form.reset_avatar) return false;
    return Boolean(user.avatar && !user.avatar.includes('googleusercontent.com'));
});

const displayedAvatar = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (form.reset_avatar) return null;
    return user.avatar;
});

const isValidPhone = computed(() => {
    if (!form.contact_number) return true;
    return /^\d{11}$/.test(form.contact_number);
});

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleAvatarChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    avatarFileToCrop.value = file;
    showAvatarCropper.value = true;
    event.target.value = '';
};

const handleCroppedAvatar = (croppedFile) => {
    form.avatar = croppedFile;
    form.reset_avatar = false;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(croppedFile);
    showAvatarCropper.value = false;
    avatarFileToCrop.value = null;
};

const closeAvatarCropper = () => {
    showAvatarCropper.value = false;
    avatarFileToCrop.value = null;
};

const resetToGoogleSync = () => {
    form.avatar = null;
    form.reset_avatar = true;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    if (user.role === 'student') {
        form.program = selectedProgram.value === 'Other'
            ? customProgram.value
            : selectedProgram.value;
    }

    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.avatar = null;
            form.reset_avatar = false;
            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
            }
            previewUrl.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                Profile Information
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Update your account's profile information and academic details.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            
            <!-- Profile Photo Upload + Auto Google Sync Default -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 shrink-0 relative">
                        <img
                            v-if="displayedAvatar"
                            :src="displayedAvatar"
                            referrerpolicy="no-referrer"
                            class="w-16 h-16 rounded-full object-cover border-4 border-white dark:border-slate-800 shadow-sm bg-white"
                        />
                        <div
                            v-else
                            class="w-16 h-16 rounded-full border-4 border-white dark:border-slate-800 shadow-sm bg-blue-600 flex items-center justify-center text-white text-xl font-black"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Profile Photo</p>
                        <p v-if="previewUrl" class="text-[10px] font-black uppercase tracking-widest text-blue-600 mt-0.5">
                            New photo selected (Save to apply)
                        </p>
                        <p v-else-if="isCustomUploadedAvatar" class="text-[10px] font-black uppercase tracking-widest text-purple-600 dark:text-purple-400 mt-0.5">
                            Custom Uploaded Photo
                        </p>
                        <p v-else class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mt-0.5">
                            {{ user.google_id ? 'Synced automatically from Google' : 'Default Account Avatar' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="hidden"
                        @change="handleAvatarChange"
                    />
                    <button
                        type="button"
                        @click="triggerFileInput"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-sm transition"
                    >
                        <Camera class="w-3.5 h-3.5" />
                        Upload Photo
                    </button>

                    <button
                        v-if="isCustomUploadedAvatar"
                        type="button"
                        @click="resetToGoogleSync"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition"
                        title="Remove custom photo and revert to Google sync"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        {{ user.google_id ? 'Use Google Sync' : 'Reset' }}
                    </button>
                </div>
            </div>
            <InputError class="mt-1" :message="form.errors.avatar" />

            <!-- Full Name -->
            <div>
                <InputLabel for="name" value="Full Name" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- Email (Read-only) -->
            <div>
                <InputLabel for="email" value="Email" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full text-sm p-2 rounded !bg-slate-100 !text-slate-500 cursor-not-allowed border-slate-200"
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

            <!-- Role (Read-only) -->
            <div>
                <InputLabel for="role" value="Role" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="role"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded !bg-slate-100 !text-slate-500 cursor-not-allowed capitalize border-slate-200"
                    :model-value="user.role"
                    disabled
                />
            </div>

            <!-- Student ID / Employee ID -->
            <div>
                <InputLabel
                    for="school_id"
                    :value="user.role === 'student' ? 'Student ID Number' : 'Employee / Admin ID Number'"
                    class="text-xs uppercase text-slate-500 font-bold"
                />
                <TextInput
                    id="school_id"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded font-mono"
                    v-model="form.school_id"
                    :placeholder="user.role === 'student' ? 'e.g. 2026-0123' : 'e.g. EMP-2026-10023'"
                />
                <InputError class="mt-1" :message="form.errors.school_id" />
            </div>

            <!-- Academic Program (Editable dropdown for Students; Read-only assigned program for Teacher/Dean) -->
            <div v-if="user.role === 'student'">
                <InputLabel for="program" value="Academic Program" class="text-xs uppercase text-slate-500 font-bold" />
                <select
                    id="program"
                    v-model="selectedProgram"
                    class="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 cursor-pointer"
                >
                    <option value="" disabled>Select your academic program...</option>
                    <option v-for="prog in programs" :key="prog.id" :value="prog.name">
                        {{ prog.name }}
                    </option>
                    <option value="Other">Other (Type manually)</option>
                </select>

                <div v-if="selectedProgram === 'Other'" class="mt-2">
                    <TextInput
                        id="custom_program"
                        type="text"
                        class="block w-full text-sm p-2 rounded"
                        v-model="customProgram"
                        placeholder="Specify your academic program"
                        required
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.program" />
            </div>

            <div v-else-if="user.role === 'teacher' || user.role === 'dean'">
                <InputLabel for="assigned_program" value="Assigned Academic Program" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="assigned_program"
                    type="text"
                    class="mt-1 block w-full text-sm p-2 rounded !bg-slate-100 !text-slate-500 cursor-not-allowed border-slate-200"
                    :model-value="departmentName || 'Unassigned (Managed by Admin)'"
                    disabled
                />
            </div>

            <!-- Mobile Number -->
            <div>
                <InputLabel for="contact_number" value="Mobile Number" class="text-xs uppercase text-slate-500 font-bold" />
                <TextInput
                    id="contact_number"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full text-sm p-2 rounded"
                    v-model="form.contact_number"
                    placeholder="e.g. 09123456789"
                />
                <p
                    class="text-[10px] font-bold mt-1 flex justify-between px-1"
                    :class="!isValidPhone ? 'text-red-500' : 'text-slate-400'"
                >
                    <span>Must be exactly 11 digits and numbers only</span>
                    <span :class="form.contact_number.length === 11 && isValidPhone ? 'text-emerald-500' : ''">
                        {{ form.contact_number.length }} / 11
                    </span>
                </p>
                <InputError class="mt-1" :message="form.errors.contact_number" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-800 dark:text-slate-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-md"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton
                    :disabled="form.processing || !isValidPhone"
                    class="text-xs px-4 py-2 bg-blue-600 border-transparent w-full sm:w-auto justify-center shadow-md disabled:opacity-50"
                >
                    Save