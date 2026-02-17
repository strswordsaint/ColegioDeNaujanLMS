<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, computed } from 'vue'; 
import Modal from '@/Components/Modal.vue';

const props = defineProps({ course: Object });
const currentUser = usePage().props.auth.user;
const activeTab = ref('announcements');
const assignmentFilter = ref('upcoming'); // <--- New Sub-Tab State
const highlightedId = ref(null);

const showSubmitModal = ref(false);
const selectedAssignment = ref(null);
const formComment = useForm({ content: '' });
const formSubmission = useForm({ files: [] });

// --- FILTER LOGIC FOR ASSIGNMENTS ---
const filteredAssignments = computed(() => {
    const now = new Date();
    return props.course.assignments.filter(a => {
        const isSubmitted = a.submissions.length > 0;
        const dueDate = a.due_date ? new Date(a.due_date) : null;
        const isPastDue = dueDate && dueDate < now;

        if (assignmentFilter.value === 'completed') {
            return isSubmitted;
        } else if (assignmentFilter.value === 'past_due') {
            return !isSubmitted && isPastDue;
        } else {
            // Upcoming (Not submitted AND (Future date OR No deadline))
            return !isSubmitted && (!isPastDue || !dueDate);
        }
    }).sort((a, b) => {
        // Sort by Due Date
        if (!a.due_date) return 1;
        if (!b.due_date) return -1;
        return new Date(a.due_date) - new Date(b.due_date);
    });
});

// --- AUTO-SCROLL LOGIC ---
onMounted(async () => {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    const target = params.get('target');

    if (tab && ['announcements', 'assignments', 'materials'].includes(tab)) {
        activeTab.value = tab;
        await nextTick();

        if (target) {
            // If targeting an assignment, verify which filter it belongs to and switch tab
            if (tab === 'assignments') {
                const targetAssignment = props.course.assignments.find(a => a.id == target);
                if (targetAssignment) {
                    const isSubmitted = targetAssignment.submissions.length > 0;
                    const isPast = targetAssignment.due_date && new Date(targetAssignment.due_date) < new Date();
                    
                    if (isSubmitted) assignmentFilter.value = 'completed';
                    else if (isPast) assignmentFilter.value = 'past_due';
                    else assignmentFilter.value = 'upcoming';
                    
                    await nextTick(); // Wait for filter switch to render
                }
            }

            const element = document.getElementById(`item-${target}`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                highlightedId.value = parseInt(target);
                setTimeout(() => highlightedId.value = null, 4000); 
            }
        }
    }
});

const linkify = (text) => text ? text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline font-bold">$1</a>') : '';

const getStatus = (assignment) => {
    const sub = assignment.submissions[0];
    if (!sub) return assignment.due_date && new Date(assignment.due_date) < new Date() 
        ? { label: 'Missing', class: 'text-red-600 bg-red-50 border-red-200' } 
        : { label: 'To Do', class: 'text-slate-500 bg-slate-100 border-slate-200' };
    if (sub.grade !== null) return { label: `Graded: ${sub.grade}/${assignment.points}`, class: 'text-green-600 bg-green-50 border-green-200' };
    return { label: 'Submitted', class: 'text-blue-600 bg-blue-50 border-blue-200' };
};

const getPaths = (paths) => {
    if (Array.isArray(paths)) return paths;
    try { return JSON.parse(paths) || []; } catch (e) { return []; }
};

const toggleComments = (post) => { post.showComments = !post.showComments; };
const submitComment = (id) => formComment.post(route('comments.store', id), { onSuccess: () => { formComment.reset(); const p = props.course.announcements.find(a => a.id === id); if(p) p.showComments = true; }, preserveScroll: true });
const deleteComment = (id) => { if(confirm('Delete?')) router.delete(route('comments.destroy', id), { preserveScroll: true }); };
const openSubmitModal = (a) => { selectedAssignment.value = a; formSubmission.reset(); showSubmitModal.value = true; };
const submitWork = () => formSubmission.post(route('assignments.submit', selectedAssignment.value.id), { onSuccess: () => { showSubmitModal.value = false; formSubmission.reset(); }, preserveScroll: true });
const undoTurnIn = () => { if (confirm('Undo submission? This will remove your files.')) router.post(route('assignments.unsubmit', selectedAssignment.value.id), {}, { onSuccess: () => showSubmitModal.value = false, preserveScroll: true }); };
const leaveClass = () => { if (confirm('Leave this class? You will lose access.')) router.delete(route('student.courses.leave', props.course.id)); };
</script>

<template>
    <Head :title="course.title" />

    <AuthenticatedLayout>
        <div class="mb-4 flex justify-between items-start">
             <div>
                 <div class="flex items-center gap-2 mb-1">
                     <h1 class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ course.title }}</h1>
                     <span class="text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 select-all">{{ course.enrollment_code }}</span>
                 </div>
                 <button @click="leaveClass" class="text-[10px] font-bold text-red-500 hover:text-red-700 hover:underline flex items-center gap-1 transition">
                     <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                     Leave Class
                 </button>
             </div>
             
             <Link :href="route('student.courses')" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-md shadow-sm transition flex items-center gap-1">
                 <span></span> Back
             </Link>
        </div>

        <div class="flex gap-4 border-b border-slate-200 dark:border-slate-700 mb-4">
            <button @click="activeTab = 'announcements'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'announcements' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">Announcements</button>
            <button @click="activeTab = 'assignments'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'assignments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">Assignments</button>
            <button @click="activeTab = 'materials'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'materials' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">Materials</button>
        </div>

        <div class="max-w-3xl mx-auto min-h-[400px]">
            <div v-if="activeTab === 'announcements'" class="space-y-3 animate-fade-in">
                <div v-if="course.announcements.length === 0" class="text-center py-8 text-slate-400 text-[10px] italic">No updates posted yet.</div>
                <div v-for="post in course.announcements" :key="post.id" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-3 pb-1 flex gap-2 items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-black text-[10px]">{{ post.user.name.charAt(0) }}</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ post.user.name }}</h4>
                            <p class="text-[9px] text-slate-400">{{ new Date(post.created_at).toLocaleString() }}</p>
                        </div>
                    </div>
                    <div class="px-3 py-1 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ post.content }}</div>
                    <div class="px-3 py-1.5 border-t border-slate-50 dark:border-slate-700/50 mt-1 bg-slate-50/50 dark:bg-slate-900/30">
                        <button @click="toggleComments(post)" class="text-[10px] font-bold text-slate-500 hover:text-blue-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            Comment <span v-if="post.comments.length" class="bg-slate-200 dark:bg-slate-700 px-1 rounded-full ml-1">{{ post.comments.length }}</span>
                        </button>
                    </div>
                    <div v-if="post.showComments" class="bg-slate-50 dark:bg-slate-900/50 p-2 border-t border-slate-100 dark:border-slate-700">
                        <div class="space-y-2 mb-2">
                            <div v-for="comment in post.comments" :key="comment.id" class="flex gap-2 group">
                                <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[8px] font-bold">{{ comment.user.name.charAt(0) }}</div>
                                <div class="bg-white dark:bg-slate-800 p-1.5 rounded border border-slate-200 dark:border-slate-700 flex-1 shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <span class="font-bold text-slate-900 dark:text-white text-[9px]">{{ comment.user.name }}</span>
                                        <button v-if="comment.user_id === currentUser.id" @click="deleteComment(comment.id)" class="text-[9px] text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100">&times;</button>
                                    </div>
                                    <span class="text-[10px] text-slate-700 dark:text-slate-300 block leading-tight">{{ comment.content }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 items-center">
                            <input v-model="formComment.content" @keyup.enter="submitComment(post.id)" type="text" placeholder="Reply..." class="flex-1 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded py-1 px-2 text-[10px] h-7 focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'assignments'" class="space-y-4 animate-fade-in">
                
                <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg gap-1">
                    <button @click="assignmentFilter = 'upcoming'" 
                        class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all"
                        :class="assignmentFilter === 'upcoming' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Upcoming
                    </button>
                    <button @click="assignmentFilter = 'past_due'" 
                        class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all"
                        :class="assignmentFilter === 'past_due' ? 'bg-white dark:bg-slate-700 text-red-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Past Due
                    </button>
                    <button @click="assignmentFilter = 'completed'" 
                        class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all"
                        :class="assignmentFilter === 'completed' ? 'bg-white dark:bg-slate-700 text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Completed
                    </button>
                </div>

                <div v-if="filteredAssignments.length === 0" class="text-center py-8 text-slate-400 text-[10px] italic flex flex-col items-center gap-2">
                    <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    No {{ assignmentFilter.replace('_', ' ') }} assignments.
                </div>
                
                <div v-for="assignment in filteredAssignments" 
                     :key="assignment.id" 
                     :id="`item-${assignment.id}`" 
                     class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-3 transition-all duration-500 relative"
                     :class="highlightedId === assignment.id 
                        ? 'border-yellow-500 border-4 ring-4 ring-yellow-400/50 shadow-[0_0_30px_rgba(234,179,8,0.6)] scale-[1.03] z-10' 
                        : 'border border-slate-200 dark:border-slate-700 hover:border-blue-300'">
                    
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1">
                                <svg v-if="highlightedId === assignment.id" class="w-4 h-4 text-yellow-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                {{ assignment.title }}
                            </h3>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[9px] bg-slate-100 dark:bg-slate-700 px-1.5 rounded text-slate-500">Due: {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString() : 'No Date' }}</span>
                                <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400">{{ assignment.points }} pts</span>
                            </div>
                        </div>
                        <span :class="getStatus(assignment).class" class="text-[9px] font-black px-1.5 py-0.5 rounded border">{{ getStatus(assignment).label }}</span>
                    </div>
                    
                    <div class="text-[10px] text-slate-600 dark:text-slate-300 whitespace-pre-wrap leading-tight mb-2" 
                         :class="highlightedId === assignment.id ? '' : 'line-clamp-2'"
                         v-html="linkify(assignment.description)">
                    </div>
                    
                    <div v-if="assignment.attachment_paths" class="flex flex-wrap gap-2 mb-2">
                         <template v-if="getPaths(assignment.attachment_paths).length">
                             <a v-for="(path, i) in getPaths(assignment.attachment_paths)" :key="i" :href="`/storage/${path}`" target="_blank" class="flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded border border-blue-100 dark:border-blue-800 text-[9px] font-bold hover:bg-blue-100 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                File {{ i + 1 }}
                             </a>
                         </template>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-700 pt-2 flex justify-between items-center">
                        <div v-if="assignment.submissions[0]" class="text-[9px] text-slate-400 font-bold">
                            Submitted: {{ new Date(assignment.submissions[0].submitted_at).toLocaleDateString() }}
                            <div v-if="assignment.submissions[0].grade" class="text-green-600 mt-0.5">Feedback: "{{ assignment.submissions[0].feedback }}"</div>
                        </div>
                        <div v-else class="text-[9px] text-slate-400 italic">Not submitted.</div>
                        <button v-if="!assignment.submissions[0] || !assignment.submissions[0].grade" @click="openSubmitModal(assignment)" class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold px-3 py-1.5 rounded shadow-sm transition">
                            {{ assignment.submissions[0] ? 'View / Undo' : 'Submit Work' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'materials'" class="space-y-2 animate-fade-in">
                <div v-if="course.lessons.length > 0" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm divide-y divide-slate-100 dark:divide-slate-700">
                    <div v-for="lesson in course.lessons" 
                         :key="lesson.id" 
                         :id="`item-${lesson.id}`"
                         class="flex items-center justify-between p-3 transition-all duration-500 relative"
                         :class="highlightedId === lesson.id 
                            ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 shadow-[inset_0_0_20px_rgba(234,179,8,0.2)] z-10' 
                            : 'hover:bg-slate-50 dark:hover:bg-slate-700/50 border-l-4 border-transparent'">
                        
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="shrink-0 w-8 h-8 rounded bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate flex items-center gap-1">
                                <svg v-if="highlightedId === lesson.id" class="w-4 h-4 text-yellow-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                {{ lesson.title }}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <a :href="`/storage/${lesson.attachment_path}`" target="_blank" class="text-[9px] font-bold bg-white border border-slate-200 text-slate-600 px-2 py-1 rounded hover:text-blue-600">View</a>
                            <a :href="`/storage/${lesson.attachment_path}`" download class="text-[9px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded hover:text-emerald-600">Save</a>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400 text-[10px] italic">No materials.</div>
            </div>
        </div>

        <Modal :show="showSubmitModal" @close="showSubmitModal = false">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-2">Submission</h3>
                
                <div v-if="selectedAssignment?.submissions[0]">
                    <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/10 rounded border border-blue-100 dark:border-blue-900/30 text-center">
                        <span class="text-blue-600 dark:text-blue-400 text-xs font-bold block mb-2">Submitted on {{ new Date(selectedAssignment.submissions[0].submitted_at).toLocaleDateString() }}</span>
                        
                        <div v-if="selectedAssignment.submissions[0].file_paths" class="text-left bg-white dark:bg-slate-900 p-2 rounded border border-slate-100 dark:border-slate-700">
                            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Attached Files:</p>
                            <div class="space-y-1">
                                <div v-for="(path, index) in getPaths(selectedAssignment.submissions[0].file_paths)" :key="index" class="flex justify-between items-center text-[10px] text-slate-600 dark:text-slate-300">
                                    <span class="truncate w-32">File {{ index + 1 }}</span>
                                    <div class="flex gap-2">
                                        <a :href="`/storage/${path}`" target="_blank" class="hover:text-blue-500 underline">View</a>
                                        <a :href="`/storage/${path}`" download class="hover:text-emerald-500 underline">Download</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-3">
                         <button @click="showSubmitModal = false" class="px-3 py-1.5 text-[10px] font-bold text-slate-500">Close</button>
                         <button @click="undoTurnIn" class="bg-red-50 text-red-600 border border-red-200 text-[10px] font-bold px-3 py-1.5 rounded hover:bg-red-100">Undo Turn In</button>
                    </div>
                </div>

                <form v-else @submit.prevent="submitWork" class="space-y-3">
                    <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded border-2 border-dashed border-slate-300 dark:border-slate-700 text-center relative hover:border-blue-400 transition">
                        <input type="file" multiple @change="e => formSubmission.files = Array.from(e.target.files)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required />
                        <p class="text-[10px] text-slate-500 font-bold">Click to select files</p>
                    </div>
                    <div v-if="formSubmission.files.length" class="space-y-1">
                        <div v-for="(f,i) in formSubmission.files" :key="i" class="text-[10px] text-slate-600 dark:text-slate-400 flex justify-between bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                            <span class="truncate w-3/4">{{ f.name }}</span>
                            <span>{{ (f.size/1024).toFixed(0) }}KB</span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showSubmitModal = false" class="px-3 py-1.5 text-[10px] font-bold text-slate-500">Cancel</button>
                        <button :disabled="formSubmission.processing" class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold px-4 py-1.5 rounded shadow-sm">Turn In</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>