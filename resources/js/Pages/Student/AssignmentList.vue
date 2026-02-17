<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ courses: Array });
const selectedCourseId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const activeTab = ref('upcoming'); 
const imageErrors = ref({});
const showDetailsModal = ref(false);
const selectedAssignment = ref(null);
const formSubmission = useForm({ files: [] });

const selectCourse = (id) => { selectedCourseId.value = id; };
const handleImageError = (id) => { imageErrors.value[id] = true; };
const selectedCourse = computed(() => props.courses.find(c => c.id === selectedCourseId.value));
const isCompleted = (a) => a.submissions && a.submissions.length > 0;

const countAssignments = (c, type) => {
    if (!c.assignments) return 0;
    const now = new Date();
    return c.assignments.filter(a => {
        const done = isCompleted(a); const past = a.due_date && new Date(a.due_date) < now;
        return type === 'completed' ? done : type === 'upcoming' ? !done && !past : false;
    }).length;
};

const filteredAssignments = computed(() => {
    if (!selectedCourse.value) return [];
    return selectedCourse.value.assignments.filter(a => {
        const done = isCompleted(a); const past = a.due_date && new Date(a.due_date) < new Date();
        if (activeTab.value === 'completed') return done;
        if (activeTab.value === 'past') return !done && past;
        return !done && !past;
    });
});

const linkify = (t) => t ? t.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>') : '';
const getPaths = (paths) => { if (Array.isArray(paths)) return paths; try { return JSON.parse(paths) || []; } catch (e) { return []; } };

const openDetails = (a) => { selectedAssignment.value = a; formSubmission.reset(); showDetailsModal.value = true; };
const submitWork = () => formSubmission.post(route('assignments.submit', selectedAssignment.value.id), { onSuccess: () => { showDetailsModal.value = false; formSubmission.reset(); }, preserveScroll: true });
const undoTurnIn = () => { if (confirm('Undo submission?')) router.post(route('assignments.unsubmit', selectedAssignment.value.id), {}, { onSuccess: () => showDetailsModal.value = false, preserveScroll: true }); };
</script>

<template>
    <Head title="My Assignments" />
    <AuthenticatedLayout>
        <div class="h-[calc(100vh-80px)] flex flex-col">
            <div class="mb-4">
                <h1 class="text-lg font-black text-slate-900 dark:text-white">Assignments</h1>
                <p class="text-slate-500 text-[10px]">Manage deadlines.</p>
            </div>

            <div v-if="courses.length > 0" class="flex-1 flex gap-4 overflow-hidden">
                <aside class="w-64 bg-white dark:bg-slate-800 rounded-lg flex flex-col overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700">
                    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Courses</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-1">
                        <button v-for="c in courses" :key="c.id" @click="selectCourse(c.id)" 
                            class="w-full text-left p-2 rounded-lg transition flex items-center gap-2 group border-2" 
                            :class="selectedCourseId === c.id ? 'bg-blue-50 border-blue-500 shadow-sm' : 'bg-transparent border-transparent hover:bg-slate-50 hover:border-slate-200 dark:hover:bg-slate-700 dark:hover:border-slate-600'">
                            
                            <div class="w-8 h-8 rounded bg-slate-200 dark:bg-slate-900 shrink-0 overflow-hidden relative border border-slate-300 dark:border-slate-700">
                                <img v-if="c.thumbnail && !imageErrors[c.id]" :src="c.thumbnail" @error="handleImageError(c.id)" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-[8px] font-bold text-slate-400">IMG</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="block truncate font-bold text-xs text-slate-800 dark:text-slate-200" :class="{'text-blue-700': selectedCourseId === c.id}">{{ c.title }}</span>
                                <span class="text-[9px] text-slate-500">{{ countAssignments(c, 'upcoming') }} Active</span>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="flex-1 bg-white dark:bg-slate-800 rounded-lg flex flex-col overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div v-if="selectedCourse" class="flex flex-col h-full">
                        <div class="p-3 border-b border-slate-200 dark:border-slate-700 flex gap-1 bg-slate-50 dark:bg-slate-900/30">
                            <button v-for="tab in ['upcoming', 'past', 'completed']" :key="tab" @click="activeTab = tab" class="px-3 py-1 rounded text-[10px] font-bold capitalize transition" :class="activeTab === tab ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 hover:text-slate-700'">{{ tab }}</button>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50 dark:bg-transparent">
                            <div v-if="filteredAssignments.length > 0" class="space-y-2">
                                <div v-for="a in filteredAssignments" :key="a.id" @click="openDetails(a)" 
                                     class="group bg-white dark:bg-white rounded-lg p-3 border-2 border-slate-200 dark:border-slate-700 border-l-4 shadow-sm hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 cursor-pointer flex items-center gap-3 transition-all duration-200" 
                                     :class="{'border-l-blue-500': activeTab === 'upcoming', 'border-l-red-500': activeTab === 'past', 'border-l-green-500': activeTab === 'completed'}">
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ a.title }}</h4>
                                        <div class="text-[10px] text-slate-500 mt-0.5">Due: {{ a.due_date ? new Date(a.due_date).toLocaleDateString() : 'None' }} • {{ a.points }}pts</div>
                                    </div>
                                    <div v-if="activeTab === 'completed' && a.submissions[0].grade" class="text-[10px] font-black text-green-600 bg-green-50 px-1.5 rounded">{{ a.submissions[0].grade }} / {{ a.points }}</div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center h-32 text-slate-400 text-[10px] italic">No assignments.</div>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center h-full text-slate-400 text-xs font-bold">Select a course.</div>
                </main>
            </div>
            <div v-else class="flex-1 flex flex-col items-center justify-center bg-white dark:bg-slate-800 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700">
                <p class="text-slate-400 text-xs">No assignments found.</p>
                <Link :href="route('student.courses')" class="text-blue-600 text-[10px] font-bold hover:underline mt-1">Join a Class</Link>
            </div>
        </div>

        <Modal :show="showDetailsModal" @close="showDetailsModal = false">
            <div class="bg-white dark:bg-slate-800 rounded-lg overflow-hidden flex flex-col max-h-[80vh]">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-start">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedAssignment?.title }}</h2>
                        <p class="text-[10px] text-slate-500 mt-0.5">Due: {{ selectedAssignment?.due_date ? new Date(selectedAssignment.due_date).toLocaleString() : 'No Deadline' }} • {{ selectedAssignment?.points }} Pts</p>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1 space-y-4">
                    <div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase mb-1">Instructions</h3>
                        <div class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed" v-html="linkify(selectedAssignment?.description || 'None')"></div>
                    </div>
                    
                    <div v-if="selectedAssignment?.attachment_paths">
                         <h3 class="text-[10px] font-black text-slate-400 uppercase mb-1">Resources</h3>
                         <div class="flex flex-wrap gap-2">
                             <template v-if="Array.isArray(JSON.parse(selectedAssignment.attachment_paths))">
                                 <a v-for="(path, i) in JSON.parse(selectedAssignment.attachment_paths)" :key="i" :href="`/storage/${path}`" target="_blank" class="px-2 py-1 bg-blue-50 text-blue-600 rounded border border-blue-100 text-[9px] font-bold hover:bg-blue-100">File {{ i + 1 }}</a>
                             </template>
                             <a v-else :href="`/storage/${selectedAssignment.attachment_paths}`" target="_blank" class="px-2 py-1 bg-blue-50 text-blue-600 rounded border border-blue-100 text-[9px] font-bold">View File</a>
                         </div>
                    </div>
                    <hr class="border-slate-100 dark:border-slate-700" />
                    
                    <div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Submission</h3>
                        
                        <div v-if="selectedAssignment?.submissions[0]">
                            <div class="mb-2 p-3 bg-blue-50 dark:bg-blue-900/10 rounded border border-blue-100 dark:border-blue-900/30 text-center">
                                <span class="text-[10px] font-bold text-blue-600 block mb-2">Submitted on {{ new Date(selectedAssignment.submissions[0].submitted_at).toLocaleDateString() }}</span>
                                
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

                            <div v-if="selectedAssignment.submissions[0].grade" class="p-2 bg-green-50 rounded text-[10px] text-green-700 mb-2"><strong>Grade:</strong> {{ selectedAssignment.submissions[0].grade }}/{{ selectedAssignment.points }} <br/> <strong>Feedback:</strong> "{{ selectedAssignment.submissions[0].feedback }}"</div>
                            <div v-else class="flex justify-end"><button @click="undoTurnIn" class="text-red-600 text-[10px] font-bold border border-red-200 px-3 py-1.5 rounded hover:bg-red-50">Undo Turn In</button></div>
                        </div>
                        
                        <form v-else @submit.prevent="submitWork" class="space-y-3">
                            <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded border-2 border-dashed border-slate-300 dark:border-slate-700 text-center relative hover:border-blue-400 transition"><input type="file" multiple @change="e => formSubmission.files = Array.from(e.target.files)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" /><p class="text-[10px] text-slate-500 font-bold">Select files</p></div>
                            <div v-if="formSubmission.files.length" class="space-y-1"><div v-for="(f,i) in formSubmission.files" :key="i" class="text-[9px] bg-slate-100 px-2 py-1 rounded flex justify-between"><span>{{ f.name }}</span><span>{{ (f.size/1024).toFixed(0) }}KB</span></div></div>
                            <div class="flex justify-end"><button :disabled="formSubmission.processing" class="bg-blue-600 text-white text-[10px] font-bold px-4 py-1.5 rounded shadow-sm hover:bg-blue-500">Turn In</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>