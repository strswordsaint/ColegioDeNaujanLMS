<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ClipboardList, Plus, ChevronRight, Clock, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    courses: Array
});

const selectedCourseId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const activeTab = ref('upcoming');
const sortOrder = ref('desc'); // NEW: Sort order state (desc = Latest, asc = Oldest)
const imageErrors = ref({});

const selectCourse = (id) => { selectedCourseId.value = id; };
const handleImageError = (id) => { imageErrors.value[id] = true; };
const selectedCourse = computed(() => props.courses.find(c => c.id === selectedCourseId.value));

const countUpcoming = (assignments) => {
    if (!assignments) return 0;
    const now = new Date();
    return assignments.filter(a => {
        const dueDate = a.due_date ? new Date(a.due_date) : null;
        return !dueDate || dueDate >= now;
    }).length;
};

// UPDATED: Now includes sorting logic based on sortOrder
const filteredAssignments = computed(() => {
    if (!selectedCourse.value) return [];
    const now = new Date();
    
    let filtered = selectedCourse.value.assignments.filter(assignment => {
        const dueDate = assignment.due_date ? new Date(assignment.due_date) : null;
        const isPastDue = dueDate && dueDate < now;
        
        if (activeTab.value === 'upcoming') return !isPastDue;
        if (activeTab.value === 'past') return isPastDue;
        return true;
    });

    filtered.sort((a, b) => {
        const dateA = new Date(a.created_at || 0).getTime();
        const dateB = new Date(b.created_at || 0).getTime();
        return sortOrder.value === 'desc' ? dateB - dateA : dateA - dateB;
    });

    return filtered;
});

// Check if the hard deadline has passed
const isClosed = (assignment) => {
    if (!assignment || !assignment.closing_date) return false;
    const closingTime = new Date(assignment.closing_date).getTime();
    const currentTime = new Date().getTime();
    return currentTime > closingTime;
};
</script>

<template>
    <Head title="Assignments" />

    <AuthenticatedLayout>
        <div class="h-full md:h-[calc(100vh-80px)] flex flex-col max-w-screen-2xl mx-auto -mt-2">
            
            <div class="mb-3 shrink-0 px-2 sm:px-0 flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <ClipboardList class="w-6 h-6 text-blue-600 dark:text-blue-500" />
                        Assignments
                    </h1>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5 ml-8">Manage tasks across your classes</p>
                </div>
                
                <Link v-if="selectedCourseId" :href="route('teacher.assignments.create', { course: selectedCourseId, source: 'global' })" 
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-[10px] sm:text-xs font-black uppercase tracking-widest flex items-center gap-1.5 transition-colors shadow-sm">
                    <Plus class="w-4 h-4" />
                    <span class="hidden sm:inline">New Task</span>
                    <span class="sm:hidden">Create</span>
                </Link>
            </div>

            <div class="flex-1 flex flex-col md:flex-row gap-0 md:gap-4 overflow-hidden bg-white dark:bg-slate-900 md:bg-transparent rounded-none md:rounded-lg">
                
                <aside class="w-full md:w-56 lg:w-64 bg-slate-50/50 md:bg-white dark:bg-slate-900 md:dark:bg-slate-800 flex flex-col shrink-0 md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden md:h-full border-b md:border-b-0 shadow-sm">
                    <div class="hidden md:flex p-3 border-b border-slate-100 dark:border-slate-700/50 shrink-0 items-center justify-between">
                        <h3 class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Your Classes</h3>
                    </div>
                    
                    <div class="flex md:flex-col overflow-x-auto md:overflow-y-auto w-full p-1.5 md:p-2 gap-1 scrollbar-hide">
                        <button 
                            v-for="course in courses" 
                            :key="course.id"
                            @click="selectCourse(course.id)"
                            class="w-48 md:w-full text-left transition-colors duration-150 flex items-center justify-between group border-b-2 md:border-b-0 md:border-l-4 shrink-0 px-2 py-2 md:py-2.5 rounded-lg md:rounded-none"
                            :class="selectedCourseId === course.id 
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-600' 
                                : 'bg-transparent border-transparent hover:bg-slate-100 dark:hover:bg-slate-700/50'"
                        >
                            <div class="flex items-center gap-2.5 overflow-hidden w-full">
                                <div class="w-7 h-7 rounded border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 overflow-hidden text-[10px] font-black uppercase">
                                    <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                         :src="course.thumbnail" 
                                         @error="handleImageError(course.id)"
                                         class="w-full h-full object-cover" />
                                    <span v-else>{{ course.title.substring(0, 2) }}</span>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <span class="block truncate text-[11px] sm:text-xs" 
                                          :class="selectedCourseId === course.id ? 'font-black text-blue-900 dark:text-blue-100' : 'font-bold text-slate-700 dark:text-slate-200'">
                                        {{ course.title }}
                                    </span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            {{ countUpcoming(course.assignments) }} active
                                        </span>
                                        <span v-if="course.ungraded_count > 0" class="flex items-center gap-1 text-red-600 dark:text-red-400 text-[9px] font-black">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                            {{ course.ungraded_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="flex-1 bg-white dark:bg-slate-800 flex flex-col md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden h-full min-h-[400px] shadow-sm relative">
                    
                    <div v-if="selectedCourse" class="flex flex-col h-full pt-1">
                        
                        <div class="border-b border-slate-200 dark:border-slate-700 shrink-0 bg-white dark:bg-slate-800 flex justify-between items-center px-2 sm:px-4">
                            <div class="flex overflow-x-auto scrollbar-hide">
                                <button @click="activeTab = 'upcoming'"
                                    class="px-3 py-3 text-[10px] font-black uppercase tracking-widest mr-2 sm:mr-6 transition-all border-b-2 whitespace-nowrap flex items-center gap-1.5"
                                    :class="activeTab === 'upcoming' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <Clock class="w-3.5 h-3.5" v-if="activeTab === 'upcoming'"/>
                                    Upcoming
                                </button>
                                <button @click="activeTab = 'past'"
                                    class="px-3 py-3 text-[10px] font-black uppercase tracking-widest mr-2 sm:mr-6 transition-all border-b-2 whitespace-nowrap flex items-center gap-1.5"
                                    :class="activeTab === 'past' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <AlertCircle class="w-3.5 h-3.5" v-if="activeTab === 'past'"/>
                                    Past Due
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
                                
                                <Link v-for="assignment in filteredAssignments" :key="assignment.id" 
                                      :href="route('teacher.assignments.show', { assignment: assignment.id, source: 'global' })"
                                      class="group flex flex-col sm:flex-row sm:items-center gap-2.5 p-3 sm:p-4 bg-white dark:bg-slate-800 border-l-4 border border-slate-200 dark:border-slate-700 rounded-lg transition-all duration-200"
                                      :class="activeTab === 'upcoming' ? 'border-l-blue-500 hover:border-blue-400 hover:shadow-md' : 'border-l-red-500 hover:border-red-400 hover:shadow-md'">
                                    
                                    <div class="hidden sm:flex shrink-0 w-8 h-8 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 items-center justify-center transition-colors"
                                         :class="activeTab === 'upcoming' ? 'group-hover:bg-blue-600 group-hover:text-white' : 'bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white'">
                                        <ClipboardList class="w-4 h-4" />
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3 sm:hidden mb-1.5">
                                            <h4 class="text-xs font-black text-slate-900 dark:text-white truncate leading-tight">
                                                {{ assignment.title }}
                                            </h4>
                                            <span class="text-[10px] font-black whitespace-nowrap bg-slate-100 dark:bg-slate-900/50 px-1.5 py-0.5 rounded"
                                                  :class="activeTab === 'upcoming' ? 'text-blue-600' : 'text-red-600'">
                                                {{ assignment.points }} pts
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="hidden sm:inline-block text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-slate-500 bg-slate-50 dark:bg-slate-900">
                                                {{ assignment.type ? assignment.type.replace('_', ' ') : 'Task' }}
                                            </span>
                                            <h4 class="hidden sm:block text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate transition-colors"
                                                :class="activeTab === 'upcoming' ? 'group-hover:text-blue-600' : 'group-hover:text-red-600'">
                                                {{ assignment.title }}
                                            </h4>
                                        </div>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate font-medium leading-relaxed">
                                            {{ assignment.description || 'No instructions provided.' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 sm:w-auto shrink-0 mt-2 sm:mt-0 pt-2 sm:pt-0 border-t border-slate-100 sm:border-none dark:border-slate-700/50">
                                        <div class="flex items-center gap-1 text-[9px] font-black uppercase tracking-widest">
                                            <span :class="activeTab === 'past' ? 'text-red-600' : 'text-slate-500'">
                                                Due: {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}) : 'No Date' }}
                                            </span>
                                        </div>
                                        
                                        <div v-if="isClosed(assignment)" class="text-[9px] font-black text-red-600 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded uppercase tracking-widest border border-red-100 dark:border-red-900/50">
                                            Locked
                                        </div>

                                        <span class="hidden sm:block text-slate-700 dark:text-slate-300 font-black text-[10px] w-12 text-right">
                                            {{ assignment.points }} pts
                                        </span>
                                        <ChevronRight class="w-4 h-4 text-slate-300 transition-transform group-hover:translate-x-0.5 hidden sm:block" 
                                                      :class="activeTab === 'upcoming' ? 'group-hover:text-blue-600' : 'group-hover:text-red-600'"/>
                                    </div>
                                </Link>

                            </div>
                            
                            <div v-else class="flex flex-col items-center justify-center h-full p-8 text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
                                <div class="w-12 h-12 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mb-3">
                                    <CheckCircle2 class="w-6 h-6 text-slate-300 dark:text-slate-500" />
                                </div>
                                <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-900 dark:text-white mb-1">You're all caught up</h3>
                                <p class="text-[9px] font-bold text-center">No {{ activeTab }} assignments found.</p>
                            </div>
                        </div>

                    </div>
                    
                    <div v-else class="flex flex-col items-center justify-center h-full p-6 text-slate-500 pt-1">
                        <ClipboardList class="w-10 h-10 mb-3 text-slate-300 dark:text-slate-600" />
                        <p class="text-[10px] font-black uppercase tracking-widest">Select a class to view tasks.</p>
                    </div>

                </main>
            </div>
        </div>
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
</style>