<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    courses: Array,
    teachers: Array // Newly passed from the controller
});

const showCreateModal = ref(false);

const form = useForm({
    title: '',
    description: '',
    difficulty_level: 'beginner',
    teacher_id: '',
    thumbnail: null
});

const submitCourse = () => {
    form.post(route('admin.courses.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const deleteCourse = (id) => {
    if (confirm('Are you sure you want to delete this course completely?')) {
        router.delete(route('teacher.courses.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Course Oversight" />

    <AuthenticatedLayout>
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Course Oversight</h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Manage all classes and assign teachers</p>
            </div>
            <button @click="showCreateModal = true" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest shadow-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Assign New Course
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link v-for="course in courses" :key="course.id" :href="route('teacher.courses.show', course.id)" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm hover:border-blue-500 transition group cursor-pointer flex flex-col">
                <div class="aspect-video bg-slate-100 dark:bg-slate-900 relative">
                    <img v-if="course.thumbnail" :src="course.thumbnail" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-300 dark:text-slate-700">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="absolute top-2 right-2 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-2 py-1 rounded text-[9px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-300 shadow-sm border border-slate-200 dark:border-slate-700">
                        Code: <span class="text-blue-600">{{ course.enrollment_code }}</span>
                    </div>
                </div>

                <div class="p-4 flex-1 flex flex-col">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[8px] font-black uppercase tracking-widest bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded border border-blue-100 dark:border-blue-800">{{ course.difficulty_level }}</span>
                        <span class="text-[8px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-1.5 py-0.5 rounded border border-emerald-100 dark:border-emerald-800">{{ course.enrollments.length }} Students</span>
                    </div>
                    
                    <h3 class="font-black text-slate-900 dark:text-white leading-tight mb-1 group-hover:text-blue-600 transition">{{ course.title }}</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-4 flex-1">Instructor: <span class="text-slate-700 dark:text-slate-300">{{ course.teacher?.name || 'Unassigned' }}</span></p>

                    <div class="flex justify-between items-center pt-3 border-t border-slate-100 dark:border-slate-700 mt-auto">
                        <div class="flex gap-3 text-[10px] font-bold text-slate-500">
                            <span>{{ course.lessons_count }} Lessons</span>
                            <span>{{ course.assignments_count }} Tasks</span>
                        </div>
                        <button @click.prevent="deleteCourse(course.id)" class="text-[10px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded transition">Delete</button>
                    </div>
                </div>
            </Link>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="md">
            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Assign New Course
                </h3>
                
                <form @submit.prevent="submitCourse" class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Course Title</label>
                        <input v-model="form.title" type="text" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white" required>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Assign Teacher</label>
                        <select v-model="form.teacher_id" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white" required>
                            <option value="" disabled>Select a teacher...</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Description (Optional)</label>
                        <textarea v-model="form.description" class="w-full rounded-md border border-slate-200 bg-transparent px-3 py-2 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white h-20 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Difficulty</label>
                            <select v-model="form.difficulty_level" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Thumbnail (Optional)</label>
                            <input type="file" @input="form.thumbnail = $event.target.files[0]" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[9px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 dark:border-slate-800 rounded-md p-1 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showCreateModal = false" class="text-[10px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 uppercase tracking-widest">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition-colors">Create & Assign</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>