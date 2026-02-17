<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ course: Object });
const currentUser = usePage().props.auth.user;

const activeTab = ref('students');
const showLessonModal = ref(false);
const showAnnouncementModal = ref(false);
const showAssignmentModal = ref(false);

// Forms
const formLesson = useForm({ title: '', file: null });
const formAnnouncement = useForm({ content: '' });
const formComment = useForm({ content: '' });

// Assignment Form (With Multiple Files Support)
const formAssignment = useForm({
    title: '',
    description: '',
    points: 100,
    due_date: '',
    files: [] 
});

// --- COMPUTED PROPERTIES FOR STUDENTS ---
const pendingStudents = computed(() => props.course.enrollments ? props.course.enrollments.filter(e => e.status === 'pending') : []);
const approvedStudents = computed(() => props.course.enrollments ? props.course.enrollments.filter(e => e.status === 'approved') : []);

// --- METHODS ---

// Student Management
const approveStudent = (userId) => {
    router.patch(route('teacher.courses.enrollments.approve', { course: props.course.id, user: userId }), {}, { preserveScroll: true });
};

const removeStudent = (userId) => {
    if(confirm('Remove this student from the class?')) {
        router.delete(route('teacher.courses.enrollments.destroy', { course: props.course.id, user: userId }), { preserveScroll: true });
    }
};

// General Actions
const toggleComments = (announcement) => { announcement.showComments = !announcement.showComments; };
const copyCode = () => { navigator.clipboard.writeText(props.course.enrollment_code); alert('Copied!'); };
const deleteItem = (url) => { if (confirm('Are you sure?')) router.delete(url, { preserveScroll: true }); };

// Submissions
const submitAnnouncement = () => {
    formAnnouncement.post(route('teacher.announcements.store', props.course.id), {
        onSuccess: () => { showAnnouncementModal.value = false; formAnnouncement.reset(); }
    });
};

const submitComment = (announcementId) => {
    formComment.post(route('comments.store', announcementId), {
        onSuccess: () => {
            formComment.reset();
            const post = props.course.announcements.find(a => a.id === announcementId);
            if(post) post.showComments = true;
        },
        preserveScroll: true
    });
};

const submitAssignment = () => {
    formAssignment.post(route('teacher.assignments.store', props.course.id), {
        onSuccess: () => { showAssignmentModal.value = false; formAssignment.reset(); }
    });
};

const submitLesson = () => { 
    formLesson.post(route('teacher.lessons.store', props.course.id), { 
        onSuccess: () => { showLessonModal.value = false; formLesson.reset(); } 
    }); 
};
</script>

<template>
    <Head :title="`Manage: ${course.title}`" />

    <AuthenticatedLayout>
        <div class="mb-6 flex justify-between items-center">
             <div class="flex items-center gap-3">
                 <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ course.title }}</h1>
                 <span class="text-[10px] font-mono bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded text-slate-600 dark:text-slate-300 cursor-pointer hover:bg-slate-300 transition" @click="copyCode" title="Click to copy code">{{ course.enrollment_code }}</span>
             </div>
             <Link :href="route('teacher.courses.edit', { course: course.id, source: 'manage' })" class="text-xs font-bold text-blue-600 hover:underline">Settings</Link>
        </div>

        <div class="flex gap-6 items-start">
            
            <aside class="w-14 shrink-0 flex flex-col gap-4 sticky top-6 z-10">
                <button @click="showAnnouncementModal = true" class="group relative flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-purple-600 hover:border-purple-600 hover:bg-purple-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    <span class="absolute left-full ml-3 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Post Announcement</span>
                </button>

                <button @click="showAssignmentModal = true" class="group relative flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="absolute left-full ml-3 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Create Assignment</span>
                </button>

                <button @click="showLessonModal = true" class="group relative flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-emerald-600 hover:border-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span class="absolute left-full ml-3 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Upload Materials</span>
                </button>
            </aside>

            <div class="flex-1 min-w-0">
                
                <div class="flex gap-6 border-b border-slate-200 dark:border-slate-700 mb-6">
                    <button @click="activeTab = 'students'" class="pb-2 text-sm font-bold border-b-2 transition-colors flex items-center gap-2" :class="activeTab === 'students' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">
                        Students <span v-if="pendingStudents.length" class="bg-red-500 text-white text-[10px] px-1.5 rounded-full">{{ pendingStudents.length }}</span>
                    </button>
                    <button @click="activeTab = 'announcements'" class="pb-2 text-sm font-bold border-b-2 transition-colors" :class="activeTab === 'announcements' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Announcements</button>
                    <button @click="activeTab = 'assignments'" class="pb-2 text-sm font-bold border-b-2 transition-colors" :class="activeTab === 'assignments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Assignments</button>
                    <button @click="activeTab = 'materials'" class="pb-2 text-sm font-bold border-b-2 transition-colors" :class="activeTab === 'materials' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Materials</button>
                </div>

                <div v-if="activeTab === 'students'" class="space-y-6">
                    
                    <div v-if="pendingStudents.length > 0" class="bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-200 dark:border-yellow-900/30 p-4">
                        <h3 class="text-xs font-black text-yellow-700 dark:text-yellow-500 uppercase mb-3">Pending Requests</h3>
                        <div class="space-y-2">
                            <div v-for="enrollment in pendingStudents" :key="enrollment.id" class="flex items-center justify-between bg-white dark:bg-slate-800 p-3 rounded-lg border border-yellow-100 dark:border-yellow-900/20 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 flex items-center justify-center font-bold text-xs">{{ enrollment.user.name.charAt(0) }}</div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ enrollment.user.name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ enrollment.user.email }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="removeStudent(enrollment.user_id)" class="text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-3 py-1.5 rounded transition">Reject</button>
                                    <button @click="approveStudent(enrollment.user_id)" class="text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded shadow-sm transition">Accept</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/30">
                            <h3 class="text-xs font-black text-slate-500 uppercase">Enrolled Students ({{ approvedStudents.length }})</h3>
                        </div>
                        <div v-if="approvedStudents.length > 0">
                            <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                                <thead class="text-[10px] uppercase font-bold text-slate-400 bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700">
                                    <tr>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Joined</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <tr v-for="enrollment in approvedStudents" :key="enrollment.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                        <td class="px-4 py-3 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-xs">{{ enrollment.user.name.charAt(0) }}</div>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ enrollment.user.name }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs">{{ new Date(enrollment.enrolled_at).toLocaleDateString() }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button @click="removeStudent(enrollment.user_id)" class="text-red-500 hover:text-red-700 text-xs font-bold bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded border border-red-100 dark:border-red-900/30">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-10 text-slate-400 text-xs italic">
                            No students enrolled yet. Share the code: <span class="font-mono font-bold text-slate-600 dark:text-slate-300">{{ course.enrollment_code }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'announcements'" class="space-y-4 max-w-2xl">
                    <div v-for="post in course.announcements" :key="post.id" class="bg-white dark:bg-slate-800 rounded-lg border-2 border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden hover:border-blue-300 dark:hover:border-blue-600 transition-colors duration-200">
                        <div class="p-3 pb-1 flex justify-between items-start">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center text-[10px] font-bold">{{ post.user.name.charAt(0) }}</div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ post.user.name }}</h4>
                                    <p class="text-[9px] text-slate-400">{{ new Date(post.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                            <button @click="deleteItem(route('teacher.announcements.destroy', post.id))" class="text-slate-300 hover:text-red-500 text-sm">&times;</button>
                        </div>
                        <div class="px-3 py-1 text-xs text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">{{ post.content }}</div>
                        <div class="px-3 py-1.5 flex items-center gap-4 border-t border-slate-50 dark:border-slate-700/50 mt-2 bg-slate-50/50 dark:bg-slate-900/20">
                            <button @click="toggleComments(post)" class="text-xs font-bold text-slate-500 hover:text-blue-600 flex items-center gap-1.5 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                Comment <span v-if="post.comments.length" class="text-[9px] bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-1.5 rounded-full font-bold">{{ post.comments.length }}</span>
                            </button>
                        </div>
                        <div v-if="post.showComments" class="bg-slate-50 dark:bg-slate-900/30 p-2 border-t border-slate-100 dark:border-slate-700">
                            <div v-if="post.comments.length > 0" class="space-y-2 mb-2">
                                <div v-for="comment in post.comments" :key="comment.id" class="flex gap-2 group">
                                    <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-300 shrink-0">{{ comment.user.name.charAt(0) }}</div>
                                    <div class="bg-white dark:bg-slate-800 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 flex-1 relative shadow-sm">
                                        <div class="flex justify-between items-start">
                                            <span class="font-bold text-slate-900 dark:text-white text-[10px]">{{ comment.user.name }}</span>
                                            <button @click="deleteItem(route('comments.destroy', comment.id))" class="text-[9px] text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100">&times;</button>
                                        </div>
                                        <span class="text-[10px] text-slate-700 dark:text-slate-300 block leading-tight">{{ comment.content }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center">
                                <div class="w-5 h-5 rounded-full bg-blue-600 flex items-center justify-center text-white text-[8px] font-bold shrink-0">{{ currentUser.name.charAt(0) }}</div>
                                <div class="flex-1 relative">
                                    <input v-model="formComment.content" @keyup.enter="submitComment(post.id)" type="text" placeholder="Write a reply..." class="w-full bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded-md py-1 px-2 text-[10px] focus:ring-1 focus:ring-blue-500 h-7" />
                                    <button @click="submitComment(post.id)" class="absolute right-1.5 top-1 text-blue-600 hover:text-blue-500">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="course.announcements.length === 0" class="text-center py-8 text-slate-400 text-xs italic">No updates posted yet. Use the sidebar to post.</div>
                </div>

                <div v-if="activeTab === 'assignments'" class="space-y-3">
                    <div v-if="course.assignments && course.assignments.length > 0" class="space-y-3">
                        <Link v-for="assignment in course.assignments" :key="assignment.id" 
                              :href="route('teacher.assignments.show', { assignment: assignment.id, source: 'course' })"
                              class="group bg-white dark:bg-slate-800 rounded-xl p-3 border-2 border-slate-200 dark:border-slate-700 hover:border-blue-600 dark:hover:border-blue-500 shadow-sm transition-all duration-200 flex items-center gap-3">
                            <div class="shrink-0 p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ assignment.title }}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">Due: {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString() : 'No Date' }}</span>
                                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">{{ assignment.points }} pts</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-12 bg-slate-50 dark:bg-slate-900/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                        <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">No assignments yet. Use the sidebar to create one.</p>
                    </div>
                </div>

                <div v-if="activeTab === 'materials'" class="space-y-3">
                    <div v-if="course.lessons && course.lessons.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                        <div v-for="lesson in course.lessons" :key="lesson.id" 
                             class="flex items-center justify-between p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0">
                                    <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ lesson.title }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a :href="`/storage/${lesson.attachment_path}`" target="_blank" class="text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded hover:bg-blue-100 hover:text-blue-600 transition">View</a>
                                <a :href="`/storage/${lesson.attachment_path}`" download class="text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded hover:bg-emerald-100 hover:text-emerald-600 transition">Download</a>
                                <button @click="deleteItem(route('teacher.lessons.destroy', lesson.id))" class="text-slate-300 hover:text-red-500 dark:text-slate-600 dark:hover:text-red-400 transition ml-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400 italic text-sm">No learning materials posted yet. Use the sidebar to upload.</div>
                </div>

            </div>
        </div>
        
        <Modal :show="showLessonModal" @close="showLessonModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-4">Upload Material</h3>
                <form @submit.prevent="submitLesson" class="space-y-4">
                    <input v-model="formLesson.title" type="text" placeholder="Title" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white" required>
                    <input type="file" @input="formLesson.file = $event.target.files[0]" class="w-full text-sm text-slate-500" required>
                    <div class="flex justify-end"><button class="bg-emerald-600 text-white px-4 py-2 rounded text-sm font-bold">Upload</button></div>
                </form>
            </div>
        </Modal>

        <Modal :show="showAnnouncementModal" @close="showAnnouncementModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-4">Post Announcement</h3>
                <form @submit.prevent="submitAnnouncement" class="space-y-4">
                    <textarea v-model="formAnnouncement.content" placeholder="Share an update with your class..." class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white h-32 text-sm resize-none focus:ring-purple-500" required autofocus></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showAnnouncementModal = false" class="text-sm text-slate-500 px-3 py-2 font-bold hover:text-slate-700">Cancel</button>
                        <button class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm">Post Update</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showAssignmentModal" @close="showAssignmentModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-4">Create Assignment</h3>
                <form @submit.prevent="submitAssignment" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Title</label>
                        <input v-model="formAssignment.title" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500" required autofocus />
                        <InputError class="mt-1" :message="formAssignment.errors.title" />
                    </div>
                    <div>
                         <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Instructions</label>
                        <textarea v-model="formAssignment.description" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs h-24 focus:ring-blue-500 resize-none" placeholder="Instructions..."></textarea>
                        <InputError class="mt-1" :message="formAssignment.errors.description" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Attachments (Optional)</label>
                        <div class="relative">
                            <input 
                                type="file" 
                                multiple 
                                @change="e => formAssignment.files = Array.from(e.target.files)"
                                class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded" 
                            />
                        </div>
                        <div v-if="formAssignment.files && formAssignment.files.length > 0" class="mt-2 space-y-1">
                            <div v-for="(file, index) in formAssignment.files" :key="index" class="text-[10px] text-slate-600 dark:text-slate-400 flex items-center gap-1">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                {{ file.name }}
                            </div>
                        </div>
                        <InputError class="mt-1" :message="formAssignment.errors.files" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Points</label>
                            <input v-model="formAssignment.points" type="number" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Due Date</label>
                            <input v-model="formAssignment.due_date" type="datetime-local" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-2 text-xs focus:ring-blue-500" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showAssignmentModal = false" class="text-sm text-slate-500 px-3 py-2 font-bold hover:text-slate-700">Cancel</button>
                        <button :disabled="formAssignment.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm">Create</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>