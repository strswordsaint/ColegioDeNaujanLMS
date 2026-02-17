<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue'; // Need this for form validation

defineProps({
    courses: Array
});

const imageErrors = ref({});
const showCreateModal = ref(false); // State for Create Class Modal

// Form for Creating Course (Moved here for Modal)
const form = useForm({
    title: '',
    description: '',
    difficulty_level: 'beginner',
    thumbnail: null // Optional: Handle file upload logic if needed, simplified here
});

const handleImageError = (id) => { imageErrors.value[id] = true; };
const copyCode = (code) => {
    if (code) {
        navigator.clipboard.writeText(code);
        alert('Class code copied: ' + code);
    }
};

const submitCourse = () => {
    form.post(route('teacher.courses.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="My Classes" />

    <AuthenticatedLayout>
        
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">My Classes</h1>
            <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-1 font-medium">Manage your course content and students.</p>
        </div>

        <div class="flex gap-6 items-start">
            
            <aside class="w-14 shrink-0 flex flex-col gap-4 sticky top-6 z-10">
                
                <button @click="showCreateModal = true" 
                        class="group relative flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                    
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    
                    <span class="absolute left-full ml-3 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg z-20">
                        Create Class
                        <span class="absolute top-1/2 right-full -mt-1 -mr-1 border-4 border-transparent border-r-slate-800"></span>
                    </span>
                </button>

            </aside>

            <div class="flex-1 min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="course in courses" :key="course.id" 
                         class="group bg-white dark:bg-slate-800 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700
                                transition-all duration-200 flex flex-col h-[260px] shadow-sm
                                hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md hover:-translate-y-0.5">
                        
                        <div class="h-28 relative bg-slate-100 dark:bg-slate-900 overflow-hidden shrink-0">
                            <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                 :src="course.thumbnail" 
                                 @error="handleImageError(course.id)"
                                 class="absolute inset-0 w-full h-full object-cover z-0 transition-transform duration-700 group-hover:scale-105" />
                            
                            <div class="absolute inset-0 bg-slate-200 dark:bg-slate-900 flex items-center justify-center" v-else>
                                 <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                                 <svg class="w-8 h-8 text-slate-400 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent"></div>

                            <div class="absolute bottom-3 left-3 right-3 z-10">
                                <h3 class="font-bold text-sm text-white group-hover:text-blue-200 transition cursor-pointer line-clamp-1">
                                    <Link :href="route('teacher.courses.edit', course.id)">{{ course.title }}</Link>
                                </h3>
                                <span class="text-[9px] text-white/90 font-bold uppercase tracking-wide bg-black/30 backdrop-blur-sm px-1.5 py-0.5 rounded border border-white/20">
                                    {{ course.difficulty_level }}
                                </span>
                            </div>
                        </div>

                        <div class="p-3 flex-1 relative flex flex-col justify-between">
                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-md p-2 mb-1 border border-slate-100 dark:border-slate-700/50 flex justify-between items-center group/code cursor-pointer hover:border-blue-400 transition" 
                                 @click="copyCode(course.enrollment_code)" title="Click to copy code">
                                <div>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider block">Code</span>
                                    <span class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 tracking-widest">{{ course.enrollment_code }}</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover/code:text-blue-500 dark:group-hover/code:text-blue-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </div>

                            <p class="text-slate-500 dark:text-slate-400 text-[10px] line-clamp-2 leading-relaxed mt-1">{{ course.description || 'No description provided.' }}</p>
                        </div>

                        <div class="p-3 pt-0 flex gap-2">
                            <Link :href="route('teacher.courses.show', course.id)" 
                                class="flex-1 flex items-center justify-center gap-2 py-2 bg-blue-600 text-white hover:bg-blue-500 rounded-md transition font-bold text-[10px] uppercase tracking-wider shadow-sm">
                                Manage
                            </Link>
                            <Link :href="route('teacher.courses.edit', course.id)" 
                                class="px-2.5 py-2 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-600 rounded-md transition" 
                                title="Settings">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </Link>
                        </div>
                    </div>  
                </div>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-4">Create New Class</h3>
                <form @submit.prevent="submitCourse" class="space-y-4">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Class Name</label>
                            <input v-model="form.title" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500" required autofocus />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Difficulty</label>
                            <select v-model="form.difficulty_level" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Description</label>
                        <textarea v-model="form.description" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs h-24 resize-none focus:ring-blue-500" placeholder="Brief overview of the class..."></textarea>
                        <InputError class="mt-1" :message="form.errors.description" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showCreateModal = false" class="text-sm text-slate-500 px-3 py-2 font-bold hover:text-slate-700 dark:hover:text-slate-300 transition">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition">Create Class</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>