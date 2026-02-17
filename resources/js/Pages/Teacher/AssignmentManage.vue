<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

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

// Helper for Go Back
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(props.backUrl);
    }
};

// ... existing code for formatDateForInput, editForm, gradeForm ...
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
// ... existing helper functions linkify, getPaths, getFileUrl ...
const linkify = (text) => text ? text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline font-bold">$1</a>') : 'No instructions.';
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
        <div class="mb-4 flex justify-between items-center">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ course.title }}</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">Assignment</span>
                </div>
                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ assignment.title }}</h1>
            </div>
            
            <button @click="goBack" class="text-slate-500 text-[10px] font-bold hover:text-blue-500 transition flex items-center gap-1 px-3 py-1.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer">
                <span>&larr;</span> Back
            </button>
        </div>

        <div class="flex gap-4 border-b border-slate-200 dark:border-slate-700 mb-4">
            <button @click="activeTab = 'details'" class="pb-2 text-xs font-bold transition border-b-2" :class="activeTab === 'details' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">Details</button>
            <button @click="activeTab = 'grading'" class="pb-2 text-xs font-bold transition border-b-2 flex items-center gap-2" :class="activeTab === 'grading' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">To Grade <span v-if="toBeGraded.length" class="bg-blue-600 text-white text-[9px] px-1.5 rounded-full">{{ toBeGraded.length }}</span></button>
            <button @click="activeTab = 'graded'" class="pb-2 text-xs font-bold transition border-b-2" :class="activeTab === 'graded' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">Graded</button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm p-4 min-h-[400px]">
            <div v-if="activeTab === 'details'">
                <div v-if="!isEditing" class="space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-0.5">Instructions</h2>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">Due: {{ assignment.due_date ? new Date(assignment.due_date).toLocaleString() : 'No Deadline' }} • {{ assignment.points }} Points</p>
                        </div>
                        <button @click="isEditing = true" class="text-[10px] bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 px-3 py-1.5 rounded font-bold transition flex items-center gap-1">Edit</button>
                    </div>
                    <div class="prose dark:prose-invert max-w-none text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed" v-html="linkify(assignment.description)"></div>
                    <div v-if="assignment.attachment_paths" class="border-t border-slate-100 dark:border-slate-700 pt-4">
                        <h3 class="text-[10px] font-bold uppercase text-slate-400 mb-2">Resources</h3>
                        <div class="flex flex-wrap gap-2">
                             <template v-if="Array.isArray(JSON.parse(assignment.attachment_paths))">
                                 <div v-for="(path, index) in JSON.parse(assignment.attachment_paths)" :key="index">
                                     <a :href="`/storage/${path}`" target="_blank" class="inline-flex items-center gap-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2 py-1 rounded hover:border-blue-500 transition group text-[10px]"><span class="font-bold text-slate-700 dark:text-white group-hover:text-blue-600">File {{ index + 1 }}</span></a>
                                 </div>
                             </template>
                        </div>
                    </div>
                </div>
                <form v-else @submit.prevent="updateAssignment" class="space-y-3 max-w-xl mx-auto py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Title</label><input v-model="editForm.title" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-1.5 text-xs focus:ring-blue-500" /></div>
                        <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Points</label><input v-model="editForm.points" type="number" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-1.5 text-xs focus:ring-blue-500" /></div>
                    </div>
                    <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Due Date</label><input v-model="editForm.due_date" type="datetime-local" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-1.5 text-xs focus:ring-blue-500 dark:[color-scheme:dark]" /></div>
                    <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Instructions</label><textarea v-model="editForm.description" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-1.5 text-xs h-24 focus:ring-blue-500 resize-none"></textarea></div>
                    <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Replace Files</label><input type="file" multiple @input="editForm.files = Array.from($event.target.files)" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded" /></div>
                    <div class="flex justify-between items-center pt-3 border-t border-slate-100 dark:border-slate-700">
                        <DangerButton type="button" @click="deleteAssignment" class="opacity-80 hover:opacity-100 text-[10px] px-3 py-1.5 h-auto">Delete</DangerButton>
                        <div class="flex gap-2"><button type="button" @click="isEditing = false" class="px-3 py-1.5 text-[10px] font-bold text-slate-500 hover:text-slate-700 transition">Cancel</button><PrimaryButton :disabled="editForm.processing" class="text-[10px] px-4 py-1.5 h-auto bg-blue-600 hover:bg-blue-500 border-transparent">Save</PrimaryButton></div>
                    </div>
                </form>
            </div>
            <div v-else>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="text-[10px] uppercase font-black text-slate-400 border-b border-slate-200 dark:border-slate-700"><tr><th class="px-3 py-2">Student</th><th class="px-3 py-2">Submitted</th><th class="px-3 py-2">Files</th><th class="px-3 py-2">Score</th><th class="px-3 py-2 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="sub in (activeTab === 'grading' ? toBeGraded : graded)" :key="sub.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer" @click="openGradeModal(sub)">
                                <td class="px-3 py-2 font-bold text-slate-900 dark:text-white flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-black text-[10px]">{{ sub.student.name.charAt(0) }}</div> {{ sub.student.name }}</td>
                                <td class="px-3 py-2 font-mono text-[10px]">{{ new Date(sub.created_at).toLocaleString() }}</td>
                                <td class="px-3 py-2"><span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-900 px-1.5 py-0.5 rounded">{{ getPaths(sub).length }} File(s)</span></td>
                                <td class="px-3 py-2"><span v-if="sub.grade" class="text-green-600 font-black bg-green-50 dark:bg-green-900/20 px-1.5 py-0.5 rounded text-[10px] border border-green-200 dark:border-green-900">{{ sub.grade }} / {{ assignment.points }}</span><span v-else class="text-slate-400 font-bold italic text-[10px]">-- / {{ assignment.points }}</span></td>
                                <td class="px-3 py-2 text-right"><button class="text-blue-600 hover:text-white font-bold text-[10px] bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 hover:bg-blue-600 transition">{{ activeTab === 'grading' ? 'Grade' : 'Update' }}</button></td>
                            </tr>
                            <tr v-if="(activeTab === 'grading' ? toBeGraded : graded).length === 0"><td colspan="5" class="px-3 py-8 text-center text-slate-400 font-medium italic text-xs">No submissions.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <Modal :show="showGradeModal" @close="showGradeModal = false">
            <div class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col h-[90vh]">
                <div class="p-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center shrink-0">
                    <div><h3 class="font-black text-sm">{{ selectedSubmission?.student.name }}</h3><p class="text-[10px] text-slate-500">Submitted: {{ new Date(selectedSubmission?.created_at).toLocaleString() }}</p></div>
                    <button @click="showGradeModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg leading-none">&times;</button>
                </div>
                <div class="flex flex-1 overflow-hidden">
                    <div class="flex-1 bg-slate-100 dark:bg-slate-950/50 flex flex-col p-3 overflow-hidden relative">
                        <div v-if="currentFilePath" class="flex-1 flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 overflow-hidden relative shadow-sm">
                            <a :href="getFileUrl(currentFilePath)" download class="absolute top-2 right-2 bg-white/90 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 text-[10px] font-bold px-2 py-1 rounded shadow border border-slate-200 dark:border-slate-700 hover:text-blue-600 transition z-10">Download</a>
                            <iframe v-if="currentFilePath.toLowerCase().endsWith('.pdf')" :src="getFileUrl(currentFilePath)" class="w-full h-full"></iframe>
                            <img v-else-if="currentFilePath.match(/\.(jpeg|jpg|png|gif)$/i)" :src="getFileUrl(currentFilePath)" class="max-w-full max-h-full object-contain p-4" />
                            <div v-else class="text-center"><p class="text-slate-400 font-bold mb-1 text-xs">No preview</p><a :href="getFileUrl(currentFilePath)" download class="text-blue-600 hover:underline text-[10px]">Download</a></div>
                        </div>
                        <div v-else class="flex items-center justify-center h-full text-slate-400 font-bold text-xs">No file selected.</div>
                    </div>
                    <div class="w-64 bg-white dark:bg-slate-800 border-l border-slate-200 dark:border-slate-700 flex flex-col shrink-0">
                        <div class="p-3 border-b border-slate-100 dark:border-slate-700 flex-1 overflow-y-auto">
                            <h4 class="text-[10px] font-black uppercase text-slate-400 mb-2 tracking-wider">Files</h4>
                            <div class="space-y-1">
                                <button v-for="(path, index) in getPaths(selectedSubmission)" :key="index" @click="currentFileIndex = index" class="w-full text-left p-1.5 rounded border text-[10px] font-bold flex items-center gap-2 transition" :class="currentFileIndex === index ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-blue-300'"><span class="w-4 h-4 flex items-center justify-center bg-slate-200 dark:bg-slate-700 rounded text-[9px] font-black text-slate-500">{{ index + 1 }}</span><span class="truncate flex-1">File {{ index + 1 }}</span></button>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-900 shrink-0">
                            <form @submit.prevent="submitGrade" class="space-y-3">
                                <div><label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 mb-1 uppercase">Score</label><div class="flex items-center gap-2"><input v-model="gradeForm.grade" type="number" class="w-16 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded p-1.5 text-center font-black text-sm focus:ring-blue-500" required :max="assignment.points" min="0" /><span class="text-slate-400 font-bold text-xs">/ {{ assignment.points }}</span></div></div>
                                <div><label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 mb-1 uppercase">Feedback</label><textarea v-model="gradeForm.feedback" class="w-full bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded p-2 text-xs h-20 resize-none focus:ring-blue-500" placeholder="Comments..."></textarea></div>
                                <div class="flex justify-end pt-1"><PrimaryButton :disabled="gradeForm.processing" class="w-full justify-center bg-blue-600 hover:bg-blue-500 font-black border-transparent text-[10px] py-1.5">Save</PrimaryButton></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>