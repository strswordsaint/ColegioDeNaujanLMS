<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { Plus, Edit3 } from 'lucide-vue-next';

const props = defineProps({
    courses: Array,
    teachers: Array 
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCourse = ref(null);
const imageErrors = ref({});

// Form for Creating
const form = useForm({
    title: '',
    description: '',
    difficulty_level: 'beginner',
    teacher_id: '',
    thumbnail: null
});

// Form for Editing (includes _method: 'PATCH' for file uploads)
const editForm = useForm({
    _method: 'PATCH',
    title: '',
    description: '',
    difficulty_level: 'beginner',
    teacher_id: '',
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
    form.post(route('admin.courses.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const openEditModal = (course) => {
    editingCourse.value = course;
    editForm.title = course.title;
    editForm.description = course.description || '';
    editForm.difficulty_level = course.difficulty_level || 'beginner';
    editForm.teacher_id = course.teacher_id || '';
    editForm.thumbnail = null; // Clear previous file selection
    editForm.clearErrors();
    showEditModal.value = true;
};

const submitEdit = () => {
    // Using POST with _method: PATCH to support file uploads in Laravel
    editForm.post(route('teacher.courses.update', editingCourse.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingCourse.value = null;
        }
    });
};

const deleteCourse = (id) => {
    if (confirm('Are you sure you want to delete this course completely?')) {
        router.delete(route('teacher.courses.destroy', id), { preserveScroll: true });
    }
};

const formatYearLevel = (level) => {
    const levels = {
        'beginner': '1st Year',
        'intermediate': '2nd Year',
        'advanced': '3rd Year',
        'final': '4th Year'
    };
    return levels[level] || level;
};
</script>

<template>
    <Head title="Course Oversight" />

    <AuthenticatedLayout>
        <div class="max-w-screen-2xl mx-auto flex flex-col h-full">
            
            <div class="mb-4 pb-3 border-b border-slate-200 dark:border-slate-700 shrink-0 px-1 sm:px-0">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Course Oversight</h1>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1">Manage all classes and assign teachers</p>
            </div>

            <div class="flex gap-4 items-start flex-1 min-h-0">
                
                <aside class="w-10 shrink-0 flex flex-col gap-3 sticky top-4 z-30">
                    <button @click="showCreateModal = true" class="group relative flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                        <Plus class="w-5 h-5" />
                        <span class="absolute left-full ml-2 px-2 py-1 bg-slate-800 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg z-50">Assign New Course</span>
                    </button>
                </aside>

                <div class="flex-1 min-w-0 flex flex-col h-full pb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 overflow-y-auto">
                        
                        <div v-for="course in courses" :key="course.id" 
                             class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700
                                    transition-all duration-200 flex flex-row sm:flex-col shadow-sm h-[115px] sm:h-[270px]
                                    hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md relative">
                            
                            <div class="absolute top-2 right-2 z-40">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="p-1 rounded-md bg-black/40 text-white hover:bg-black/60 backdrop-blur-sm transition shadow-sm focus:outline-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <button @click.prevent="openEditModal(course)" class="block w-full text-left px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                            Edit Settings
                                        </button>
                                        <button @click.prevent="deleteCourse(course.id)" class="block w-full text-left px-4 py-2 text-[10px] font-black uppercase tracking-widest text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            Delete Course
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>

                            <div class="w-[100px] sm:w-full h-full sm:h-32 relative bg-slate-100 dark:bg-slate-900 shrink-0 border-r sm:border-r-0 sm:border-b border-slate-200 dark:border-slate-700 rounded-l-xl sm:rounded-t-xl sm:rounded-bl-none overflow-hidden">
                                <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                     :src="course.thumbnail" 
                                     @error="handleImageError(course.id)"
                                     class="absolute inset-0 w-full h-full object-cover z-0 transition-transform duration-700 group-hover:scale-105" />
                                
                                <div class="absolute inset-0 bg-slate-200 dark:bg-slate-900 flex items-center justify-center" v-else>
                                     <svg class="w-8 h-8 text-slate-400 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent hidden sm:block z-10 pointer-events-none"></div>

                                <div class="absolute sm:bottom-2 sm:left-2 top-2 left-2 z-20">
                                    <span class="text-[8px] font-black uppercase tracking-widest bg-white/90 text-blue-600 px-1.5 py-0.5 rounded shadow-sm">
                                        {{ formatYearLevel(course.difficulty_level) }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-2 sm:p-3 flex-1 flex flex-col justify-between min-w-0">
                                <div class="min-w-0">
                                    <Link :href="route('teacher.courses.show', course.id)" class="block font-black text-sm sm:text-base text-slate-900 dark:text-white group-hover:text-blue-600 transition truncate leading-tight pr-6 sm:pr-0 mb-0.5">
                                        {{ course.title }}
                                    </Link>
                                    
                                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-1.5 truncate">
                                        Instruct: <span class="text-slate-700 dark:text-slate-300">{{ course.teacher?.name || 'Unassigned' }}</span>
                                    </p>

                                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded inline-flex px-1.5 py-1 border border-slate-100 dark:border-slate-700/50 items-center group/code cursor-pointer hover:border-blue-400 transition" 
                                         @click="copyCode(course.enrollment_code)" title="Click to copy code">
                                        <span class="text-[8px] sm:text-[9px] text-slate-400 dark:text-slate-500 uppercase font-black tracking-widest mr-1.5">Code:</span>
                                        <span class="text-[10px] sm:text-xs font-mono font-bold text-blue-600 dark:text-blue-400 tracking-widest leading-none">{{ course.enrollment_code }}</span>
                                    </div>
                                    
                                    <div class="hidden sm:flex items-center gap-1.5 mt-2">
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded">{{ course.enrollments?.length || 0 }} Studs</span>
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded">{{ course.lessons_count || 0 }} Less</span>
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded">{{ course.assignments_count || 0 }} Task</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 sm:gap-2 mt-2 sm:pt-3 sm:border-t border-slate-100 dark:border-slate-700">
                                    <Link :href="route('teacher.courses.show', course.id)" 
                                        class="flex-1 flex items-center justify-center py-1.5 sm:py-2 bg-blue-600 text-white hover:bg-blue-500 rounded sm:rounded-md transition font-black text-[9px] sm:text-[10px] uppercase tracking-widest shadow-sm">
                                        Manage Class
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="sm">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    Assign New Course
                </h3>
                
                <form @submit.prevent="submitCourse" class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Course Title *</label>
                        <input v-model="form.title" type="text" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white" required autofocus>
                        <InputError class="mt-1 text-[9px]" :message="form.errors.title" />
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Assign Teacher *</label>
                        <select v-model="form.teacher_id" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white cursor-pointer" required>
                            <option value="" disabled>Select a teacher...</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                        <InputError class="mt-1 text-[9px]" :message="form.errors.teacher_id" />
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Description (Optional)</label>
                        <textarea v-model="form.description" class="w-full rounded-md border border-slate-200 bg-transparent px-3 py-2 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white h-20 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Year Level</label>
                            <select v-model="form.difficulty_level" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white cursor-pointer">
                                <option value="beginner">1st Year</option>
                                <option value="intermediate">2nd Year</option>
                                <option value="advanced">3rd Year</option>
                                <option value="final">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Thumbnail (Optional)</label>
                            <input type="file" @input="form.thumbnail = $event.target.files[0]" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[9px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 dark:border-slate-800 rounded-md p-1 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showCreateModal = false" class="text-[10px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition-colors">Create & Assign</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showEditModal" @close="showEditModal = false" maxWidth="sm">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center">
                        <Edit3 class="w-3.5 h-3.5" />
                    </div>
                    Edit Course Settings
                </h3>
                
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Course Title *</label>
                        <input v-model="editForm.title" type="text" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white" required>
                        <InputError class="mt-1 text-[9px]" :message="editForm.errors.title" />
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Appoint Teacher *</label>
                        <select v-model="editForm.teacher_id" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white cursor-pointer" required>
                            <option value="" disabled>Select a teacher...</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                        <InputError class="mt-1 text-[9px]" :message="editForm.errors.teacher_id" />
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Description</label>
                        <textarea v-model="editForm.description" class="w-full rounded-md border border-slate-200 bg-transparent px-3 py-2 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white h-20 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Year Level</label>
                            <select v-model="editForm.difficulty_level" class="w-full h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-slate-800 dark:text-white cursor-pointer">
                                <option value="beginner">1st Year</option>
                                <option value="intermediate">2nd Year</option>
                                <option value="advanced">3rd Year</option>
                                <option value="final">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Update Thumbnail</label>
                            <input type="file" @input="editForm.thumbnail = $event.target.files[0]" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[9px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 dark:border-slate-800 rounded-md p-1 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showEditModal = false" class="text-[10px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition">Cancel</button>
                        <button :disabled="editForm.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>