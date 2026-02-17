<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { ref } from 'vue';

const props = defineProps({ joinedCourses: Array });

const showJoinModal = ref(false);
const form = useForm({ code: '' });
const imageErrors = ref({});

const handleImageError = (id) => { imageErrors.value[id] = true; };

const submitJoin = () => {
    form.post(route('student.courses.join'), {
        onSuccess: () => { showJoinModal.value = false; form.reset(); }
    });
};
</script>

<template>
    <Head title="My Classes" />

    <AuthenticatedLayout>
        <div class="mb-4">
            <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">My Classes</h1>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Access your learning materials.</p>
        </div>

        <div class="flex gap-4 items-start">
            
            <aside class="w-10 shrink-0 flex flex-col gap-3 sticky top-6 z-10">
                <button @click="showJoinModal = true" 
                        class="group relative flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    
                    <span class="absolute left-full ml-2 px-2 py-1 bg-slate-800 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap shadow-lg z-20">
                        Join Class
                    </span>
                </button>
            </aside>

            <div class="flex-1 min-w-0">
                <div v-if="joinedCourses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div v-for="course in joinedCourses" :key="course.id" class="group bg-white dark:bg-slate-800 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 transition flex flex-col h-[200px] shadow-sm hover:border-blue-500 dark:hover:border-blue-500 hover:-translate-y-0.5">
                        <div class="h-24 bg-slate-100 dark:bg-slate-900 relative overflow-hidden shrink-0">
                            <img v-if="course.thumbnail && !imageErrors[course.id]" :src="course.thumbnail" @error="handleImageError(course.id)" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            
                            <div v-if="course.pivot.status === 'pending'" class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-[1px]">
                                <span class="bg-yellow-500 text-white text-[9px] font-black px-2 py-1 rounded uppercase tracking-wider shadow-lg">Pending Approval</span>
                            </div>
                            
                            <div v-else class="absolute bottom-2 left-2 right-2">
                                <span class="text-[8px] text-white/90 font-bold uppercase bg-black/30 backdrop-blur-sm px-1 py-0.5 rounded border border-white/20">{{ course.difficulty_level }}</span>
                            </div>
                        </div>

                        <div class="p-2.5 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ course.title }}</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed mt-0.5">{{ course.description || 'No description.' }}</p>
                            </div>
                            
                            <div class="mt-2 flex gap-2">
                                <Link v-if="course.pivot.status === 'approved'" :href="route('student.courses.show', course.id)" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-bold py-1.5 rounded text-center transition uppercase tracking-wider">
                                    Enter Class
                                </Link>
                                <button v-else disabled class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 text-[9px] font-bold py-1.5 rounded text-center cursor-not-allowed border border-slate-200 dark:border-slate-600 uppercase tracking-wider">
                                    Waiting...
                                </button>

                                <Link :href="route('student.courses.leave', course.id)" method="delete" as="button" class="px-2 py-1.5 bg-slate-50 dark:bg-slate-700 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-slate-400 rounded transition border border-slate-200 dark:border-slate-600 flex items-center justify-center" title="Leave Class">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-20 text-center bg-slate-50 dark:bg-slate-800/50 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700">
                    <div class="bg-white dark:bg-slate-800 p-3 rounded-full mb-2 shadow-sm">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-sm text-slate-900 dark:text-white font-bold">No Classes Yet</h3>
                    <p class="text-[10px] text-slate-500 mb-3">Join a class to get started.</p>
                    <button @click="showJoinModal = true" class="text-blue-600 font-bold text-[10px] hover:underline">Click sidebar button to Join</button>
                </div>
            </div>
        </div>

        <Modal :show="showJoinModal" @close="showJoinModal = false">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-4">Join a Class</h3>
                <form @submit.prevent="submitJoin" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Class Code</label>
                        <input v-model="form.code" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-center text-lg font-mono tracking-widest uppercase py-2 focus:ring-blue-500" placeholder="A1B2C3" maxlength="6" required autofocus />
                        <InputError class="mt-1 text-center" :message="form.errors.code" />
                    </div>
                    <p class="text-[10px] text-slate-400 text-center">Ask your teacher for the 6-character code.</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showJoinModal = false" class="text-[10px] font-bold text-slate-500 px-3 py-1.5 hover:text-slate-700">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold px-4 py-1.5 rounded shadow-sm transition">Join Class</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>