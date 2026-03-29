<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { 
    ChevronLeft, Calendar, Clock, Trophy, 
    FileText, Edit3, Trash2, Paperclip, ExternalLink 
} from 'lucide-vue-next';

const props = defineProps({
    assignment: Object,
    course: Object,
    toBeGraded: Array,
    graded: Array,
    backUrl: String
});

const activeTab = ref('details'); 
const isEditing = ref(false); 
const showGradeModal = ref(false);
const selectedSubmission = ref(null);
const currentFileIndex = ref(0);

const goBack = () => {
    if (props.backUrl) {
        router.visit(`${props.backUrl}?tab=assignments`);
    } else {
        window.history.back();
    }
};

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toISOString().slice(0, 16);
};

const editForm = useForm({
    title: props.assignment.title,
    description: props.assignment.description,
    points: props.assignment.points,
    due_date: formatDateForInput(props.assignment.due_date),
    files: [], 
});

const gradeForm = useForm({ grade: '', feedback: '' });

const linkify = (text) => text ? text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline font-bold">$1</a>') : 'No instructions provided.';

const getPaths = (submission) => {
    if (!submission || !submission.file_paths) return [];
    if (Array.isArray(submission.file_paths)) return submission.file_paths;
    try { return JSON.parse(submission.file_paths) || []; } catch (e) { return []; }
};

const getFileUrl = (path) => `/storage/${path}`;

const currentFilePath = computed(() => {
    const paths = getPaths(selectedSubmission.value);
    return paths.length > 0 ? paths[currentFileIndex.value] : null;
});

const updateAssignment = () => {
    editForm.post(route('teacher.assignments.update', props.assignment.id), {
        _method: 'PATCH',
        onSuccess: () => isEditing.value = false,
    });
};

const deleteAssignment = () => {
    if(confirm('Are you sure? This will delete all student submissions for this task.')) {
        router.delete(route('teacher.assignments.destroy', props.assignment.id));
    }
};

const openGradeModal = (submission) => {
    selectedSubmission.value = submission;
    currentFileIndex.value = 0;
    gradeForm.grade = submission.grade || '';
    gradeForm.feedback = submission.feedback || '';
    showGradeModal.value = true;
};

const submitGrade = () => {
    gradeForm.post(route('teacher.submissions.grade', selectedSubmission.value.id), {
        onSuccess: () => { showGradeModal.value = false; gradeForm.reset(); }
    });
};
</script>

<template>
    <Head :title="`Manage: ${assignment.title}`" />

    <AuthenticatedLayout>
        <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-end border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="min-w-0">
                <nav class="flex items-center gap-1.5 mb-1.5">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest truncate max-w-[150px] sm:max-w-none">{{ course.title }}</span>
                    <ChevronLeft class="w-3 h-3 text-slate-300 shrink-0" />
                    <span class="text-[9px] font-black uppercase text-blue-500 tracking-widest shrink-0">Task</span>
                </nav>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">{{ assignment.title }}</h1>
            </div>
            
            <button @click="goBack" class="mt-3 md:mt-0 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm w-full md:w-auto">
                <ChevronLeft class="w-3 h-3" /> Back
            </button>
        </div>

        <div class="flex gap-4 sm:gap-6 border-b border-slate-200 dark:border-slate-700 mb-4 sm:mb-6 overflow-x-auto scrollbar-hide">
            <button @click="activeTab = 'details'" class="pb-2.5 text-[11px] sm:text-xs font-black uppercase tracking-widest transition-all relative whitespace-nowrap" :class="activeTab === 'details' ? 'text-blue-600' : 'text-slate-400 hover:text-slate-600'">
                Details
                <div v-if="activeTab === 'details'" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></div>
            </button>
            <button @click="activeTab = 'grading'" class="pb-2.5 text-[11px] sm:text-xs font-black uppercase tracking-widest transition-all relative flex items-center gap-1.5 whitespace-nowrap" :class="activeTab === 'grading' ? 'text-blue-600' : 'text-slate-400 hover:text-slate-600'">
                To Grade 
                <span v-if="toBeGraded.length" class="bg-blue-600 text-white text-[9px] px-1.5 py-0.5 rounded-md font-black">{{ toBeGraded.length }}</span>
                <div v-if="activeTab === 'grading'" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></div>
            </button>
            <button @click="activeTab = 'graded'" class="pb-2.5 text-[11px] sm:text-xs font-black uppercase tracking-widest transition-all relative whitespace-nowrap" :class="activeTab === 'graded' ? 'text-blue-600' : 'text-slate-400 hover:text-slate-600'">
                Graded
                <div v-if="activeTab === 'graded'" class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></div>
            </button>
        </div>

        <div class="min-h-[400px]">
            <div v-if="activeTab === 'details'" class="max-w-5xl animate-in fade-in slide-in-from-bottom-2 duration-300">
                <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <div class="md:col-span-2 space-y-4">
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5 shadow-sm">
                            <div class="flex items-center gap-2 mb-3 text-slate-400">
                                <FileText class="w-3.5 h-3.5" />
                                <h2 class="text-[10px] font-black uppercase tracking-widest">Instructions</h2>
                            </div>
                            <div class="prose dark:prose-invert max-w-none text-xs sm:text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed" v-html="linkify(assignment.description)"></div>
                        </div>

                        <div v-if="assignment.attachment_paths" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5 shadow-sm">
                            <div class="flex items-center gap-2 mb-3 text-slate-400">
                                <Paperclip class="w-3.5 h-3.5" />
                                <h2 class="text-[10px] font-black uppercase tracking-widest">Attached Resources</h2>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template v-if="assignment.attachment_paths">
                                    <div v-for="(path, index) in JSON.parse(assignment.attachment_paths)" :key="index">
                                        <a :href="getFileUrl(path)" target="_blank" class="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition group">
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 group-hover:text-blue-600 truncate mr-2">Resource File {{ index + 1 }}</span>
                                            <ExternalLink class="w-3 h-3 text-slate-300 group-hover:text-blue-500 shrink-0" />
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 shrink-0">
                                    <Trophy class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Max Score</p>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">{{ assignment.points }} Points</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 border-t border-slate-100 dark:border-slate-700 pt-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 shrink-0">
                                    <Clock class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Due Date</p>
                                    <p class="text-[11px] font-bold text-slate-700 dark:text-slate-200 leading-tight">
                                        {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No Deadline' }}
                                    </p>
                                    <p class="text-[9px] text-slate-500 font-medium">
                                        {{ assignment.due_date ? 'at ' + new Date(assignment.due_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row md:flex-col gap-2">
                            <button @click="isEditing = true" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 text-white rounded-lg font-black text-[10px] uppercase tracking-widest hover:bg-blue-500 transition shadow-sm">
                                <Edit3 class="w-3.5 h-3.5" /> Edit
                            </button>
                            <button @click="deleteAssignment" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/30 text-red-500 rounded-lg font-black text-[10px] uppercase tracking-widest hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <Trash2 class="w-3.5 h-3.5" /> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <form v-else @submit.prevent="updateAssignment" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4 sm:p-6 max-w-2xl mx-auto space-y-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Edit3 class="w-4 h-4 text-blue-500" />
                        <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Edit Assignment</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[9px] font-black uppercase text-slate-500 mb-1 tracking-widest">Title</label>
                            <input v-model="editForm.title" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase text-slate-500 mb-1 tracking-widest">Points</label>
                            <input v-model="editForm.points" type="number" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase text-slate-500 mb-1 tracking-widest">Due Date</label>
                            <input v-model="editForm.due_date" type="datetime-local" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-[10px] font-bold focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:[color-scheme:dark]" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase text-slate-500 mb-1 tracking-widest">Instructions</label>
                        <textarea v-model="editForm.description" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-xs h-24 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-2">
                        <button type="button" @click="isEditing = false" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-700 transition">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm transition">Save</button>
                    </div>
                </form>
            </div>
            
            <div v-else class="animate-in fade-in duration-300">
                <div v-if="(activeTab === 'grading' ? toBeGraded : graded).length > 0" class="flex flex-col gap-2">
                    <div v-for="sub in (activeTab === 'grading' ? toBeGraded : graded)" :key="sub.id" 
                         @click="openGradeModal(sub)"
                         class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm gap-3 cursor-pointer hover:border-blue-300 dark:hover:border-blue-600 transition">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 shrink-0 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-300 font-black text-xs">{{ sub.student.name.charAt(0) }}</div> 
                            <div class="truncate">
                                <div class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate">{{ sub.student.name }}</div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5 truncate">{{ new Date(sub.created_at).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) }}</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto border-t sm:border-none border-slate-100 dark:border-slate-700 pt-2 sm:pt-0 mt-1 sm:mt-0 shrink-0">
                            <div class="text-center">
                                <span v-if="sub.grade !== null" class="inline-flex items-center px-2 py-1 rounded border shadow-sm text-[10px] font-black bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">
                                    {{ sub.grade }} / {{ assignment.points }}
                                </span>
                                <span v-else class="text-slate-400 font-black text-[9px] uppercase tracking-widest bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded">Pending</span>
                            </div>
                            <button class="text-white font-black text-[9px] uppercase tracking-widest bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded shadow-sm transition">
                                {{ activeTab === 'grading' ? 'Grade' : 'Review' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 shadow-sm">
                    <p class="text-slate-400 font-black text-[10px] uppercase tracking-widest">No submissions found in this tab.</p>
                </div>
            </div>
        </div>
        
        <Modal :show="showGradeModal" @close="showGradeModal = false">
            <div class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl overflow-hidden shadow-2xl flex flex-col h-[90vh] md:h-[80vh]">
                
                <div class="p-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 shrink-0 rounded-full bg-blue-600 text-white flex items-center justify-center font-black text-[10px]">{{ selectedSubmission?.student.name.charAt(0) }}</div>
                        <div class="truncate">
                            <h3 class="font-black text-xs truncate">{{ selectedSubmission?.student.name }}</h3>
                            <p class="text-[8px] font-bold uppercase tracking-widest text-slate-500 truncate">Sub: {{ new Date(selectedSubmission?.created_at).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) }}</p>
                        </div>
                    </div>
                    <button @click="showGradeModal = false" class="w-7 h-7 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300 hover:bg-slate-300 transition shrink-0">&times;</button>
                </div>
                
                <div class="flex flex-col md:flex-row flex-1 overflow-y-auto md:overflow-hidden">
                    
                    <div class="flex-1 bg-slate-100 dark:bg-slate-950/50 flex flex-col p-3 min-h-[300px] md:min-h-0 overflow-y-auto relative">
                        <div v-if="selectedSubmission?.text_content" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-4 mb-3 shrink-0 shadow-sm">
                            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-1.5 tracking-widest">Student Response</h4>
                            <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ selectedSubmission.text_content }}</p>
                        </div>

                        <div v-if="currentFilePath" class="flex-1 flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 overflow-hidden relative shadow-sm min-h-[250px]">
                            <a :href="getFileUrl(currentFilePath)" download class="absolute top-2 right-2 bg-white/90 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded shadow border border-slate-200 dark:border-slate-700 hover:text-blue-600 transition z-10">DL File</a>
                            <iframe v-if="currentFilePath.toLowerCase().endsWith('.pdf')" :src="getFileUrl(currentFilePath)" class="w-full h-full border-none"></iframe>
                            <img v-else-if="currentFilePath.match(/\.(jpeg|jpg|png|gif)$/i)" :src="getFileUrl(currentFilePath)" class="max-w-full max-h-full object-contain p-4" />
                            <div v-else class="text-center p-8">
                                <FileText class="w-10 h-10 text-slate-300 mb-2 mx-auto" />
                                <p class="text-slate-400 font-bold mb-1 text-[10px] uppercase">Preview unavailable</p>
                                <a :href="getFileUrl(currentFilePath)" download class="text-blue-600 hover:underline font-black text-[10px] uppercase tracking-widest">Download</a>
                            </div>
                        </div>
                        <div v-else-if="!selectedSubmission?.text_content" class="flex flex-col items-center justify-center h-full text-slate-400 font-bold text-[10px] uppercase tracking-widest">No attachments.</div>
                    </div>
                    
                    <div class="w-full md:w-72 lg:w-80 bg-white dark:bg-slate-800 border-t md:border-t-0 md:border-l border-slate-200 dark:border-slate-700 flex flex-col shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] md:shadow-none z-10">
                        
                        <div class="p-4 border-b border-slate-100 dark:border-slate-700 shrink-0">
                            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-2 tracking-widest">Files List</h4>
                            <div v-if="getPaths(selectedSubmission).length" class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
                                <button v-for="(path, index) in getPaths(selectedSubmission)" :key="index" @click="currentFileIndex = index" class="shrink-0 px-3 py-1.5 rounded border text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 transition" :class="currentFileIndex === index ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400'">
                                    File {{ index + 1 }}
                                </button>
                            </div>
                            <div v-else class="text-[9px] font-black text-slate-400 uppercase tracking-widest">No files</div>
                        </div>

                        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 flex-1 md:flex-none flex flex-col">
                            <form @submit.prevent="submitGrade" class="flex flex-col h-full gap-4">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-widest">Assign Score</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="gradeForm.grade" type="number" class="w-20 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-center font-black text-sm focus:ring-2 focus:ring-blue-500 transition" required :max="assignment.points" min="0" />
                                        <span class="text-slate-400 font-black text-[10px] uppercase tracking-widest">/ {{ assignment.points }} Pts</span>
                                    </div>
                                </div>
                                <div class="flex-1 flex flex-col">
                                    <label class="block text-[9px] font-black text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-widest">Feedback</label>
                                    <textarea v-model="gradeForm.feedback" class="w-full flex-1 min-h-[80px] md:min-h-[120px] bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg p-2 text-[11px] resize-none focus:ring-2 focus:ring-blue-500 transition" placeholder="Add comments..."></textarea>
                                </div>
                                <button :disabled="gradeForm.processing" class="w-full justify-center bg-blue-600 hover:bg-blue-500 text-white font-black uppercase tracking-widest py-2.5 rounded-lg text-[10px] shadow-md transition shrink-0">Submit Grade</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Mobile Tab Scroll Hider */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>