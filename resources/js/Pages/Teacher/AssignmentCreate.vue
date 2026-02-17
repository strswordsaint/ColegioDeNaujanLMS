<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    source: String,
    backUrl: String
});

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    points: 100,
    files: [],
    source: props.source 
});

// Smart Go Back Function
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(props.backUrl);
    }
};

const submit = () => {
    form.post(route('teacher.assignments.store', props.course.id));
};
</script>

<template>
    <Head title="Create Assignment" />

    <AuthenticatedLayout>
        <div class="mb-4 flex justify-between items-center">
             <div>
                 <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Create Assignment</h1>
                 <p class="text-slate-500 dark:text-slate-400 text-[10px] font-medium">Add a task for {{ course.title }}.</p>
             </div>
             
             <button @click="goBack" class="text-slate-500 text-xs font-bold hover:text-blue-600 transition flex items-center gap-1 cursor-pointer">
                &larr; Back
             </button>
        </div>

        <div class="max-w-xl mx-auto">
            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
                
                <form @submit.prevent="submit" class="space-y-3">
                    
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500 focus:border-blue-500" required autofocus />
                        <InputError class="mt-1" :message="form.errors.title" />
                    </div>

                    <div>
                         <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Instructions (Optional)</label>
                        <textarea v-model="form.description" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs h-24 focus:ring-blue-500 focus:border-blue-500 resize-none" placeholder="Instructions..."></textarea>
                        <InputError class="mt-1" :message="form.errors.description" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Attachments (Optional)</label>
                        <input type="file" multiple @change="e => form.files = Array.from(e.target.files)" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded" />
                        <InputError class="mt-1" :message="form.errors.files" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Points <span class="text-red-500">*</span></label>
                            <input v-model="form.points" type="number" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500 focus:border-blue-500" required />
                            <InputError class="mt-1" :message="form.errors.points" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Due Date <span class="text-red-500">*</span></label>
                            <input v-model="form.due_date" type="datetime-local" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500 focus:border-blue-500 dark:[color-scheme:dark]" required />
                            <InputError class="mt-1" :message="form.errors.due_date" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="goBack" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 text-[10px] font-bold transition px-3 py-1.5 cursor-pointer">Cancel</button>
                        
                        <PrimaryButton :disabled="form.processing" class="text-xs px-4 py-1.5 h-8 bg-blue-600 hover:bg-blue-500 border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create</span>
                        </PrimaryButton>
                    </div>

                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>