<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ courses: Array });
const selectedCourseId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const activeTab = ref('upcoming'); 
const sortOrder = ref('desc'); // NEW: Sort order state (desc = Latest, asc = Oldest)
const imageErrors = ref({});
const showDetailsModal = ref(false);
const selectedAssignment = ref(null);

const formSubmission = useForm({ 
    files: [],
    text_content: ''
});

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

// UPDATED: Now includes sorting logic based on sortOrder
const filteredAssignments = computed(() => {
    if (!selectedCourse.value) return [];
    
    let filtered = selectedCourse.value.assignments.filter(a => {
        const done = isCompleted(a); const past = a.due_date && new Date(a.due_date) < new Date();
        if (activeTab.value === 'completed') return done;
        if (activeTab.value === 'past') return !done && past;
        return !done && !past;
    });

    filtered.sort((a, b) => {
        const dateA = new Date(a.created_at || 0).getTime();
        const dateB = new Date(b.created_at || 0).getTime();
        return sortOrder.value === 'desc' ? dateB - dateA : dateA - dateB;
    });

    return filtered;
});

const isClosed = (assignment) => {
    if (!assignment || !assignment.closing_date) return false;
    return new Date().getTime() > new Date(assignment.closing_date).getTime();
};

const linkify = (t) => t ? t.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>') : '';
const getPaths = (paths) => { if (Array.isArray(paths)) return paths; try { return JSON.parse(paths) || []; } catch (e) { return []; } };

// FILE SIZE CALCULATIONS (15MB Limit)
const MAX_TOTAL_SIZE = 15 * 1024 * 1024; // 15 MB in bytes

const totalFileSize = computed(() => {
    return formSubmission.files.reduce((total, file) => total + file.size, 0);
});

const isOverSizeLimit = computed(() => totalFileSize.value > MAX_TOTAL_SIZE);

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const handleFileSelect = (e) => {
    const newFiles = Array.from(e.target.files);
    formSubmission.files = [...formSubmission.files, ...newFiles];
    e.target.value = ''; 
};

const removeFile = (index) => {
    formSubmission.files.splice(index, 1);
};

const openDetails = (a) => { 
    selectedAssignment.value = a; 
    formSubmission.reset(); 
    formSubmission.files = []; 
    showDetailsModal.value = true; 
};

const submitWork = () => {
    if (isOverSizeLimit.value) return;
    
    formSubmission.post(route('assignments.submit', selectedAssignment.value.id), { 
        onSuccess: () => { showDetailsModal.value = false; formSubmission.reset(); formSubmission.files = []; }, 
        preserveScroll: true 
    });
};

const undoTurnIn = () => { 
    if (confirm('Undo submission? You can resubmit as long as the task is not locked.')) {
        router.post(route('assignments.unsubmit', selectedAssignment.value.id), {}, { onSuccess: () => showDetailsModal.value = false, preserveScroll: true }); 
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'None';
    return new Date(dateString).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
};
</script>

<template>
    <Head title="My Tasks" />
    <AuthenticatedLayout>
        <div class="h-full md:h-[calc(100vh-80px)] flex flex-col max-w-screen-2xl mx-auto -mt-2">
            
            <div class="mb-3 shrink-0 px-1 sm:px-0">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Tasks
                </h1>
                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5 ml-8">Manage your deadlines</p>
            </div>

            <div v-if="courses.length > 0" class="flex-1 flex flex-col md:flex-row gap-0 md:gap-4 overflow-hidden bg-white dark:bg-slate-900 md:bg-transparent rounded-none md:rounded-lg">
                
                <aside class="w-full md:w-56 lg:w-64 bg-slate-50/50 md:bg-white dark:bg-slate-900 md:dark:bg-slate-800 flex flex-col shrink-0 md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden md:h-full border-b md:border-b-0 shadow-sm">
                    <div class="hidden md:flex p-3 border-b border-slate-100 dark:border-slate-700/50 shrink-0 items-center justify-between">
                        <h3 class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Your Classes</h3>
                    </div>
                    
                    <div class="flex md:flex-col overflow-x-auto md:overflow-y-auto w-full p-1.5 md:p-2 gap-1 scrollbar-hide">
                        <button v-for="c in courses" :key="c.id" @click="selectCourse(c.id)" 
                            class="w-48 md:w-full text-left transition-colors duration-150 flex items-center justify-between group border-b-2 md:border-b-0 md:border-l-4 shrink-0 px-2 py-2 md:py-2.5 rounded-lg md:rounded-none"
                            :class="selectedCourseId === c.id 
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-600 shadow-md' 
                                : 'bg-transparent border-transparent hover:bg-slate-100 dark:hover:bg-slate-700/50'"
                        >
                            <div class="flex items-center gap-2.5 overflow-hidden w-full">
                                <div class="w-7 h-7 rounded border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 overflow-hidden text-[10px] font-black">
                                    <img v-if="c.thumbnail && !imageErrors[c.id]" :src="c.thumbnail" @error="handleImageError(c.id)" class="w-full h-full object-cover" />
                                    <span v-else>IMG</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="block truncate text-[11px] sm:text-xs" :class="selectedCourseId === c.id ? 'font-black text-blue-900 dark:text-blue-100' : 'font-bold text-slate-700 dark:text-slate-200'">{{ c.title }}</span>
                                    <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider block mt-0.5">{{ countAssignments(c, 'upcoming') }} Active</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="flex-1 bg-white dark:bg-slate-800 flex flex-col md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden h-full min-h-[400px] shadow-sm relative">

                    <div v-if="selectedCourse" class="flex flex-col h-full pt-1">
                        
                        <div class="border-b border-slate-200 dark:border-slate-700 shrink-0 bg-white dark:bg-slate-800 flex justify-between items-center px-2 sm:px-4">
                            <div class="flex overflow-x-auto scrollbar-hide">
                                <button v-for="tab in ['upcoming', 'past', 'completed']" :key="tab" @click="activeTab = tab" 
                                    class="px-3 py-3 text-[10px] sm:text-[11px] font-black uppercase tracking-widest mr-2 sm:mr-4 transition-all border-b-2 whitespace-nowrap flex items-center gap-1.5"
                                    :class="activeTab === tab ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <svg v-if="tab === 'upcoming' && activeTab === 'upcoming'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <svg v-if="tab === 'past' && activeTab === 'past'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <svg v-if="tab === 'completed' && activeTab === 'completed'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ tab }}
                                </button>
                            </div>
                            <div class="shrink-0 py-2 ml-2">
                                <select v-model="sortOrder" class="text-[9px] font-black uppercase tracking-widest text-slate-500 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded shadow-sm focus:ring-0 cursor-pointer py-1.5 pl-2 pr-6 transition">
                                    <option value="desc">Latest</option>
                                    <option value="asc">Oldest</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-900/20 p-2 sm:p-3">
                            <div v-if="filteredAssignments.length > 0" class="flex flex-col gap-2">
                                <div v-for="a in filteredAssignments" :key="a.id" @click="openDetails(a)" 
                                     class="group flex flex-col sm:flex-row sm:items-center gap-2.5 p-3 sm:p-4 bg-white dark:bg-slate-800 border-l-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:shadow-md cursor-pointer transition-all duration-200" 
                                     :class="{
                                         'border-l-blue-500 hover:border-blue-400': activeTab === 'upcoming', 
                                         'border-l-red-500 hover:border-red-400': activeTab === 'past', 
                                         'border-l-emerald-500 hover:border-emerald-400': activeTab === 'completed'
                                     }">
                                    
                                    <div class="flex items-center gap-3 min-w-0 w-full sm:flex-1">
                                        <div class="hidden sm:flex shrink-0 w-8 h-8 rounded items-center justify-center transition-colors"
                                             :class="{
                                                 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 group-hover:bg-blue-600 group-hover:text-white': activeTab === 'upcoming',
                                                 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 group-hover:bg-red-600 group-hover:text-white': activeTab === 'past',
                                                 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white': activeTab === 'completed'
                                             }">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3 sm:hidden mb-1.5">
                                                <h4 class="text-xs font-black text-slate-900 dark:text-white truncate leading-tight">{{ a.title }}</h4>
                                                <span class="font-black text-[10px] whitespace-nowrap px-1.5 py-0.5 rounded"
                                                      :class="{
                                                          'text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400': activeTab === 'upcoming', 
                                                          'text-red-600 bg-red-50 dark:bg-red-900/30 dark:text-red-400': activeTab === 'past', 
                                                          'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400': activeTab === 'completed'
                                                      }">
                                                    {{ a.points }} pts
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="hidden sm:inline-block text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded border"
                                                      :class="{
                                                          'border-blue-200 text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-800/50 dark:text-blue-400': activeTab === 'upcoming',
                                                          'border-red-200 text-red-600 bg-red-50 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400': activeTab === 'past',
                                                          'border-emerald-200 text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400': activeTab === 'completed'
                                                      }">
                                                    {{ a.type ? a.type.replace('_', ' ') : 'Task' }}
                                                </span>
                                                <h4 class="hidden sm:block text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate transition-colors"
                                                    :class="{
                                                        'group-hover:text-blue-600': activeTab === 'upcoming',
                                                        'group-hover:text-red-600': activeTab === 'past',
                                                        'group-hover:text-emerald-600': activeTab === 'completed'
                                                    }">
                                                    {{ a.title }}
                                                </h4>
                                            </div>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5 font-medium leading-relaxed">
                                                {{ a.description || 'No instructions provided.' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 sm:w-auto shrink-0 mt-1 sm:mt-0 pt-2 sm:pt-0 border-t border-slate-100 sm:border-none dark:border-slate-700/50">
                                        <div class="flex flex-col items-end">
                                            <div class="flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 px-2 py-1 rounded"
                                                 :class="{
                                                     'bg-blue-50 dark:bg-blue-900/20': activeTab === 'upcoming',
                                                     'bg-red-50 dark:bg-red-900/20': activeTab === 'past',
                                                     'bg-emerald-50 dark:bg-emerald-900/20': activeTab === 'completed'
                                                 }">
                                                <span :class="{
                                                        'text-blue-600 dark:text-blue-400': activeTab === 'upcoming',
                                                        'text-red-600 dark:text-red-400': activeTab === 'past', 
                                                        'text-emerald-600 dark:text-emerald-400': activeTab === 'completed'
                                                    }">
                                                    Due: {{ a.due_date ? new Date(a.due_date).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}) : 'None' }}
                                                </span>
                                            </div>
                                            <span v-if="a.closing_date" class="text-[8px] font-bold text-red-500 mt-0.5">Closes: {{ new Date(a.closing_date).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}) }}</span>
                                        </div>
                                        
                                        <div v-if="isClosed(a)" class="text-[9px] font-black text-red-600 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded uppercase tracking-widest border border-red-100 dark:border-red-900/50">
                                            Locked
                                        </div>

                                        <span class="hidden sm:block font-black text-[10px] w-12 text-right"
                                              :class="{'text-blue-600 dark:text-blue-400': activeTab === 'upcoming', 'text-red-600 dark:text-red-400': activeTab === 'past', 'text-emerald-600 dark:text-emerald-400': activeTab === 'completed'}">
                                            {{ a.points }} pts
                                        </span>
                                        
                                        <div v-if="activeTab === 'completed' && a.submissions[0].grade" class="hidden sm:flex text-[10px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded border border-emerald-200 dark:border-emerald-800/50">
                                            {{ a.submissions[0].grade }} / {{ a.points }}
                                        </div>
                                        
                                        <svg class="w-4 h-4 text-slate-300 transition-transform group-hover:translate-x-0.5 hidden sm:block" 
                                             :class="{
                                                 'group-hover:text-blue-600': activeTab === 'upcoming',
                                                 'group-hover:text-red-600': activeTab === 'past',
                                                 'group-hover:text-emerald-600': activeTab === 'completed'
                                             }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="flex flex-col items-center justify-center h-full p-8 text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
                                <div class="w-12 h-12 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-900 dark:text-white mb-1">You're all caught up</h3>
                                <p class="text-[9px] font-bold text-center">No {{ activeTab }} tasks found.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="flex flex-col items-center justify-center h-full p-6 text-slate-500 pt-1">
                        <svg class="w-10 h-10 mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                        <p class="text-[10px] font-black uppercase tracking-widest">Select a class to view tasks.</p>
                    </div>
                </main>
            </div>
            
            <div v-else class="flex-1 flex flex-col items-center justify-center bg-white dark:bg-slate-800 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 shadow-sm mx-1 sm:mx-0">
                <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-900 dark:text-white">No classes joined</p>
                <Link :href="route('student.courses')" class="mt-2 text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-3 py-1.5 rounded hover:bg-blue-100 transition">Join a Class</Link>
            </div>
        </div>

        <Modal :show="showDetailsModal" @close="showDetailsModal = false" maxWidth="lg">
            <div v-if="selectedAssignment" class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden flex flex-col max-h-[90vh] shadow-xl border border-slate-200 dark:border-slate-800">
                
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-start bg-slate-50 dark:bg-slate-900 shrink-0">
                    <div class="flex items-start gap-3 min-w-0 pr-4">
                        <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-black text-slate-900 dark:text-white leading-tight truncate">{{ selectedAssignment.title }}</h2>
                            <div class="flex flex-col mt-1">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500 truncate">
                                    Due: {{ formatDate(selectedAssignment.due_date) }} • {{ selectedAssignment.points }} Pts
                                </span>
                                <span v-if="selectedAssignment.closing_date" class="text-[8px] font-bold uppercase tracking-widest text-red-500 truncate mt-0.5">
                                    Closes: {{ formatDate(selectedAssignment.closing_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white bg-white dark:bg-slate-800 rounded-full p-1 shadow-sm border border-slate-200 dark:border-slate-700 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="flex flex-col md:flex-row flex-1 overflow-y-auto md:overflow-hidden">
                    
                    <div class="flex-1 p-4 overflow-y-auto custom-scrollbar md:border-r border-slate-100 dark:border-slate-800">
                        <div class="mb-4">
                            <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Instructions</h3>
                            <div class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-100 dark:border-slate-800" v-html="linkify(selectedAssignment.description || 'None')"></div>
                        </div>
                        
                        <div v-if="selectedAssignment.attachment_paths && selectedAssignment.attachment_paths !== 'null'">
                            <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Resources</h3>
                            <div class="flex flex-col gap-2">
                                <template v-if="Array.isArray(JSON.parse(selectedAssignment.attachment_paths))">
                                    <div v-for="(path, i) in JSON.parse(selectedAssignment.attachment_paths)" :key="i" class="px-3 py-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-between shadow-sm">
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2 truncate pr-2">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                            Resource {{ i + 1 }}
                                        </span>
                                        <div class="flex gap-1 shrink-0">
                                            <a :href="`/storage/${path}`" target="_blank" title="View" class="p-1.5 text-slate-400 hover:text-blue-500 bg-slate-50 hover:bg-blue-50 dark:bg-slate-900 dark:hover:bg-blue-900/30 rounded transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <a :href="`/storage/${path}`" download title="Download" class="p-1.5 text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 rounded transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-80 bg-slate-50 dark:bg-slate-900 p-4 shrink-0 flex flex-col overflow-y-auto">
                        <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> 
                            Your Work
                        </h3>
                        
                        <div v-if="selectedAssignment.submissions && selectedAssignment.submissions[0]" class="flex flex-col gap-3">
                            <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 block mb-2 ml-2">Turned in on {{ new Date(selectedAssignment.submissions[0].submitted_at).toLocaleDateString() }}</span>
                                
                                <div v-if="selectedAssignment.submissions[0].text_content" class="mb-3 ml-2">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Your Note:</p>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 italic">"{{ selectedAssignment.submissions[0].text_content }}"</p>
                                </div>

                                <div v-if="selectedAssignment.submissions[0].file_paths" class="space-y-1.5 ml-2">
                                    <div v-for="(path, index) in getPaths(selectedAssignment.submissions[0].file_paths)" :key="index" class="flex justify-between items-center text-[10px] font-bold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-2 rounded border border-slate-100 dark:border-slate-800">
                                        <span class="truncate pr-2">File {{ index + 1 }}</span>
                                        <div class="flex gap-1 shrink-0">
                                            <a :href="`/storage/${path}`" target="_blank" class="p-1 hover:text-blue-500 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a>
                                            <a :href="`/storage/${path}`" download class="p-1 text-blue-500 hover:text-blue-700 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedAssignment.submissions[0].grade" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 rounded-lg text-xs text-slate-700 dark:text-slate-300 shadow-sm">
                                <strong class="text-[9px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400 block mb-1">Score: {{ selectedAssignment.submissions[0].grade }}/{{ selectedAssignment.points }}</strong>
                                <p class="italic mt-1 text-[11px]">"{{ selectedAssignment.submissions[0].feedback }}"</p>
                            </div>
                            <div v-else-if="!isClosed(selectedAssignment)" class="flex justify-end mt-2">
                                <button @click="undoTurnIn" class="w-full text-red-600 text-[9px] font-black uppercase tracking-widest border border-red-200 dark:border-red-800/50 bg-white dark:bg-slate-800 px-4 py-2.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition shadow-sm">Undo Turn In</button>
                            </div>
                        </div>
                        
                        <form v-else-if="!isClosed(selectedAssignment)" @submit.prevent="submitWork" class="flex flex-col h-full space-y-3">
                            
                            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                                <textarea v-model="formSubmission.text_content" class="w-full border-none bg-transparent text-xs p-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 resize-none min-h-[80px]" placeholder="Type your answer or a note here (optional)..."></textarea>
                            </div>

                            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 text-center relative hover:border-blue-400 dark:hover:border-blue-500 transition cursor-pointer group shadow-sm">
                                <input type="file" multiple @change="handleFileSelect" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                <svg class="w-6 h-6 mx-auto text-slate-400 group-hover:text-blue-500 mb-1 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest group-hover:text-blue-600 transition-colors">Select multiple files</p>
                            </div>

                            <div v-if="formSubmission.files.length" class="space-y-1.5 max-h-32 overflow-y-auto custom-scrollbar bg-slate-100 dark:bg-slate-800/50 p-2 rounded-lg border border-slate-200 dark:border-slate-700">
                                <div v-for="(f, i) in formSubmission.files" :key="i" class="text-[9px] font-bold uppercase tracking-widest bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2 py-1.5 rounded flex items-center justify-between shadow-sm text-slate-600 dark:text-slate-300">
                                    <span class="truncate pr-2 flex-1">{{ f.name }}</span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-slate-400">{{ formatSize(f.size) }}</span>
                                        <button type="button" @click.prevent="removeFile(i)" class="text-red-500 hover:text-red-700 p-0.5 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center px-1 mt-auto shrink-0">
                                <span class="text-[9px] font-black uppercase tracking-widest" :class="isOverSizeLimit ? 'text-red-600' : 'text-slate-400'">
                                    Total Size: {{ formatSize(totalFileSize) }} / 15 MB
                                </span>
                            </div>

                            <div class="pt-2 shrink-0">
                                <button :disabled="formSubmission.processing || isOverSizeLimit || (formSubmission.files.length === 0 && !formSubmission.text_content.trim())" 
                                    class="w-full bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest py-2.5 rounded-lg shadow-sm hover:bg-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ isOverSizeLimit ? 'Size Limit Exceeded' : 'Turn In Task' }}
                                </button>
                            </div>
                        </form>

                        <div v-else class="text-center py-4 bg-red-50 dark:bg-red-900/20 rounded-lg mt-2 border border-red-200 dark:border-red-800/50 shadow-sm">
                            <span class="text-[10px] font-black text-red-600 dark:text-red-500 uppercase tracking-widest flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Final Deadline Passed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 10px;
}
</style>