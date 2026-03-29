<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue'; 

defineProps({
    courses: Array
});

const imageErrors = ref({});
const showCreateModal = ref(false);

const form = useForm({
    title: '',
    description: '',
    difficulty_level: 'beginner',
    thumbnail: null
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
        
        <div class="mb-6 px-1 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">My Classes</h1>
            <p class="text-slate-500 dark:text-slate-400 text-[10px] sm:text-xs mt-1 font-bold uppercase tracking-widest">Manage your course content and students.</p>
        </div>

        <div class="flex gap-4 sm:gap-6 items-start">
            
            <aside class="w-12 sm:w-14 shrink-0 flex flex-col gap-4 sticky top-6 z-10">
                <button @click="showCreateModal = true" 
                        class="group relative flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                    
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    
                    <span class="absolute left-full ml-3 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 sm:group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg z-20">
                        Create Class
                        <span class="absolute top-1/2 right-full -mt-1 -mr-1 border-4 border-transparent border-r-slate-800"></span>
                    </span>
                </button>
            </aside>

            <div class="flex-1 min-w-0">
                <div v-if="courses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                    
                    <div v-for="course in courses" :key="course.id" 
                         class="group bg-white dark:bg-slate-800 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700
                                transition-all duration-200 flex flex-row sm:flex-col shadow-sm h-[110px] sm:h-[280px]
                                hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md">
                        
                        <div class="w-[100px] sm:w-full h-full sm:h-32 relative bg-slate-100 dark:bg-slate-900 shrink-0 border-r sm:border-r-0 sm:border-b border-slate-200 dark:border-slate-700 overflow-hidden">
                            <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                 :src="course.thumbnail" 
                                 @error="handleImageError(course.id)"
                                 class="absolute inset-0 w-full h-full object-cover z-0 transition-transform duration-700 group-hover:scale-105" />
                            
                            <div class="absolute inset-0 bg-slate-200 dark:bg-slate-900 flex items-center justify-center" v-else>
                                 <svg class="w-8 h-8 text-slate-400 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent hidden sm:block z-10"></div>

                            <div class="absolute sm:bottom-2 sm:left-2 top-2 left-2 z-20">
                                <span class="text-[8px] font-black uppercase tracking-widest bg-white/90 text-blue-600 px-1.5 py-0.5 rounded shadow-sm">
                                    {{ course.difficulty_level }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2 sm:p-3 flex-1 flex flex-col justify-between min-w-0">
                            
                            <div class="min-w-0">
                                <Link :href="route('teacher.courses.edit', course.id)" class="block font-bold text-sm sm:text-base text-slate-900 dark:text-white group-hover:text-blue-600 transition truncate leading-tight mb-1">
                                    {{ course.title }}
                                </Link>

                                <div class="bg-slate-50 dark:bg-slate-900/50 rounded inline-flex px-1.5 py-1 border border-slate-100 dark:border-slate-700/50 items-center group/code cursor-pointer hover:border-blue-400 transition" 
                                     @click="copyCode(course.enrollment_code)" title="Click to copy code">
                                    <span class="text-[8px] sm:text-[9px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-widest mr-1.5">Code:</span>
                                    <span class="text-[10px] sm:text-xs font-mono font-bold text-blue-600 dark:text-blue-400 tracking-widest leading-none">{{ course.enrollment_code }}</span>
                                </div>
                                
                                <p class="hidden sm:block text-slate-500 dark:text-slate-400 text-[10px] line-clamp-2 leading-relaxed mt-2">
                                    {{ course.description || 'No description provided.' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 sm:gap-2 mt-2 sm:pt-3 sm:border-t border-slate-100 dark:border-slate-700">
                                <Link :href="route('teacher.courses.show', course.id)" 
                                    class="flex-1 flex items-center justify-center py-1.5 sm:py-2 bg-blue-600 text-white hover:bg-blue-500 rounded sm:rounded-md transition font-bold text-[9px] sm:text-[10px] uppercase tracking-wider shadow-sm">
                                    Manage
                                </Link>
                                <Link :href="route('teacher.courses.edit', course.id)" 
                                    class="px-2 sm:px-2.5 py-1.5 sm:py-2 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-600 rounded sm:rounded-md transition shadow-sm shrink-0" 
                                    title="Settings">
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

                <div v-else class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 shadow-sm mx-1 sm:mx-0">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h3 class="mt-3 text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">No classes</h3>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">Get started by creating your first class.</p>
                </div>

            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
                <h3 class="font-black text-base text-slate-900 dark:text-white mb-4 uppercase tracking-tight flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    Create New Class
                </h3>
                <form @submit.prevent="submitCourse" class="space-y-4">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[9px] font-black uppercase text-slate-500 mb-1.5 tracking-widest">Class Name *</label>
                            <input v-model="form.title" type="text" class="w-full rounded-md bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-2 focus:ring-blue-500 shadow-sm transition" required autofocus />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[9px] font-black uppercase text-slate-500 mb-1.5 tracking-widest">Difficulty</label>
                            <select v-model="form.difficulty_level" class="w-full rounded-md bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-2 focus:ring-blue-500 shadow-sm transition cursor-pointer">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase text-slate-500 mb-1.5 tracking-widest">Description</label>
                        <textarea v-model="form.description" class="w-full rounded-md bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs h-24 resize-none focus:ring-2 focus:ring-blue-500 shadow-sm transition" placeholder="Brief overview of the class..."></textarea>
                        <InputError class="mt-1" :message="form.errors.description" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4 mt-2 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showCreateModal = false" class="text-[10px] text-slate-500 px-3 py-2 font-black uppercase tracking-widest hover:text-slate-700 dark:hover:text-slate-300 transition">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition">Create Class</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>