<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { BookOpen, ClipboardList, Plus, ChevronRight, Clock, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    courses: Array
});

const selectedCourseId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const activeTab = ref('upcoming');
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

const filteredAssignments = computed(() => {
    if (!selectedCourse.value) return [];
    const now = new Date();
    return selectedCourse.value.assignments.filter(assignment => {
        const dueDate = assignment.due_date ? new Date(assignment.due_date) : null;
        return activeTab.value === 'upcoming' ? (!dueDate || dueDate >= now) : (dueDate && dueDate < now);
    });
});
</script>

<template>
    <Head title="Assignments" />

    <AuthenticatedLayout>
        <div class="h-full md:h-[calc(100vh-80px)] flex flex-col max-w-screen-2xl mx-auto -mt-2">
            
            <div class="mb-4 shrink-0 px-2 sm:px-0">
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <ClipboardList class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    Assignments
                </h1>
            </div>

            <div class="flex-1 flex flex-col md:flex-row gap-0 md:gap-6 overflow-hidden bg-white dark:bg-slate-900 md:bg-transparent rounded-none md:rounded-lg">
                
                <aside class="w-full md:w-64 lg:w-72 bg-slate-50/50 md:bg-white dark:bg-slate-900 md:dark:bg-slate-800 flex flex-col shrink-0 md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden md:h-full border-b md:border-b-0">
                    <div class="hidden md:flex p-4 border-b border-slate-100 dark:border-slate-700/50 shrink-0 items-center justify-between">
                        <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Your Classes</h3>
                    </div>
                    
                    <div class="flex md:flex-col overflow-y-auto w-full p-0 md:py-2">
                        <button 
                            v-for="course in courses" 
                            :key="course.id"
                            @click="selectCourse(course.id)"
                            class="md:w-full text-left transition-colors duration-150 flex items-center justify-between group border-b-2 md:border-b-0 md:border-l-4 shrink-0 px-3 py-2.5 md:py-3 md:px-4"
                            :class="selectedCourseId === course.id 
                                ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-600' 
                                : 'bg-transparent border-transparent hover:bg-slate-100 dark:hover:bg-slate-700/50'"
                        >
                            <div class="flex items-center gap-3 overflow-hidden w-full">
                                <div class="w-8 h-8 rounded bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold">
                                    <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                         :src="course.thumbnail" 
                                         @error="handleImageError(course.id)"
                                         class="w-full h-full object-cover" />
                                    <span v-else>{{ course.title.substring(0, 2).toUpperCase() }}</span>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <span class="block truncate text-sm" 
                                          :class="selectedCourseId === course.id ? 'font-semibold text-indigo-900 dark:text-indigo-100' : 'font-medium text-slate-700 dark:text-slate-200'">
                                        {{ course.title }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                            {{ countUpcoming(course.assignments) }} active
                                        </span>
                                        <span v-if="course.ungraded_count > 0" class="flex items-center gap-1 text-red-600 dark:text-red-400 text-[10px] font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                            {{ course.ungraded_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="flex-1 bg-white dark:bg-slate-800 flex flex-col md:border border-slate-200 dark:border-slate-700 md:rounded-lg overflow-hidden h-full min-h-[500px]">
                    
                    <div v-if="selectedCourse" class="flex flex-col h-full">
                        
                        <div class="border-b border-slate-200 dark:border-slate-700 shrink-0 bg-white dark:bg-slate-800">
                            <div class="px-4 py-3 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ selectedCourse.title }}
                                </h2>
                                <Link :href="route('teacher.assignments.create', { course: selectedCourseId, source: 'global' })" 
                                      class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5 shadow-sm">
                                    <Plus class="w-4 h-4" />
                                    <span class="hidden sm:inline">New Assignment</span>
                                    <span class="sm:hidden">New</span>
                                </Link>
                            </div>

                            <div class="flex px-4 overflow-x-auto scrollbar-hide">
                                <button @click="activeTab = 'upcoming'"
                                    class="px-1 py-3 text-sm font-medium mr-6 transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                                    :class="activeTab === 'upcoming' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <Clock class="w-4 h-4" v-if="activeTab === 'upcoming'"/>
                                    Upcoming
                                </button>
                                <button @click="activeTab = 'past'"
                                    class="px-1 py-3 text-sm font-medium mr-6 transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                                    :class="activeTab === 'past' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <AlertCircle class="w-4 h-4" v-if="activeTab === 'past'"/>
                                    Past Due
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-900/20">
                            <div v-if="filteredAssignments.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                
                                <Link v-for="assignment in filteredAssignments" :key="assignment.id" 
                                      :href="route('teacher.assignments.show', { assignment: assignment.id, source: 'global' })"
                                      class="group flex flex-col sm:flex-row sm:items-center gap-3 p-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer">
                                    
                                    <div class="hidden sm:flex shrink-0 w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 items-center justify-center">
                                        <ClipboardList class="w-5 h-5" />
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4 sm:hidden mb-2">
                                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                {{ assignment.title }}
                                            </h4>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-xs whitespace-nowrap">
                                                {{ assignment.points }} pts
                                            </span>
                                        </div>
                                        <h4 class="hidden sm:block text-sm font-semibold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 transition-colors">
                                            {{ assignment.title }}
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                            {{ assignment.description || 'No instructions provided.' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-auto shrink-0 mt-2 sm:mt-0">
                                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span class="font-medium" :class="{'text-red-500': activeTab === 'past'}">
                                                {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}) : 'No due date' }}
                                            </span>
                                        </div>
                                        <span class="hidden sm:block text-slate-700 dark:text-slate-300 font-medium text-xs w-12 text-right">
                                            {{ assignment.points }} pts
                                        </span>
                                        <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 transition-colors hidden sm:block" />
                                    </div>
                                </Link>

                            </div>
                            
                            <div v-else class="flex flex-col items-center justify-center h-full p-8 text-slate-500">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                                    <CheckCircle2 class="w-8 h-8 text-slate-400" />
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">You're all caught up</h3>
                                <p class="text-xs text-center">No {{ activeTab }} assignments found for this class.</p>
                            </div>
                        </div>

                    </div>
                    
                    <div v-else class="flex flex-col items-center justify-center h-full p-6 text-slate-500">
                        <BookOpen class="w-12 h-12 mb-4 text-slate-300 dark:text-slate-600" />
                        <p class="text-sm font-medium">Select a class from the sidebar to view tasks.</p>
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